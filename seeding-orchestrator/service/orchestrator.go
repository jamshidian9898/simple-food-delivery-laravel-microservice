package service

import (
	"bufio"
	"fmt"
	"os"
	"os/exec"
	"strconv"
	"strings"
	"time"

	"seeding-orchestrator/config"
	"seeding-orchestrator/redis"
)

// Orchestrator handles the seeding orchestration logic
type Orchestrator struct {
	config  *config.SeedConfig
	dryRun  bool
	cleanup bool
}

// NewOrchestrator constructs an Orchestrator configured with the provided SeedConfig and execution flags.
// The cfg defines services and their dependencies; dryRun enables a report-only mode that does not execute commands; cleanup selects the cleanup workflow instead of normal seeding.
func NewOrchestrator(cfg *config.SeedConfig, dryRun, cleanup bool) *Orchestrator {
	return &Orchestrator{
		config:  cfg,
		dryRun:  dryRun,
		cleanup: cleanup,
	}
}

// Execute runs the seeding orchestration
func (o *Orchestrator) Execute() error {
	startTime := time.Now()

	fmt.Println("🚀 Starting microservices seeding orchestrator...")
	fmt.Printf("🔧 Mode: %s\n", o.getMode())
	fmt.Println("=" + strings.Repeat("=", 60))
	fmt.Println()

	startTime := time.Now()

	fmt.Println("🚀 Starting microservices seeding orchestrator...")
	fmt.Printf("🔧 Mode: %s\n", o.getMode())
	fmt.Println("=" + strings.Repeat("=", 60))
	fmt.Println()

	// Execute seeding in dependency order
	if o.dryRun {
		// ...
	}

	duration := time.Since(startTime)
	fmt.Println("=" + strings.Repeat("=", 60))
	fmt.Printf("🎉 Seeding orchestration completed successfully in %v\n", duration.Round(time.Second))

	// Execute seeding in dependency order
	if o.dryRun {
		// For dry run, show what would be executed
		visited := map[string]bool{}
		for len(visited) < len(o.config.Services) {
			progress := false

			for _, svc := range o.config.Services {
				if visited[svc.Name] {
					continue
				}

				if o.allDependenciesCompleted(svc.DependsOn, visited) {
					fmt.Printf("▶️  Processing service: %s (container: %s)\n", svc.Name, svc.Container)
					fmt.Printf("   🔍 DRY RUN - Would execute %d commands:\n", len(svc.Commands))
					for i, cmd := range svc.Commands {
						fmt.Printf("   │ %d. %s\n", i+1, cmd)
					}
					fmt.Printf("✅ Completed: %s\n", svc.Name)
					fmt.Println()
					visited[svc.Name] = true
					progress = true
				}
			}

			if !progress {
				return fmt.Errorf("dependency loop detected or missing dependency. Check your seed.yml configuration")
			}
		}
	} else if o.cleanup {
		// For cleanup only, use the extracted runCleanupOperations method
		if err := o.runCleanupOperations(); err != nil {
			return fmt.Errorf("cleanup operations failed: %v", err)
		}
	} else {
		// For actual execution, use the extracted executeSeeding method
		if err := o.executeSeeding(); err != nil {
			return err
		}
	}

	fmt.Println("=" + strings.Repeat("=", 60))
	fmt.Printf("🎉 Seeding orchestration completed successfully in %v\n", duration.Round(time.Second))
	fmt.Printf("📊 Processed %d services\n", len(o.config.Services))

	return nil
}

// allDependenciesCompleted checks if all dependencies for a service have been completed
func (o *Orchestrator) allDependenciesCompleted(deps []string, visited map[string]bool) bool {
	for _, dep := range deps {
		if !visited[dep] {
			return false
		}
	}
	return true
}

// getMode returns the current execution mode
func (o *Orchestrator) getMode() string {
	mode := "Production"
	if o.dryRun {
		mode = "Dry Run"
	}
	if o.cleanup {
		mode += " + Cleanup"
	}
	return mode
}

