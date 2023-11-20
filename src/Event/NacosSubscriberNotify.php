<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc\Event;

use Crayxn\ServiceGovernanceNacosGrpc\Response\NotifySubscriberRequest;

class NacosSubscriberNotify
{
    public function __construct(public NotifySubscriberRequest $request)
    {
    }
}