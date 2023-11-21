<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc;

use Crayxn\ServiceGovernanceNacosGrpc\Response\QueryServiceResponse;
use Exception;
use Hyperf\Codec\Json;
use Hyperf\Collection\Arr;
use Hyperf\Contract\IPReaderInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Coordinator\Constants;
use Hyperf\Coordinator\CoordinatorManager;
use Hyperf\Engine\Http\V2\Request;
use Hyperf\Grpc\Parser;
use Hyperf\Http2Client\Client;
use Hyperf\Nacos\Application;
use Hyperf\Nacos\Config;
use Hyperf\Nacos\Exception\ConnectToServerFailedException;
use Hyperf\Nacos\Exception\RequestException;
use Hyperf\Nacos\Protobuf\Any;
use Hyperf\Nacos\Protobuf\Metadata;
use Hyperf\Nacos\Protobuf\Payload;
use Hyperf\Nacos\Protobuf\Request\HealthCheckRequest;
use Hyperf\Nacos\Protobuf\Request\RequestInterface;
use Hyperf\Nacos\Protobuf\Request\ServerCheckRequest;
use Hyperf\Nacos\Protobuf\Response\Mapping;
use Hyperf\Nacos\Protobuf\Response\Response;
use Hyperf\Nacos\Provider\AccessToken;
use Hyperf\Support\Network;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Crayxn\ServiceGovernanceNacosGrpc\ListenHandler\SubscriberNotifyHandler;
use Crayxn\ServiceGovernanceNacosGrpc\Request\ConnectionSetupRequest;
use Crayxn\ServiceGovernanceNacosGrpc\Request\SubscribeServiceRequest;
use Crayxn\ServiceGovernanceNacosGrpc\Response\NotifySubscriberRequest;
use function Hyperf\Coroutine\go;

class GrpcClient
{
    use AccessToken;

    protected ?Client $client = null;

    protected ?LoggerInterface $logger = null;

    protected Application $app;

    public int $streamId;

    private array $mapping = [];

    /**
     * @var array
     */
    private array $subscriber = [];

    private SubscriberNotifyHandler $subscribeNotifyHandler;

    public function __construct(
        protected ContainerInterface $container,
        protected Config             $config,
        protected string             $namespaceId = '',
        protected string             $clientAppName = '',
    )
    {
        if ($this->container->has(StdoutLoggerInterface::class)) {
            $this->logger = $this->container->get(StdoutLoggerInterface::class);
        }
        if ($this->container->has(Application::class)) {
            $this->app = $this->container->get(Application::class);
        }

        $this->subscribeNotifyHandler = $this->container->get(SubscriberNotifyHandler::class);

        $this->mapping = array_merge(Mapping::$mappings, [
            //more naming
            'NotifySubscriberRequest' => NotifySubscriberRequest::class,
            'QueryServiceResponse' => QueryServiceResponse::class,
        ]);
    }

    public function request(RequestInterface $request, ?Client $client = null): Response
    {
        // collect subscribe request
        if ($request instanceof SubscribeServiceRequest) {
            $this->subscriber[$request->getKey()] = $request;
        }

        $payload = new Payload([
            'metadata' => new Metadata($this->getMetadata($request)),
            'body' => new Any([
                'value' => Json::encode($request->getValue()),
            ]),
        ]);

        if (!$client) {
            if (!$this->client) {
                $this->reconnect();
            }
            $client = $this->client;
        }

        $response = $client->request(
            new Request('/Request/request', 'POST', Parser::serializeMessage($payload), $this->grpcDefaultHeaders())
        );

        return $this->toResponse($response->getBody());
    }

    public function write(int $streamId, RequestInterface $request, ?Client $client = null): bool
    {
        $payload = new Payload([
            'metadata' => new Metadata($this->getMetadata($request)),
            'body' => new Any([
                'value' => Json::encode($request->getValue()),
            ]),
        ]);

        $client ??= $this->client;

        return $client->write($streamId, Parser::serializeMessage($payload));
    }

    protected function reconnect(): void
    {
        $this->client && $this->client->close();
        $this->client = new Client(
            $this->config->getHost() . ':' . ($this->config->getPort() + 1000),
            [
                'heartbeat' => null,
            ]
        );
        if ($this->logger) {
            $this->client->setLogger($this->logger);
        }

        $this->serverCheck();
        $this->streamId = $this->bindStreamCall();
        $this->healthCheck();
    }

    protected function healthCheck(): void
    {
        go(function () {
            $client = $this->client;
            $heartbeat = $this->config->getGrpc()['heartbeat'];
            while ($heartbeat > 0 && $client->inLoop()) {
                if (CoordinatorManager::until(Constants::WORKER_EXIT)->yield($heartbeat)) {
                    break;
                }
                $res = $this->request(new HealthCheckRequest(), $client);
                if ($res->errorCode !== 0) {
                    $this->logger?->error('Health check failed, the result is ' . (string)$res);
                }
            }
        });
    }