// IsContainerRunning reports whether a Docker container with the exact given name is currently listed as running.
// If invoking `docker ps` fails, it returns false.
func IsContainerRunning(container string) bool {
	cmd := exec.Command("docker", "ps", "--format", "{{.Names}}", "--filter", fmt.Sprintf("name=%s", container))
	output, err := cmd.Output()
	if err != nil {
		return false
	}

	lines := strings.Split(strings.TrimSpace(string(output)), "\n")
	for _, line := range lines {
		if strings.TrimSpace(line) == container {
			return true
		}
	}
	return false
}

// RunSeedCommand executes the given shell command inside the specified Docker container and prints any non-empty lines of the command output prefixed with an indented marker.
// It returns an error if the docker exec invocation fails; the returned error includes the underlying execution error and the command's combined output.
func RunSeedCommand(container, command string) error {
	cmd := exec.Command("docker", "exec", container, "sh", "-c", command)

	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("command failed: %v\nOutput: %s", err, string(output))
	}

	// Display command output with proper formatting
	lines := strings.Split(string(output), "\n")
	for _, line := range lines {
		trimmed := strings.TrimSpace(line)
		if trimmed != "" {
			fmt.Printf("   │ %s\n", line)
		}
	}

	return nil
}

// executeSeeding runs the seeding process with dependency order (extracted from Execute method)
func (o *Orchestrator) executeSeeding() error {
	visited := map[string]bool{}

	// Execute seeding in dependency order
	for len(visited) < len(o.config.Services) {
		progress := false

		for _, svc := range o.config.Services {
			if visited[svc.Name] {
				continue
			}

			if o.allDependenciesCompleted(svc.DependsOn, visited) {
				fmt.Printf("▶️  Processing service: %s (container: %s)\n", svc.Name, svc.Container)

				// Check if container is running
				if !IsContainerRunning(svc.Container) {
					fmt.Printf("   ⚠️  Warning: Container '%s' is not running. Skipping...\n", svc.Container)
					visited[svc.Name] = true
					progress = true
					continue
				}

				// Execute seeding commands
				for i, cmd := range svc.Commands {
					fmt.Printf("   🧩 [%d/%d] Running: %s\n", i+1, len(svc.Commands), cmd)
					if err := RunSeedCommand(svc.Container, cmd); err != nil {
						return fmt.Errorf("failed to execute command in %s: %v", svc.Container, err)
					}
				}

				fmt.Printf("✅ Completed: %s\n", svc.Name)
				fmt.Println()
				visited[svc.Name] = true
				progress = true
			}
		}

		if !progress {
			return fmt.Errorf("dependency loop detected or missing dependency. Check your seed.yml configuration")
		}
	}

	return nil
}

// runCleanupOperations handles smart cleanup with user confirmation
func (o *Orchestrator) runCleanupOperations() error {
	fmt.Println()
	fmt.Println("🧹 Smart Cleanup Operations")
	fmt.Println("=" + strings.Repeat("=", 30))
	fmt.Println()
	fmt.Println("Choose cleanup level:")
	fmt.Println("  1️⃣  Redis Cache Only (Safe - clears cross-service data)")
	fmt.Println("  2️⃣  Seeded Data Only (Moderate - clears seeded records)")
	fmt.Println("  3️⃣  Full Database Reset (Destructive - wipes all data)")
	fmt.Println("  0️⃣  Skip Cleanup")
	fmt.Println()
	fmt.Print("Enter your choice (0-3): ")

	reader := bufio.NewReader(os.Stdin)
	input, err := reader.ReadString('\n')
	if err != nil {
		return fmt.Errorf("failed to read user input: %v", err)
	}

	choice, err := strconv.Atoi(strings.TrimSpace(input))
	if err != nil {
		fmt.Println("⚠️  Invalid input, skipping cleanup")
		return nil
	}

	fmt.Println()

	switch choice {
	case 1:
		return o.cleanupRedisOnly()
	case 2:
		return o.cleanupSeededData()
	case 3:
		return o.cleanupFullReset()
	case 0:
		fmt.Println("⏭️  Skipping cleanup operations")
		return nil
	default:
		fmt.Println("⚠️  Invalid choice, skipping cleanup")
		return nil
	}
}

