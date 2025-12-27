.PHONY: help up down build rebuild restart logs shell composer npm artisan migrate fresh seed install clean crop-logo npm-watch npm-watch-poll npm-watch-bg composer-dump apply-changes setup-queue queue-work-bg

# Default target
.DEFAULT_GOAL := help

# Colors for output
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
RESET  := $(shell tput -Txterm sgr0)

help: ## Show this help message
	@echo "$(GREEN)Employee Management App - Docker Commands$(RESET)"
	@echo ""
	@echo "$(YELLOW)Usage:$(RESET)"
	@echo "  make [target]"
	@echo ""
	@echo "$(YELLOW)Available targets:$(RESET)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-15s$(RESET) %s\n", $$1, $$2}'

up: ## Start the container
	@echo "$(GREEN)Starting container...$(RESET)"
	docker-compose up -d
	@echo "$(GREEN)Container started! Waiting for services...$(RESET)"
	@sleep 5
	@echo "$(GREEN)Check logs with: make logs$(RESET)"
	@echo "$(YELLOW)Note: Run 'make minio-setup' to create MinIO bucket$(RESET)"

down: ## Stop the container
	@echo "$(GREEN)Stopping container...$(RESET)"
	docker-compose down

build: ## Build the Docker image
	@echo "$(GREEN)Building Docker image...$(RESET)"
	docker-compose build

rebuild: ## Rebuild the Docker image from scratch
	@echo "$(GREEN)Rebuilding Docker image (no cache)...$(RESET)"
	docker-compose build --no-cache
	@echo "$(GREEN)Image rebuilt! Starting containers...$(RESET)"
	@make up
	@sleep 10
	@make minio-setup || true
	@echo "$(GREEN)Rebuild complete!$(RESET)"

restart: ## Restart the container
	@echo "$(GREEN)Restarting container...$(RESET)"
	docker-compose restart

logs: ## Show container logs (follow mode)
	docker-compose logs -f

logs-tail: ## Show last 100 lines of logs
	docker-compose logs --tail=100

shell: ## Open shell in the container
	@echo "$(YELLOW)Waiting for container to be ready...$(RESET)"
	@sleep 2
	docker-compose exec app bash

wait: ## Wait for container to be ready
	@echo "$(GREEN)Waiting for container to be ready...$(RESET)"
	@for i in 1 2 3 4 5; do \
		if docker-compose ps | grep -q "Up"; then \
			echo "$(GREEN)Container is ready!$(RESET)"; \
			exit 0; \
		fi; \
		echo "Waiting... ($$i/5)"; \
		sleep 2; \
	done
	@echo "$(YELLOW)Container may still be starting. Check logs with: make logs$(RESET)"

composer: ## Install Composer dependencies
	@echo "$(GREEN)Installing Composer dependencies...$(RESET)"
	@echo "$(YELLOW)Waiting for container to be ready...$(RESET)"
	@sleep 3
	docker-compose exec -T app composer install --no-interaction || \
		(echo "$(YELLOW)Install failed, trying update...$(RESET)" && \
		 docker-compose exec -T app composer update --no-interaction)

composer-update: ## Update Composer dependencies and lock file
	@echo "$(GREEN)Updating Composer dependencies and lock file...$(RESET)"
	@echo "$(YELLOW)Waiting for container to be ready...$(RESET)"
	@sleep 3
	docker-compose exec -T app composer update --no-interaction

composer-fix-lock: ## Fix composer.lock to match composer.json
	@echo "$(GREEN)Fixing composer.lock file...$(RESET)"
	@echo "$(YELLOW)This will update composer.lock to match composer.json$(RESET)"
	@echo "$(YELLOW)Waiting for container to be ready...$(RESET)"
	@sleep 3
	docker-compose exec -T app composer update --lock --no-interaction
	@echo "$(GREEN)composer.lock updated!$(RESET)"

npm: ## Install Node dependencies
	@echo "$(GREEN)Installing Node dependencies...$(RESET)"
	@echo "$(YELLOW)Waiting for container to be ready...$(RESET)"
	@sleep 3
	@docker-compose exec -T app npm install || \
		(echo "$(YELLOW)First attempt failed, retrying...$(RESET)" && \
		 sleep 3 && \
		 docker-compose exec -T app npm install)

npm-build: ## Build frontend assets (production)
	@echo "$(GREEN)Building frontend assets...$(RESET)"
	docker-compose exec -T app npm run production

npm-dev: ## Build frontend assets (development)
	@echo "$(GREEN)Building frontend assets (dev)...$(RESET)"
	docker-compose exec -T app npm run dev

npm-watch: ## Watch and auto-compile CSS/JS on changes (runs in foreground)
	@echo "$(GREEN)Starting watch mode (auto-compile on changes)...$(RESET)"
	@echo "$(YELLOW)Press Ctrl+C to stop$(RESET)"
	@docker-compose exec app npm run watch

