<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc\Request;

use Hyperf\Nacos\Protobuf\Request\Request;

/**
 * 重写
 */
class ConnectionSetupRequest extends Request
{
    public function __construct(public string $module, public string $namespace = '', public string $appName = '')
    {
    }

    public function getValue(): array
    {
        return [
            'tenant' => $this->namespace,
            'clientVersion' => 'Nacos-Kyy-Client:v1.0',
            'labels' => [
                'source' => 'sdk',
                'AppName' => $this->appName,
                'taskId' => '0',
                'module' => $this->module,
            ],
            'module' => 'internal',
        ];
    }

    public function getType(): string
    {
        return 'ConnectionSetupRequest';
    }
}
