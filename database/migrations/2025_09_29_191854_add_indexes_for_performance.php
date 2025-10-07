<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = \DB::select("SHOW INDEX FROM {$table}");
        return collect($indexes)->contains('Key_name', $index);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // إضافة فهرسة لجدول المستخدمين لتحسين الأداء
        Schema::table('users', function (Blueprint $table) {
            // التحقق من وجود الفهرسة قبل إضافتها
            if (!$this->indexExists('users', 'users_status_type_index')) {
                $table->index(['status', 'type'], 'users_status_type_index');
            }
            if (!$this->indexExists('users', 'users_full_name_index')) {
                $table->index(['full_name'], 'users_full_name_index');
            }
            if (!$this->indexExists('users', 'users_email_index')) {
                $table->index(['email'], 'users_email_index');
            }
            if (!$this->indexExists('users', 'users_phone_index')) {
                $table->index(['phone'], 'users_phone_index');
            }
            if (!$this->indexExists('users', 'users_created_at_index')) {
                $table->index(['created_at'], 'users_created_at_index');
            }
            if (!$this->indexExists('users', 'users_is_approved_index')) {
                $table->index(['is_approved'], 'users_is_approved_index');
            }
        });

        // إضافة فهرسة لجدول التحذيرات
        Schema::table('alerts', function (Blueprint $table) {
            // التحقق من وجود الفهرسة قبل إضافتها
            if (!$this->indexExists('alerts', 'alerts_user_created_index')) {
                $table->index(['user_id', 'created_at'], 'alerts_user_created_index');
            }
            if (!$this->indexExists('alerts', 'alerts_status_index')) {
                $table->index(['status'], 'alerts_status_index');
            }
            if (!$this->indexExists('alerts', 'alerts_type_index')) {
                $table->index(['type'], 'alerts_type_index');
            }
            if (!$this->indexExists('alerts', 'alerts_severity_index')) {
                $table->index(['severity'], 'alerts_severity_index');
            }
            if (!$this->indexExists('alerts', 'alerts_is_read_index')) {
                $table->index(['is_read'], 'alerts_is_read_index');
            }
        });

        // إضافة فهرسة لجدول سجل الدخول
        if (Schema::hasTable('authentication_logs')) {
            Schema::table('authentication_logs', function (Blueprint $table) {
                if (!$this->indexExists('authentication_logs', 'auth_logs_user_login_index')) {
                    $table->index(['authenticatable_id', 'authenticatable_type', 'login_at'], 'auth_logs_user_login_index');
                }
                if (!$this->indexExists('authentication_logs', 'auth_logs_login_at_index')) {
                    $table->index(['login_at'], 'auth_logs_login_at_index');
                }
                if (!$this->indexExists('authentication_logs', 'auth_logs_login_successful_index')) {
                    $table->index(['login_successful'], 'auth_logs_login_successful_index');
                }
            });
        }

        // إضافة فهرسة لجدول النشاطات
        if (Schema::hasTable('user_activities')) {
            Schema::table('user_activities', function (Blueprint $table) {
                if (!$this->indexExists('user_activities', 'user_activities_user_activity_index')) {
                    $table->index(['user_id', 'activity_at'], 'user_activities_user_activity_index');
                }
                if (!$this->indexExists('user_activities', 'user_activities_action_index')) {
                    $table->index(['action'], 'user_activities_action_index');
                }
                if (!$this->indexExists('user_activities', 'user_activities_status_index')) {
                    $table->index(['status'], 'user_activities_status_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إزالة الفهرسة من جدول المستخدمين
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_status_type_index');
            $table->dropIndex('users_full_name_index');
            $table->dropIndex('users_email_index');
            $table->dropIndex('users_phone_index');
            $table->dropIndex('users_created_at_index');
            $table->dropIndex('users_is_approved_index');
        });

        // إزالة الفهرسة من جدول التحذيرات
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex('alerts_user_created_index');
            $table->dropIndex('alerts_status_index');
            $table->dropIndex('alerts_type_index');
            $table->dropIndex('alerts_severity_index');
            $table->dropIndex('alerts_is_read_index');
        });

        // إزالة الفهرسة من جدول سجل الدخول
        if (Schema::hasTable('authentication_logs')) {
            Schema::table('authentication_logs', function (Blueprint $table) {
                $table->dropIndex('auth_logs_user_login_index');
                $table->dropIndex('auth_logs_login_at_index');
                $table->dropIndex('auth_logs_login_successful_index');
            });
        }

        // إزالة الفهرسة من جدول النشاطات
        if (Schema::hasTable('user_activities')) {
            Schema::table('user_activities', function (Blueprint $table) {
                $table->dropIndex('user_activities_user_activity_index');
                $table->dropIndex('user_activities_action_index');
                $table->dropIndex('user_activities_status_index');
            });
        }
    }
};