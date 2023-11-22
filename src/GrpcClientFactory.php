<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Nacos\Config;
use Psr\Container\ContainerInterface;

class GrpcClientFactory
{
    public function __invoke(ContainerInterface $container): GrpcClient
    {
        $config = $container->get(ConfigInterface::class);
        $nacosConfig = $config->get('services.drivers.nacos', []);

        return new GrpcClient($container, new Config([
            'base_uri' => "http://{$nacosConfig['host']}:{$nacosConfig['port']}/",
            'host' => $nacosConfig['host'] ?? '127.0.0.1',
            'port' => $nacosConfig['port'] ?? 8848,
            'username' => $nacosConfig['username'] ?? null,
            'password' => $nacosConfig['password'] ?? null,
            'guzzle_config' => $nacosConfig['guzzle']['config'] ?? null,
            'access_key' => $nacosConfig['access_key'] ?? null,
            'access_secret' => $nacosConfig['access_secret'] ?? null,
        ]), $nacosConfig['namespace_id'] ?? '', $config->get('app_name', 'KYY'));
    }
}