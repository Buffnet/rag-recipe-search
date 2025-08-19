# Laravel 12 + Docker + PostgreSQL (Local Dev Starter)

Этот стартер поднимет **Laravel 12.x** в Docker c Nginx, PHP-FPM, PostgreSQL, Redis и Mailpit.
Всё собирается и запускается одной командой, а установка `laravel/laravel:^12.0`
происходит внутри контейнера (Composer в контейнере).

## Быстрый старт
1) Установите Docker и Docker Compose v2.
2) В корне проекта выполните:
   ```bash
   make up
   make init   # создаст новый проект Laravel 12 в ./src, сгенерирует ключ, применит .env
   ```
3) Откройте: http://localhost:8080

> Если вы хотите указать имя проекта, установите переменную `APP_NAME` в `.env.docker` до `make init`.

## Сервисы
- **nginx**: `:8080`
- **php-fpm (app)**: PHP 8.3 с Composer
- **postgres**: порт `:54322` (внутри сети — `postgres:5432`), БД `app`, пользователь/пароль — см. `.env.docker`
- **redis**: `:63790`
- **mailpit** (почтовая песочница): веб-интерфейс `:8025`
- **node**: Node 20 для сборки фронта (`npm run dev/build`), доступ через `make npm-*`

## Команды Makefile (основные)
- `make up` — поднять контейнеры в фоне
- `make down` — остановить и удалить контейнеры
- `make logs` — логи
- `make init` — создать новый проект Laravel 12 внутри `./src`, подготовить `.env`
- `make composer ARGS="..."` — выполнить Composer в контейнере `app`
- `make artisan ARGS="..."` — выполнить Artisan
- `make npm-install` / `make npm-dev` / `make npm-build` — npm внутри контейнера `node`
- `make tinker` — Artisan Tinker
- `make test` — PHPUnit
- `make xdebug-on` / `make xdebug-off` — переключить Xdebug

## Настройки окружения
В файле `.env.docker` задаются параметры БД, Redis, Mail и имя приложения.
После `make init` будет создан `.env` внутри `./src` на основе `.env.example` с нужными значениями
для подключения к Postgres/Redis/Mailpit.

## Важные пути
- Код приложения: `./src` (мапится в `/var/www/html` в контейнере PHP)
- Конфиги nginx: `./docker/nginx/default.conf`
- Dockerfile PHP: `./docker/php/Dockerfile`

## Примечания
- Первичная установка может занять время — Composer скачивает пакеты.
- Для Mac/Windows файлы проекта создаются на хосте, поэтому пермишены корректируются автоматически.
- Если Laravel уже создан в `./src`, команда `make init` пропустит установку.

Удачной разработки!
