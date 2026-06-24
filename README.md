<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/PHP-8.4+-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/Redis-7.x-DC382D?style=for-the-badge&logo=redis&logoColor=white" />
  <img src="https://img.shields.io/badge/License-MIT-22c55e?style=for-the-badge" />
</p>

<h1 align="center">PerformanceAggregator</h1>

<p align="center">
  A production-grade Laravel application for aggregating, validating, and analyzing<br/>
  monthly Excel performance reports submitted by organizational units.
</p>

---

## Table of Contents

- [Overview](#overview)
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

**PerformanceAggregator** is an internal web platform that consolidates monthly `.xlsx` performance reports submitted by organizational units. Files are uploaded in bulk, processed asynchronously in the background, validated against reference tables, and made immediately available through a filterable dashboard with export capabilities.

---

## Prerequisites

| Dependency | Minimum Version |
|------------|----------------|
| PHP | 8.4+ |
| Composer | 2.x |
| MySQL | 8.0+ |
| Redis | 7.x |
| Node.js | 20 LTS |
| npm | 10.x |

---

## Installation

### 1. Clone and install dependencies

```bash
git clone https://github.com/your-org/performance-aggregator.git
cd performance-aggregator

composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### 2. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database

```bash
php artisan migrate --seed
```

### 4. Storage

```bash
php artisan storage:link
```

---

## Environment Configuration

```dotenv
APP_NAME="PerformanceAggregator"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

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
[program:pa-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/pa/artisan queue:work redis --tries=3 --timeout=120
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/pa-worker.log
stopwaitsecs=120
```

---

## Testing

```bash
php artisan test
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
    root /var/www/pa/public;
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
3. Commit using [Conventional Commits](https://www.conventionalcommits.org)
4. Push and open a Pull Request

---

## License

MIT © 2025
