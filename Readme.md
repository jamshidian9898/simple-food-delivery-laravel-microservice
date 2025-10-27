# food delivery - Microservices Order Processing Platform

A modern, scalable microservices architecture for order processing and management built with Laravel, Node.js, and Docker.

## 🚀 Project Overview

food delivery is a multi-stage order processing system designed for high-scale e-commerce operations. The platform handles the complete order lifecycle from placement to delivery with real-time notifications and comprehensive logging.

### Key Features

- **Microservices Architecture**: 5 independent Laravel services + 1 Node.js logging service
- **Event-Driven Communication**: RabbitMQ message queues for service communication
- **JWT Authentication**: Secure user authentication with multiple user types
- **Real-time Notifications**: Multi-channel notification system (email, SMS, push)
- **Comprehensive Logging**: Centralized logging with PostgreSQL and Metabase analytics
- **API Gateway**: Traefik reverse proxy with load balancing
- **Containerized Deployment**: Full Docker Compose setup with development tools

## 🏗️ Architecture

### Services Overview

| Service | Technology | Database | Port | Purpose |
|---------|------------|----------|------|---------|
| **User Service** | Laravel 12 | MySQL | 3311 | Authentication, user management |
| **Product Service** | Laravel 12 | MySQL | 3310 | Product catalog, inventory |
| **Order Service** | Laravel 12 | MySQL | 3308 | Order management, workflow |
| **Payment Service** | Laravel 12 | MySQL | 3309 | Payment processing, validation |
| **Notification Service** | Laravel 12 | MySQL | 3307 | Multi-channel notifications |
| **Log Service** | Node.js 20 | PostgreSQL | 5432 | Centralized logging, analytics |

### Infrastructure Components

- **Traefik**: API Gateway and reverse proxy
- **RabbitMQ**: Message broker for inter-service communication
- **Redis**: Caching and session storage
- **Mailpit**: Email testing and development
- **Metabase**: Analytics and business intelligence

## 🔄 Order Processing Workflow

```
User Places Order → Order Service (pending)
                 ↓
              Payment Service (validate)
                 ↓
              Product Service (check inventory)
                 ↓
              Order Service (confirmed → preparing → delivered)
                 ↓
              Notification Service (alerts at each stage)
                 ↓
              Log Service (audit trail)
```

## 🛠️ Quick Start

### Prerequisites

- Docker & Docker Compose
- Make (optional, for convenience commands)

### 1. Clone and Setup

```bash
git clone <repository-url>
cd food delivery-test-project
./setup.sh
```

### 2. Start Services

```bash
# Start all services
make up

# Or use Docker Compose directly
docker-compose up -d
```

### 3. Initialize Services

```bash
# Start queue workers
make start-workers

# Check service status
make status
```

## 📋 Available Commands

### General Operations
```bash
make up              # Start all services
make down            # Stop all services
make restart         # Restart all services
make status          # Check service status
make logs            # View all logs
```

### Service Management
```bash
make service-up-notification    # Start specific service
make logs-order                # View service logs
make shell SERVICE=user        # Access service shell
```

### Development
```bash
make artisan SERVICE=order CMD="migrate"     # Run artisan commands
make composer SERVICE=user CMD="install"    # Run composer commands
make test SERVICE=notification               # Run service tests
```

### Queue Management
```bash
make start-workers    # Start RabbitMQ workers
make stop-workers     # Stop RabbitMQ workers
make queue-status     # Check worker status
```

## 🌐 Service URLs

### API Endpoints
- **User Service**: http://user.api.localhost
- **Product Service**: http://product.api.localhost
- **Order Service**: http://order.api.localhost
- **Payment Service**: http://payment.api.localhost
- **Notification Service**: http://notification.api.localhost

### Management Interfaces
- **Traefik Dashboard**: http://localhost:8080
- **RabbitMQ Management**: http://rabbitmq.api.localhost (admin/password)
- **Mailpit**: http://mailpit.api.localhost
- **Metabase**: http://metabase.api.localhost

### Database Connections
- **User DB**: localhost:3311
- **Product DB**: localhost:3310
- **Order DB**: localhost:3308
- **Payment DB**: localhost:3309
- **Notification DB**: localhost:3307
- **Log DB**: localhost:5432
- **Redis**: localhost:6379

## 🔐 Authentication

The platform supports JWT-based authentication with three user types:

- **Customer**: End users placing orders
- **Restaurant**: Business users managing products
- **Courier**: Delivery personnel

### API Authentication Endpoints

```bash
POST /api/auth/register    # User registration
POST /api/auth/login       # User login
GET  /api/auth/me         # Get current user (protected)
POST /api/auth/logout     # Logout (protected)
POST /api/auth/refresh    # Refresh token (protected)
```

## 📊 Monitoring & Logging

### Queue Monitoring
- RabbitMQ Management UI: http://rabbitmq.api.localhost
- Queue workers status: `make queue-status`

### Application Logs
- Service logs: `make logs-[service-name]`
- Real-time monitoring: `make monitor`

### Analytics
- Metabase dashboard for log analytics
- PostgreSQL database for centralized logging

## 🧪 Testing

### Run Tests
```bash
make test SERVICE=notification    # Test specific service
make test-all                    # Test all services
make test-infra                  # Test infrastructure components
```

### Infrastructure Tests
Each service includes RabbitMQ connectivity and messaging tests to ensure proper queue integration.

## 🔧 Development Status

### ✅ Completed
- [x] Docker infrastructure setup
- [x] Traefik API gateway configuration
- [x] RabbitMQ message queuing
- [x] User service with JWT authentication
- [x] Centralized logging service
- [x] Infrastructure testing suite

### 🚧 In Progress
- [ ] Product service implementation
- [ ] Order service workflow
- [ ] Payment service integration
- [ ] Notification service channels

### 📋 Planned Features
- [ ] API documentation with Swagger
- [ ] CI/CD pipeline
- [ ] Production deployment scripts
- [ ] Performance monitoring
- [ ] Security hardening

## 🛡️ Security Features

- JWT token-based authentication
- Service-to-service communication via private network
- Environment-based configuration
- Database isolation per service
- Request ID tracking for audit trails

## 📚 Documentation

- **Setup Guide**: `./setup.sh` - Automated environment setup
- **Makefile**: Comprehensive command reference
- **Queue Management**: `./start-queue-workers.sh` and `./stop-queue-workers.sh`
- **Infrastructure Tests**: Each service includes test suites

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests: `make test-all`
5. Submit a pull request

## 📄 License

This project is proprietary software for food delivery platform.

---

**Quick Commands Reference:**
```bash
make up && make start-workers    # Full startup
make urls                       # Show all service URLs  
make monitor                    # Real-time monitoring
make clean                      # Cleanup resources
```