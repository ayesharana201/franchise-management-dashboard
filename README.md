# Laravel Franchise Management Dashboard

A Laravel web app for franchisors to manage multiple franchise locations and track performance, royalties, and compliance from one place.

**Stack:** Laravel 11/12 (PHP 8.2+), Blade + Vue 3, Tailwind CSS, REST API (Sanctum tokens), MySQL / PostgreSQL / SQLite, Docker, GitHub Actions.

## Features (all 8 core features from the plan)

| # | Feature | Where |
|---|---------|-------|
| 1 | Multi-location performance dashboard | `/dashboard` — KPIs, monthly sales chart, leaderboard, alerts, date filter |
| 2 | Royalty & fee calculation | `/royalties` — live calculator, monthly sales entry → auto-generated statements, mark paid, overdue detection |
| 3 | Compliance checklist tracking | `/compliance` — define items, assign to one/all locations, record pass/fail with notes |
| 4 | Franchisee communication portal | `/messages` — threaded messages, replies, network-wide announcements |
| 5 | Inventory & supply ordering | `/inventory`, `/orders` — catalogue, stock-checked orders, status workflow, stock returned on cancel |
| 6 | Document & contract management | `/documents` — upload/download, expiry tracking, per-location or network-wide |
| 7 | Financial reporting per location | `/reports` — monthly P&L, fees paid/outstanding, CSV export |
| 8 | Role-based access | Franchisor / Regional manager / Franchisee, enforced on every route and query |

Plus: a REST API (`docs/API.md`), a daily scheduled job that flags overdue royalties and emails franchisees, Docker setup, and a CI pipeline.

## Quick start

This repository contains the **application layer** (migrations, models, controllers, views, tests, config). It is applied on top of a fresh Laravel install by the setup script:

```bash
# Linux / macOS / Git Bash / WSL
./setup.sh                 # creates ../franchise-app

# Windows PowerShell
.\setup.ps1
```

Requires PHP 8.2+, Composer 2, Node 20+. The script installs Laravel, adds Sanctum (`php artisan install:api`), copies this code over it, builds assets, migrates and seeds demo data, and runs the tests.

Then:

```bash
cd ../franchise-app
php artisan serve          # http://localhost:8000
```

### Manual steps (if you prefer)

```bash
composer create-project laravel/laravel franchise-app
cd franchise-app
php artisan install:api --no-interaction
cp -R /path/to/this/folder/. .          # overwrites bootstrap/app.php, routes, models, etc.
rm setup.sh setup.ps1 tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php
npm install && npm run build
php artisan migrate:fresh --seed
php artisan test
```

### Demo accounts (password: `password`)

| Role | Email | Sees |
|------|-------|------|
| Franchisor | `admin@franchise.test` | Everything |
| Regional manager | `north.manager@franchise.test` | North region only |
| Franchisee | `lahore@franchise.test` | Their own location only |

## Database

SQLite works out of the box (Laravel default). For MySQL or PostgreSQL set `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` in `.env` and run `php artisan migrate --seed`.

## Docker

```bash
cp .env.example .env && php artisan key:generate   # (or set APP_KEY manually)
docker compose up -d --build
docker compose exec app php artisan migrate --seed --force
# App: http://localhost:8080
```

Services: `app` (php-fpm), `web` (nginx), `scheduler` (runs `schedule:run` each minute), `db` (MySQL 8.4). After upgrading with new front-end assets run `docker compose down -v` once (or remove the `public` volume) so nginx picks up the new build.

Mail defaults to the `log` driver; set `MAIL_*` in `.env` to send real email.

## Tests

```bash
php artisan test
```

Covers fee calculation maths, role/location scoping on every module, order stock handling, document upload rules, API auth, and the overdue-royalty command. See `docs/TESTING.md`.

## Project layout

```
app/Models/                 Eloquent models (User has role + location/region scope)
app/Services/               RoyaltyCalculator, ReportService
app/Http/Controllers/       Web controllers; Api/ for the REST API
app/Http/Middleware/        EnsureRole  (role:franchisor,regional_manager)
app/Console/Commands/       royalties:mark-overdue
app/Notifications/          RoyaltyOverdue (mail)
database/migrations/        Schema; database/seeders/ demo data
resources/views/            Blade pages (Vue used for calculator + order form)
routes/web.php, api.php     Routes
tests/                      Unit + Feature tests
docs/                       Architecture, API, user guide, testing, known issues
```

## Documentation

- `docs/ARCHITECTURE.md` — system design, ER diagram, role matrix
- `docs/API.md` — REST endpoints
- `docs/USER_GUIDE.md` — how each role uses the app
- `docs/TESTING.md` — what's tested and how to run it
- `docs/KNOWN_ISSUES.md` — limitations and scope notes
