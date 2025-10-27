#!/bin/bash

# =============================================================================
# DirectAM Microservices Setup Script
# =============================================================================
# This script helps with first-time setup of all microservices
# Author: Setup CLI Generator
# Version: 1.0.0
# =============================================================================

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Service definitions
SERVICES=("notification" "order" "payment" "product" "user")
INFRASTRUCTURE=("traefik" "redis" "rabbitmq" "mailpit")

# Configuration
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVICES_DIR="$PROJECT_ROOT/Services"
CERT_DIR="$PROJECT_ROOT/.cert"
JWT_SECRET_FILE="$CERT_DIR/jwt-secret"

# =============================================================================
# UTILITY FUNCTIONS
# =============================================================================

print_header() {
    echo -e "${CYAN}"
    echo "╔══════════════════════════════════════════════════════════════════════════════╗"
    echo "║                    DirectAM Microservices Setup                             ║"
    echo "║                         First-Time Setup CLI                                ║"
    echo "╚══════════════════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

print_section() {
    echo -e "\n${BLUE}▶ $1${NC}"
    echo -e "${BLUE}$(printf '%.0s─' {1..50})${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${CYAN}ℹ️  $1${NC}"
}

confirm_action() {
    local message="$1"
    local default="${2:-n}"
    
    if [[ "$default" == "y" ]]; then
        prompt="[Y/n]"
    else
        prompt="[y/N]"
    fi
    
    echo -ne "${YELLOW}$message $prompt: ${NC}"
    read -r response
    
    if [[ -z "$response" ]]; then
        response="$default"
    fi
    
    [[ "$response" =~ ^[Yy]$ ]]
}

check_requirements() {
    print_section "Checking System Requirements"
    
    local missing_tools=()
    
    # Check Docker
    if ! command -v docker &> /dev/null; then
        missing_tools+=("docker")
    else
        print_success "Docker found: $(docker --version | cut -d' ' -f3 | cut -d',' -f1)"
    fi
    
    # Check Docker Compose
    if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
        missing_tools+=("docker-compose")
    else
        if command -v docker-compose &> /dev/null; then
            print_success "Docker Compose found: $(docker-compose --version | cut -d' ' -f3 | cut -d',' -f1)"
        else
            print_success "Docker Compose found: $(docker compose version --short)"
        fi
    fi
    
    # Check Make
    if ! command -v make &> /dev/null; then
        missing_tools+=("make")
    else
        print_success "Make found: $(make --version | head -n1 | cut -d' ' -f3)"
    fi
    
    # Check Git
    if ! command -v git &> /dev/null; then
        missing_tools+=("git")
    else
        print_success "Git found: $(git --version | cut -d' ' -f3)"
    fi
    
    if [[ ${#missing_tools[@]} -gt 0 ]]; then
        print_error "Missing required tools: ${missing_tools[*]}"
        echo -e "${RED}Please install the missing tools and run this script again.${NC}"
        exit 1
    fi
    
    print_success "All system requirements satisfied!"
}

setup_service_environment() {
    local service="$1"
    local service_dir="$SERVICES_DIR/${service}-service"
    
    print_info "Setting up $service service environment..."
    
    if [[ ! -d "$service_dir" ]]; then
        print_error "Service directory not found: $service_dir"
        return 1
    fi
    
    cd "$service_dir"
    
    # Copy .env.example to .env if it doesn't exist
    if [[ ! -f ".env" ]]; then
        if [[ -f ".env.example" ]]; then
            cp ".env.example" ".env"
            print_success "Created .env file for $service service"
        else
            print_warning "No .env.example found for $service service"
        fi
    else
        print_info ".env file already exists for $service service"
    fi
    
    # Set up shared JWT secret for services that need it
    if [[ -f "$JWT_SECRET_FILE" && -f ".env" ]]; then
        local jwt_secret=$(cat "$JWT_SECRET_FILE")
        
        # Update JWT configuration if the service uses JWT
        if grep -q "JWT_SECRET" ".env" 2>/dev/null || [[ "$service" == "user" ]]; then
            # Add or update JWT_SECRET in .env
            if grep -q "^JWT_SECRET=" ".env"; then
                sed -i.bak "s|^JWT_SECRET=.*|JWT_SECRET=$jwt_secret|" ".env"
            else
                echo "JWT_SECRET=$jwt_secret" >> ".env"
            fi
            print_success "Updated JWT secret for $service service"
        fi
    fi
    
    # Update .env with Docker-specific configurations
    if [[ -f ".env" ]]; then
        # Update database configuration for Docker
        case "$service" in
            "notification")
                DB_PORT="3307"
                ;;
            "order")
                DB_PORT="3308"
                ;;
            "payment")
                DB_PORT="3309"
                ;;
            "product")
                DB_PORT="3310"
                ;;
            "user")
                DB_PORT="3311"
                ;;
        esac
        
        # Update .env file with Docker configurations
        sed -i.bak "s/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/" .env
        sed -i.bak "s/# DB_HOST=127.0.0.1/DB_HOST=${service}-db/" .env
        sed -i.bak "s/# DB_PORT=3306/DB_PORT=3306/" .env
        sed -i.bak "s/# DB_DATABASE=laravel/DB_DATABASE=${service}-db/" .env
        sed -i.bak "s/# DB_USERNAME=root/DB_USERNAME=laravel/" .env
        sed -i.bak "s/# DB_PASSWORD=/DB_PASSWORD=secret/" .env
        
        # Update Redis configuration
        sed -i.bak "s/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/" .env
        
        # Update Mail configuration for Mailpit
        sed -i.bak "s/MAIL_MAILER=log/MAIL_MAILER=smtp/" .env
        sed -i.bak "s/MAIL_HOST=127.0.0.1/MAIL_HOST=mailpit/" .env
        sed -i.bak "s/MAIL_PORT=2525/MAIL_PORT=1025/" .env
        
        # Update Queue configuration for Redis
        sed -i.bak "s/QUEUE_CONNECTION=database/QUEUE_CONNECTION=redis/" .env
        
        # Update Cache configuration for Redis
        sed -i.bak "s/CACHE_STORE=database/CACHE_STORE=redis/" .env
        
        # Remove backup files
        rm -f .env.bak
        
        print_success "Updated $service service .env configuration for Docker"
    fi
    
    cd "$PROJECT_ROOT"
}

