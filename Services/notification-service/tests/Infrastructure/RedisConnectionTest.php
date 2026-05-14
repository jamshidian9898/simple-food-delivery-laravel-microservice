<?php

namespace Tests\Infrastructure;

use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class RedisConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip tests if Redis is not configured
        if (!extension_loaded('redis') && !class_exists('Predis\Client')) {
            $this->fail('Redis extension or Predis client is not available');
        }
    }

    public function test_redis_server_is_reachable()
    {
        $host = config('database.redis.default.host', '127.0.0.1');
        $port = config('database.redis.default.port', 6379);
        
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        
        if ($connection) {
            fclose($connection);
            $this->assertTrue(true, 'Redis server is reachable');
        } else {
            $this->fail("Redis server is not reachable at {$host}:{$port}. Error ({$errno}): {$errstr}");
        }
    }

    public function test_redis_configuration_is_valid()
    {
        $config = config('database.redis');
        
        $this->assertNotNull($config, 'Redis configuration should exist');
        $this->assertIsArray($config, 'Redis configuration should be an array');
        $this->assertArrayHasKey('default', $config, 'Redis should have default connection');
        $this->assertArrayHasKey('cache', $config, 'Redis should have cache connection');
        
        $defaultConfig = $config['default'];
        $this->assertArrayHasKey('host', $defaultConfig, 'Default Redis connection should have host');
        $this->assertArrayHasKey('port', $defaultConfig, 'Default Redis connection should have port');
        $this->assertArrayHasKey('database', $defaultConfig, 'Default Redis connection should have database');
        
        $cacheConfig = $config['cache'];
        $this->assertArrayHasKey('host', $cacheConfig, 'Cache Redis connection should have host');
        $this->assertArrayHasKey('port', $cacheConfig, 'Cache Redis connection should have port');
        $this->assertArrayHasKey('database', $cacheConfig, 'Cache Redis connection should have database');
    }

    public function test_redis_default_connection()
    {
        try {
            // Test default Redis connection
            $redis = Redis::connection('default');
            $this->assertNotNull($redis, 'Redis default connection should not be null');
            
            // Test basic Redis operations
            $testKey = 'test_connection_' . bin2hex(random_bytes(8));
            $testValue = 'test_value_' . bin2hex(random_bytes(8));
            
            // Set a test value
            $result = $redis->set($testKey, $testValue);
            // Handle both phpredis (returns true) and predis (returns Status object with "OK")
            $this->assertTrue(
                $result === true || 
                (is_object($result) && method_exists($result, 'getPayload') && $result->getPayload() === 'OK') ||
                (is_object($result) && isset($result->payload) && $result->payload === 'OK') ||
                $result === 'OK',
                'Should be able to set a value in Redis'
            );
            
            // Get the test value
            $retrievedValue = $redis->get($testKey);
            $this->assertEquals($testValue, $retrievedValue, 'Should be able to retrieve the set value');
            
            // Delete the test value
            $deleted = $redis->del($testKey);
            $this->assertTrue($deleted > 0, 'Should be able to delete the test value');
            
        } catch (\Exception $e) {
            $this->fail('Failed to establish Redis default connection: ' . $e->getMessage());
        }
    }

    public function test_redis_cache_connection()
    {
        try {
            // Test cache Redis connection
            $redis = Redis::connection('cache');
            $this->assertNotNull($redis, 'Redis cache connection should not be null');
            
            // Test basic Redis operations on cache connection
            $testKey = 'test_cache_' . bin2hex(random_bytes(8));
            $testValue = 'cache_value_' . bin2hex(random_bytes(8));
            
            // Set a test value
            $result = $redis->set($testKey, $testValue);
            // Handle both phpredis (returns true) and predis (returns Status object with "OK")
            $this->assertTrue(
                $result === true || 
                (is_object($result) && method_exists($result, 'getPayload') && $result->getPayload() === 'OK') ||
                (is_object($result) && isset($result->payload) && $result->payload === 'OK') ||
                $result === 'OK',
                'Should be able to set a value in Redis cache'
            );
            
            // Get the test value
            $retrievedValue = $redis->get($testKey);
            $this->assertEquals($testValue, $retrievedValue, 'Should be able to retrieve the set value from cache');
            
            // Delete the test value
            $deleted = $redis->del($testKey);
            $this->assertTrue($deleted > 0, 'Should be able to delete the test value from cache');
            
        } catch (\Exception $e) {
            $this->fail('Failed to establish Redis cache connection: ' . $e->getMessage());
        }
    }

    public function test_redis_info_command()
    {
        try {
            $redis = Redis::connection('default');
            $info = $redis->info();
            
            $this->assertIsArray($info, 'Redis INFO command should return an array');
            
            // Handle different Redis client INFO response formats
            $hasVersionInfo = isset($info['redis_version']) || 
                             isset($info['Redis']['redis_version']) ||
                             isset($info['server']['redis_version']) ||
                             isset($info['Server']['redis_version']);
            
            if (!$hasVersionInfo) {
                // Try to find version in any section
                foreach ($info as $section => $data) {
                    if (is_array($data) && isset($data['redis_version'])) {
                        $hasVersionInfo = true;
                        break;
                    }
                }
            }
            
            $this->assertTrue($hasVersionInfo, 'Redis INFO should contain version information. Available keys: ' . implode(', ', array_keys($info)));
            
        } catch (\Exception $e) {
            $this->fail('Redis INFO command failed: ' . $e->getMessage());
        }
    }

    public function test_redis_ping_command()
    {
        try {
            $redis = Redis::connection('default');
            $pong = $redis->ping();
            
            // Handle different client responses:
            // phpredis: returns true or +PONG
            // predis: returns Status object with "PONG" payload or string "PONG"
            $isValidPong = $pong === true || 
                          $pong === 'PONG' || 
                          $pong === '+PONG' ||
                          (is_object($pong) && method_exists($pong, 'getPayload') && $pong->getPayload() === 'PONG') ||
                          (is_object($pong) && isset($pong->payload) && $pong->payload === 'PONG');
            
            $this->assertTrue($isValidPong, 'Redis PING should return PONG or true. Got: ' . var_export($pong, true));
            
        } catch (\Exception $e) {
            $this->fail('Redis PING command failed: ' . $e->getMessage());
        }
    }

    public function test_laravel_cache_redis_integration()
    {
        // Skip if cache driver is not Redis
        if (config('cache.default') !== 'redis') {
            $this->fail('Cache driver is not set to Redis');
        }

        try {
            $testKey = 'laravel_cache_test_' . bin2hex(random_bytes(8));
            $testValue = 'laravel_cache_value_' . bin2hex(random_bytes(8));
            
            // Test Laravel Cache facade with Redis
            Cache::put($testKey, $testValue, 60);
            $this->assertTrue(Cache::has($testKey), 'Cache should have the test key');
            
            $retrievedValue = Cache::get($testKey);
            $this->assertEquals($testValue, $retrievedValue, 'Cache should return the correct value');
            
            Cache::forget($testKey);
            $this->assertFalse(Cache::has($testKey), 'Cache should not have the key after forgetting');
            
        } catch (\Exception $e) {
            $this->fail('Laravel Cache Redis integration failed: ' . $e->getMessage());
        }
    }

    public function test_redis_connection_settings()
    {
        $config = config('database.redis.default');
        
        $this->assertIsArray($config, 'Redis default config should be an array');
        $this->assertArrayHasKey('host', $config, 'Redis host setting should exist');
        $this->assertArrayHasKey('port', $config, 'Redis port setting should exist');
        $this->assertArrayHasKey('database', $config, 'Redis database setting should exist');
        
        // Test basic connection settings
        $this->assertNotEmpty($config['host'], 'Redis host should not be empty');
        $this->assertIsNumeric($config['port'], 'Redis port should be numeric');
        $this->assertIsNumeric($config['database'], 'Redis database should be numeric');
        
        // Test retry settings
        $this->assertArrayHasKey('max_retries', $config, 'Redis should have max_retries setting');
        $this->assertIsNumeric($config['max_retries'], 'Redis max_retries should be numeric');
        
        // Test backoff settings
        $this->assertArrayHasKey('backoff_algorithm', $config, 'Redis should have backoff_algorithm setting');
        $this->assertArrayHasKey('backoff_base', $config, 'Redis should have backoff_base setting');
        $this->assertArrayHasKey('backoff_cap', $config, 'Redis should have backoff_cap setting');
    }

    public function test_redis_client_type()
    {
        $client = config('database.redis.client', 'phpredis');
        
        $this->assertContains($client, ['phpredis', 'predis'], 'Redis client should be either phpredis or predis');
        
        if ($client === 'phpredis') {
            $this->assertTrue(extension_loaded('redis'), 'PhpRedis extension should be loaded');
        } elseif ($client === 'predis') {
            $this->assertTrue(class_exists('Predis\Client'), 'Predis client class should exist');
        }
    }

    public function test_redis_prefix_configuration()
    {
        $options = config('database.redis.options');
        
        if (isset($options['prefix'])) {
            $this->assertIsString($options['prefix'], 'Redis prefix should be a string');
            $this->assertNotEmpty($options['prefix'], 'Redis prefix should not be empty if set');
        }
    }
}
