# Laravel Docker Starter

A clean Docker-based Laravel 12 development environment with PostgreSQL, Redis, and all necessary services.

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose v2

### 1. Start the Laravel Application
```bash
make up
make init   # Creates Laravel 12 project in ./src and configures environment
```

### 2. Run Database Migrations
```bash
make artisan ARGS="migrate"
```

### 3. Access the Application
- **Web Application**: http://localhost:8080
- **Laravel API**: http://localhost:8080/api
- **Mailpit (Email testing)**: http://localhost:8025

## 📊 Services

- **nginx**: `:8080` - Web server and Laravel app
- **php-fpm (app)**: PHP 8.3 with Composer and Laravel
- **postgres**: `:54322` (internal `postgres:5432`) - Database
- **redis**: `:63790` - Caching and sessions  
- **mailpit**: `:8025` - Email testing sandbox
- **node**: Node 20 for frontend builds

## 🛠 Makefile Commands

### Laravel Development:
- `make up` - Start all containers in background
- `make up-work` - Start containers with logs visible
- `make down` - Stop and remove containers
- `make logs` - View container logs
- `make init` - Create Laravel 12 project and configure environment
- `make composer ARGS="..."` - Run Composer in app container
- `make artisan ARGS="..."` - Execute Artisan commands
- `make tinker` - Laravel Tinker REPL
- `make test` - Run PHPUnit tests

### Frontend Development:
- `make npm-install` - Install Node.js dependencies
- `make npm-dev` - Start development build with watch
- `make npm-build` - Production build

### Debugging:
- `make xdebug-on` - Enable Xdebug
- `make xdebug-off` - Disable Xdebug

## 🎨 Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Web Browser   │───▶│   Laravel App   │───▶│  PostgreSQL DB  │
│                 │    │                 │    │                 │
│ - Blade Views   │    │ - MVC Pattern   │    │ - Migrations    │
│ - API Calls     │    │ - API Routes    │    │ - Models        │
│ - Responsive    │    │ - Controllers   │    │ - Eloquent ORM  │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## 📁 Project Structure

```
├── src/                          # Laravel 12 application
│   ├── app/
│   │   ├── Models/              # Eloquent models
│   │   ├── Http/Controllers/    # Controllers
│   │   └── Console/Commands/    # Artisan commands
│   ├── resources/views/         # Blade templates
│   ├── routes/                  # Web and API routes
│   └── database/migrations/     # Database schema
├── docker/                      # Docker configuration
│   ├── nginx/                   # Nginx configuration
│   ├── php/                     # PHP-FPM configuration
│   └── postgres/                # PostgreSQL configuration
└── Makefile                     # Development commands
```

## 🔧 Environment Configuration

### Database Settings (`.env.docker`):
```env
POSTGRES_DB=app
POSTGRES_USER=app
POSTGRES_PASSWORD=app
POSTGRES_PORT=54322
```

### Laravel Environment (auto-generated in `src/.env`):
```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=app
DB_USERNAME=app
DB_PASSWORD=app
```

## 🧪 Development Workflow

### Creating New Features:
```bash
# Generate controller
make artisan ARGS="make:controller YourController"

# Generate model with migration
make artisan ARGS="make:model YourModel -m"

# Run migrations
make artisan ARGS="migrate"
```

### API Development:
```bash
# Test API endpoints
curl http://localhost:8080/api/your-endpoint
```

### Frontend Development:
1. Edit Blade templates in `src/resources/views/`
2. Use Vite for asset compilation: `make npm-dev`
3. Access your app at http://localhost:8080

## 🚀 Deployment Notes

- **Production**: Use environment-specific `.env` files
- **Security**: Update default database credentials
- **Performance**: Configure opcache and other PHP optimizations
- **Monitoring**: Add logging and monitoring as needed

---

**Happy coding!** 🚀