install_service_dependencies() {
    local service="$1"
    local service_dir="$SERVICES_DIR/${service}-service"
    
    print_info "Installing dependencies for $service service..."
    
    # Check if composer.lock exists (dependencies already installed)
    if [[ -f "$service_dir/vendor/autoload.php" ]]; then
        print_info "Dependencies already installed for $service service"
        return 0
    fi
    
    # Use Docker to install dependencies if containers are running
    if docker-compose ps -q "${service}-service" &> /dev/null; then
        print_info "Installing PHP dependencies via Docker for $service service..."
        docker-compose exec "${service}-service" composer install --no-interaction --prefer-dist --optimize-autoloader
        
        print_info "Installing Node.js dependencies via Docker for $service service..."
        docker-compose exec "${service}-service" npm install
        
        print_success "Dependencies installed for $service service"
    else
        print_warning "Service container not running. Dependencies will be installed when container starts."
    fi
}

generate_jwt_secret() {
    print_section "Generating Shared JWT Secret"
    
    # Create .cert directory if it doesn't exist
    if [[ ! -d "$CERT_DIR" ]]; then
        mkdir -p "$CERT_DIR"
        print_success "Created .cert directory"
    fi
    
    # Generate JWT secret if it doesn't exist
    if [[ ! -f "$JWT_SECRET_FILE" ]]; then
        # Generate a 256-bit (32 bytes) base64 encoded secret
        openssl rand -base64 32 > "$JWT_SECRET_FILE"
        chmod 600 "$JWT_SECRET_FILE"  # Restrict permissions
        print_success "Generated shared JWT secret in $JWT_SECRET_FILE"
    else
        print_info "JWT secret already exists in $JWT_SECRET_FILE"
    fi
    
    # Update root .env file with JWT secret
    local jwt_secret=$(cat "$JWT_SECRET_FILE")
    local root_env_file="$PROJECT_ROOT/.env"
    
    if [[ -f "$root_env_file" ]]; then
        # Add or update JWT_SECRET in root .env
        if grep -q "^JWT_SECRET=" "$root_env_file"; then
            sed -i.bak "s|^JWT_SECRET=.*|JWT_SECRET=$jwt_secret|" "$root_env_file"
            print_success "Updated JWT secret in root .env file"
        else
            echo "JWT_SECRET=$jwt_secret" >> "$root_env_file"
            print_success "Added JWT secret to root .env file"
        fi
        # Remove backup file
        rm -f "$root_env_file.bak"
    else
        # Create root .env file with JWT secret
        echo "JWT_SECRET=$jwt_secret" > "$root_env_file"
        print_success "Created root .env file with JWT secret"
    fi
    
    # Display the secret for reference (first 16 characters)
    local secret_preview=$(head -c 16 "$JWT_SECRET_FILE")
    print_info "JWT Secret preview: ${secret_preview}..."
}