    protected function ip(): string
    {
        if ($this->container->has(IPReaderInterface::class)) {
            return $this->container->get(IPReaderInterface::class)->read();
        }

        return Network::ip();
    }

    protected function bindStreamCall(): int
    {
        $id = $this->client->send(new Request('/BiRequestStream/requestBiStream', 'POST', '', $this->grpcDefaultHeaders(), true));
        go(function () use ($id) {
            $client = $this->client;
            while (true) {
                try {
                    if (!$client->inLoop()) {
                        break;
                    }
                    $response = $client->recv($id, -1);
                    $response = $this->toResponse($response->getBody());
                    // handle subscriber notify
                    if ($response instanceof NotifySubscriberRequest) {
                        $this->subscribeNotifyHandler->handle($response);
                        $this->write($id, $this->subscribeNotifyHandler->ack($response));
                    }
                } catch (Throwable $e) {
                    !$this->isWorkerExit() && $this->logger->error((string)$e);
                }
            }

            if (!$this->isWorkerExit()) {
                $this->reconnect();
                $this->resubscribe();
            }
        });

        $request = new ConnectionSetupRequest('naming', $this->namespaceId, $this->clientAppName);
        $this->write($id, $request);
        //wait
        sleep(1);

        return $id;
    }

    protected function serverCheck(): bool
    {
        $request = new ServerCheckRequest();

        while (true) {
            try {
                $response = $this->request($request);
                if ($response->errorCode !== 0) {
                    $this->logger?->error('Nacos check server failed.');
                    if (CoordinatorManager::until(Constants::WORKER_EXIT)->yield(5)) {
                        break;
                    }
                    continue;
                }

                return true;
            } catch (Exception $exception) {
                $this->logger?->error((string)$exception);
                if (CoordinatorManager::until(Constants::WORKER_EXIT)->yield(5)) {
                    break;
                }
            }
        }

        throw new ConnectToServerFailedException('the nacos server is not ready to work in 30 seconds, connect to server failed');
    }

    private function isWorkerExit(): bool
    {
        return CoordinatorManager::until(Constants::WORKER_EXIT)->isClosing();
    }

    private function getMetadata(RequestInterface $request): array
    {
        $metadata = [
            'type' => $request->getType(),
            'clientIp' => $this->ip(),
            'headers' => [
                'app' => $this->clientAppName
            ]
        ];

        if (!empty($this->config->getAccessKey()) && !empty($this->config->getAccessSecret())) {
            $metadata['headers']['data'] = $this->getStringToSign($request);
            $metadata['headers']['ak'] = $this->config->getAccessKey();
            $metadata['headers']['signature'] = base64_encode(hash_hmac('sha1', $metadata['headers']['data'], $this->config->getAccessSecret(), true));
        }

        if ($token = $this->getAccessToken()) {
            $metadata['headers']['accessToken'] = $token;
        }

        return $metadata;
    }

    private function getStringToSign(RequestInterface $request): string
    {
        $serviceName = $request->getValue()['serviceName'] ?? '';
        $groupName = $request->getValue()['groupName'] ?? '';

        $signStr = round(microtime(true) * 1000);

        if (!empty($serviceName) && !empty($groupName)) {
            $signStr .= "@@{$groupName}@@{$serviceName}";
        }
        return $signStr;
    }

    private function grpcDefaultHeaders(): array
    {
        return [
            'content-type' => 'application/grpc+proto',
            'te' => 'trailers',
            'user-agent' => 'Nacos-Kyy-Client:v3.0'
        ];
    }

    private function toResponse(mixed $data): Response
    {
        /** @var Payload $payload */
        $payload = Parser::deserializeMessage([Payload::class, 'decode'], $data);

        $json = Json::decode($payload->getBody()->getValue());
        $class = $this->mapping[$payload->getMetadata()->getType()] ?? null;
        if (!$class) {
            return new Response(...Arr::only($json, ['resultCode', 'errorCode', 'success', 'message', 'requestId']));
        }

        /* @phpstan-ignore-next-line */
        return new $class($json);
    }

    private function resubscribe(): void
    {
        if (!empty($this->subscriber)) {
            foreach ($this->subscriber as $subscriber) {
                $subscriber instanceof RequestInterface && $this->request($subscriber);
            }
        }
    }

    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $contents = (string)$response->getBody();

        if ($statusCode !== 200) {
            throw new RequestException($contents, $statusCode);
        }

        return Json::decode($contents);
    }
}