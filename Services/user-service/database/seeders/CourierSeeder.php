<?php

namespace Database\Seeders;

use App\Infrastructure\Services\UserDataPublisher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CourierSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ensure courier user records exist and publish their data for cross-service access.
     *
     * Creates three fixed test courier accounts and twelve additional random courier accounts,
     * persists any missing users, and publishes the created users to Redis via UserDataPublisher.
     */
    public function run(): void
    {
        $publisher = new UserDataPublisher();
        $createdUsers = collect();

        // Create test couriers with known credentials
        $testCouriers = [
            [
                'name' => 'Alex Courier',
                'email' => 'courier@example.com',
                'type' => 'courier',
                'phone' => '+1234567700',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Sarah Delivery',
                'email' => 'sarah.courier@example.com',
                'type' => 'courier',
                'phone' => '+1234567701',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'David Fast',
                'email' => 'david.courier@example.com',
                'type' => 'courier',
                'phone' => '+1234567702',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($testCouriers as $courierData) {
            $courierData = User::factory()->courier()->make($courierData)->toArray();

            // Ensure password is always present
            if (!isset($courierData['password'])) {
                $courierData['password'] = Hash::make('password123');
            }

            $user = User::firstOrCreate(
                ['email' => $courierData['email'], 'type' => $courierData['type']],
                $courierData
            );
            $createdUsers->push($user);
            $publisher->publishUser($user);
        }

        // Create additional random couriers
        $randomCouriers = User::factory()
            ->courier()
            ->count(12) // Total 15 couriers (3 test + 12 random)
            ->create();

        $createdUsers = $createdUsers->merge($randomCouriers);
        $publisher->publishUsers($randomCouriers);

        $this->command->info('✅ Ensured 15 courier users (3 test + 12 random)');
        $this->command->info('📡 Published ' . $createdUsers->count() . ' couriers to Redis for cross-service access');
    }
}
