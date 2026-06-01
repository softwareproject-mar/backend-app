<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DataPenghasilanService;
use App\Support\MemberScope;
use Illuminate\Console\Command;

class TestUserCrudCommand extends Command
{
    protected $signature = 'test:user-crud';

    protected $description = 'Test CRUD operations for user without no_agt';

    public function handle()
    {
        $this->info('=== Testing User without no_agt ===');

        // Find or create test user
        $user = User::where('role', 'user')->whereNull('no_agt')->first();
        if (! $user) {
            $this->info('No user without no_agt found. Creating test user...');
            $user = User::create([
                'name' => 'Test User Without NoAgt',
                'email' => 'test-no-agt@example.com',
                'password' => bcrypt('password'),
                'role' => 'user',
                'no_agt' => null,
                'is_active' => true,
                'registration_status' => 'approved',
            ]);
        }

        $this->info("User: {$user->email} (ID: {$user->id}, no_agt: ".($user->no_agt ?? 'NULL').')');

        // Test MemberScope functions
        $this->info('=== Testing MemberScope ===');
        $this->info('isRestrictedMemberUser: '.(MemberScope::isRestrictedMemberUser($user) ? 'true' : 'false'));
        $this->info('memberNoAgt: '.(MemberScope::memberNoAgt($user) ?? 'NULL'));

        // Test filter merging
        $filters = ['test' => 'value'];
        $mergedFilters = MemberScope::mergeOwnershipFilterForCrud($user, $filters);
        $this->info('Merged filters: '.json_encode($mergedFilters));

        // Test data penghasilan service
        $this->info('=== Testing DataPenghasilanService ===');
        $service = new DataPenghasilanService;

        try {
            $result = $service->paginate([], 10, $user);
            $this->info('Paginate result: '.$result->total().' items');
            $this->info('Items: '.count($result->items()));
        } catch (\Exception $e) {
            $this->error('Paginate error: '.$e->getMessage());
        }

        // Test creating data
        $this->info('=== Testing Create Data ===');
        try {
            // Use valid NO_AGT from anggota table
            $testData = [
                'NO_AGT' => '16005000000003', // Valid anggota NO_AGT
                'PENGHASILAN' => '5000000',
                'PENGELUARAN' => '3000000',
                'TGL_DATA' => '2026-04-06',
                'created_by' => $user->id,
            ];

            $created = $service->create($testData, $user);
            $this->info('Created successfully with ID: '.$created->id);

            // Test paginate again
            $result = $service->paginate([], 10, $user);
            $this->info('After create - Total items: '.$result->total());

            // Clean up test data
            $created->delete();
            $this->info('Test record cleaned up');

        } catch (\Exception $e) {
            $this->error('Create error: '.$e->getMessage());
        }

        $this->info('=== Test Complete ===');

        return 0;
    }
}
