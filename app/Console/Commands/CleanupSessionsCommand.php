<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class CleanupSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:cleanup {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تنظيف الجلسات القديمة والفاسدة';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('بدء تنظيف الجلسات...');
        
        $sessionDriver = config('session.driver');
        $cleanedCount = 0;
        
        if ($sessionDriver === 'file') {
            $cleanedCount = $this->cleanupFileSessions();
        } elseif ($sessionDriver === 'database') {
            $cleanedCount = $this->cleanupDatabaseSessions();
        }
        
        $this->info("تم تنظيف {$cleanedCount} جلسة قديمة.");
        
        return Command::SUCCESS;
    }
    
    private function cleanupFileSessions(): int
    {
        $sessionPath = storage_path('framework/sessions');
        $files = File::files($sessionPath);
        $cleanedCount = 0;
        $lifetime = config('session.lifetime', 120) * 60; // تحويل إلى ثواني
        
        foreach ($files as $file) {
            $fileAge = time() - $file->getMTime();
            
            // حذف الملفات الأقدم من عمر الجلسة
            if ($fileAge > $lifetime) {
                File::delete($file->getPathname());
                $cleanedCount++;
            }
        }
        
        return $cleanedCount;
    }
    
    private function cleanupDatabaseSessions(): int
    {
        $table = config('session.table', 'sessions');
        $lifetime = config('session.lifetime', 120);
        
        $cleanedCount = DB::table($table)
            ->where('last_activity', '<', now()->subMinutes($lifetime)->timestamp)
            ->delete();
            
        return $cleanedCount;
    }
}
