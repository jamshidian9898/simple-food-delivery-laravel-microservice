#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Starting RabbitMQ Queue Workers for All Services${NC}"
echo ""

# Check if RabbitMQ is running
echo -e "${YELLOW}Checking RabbitMQ status...${NC}"
if ! docker-compose ps rabbitmq | grep -q "Up"; then
    echo -e "${YELLOW}Starting RabbitMQ...${NC}"
    docker-compose up -d rabbitmq
    echo -e "${YELLOW}Waiting for RabbitMQ to be ready...${NC}"
    sleep 10
fi

echo -e "${GREEN}✅ RabbitMQ is running${NC}"
echo ""

# Start workers for each service
echo -e "${BLUE}Starting queue workers...${NC}"

# Order Service Worker
echo -e "${YELLOW}Starting Order Service worker...${NC}"
docker-compose exec -d order-service bash -c "nohup php artisan queue:work rabbitmq --queue=order_queue --sleep=3 --tries=3 --max-time=3600 > /var/log/queue-worker.log 2>&1 &"

# Payment Service Worker  
echo -e "${YELLOW}Starting Payment Service worker...${NC}"
docker-compose exec -d payment-service bash -c "nohup php artisan queue:work rabbitmq --queue=payment_queue --sleep=3 --tries=3 --max-time=3600 > /var/log/queue-worker.log 2>&1 &"

# Notification Service Worker
echo -e "${YELLOW}Starting Notification Service worker...${NC}"
docker-compose exec -d notification-service bash -c "nohup php artisan queue:work rabbitmq --queue=notification_queue --sleep=3 --tries=3 --max-time=3600 > /var/log/queue-worker.log 2>&1 &"

echo ""
echo -e "${GREEN}🎉 All queue workers started successfully!${NC}"
echo ""
echo -e "${BLUE}Queue Configuration:${NC}"
echo -e "  - ${YELLOW}Order Queue:${NC} order_queue"
echo -e "  - ${YELLOW}Payment Queue:${NC} payment_queue" 
echo -e "  - ${YELLOW}Notification Queue:${NC} notification_queue"
echo ""
echo -e "${BLUE}Monitor queues at:${NC} http://127.0.0.1:15672 (admin/password)"
echo ""
echo -e "${YELLOW}To stop workers:${NC} ./stop-queue-workers.sh"
