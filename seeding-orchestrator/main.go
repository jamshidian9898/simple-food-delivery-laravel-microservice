package main

import (
	"fmt"
	"log"
	"os"

	"seeding-orchestrator/cli"
	"seeding-orchestrator/config"
	"seeding-orchestrator/redis"
	"seeding-orchestrator/service"
)

func main() {
	// Load environment variables from .env file
	if err := config.LoadEnvFile(); err != nil {
		log.Printf("⚠️  Warning: Could not load .env file: %v", err)
	}

	// Parse command line arguments
	configPath, dryRun, cleanup, status, help := cli.ParseArgs()

	// Handle help command
	if help {
		cli.ShowHelp()
		os.Exit(0)
	}

	// Handle status command
	if status {
		cli.ShowStatus(configPath)
		os.Exit(0)
	}

	// Load seed configuration
	cfg, err := config.LoadSeedConfig(configPath)
	if err != nil {
		log.Fatalf("❌ %v", err)
	}

	fmt.Printf("📋 Configuration: %s\n", configPath)

	// Create and execute orchestrator
	orchestrator := service.NewOrchestrator(cfg, dryRun, cleanup)
	if err := orchestrator.Execute(); err != nil {
		log.Fatalf("❌ Orchestration failed: %v", err)
	}

	// After successful seeding, cleanup Redis cache if not in dry-run mode
	if !dryRun {
		fmt.Println()
		if err := cleanupRedisCache(); err != nil {
			log.Printf("⚠️  Redis cleanup failed: %v", err)
		}
	}
}

// cleanupRedisCache connects to global Redis and removes all keys with seed* prefix
func cleanupRedisCache() error {
	// Load Redis configuration
	redisConfig, err := config.LoadRedisConfig()
	if err != nil {
		return fmt.Errorf("failed to load Redis config: %v", err)
	}

	// Create Redis client
	redisClient, err := redis.NewClient(redisConfig)
	if err != nil {
		return fmt.Errorf("failed to connect to Redis: %v", err)
	}
	defer redisClient.Close()

	// Test connection first
	if err := redisClient.TestConnection(); err != nil {
		return err
	}

	// Cleanup seed data
	return redisClient.CleanupSeedData()
}
