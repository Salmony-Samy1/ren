<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class OptimizePerformanceCommand extends Command
{
    protected $signature = 'app:optimize-performance';
    protected $description = 'Optimize application performance by cleaning up caches and optimizing database';

    public function handle()
    {
        $this->info('Starting performance optimization...');

        // Clear application caches
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        // Optimize database
        $this->optimizeDatabase();

        // Clean Redis memory
        $this->cleanRedisMemory();

        // Clear failed jobs
        $this->call('queue:flush');

        $this->info('Performance optimization completed successfully!');
    }

    private function optimizeDatabase()
    {
        $this->info('Optimizing database...');
        
        try {
            // Analyze tables for better query planning
            DB::statement('ANALYZE TABLE services, bookings, users, events, restaurants, properties, caterings');
            
            // Clean up old cache entries
            DB::table('cache')->where('expiration', '<', now()->timestamp)->delete();
            
            $this->info('Database optimization completed');
        } catch (\Exception $e) {
            $this->error('Database optimization failed: ' . $e->getMessage());
        }
    }

    private function cleanRedisMemory()
    {
        $this->info('Cleaning Redis memory...');
        
        try {
            // Clean up expired presence data
            $now = time();
            Redis::zremrangebyscore('presence:users', '-inf', $now);
            Redis::zremrangebyscore('presence:providers', '-inf', $now);
            Redis::zremrangebyscore('presence:admins', '-inf', $now);
            
            // Clean up old cache keys
            $keys = Redis::keys('*cache*');
            foreach ($keys as $key) {
                if (Redis::ttl($key) === -1) {
                    Redis::expire($key, 3600); // Set 1 hour expiry for keys without TTL
                }
            }
            
            $this->info('Redis memory cleanup completed');
        } catch (\Exception $e) {
            $this->error('Redis cleanup failed: ' . $e->getMessage());
        }
    }
}
