<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc;

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

    protected InstanceManager $instanceManager;

    private string $groupName;
    private string $namespaceId;

    private array $subscribed = [];

    private array $registered = [];


    public function __construct(protected ContainerInterface $container)
    {
        $this->client = $container->get(GrpcClient::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->config = $container->get(ConfigInterface::class);
        $this->instanceManager = $this->container->get(InstanceManager::class);

        $this->groupName = $this->config->get('services.drivers.nacos.group_name', 'DEFAULT_GROUP');
        $this->namespaceId = $this->config->get('services.drivers.nacos.namespace_id', '');
    }

    public function getNodes(string $uri, string $name, array $metadata): array
    {
        $serviceKey = "{$this->groupName}@@{$name}";
        //check subscribe
        if (!array_key_exists($serviceKey, $this->subscribed)) {
            //todo query service

            //subscribe
            $response = $this->client->request(new SubscribeServiceRequest($name, true, $this->namespaceId, $this->groupName));
            if ($response->success) {
                $this->subscribed[$serviceKey] = true;
            }
        }
        return $this->instanceManager->getInstances($serviceKey);
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