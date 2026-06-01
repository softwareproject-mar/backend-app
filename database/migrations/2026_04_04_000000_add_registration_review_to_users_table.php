<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'registration_reviewed_at')) {
                $table->timestamp('registration_reviewed_at')->nullable()->after('registration_status');
            }
            if (! Schema::hasColumn('users', 'registration_reviewed_by')) {
                $table->foreignId('registration_reviewed_by')
                    ->nullable()
                    ->after('registration_reviewed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasColumn('users', 'registration_reviewed_at')) {
            DB::table('users')
                ->where('role', 'user')
                ->whereIn('registration_status', ['approved', 'rejected'])
                ->whereNull('registration_reviewed_at')
                ->update(['registration_reviewed_at' => DB::raw('updated_at')]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'registration_reviewed_by')) {
                $table->dropForeign(['registration_reviewed_by']);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'registration_reviewed_by')) {
                $table->dropColumn('registration_reviewed_by');
            }
            if (Schema::hasColumn('users', 'registration_reviewed_at')) {
                $table->dropColumn('registration_reviewed_at');
            }
        });
    }
};
