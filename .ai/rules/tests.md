---
paths:
  - 'tests/**'
---

# Tests

## Tests must use laravel_admin_testing only
PHPUnit is configured via phpunit.xml and .env.testing to use DB_DATABASE=laravel_admin_testing. Never run RefreshDatabase tests against laravel_admin (dev). TestCase bootstraps the app and fails fast unless `config('database.connections.*.database')` ends with `_testing` — not only `$_ENV['DB_DATABASE']`, so a cached config cannot silently point tests at dev. Tests also refuse to run while configuration is cached; run `php artisan config:clear` first. First-time setup: composer test:db:setup (clones dev into the test DB).
