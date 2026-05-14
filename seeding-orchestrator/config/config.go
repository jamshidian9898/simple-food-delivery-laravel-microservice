package config

import (
	"fmt"
	"os"
	"strconv"
	"strings"

	"gopkg.in/yaml.v3"
)

// Service represents a microservice with its seeding configuration
type Service struct {
	Name      string   `yaml:"name"`
	Container string   `yaml:"container"`
	Commands  []string `yaml:"commands"`
	DependsOn []string `yaml:"depends_on"`
}

// SeedConfig represents the complete seeding configuration
type SeedConfig struct {
	Services []Service `yaml:"services"`
}

// RedisConfig holds Redis connection configuration
type RedisConfig struct {
	Host     string
	Port     int
	Password string
}

// LoadSeedConfig reads and parses the YAML configuration file
func LoadSeedConfig(configPath string) (*SeedConfig, error) {
	data, err := os.ReadFile(configPath)
	if err != nil {
		return nil, fmt.Errorf("error reading config file '%s': %v", configPath, err)
	}

	var cfg SeedConfig
	if err := yaml.Unmarshal(data, &cfg); err != nil {
		return nil, fmt.Errorf("error parsing YAML: %v", err)
	}

	if err := validateConfig(&cfg); err != nil {
		return nil, fmt.Errorf("configuration validation failed: %v", err)
	}

	return &cfg, nil
}

// LoadRedisConfig loads Redis configuration from environment variables
func LoadRedisConfig() (*RedisConfig, error) {
	host := getEnv("GLOBAL_REDIS_HOST", "localhost")
	portStr := getEnv("GLOBAL_REDIS_PORT", "6379")
	password := getEnv("GLOBAL_REDIS_PASSWORD", "")

	port, err := strconv.Atoi(portStr)
	if err != nil {
		return nil, fmt.Errorf("invalid Redis port '%s': %v", portStr, err)
	}

	return &RedisConfig{
		Host:     host,
		Port:     port,
		Password: password,
	}, nil
}

// validateConfig validates the seeding configuration
func validateConfig(cfg *SeedConfig) error {
	if len(cfg.Services) == 0 {
		return fmt.Errorf("no services defined in configuration")
	}

	serviceNames := make(map[string]bool)
	for _, svc := range cfg.Services {
		if svc.Name == "" {
			return fmt.Errorf("service name cannot be empty")
		}
		if svc.Container == "" {
			return fmt.Errorf("container name cannot be empty for service '%s'", svc.Name)
		}
		if len(svc.Commands) == 0 {
			return fmt.Errorf("no commands defined for service '%s'", svc.Name)
		}

		if serviceNames[svc.Name] {
			return fmt.Errorf("duplicate service name '%s'", svc.Name)
		}
		serviceNames[svc.Name] = true
	}

	// Validate dependencies exist
	for _, svc := range cfg.Services {
		for _, dep := range svc.DependsOn {
			if !serviceNames[dep] {
				return fmt.Errorf("service '%s' depends on undefined service '%s'", svc.Name, dep)
			}
		}
	}

	// Detect circular dependencies
	if err := detectCycles(cfg); err != nil {
		return err
	}

	return nil
}

// detectCycles checks for circular dependencies using DFS
func detectCycles(cfg *SeedConfig) error {
	// Build adjacency list
	graph := make(map[string][]string)
	for _, svc := range cfg.Services {
		graph[svc.Name] = svc.DependsOn
	}

	visited := make(map[string]bool)
	recStack := make(map[string]bool)

	var dfs func(string) error
	dfs = func(node string) error {
		visited[node] = true
		recStack[node] = true

		for _, dep := range graph[node] {
			if !visited[dep] {
				if err := dfs(dep); err != nil {
					return err
				}
			} else if recStack[dep] {
				return fmt.Errorf("circular dependency detected: '%s' -> '%s'", node, dep)
			}
		}

		recStack[node] = false
		return nil
	}

	for _, svc := range cfg.Services {
		if !visited[svc.Name] {
			if err := dfs(svc.Name); err != nil {
				return err
			}
		}
	}

	return nil
}

	return nil
}

// getEnv gets an environment variable with a default value
func getEnv(key, defaultValue string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return defaultValue
}

// LoadEnvFile loads environment variables from .env file in project root
func LoadEnvFile() error {
	// Try to find .env file in parent directory (project root)
	envPath := "../.env"
	if _, err := os.Stat(envPath); os.IsNotExist(err) {
		// If not found, it's not an error - env vars might be set elsewhere
		return nil
	}

	data, err := os.ReadFile(envPath)
	if err != nil {
		return fmt.Errorf("error reading .env file: %v", err)
	}

	lines := strings.Split(string(data), "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line == "" || strings.HasPrefix(line, "#") {
			continue
		}

		parts := strings.SplitN(line, "=", 2)
		if len(parts) == 2 {
			key := strings.TrimSpace(parts[0])
			value := strings.TrimSpace(parts[1])
			
			// Only set if not already set
			if os.Getenv(key) == "" {
				os.Setenv(key, value)
			}
		}
	}

	return nil
}
