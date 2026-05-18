<?php

namespace Database\Seeders;

use App\Infrastructure\Services\UserDataPublisher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ensures a set of customer users exist and publishes them for cross-service access.
     *
     * Creates three predefined customer records (with controlled emails, types, phone and verification states),
     * generates 47 additional random customer users (for a total of 50), ensures each user has a password,
     * upserts the predefined users, publishes all created users via UserDataPublisher, and logs summary messages to the console.
     */
    public function run(): void
    {
        $publisher = new UserDataPublisher();
        $createdUsers = collect();

        // Create test customers with known credentials
        $testCustomers = [
            [
                'name' => 'John Doe',
                'email' => 'customer@example.com',
                'type' => 'customer',
                'phone' => '+1234567890',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.customer@example.com',
                'type' => 'customer',
                'phone' => '+1234567891',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike.customer@example.com',
                'type' => 'customer',
                'phone' => null, // Some customers might not have phone
                'email_verified_at' => null, // Some might be unverified
            ],
        ];

        foreach ($testCustomers as $customerData) {
            $customerData = User::factory()->customer()->make($customerData)->toArray();

            // Ensure password is always present
            if (!isset($customerData['password'])) {
                $customerData['password'] = Hash::make('password123');
            }

            $user = User::firstOrCreate(
                ['email' => $customerData['email'], 'type' => $customerData['type']],
                $customerData
            );
            $createdUsers->push($user);
            $publisher->publishUser($user);
        }

        // Create additional random customers
        $randomCustomers = User::factory()
            ->customer()
            ->count(47) // Total 50 customers (3 test + 47 random)
            ->create();

        $createdUsers = $createdUsers->merge($randomCustomers);
        $publisher->publishUsers($randomCustomers);

        $this->command->info('✅ Ensured 50 customer users (3 test + 47 random)');
        $this->command->info('📡 Published ' . $createdUsers->count() . ' customers to Redis for cross-service access');
    }
}
