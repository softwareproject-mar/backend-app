<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['data_penghasilan', 'data_jlh_keluarga', 'data_trs', 'data_kunjungan'] as $tableName) {
            Schema::table($tableName, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('created_by')->nullable();
                $blueprint->index('created_by');
                $blueprint->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['data_penghasilan', 'data_jlh_keluarga', 'data_trs', 'data_kunjungan'] as $tableName) {
            Schema::table($tableName, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['created_by']);
                $blueprint->dropIndex(['created_by']);
                $blueprint->dropColumn('created_by');
            });
        }
    }
};
