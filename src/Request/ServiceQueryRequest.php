<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc\Request;

use Hyperf\Nacos\Protobuf\Request\RequestInterface;

class ServiceQueryRequest implements RequestInterface
{
    public function __construct(private string $serviceName, private bool $healthyOnly = false, private string $namespace = '', private string $groupName = 'DEFAULT_GROUP')
    {
    }

    public function getValue(): array
    {
        return [
            'namespace' => $this->namespace,
            'serviceName' => $this->serviceName,
            'groupName' => $this->groupName,
            'module' => "naming",
            'cluster' => '',
            'healthyOnly' => $this->healthyOnly,
            'udpPort' => 0
        ];
    }

    public function getType(): string
    {
        return "ServiceQueryRequest";
    }
}