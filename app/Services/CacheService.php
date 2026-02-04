<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Cache TTL (Time To Live) dalam detik
     */
    private const TTL_MASTER = 86400;      // 24 jam - master data
    private const TTL_QUERY = 300;         // 5 menit - query results
    private const TTL_USER_SESSION = 3600; // 1 jam - user session data

    /**
     * Master data cache (departments, roles, document types)
     */
    public function getMasterData(string $key, callable $callback): mixed
    {
        return Cache::remember(
            "master:{$key}",
            self::TTL_MASTER,
            $callback
        );
    }

    /**
     * Query result cache
     */
    public function getQueryCache(string $key, callable $callback, int $ttl = self::TTL_QUERY): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * User session cache
     */
    public function getUserCache(int $userId, string $key, callable $callback): mixed
    {
        return Cache::remember(
            "user:{$userId}:{$key}",
            self::TTL_USER_SESSION,
            $callback
        );
    }

    /**
     * Invalidate user cache
     */
    public function forgetUserCache(int $userId, ?string $key = null): void
    {
        if ($key) {
            Cache::forget("user:{$userId}:{$key}");
        } else {
            // Invalidate semua cache user (pattern-based)
            $this->forgetPattern("user:{$userId}:*");
        }
    }

    /**
     * Invalidate document cache
     */
    public function forgetDocumentCache(int $documentId): void
    {
        Cache::forget("document:{$documentId}");
        $this->forgetPattern("documents:list:*");
    }

    /**
     * Invalidate dashboard cache
     */
    public function forgetDashboardCache(int $userId): void
    {
        Cache::forget("dashboard:stats:{$userId}");
        Cache::forget("dashboard:my-documents:{$userId}");
        Cache::forget("dashboard:my-approvals:{$userId}");
    }

    /**
     * Cache warming - panaskan cache dengan data yang sering diakses
     */
    public function warmMasterData(): void
    {
        // Departments
        $this->getMasterData('departments', function () {
            return \App\Models\Department::all();
        });

        // Roles
        $this->getMasterData('roles', function () {
            return \App\Models\Role::all();
        });

        // Document Types
        $this->getMasterData('document_types', function () {
            return \App\Models\DocumentType::active()->get();
        });

        // Policies
        $this->getMasterData('policies', function () {
            return \App\Models\Policy::active()->get();
        });
    }

    /**
     * Hapus cache berdasarkan pattern (Redis specific)
     */
    private function forgetPattern(string $pattern): void
    {
        // Hanya work di Redis
        if (config('cache.default') === 'redis') {
            $keys = Cache::getRedis()->keys($pattern);
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
    }
}
