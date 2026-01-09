<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $entities = [
            'ketua-ks' => ['table' => 'ketua_ks', 'id_field' => 'ID_KET'],
            'kel-sah' => ['table' => 'kel_sah', 'id_field' => 'ID_KEL'],
            'data-lo' => ['table' => 'data_lo', 'id_field' => 'ID_LO'],
            'sekre-ks' => ['table' => 'sekre_ks', 'id_field' => 'ID_SEKRE'],
            'data-ao' => ['table' => 'data_ao', 'id_field' => 'ID_AO'],
            'data-pengelola' => ['table' => 'data_pengelola', 'id_field' => 'ID_PENG'],
        ];

        foreach ($entities as $entityType => $config) {
            // Get max ID from table
            $maxId = DB::table($config['table'])
                ->max($config['id_field']);

            if ($maxId) {
                // Extract running number (last 5 digits)
                // Format: 016005X00001 -> extract 00001 (12 digit total)
                $runningNumber = (int) substr($maxId, 7);

                // Update id_sequences with the max running number
                DB::table('id_sequences')
                    ->where('entity_type', $entityType)
                    ->update([
                        'last_number' => $runningNumber,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset all sequences to 0
        DB::table('id_sequences')->update(['last_number' => 0]);
    }
};
