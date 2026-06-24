<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/PHP-8.4+-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/Redis-7.x-DC382D?style=for-the-badge&logo=redis&logoColor=white" />
  <img src="https://img.shields.io/badge/License-MIT-22c55e?style=for-the-badge" />
</p>

<h1 align="center">Bank Performance Aggregator</h1>

<p align="center">
  A production-grade Laravel application for aggregating, validating, and analyzing<br/>
  monthly Excel performance reports submitted by bank branches across all sales zones.
</p>

---

## Table of Contents

- [Overview](#overview)
- [Problem Statement](#problem-statement)
- [Architecture](#architecture)
- [Data Model](#data-model)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Environment Configuration](#environment-configuration)
- [Project Structure](#project-structure)
- [File Processing Pipeline](#file-processing-pipeline)
- [Validation Rules](#validation-rules)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [License](#license)

---

## Overview

**BankPerformanceAggregator** is an internal web platform that consolidates monthly `.xlsx` performance reports submitted by bank branches. Each branch submits a file named after its branch code (e.g., `2329.xlsx`, `2662.xlsx`). The system ingests these files asynchronously, validates all rows against reference tables, stores clean records in a relational database, and exposes a management dashboard with filtering, aggregation, and Excel export capabilities.

The system handles two performance dimensions per file:

- **Banking Services Activation** — tracking which employees activated which digital services (Sepino, Chekno, Mobile Banking, Internet Banking, POS Marketing, Payment Gateway Marketing) for which customers.
- **POS Terminal Monitoring** — tracking branch employee visits to merchant POS terminals.

---

## Problem Statement

Before this system, the performance review process looked like this:

- A supervisor manually collected 30+ Excel files from branches each month
- Files arrived with inconsistent naming, missing rows, and mistyped branch/employee codes
- Consolidation was done manually in a master spreadsheet — error-prone and time-consuming
- There was no way to detect duplicate submissions or cross-validate employee codes
- Generating a ranked performance report took hours of manual work

**BankPerformanceAggregator eliminates all of that.** Files are uploaded in bulk, processed asynchronously in the background, validated against live reference tables, and made immediately available through a filterable dashboard and exportable reports.

---

## Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                        Browser Client                         │
│                  Blade + Alpine.js + Tailwind                 │
└────────────────────────────┬─────────────────────────────────┘
                             │ HTTP / JSON
┌────────────────────────────▼─────────────────────────────────┐
│                     Laravel 11 Application                     │
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
│   │           Data Layer  —  Eloquent + Repositories      │   │
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

### Layer Responsibilities

| Layer | Responsibility |
|-------|----------------|
| `Controllers` | Accept HTTP requests, delegate to services, return responses |
| `ExcelParserService` | Load `.xlsx` files, identify sheets by title, extract and normalize raw rows |
| `UploadValidationService` | Validate each row against reference tables with Redis-cached lookups |
| `AggregatorService` | Run grouped queries and compute performance statistics |
| `ProcessUploadedFileJob` | Async queue job — parse → validate → persist → report |
| `Repositories` | Decouple query logic from business logic |

---

## Data Model

### Sheet 1 — Banking Services (`فعال سازی خدمات نوین بانکی`)

| Column | Type | Description | Example |
|--------|------|-------------|---------|
| Row # | int | Line number | 1 |
| Activation Date | string | Jalali date `YYYYMMDD` | `14050201` |
| Zone Code | int | Zone identifier: 0, 1, or 2 | `1` |
| Branch Code | string | 4-digit branch code | `2329` |
| Branch Name | string | Branch display name | `Khorramshahr St.` |
| Employee Code | string | Personnel ID | `39826020` |
| Employee Name | string | Full name | `Hamed Mohammadi` |
| Service Type | string | Enum — see reference table | `Sepino` |
| Customer Account | string | 12-digit account number | `111111111111` |
| Customer Name | string | Name of the onboarded customer | `Alireza Hosseini` |
| Notes | string | Optional free text | — |

**Valid service types (seeded from Sheet5):**

| Slug | Persian Name |
|------|-------------|
| `sepino` | سپینو |
| `cheknoo` | چکنو |
| `pos-marketing` | بازاریابی پایانه فروش |
| `payment-gateway-marketing` | بازاریابی درگاه پرداخت |
| `mobile-banking` | همراه بانک |
| `internet-banking` | اینترنت بانک |

### Sheet 2 — POS Terminal Monitoring (`پایش پایانه های فروش`)

| Column | Type | Description |
|--------|------|-------------|
| Row # | int | Line number |
| Visit Date | string | Jalali date `YYYYMMDD` |
| Zone Code | int | 0, 1, or 2 |
| Branch Code | string | 4-digit branch code |
| Branch Name | string | Branch display name |
| Employee Code | string | Personnel ID |
| Employee Name | string | Full name |
| Terminal Number | string | POS terminal identifier |
| Customer Account | string | 12-digit account number |
| Customer Name | string | Merchant name |
| Colleague Account | string | Optional — 12-digit |
| Notes | string | Optional free text |

### Reference Tables (Sheet4, Sheet5, Sheet6)

These sheets are **identical across all uploaded files** and are seeded into the database on initial setup. They serve as validation sources at runtime.

- **Sheet4 (`branches`)** — 32 branch and sub-branch records with zone assignments
- **Sheet5 (`service_types`)** — 6 valid banking service type names
- **Sheet6 (`employees`)** — 263 active personnel records

> **Design note:** Reference tables are loaded into Redis for 60-minute TTL caching. Every row validation hits cache, not the database.

---

## Prerequisites

| Dependency | Minimum Version | Notes |
|------------|----------------|-------|
| PHP | 8.3+ | Extensions: `mbstring`, `zip`, `pdo_mysql`, `openssl`, `curl`, `fileinfo` |
| Composer | 2.x | PHP dependency manager |
| MySQL | 8.0+ | Primary relational database |
| Redis | 7.x | Queue backend and reference table cache |
| Node.js | 20 LTS | Asset compilation only |
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

Create the database first:

```sql
CREATE DATABASE bank_performance
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Then run migrations and seed reference data:

```bash
php artisan migrate --seed
```

Seeders included:

| Seeder | Records |
|--------|---------|
| `BranchSeeder` | 32 branches and sub-branches |
| `ServiceTypeSeeder` | 6 service types |
| `EmployeeSeeder` | Loaded via Artisan command from the source Excel file |

To import employees from the reference Excel:

```bash
php artisan bpa:import-employees path/to/reference.xlsx
```

### 4. Storage

```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

### 5. Queue worker

```bash
# Development
php artisan queue:work redis --queue=excel-processing

# Production — use Supervisor (see Deployment section)
```

### 6. Start development server

```bash
php artisan serve
# http://localhost:8000
```

---

## Environment Configuration

```dotenv
APP_NAME="BankPerformanceAggregator"
APP_ENV=production
APP_KEY=                         # Generated by php artisan key:generate
APP_DEBUG=false
APP_URL=https://your-domain.ir
APP_LOCALE=fa
APP_TIMEZONE=Asia/Tehran

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bank_performance
DB_USERNAME=bpa_user
DB_PASSWORD=strong_password_here
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Upload constraints
EXCEL_MAX_FILE_SIZE_MB=10
EXCEL_MAX_ROWS_PER_SHEET=200

# Reference validation toggles
VALIDATE_BRANCH_CODES=true
VALIDATE_EMPLOYEE_CODES=true

LOG_CHANNEL=daily
LOG_LEVEL=warning
```

---

## Project Structure

```
app/
├── Console/Commands/
│   ├── ImportEmployees.php              # Import personnel from reference Excel
│   └── ReprocessFailedUploads.php       # Retry failed upload jobs
│
├── Exceptions/
│   └── InvalidExcelStructureException.php
│
├── Http/
│   ├── Controllers/
│   │   ├── UploadController.php         # File upload endpoints
│   │   ├── ReportController.php         # Dashboard and aggregated reports
│   │   └── ExportController.php         # Excel export download
│   └── Requests/
│       └── UploadFilesRequest.php       # Upload validation rules
│
├── Jobs/
│   └── ProcessUploadedFileJob.php       # Async Excel processing job
│
├── Models/
│   ├── Upload.php                       # Upload records with UUID PK
│   ├── BankingServiceRecord.php         # Sheet 1 rows
│   ├── PosMonitoringRecord.php          # Sheet 2 rows
│   ├── Branch.php                       # Reference: branches
│   ├── Employee.php                     # Reference: personnel
│   └── ServiceType.php                  # Reference: service types
│
├── Repositories/
│   ├── UploadRepository.php
│   ├── BankingServiceRepository.php
│   └── PosMonitoringRepository.php
│
└── Services/
    ├── Aggregation/
    │   └── AggregatorService.php        # Grouped stats and dashboard summary
    ├── Excel/
    │   └── ExcelParserService.php       # Sheet detection, row extraction, normalization
    ├── Export/
    │   └── ExcelExportService.php       # Consolidated Excel output generation
    └── Validation/
        └── UploadValidationService.php  # Row-level validation with cached lookups

database/
├── migrations/                          # 6 migration files
└── seeders/
    ├── BranchSeeder.php
    ├── ServiceTypeSeeder.php
    └── DatabaseSeeder.php

tests/
├── Feature/
│   ├── UploadTest.php
│   ├── ReportTest.php
│   └── ExportTest.php
├── Fixtures/
│   ├── valid_sample.xlsx
│   ├── missing_sheet.xlsx
│   ├── invalid_employee.xlsx
│   └── malformed_date.xlsx
└── Unit/
    └── Services/
        ├── ExcelParserServiceTest.php
        ├── ValidationServiceTest.php
        └── AggregatorServiceTest.php
```

---

## File Processing Pipeline

```
POST /api/v1/uploads
        │
        ▼
UploadController::store()
        │
        ├── Validate: extension=xlsx, size ≤ 10MB, max 50 files per request
        ├── Store file: storage/excel-uploads/{year}/{month}/{uuid}.xlsx
        ├── Create Upload record (status: pending)
        └── Dispatch ProcessUploadedFileJob → Redis queue
                │
                ▼
        ProcessUploadedFileJob::handle()
                │
                ├── ExcelParserService::parse()
                │       ├── Load spreadsheet via PhpSpreadsheet
                │       ├── Assert required sheets exist by title
                │       ├── Iterate data rows (skip empty rows)
                │       └── Normalize: trim whitespace, strip leading zeros from accounts
                │
                ├── UploadValidationService::validateBankingServiceRows()
                │       ├── Persian date format (YYYYMMDD, year 1380–1450)
                │       ├── Zone code ∈ {0, 1, 2}
                │       ├── Branch code exists in branches table (Redis cache)
                │       ├── Employee code exists in employees table (Redis cache)
                │       ├── Service type exists in service_types table (Redis cache)
                │       ├── Customer account = exactly 12 digits
                │       └── Customer name not empty
                │
                ├── UploadValidationService::validatePosMonitoringRows()
                │       └── (same checks minus service type, plus terminal number)
                │
                ├── DB::transaction()
                │       ├── BankingServiceRecord::upsert() in chunks of 500
                │       └── PosMonitoringRecord::insert() in chunks of 500
                │
                └── Upload::markAsCompleted(stats)
                        └── Stores row counts + per-row error details as JSON
```

**Idempotency:** `BankingServiceRecord` uses a unique constraint on `(activation_date, employee_code, service_type, customer_account)`. Job retries on failure will not create duplicate records.

---

## Validation Rules

### Structural (file-level — rejects entire file)

- Missing required sheet (`خدمات نوین` or `پایش پایانه های فروش`)
- File is password-protected or corrupt
- Column count does not match expected schema

### Row-level (soft — rejects only the offending row)

| Field | Rule |
|-------|------|
| Date | Format `YYYYMMDD`, valid Jalali calendar, year between 1380 and 1450 |
| Zone | Integer value in `{0, 1, 2}` |
| Branch Code | Present in `branches` table |
| Employee Code | Present in `employees` table with `is_active = true` |
| Service Type | Present in `service_types` table |
| Account Number | Exactly 12 digits, numeric only, no leading zeros |
| Terminal Number | Non-empty string, minimum 8 characters |

### Warnings (logged, row accepted)

- Branch name in file does not match the reference name (possible typo)
- Employee name in file does not match the personnel record

---

## API Reference

### Upload files

```http
POST /api/v1/uploads
Content-Type: multipart/form-data

files[]: @2329.xlsx
files[]: @2662.xlsx
period: "140501"
```

```json
{
  "status": "queued",
  "upload_ids": ["uuid-1", "uuid-2"],
  "message": "2 files queued for processing"
}
```

### Check processing status

```http
GET /api/v1/uploads/{upload_id}
```

```json
{
  "id": "uuid-1",
  "filename": "2329.xlsx",
  "status": "completed",
  "rows_banking_service": 38,
  "rows_pos_monitoring": 12,
  "rows_rejected": 2,
  "errors": [
    {
      "sheet": "Banking Services",
      "row": 15,
      "errors": ["Employee code '99999999' not found in personnel table"],
      "raw_data": "Employee: 99999999 | Branch: 2329 | Date: 14050215"
    }
  ],
  "processed_at": "2025-02-01 09:14:22"
}
```

### Aggregated report

```http
GET /api/v1/reports/by-employee
  ?from=14050101
  &to=14050130
  &zone=1
  &branch_code=2329
  &service_type=sepino
```

```json
{
  "filters": { "from": "14050101", "to": "14050130", "zone": "1" },
  "count": 18,
  "data": [
    {
      "employee_code": "39826020",
      "employee_name": "Hamed Mohammadi",
      "branch_code": "2329",
      "zone": 1,
      "activations": {
        "سپینو": 12,
        "چکنو": 3,
        "همراه بانک": 8,
        "اینترنت بانک": 5,
        "بازاریابی پایانه فروش": 0,
        "بازاریابی درگاه پرداخت": 2,
        "total": 30
      },
      "pos_visits": 7
    }
  ]
}
```

### Export consolidated Excel

```http
GET /api/v1/exports/excel?from=14050101&to=14050130&format=aggregated
```

Returns `performance_report_14050101_14050130.xlsx`

---

## Testing

```bash
# Run all tests
php artisan test

# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature

# With code coverage (requires Xdebug or PCOV)
php artisan test --coverage --min=80
```

### Test fixtures

Place sample `.xlsx` files in `tests/Fixtures/` to cover edge cases:

| File | Purpose |
|------|---------|
| `valid_sample.xlsx` | 5 clean rows across both sheets |
| `missing_sheet.xlsx` | Sheet 1 absent — triggers structural rejection |
| `invalid_employee.xlsx` | Unknown employee code — triggers row rejection |
| `malformed_date.xlsx` | Date `99991399` — fails Jalali validation |
| `duplicate_submission.xlsx` | Same rows as `valid_sample.xlsx` — verifies upsert idempotency |

---

## Deployment

### Docker (recommended)

```yaml
# docker-compose.yml
version: '3.9'
services:
  app:
    build: .
    environment:
      APP_ENV: production
    volumes:
      - uploads_data:/var/www/html/storage/app

  worker:
    build: .
    command: php artisan queue:work redis --queue=excel-processing --tries=3 --timeout=120
    restart: always
    deploy:
      replicas: 2

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: bank_performance
      MYSQL_USER: bpa_user
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine

volumes:
  uploads_data:
  mysql_data:
```

### Supervisor (bare metal)

```ini
[program:bpa-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/bpa/artisan queue:work redis --queue=excel-processing --tries=3 --timeout=120
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/bpa-worker.log
stopwaitsecs=120
```

### Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.ir;
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

### Post-deploy checklist

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
php artisan migrate --force
php artisan queue:restart
```

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/add-pdf-export`
3. Commit using [Conventional Commits](https://www.conventionalcommits.org):
   ```
   feat: add PDF export for aggregated reports
   fix: handle empty rows in POS monitoring sheet
   refactor: extract column mapping to dedicated class
   test: add fixture for malformed Jalali date
   ```
4. Push and open a Pull Request

---

## License

MIT © 2025 — Your Organization
