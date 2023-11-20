<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc\Model;

class Instance
{
    public string $instanceId;
    public string $ip;
    public int $port;
    public float $weight;
    public bool $healthy = true;
    public bool $enabled = true;
    public bool $ephemeral = true;
    public string $clusterName = '';
    public string $serviceName;
    public array $metadata = [];
    public int $instanceHeartBeatInterval;
    public int $ipDeleteTimeout;
    public int $instanceHeartBeatTimeOut;
}