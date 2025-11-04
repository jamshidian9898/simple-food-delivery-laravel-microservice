package service

import (
	"fmt"
	"log"
	"os/exec"
	"strings"
	"time"

	"seeding-orchestrator/config"
)

// Orchestrator handles the seeding orchestration logic
type Orchestrator struct {
	config  *config.SeedConfig
	dryRun  bool
	cleanup bool
}

// NewOrchestrator creates a new orchestrator instance
func NewOrchestrator(cfg *config.SeedConfig, dryRun, cleanup bool) *Orchestrator {
	return &Orchestrator{
		config:  cfg,
		dryRun:  dryRun,
		cleanup: cleanup,
	}
}

// Execute runs the seeding orchestration
func (o *Orchestrator) Execute() error {
	visited := map[string]bool{}
	startTime := time.Now()

	fmt.Println("🚀 Starting microservices seeding orchestrator...")
	fmt.Printf("🔧 Mode: %s\n", o.getMode())
	fmt.Println("=" + strings.Repeat("=", 60))
	fmt.Println()

	// Execute seeding in dependency order
	for len(visited) < len(o.config.Services) {
		progress := false

		for _, svc := range o.config.Services {
			if visited[svc.Name] {
				continue
			}

			if o.allDependenciesCompleted(svc.DependsOn, visited) {
				fmt.Printf("▶️  Processing service: %s (container: %s)\n", svc.Name, svc.Container)

				if o.dryRun {
					fmt.Printf("   🔍 DRY RUN - Would execute %d commands:\n", len(svc.Commands))
					for i, cmd := range svc.Commands {
						fmt.Printf("   │ %d. %s\n", i+1, cmd)
					}
				} else {
					// Check if container is running
					if !IsContainerRunning(svc.Container) {
						log.Printf("   ⚠️  Warning: Container '%s' is not running. Skipping...", svc.Container)
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

	duration := time.Since(startTime)

	if o.cleanup && !o.dryRun {
		fmt.Println("🧹 Running cleanup operations...")
		fmt.Println("   💡 Cleanup functionality can be extended here")
		fmt.Println()
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

// IsContainerRunning checks if a Docker container is running
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

// RunSeedCommand executes a seeding command inside a Docker container
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
