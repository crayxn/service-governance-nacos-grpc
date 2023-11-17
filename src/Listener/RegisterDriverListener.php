<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\ServiceGovernance\DriverManager;
use Crayxn\ServiceGovernanceNacosGrpc\NacosGrpcDriver;
use function Hyperf\Support\make;

class RegisterDriverListener implements ListenerInterface
{
    public function __construct(protected DriverManager $driverManager)
    {
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        $this->driverManager->register('nacos-grpc', make(NacosGrpcDriver::class));
    }
}