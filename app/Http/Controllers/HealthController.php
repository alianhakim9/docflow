<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\JsonResponse;

class HealthController extends Controller
{
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
        ];

        $isReady = !in_array(false, $checks, true);

        return response()->json([
            'status' => $isReady ? 'ready' : 'not_ready',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function deep(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'database_write' => $this->checkDatabaseWrite(),
            'redis' => $this->checkRedis(),
            'redis_write' => $this->checkRedisWrite(),
            'disk_space' => $this->checkDiskSpace(),
            'memory' => $this->checkMemory(),
        ];

        $isHealthy = !in_array(false, $checks, true);

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    private function checkDatabaseWrite(): bool
    {
        try {
            DB::statement('SELECT 1');
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    private function checkRedisWrite(): bool
    {
        try {
            $testKey = 'health_check_' . time();
            $redis = Redis::connection();
            $redis->set($testKey, 'ok', 'EX', 10);
            return $redis->get($testKey) === 'ok';
        } catch (\Throwable $e) {
            return false;
        }
    }


    private function checkDiskSpace(): bool
    {
        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');

        if ($freeSpace === false || $totalSpace === false) {
            return true;
        }

        $usedPercentage = (($totalSpace - $freeSpace) / $totalSpace) * 100;

        return $usedPercentage  < 90;
    }

    private function checkMemory(): bool
    {
        $memoryLimit = $this->returnBytes(ini_get('memory_limit'));
        $memoryUsage = memory_get_usage(true);

        if ($memoryLimit === -1) {
            return true;
        }

        $usedPercentage = ($memoryUsage / $memoryLimit) * 100;

        return $usedPercentage < 80;
    }

    private function returnBytes(string $val): int
    {
        $val = trim($val);

        if ($val === '-1') {
            return -1;
        }

        $last = strtolower($val[strlen($val) - 1]);
        $val = (int) $val;

        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}
