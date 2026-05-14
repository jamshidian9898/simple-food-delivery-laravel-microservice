# Microservices Management Makefile
# Dynamic service management for notification, order, payment, product, and user services

# Colors for output
RED := \033[31m
GREEN := \033[32m
YELLOW := \033[33m
BLUE := \033[34m
MAGENTA := \033[35m
CYAN := \033[36m
WHITE := \033[37m
RESET := \033[0m

# Service definitions
SERVICES := notification order payment product user
INFRASTRUCTURE := traefik redis rabbitmq mailpit

# Default target - show help
.DEFAULT_GOAL := help

# Help target
.PHONY: help
help: ## Show this help message
	@echo "$(CYAN)🚀 Microservices Management Commands$(RESET)"
	@echo ""
	@echo "$(YELLOW)📋 General Commands:$(RESET)"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  $(GREEN)%-20s$(RESET) %s\n", $$1, $$2}' $(MAKEFILE_LIST) | grep -E "^  [^-]"
	@echo ""
	@echo "$(YELLOW)🔧 Service Management:$(RESET)"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  $(GREEN)%-20s$(RESET) %s\n", $$1, $$2}' $(MAKEFILE_LIST) | grep -E "service|logs"
	@echo ""
	@echo "$(YELLOW)🏗️  Infrastructure:$(RESET)"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  $(GREEN)%-20s$(RESET) %s\n", $$1, $$2}' $(MAKEFILE_LIST) | grep -E "infra|db"
	@echo ""
	@echo "$(YELLOW)📊 Monitoring & Logs:$(RESET)"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  $(GREEN)%-20s$(RESET) %s\n", $$1, $$2}' $(MAKEFILE_LIST) | grep -E "logs|status|monitor"
	@echo ""
	@echo "$(YELLOW)🧹 Maintenance:$(RESET)"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  $(GREEN)%-20s$(RESET) %s\n", $$1, $$2}' $(MAKEFILE_LIST) | grep -E "clean|reset|rebuild"
	@echo ""
	@echo "$(BLUE)Available Services: $(WHITE)$(SERVICES)$(RESET)"
	@echo "$(BLUE)Infrastructure: $(WHITE)$(INFRASTRUCTURE)$(RESET)"
	@echo ""
	@echo "$(MAGENTA)Examples:$(RESET)"
	@echo "  make up                    # Start all services"
	@echo "  make service-up-notification  # Start notification service only"
	@echo "  make logs-notification     # View notification service logs"
	@echo "  make status               # Check all services status"

# =============================================================================
# GENERAL COMMANDS
# =============================================================================

.PHONY: up
up: ## Start all services
	@echo "$(GREEN)🚀 Starting all services...$(RESET)"
	@docker-compose up -d
	@echo "$(GREEN)✅ All services started successfully!$(RESET)"

.PHONY: down
down: ## Stop all services
	@echo "$(YELLOW)🛑 Stopping all services...$(RESET)"
	@docker-compose down
	@echo "$(GREEN)✅ All services stopped successfully!$(RESET)"

.PHONY: restart
restart: ## Restart all services
	@echo "$(YELLOW)🔄 Restarting all services...$(RESET)"
	@docker-compose restart
	@echo "$(GREEN)✅ All services restarted successfully!$(RESET)"

.PHONY: build
build: ## Build all services
	@echo "$(BLUE)🔨 Building all services...$(RESET)"
	@docker-compose build --no-cache
	@echo "$(GREEN)✅ All services built successfully!$(RESET)"

.PHONY: rebuild
rebuild: down build up ## Rebuild and restart all services

# =============================================================================
# INFRASTRUCTURE MANAGEMENT
# =============================================================================

.PHONY: infra-up
infra-up: ## Start infrastructure services only (traefik, redis, rabbitmq, mailpit)
	@echo "$(BLUE)🏗️  Starting infrastructure services...$(RESET)"
	@docker-compose up -d $(INFRASTRUCTURE)
	@echo "$(GREEN)✅ Infrastructure services started!$(RESET)"

.PHONY: infra-down
infra-down: ## Stop infrastructure services only
	@echo "$(YELLOW)🏗️  Stopping infrastructure services...$(RESET)"
	@docker-compose stop $(INFRASTRUCTURE)
	@echo "$(GREEN)✅ Infrastructure services stopped!$(RESET)"

.PHONY: db-up
db-up: ## Start all database services
	@echo "$(BLUE)🗄️  Starting database services...$(RESET)"
	@docker-compose up -d notification-db order-db payment-db product-db user-db
	@echo "$(GREEN)✅ Database services started!$(RESET)"

.PHONY: db-down
db-down: ## Stop all database services
	@echo "$(YELLOW)🗄️  Stopping database services...$(RESET)"
	@docker-compose stop notification-db order-db payment-db product-db user-db
	@echo "$(GREEN)✅ Database services stopped!$(RESET)"

# =============================================================================
# INDIVIDUAL SERVICE MANAGEMENT
# =============================================================================

# Generate service-specific targets dynamically
define SERVICE_TARGETS
.PHONY: service-up-$(1)
service-up-$(1): ## Start $(1) service and its dependencies
	@echo "$(GREEN)🚀 Starting $(1) service...$(RESET)"
	@docker-compose up -d $(1)-service $(1)-nginx $(1)-db
	@echo "$(GREEN)✅ $(1) service started successfully!$(RESET)"

.PHONY: service-down-$(1)
service-down-$(1): ## Stop $(1) service and its dependencies
	@echo "$(YELLOW)🛑 Stopping $(1) service...$(RESET)"
	@docker-compose stop $(1)-service $(1)-nginx $(1)-db
	@echo "$(GREEN)✅ $(1) service stopped successfully!$(RESET)"

.PHONY: service-restart-$(1)
service-restart-$(1): ## Restart $(1) service and its dependencies
	@echo "$(YELLOW)🔄 Restarting $(1) service...$(RESET)"
	@docker-compose restart $(1)-service $(1)-nginx $(1)-db
	@echo "$(GREEN)✅ $(1) service restarted successfully!$(RESET)"

.PHONY: service-build-$(1)
service-build-$(1): ## Build $(1) service
	@echo "$(BLUE)🔨 Building $(1) service...$(RESET)"
	@docker-compose build --no-cache $(1)-service
	@echo "$(GREEN)✅ $(1) service built successfully!$(RESET)"

.PHONY: service-rebuild-$(1)
service-rebuild-$(1): service-down-$(1) service-build-$(1) service-up-$(1) ## Rebuild $(1) service

endef

# Generate targets for each service
$(foreach service,$(SERVICES),$(eval $(call SERVICE_TARGETS,$(service))))

# =============================================================================
# LOGGING AND MONITORING
# =============================================================================

.PHONY: logs
logs: ## Show logs for all services
	@echo "$(CYAN)📋 Showing logs for all services...$(RESET)"
	@docker-compose logs -f

.PHONY: logs-tail
logs-tail: ## Show last 100 lines of logs for all services
	@echo "$(CYAN)📋 Showing last 100 lines of logs...$(RESET)"
	@docker-compose logs --tail=100

# Generate logging targets for each service
define LOG_TARGETS
.PHONY: logs-$(1)
logs-$(1): ## Show logs for $(1) service
	@echo "$(CYAN)📋 Showing logs for $(1) service...$(RESET)"
	@docker-compose logs -f $(1)-service $(1)-nginx $(1)-db

.PHONY: logs-$(1)-tail
logs-$(1)-tail: ## Show last 50 lines of logs for $(1) service
	@echo "$(CYAN)📋 Showing last 50 lines for $(1) service...$(RESET)"
	@docker-compose logs --tail=50 $(1)-service $(1)-nginx $(1)-db

.PHONY: logs-$(1)-service
logs-$(1)-service: ## Show logs for $(1) PHP service only
	@echo "$(CYAN)📋 Showing logs for $(1) PHP service...$(RESET)"
	@docker-compose logs -f $(1)-service

.PHONY: logs-$(1)-nginx
logs-$(1)-nginx: ## Show logs for $(1) nginx only
	@echo "$(CYAN)📋 Showing logs for $(1) nginx...$(RESET)"
	@docker-compose logs -f $(1)-nginx

.PHONY: logs-$(1)-db
logs-$(1)-db: ## Show logs for $(1) database only
	@echo "$(CYAN)📋 Showing logs for $(1) database...$(RESET)"
	@docker-compose logs -f $(1)-db

endef

# Generate log targets for each service
$(foreach service,$(SERVICES),$(eval $(call LOG_TARGETS,$(service))))

# Infrastructure logging
.PHONY: logs-traefik
logs-traefik: ## Show traefik logs
	@echo "$(CYAN)📋 Showing traefik logs...$(RESET)"
	@docker-compose logs -f traefik

.PHONY: logs-redis
logs-redis: ## Show redis logs
	@echo "$(CYAN)📋 Showing redis logs...$(RESET)"
	@docker-compose logs -f redis

.PHONY: logs-rabbitmq
logs-rabbitmq: ## Show rabbitmq logs
	@echo "$(CYAN)📋 Showing rabbitmq logs...$(RESET)"
	@docker-compose logs -f rabbitmq

.PHONY: logs-mailpit
logs-mailpit: ## Show mailpit logs
	@echo "$(CYAN)📋 Showing mailpit logs...$(RESET)"
	@docker-compose logs -f mailpit

# =============================================================================
# STATUS AND MONITORING
# =============================================================================

.PHONY: status
status: ## Show status of all services
	@echo "$(CYAN)📊 Service Status Overview$(RESET)"
	@echo "$(YELLOW)========================$(RESET)"
	@docker-compose ps

.PHONY: status-detailed
status-detailed: ## Show detailed status with resource usage
	@echo "$(CYAN)📊 Detailed Service Status$(RESET)"
	@echo "$(YELLOW)===========================$(RESET)"
	@docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}\t{{.BlockIO}}"

.PHONY: health
health: ## Check health of all services
	@echo "$(CYAN)🏥 Health Check$(RESET)"
	@echo "$(YELLOW)===============$(RESET)"
	@for container in $$(docker-compose ps -q); do \
		name=$$(docker inspect --format='{{.Name}}' $$container | sed 's/\///'); \
		health=$$(docker inspect --format='{{.State.Health.Status}}' $$container 2>/dev/null || echo "no-health-check"); \
		status=$$(docker inspect --format='{{.State.Status}}' $$container); \
		if [ "$$health" = "healthy" ]; then \
			echo "$(GREEN)✅ $$name: $$status ($$health)$(RESET)"; \
		elif [ "$$health" = "unhealthy" ]; then \
			echo "$(RED)❌ $$name: $$status ($$health)$(RESET)"; \
		elif [ "$$status" = "running" ]; then \
			echo "$(YELLOW)⚡ $$name: $$status$(RESET)"; \
		else \
			echo "$(RED)🛑 $$name: $$status$(RESET)"; \
		fi; \
	done

.PHONY: monitor
monitor: ## Monitor services in real-time
	@echo "$(CYAN)📊 Real-time Service Monitoring$(RESET)"
	@echo "$(YELLOW)Press Ctrl+C to stop monitoring$(RESET)"
	@while true; do \
		clear; \
		echo "$(CYAN)📊 Service Status:$(RESET)"; \
		docker-compose ps; \
		echo ""; \
		echo "$(CYAN)📈 Resource Usage:$(RESET)"; \
		docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}" 2>/dev/null || echo "No running containers"; \
		echo ""; \
		echo "$(YELLOW)Updated: $$(date)$(RESET)"; \
		sleep 2; \
	done

# =============================================================================
# MAINTENANCE AND CLEANUP
# =============================================================================

.PHONY: clean
clean: ## Remove stopped containers and unused images
	@echo "$(YELLOW)🧹 Cleaning up Docker resources...$(RESET)"
	@docker-compose down --remove-orphans
	@docker system prune -f
	@echo "$(GREEN)✅ Cleanup completed!$(RESET)"

.PHONY: clean-all
clean-all: ## Remove all containers, images, and volumes (DESTRUCTIVE)
	@echo "$(RED)⚠️  WARNING: This will remove ALL data including databases!$(RESET)"
	@read -p "Are you sure? Type 'yes' to continue: " confirm && [ "$$confirm" = "yes" ] || exit 1
	@docker-compose down -v --remove-orphans
	@docker system prune -af --volumes
	@echo "$(GREEN)✅ Complete cleanup finished!$(RESET)"

.PHONY: reset-db
reset-db: ## Reset all databases (removes all data)
	@echo "$(RED)⚠️  WARNING: This will remove ALL database data!$(RESET)"
	@read -p "Are you sure? Type 'yes' to continue: " confirm && [ "$$confirm" = "yes" ] || exit 1
	@docker-compose down
	@docker volume rm $$(docker volume ls -q | grep -E "(notification|order|payment|product|user)_data") 2>/dev/null || true
	@docker-compose up -d
	@echo "$(GREEN)✅ Databases reset completed!$(RESET)"

# Generate database reset targets for individual services
define DB_RESET_TARGETS
.PHONY: reset-db-$(1)
reset-db-$(1): ## Reset $(1) database (removes all data)
	@echo "$(RED)⚠️  WARNING: This will remove ALL $(1) database data!$(RESET)"
	@read -p "Are you sure? Type 'yes' to continue: " confirm && [ "$$confirm" = "yes" ] || exit 1
	@docker-compose stop $(1)-db
	@docker volume rm $(1)_data 2>/dev/null || true
	@docker-compose up -d $(1)-db
	@echo "$(GREEN)✅ $(1) database reset completed!$(RESET)"

endef

# Generate database reset targets for each service
$(foreach service,$(SERVICES),$(eval $(call DB_RESET_TARGETS,$(service))))

# =============================================================================
# DEVELOPMENT HELPERS
# =============================================================================

.PHONY: shell
shell: ## Open shell in a service container (usage: make shell SERVICE=notification)
	@if [ -z "$(SERVICE)" ]; then \
		echo "$(RED)❌ Please specify SERVICE. Example: make shell SERVICE=notification$(RESET)"; \
		exit 1; \
	fi
	@echo "$(CYAN)🐚 Opening shell in $(SERVICE) service...$(RESET)"
	@docker-compose exec $(SERVICE)-service /bin/bash

.PHONY: composer
composer: ## Run composer command in a service (usage: make composer SERVICE=notification CMD="install")
	@if [ -z "$(SERVICE)" ] || [ -z "$(CMD)" ]; then \
		echo "$(RED)❌ Please specify SERVICE and CMD. Example: make composer SERVICE=notification CMD=\"install\"$(RESET)"; \
		exit 1; \
	fi
	@echo "$(CYAN)📦 Running composer $(CMD) in $(SERVICE) service...$(RESET)"
	@docker-compose exec $(SERVICE)-service composer $(CMD)

.PHONY: artisan
artisan: ## Run artisan command in a service (usage: make artisan SERVICE=notification CMD="migrate")
	@if [ -z "$(SERVICE)" ] || [ -z "$(CMD)" ]; then \
		echo "$(RED)❌ Please specify SERVICE and CMD. Example: make artisan SERVICE=notification CMD=\"migrate\"$(RESET)"; \
		exit 1; \
	fi
	@echo "$(CYAN)⚡ Running artisan $(CMD) in $(SERVICE) service...$(RESET)"
	@docker-compose exec $(SERVICE)-service php artisan $(CMD)

# =============================================================================
# QUICK ACCESS URLS
# =============================================================================

.PHONY: urls
urls: ## Show all service URLs
	@echo "$(CYAN)🌐 Service URLs$(RESET)"
	@echo "$(YELLOW)===============$(RESET)"
	@echo "$(GREEN)🎯 API Services:$(RESET)"
	@echo "  Notification: http://notification.api.localhost"
	@echo "  Order:        http://order.api.localhost"
	@echo "  Payment:      http://payment.api.localhost"
	@echo "  Product:      http://product.api.localhost"
	@echo "  User:         http://user.api.localhost"
	@echo ""
	@echo "$(GREEN)🔧 Infrastructure:$(RESET)"
	@echo "  Traefik:      http://localhost:8080"
	@echo "  RabbitMQ:     http://rabbitmq.api.localhost (admin/password)"
	@echo "  Mailpit:      http://mailpit.api.localhost"
	@echo ""
	@echo "$(GREEN)🗄️  Databases:$(RESET)"
	@echo "  Notification: localhost:3307"
	@echo "  Order:        localhost:3308"
	@echo "  Payment:      localhost:3309"
	@echo "  Product:      localhost:3310"
	@echo "  User:         localhost:3311"
	@echo "  Redis:        localhost:6379"

# =============================================================================
# TESTING HELPERS
# =============================================================================

.PHONY: test
test: ## Run tests for a specific service (usage: make test SERVICE=notification)
	@if [ -z "$(SERVICE)" ]; then \
		echo "$(RED)❌ Please specify SERVICE. Example: make test SERVICE=notification$(RESET)"; \
		exit 1; \
	fi
	@echo "$(CYAN)🧪 Running tests for $(SERVICE) service...$(RESET)"
	@docker-compose exec $(SERVICE)-service php artisan test

.PHONY: test-all
test-all: ## Run tests for all services
	@echo "$(CYAN)🧪 Running tests for all services...$(RESET)"
	@echo "$(BLUE)🔍 Checking infrastructure services...$(RESET)"
	@docker-compose ps rabbitmq | grep -q "Up" || (echo "$(YELLOW)⚠️  Starting RabbitMQ for infrastructure tests...$(RESET)" && docker-compose up -d rabbitmq && sleep 5)
	@for service in $(SERVICES); do \
		echo "$(YELLOW)Testing $$service service...$(RESET)"; \
		docker-compose exec $$service-service php artisan test || true; \
	done

.PHONY: test-infra
test-infra: ## Run infrastructure tests for all services (requires RabbitMQ)
	@echo "$(CYAN)🧪 Running infrastructure tests for all services...$(RESET)"
	@echo "$(BLUE)🔍 Ensuring RabbitMQ is running...$(RESET)"
	@docker-compose up -d rabbitmq
	@echo "$(YELLOW)⏳ Waiting for RabbitMQ to be ready...$(RESET)"
	@sleep 10
	@for service in $(SERVICES); do \
		echo "$(YELLOW)Testing $$service infrastructure...$(RESET)"; \
		if docker-compose exec $$service-service test -d tests/Infrastructure; then \
			docker-compose exec $$service-service ./vendor/bin/phpunit tests/Infrastructure/ --colors=always || true; \
		else \
			echo "$(BLUE)ℹ️  No infrastructure tests found for $$service service$(RESET)"; \
		fi; \
	done

	@echo "$(CYAN)🧪 Running Redis connection tests for all services...$(RESET)"
	@echo "$(BLUE)🔍 Ensuring Redis is running...$(RESET)"
	@docker-compose up -d redis
	@echo "$(YELLOW)⏳ Waiting for Redis to be ready...$(RESET)"
	@sleep 5
	`@for` service in $(SERVICES); do \
		echo "$(YELLOW)Testing $$service Redis connection...$(RESET)"; \
		if docker-compose exec $$service-service test -f tests/Infrastructure/RedisConnectionTest.php; then \
			docker-compose exec $$service-service ./vendor/bin/phpunit tests/Infrastructure/RedisConnectionTest.php --colors=always; \
		else \
			echo "$(BLUE)ℹ️  No Redis tests found for $$service service$(RESET)"; \
		fi; \
	done

.PHONY: start-workers
start-workers: ## Start RabbitMQ queue workers for all services
	@echo "$(CYAN)🚀 Starting RabbitMQ queue workers...$(RESET)"
	@./start-queue-workers.sh

.PHONY: stop-workers
stop-workers: ## Stop RabbitMQ queue workers for all services
	@echo "$(CYAN)🛑 Stopping RabbitMQ queue workers...$(RESET)"
	@./stop-queue-workers.sh

.PHONY: queue-status
queue-status: ## Show queue worker status and RabbitMQ queues
	@echo "$(CYAN)📊 Queue Worker Status$(RESET)"
	@echo "$(YELLOW)Order Service Workers:$(RESET)"
	@docker-compose exec order-service ps aux | grep "artisan queue:work" | grep -v grep || echo "$(RED)No workers running$(RESET)"
	@echo "$(YELLOW)Payment Service Workers:$(RESET)"
	@docker-compose exec payment-service ps aux | grep "artisan queue:work" | grep -v grep || echo "$(RED)No workers running$(RESET)"
	@echo "$(YELLOW)Notification Service Workers:$(RESET)"
	@docker-compose exec notification-service ps aux | grep "artisan queue:work" | grep -v grep || echo "$(RED)No workers running$(RESET)"
	@echo ""
	@echo "$(BLUE)💡 Monitor queues at: http://127.0.0.1:15672 (admin/password)$(RESET)"

# =============================================================================
# 🌱 SEEDING ORCHESTRATOR
# =============================================================================

.PHONY: seed-build seed-run seed-dry-run seed-clean seed-status

seed-build: ## Build the seeding orchestrator using Docker (no local Go required)
	@echo "$(CYAN)🔨 Building seeding orchestrator using Docker...$(RESET)"
	@./seeding-orchestrator/build-orchestrator.sh
	@echo "$(GREEN)✅ Seeding orchestrator built successfully$(RESET)"

seed-run: ## Run the seeding orchestrator with dependency management
	@echo "$(CYAN)🌱 Starting seeding orchestration...$(RESET)"
	@if [ ! -f "seeding-orchestrator/seed-orchestrator" ]; then \
		echo "$(YELLOW)Binary not found, building first...$(RESET)"; \
		$(MAKE) seed-build; \
	fi
	@cd seeding-orchestrator && ./seed-orchestrator seed.yml

seed-dry-run: ## Run seeding orchestrator in dry-run mode (show what would be executed)
	@echo "$(CYAN)🔍 Running seeding orchestrator in dry-run mode...$(RESET)"
	@if [ ! -f "seeding-orchestrator/seed-orchestrator" ]; then \
		echo "$(YELLOW)Binary not found, building first...$(RESET)"; \
		$(MAKE) seed-build; \
	fi
	@cd seeding-orchestrator && ./seed-orchestrator seed.yml --dry-run

seed-clean: ## Run seeding orchestrator with cleanup
	@echo "$(CYAN)🧹 Running seeding orchestrator with cleanup...$(RESET)"
	@if [ ! -f "seeding-orchestrator/seed-orchestrator" ]; then \
		echo "$(YELLOW)Binary not found, building first...$(RESET)"; \
		$(MAKE) seed-build; \
	fi
	@cd seeding-orchestrator && ./seed-orchestrator seed.yml --cleanup

seed-status: ## Check seeding orchestrator status and requirements
	@if [ ! -f "seeding-orchestrator/seed-orchestrator" ]; then \
		echo "$(YELLOW)Binary not found, building first...$(RESET)"; \
		$(MAKE) seed-build; \
	fi
	@cd seeding-orchestrator && ./seed-orchestrator --status

# Make sure help is shown when make is run without arguments
.DEFAULT: help