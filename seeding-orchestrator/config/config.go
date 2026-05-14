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

// LoadSeedConfig loads the seed configuration from the YAML file at configPath into a SeedConfig.
// It validates the resulting configuration and returns an error if the file cannot be read, the YAML cannot be parsed, or validation fails.
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

// LoadRedisConfig loads Redis connection settings from environment variables.
// It reads GLOBAL_REDIS_HOST (default "localhost"), GLOBAL_REDIS_PORT (default "6379"),
// and GLOBAL_REDIS_PASSWORD (default ""), parses the port as an integer, and returns a RedisConfig.
// An error is returned if the port value cannot be parsed as an integer.
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

// validateConfig verifies that a SeedConfig is well-formed.
// It requires at least one service, each service to have a non-empty name and container and at least one command,
// service names to be unique, and every dependency listed in DependsOn to reference a defined service.
// It returns an error describing the first validation failure encountered, or nil if the configuration is valid.
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

	return nil
}

// getEnv retrieves the value of the environment variable named by key; if the
// variable is unset or empty, it returns defaultValue.
func getEnv(key, defaultValue string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return defaultValue
}

// LoadEnvFile loads environment variables from a `.env` file located at the repository root (../.env).
// 
// If the file does not exist, the function returns nil. When present, the file is read and parsed
// line-by-line; blank lines and lines beginning with `#` are ignored. Each non-comment line is split
// at the first `=` into a key and value, both trimmed of surrounding whitespace. Environment variables
// are set only when the key is not already present in the process environment. Errors are returned
// only for failures reading the `.env` file.
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
