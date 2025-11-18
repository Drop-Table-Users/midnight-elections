# SK Elections - Midnight Blockchain Voting System

A secure, privacy-preserving voting system built on the Midnight blockchain with zero-knowledge proofs.

## Architecture

The system consists of multiple components:

- **Midnight Contracts** (Compact language) - Smart contracts for election management
- **Elections API** (Node.js/Express) - Backend API for contract interactions
- **Web Application** (Laravel/PHP) - Frontend web interface
- **Proof Server** (Docker) - Zero-knowledge proof generation service
- **Queue Worker** - Background job processor for async operations

## Prerequisites

- **Docker** & **Docker Compose** (v2.0+)
- **Make** (GNU Make 4.0+)
- **Task** (optional - Go Task runner)
- **Node.js** 20+ (for local development)
- **PHP** 8.2+ (for local development)
- **Composer** (for local development)

## Quick Start with Docker

### 1. Clone and Build

```bash
# Build all containers
make build

# Or using docker-compose directly
docker-compose build
```

### 2. Start Services

```bash
# Start all services
make up

# Services will be available at:
# - Web Application: http://localhost:8000
# - Elections API: http://localhost:3000
# - Proof Server: http://localhost:6300
```

### 3. Check Service Health

```bash
make health
```

### 4. View Logs

```bash
# All services
make logs

# Individual services
make web-logs      # Laravel web application
make api-logs      # Elections API
make proof-logs    # Proof server
make queue-logs    # Queue worker
```

## Docker Composition Explained

### Services

#### 1. **proof-server** (Port 6300)
```yaml
image: midnightnetwork/proof-server:latest
command: ["midnight-proof-server", "--network", "testnet"]
```
- Official Midnight proof server for ZK proof generation
- Required for all contract operations
- Connects to Midnight testnet

#### 2. **elections-api** (Port 3000)
```yaml
build: Dockerfile.elections-api
depends_on: [proof-server]
```
- Node.js/Express API built from source
- Manages wallet operations and contract interactions
- Persists data in `./contract/midnight-level-db`
- Compiled contracts from `./contract/contracts`

**Environment Variables:**
- `PROOF_SERVER=http://proof-server:6300` - Internal Docker network URL
- `MIDNIGHT_INDEXER` - Testnet indexer (GraphQL)
- `MIDNIGHT_INDEXER_WS` - Testnet indexer WebSocket
- `MIDNIGHT_NODE` - Testnet RPC endpoint

#### 3. **web** (Port 8000)
```yaml
depends_on: [elections-api]
volumes:
  - ./web:/app
  - ./web/laravel-midnight:/app/laravel-midnight:ro
```
- Laravel 12 web application
- SQLite database at `./web/database/database.sqlite`
- Auto-migration and seeding on startup
- Connects to elections-api via `http://elections-api:3000`

#### 4. **queue-worker**
```yaml
command: ["php", "artisan", "queue:work", "--tries=3"]
```
- Background job processor
- Processes async blockchain transactions
- Shares database with web application

### Networking

All services run on the `midnight-network` bridge network, allowing internal communication using service names (e.g., `http://proof-server:6300`).

### Volume Mounts

```yaml
# Persistent data
./contract/midnight-level-db    # Wallet state
./web/database/database.sqlite  # Application database
./contract/deployment.json      # Deployed contract address

# Development code (hot-reload)
./contract:/app                 # Contract source
./web:/app                      # Laravel source
```

## Makefile Commands

The `Makefile` provides high-level commands for the entire system.

### Setup & Installation

```bash
make help              # Show all available commands
make install           # Install all dependencies (web + contract)
make setup             # Complete first-time setup
```

### Docker Operations

```bash
make build             # Build all Docker containers
make up                # Start all services
make down              # Stop all services
make restart           # Restart all services
make logs              # View logs from all services
make clean             # Clean up containers and volumes
```

### Contract Operations

```bash
make compile                # Compile all contracts
make compile-elections      # Compile elections contract only
make deploy-elections       # Deploy elections contract to testnet
make contract-info          # Show deployed contract address and info
```

### Development

```bash
make web-shell         # Open shell in web container
make api-shell         # Open shell in elections-api container
make web-logs          # View Laravel logs
make api-logs          # View API logs
```

### Laravel Operations