npm-watch-poll: ## Watch with polling (better for Docker volumes)
	@echo "$(GREEN)Starting watch mode with polling...$(RESET)"
	@echo "$(YELLOW)Press Ctrl+C to stop$(RESET)"
	@docker-compose exec app npm run watch-poll

npm-watch-bg: ## Watch in background (detached)
	@echo "$(GREEN)Starting watch mode in background...$(RESET)"
	@docker-compose exec -d app npm run watch-poll || \
		(docker-compose exec -d app sh -c "npm run watch-poll &")
	@echo "$(GREEN)Watch mode started in background$(RESET)"
	@echo "$(YELLOW)Check logs with: make logs$(RESET)"


artisan: ## Run artisan command (usage: make artisan CMD="migrate")
	@if [ -z "$(CMD)" ]; then \
		echo "$(YELLOW)Usage: make artisan CMD=\"your-command\"$(RESET)"; \
		echo "$(YELLOW)Example: make artisan CMD=\"migrate\"$(RESET)"; \
	else \
		docker-compose exec -T app php artisan $(CMD); \
	fi

migrate: ## Run database migrations
	@echo "$(GREEN)Running migrations...$(RESET)"
	docker-compose exec -T app php artisan migrate

migrate-fresh: ## Fresh migration (drop all tables and re-run)
	@echo "$(YELLOW)WARNING: This will drop all tables!$(RESET)"
	@read -p "Continue? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose exec -T app php artisan migrate:fresh; \
	fi

migrate-rollback: ## Rollback the last migration
	@echo "$(GREEN)Rolling back last migration...$(RESET)"
	docker-compose exec -T app php artisan migrate:rollback

seed: ## Run database seeders
	@echo "$(GREEN)Running seeders...$(RESET)"
	docker-compose exec -T app php artisan db:seed

fresh-seed: ## Fresh migration with seeders
	@echo "$(YELLOW)WARNING: This will drop all tables and re-run migrations + seeders!$(RESET)"
	@read -p "Continue? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose exec -T app php artisan migrate:fresh --seed; \
	fi

key: ## Generate application key
	@echo "$(GREEN)Generating application key...$(RESET)"
	docker-compose exec -T app php artisan key:generate

cache-clear: ## Clear all caches
	@echo "$(GREEN)Clearing caches...$(RESET)"
	docker-compose exec -T app php artisan cache:clear
	docker-compose exec -T app php artisan config:clear
	docker-compose exec -T app php artisan route:clear
	docker-compose exec -T app php artisan view:clear

cache-config: ## Cache configuration
	@echo "$(GREEN)Caching configuration...$(RESET)"
	docker-compose exec -T app php artisan config:cache

minio-setup: ## Setup MinIO bucket (creates bucket if not exists)
	@echo "$(GREEN)Setting up MinIO bucket...$(RESET)"
	@echo "$(YELLOW)Waiting for MinIO to be ready...$(RESET)"
	@sleep 5
	@docker-compose exec -T app bash -c '\
		MINIO_ENDPOINT="$${MINIO_ENDPOINT:-http://127.0.0.1:9002}"; \
		MINIO_ACCESS_KEY="$${MINIO_ACCESS_KEY:-minioadmin}"; \
		MINIO_SECRET_KEY="$${MINIO_SECRET_KEY:-minioadmin123}"; \
		MINIO_BUCKET="$${MINIO_BUCKET:-workforcehub}"; \
		for i in {1..60}; do \
			if curl -sf "$$MINIO_ENDPOINT/minio/health/live" > /dev/null 2>&1; then \
				echo "$(GREEN)✅ MinIO is ready!$(RESET)"; \
				break; \
			fi; \
			if [ $$i -eq 60 ]; then \
				echo "$(YELLOW)⚠️  MinIO did not become ready in time$(RESET)"; \
				exit 0; \
			fi; \
			sleep 1; \
		done; \
		echo "$(GREEN)📦 Creating bucket: $$MINIO_BUCKET$(RESET)"; \
		response=$$(curl -s -w "\n%{http_code}" -X PUT "$$MINIO_ENDPOINT/$$MINIO_BUCKET" -H "x-amz-content-sha256: UNSIGNED-PAYLOAD" --user "$$MINIO_ACCESS_KEY:$$MINIO_SECRET_KEY" 2>/dev/null || echo "000"); \
		http_code=$$(echo "$$response" | tail -n1); \
		if [ "$$http_code" = "200" ] || [ "$$http_code" = "409" ]; then \
			echo "$(GREEN)✅ Bucket '\''$$MINIO_BUCKET'\'' is ready$(RESET)"; \
		else \
			echo "$(YELLOW)⚠️  Could not create bucket automatically (HTTP $$http_code)$(RESET)"; \
			echo "$(YELLOW)   You can create it manually via MinIO Console: http://localhost:9001$(RESET)"; \
		fi' || echo "$(YELLOW)⚠️  MinIO setup had issues, but continuing...$(RESET)"

