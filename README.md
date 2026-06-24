<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/PHP-8.4+-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/Redis-7.x-DC382D?style=for-the-badge&logo=redis&logoColor=white" />
  <img src="https://img.shields.io/badge/License-MIT-22c55e?style=for-the-badge" />
</p>

<h1 align="center">Bank Performance Aggregator</h1>

<p align="center">
  A production-grade Laravel application for aggregating, validating, and analyzing<br/>
  monthly Excel performance reports submitted by organizational units across all zones.
</p>

---

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [How It Works](#how-it-works)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Environment Configuration](#environment-configuration)
- [Queue Worker](#queue-worker)
- [Testing](#testing)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [License](#license)

---

## Overview

**Bank Performance Aggregator** is an internal web platform designed to eliminate the manual overhead of consolidating monthly performance reports. Each organizational unit submits a structured `.xlsx` file. The system ingests these files asynchronously, validates all records against reference tables, persists clean data into a relational database, and exposes a management dashboard with filtering, aggregation, and Excel export capabilities.

### Problems it solves

Without this system, the monthly review process involves:

- Manually collecting dozens of Excel files from different units
- Consolidating them into a master spreadsheet — error-prone and time-consuming
- No automated detection of invalid or duplicate records
- No way to generate ranked performance reports without hours of manual work

**Bank Performance Aggregator automates all of that.**

---

## Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                        Browser Client                         │
│                  Blade + Alpine.js + Tailwind                 │
└────────────────────────────┬─────────────────────────────────┘
                             │ HTTP / JSON
┌────────────────────────────▼─────────────────────────────────┐
│                     Laravel 13 Application                     │
│                                                               │
│   ┌───────────────┐   ┌─────────────────┐   ┌─────────────┐  │
│   │  Controllers  │   │    Services     │   │    Jobs     │  │
│   │               │   │                 │   │             │  │
│   │ UploadCtrl    │──▶│ ExcelParser     │──▶│ ProcessFile │  │
│   │ ReportCtrl    │   │ ValidationSvc   │   │             │  │
│   │ ExportCtrl    │   │ AggregatorSvc   │   │             │  │
│   └───────────────┘   └─────────────────┘   └─────────────┘  │
│                                                               │
│   ┌───────────────────────────────────────────────────────┐   │
│   │              Eloquent ORM  +  Repositories            │   │
│   └───────────────────────────────────────────────────────┘   │
└────────────────────────────┬─────────────────────────────────┘
                             │
           ┌─────────────────┼──────────────────┐
           │                 │                  │
   ┌───────▼──────┐  ┌───────▼───────┐  ┌──────▼───────┐
   │   MySQL 8+   │  │  Redis Queue  │  │   Storage    │
   │  Primary DB  │  │   + Cache     │  │   Uploads    │
   └──────────────┘  └───────────────┘  └──────────────┘
```

### Layer responsibilities

| Layer | Responsibility |
|-------|----------------|
| `Controllers` | Accept HTTP requests, delegate to services, return responses |
| `ExcelParserService` | Load `.xlsx` files, identify sheets, extract and normalize raw rows |
| `ValidationService` | Validate each row against reference tables with Redis-cached lookups |
| `AggregatorService` | Run grouped queries and compute performance statistics |
| `ProcessUploadedFileJob` | Async queue job — parse → validate → persist → report |
| `Repositories` | Decouple query logic from business logic |

---

## How It Works

```
User uploads one or more .xlsx files
             │
             ▼
     UploadController
             │
             ├── Validate file format and size
             ├── Store file to disk
             ├── Create Upload record (status: pending)
             └── Dispatch async job to Redis queue
                          │
                          ▼
             ProcessUploadedFileJob
                          │
                          ├── Parse both sheets
                          ├── Validate every row
                          │     ├── Valid rows   → saved to database
                          │     └── Invalid rows → logged with reason
                          │
                          └── Update Upload status + stats
```

Key design decisions:

- **Async processing** — files are queued immediately after upload; the user does not wait for processing to complete.
- **Idempotent inserts** — re-uploading the same file does not create duplicate records.
- **Soft validation** — a single invalid row does not reject the entire file; only that row is skipped and logged.
- **Redis caching** — reference table lookups (zones, branches, employees) are cached to avoid repeated database hits during bulk validation.

---

## Prerequisites

| Dependency | Minimum Version | Notes |
|------------|----------------|-------|
| PHP | 8.4+ | Extensions: `mbstring`, `zip`, `pdo_mysql`, `openssl`, `curl`, `fileinfo` |
| Composer | 2.x | PHP dependency manager |
| MySQL | 8.0+ | Primary relational database |
| Redis | 7.x | Queue backend and cache |
| Node.js | 20 LTS | Asset compilation |
| npm | 10.x | — |

---

## Installation

### 1. Clone and install dependencies

```bash
git clone https://github.com/your-org/bank-performance-aggregator.git
cd bank-performance-aggregator

composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### 2. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database

Create the database:

```sql
CREATE DATABASE your_database
    CHARACTER SET utf8
    COLLATE utf8_unicode_ci;
```

Run migrations and seed reference data:

```bash
php artisan migrate --seed
```

### 4. Storage

```bash
php artisan storage:link
```

### 5. Start development server

```bash
php artisan serve
```

---

## Environment Configuration

```dotenv
APP_NAME="Bank Performance Aggregator"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_TIMEZONE=Asia/Tehran

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_CHARSET=utf8
DB_COLLATION=utf8_unicode_ci

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

LOG_CHANNEL=daily
LOG_LEVEL=warning
```

---

## Queue Worker

```bash
# Development
php artisan queue:work redis --tries=3 --timeout=120

# Production — use Supervisor
```

### Supervisor config

```ini
[program:bpa-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/bpa/artisan queue:work redis --tries=3 --timeout=120
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/bpa-worker.log
stopwaitsecs=120
```

---

## Testing

```bash
# Run all tests
php artisan test

# With code coverage
php artisan test --coverage --min=80
```

---

## Deployment

### Post-deploy checklist

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
php artisan migrate --force
php artisan queue:restart
```

### Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/bpa/public;
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit using [Conventional Commits](https://www.conventionalcommits.org):
   ```
   feat: add new feature
   fix: resolve a bug
   docs: update documentation
   refactor: restructure without behavior change
   test: add or update tests
   ```
4. Push and open a Pull Request

---

## License

MIT © 2025
