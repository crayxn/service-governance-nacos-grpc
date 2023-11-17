<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc\Request;

use Hyperf\Nacos\Protobuf\Request\RequestInterface;

class SubscribeServiceRequest implements RequestInterface
{
    public function __construct(public string $serviceName, public bool $subscribe = true, public string $namespace = '', public string $groupName = 'DEFAULT_GROUP')
    {
    }

    public function getValue(): array
    {
        return [
            'namespace' => $this->namespace,
            'serviceName' => $this->serviceName,
            'groupName' => $this->groupName,
            'module' => "naming",
            'subscribe' => $this->subscribe,
        ];
    }

    public function getType(): string
    {
        return "SubscribeServiceRequest";
    }

    public function getKey(): string
    {
        return $this->namespace . '@@' . $this->groupName . '@@' . $this->serviceName;
    }
}