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
     * Publish user data to Redis for other services
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
     * Publish multiple users in batch
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
     * Prepare user data for cross-service consumption
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
     * Clear all seeded user data from Redis
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
     * Get statistics about seeded users in Redis
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