generate_app_keys() {
    print_section "Generating Application Keys"
    
    for service in "${SERVICES[@]}"; do
        print_info "Generating app key for $service service..."
        
        # Check if service is running
        if docker-compose ps -q "${service}-service" &> /dev/null; then
            docker-compose exec "${service}-service" php artisan key:generate --force
            print_success "App key generated for $service service"
        else
            print_warning "Service $service not running. Key will be generated when service starts."
        fi
    done
}

run_migrations() {
    print_section "Running Database Migrations"
    
    for service in "${SERVICES[@]}"; do
        print_info "Running migrations for $service service..."
        
        # Check if service and database are running
        if docker-compose ps -q "${service}-service" &> /dev/null && docker-compose ps -q "${service}-db" &> /dev/null; then
            # Wait a bit for database to be ready
            sleep 2
            docker-compose exec "${service}-service" php artisan migrate --force
            print_success "Migrations completed for $service service"
        else
            print_warning "Service or database not running for $service. Migrations will need to be run manually."
        fi
    done
}

run_tests() {
    print_section "Running Service Tests"
    
    if confirm_action "Run tests for all services?" "y"; then
        print_info "Running comprehensive test suite..."
        
        # Check if Make is available
        if command -v make &> /dev/null; then
            # Use make test-all command
            if make test-all; then
                print_success "All service tests completed successfully!"
            else
                print_warning "Some tests may have failed. Check the output above for details."
            fi
        else
            print_warning "Make not available. Running tests manually..."
            
            # Fallback: run tests manually for each service
            for service in "${SERVICES[@]}"; do
                if docker-compose ps -q "${service}-service" &> /dev/null; then
                    print_info "Running tests for $service service..."
                    docker-compose exec "${service}-service" php artisan test || print_warning "Tests failed for $service service"
                else
                    print_warning "Service $service not running. Skipping tests."
                fi
            done
        fi
    else
        print_info "Tests skipped. You can run them later with: make test-all"
    fi
}

setup_docker_environment() {
    print_section "Setting Up Docker Environment"
    
    # Check if Docker is running
    if ! docker info &> /dev/null; then
        print_error "Docker is not running. Please start Docker and try again."
        exit 1
    fi
    
    print_success "Docker is running"
    
    # Build images if they don't exist
    print_info "Building Docker images..."
    docker-compose build --no-cache
    print_success "Docker images built successfully"
}

start_services() {
    print_section "Starting Services"
    
    if confirm_action "Start all services now?" "y"; then
        print_info "Starting infrastructure services..."
        docker-compose up -d traefik redis rabbitmq mailpit
        
        print_info "Starting database services..."
        docker-compose up -d notification-db order-db payment-db product-db user-db
        
        # Wait for databases to be ready
        print_info "Waiting for databases to be ready..."
        sleep 10
        
        print_info "Starting application services..."
        docker-compose up -d
        
        print_success "All services started successfully!"
        
        # Show service URLs
        show_service_urls
    else
        print_info "Services not started. You can start them later with: make up"
    fi
}

show_service_urls() {
    print_section "Service URLs"
    
    echo -e "${GREEN}🎯 API Services:${NC}"
    echo "  Notification: http://notification.api.localhost"
    echo "  Order:        http://order.api.localhost"
    echo "  Payment:      http://payment.api.localhost"
    echo "  Product:      http://product.api.localhost"
    echo "  User:         http://user.api.localhost"
    echo ""
    echo -e "${GREEN}🔧 Infrastructure:${NC}"
    echo "  Traefik:      http://localhost:8080"
    echo "  RabbitMQ:     http://rabbitmq.api.localhost (admin/password)"
    echo "  Mailpit:      http://mailpit.api.localhost"
    echo ""
    echo -e "${GREEN}🗄️  Databases:${NC}"
    echo "  Notification: localhost:3307"
    echo "  Order:        localhost:3308"
    echo "  Payment:      localhost:3309"
    echo "  Product:      localhost:3310"
    echo "  User:         localhost:3311"
    echo "  Redis:        localhost:6379"
}

show_next_steps() {
    print_section "Next Steps"
    
    echo -e "${CYAN}📋 Available Commands:${NC}"
    echo "  make help          - Show all available commands"
    echo "  make up            - Start all services"
    echo "  make down          - Stop all services"
    echo "  make status        - Check service status"
    echo "  make logs          - View all service logs"
    echo "  make urls          - Show service URLs"
    echo "  make test-all      - Run tests for all services"
    echo ""
    echo -e "${CYAN}📖 Service Management:${NC}"
    echo "  make service-up-notification    - Start notification service"
    echo "  make logs-notification          - View notification logs"
    echo "  make shell SERVICE=notification - Open shell in service"
    echo ""
    echo -e "${CYAN}🔧 Development:${NC}"
    echo "  make artisan SERVICE=notification CMD=\"migrate\"  - Run artisan commands"
    echo "  make composer SERVICE=notification CMD=\"install\" - Run composer commands"
    echo ""
    echo -e "${CYAN}🔐 Security:${NC}"
    echo "  JWT Secret: $JWT_SECRET_FILE"
    echo "  All services share the same JWT secret for authentication"
    echo ""
    echo -e "${GREEN}✨ Setup completed successfully!${NC}"
}

