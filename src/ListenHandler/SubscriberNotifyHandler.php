<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc\ListenHandler;

use Crayxn\ServiceGovernanceNacosGrpc\Event\NacosSubscriberNotify;
use Crayxn\ServiceGovernanceNacosGrpc\Response\NotifySubscriberRequest;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nacos\Protobuf\ListenHandlerInterface;
use Hyperf\Nacos\Protobuf\Request\NotifySubscriberResponse;
use Hyperf\Nacos\Protobuf\Request\Request;
use Hyperf\Nacos\Protobuf\Response\Response;
use Psr\EventDispatcher\EventDispatcherInterface;

class SubscriberNotifyHandler implements ListenHandlerInterface
{

    public function __construct(
        protected StdoutLoggerInterface    $logger,
        protected EventDispatcherInterface $dispatcher
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
        $response instanceof NotifySubscriberRequest && $this->dispatcher->dispatch(new NacosSubscriberNotify($response));
    }

    public function ack(Response $response): Request
    {
        return new NotifySubscriberResponse($response->requestId);
    }
}