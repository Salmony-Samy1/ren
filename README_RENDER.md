# Gathro API - Render Deployment and DB Migration

## 1) Prerequisites
- PHP 8.2+ locally, Composer
- MySQL source DB with data (e.g. `d8966_gathro_dev`)
- Render account
- Docker (to run pgloader easily on Windows)

## 2) Create Render PostgreSQL
1. In Render, create a Managed PostgreSQL (e.g. name: `gathro-db`).
2. Copy the connection string (starts with `postgres://` or `postgresql://`).

## 3) Import data from MySQL to Render PostgreSQL (pgloader)
1. Edit `scripts/pgloader.load` and fill in:
   - `from mysql://.../DATABASE`
   - `into postgresql://USER:PASSWORD@HOST:PORT/DB?sslmode=require`
2. Run via Docker:
```powershell
# From project root
# Ensure MySQL is reachable from your machine
# Mount Windows path correctly for Docker

docker run --rm \ 
  -v ${PWD}/scripts:/scripts \ 
  dimitri/pgloader:latest \ 
  pgloader /scripts/pgloader.load
```
Notes:
- If you need pgloader to create tables in PostgreSQL, change the `with` section in the load file to include `including drop, create tables` and remove `data only`.
- The current load file maps MySQL-specific types (enum, json, blob, tinyint) to PostgreSQL-friendly types.

## 4) Configure environment on Render
Use either `render.yaml` (Blueprint) or manual service setup.

If using `render.yaml`:
- Push this repo to GitHub and use Render Blueprints to create both the DB and the Web Service.
- In the Web Service:
  - Set `APP_KEY` (run locally: `php artisan key:generate --show` and paste the value).
  - Ensure `DB_CONNECTION=pgsql` and `DB_URL` is auto-injected from the DB resource.
  - Consider updating `APP_URL` to your final Render URL.

If creating service manually:
- Runtime: PHP
- Build command:
```
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan storage:link || true
```
- Start command:
```
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
php -d variables_order=EGPCS -S 0.0.0.0:$PORT -t public public/index.php
```
- Env vars (minimum):
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://<your-service>.onrender.com`
  - `APP_KEY=<paste from local>`
  - `DB_CONNECTION=pgsql`
  - `DB_URL=<Render connection string>`
  - `SESSION_DRIVER=database` (ensure `sessions` table exists in target DB)
  - `CACHE_STORE=database`
  - `QUEUE_CONNECTION=sync`

## 5) Sessions/Cache tables
- This project uses `SESSION_DRIVER=database`. Ensure `sessions` table exists in the source MySQL before migration:
  - If missing, run locally: `php artisan session:table && php artisan migrate`.
  - Re-run pgloader to copy it to PostgreSQL.
- Cache tables: if using `CACHE_STORE=database`, ensure cache table exists: `php artisan cache:table && php artisan migrate` (then re-run pgloader).

## 6) Migrations on PostgreSQL
- Do not run all migrations on PostgreSQL after import. Some migrations use MySQL-specific SQL (e.g. `MODIFY COLUMN ... ENUM` in `database/migrations/2025_09_27_000114_update_payment_methods_enum_in_payment_transactions_table.php`).
- After import, only run targeted migrations that are known to be PostgreSQL-compatible.

## 7) Post-deploy checks
- Open the service URL and ensure a 200 response on `/` (adjust health check path if your app uses a different index route).
- Check logs for DB connection success.
- Verify critical flows that read/write the DB.

## 8) Filesystem note
- `FILESYSTEM_DISK=local` on Render is ephemeral (resets on deploy). For user uploads, configure S3-compatible storage in production.

## 9) Queues and websockets
- Currently set to `QUEUE_CONNECTION=sync` for simplicity. If you need workers or websockets, create additional Render services and update env vars accordingly.
