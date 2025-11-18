#!/bin/bash
set -e

echo "=== Elections API Docker Entrypoint ==="

# Check if contract files exist
if [ ! -d "/app/contracts/managed/elections" ]; then
    echo "ERROR: Compiled contract not found!"
    echo "Please run 'make compile-elections' before starting the API"
    exit 1
fi

# Check if TypeScript has been compiled
if [ ! -d "/app/dist-elections-api" ]; then
    echo "Building TypeScript..."
    npm run build:elections-api
fi

# Check if deployment.json exists
if [ ! -f "/app/deployment.json" ]; then
    echo "WARNING: No deployment.json found"
    echo "The contract may not be deployed yet"
    echo "Run 'make deploy-elections' to deploy the contract"
fi

echo "=== Starting Elections API ==="
echo "Port: ${PORT:-3000}"
echo "Proof Server: ${PROOF_SERVER:-http://proof-server:6300}"
echo ""

# Execute the main command
exec "$@"
