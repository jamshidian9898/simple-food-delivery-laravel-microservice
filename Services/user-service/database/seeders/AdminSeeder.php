<?php

namespace Database\Seeders;

use App\Infrastructure\Services\UserDataPublisher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed admin/test users with known credentials.
     */
    public function run(): void
    {
        $publisher = new UserDataPublisher();
        // Create admin users for each type with well-known credentials
        $adminUsers = [
            [
                'name' => 'Admin Customer',
                'email' => 'admin.customer@fooddelivery.com',
                'password' => Hash::make('password123'),
                'type' => 'customer',
                'phone' => '+1000000001',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Admin Restaurant',
                'email' => 'admin.restaurant@fooddelivery.com',
                'password' => Hash::make('password123'),
                'type' => 'restaurant',
                'phone' => '+1000000002',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Admin Courier',
                'email' => 'admin.courier@fooddelivery.com',
                'password' => Hash::make('password123'),
                'type' => 'courier',
                'phone' => '+1000000003',
                'email_verified_at' => now(),
            ],
        ];

        $createdUsers = collect();

        foreach ($adminUsers as $adminData) {
            if ($adminData['type'] === 'customer') {
                $adminData = User::factory()->customer()->make($adminData)->toArray();
            } elseif ($adminData['type'] === 'restaurant') {
                $adminData = User::factory()->restaurant()->make($adminData)->toArray();
            } elseif ($adminData['type'] === 'courier') {
                $adminData = User::factory()->courier()->make($adminData)->toArray();
            }

            // Ensure password is always present
            if (!isset($adminData['password'])) {
                $adminData['password'] = Hash::make('password123');
            }

            $user = User::firstOrCreate(
                ['email' => $adminData['email'], 'type' => $adminData['type']],
                $adminData
            );
            $createdUsers->push($user);
            $publisher->publishUser($user);
        }

        $this->command->info('✅ Ensured 3 admin users with known credentials');
        $this->command->info('   - admin.customer@fooddelivery.com (password: password123)');
        $this->command->info('   - admin.restaurant@fooddelivery.com (password: password123)');
        $this->command->info('   - admin.courier@fooddelivery.com (password: password123)');
        $this->command->info('📡 Published ' . $createdUsers->count() . ' admin users to Redis for cross-service access');
    }
}
