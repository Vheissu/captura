<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $services = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'worker' => $this->checkWorker(),
        ];

        $status = in_array('fail', $services, true) ? 'degraded' : 'ok';

        return response()->json([
            'status' => $status,
            'services' => $services,
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    private function checkDatabase(): string
    {
        try {
            DB::select('select 1');
            return 'ok';
        } catch (\Throwable) {
            return 'fail';
        }
    }

    private function checkRedis(): string
    {
        try {
            Redis::ping();
            return 'ok';
        } catch (\Throwable) {
            return 'fail';
        }
    }

    private function checkWorker(): string
    {
        try {
            $key = config('screenshot.worker.heartbeat_key');
            $ttl = (int) config('screenshot.worker.heartbeat_ttl', 30);
            $timestamp = Redis::get($key);

            if (!$timestamp) {
                return 'fail';
            }

            $age = now()->getTimestamp() - (int) ($timestamp / 1000);
            return $age <= $ttl ? 'ok' : 'fail';
        } catch (\Throwable) {
            return 'fail';
        }
    }
}
