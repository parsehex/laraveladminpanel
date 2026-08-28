---
paths:
  - 'tests/**'
---

# Tests

## Tests must use laravel_admin_testing only
PHPUnit is configured via phpunit.xml and .env.testing to use DB_DATABASE=laravel_admin_testing. Never run RefreshDatabase tests against laravel_admin (dev). TestCase fails fast if the database name does not end with _testing. First-time setup: composer test:db:setup (clones dev into the test DB).
