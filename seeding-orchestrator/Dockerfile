# Multi-stage build for Go seeding orchestrator
FROM golang:1.21-alpine AS builder

# Set working directory
WORKDIR /app

# Copy go mod files
COPY go.mod go.sum ./

# Download dependencies
RUN go mod download

# Copy source code
COPY main.go ./

# Build the binary for host architecture
ARG TARGETOS
ARG TARGETARCH
RUN CGO_ENABLED=0 GOOS=${TARGETOS:-linux} GOARCH=${TARGETARCH:-amd64} go build -a -installsuffix cgo -o seed-orchestrator main.go

# Final stage - minimal image
FROM alpine:latest

# Install docker CLI (needed to execute docker commands)
RUN apk --no-cache add docker-cli

# Set working directory
WORKDIR /app

# Copy the binary from builder stage
COPY --from=builder /app/seed-orchestrator .

# Make binary executable
RUN chmod +x seed-orchestrator

# Entry point
ENTRYPOINT ["./seed-orchestrator"]
