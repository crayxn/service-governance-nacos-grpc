<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc\Response;

use Hyperf\Codec\Json;
use Hyperf\Nacos\Protobuf\Response\Response;
use JsonSerializable;

class QueryServiceResponse extends Response implements JsonSerializable
{
    public array $serviceInfo;

    private array $json;

    public function __construct(array $json)
    {
        $this->serviceInfo = $json['serviceInfo'];
        $this->json = $json;
    }

    public function __toString(): string
    {
        return Json::encode($this->json);
    }

    public function jsonSerialize(): mixed
    {
        return $this->json;
    }
}