<?php

namespace App\Infrastructure\Services;

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class UserDataPublisher
{
    /**
     * Redis key prefix for user data
     */
    private const USER_DATA_PREFIX = 'seed:users:';
    
    /**
     * Redis key for user list by type
     */
    private const USER_LIST_PREFIX = 'seed:users:list:';
    
    /**
     * Redis key for all users list
     */
    private const ALL_USERS_KEY = 'seed:users:all';

    /**
     * Publish a user's normalized data to Redis for cross-service consumption.
     *
     * Stores a per-user JSON payload (TTL 3600s), adds the user ID to a type-specific set
     * and the global seeded-users set (each refreshed to a 3600s TTL), and publishes a
     * `user.created` event containing event metadata and the payload.
     *
     * On failure the exception is caught and an error is logged with `user_id` and the
     * exception message.
     *
     * @param User $user The user model to publish.
     */
    public function publishUser(User $user): void
    {
        try {
            // Prepare user data for cross-service consumption
            $userData = $this->prepareUserData($user);
            
            // Store individual user data
            $userKey = self::USER_DATA_PREFIX . $user->id;
            Redis::setex($userKey, 3600, json_encode($userData)); // 1 hour TTL
            
            // Add to type-specific list
            $typeListKey = self::USER_LIST_PREFIX . $user->type;
            Redis::eval(
                "redis.call('SADD', KEYS[1], ARGV[1])
                redis.call('EXPIRE', KEYS[1], ARGV[2])
                redis.call('SADD', KEYS[2], ARGV[1])
                redis.call('EXPIRE', KEYS[2], ARGV[2])
                return 1",
                2,
                $typeListKey,
                self::ALL_USERS_KEY,
                $user->id,
                3600
            );
            
            // Publish event for real-time updates
            Redis::publish('user.created', json_encode([
                'event' => 'user.created',
                'user_id' => $user->id,
                'user_type' => $user->type,
                'timestamp' => now()->toISOString(),
                'data' => $userData
            ]));
            
        } catch (\Exception $e) {
            Log::error("Failed to publish user data to Redis", [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Publish a collection of users by publishing each user individually and record batch statistics to the log.
     *
     * @param \Illuminate\Support\Collection $users Collection of User models to publish; after processing, the method logs the total count and counts grouped by `type`.
     */
    public function publishUsers(\Illuminate\Support\Collection $users): void
    {
        foreach ($users as $user) {
            $this->publishUser($user);
        }
        
        Log::info("Published batch of users to Redis", [
            'count' => $users->count(),
            'types' => $users->groupBy('type')->map->count()->toArray()
        ]);
    }
    
    /**
         * Builds a normalized associative array of user attributes for cross-service publishing.
         *
         * Timestamps are converted to ISO 8601 strings and boolean flags are added for verification and phone presence.
         *
         * @param User $user The user model to normalize.
         * @return array{
         *     id: mixed,
         *     name: string|null,
         *     email: string|null,
         *     type: string|null,
         *     phone: string|null,
         *     email_verified_at: string|null,
         *     created_at: string,
         *     updated_at: string,
         *     is_verified: bool,
         *     has_phone: bool
         * } Associative array containing normalized user data ready for serialization and publishing.
         */
    private function prepareUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => $user->type,
            'phone' => $user->phone,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
            'is_verified' => !is_null($user->email_verified_at),
            'has_phone' => !is_null($user->phone),
        ];
    }
    
    /**
         * Remove all seeded user entries and associated type lists from Redis.
         *
         * Deletes per-user data keys referenced by the global seeded-users set, removes the known
         * type-specific user ID sets (customer, restaurant, courier), and deletes the global set
         * that tracks all seeded user IDs. Logs the number of cleared users on success and an
         * error message if the operation fails.
         */
    public function clearSeedData(): void
    {
        try {
            // Get all user IDs
            $userIds = Redis::smembers(self::ALL_USERS_KEY);
            
            // Delete individual user data
            foreach ($userIds as $userId) {
                Redis::del(self::USER_DATA_PREFIX . $userId);
            }
            
            // Delete type lists
            Redis::del(self::USER_LIST_PREFIX . 'customer');
            Redis::del(self::USER_LIST_PREFIX . 'restaurant');
            Redis::del(self::USER_LIST_PREFIX . 'courier');
            
            // Delete all users list
            Redis::del(self::ALL_USERS_KEY);
            
            Log::info("Cleared all seeded user data from Redis", [
                'cleared_users' => count($userIds)
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to clear seeded user data from Redis", [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Retrieve counts of seeded users stored in Redis, grouped by category.
     *
     * Returns an associative array with current Redis set cardinalities for seeded users.
     *
     * @return array{total_users:int, customers:int, restaurants:int, couriers:int} Counts keyed by:
     *   - `total_users`: number of all seeded users,
     *   - `customers`: number of seeded users with type "customer",
     *   - `restaurants`: number of seeded users with type "restaurant",
     *   - `couriers`: number of seeded users with type "courier".
     */
    public function getStatistics(): array
    {
        try {
            return [
                'total_users' => Redis::scard(self::ALL_USERS_KEY),
                'customers' => Redis::scard(self::USER_LIST_PREFIX . 'customer'),
                'restaurants' => Redis::scard(self::USER_LIST_PREFIX . 'restaurant'),
                'couriers' => Redis::scard(self::USER_LIST_PREFIX . 'courier'),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get user statistics from Redis", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