```bash
make migrate           # Run database migrations
make migrate-fresh     # Fresh migration (deletes all data)
make seed              # Run database seeders
make migrate-seed      # Run migrations and seeders
make cache-clear       # Clear all Laravel caches
make artisan CMD=xxx   # Run custom artisan command
```

**Example:**
```bash
make artisan CMD='queue:work'
make artisan CMD='tinker'
```

### Database Operations

```bash
make db-backup                           # Backup SQLite database
make db-restore FILE=backups/xxx.sqlite  # Restore from backup
```

### Health & Testing

```bash
make health            # Check all services health
make test              # Run all tests (web + api)
make test-web          # Run Laravel tests only
make test-api          # Run API tests only
```

## Taskfile Commands

The `Taskfile.yml` provides low-level contract operations (runs inside the `contract/` directory).

**Installation:**
```bash
# macOS/Linux
sh -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b ~/.local/bin

# Windows
choco install go-task

# Or use npm
npm install -g @go-task/cli
```

### Usage

```bash
cd contract/

task --list            # Show all available tasks
task install           # Install dependencies
task compile:elections # Compile elections contract
task build:elections   # Build TypeScript
task deploy:elections  # Deploy to testnet
task info              # Show deployment info
task api:start         # Start API server locally
task clean             # Clean build artifacts
```

### Key Tasks

```bash
# Development workflow
task dev                    # Full dev setup (install + compile + build)
task watch:elections        # Auto-recompile on changes
task watch:api             # Auto-rebuild API on changes

# Contract operations
task compile:elections      # Compile elections.compact
task build:elections        # Build TypeScript
task deploy:elections       # Deploy to testnet
task interact              # Interact with deployed contract

# API operations
task api:start             # Start API server
task api:dev               # Start with auto-reload

# Proof server (local)
task proof:start           # Start proof server container
task proof:stop            # Stop proof server

# Information
task info                  # Show deployment.json
task contract:address      # Show contract address only

# Utilities
task check:all             # Check all dependencies
task check:compact         # Verify compact installed
task clean:all             # Clean everything including node_modules
```

## URLs and Endpoints

### Production Deployment

| Service | URL | Description |
|---------|-----|-------------|
| **Web Application** | `http://localhost:8000` | Laravel frontend |
| **Elections API** | `http://localhost:3000` | Contract API |
| **Proof Server** | `http://localhost:6300` | ZK proof generation |

### API Endpoints

#### Elections API (`http://localhost:3000`)

```bash
GET  /health                    # Health check
GET  /wallet/info               # Wallet address and balance
POST /contract/call             # Execute contract call
```

**Contract Call Example:**
```bash
# Open election
curl -X POST http://localhost:3000/contract/call \
  -H "Content-Type: application/json" \
  -d '{"action": "open"}'

# Register candidate
curl -X POST http://localhost:3000/contract/call \
  -H "Content-Type: application/json" \
  -d '{
    "action": "register",
    "candidate_id": "candidate_001"
  }'

# Vote (requires credential data)
curl -X POST http://localhost:3000/contract/call \
  -H "Content-Type: application/json" \
  -d '{
    "action": "vote",
    "candidate_id": "candidate_001",
    "ballot_data": {...}
  }'
```

### Midnight Testnet Endpoints

```bash
# RPC Node
https://rpc.testnet-02.midnight.network

# Indexer (GraphQL)
https://indexer.testnet-02.midnight.network/api/v1/graphql

# Indexer WebSocket
wss://indexer.testnet-02.midnight.network/api/v1/graphql/ws

# Explorer
https://nightly.mexplorer.io
```

## Development Workflow

### Option 1: Docker (Recommended)

```bash
# Start everything
make up

# View logs in real-time
make logs

# Make changes to code (hot-reload enabled)
# - Edit files in ./web/* or ./contract/*
# - Changes are automatically reflected

# Run commands in containers
make web-shell      # Access Laravel container
make api-shell      # Access API container

# Stop when done
make down
```

### Option 2: Local Development

```bash
# Terminal 1: Install dependencies
make install
cd contract && task install

# Terminal 2: Start proof server
docker run -p 6300:6300 midnightnetwork/proof-server -- 'midnight-proof-server --network testnet'

# Terminal 3: Compile and start API
cd contract
task compile:elections
task build:elections-api
task api:dev

# Terminal 4: Start Laravel
cd web
php artisan serve --port=8000
php artisan queue:work

# Terminal 5: Frontend assets
cd web
npm run dev
```

