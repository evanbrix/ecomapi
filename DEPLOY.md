# Shared Hosting Deployment

This API is configured to run on standard shared hosting (cPanel / Hostinger / Namecheap / etc.) without SSH composer access and without `storage:link`.

## Before you upload

1. **On your local machine**, install production dependencies and cache config:

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

   You will upload the `vendor/` folder along with the code — most shared hosts can't run composer.

2. **Create a MySQL database** in your hosting control panel and note the credentials. SQLite is not recommended on shared hosting.

3. **Edit `.env`** (copy from `.env.example`):

   ```env
   APP_ENV=production
   APP_KEY=                 # leave blank, fill after upload via cron/SSH
   APP_DEBUG=false
   APP_URL=https://yourdomain.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_DATABASE=yourcpaneluser_ecomapi
   DB_USERNAME=yourcpaneluser_dbuser
   DB_PASSWORD=...

   SESSION_SECURE_COOKIE=true
   CORS_ALLOWED_ORIGINS=https://your-frontend.com
   SANCTUM_TOKEN_EXPIRATION=10080

   ADMIN_NAME=Admin
   ADMIN_EMAIL=you@yourdomain.com
   ADMIN_PASSWORD=<at-least-12-characters>
   ```

## Upload layout

You have two choices depending on your host.

### Option A — Document root pointed at `public/` (preferred)

Many cPanel hosts let you set the domain's document root via **Domains → Manage → Document Root**.

1. Upload the **entire project** to `/home/youruser/ecomapi/` (above `public_html`).
2. Point the domain document root at `/home/youruser/ecomapi/public`.
3. That's it — no path edits needed.

### Option B — Forced to use `public_html/`

If your host won't let you change the document root:

1. Upload the project to `/home/youruser/ecomapi/`.
2. Copy the contents of `ecomapi/public/` into `public_html/`.
3. Open `public_html/index.php` and change these two lines:

   ```php
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';
   ```

   to:

   ```php
   require __DIR__.'/../ecomapi/vendor/autoload.php';
   $app = require_once __DIR__.'/../ecomapi/bootstrap/app.php';
   ```

4. Make sure `public_html/uploads/` exists and is writable (copy from `ecomapi/public/uploads/`).

## File permissions

After upload, set writable directories (via cPanel File Manager or SSH):

```
chmod -R 775 storage bootstrap/cache public/uploads
```

The web user (usually `nobody`, `www-data`, or your cPanel username) must own these.

## Run once after upload

Use cPanel's **Terminal** if available, or set a **one-shot cron job**:

```bash
cd ~/ecomapi
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
```

`db:seed` will only create the admin if `ADMIN_EMAIL` and `ADMIN_PASSWORD` are set in `.env`.

If your host has **no terminal at all**, temporarily add a one-shot setup route to `routes/api.php` (NOT `routes/web.php` — the web middleware stack reads the `sessions` table before your route fires, which doesn't exist yet):

```php
use Illuminate\Support\Facades\Artisan;

Route::get('_setup_abc123', function () {
    Artisan::call('key:generate', ['--force' => true]);
    Artisan::call('migrate', ['--force' => true]);
    Artisan::call('db:seed', ['--force' => true]);
    return response()->json(['status' => 'OK', 'output' => Artisan::output()]);
});
```

Hit `https://yourdomain.com/api/_setup_abc123` once in your browser, then **delete the route** immediately and redeploy.

## Verify

```
GET  https://yourdomain.com/api/settings        → 200, JSON
GET  https://yourdomain.com/api/sliders         → 200, JSON
POST https://yourdomain.com/api/admin/login     → token
```

## Things that work without changes on shared hosting

- Sessions, cache, queue all use the database (no Redis required).
- Uploads write to `public/uploads/` directly — **no `storage:link` symlink needed**.
- Rate limiting and Sanctum token expiry use the same database.
- `public/uploads/.htaccess` blocks PHP/script execution inside the uploads folder.

## When to move off shared hosting

- Traffic exceeds ~50 req/s sustained.
- You need a queue worker (currently nothing queues — image processing is synchronous).
- You need horizontal scaling: at that point set `FILESYSTEM_DISK=s3` and fill in the `AWS_*` keys — no code changes needed beyond that.
