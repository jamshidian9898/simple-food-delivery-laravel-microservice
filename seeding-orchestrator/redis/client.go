package redis

import (
	"context"
	"fmt"
	"strings"
	"time"

	"github.com/redis/go-redis/v9"

	"seeding-orchestrator/config"
)

// Client wraps Redis operations for global cache cleanup
type Client struct {
	rdb *redis.Client
	ctx context.Context
}

// NewClient creates a new Redis Client configured from cfg and verifies connectivity.
//
// NewClient constructs a redis.Client using cfg.Host, cfg.Port, cfg.Password and DB 0, uses context.Background() for requests, and attempts a Ping to validate the connection. It returns a ready-to-use *Client on success or an error if the ping fails.
func NewClient(cfg *config.RedisConfig) (*Client, error) {
	rdb := redis.NewClient(&redis.Options{
		Addr:     fmt.Sprintf("%s:%d", cfg.Host, cfg.Port),
		Password: cfg.Password,
		DB:       0, // use default DB
	})

	ctx := context.Background()

	// Test the connection
	_, err := rdb.Ping(ctx).Result()
	if err != nil {
		return nil, fmt.Errorf("failed to connect to Redis: %v", err)
	}

	return &Client{
		rdb: rdb,
		ctx: ctx,
	}, nil
}

// CleanupSeedData removes all Redis keys with "seed*" prefix
func (c *Client) CleanupSeedData() error {
	fmt.Println("🧹 Connecting to global Redis for cleanup...")

	// Find all keys with seed* prefix
	keys, err := c.rdb.Keys(c.ctx, "seed*").Result()
	if err != nil {
		return fmt.Errorf("failed to find seed keys: %v", err)
	}

	if len(keys) == 0 {
		fmt.Println("   ✅ No seed data found in Redis cache")
		return nil
	}

	fmt.Printf("   🔍 Found %d keys with 'seed*' prefix\n", len(keys))

	// Delete all found keys
	deleted, err := c.rdb.Del(c.ctx, keys...).Result()
	if err != nil {
		return fmt.Errorf("failed to delete seed keys: %v", err)
	}

	fmt.Printf("   ✅ Successfully deleted %d seed keys from Redis\n", deleted)

	// Show some example keys that were deleted (max 5)
	if len(keys) > 0 {
		fmt.Println("   📋 Sample deleted keys:")
		maxShow := 5
		if len(keys) < maxShow {
			maxShow = len(keys)
		}
		for i := 0; i < maxShow; i++ {
			fmt.Printf("     - %s\n", keys[i])
		}
		if len(keys) > maxShow {
			fmt.Printf("     ... and %d more\n", len(keys)-maxShow)
		}
	}

	return nil
}

// TestConnection tests the Redis connection
func (c *Client) TestConnection() error {
	start := time.Now()
	pong, err := c.rdb.Ping(c.ctx).Result()
	duration := time.Since(start)

	if err != nil {
		return fmt.Errorf("Redis connection failed: %v", err)
	}

	fmt.Printf("✅ Redis connection successful (%s) - %v\n", pong, duration.Round(time.Millisecond))
	return nil
}

// GetInfo returns Redis server information
func (c *Client) GetInfo() (map[string]string, error) {
	info, err := c.rdb.Info(c.ctx).Result()
	if err != nil {
		return nil, fmt.Errorf("failed to get Redis info: %v", err)
	}

	// Parse basic info
	result := make(map[string]string)
	lines := strings.Split(info, "\r\n")
	for _, line := range lines {
		if strings.Contains(line, ":") && !strings.HasPrefix(line, "#") {
			parts := strings.SplitN(line, ":", 2)
			if len(parts) == 2 {
				result[parts[0]] = parts[1]
			}
		}
	}

	return result, nil
}

// Close closes the Redis connection
func (c *Client) Close() error {
	return c.rdb.Close()
}
