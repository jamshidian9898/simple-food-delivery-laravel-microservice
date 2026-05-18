package cli

import (
	"fmt"
	"os"
	"strings"

	"seeding-orchestrator/config"
	"seeding-orchestrator/redis"
	"seeding-orchestrator/service"
)

// ShowHelp prints the program title, usage line, argument and option descriptions, and example invocations to standard output.
func ShowHelp() {
	fmt.Println("🌱 Microservices Seeding Orchestrator")
	fmt.Println("")
	fmt.Println("Usage: ./seed-orchestrator [config.yml] [OPTIONS]")
	fmt.Println("")
	fmt.Println("Arguments:")
	fmt.Println("  config.yml    Configuration file (default: seed.yml)")
	fmt.Println("")
	fmt.Println("Options:")
	fmt.Println("  --dry-run     Show what would be executed without running commands")
	fmt.Println("  --cleanup     Run smart cleanup after seeding (interactive menu)")
	fmt.Println("  --status      Show system status and configuration")
	fmt.Println("  --help, -h    Show this help message")
	fmt.Println("")
	fmt.Println("Examples:")
	fmt.Println("  ./seed-orchestrator                    # Run with default seed.yml")
	fmt.Println("  ./seed-orchestrator --dry-run          # Preview what would be executed")
	fmt.Println("  ./seed-orchestrator --cleanup          # Run seeding with cleanup menu")
	fmt.Println("  ./seed-orchestrator --status           # Check system status")
	fmt.Println("  ./seed-orchestrator custom.yml         # Use custom config file")
}

// ShowStatus displays the current orchestrator status across configuration, containers, and Redis.
// 
// It prints three sections to standard output:
// - Configuration: checks the given configPath for existence and, if readable, lists configured services.
// - Container Status: reports running state for a fixed set of service containers.
// - Global Redis Status: attempts to load environment and Redis configuration, then connects to Redis to report host/port and whether authentication is configured.
// 
// The function uses configPath to locate the seed configuration file and emits informative messages and errors to stdout; it does not return a value.
func ShowStatus(configPath string) {
	fmt.Println("📊 Seeding Orchestrator Status")
	fmt.Println("")

	// Configuration file status
	fmt.Println("Configuration:")
	if _, err := os.Stat(configPath); err == nil {
		fmt.Printf("✅ %s found\n", configPath)

		// Try to read and show services
		if cfg, err := config.LoadSeedConfig(configPath); err == nil {
			fmt.Println("Services configured:")
			for _, svc := range cfg.Services {
				fmt.Printf("  - %s\n", svc.Name)
			}
		}
	} else {
		fmt.Printf("❌ %s not found\n", configPath)
	}
	fmt.Println("")

	// Container status
	fmt.Println("Container Status:")
	services := []string{"user-service", "product-service", "order-service", "payment-service", "notification-service"}
	for _, serviceName := range services {
		if service.IsContainerRunning(serviceName) {
			fmt.Printf("✅ %s is running\n", serviceName)
		} else {
			fmt.Printf("❌ %s is not running\n", serviceName)
		}
	}

	fmt.Println("")

	// Redis connection status
	fmt.Println("Global Redis Status:")
	if err := config.LoadEnvFile(); err != nil {
		fmt.Printf("⚠️  Could not load .env file: %v\n", err)
	}

	if redisConfig, err := config.LoadRedisConfig(); err == nil {
		// test connection to redis
		redisClient, err := redis.NewClient(redisConfig)
		if err != nil {
			fmt.Printf("❌ Redis client creation failed: %v\n", err)
			return
		}
		defer redisClient.Close()

		if err := redisClient.TestConnection(); err != nil {
			fmt.Printf("❌ Redis connection error: %v\n", err)
			return
		}

		fmt.Printf("📍 Redis Host: %s:%d\n", redisConfig.Host, redisConfig.Port)
		fmt.Printf("🔐 Redis Auth: %s\n", func() string {
			if redisConfig.Password != "" && redisConfig.Password != "null" {
				return "Configured"
			}
			return "No password"
		}())
	} else {
		fmt.Printf("❌ Redis config error: %v\n", err)
	}
}

// ContainsArg reports whether the exact argument string arg appears in the command-line arguments (os.Args[1:]).
// It returns true if an exact match is found, false otherwise.
func ContainsArg(arg string) bool {
	for _, a := range os.Args[1:] {
		if a == arg {
			return true
		}
	}
	return false
}

// ParseArgs parses command-line arguments and selects the configuration path and mode flags.
// 
// If the first argument is "help", "--help", or "-h", it sets help to true and returns immediately.
// By default configPath is "seed.yml"; if the first argument exists and does not start with "--"
// it is treated as the configPath override. The boolean flags cleanup, dryRun, and status are set
// when the corresponding arguments `--cleanup`, `--dry-run`, and `--status` are present.
func ParseArgs() (configPath string, dryRun, cleanup, status, help bool) {
	// Handle help command
	if len(os.Args) > 1 && (os.Args[1] == "help" || os.Args[1] == "--help" || os.Args[1] == "-h") {
		help = true
		return
	}

	// Default config path
	configPath = "seed.yml"
	if len(os.Args) > 1 && !strings.HasPrefix(os.Args[1], "--") {
		configPath = os.Args[1]
	}

	cleanup = ContainsArg("--cleanup")
	dryRun = ContainsArg("--dry-run")
	status = ContainsArg("--status")

	return
}
