.PHONY: help install build up down restart logs clean compile deploy test

# Default target
help:
	@echo "SK Elections - Available Commands"
	@echo ""
	@echo "Setup & Installation:"
	@echo "  make install          - Install all dependencies (composer, npm)"
	@echo "  make setup            - Complete first-time setup"
	@echo ""
	@echo "Docker Operations:"
	@echo "  make build            - Build all Docker containers"
	@echo "  make up               - Start all services"
	@echo "  make down             - Stop all services"
	@echo "  make restart          - Restart all services"
	@echo "  make logs             - View logs from all services"
	@echo "  make clean            - Clean up containers, volumes, and build files"
	@echo ""
	@echo "Contract Operations:"
	@echo "  make compile          - Compile all Midnight contracts"
	@echo "  make compile-elections - Compile elections contract only"
	@echo "  make deploy-elections  - Deploy elections contract"
	@echo "  make contract-info     - Show deployed contract information"
	@echo ""
	@echo "Development:"
	@echo "  make web-shell        - Open shell in web container"
	@echo "  make api-shell        - Open shell in elections-api container"
	@echo "  make web-logs         - View web application logs"
	@echo "  make api-logs         - View elections API logs"
	@echo "  make proof-logs       - View proof server logs"
	@echo ""
	@echo "Laravel Operations:"
	@echo "  make migrate          - Run database migrations"
	@echo "  make migrate-fresh    - Fresh migration (WARNING: deletes data)"
	@echo "  make seed             - Run database seeders"
	@echo "  make migrate-seed     - Run migrations and seeders"
	@echo "  make cache-clear      - Clear all Laravel caches"
	@echo "  make artisan CMD=xxx  - Run artisan command"
	@echo ""
	@echo "Testing:"
	@echo "  make test             - Run all tests"
	@echo "  make test-web         - Run Laravel tests"
	@echo "  make test-api         - Run API tests"

# Installation & Setup
install: install-web install-contract

install-web:
	@echo "Installing web dependencies..."
	cd web && composer install
	cd web && npm install

install-contract:
	@echo "Installing contract dependencies..."
	cd contract && npm install

setup: install
	@echo "Setting up database..."
	cd web && php artisan key:generate
	cd web && touch database/database.sqlite
	cd web && php artisan migrate
	@echo "Building frontend assets..."
	cd web && npm run build
	@echo "Setup complete!"

# Docker Operations
build:
	@echo "Building Docker containers..."
	docker-compose build

up:
	@echo "Starting all services..."
	docker-compose up -d
	@echo "Services started. Access:"
	@echo "  Web: http://localhost:8000"
	@echo "  API: http://localhost:3000"
	@echo "  Proof Server: http://localhost:6300"

down:
	@echo "Stopping all services..."
	docker-compose down

restart:
	@echo "Restarting all services..."
	docker-compose restart

logs:
	docker-compose logs -f

clean:
	@echo "Cleaning up..."
	docker-compose down -v
	rm -rf contract/node_modules contract/dist-*
	rm -rf web/vendor web/node_modules
	@echo "Clean complete!"

# Contract Operations
compile:
	@echo "Compiling all contracts..."
	cd contract && npm run compile

compile-elections:
	@echo "Compiling elections contract..."
	cd contract && npm run compile:elections

build-elections:
	@echo "Building elections contract..."
	cd contract && npm run build:elections

deploy-elections:
	@echo "Deploying elections contract..."
	cd contract && echo "y" | npm run deploy:elections

contract-info:
	@if [ -f contract/deployment.json ]; then \
		echo "=== Deployed Contract Information ==="; \
		cat contract/deployment.json | jq '.'; \
	else \
		echo "No deployment found. Run 'make deploy-elections' first."; \
	fi

# Development
web-shell:
	@echo "Opening shell in web container..."
	docker-compose exec web sh

api-shell:
	@echo "Opening shell in elections-api container..."
	docker-compose exec elections-api sh

web-logs:
	docker-compose logs -f web

api-logs:
	docker-compose logs -f elections-api

proof-logs:
	docker-compose logs -f proof-server

queue-logs:
	docker-compose logs -f queue-worker

# Laravel Operations
migrate:
	@echo "Running migrations..."
	docker-compose exec web php artisan migrate

migrate-fresh:
	@echo "WARNING: This will delete all data!"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose exec web php artisan migrate:fresh --seed; \
	fi

seed:
	@echo "Running database seeders..."
	docker-compose exec web php artisan db:seed

migrate-seed:
	@echo "Running migrations and seeders..."
	docker-compose exec web php artisan migrate --seed

cache-clear:
	@echo "Clearing caches..."
	docker-compose exec web php artisan cache:clear
	docker-compose exec web php artisan config:clear
	docker-compose exec web php artisan view:clear
	docker-compose exec web php artisan route:clear

artisan:
	@if [ -z "$(CMD)" ]; then \
		echo "Usage: make artisan CMD='command'"; \
		echo "Example: make artisan CMD='queue:work'"; \
	else \
		docker-compose exec web php artisan $(CMD); \
	fi

# Testing
test: test-web test-api

test-web:
	@echo "Running Laravel tests..."
	docker-compose exec web php artisan test

test-api:
	@echo "Running API tests..."
	docker-compose exec elections-api npm test

# Quick start for development
dev-up: up
	@echo "Starting development mode..."
	@echo "Opening logs..."
	$(MAKE) logs

# Production deployment
prod-deploy: build up migrate
	@echo "Production deployment complete!"
	$(MAKE) contract-info

# Health check
health:
	@echo "=== Service Health Check ==="
	@echo -n "Web Application: "
	@curl -sf http://localhost:8000 > /dev/null && echo "✓ Running" || echo "✗ Down"
	@echo -n "Elections API: "
	@curl -sf http://localhost:3000/health > /dev/null && echo "✓ Running" || echo "✗ Down"
	@echo -n "Proof Server: "
	@curl -sf http://localhost:6300 > /dev/null && echo "✓ Running" || echo "✗ Down"
	@echo ""
	@echo "=== Container Status ==="
	@docker-compose ps

# Database operations
db-backup:
	@echo "Backing up database..."
	@mkdir -p backups
	@cp web/database/database.sqlite backups/database-$(shell date +%Y%m%d-%H%M%S).sqlite
	@echo "Backup created in backups/"

db-restore:
	@if [ -z "$(FILE)" ]; then \
		echo "Usage: make db-restore FILE=backups/database-xxx.sqlite"; \
	else \
		cp $(FILE) web/database/database.sqlite; \
		echo "Database restored from $(FILE)"; \
	fi

# Composer operations
composer-update:
	cd web && composer update
	docker-compose restart web

composer-install:
	cd web && composer install
	docker-compose restart web
