<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc\Request;

use Crayxn\ServiceGovernanceNacosGrpc\Model\Instance;
use Hyperf\Nacos\Protobuf\Request\RequestInterface;

class InstanceRequest implements RequestInterface
{
    public function __construct(private Instance $instance, private string $serviceName, private string $namespace = '', private string $groupName = 'DEFAULT_GROUP', private string $type = '')
    {
    }

    public function getValue(): array
    {
        return [
            'namespace' => $this->namespace,
            'serviceName' => $this->serviceName,
            'groupName' => $this->groupName,
            'module' => "naming",
            'type' => $this->type,
            'instance' => (array)$this->instance
        ];
    }

    public function getType(): string
    {
        return 'InstanceRequest';
    }

    public function getKey(): string
    {
        return $this->namespace . '@@' . $this->groupName . '@@' . $this->serviceName;
    }
}