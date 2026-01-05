# Migration Safety & Recovery

Immediate recovery checklist (if MySQL data was lost)
- Stop writing to the DB immediately (maintenance mode: `php artisan down`) to avoid further damage.
- Restore from the most recent backup/dump:
  - If you have a dump file: `mysql -u USER -p DATABASE < /path/to/dump.sql`
  - If hosted, check provider snapshots/backups and restore via dashboard or request support.
  - If binary logs (binlog) are available, perform point-in-time recovery from binlogs.
- If no backups exist, contact your host/DBA — recovery may still be possible via snapshots or logs.

Prevention & best-practices
- Tests should run on SQLite (in-memory) via `.env.testing` or `phpunit.xml`. Do not run test suites against production DB.
- Never run `php artisan migrate:fresh` / `migrate:refresh` / `db:wipe` on production.
- Use the built-in guard (AppServiceProvider) to block destructive artisan commands in production (already added).
- Always take a DB dump before running risky operations:
  - `mysqldump -u USER -p DATABASE > backup-$(date +%F_%T).sql`
- Use a backup/restore policy and test restores regularly.

If you want, I can:
- Add an artisan command to create automated backups before running migrations,
- Add CI checks to ensure migration commands targeting production are disabled,
- Walk you through restoring from a specific backup (if you provide the file or storage location).
