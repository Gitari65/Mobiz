# SQLite Setup (local & test)

Local dev (persistent file)
1. Create sqlite file:
   composer db:init
2. In your local `.env` set:
   DB_CONNECTION=sqlite
   DB_DATABASE=database/database.sqlite
3. Run migrations:
   php artisan migrate
4. Start server:
   php artisan serve

Testing (in-memory)
- PHPUnit uses `.env.testing` (in-memory SQLite).
- Recommended safe command (ensures `APP_ENV=testing`): 
  - `composer test:safe`
  - or `php artisan test --env=testing`
- The test runner now aborts if it detects a non-SQLite DB (prevents accidental runs against local MySQL).

Notes:
- Ensure PHP `pdo_sqlite` extension is enabled.
- MAIL_MAILER=log prevents sending emails during tests.
