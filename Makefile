.PHONY: help install up down restart logs logs-app logs-worker shell shell-node test migrate fresh lint build

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?##' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "%-15s %s\n", $$1, $$2}'

install: ## First-time setup: build, migrate, generate key
	cp -n app/.env.example app/.env || true
	docker compose build
	docker compose run --rm app php artisan key:generate
	docker compose run --rm app php artisan migrate

up: ## Start all containers
	docker compose up -d

down: ## Stop all containers
	docker compose down

restart: ## Restart all containers
	docker compose down
	docker compose up -d

logs: ## Tail all container logs
	docker compose logs -f

logs-app: ## Tail Laravel app logs
	docker compose logs -f app

logs-worker: ## Tail screenshot worker logs
	docker compose logs -f screenshot-worker

shell: ## Open shell in app container
	docker compose exec app sh

shell-node: ## Open shell in Node worker container
	docker compose exec screenshot-worker sh

test: ## Run test suite
	docker compose run --rm app php artisan test

migrate: ## Run database migrations
	docker compose run --rm app php artisan migrate

fresh: ## Fresh migration with seeders
	docker compose run --rm app php artisan migrate:fresh --seed

lint: ## Run linters (PHP CS Fixer, ESLint)
	docker compose run --rm app ./vendor/bin/pint
	docker compose run --rm screenshot-worker npm run lint

build: ## Rebuild all containers
	docker compose build
