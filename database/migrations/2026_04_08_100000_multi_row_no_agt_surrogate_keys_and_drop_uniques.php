<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->upSqliteSurrogateKeys();
        } else {
            $this->rebuildChildTableWithSurrogateId('data_jlh_keluarga', function (Blueprint $table) {
                $table->foreign(['NO_AGT'], 'FK_DATA_JLH_KELUARGA_1')
                    ->references(['NO_AGT'])->on('anggota')
                    ->onUpdate('cascade')
                    ->onDelete('no action');
            });

            $this->rebuildChildTableWithSurrogateId('data_penghasilan', function (Blueprint $table) {
                $table->foreign(['NO_AGT'], 'FK_DATA_PENGHASILAN_1')
                    ->references(['NO_AGT'])->on('anggota')
                    ->onUpdate('cascade')
                    ->onDelete('no action');
            });

            $this->rebuildChildTableWithSurrogateId('data_trs', function (Blueprint $table) {
                $table->foreign(['NO_AGT'], 'FK_DATA_TRS_1')
                    ->references(['NO_AGT'])->on('anggota')
                    ->onUpdate('no action')
                    ->onDelete('no action');
            });
        }

        Schema::table('data_ao', function (Blueprint $table) {
            $table->dropUnique('unq1_data_ao');
        });

        Schema::table('data_lo', function (Blueprint $table) {
            $table->dropUnique('unq1_data_lo');
        });

        Schema::table('ketua_ks', function (Blueprint $table) {
            $table->dropUnique('unq1_ketua_ks');
        });

        Schema::table('sekre_ks', function (Blueprint $table) {
            $table->dropUnique('unq1_sekre_ks');
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            throw new \RuntimeException('Rollback of migration multi_row_no_agt_surrogate_keys_and_drop_uniques is not supported on SQLite.');
        }

        Schema::table('sekre_ks', function (Blueprint $table) {
            $table->unique('NO_AGT', 'unq1_sekre_ks');
        });

        Schema::table('ketua_ks', function (Blueprint $table) {
            $table->unique('NO_AGT', 'unq1_ketua_ks');
        });

        Schema::table('data_lo', function (Blueprint $table) {
            $table->unique(['NO_AGT'], 'unq1_data_lo');
        });

        Schema::table('data_ao', function (Blueprint $table) {
            $table->unique('NO_AGT', 'unq1_data_ao');
        });

        $this->restorePrimaryNoAgtOnly('data_trs', function (Blueprint $table) {
            $table->foreign(['NO_AGT'], 'FK_DATA_TRS_1')
                ->references(['NO_AGT'])->on('anggota')
                ->onUpdate('no action')
                ->onDelete('no action');
        });

        $this->restorePrimaryNoAgtOnly('data_penghasilan', function (Blueprint $table) {
            $table->foreign(['NO_AGT'], 'FK_DATA_PENGHASILAN_1')
                ->references(['NO_AGT'])->on('anggota')
                ->onUpdate('cascade')
                ->onDelete('no action');
        });

        $this->restorePrimaryNoAgtOnly('data_jlh_keluarga', function (Blueprint $table) {
            $table->foreign(['NO_AGT'], 'FK_DATA_JLH_KELUARGA_1')
                ->references(['NO_AGT'])->on('anggota')
                ->onUpdate('cascade')
                ->onDelete('no action');
        });
    }

    private function upSqliteSurrogateKeys(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->sqliteReshapeJlhKeluarga();
        $this->sqliteReshapePenghasilan();
        $this->sqliteReshapeTrs();

        Schema::enableForeignKeyConstraints();
    }

    private function sqliteReshapeJlhKeluarga(): void
    {
        Schema::rename('data_jlh_keluarga', 'data_jlh_keluarga_legacy');

        Schema::create('data_jlh_keluarga', function (Blueprint $table) {
            $table->id();
            $table->string('NO_AGT', 15);
            $table->string('JLH_AGT_KEL', 50)->nullable();
            $table->string('TGL', 50)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->index('NO_AGT');
            $table->foreign(['NO_AGT'], 'FK_DATA_JLH_KELUARGA_1')
                ->references(['NO_AGT'])->on('anggota')
                ->onUpdate('cascade')
                ->onDelete('no action');
        });

        DB::statement('INSERT INTO data_jlh_keluarga (NO_AGT, JLH_AGT_KEL, TGL, created_by) SELECT NO_AGT, JLH_AGT_KEL, TGL, created_by FROM data_jlh_keluarga_legacy');

        Schema::drop('data_jlh_keluarga_legacy');
    }

    private function sqliteReshapePenghasilan(): void
    {
        Schema::rename('data_penghasilan', 'data_penghasilan_legacy');

        Schema::create('data_penghasilan', function (Blueprint $table) {
            $table->id();
            $table->string('NO_AGT', 15);
            $table->string('PENGHASILAN', 50)->nullable();
            $table->string('PENGELUARAN', 50)->nullable();
            $table->string('TGL_DATA', 50)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->index('NO_AGT');
            $table->foreign(['NO_AGT'], 'FK_DATA_PENGHASILAN_1')
                ->references(['NO_AGT'])->on('anggota')
                ->onUpdate('cascade')
                ->onDelete('no action');
        });

        DB::statement('INSERT INTO data_penghasilan (NO_AGT, PENGHASILAN, PENGELUARAN, TGL_DATA, created_by) SELECT NO_AGT, PENGHASILAN, PENGELUARAN, TGL_DATA, created_by FROM data_penghasilan_legacy');

        Schema::drop('data_penghasilan_legacy');
    }

    private function sqliteReshapeTrs(): void
    {
        Schema::rename('data_trs', 'data_trs_legacy');

        Schema::create('data_trs', function (Blueprint $table) {
            $table->id();
            $table->string('NO_AGT', 15);
            $table->string('STR_SP', 50)->nullable();
            $table->string('STR_SW', 50)->nullable();
            $table->string('STR_SKA', 50)->nullable();
            $table->string('STR_SRI', 50)->nullable();
            $table->string('STR_SDK', 50)->nullable();
            $table->string('STR_PJM', 50)->nullable();
            $table->string('STR_BNG', 50)->nullable();
            $table->string('PJM_BARU', 50)->nullable();
            $table->string('STR_SHR', 50)->nullable();
            $table->string('STR_SBJ', 50)->nullable();
            $table->string('STR_SJP', 50)->nullable();
            $table->string('STR_SPD', 50)->nullable();
            $table->string('STR_SRY', 50)->nullable();
            $table->string('STR_SMD', 50)->nullable();
            $table->string('TGL_LAP', 50)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->index('NO_AGT');
            $table->foreign(['NO_AGT'], 'FK_DATA_TRS_1')
                ->references(['NO_AGT'])->on('anggota')
                ->onUpdate('no action')
                ->onDelete('no action');
        });

        DB::statement('INSERT INTO data_trs (NO_AGT, STR_SP, STR_SW, STR_SKA, STR_SRI, STR_SDK, STR_PJM, STR_BNG, PJM_BARU, STR_SHR, STR_SBJ, STR_SJP, STR_SPD, STR_SRY, STR_SMD, TGL_LAP, created_by) SELECT NO_AGT, STR_SP, STR_SW, STR_SKA, STR_SRI, STR_SDK, STR_PJM, STR_BNG, PJM_BARU, STR_SHR, STR_SBJ, STR_SJP, STR_SPD, STR_SRY, STR_SMD, TGL_LAP, created_by FROM data_trs_legacy');

        Schema::drop('data_trs_legacy');
    }

    /**
     * @param  callable(Blueprint): void  $reForeign
     */
    private function rebuildChildTableWithSurrogateId(string $tableName, callable $reForeign): void
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropForeign(['NO_AGT']);
        });

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropPrimary(['NO_AGT']);
        });

        Schema::table($tableName, function (Blueprint $table) {
            $table->id();
        });

        Schema::table($tableName, function (Blueprint $table) use ($reForeign) {
            $table->index('NO_AGT');
            $reForeign($table);
        });
    }

    /**
     * Best-effort rollback: requires at most one row per NO_AGT.
     *
     * @param  callable(Blueprint): void  $reForeign
     */
    private function restorePrimaryNoAgtOnly(string $tableName, callable $reForeign): void
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropForeign(['NO_AGT']);
        });

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropIndex(['NO_AGT']);
        });

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table($tableName, function (Blueprint $table) use ($reForeign) {
            $table->primary(['NO_AGT']);
            $reForeign($table);
        });
    }
};
