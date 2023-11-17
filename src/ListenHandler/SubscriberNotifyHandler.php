<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc\ListenHandler;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nacos\Protobuf\ListenHandlerInterface;
use Hyperf\Nacos\Protobuf\Request\NotifySubscriberResponse;
use Hyperf\Nacos\Protobuf\Request\Request;
use Hyperf\Nacos\Protobuf\Response\Response;
use Crayxn\ServiceGovernanceNacosGrpc\InstanceManager;
use Crayxn\ServiceGovernanceNacosGrpc\Response\NotifySubscriberRequest;

class SubscriberNotifyHandler implements ListenHandlerInterface
{

    public function __construct(
        protected StdoutLoggerInterface $logger,
        protected InstanceManager       $instanceManager
    )
    {
    }

    /**
     * @param Response $response
     * @return void
     */
    public function handle(Response $response): void
    {
        $this->logger->debug('Nacos subscribe notify');
        /**
         * @var NotifySubscriberRequest $response
         */
        foreach ($response->serviceInfo as $service) {
            //todo 需要区分 namespace
            $this->instanceManager->updateInstances("{$service['groupName']}@@{$service['name']}", array_map(function ($item) {
                if (($item['healthy'] ?? 0) == 1 && ($item['enabled']) ?? 0 == 1) {
                    return $item;
                }
                return null;
            }, $service['hosts'] ?? []));
        }
    }

    public function ack(Response $response): Request
    {
        return new NotifySubscriberResponse($response->requestId);
    }
}