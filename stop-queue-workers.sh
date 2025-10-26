#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🛑 Stopping RabbitMQ Queue Workers${NC}"
echo ""

# Stop all artisan queue:work processes
echo -e "${YELLOW}Stopping queue workers...${NC}"

# Kill queue workers in each service
docker-compose exec order-service bash -c "ps aux | grep 'artisan queue:work' | grep -v grep | awk '{print \$2}' | xargs -r kill" || true
docker-compose exec payment-service bash -c "ps aux | grep 'artisan queue:work' | grep -v grep | awk '{print \$2}' | xargs -r kill" || true  
docker-compose exec notification-service bash -c "ps aux | grep 'artisan queue:work' | grep -v grep | awk '{print \$2}' | xargs -r kill" || true

echo -e "${GREEN}✅ All queue workers stopped${NC}"
echo ""
echo -e "${YELLOW}Note:${NC} RabbitMQ server is still running"
echo -e "${YELLOW}To stop RabbitMQ:${NC} docker-compose stop rabbitmq"