// cleanupRedisOnly clears only Redis cache data
func (o *Orchestrator) cleanupRedisOnly() error {
	fmt.Println("🗑️  Clearing Redis seed data...")

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
		return fmt.Errorf("Redis connection test failed: %v", err)
	}

	// Cleanup seed data
	if err := redisClient.CleanupSeedData(); err != nil {
		return fmt.Errorf("Redis cleanup failed: %v", err)
	}

	fmt.Println("✅ Redis cleanup completed successfully")
	return nil
}

// cleanupSeededData clears only seeded database records
func (o *Orchestrator) cleanupSeededData() error {
	fmt.Println("🗑️  Clearing seeded database records...")

	// First, run migrate:refresh on all services from YAML config
	successCount := 0
	for _, svc := range o.config.Services {
		if !IsContainerRunning(svc.Container) {
			fmt.Printf("   ⚠️  %s container not running, skipping...\n", svc.Name)
			continue
		}

		fmt.Printf("   🧽 Refreshing %s database...\n", svc.Name)
		if err := RunSeedCommand(svc.Container, "php artisan db:wipe"); err != nil {
			fmt.Printf("   ❌ Failed to wipe %s: %v\n", svc.Name, err)
			continue
		}

		if err := RunSeedCommand(svc.Container, "php artisan migrate"); err != nil {
			fmt.Printf("   ❌ Failed to migrate %s: %v\n", svc.Name, err)
			continue
		}
		fmt.Printf("   ✅ %s database refreshed\n", svc.Name)
		successCount++
	}

	fmt.Printf("✅ Database refresh completed (%d services processed)\n", successCount)
	fmt.Println()

	// Then re-seed using orchestrator's internal logic to maintain dependency order
	fmt.Println("🌱 Re-seeding with proper dependency order...")
	if err := o.executeSeeding(); err != nil {
		return fmt.Errorf("re-seeding failed: %v", err)
	}

	// Also cleanup Redis
	if err := o.cleanupRedisOnly(); err != nil {
		fmt.Printf("   ⚠️  Redis cleanup failed: %v\n", err)
	}

	return nil
}

// cleanupFullReset performs complete database reset
func (o *Orchestrator) cleanupFullReset() error {
	fmt.Println("⚠️  PERFORMING FULL DATABASE RESET...")
	fmt.Println("   This will destroy ALL data in all databases!")
	fmt.Print("   Are you sure? Type 'YES' to confirm: ")

	reader := bufio.NewReader(os.Stdin)
	confirmation, err := reader.ReadString('\n')
	if err != nil {
		return fmt.Errorf("failed to read confirmation: %v", err)
	}

	if strings.TrimSpace(confirmation) != "YES" {
		fmt.Println("   ⏭️  Full reset cancelled")
		return nil
	}

	fmt.Println()
	fmt.Println("💥 Resetting all databases...")

	// First, run migrate:fresh on all services from YAML config
	successCount := 0
	for _, svc := range o.config.Services {
		if !IsContainerRunning(svc.Container) {
			fmt.Printf("   ⚠️  %s container not running, skipping...\n", svc.Name)
			continue
		}

		fmt.Printf("   💥 Resetting %s database...\n", svc.Name)
		if err := RunSeedCommand(svc.Container, "php artisan migrate:fresh"); err != nil {
			fmt.Printf("   ❌ Failed to reset %s: %v\n", svc.Name, err)
			continue
		}
		fmt.Printf("   ✅ %s database reset\n", svc.Name)
		successCount++
	}

	fmt.Printf("✅ Database reset completed (%d services processed)\n", successCount)
	fmt.Println()

	// Then re-seed using orchestrator's internal logic to maintain dependency order
	fmt.Println("🌱 Re-seeding with proper dependency order...")
	if err := o.executeSeeding(); err != nil {
		return fmt.Errorf("re-seeding failed: %v", err)
	}

	// Also cleanup Redis
	if err := o.cleanupRedisOnly(); err != nil {
		fmt.Printf("   ⚠️  Redis cleanup failed: %v\n", err)
	}
	return nil
}
