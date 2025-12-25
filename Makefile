.PHONY: help up down build rebuild restart logs shell composer npm artisan migrate fresh seed install clean

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

down: ## Stop the container
	@echo "$(GREEN)Stopping container...$(RESET)"
	docker-compose down

build: ## Build the Docker image
	@echo "$(GREEN)Building Docker image...$(RESET)"
	docker-compose build

rebuild: ## Rebuild the Docker image from scratch
	@echo "$(GREEN)Rebuilding Docker image (no cache)...$(RESET)"
	docker-compose build --no-cache
	@echo "$(GREEN)Image rebuilt! Start with: make up$(RESET)"

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
	docker-compose exec app composer install --no-interaction || \
		(echo "$(YELLOW)Install failed, trying update...$(RESET)" && \
		 docker-compose exec app composer update --no-interaction)

composer-update: ## Update Composer dependencies and lock file
	@echo "$(GREEN)Updating Composer dependencies and lock file...$(RESET)"
	@echo "$(YELLOW)Waiting for container to be ready...$(RESET)"
	@sleep 3
	docker-compose exec app composer update --no-interaction

composer-fix-lock: ## Fix composer.lock to match composer.json
	@echo "$(GREEN)Fixing composer.lock file...$(RESET)"
	@echo "$(YELLOW)This will update composer.lock to match composer.json$(RESET)"
	@echo "$(YELLOW)Waiting for container to be ready...$(RESET)"
	@sleep 3
	docker-compose exec app composer update --lock --no-interaction
	@echo "$(GREEN)composer.lock updated!$(RESET)"

npm: ## Install Node dependencies
	@echo "$(GREEN)Installing Node dependencies...$(RESET)"
	docker-compose exec app npm install

npm-build: ## Build frontend assets (production)
	@echo "$(GREEN)Building frontend assets...$(RESET)"
	docker-compose exec app npm run production

npm-dev: ## Build frontend assets (development)
	@echo "$(GREEN)Building frontend assets (dev)...$(RESET)"
	docker-compose exec app npm run dev

artisan: ## Run artisan command (usage: make artisan CMD="migrate")
	@if [ -z "$(CMD)" ]; then \
		echo "$(YELLOW)Usage: make artisan CMD=\"your-command\"$(RESET)"; \
		echo "$(YELLOW)Example: make artisan CMD=\"migrate\"$(RESET)"; \
	else \
		docker-compose exec app php artisan $(CMD); \
	fi

migrate: ## Run database migrations
	@echo "$(GREEN)Running migrations...$(RESET)"
	docker-compose exec app php artisan migrate

migrate-fresh: ## Fresh migration (drop all tables and re-run)
	@echo "$(YELLOW)WARNING: This will drop all tables!$(RESET)"
	@read -p "Continue? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose exec app php artisan migrate:fresh; \
	fi

migrate-rollback: ## Rollback the last migration
	@echo "$(GREEN)Rolling back last migration...$(RESET)"
	docker-compose exec app php artisan migrate:rollback

seed: ## Run database seeders
	@echo "$(GREEN)Running seeders...$(RESET)"
	docker-compose exec app php artisan db:seed

fresh-seed: ## Fresh migration with seeders
	@echo "$(YELLOW)WARNING: This will drop all tables and re-run migrations + seeders!$(RESET)"
	@read -p "Continue? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose exec app php artisan migrate:fresh --seed; \
	fi

key: ## Generate application key
	@echo "$(GREEN)Generating application key...$(RESET)"
	docker-compose exec app php artisan key:generate

cache-clear: ## Clear all caches
	@echo "$(GREEN)Clearing caches...$(RESET)"
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

cache-config: ## Cache configuration
	@echo "$(GREEN)Caching configuration...$(RESET)"
	docker-compose exec app php artisan config:cache

install: ## Full installation (dependencies + key + migrate)
	@echo "$(GREEN)Running full installation...$(RESET)"
	@echo "$(YELLOW)Waiting for container to be ready...$(RESET)"
	@sleep 5
	@make composer || true
	@make npm || true
	@make npm-build || true
	@make key || true
	@sleep 3
	@make migrate || true
	@echo "$(GREEN)Installation complete!$(RESET)"

setup: ## Initial setup (build + install)
	@echo "$(GREEN)Running initial setup...$(RESET)"
	@make build
	@make up
	@sleep 10
	@make install
	@echo "$(GREEN)Setup complete! Access at http://localhost:8000$(RESET)"

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