## First-Time Setup

### 1. Initial Setup

```bash
# Clone repository
git clone <repository-url>
cd sk.elections/contract

# Build and start
make build
make up

# Verify services are healthy
make health
```

### 2. Compile and Deploy Contract

```bash
# Option A: Using Makefile
make compile-elections
make deploy-elections
make contract-info

# Option B: Using Taskfile
cd contract
task compile:elections
task build:elections
task deploy:elections
task info
```

### 3. Access Application

```bash
# Web interface
open http://localhost:8000

# API health check
curl http://localhost:3000/health

# Wallet info
curl http://localhost:3000/wallet/info
```

### 4. View Deployment Info

```bash
# Show contract address and transaction hash
make contract-info

# Or view directly
cat contract/deployment.json
```

## Configuration

### Environment Variables

**Elections API** (`docker-compose.yml`):
```yaml
PROOF_SERVER=http://proof-server:6300
MIDNIGHT_INDEXER=https://indexer.testnet-02.midnight.network/api/v1/graphql
MIDNIGHT_INDEXER_WS=wss://indexer.testnet-02.midnight.network/api/v1/graphql/ws
MIDNIGHT_NODE=https://rpc.testnet-02.midnight.network
```

**Web Application**:
```yaml
APP_URL=https://midnight.dev.v2.sk
ELECTIONS_API_URL=http://elections-api:3000
DB_CONNECTION=sqlite
DB_DATABASE=/app/database/database.sqlite
```

### Wallet Configuration

The API uses a managed wallet with seed configured in `elections-api/config.ts`:

```typescript
export const WALLET_SEED = "f468965bfa3aa8056e7232a6de1067d32b89f5d451d4fde61666a66cfaf4ce2f";
```

**  WARNING:** This is a development seed. **NEVER use this in production!**

## Troubleshooting

### Services won't start

```bash
# Check service status
make health
docker-compose ps

# View logs
make logs

# Rebuild containers
make down
make clean
make build
make up
```

### Proof server issues

```bash
# Check proof server logs
make proof-logs

# Restart proof server
docker-compose restart proof-server

# Test proof server directly
curl http://localhost:6300
```

### Contract deployment failed

```bash
# Check API logs
make api-logs

# Verify proof server is running
curl http://localhost:6300

# Check wallet has funds
curl http://localhost:3000/wallet/info

# Recompile and redeploy
cd contract
task clean
task compile:elections
task build:elections
task deploy:elections
```

### Database issues

```bash
# Clear migrations and start fresh
make migrate-fresh

# Backup database first
make db-backup

# Clear Laravel caches
make cache-clear
```

### Port conflicts

If ports 3000, 6300, or 8000 are already in use, edit `docker-compose.yml`:

```yaml
services:
  web:
    ports:
      - "8080:8000"  # Change 8000 to 8080
```

## Project Structure

```
sk.elections/contract/
   contract/                  # Smart contracts
      contracts/
         elections.compact  # Main voting contract
      deployment.json        # Deployed contract address
      midnight-level-db/     # Wallet state database
   elections-api/             # Node.js API
      server.ts              # Express server
      config.ts              # Network configuration
      actions.ts             # Contract actions
   web/                       # Laravel application
      app/                   # Application code
      resources/             # Views and assets
      database/              # SQLite database
      laravel-midnight/      # Midnight Laravel package
   docker-compose.yml         # Docker services
   Dockerfile.elections-api   # API container
   Makefile                   # High-level commands
   Taskfile.yml              # Contract operations
   README.md                  # This file
```

## Security Notes

- **Development seed is public** - Never use in production
- **SQLite is for development** - Use PostgreSQL/MySQL in production
- **No authentication** - Add auth before production deployment
- **CORS is wide open** - Configure CORS properly for production
- **Proof server is public** - May need authentication in production

## Resources

- [Midnight Documentation](https://docs.midnight.network)
- [Compact Language Guide](https://docs.midnight.network/develop/compact/)
- [Laravel Documentation](https://laravel.com/docs)
- [Docker Documentation](https://docs.docker.com)

## License

MIT
