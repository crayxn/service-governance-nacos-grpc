<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc;

use Crayxn\ServiceGovernanceNacosGrpc\Request\ServiceQueryRequest;
use Crayxn\ServiceGovernanceNacosGrpc\Response\QueryServiceResponse;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ServiceGovernance\DriverInterface;
use Hyperf\ServiceGovernanceNacos\Client;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Crayxn\ServiceGovernanceNacosGrpc\Request\SubscribeServiceRequest;

class NacosGrpcDriver implements DriverInterface
{

    protected GrpcClient $client;

    protected LoggerInterface $logger;

    protected ConfigInterface $config;

    private string $groupName;
    private string $namespaceId;

    private array $subscribed = [];

    private array $registered = [];


    public function __construct(protected ContainerInterface $container)
    {
        $this->client = $container->get(GrpcClient::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->config = $container->get(ConfigInterface::class);

        $this->groupName = $this->config->get('services.drivers.nacos.group_name', 'DEFAULT_GROUP');
        $this->namespaceId = $this->config->get('services.drivers.nacos.namespace_id', '');
    }

    public function getNodes(string $uri, string $name, array $metadata): array
    {
        $serviceKey = "{$this->groupName}@@{$name}";
        $nodes = [];
        /**
         * query service
         * @var ?QueryServiceResponse $queryServiceResponse
         */
        $queryServiceResponse = $this->client->request(
            new ServiceQueryRequest($name, true, $this->namespaceId, $this->groupName)
        );
        if ($queryServiceResponse instanceof QueryServiceResponse) {
            $nodes = array_map(fn($item) => [
                'host' => $item['ip'],
                'port' => $item['port'],
                'weight' => intval(100 * ($item['weight'] ?? 1)),
            ], $queryServiceResponse->serviceInfo['hosts'] ?? []);
        }
        //check subscribe
        if (!empty($nodes) && !array_key_exists($serviceKey, $this->subscribed)) {
            //subscribe
            $response = $this->client->request(new SubscribeServiceRequest($name, true, $this->namespaceId, $this->groupName));
            if ($response->success) {
                $this->subscribed[$serviceKey] = true;
            }
        }
        return $nodes;
    }

    public function register(string $name, string $host, int $port, array $metadata): void
    {
        // TODO: Implement register() method.
    }

    public function isRegistered(string $name, string $host, int $port, array $metadata): bool
    {
        // TODO: Implement isRegistered() method.
    }
}