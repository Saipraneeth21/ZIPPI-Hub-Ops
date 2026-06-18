#!/usr/bin/env sh
set -e

# Render injects RENDER_EXTERNAL_URL with the public URL of this service.
# Use it as APP_URL so signed links (e.g. KYC document URLs) resolve correctly.
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

# These need the runtime environment (DB creds, APP_KEY), so they run here
# rather than during the image build.
php artisan package:discover --ansi || true
php artisan filament:assets || true
php artisan storage:link || true

# Apply the database schema. Safe to run on every boot (no-op once applied).
php artisan migrate --force

# Serve on the port Render expects.
exec php artisan serve --host 0.0.0.0 --port "${PORT:-10000}"