install: ## Full installation (dependencies + key + migrate + minio)
	@echo "$(GREEN)Running full installation...$(RESET)"
	@echo "$(YELLOW)Waiting for container to be ready...$(RESET)"
	@sleep 5
	@make composer || true
	@make npm || true
	@make npm-build || true
	@make key || true
	@sleep 3
	@make migrate || true
	@make minio-setup || true
	@echo "$(GREEN)Installation complete!$(RESET)"

setup: ## Initial setup (build + install)
	@echo "$(GREEN)Running initial setup...$(RESET)"
	@make build
	@make up
	@sleep 10
	@make install
	@echo "$(GREEN)Setup complete! Access at http://localhost:8000$(RESET)"
	@echo "$(GREEN)MinIO Console: http://localhost:9001 (minioadmin/minioadmin123)$(RESET)"

mysql: ## Access MySQL shell
	@echo "$(GREEN)Accessing MySQL shell...$(RESET)"
	@echo "$(YELLOW)Username: laravel_user$(RESET)"
	@echo "$(YELLOW)Password: laravel_password$(RESET)"
	@echo "$(YELLOW)Database: employee_management$(RESET)"
	docker-compose exec app mysql -u laravel_user -p employee_management

status: ## Show container status
	@echo "$(GREEN)Container status:$(RESET)"
	docker-compose ps
	@echo ""
	@echo "$(GREEN)Service status (inside container):$(RESET)"
	@docker-compose exec app supervisorctl status || echo "Container not running"

clean: ## Remove containers, volumes, and images
	@echo "$(YELLOW)WARNING: This will remove containers, volumes, and images!$(RESET)"
	@read -p "Continue? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose down -v --rmi all; \
		echo "$(GREEN)Cleanup complete!$(RESET)"; \
	fi

clean-volumes: ## Remove only volumes (keeps images)
	@echo "$(YELLOW)WARNING: This will remove all volumes!$(RESET)"
	@read -p "Continue? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose down -v; \
		echo "$(GREEN)Volumes removed!$(RESET)"; \
	fi

test: ## Run PHPUnit tests
	@echo "$(GREEN)Running tests...$(RESET)"
	docker-compose exec app php artisan test

tinker: ## Open Laravel Tinker
	docker-compose exec app php artisan tinker

queue-work: ## Start queue worker
	@echo "$(GREEN)Starting queue worker...$(RESET)"
	docker-compose exec app php artisan queue:work

queue-listen: ## Start queue listener
	@echo "$(GREEN)Starting queue listener...$(RESET)"
	docker-compose exec app php artisan queue:listen

queue-work-bg: ## Start queue worker in background (detached)
	@echo "$(GREEN)Starting queue worker in background...$(RESET)"
	@docker-compose exec -d app php artisan queue:work --daemon || \
		(docker-compose exec -d app sh -c "php artisan queue:work &")
	@echo "$(GREEN)Queue worker started in background$(RESET)"
	@echo "$(YELLOW)Check logs with: make logs$(RESET)"

composer-dump: ## Dump composer autoload (after adding new classes)
	@echo "$(GREEN)Dumping composer autoload...$(RESET)"
	docker-compose exec -T app composer dump-autoload

apply-changes: ## Apply all recent changes (migrations, autoload, cache clear)
	@echo "$(GREEN)Applying all changes...$(RESET)"
	@echo "$(YELLOW)1. Updating composer autoload...$(RESET)"
	@make composer-dump || true
	@echo "$(YELLOW)2. Running database migrations...$(RESET)"
	@make migrate || true
	@echo "$(YELLOW)3. Clearing all caches...$(RESET)"
	@make cache-clear || true
	@echo "$(GREEN)✅ All changes applied!$(RESET)"
	@echo "$(YELLOW)Note: Start queue worker with 'make queue-work' for background jobs$(RESET)"

setup-queue: ## Setup queue tables (for database queue driver)
	@echo "$(GREEN)Setting up queue tables...$(RESET)"
	@docker-compose exec -T app php artisan queue:table || true
	@make migrate || true
	@echo "$(GREEN)Queue tables ready!$(RESET)"

security-audit: ## Run dependency security audit (composer audit)
	@echo "$(GREEN)Running security audit...$(RESET)"
	@docker-compose exec -T app composer audit || \
		(echo "$(YELLOW)composer audit not available. Install with: composer require symfony/security-checker$(RESET)" && exit 0)
	@echo "$(GREEN)Security audit completed!$(RESET)"

security-check: security-audit ## Alias for security-audit

