graz.news
=========

News aggregator for Graz, Austria. Cron-driven; the homepage is read-only.

## Layout

- `index.php` — renders the latest 27 articles from sqlite.
- `fetch.php` — cron entry point. Walks `news-sites.csv`, honours
  robots.txt per site (24h cache), parses each feed via SimplePie,
  inserts new articles.
- `database.inc.php` — schema, idempotent migrations, prepared statements.
  Loaded by both `index.php` and `fetch.php`.
- `news-sites.csv` — feed config. Columns: `title,site,feed,robots,filter`.
  `filter=TRUE` keeps only items mentioning "graz" (case-insensitive);
  `filter=FALSE` keeps all items.
- `theme/`, `functions.inc.php` — Bootstrap theme + template helpers.
- `cache/`, `database.sqlite` — runtime data, gitignored.

## Local development

Needs PHP ≥ 8.1, ext-pdo_sqlite, ext-simplexml.

```bash
composer install
php -S localhost:8000
php fetch.php
```

## Deployment

Tested target is PHP 8.4 shared hosting with SSH/SFTP and no shell
composer. Cron runs `php fetch.php` every 30 min (configure in your
host's panel or `crontab`).

1. **Local prep**

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. **Backup** the live `database.sqlite` and `cache/`.

3. **Rsync to webroot** (dry-run first):

   ```bash
   rsync -avh --delete-after \
     --exclude='.git/' --exclude='.gitignore' \
     --exclude='database.sqlite' --exclude='cache/*' \
     --exclude='.htaccess' \
     --exclude='Vagrantfile' --exclude='.vagrant' \
     --exclude='README.md' --exclude='.claude/' \
     ./ <host>:<webroot>/
   ```

   `--delete-after` removes vendor files left over from earlier
   dependency versions. Excludes preserve runtime state.

4. **Force pending migrations from CLI** (web requests time out at 30 s):

   ```bash
   ssh <host> 'cd <webroot> && php -r "require '\''database.inc.php'\'';"'
   ```

5. **Prime articles** instead of waiting for cron:

   ```bash
   ssh <host> 'cd <webroot> && php fetch.php'
   ```

6. **Verify**: `curl -sI https://yoursite/ | head -1`

7. **VACUUM** after large migrations to reclaim disk:

   ```bash
   ssh <host> 'cd <webroot> && sqlite3 database.sqlite VACUUM;'
   ```

   Locks the DB; run between cron slots.
