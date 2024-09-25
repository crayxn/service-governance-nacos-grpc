<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc\Request;

use Hyperf\Nacos\Protobuf\Request\RequestInterface;
use Hyperf\Nacos\Protobuf\Response\Response;

class ClientDetectionResponse extends Response implements RequestInterface {
    public function getValue(): array {
        return [
            'resultCode' => $this->resultCode,
            'errorCode'  => $this->errorCode,
            'success'    => $this->success,
            'message'    => $this->message,
            'requestId'  => $this->requestId,
        ];
    }

    public function getType(): string {
        return 'ClientDetectionResponse';
    }
}