# =============================================================================
# MAIN SETUP FLOW
# =============================================================================

main() {
    print_header
    
    echo -e "${WHITE}This script will help you set up the DirectAM microservices project for the first time.${NC}"
    echo -e "${WHITE}It will configure environment files, install dependencies, and start services.${NC}\n"
    
    if ! confirm_action "Continue with setup?" "y"; then
        echo -e "${YELLOW}Setup cancelled.${NC}"
        exit 0
    fi
    
    # Step 1: Check system requirements
    check_requirements
    
    # Step 2: Setup Docker environment
    setup_docker_environment
    
    # Step 3: Setup service environments
    print_section "Setting Up Service Environments"
    for service in "${SERVICES[@]}"; do
        setup_service_environment "$service"
    done
    
    # Step 4: Start services
    start_services
    
    # Step 5: Generate shared JWT secret
    generate_jwt_secret
    
    # Step 6: Generate app keys and run migrations if services are running
    if docker-compose ps -q | grep -q .; then
        generate_app_keys
        run_migrations
        
        # Step 7: Install dependencies
        print_section "Installing Service Dependencies"
        for service in "${SERVICES[@]}"; do
            install_service_dependencies "$service"
        done
        
        # Step 8: Run tests (unless --no-tests flag is set)
        if [[ "$NO_TESTS" != true ]]; then
            run_tests
        else
            print_info "Tests skipped due to --no-tests flag. You can run them later with: make test-all"
        fi
    fi
    
    # Step 9: Show next steps
    show_next_steps
}

# =============================================================================
# COMMAND LINE OPTIONS
# =============================================================================

show_help() {
    echo -e "${CYAN}DirectAM Microservices Setup Script${NC}"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -h, --help              Show this help message"
    echo "  --env-only             Setup environment files only"
    echo "  --docker-only          Setup Docker environment only"
    echo "  --no-start             Don't start services after setup"
    echo "  --no-tests             Skip running tests after setup"
    echo "  --service SERVICE      Setup specific service only"
    echo ""
    echo "Examples:"
    echo "  $0                     # Full setup"
    echo "  $0 --env-only          # Setup .env files only"
    echo "  $0 --service notification  # Setup notification service only"
}

# Parse command line arguments
ENV_ONLY=false
DOCKER_ONLY=false
NO_START=false
NO_TESTS=false
SPECIFIC_SERVICE=""

while [[ $# -gt 0 ]]; do
    case $1 in
        -h|--help)
            show_help
            exit 0
            ;;
        --env-only)
            ENV_ONLY=true
            shift
            ;;
        --docker-only)
            DOCKER_ONLY=true
            shift
            ;;
        --no-start)
            NO_START=true
            shift
            ;;
        --no-tests)
            NO_TESTS=true
            shift
            ;;
        --service)
            SPECIFIC_SERVICE="$2"
            shift 2
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            show_help
            exit 1
            ;;
    esac
done

# Execute based on options
if [[ "$ENV_ONLY" == true ]]; then
    print_header
    print_section "Setting Up Environment Files Only"
    
    # Generate JWT secret first
    generate_jwt_secret
    
    if [[ -n "$SPECIFIC_SERVICE" ]]; then
        setup_service_environment "$SPECIFIC_SERVICE"
    else
        for service in "${SERVICES[@]}"; do
            setup_service_environment "$service"
        done
    fi
    print_success "Environment setup completed!"
elif [[ "$DOCKER_ONLY" == true ]]; then
    print_header
    check_requirements
    setup_docker_environment
    print_success "Docker setup completed!"
elif [[ -n "$SPECIFIC_SERVICE" ]]; then
    print_header
    print_section "Setting Up $SPECIFIC_SERVICE Service"
    
    # Generate JWT secret first
    generate_jwt_secret
    
    setup_service_environment "$SPECIFIC_SERVICE"
    if [[ "$NO_START" != true ]]; then
        if confirm_action "Start $SPECIFIC_SERVICE service?" "y"; then
            docker-compose up -d "${SPECIFIC_SERVICE}-db" "${SPECIFIC_SERVICE}-service" "${SPECIFIC_SERVICE}-nginx"
            print_success "$SPECIFIC_SERVICE service started!"
        fi
    fi
else
    # Run full setup
    main
fi
