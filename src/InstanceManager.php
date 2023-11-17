<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayxn\ServiceGovernanceNacosGrpc;

class InstanceManager
{
    /**
     * @var array<string,array>
     */
    private array $instances;

    public function updateInstances(string $serviceKey, array $hosts): void
    {
        $instances = [];
        foreach ($hosts as $host) {
            if ($host && !empty($host['ip']) && !empty($host['port'])) {
                $instances[] = [
                    'host' => $host['ip'],
                    'port' => $host['port'],
                    'weight' => $this->getWeight($host['weight'] ?? 1),
                ];
            }
        }
        $this->instances[$serviceKey] = $instances;
    }

    private function getWeight($weight): int
    {
        return intval(100 * $weight);
    }


    public function getInstances(string $serviceKey): array
    {
        return $this->instances[$serviceKey] ?? [];
    }
}