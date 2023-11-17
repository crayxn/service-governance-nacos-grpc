<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc\Request;

use Hyperf\Nacos\Protobuf\Request\RequestInterface;

class BatchInstanceRequest implements RequestInterface
{

    public function getValue(): array
    {
        return [
            'namespace' => 'dd337529-b16a-4c2c-8999-a9e9011adbb2',
            'serviceName' => 'partner.grpc',
            'groupName' => 'srv',
            'module' => "naming",
            'type' => '',
        ];
    }

    public function getType(): string
    {
        return 'BatchInstanceRequest';
    }
}