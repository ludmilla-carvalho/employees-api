### Makefile - Laravel Dev Helper ###

# Variáveis
EXEC_APP=docker compose exec app
EXEC_APP_TTY=docker compose exec -T app
COMPOSER=$(EXEC_APP) composer
ARTISAN=$(EXEC_APP) php artisan
NPM=$(EXEC_APP) npm
PHPUNIT=$(EXEC_APP) ./vendor/bin/phpunit
PINT=$(EXEC_APP_TTY) ./vendor/bin/pint

# Comandos principais
up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

install:
	$(COMPOSER) install
	$(NPM) install

migrate:
	$(ARTISAN) migrate --seed

test:
	$(PHPUNIT)

format:
	$(PINT) --dirty

format-test:
	$(PINT) --test

queue:
	docker compose logs -f worker

logs:
	docker compose logs -f app

bash:
	$(EXEC_APP) bash

restart:
	docker compose restart app

scheduler:
	docker compose logs -f scheduler

cache-clear:
	$(ARTISAN) cache:clear
	$(ARTISAN) config:clear
	$(ARTISAN) route:clear
	$(ARTISAN) view:clear

fresh:
	$(ARTISAN) migrate:fresh --seed

.PHONY: up down build install migrate test lint format queue logs bash restart scheduler cache-clear fresh
