<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaikan: baris di tabel `migrations` sudah mencatat migrasi lama, tetapi
 * tabel `anggota` bisa hilang (drop manual / restore parsial). Tanpa ini,
 * `php artisan migrate` mengatakan "Nothing to migrate" padahal tabel tidak ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('anggota')) {
            return;
        }

        Schema::create('anggota', function (Blueprint $table) {
            $table->string('NO_AGT', 15)->primary();
            $table->string('NAMA', 50)->nullable();
            $table->string('ID_KS', 12)->nullable()->index('fk_anggota_1');
            $table->string('ID_LO', 12)->nullable();
            $table->string('ID_AO', 12)->nullable();
            $table->string('ID_KS_ASL', 12)->nullable()->index('fk_anggota_2');
            $table->string('TGL_MTS', 50)->nullable();
            $table->string('TGL_AKTIF', 50)->nullable();
            $table->string('TGL_JA', 50)->nullable();
        });

        if (Schema::hasTable('kel_sah')) {
            try {
                Schema::table('anggota', function (Blueprint $table) {
                    $table->foreign(['ID_KS'], 'FK_ANGGOTA_1')
                        ->references(['ID_KEL'])->on('kel_sah')
                        ->onUpdate('no action')->onDelete('no action');
                    $table->foreign(['ID_KS_ASL'], 'FK_ANGGOTA_2')
                        ->references(['ID_KEL'])->on('kel_sah')
                        ->onUpdate('no action')->onDelete('no action');
                });
            } catch (\Throwable) {
                // Constraint sudah ada atau engine tidak mendukung — import tetap bisa dengan FK_CHECKS=0
            }
        }
    }

    public function down(): void
    {
        // Sengaja kosong: menghindari DROP anggota saat rollback (data produksi).
    }
};
