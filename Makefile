# Use bash for recipes and run each target in a single shell
SHELL := /bin/bash
.ONESHELL:
.SHELLFLAGS := -e -o pipefail -c

up:
	 docker compose --env-file .env.docker up -d --build

up-work:
	 docker compose --env-file .env.docker up

down:
	 docker compose --env-file .env.docker down -v

logs:
	 docker compose --env-file .env.docker logs -f --tail=150

composer:
	 docker compose --env-file .env.docker run --rm app bash -lc 'composer $(ARGS)'

artisan:
	 docker compose --env-file .env.docker run --rm app bash -lc 'php artisan $(ARGS)'

tinker:
	 $(MAKE) artisan ARGS="tinker"

test:
	 $(MAKE) artisan ARGS="test"

npm-install:
	 docker compose --env-file .env.docker run --rm node sh -lc 'cd /var/www/html && npm install'

npm-dev:
	 docker compose --env-file .env.docker run --rm -p 5173:5173 node sh -lc 'cd /var/www/html && npm run dev -- --host'

npm-build:
	 docker compose --env-file .env.docker run --rm node sh -lc 'cd /var/www/html && npm run build'

xdebug-on:
	 docker compose --env-file .env.docker exec app bash -lc 'export XDEBUG_MODE=debug && pkill -o -USR2 php-fpm || true'

xdebug-off:
	 docker compose --env-file .env.docker exec app bash -lc 'export XDEBUG_MODE=off && pkill -o -USR2 php-fpm || true'

init:
	# создаём Laravel 12, если каталога нет или он пустой
	@if [ ! -d src ] || [ -z "$$(ls -A src 2>/dev/null || true)" ]; then \
		echo "→ Installing Laravel 12 into ./src"; \
		docker compose --env-file .env.docker run --rm app bash -lc 'composer create-project laravel/laravel:^12.0 /var/www/html'; \
	else \
		echo "✓ src уже существует — пропускаем create-project"; \
	fi

	# .env
	@if [ -f src/.env ]; then \
		echo "✓ .env уже есть"; \
	else \
		cp src/.env.example src/.env; \
	fi

	# Патчим .env для Postgres/Redis/Mailpit
	@sed -i.bak 's/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/' src/.env
	@sed -i.bak 's/^DB_HOST=.*/DB_HOST=postgres/' src/.env
	@sed -i.bak 's/^DB_PORT=.*/DB_PORT=5432/' src/.env
	@sed -i.bak 's/^DB_DATABASE=.*/DB_DATABASE=$${POSTGRES_DB:-app}/' src/.env
	@sed -i.bak 's/^DB_USERNAME=.*/DB_USERNAME=$${POSTGRES_USER:-app}/' src/.env
	@sed -i.bak 's/^DB_PASSWORD=.*/DB_PASSWORD=$${POSTGRES_PASSWORD:-app}/' src/.env
	@sed -i.bak 's/^REDIS_HOST=.*/REDIS_HOST=redis/' src/.env
	@sed -i.bak 's/^MAIL_MAILER=.*/MAIL_MAILER=smtp/' src/.env
	@sed -i.bak 's/^MAIL_HOST=.*/MAIL_HOST=mailpit/' src/.env
	@sed -i.bak 's/^MAIL_PORT=.*/MAIL_PORT=1025/' src/.env
	@sed -i.bak 's#^APP_URL=.*#APP_URL=$${APP_URL:-http://localhost:8080}#' src/.env
	@rm -f src/.env.bak

	# права и ключ
	@docker compose --env-file .env.docker run --rm app bash -lc 'chown -R www-data:www-data storage bootstrap/cache || true'
	@docker compose --env-file .env.docker run --rm app bash -lc 'php artisan key:generate'

	@echo "✓ Laravel готов. Откройте http://localhost:8080"
