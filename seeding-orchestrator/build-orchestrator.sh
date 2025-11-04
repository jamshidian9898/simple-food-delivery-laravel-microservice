#!/bin/bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BINARY_NAME="seed-orchestrator"

# Change to the script directory (seeding-orchestrator/)
cd "$SCRIPT_DIR"

# Try Docker build using Dockerfile
if command -v docker >/dev/null 2>&1 && docker ps >/dev/null 2>&1; then
    echo "Building with Docker using Dockerfile..."
    
    # Detect host OS and architecture
    GOOS=$(uname -s | tr '[:upper:]' '[:lower:]')
    GOARCH=$(uname -m | sed 's/x86_64/amd64/')
    
    # Build using the Dockerfile and extract binary
    if docker build --build-arg TARGETOS="$GOOS" --build-arg TARGETARCH="$GOARCH" -t seed-orchestrator-builder . >/dev/null 2>&1; then
        # Create temporary container and copy binary
        CONTAINER_ID=$(docker create seed-orchestrator-builder)
        docker cp "$CONTAINER_ID:/app/$BINARY_NAME" "./$BINARY_NAME"
        docker rm "$CONTAINER_ID" >/dev/null
        chmod +x "$BINARY_NAME"
        echo "✅ Built successfully with Docker"
        exit 0
    else
        echo "⚠️ Docker build failed, trying local Go..."
    fi
fi

# Fallback to local Go
if command -v go >/dev/null 2>&1; then
    echo "Building with local Go..."
    go mod tidy
    if go build -o "$BINARY_NAME" main.go; then
        chmod +x "$BINARY_NAME"
        echo "✅ Built successfully with local Go"
        exit 0
    fi
fi

echo "❌ Both Docker and local Go builds failed"
exit 1
