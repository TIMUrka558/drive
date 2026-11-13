# BeeWin Casino

PHP-based online casino platform with games (Dice, Mines, Bubbles, Wheel, Slots, etc.) redesigned in BeeWin16.org style.

## Run & Operate

- `bash /home/runner/workspace/start.sh` — starts MySQL + PHP server on port 3000 (managed by "PHP Casino Server" workflow)
- MySQL: socket at `/home/runner/workspace/mysql-run/mysql.sock`, DB: `beezename`, user: `beezeuser`
- Site root: `php-project/` — all game logic, templates, CSS here
- PHP version: 8.2 (with MySQLi)

## Stack

- **PHP 8.2** (via Nix `php-8.2` module)
- **MySQL 8.0** (via Nix `mysql80` package)
- **Node.js API server** (separate artifact at `/api`)
- **Custom BeeWin theme CSS**: `php-project/css/beewin-theme.css`

## Where Things Live

- `php-project/` — entire PHP casino project
- `php-project/inc/bd.php` — DB connection (uses MySQLi)
- `php-project/inc/mysql_compat.php` — mysql_* → mysqli_* compatibility shim
- `php-project/inc/site_config.php` — site settings loaded from `kot_config` table
- `php-project/components/header.php` — global header (includes new theme CSS)
- `php-project/components/footer.php` — global footer (includes mobile bottom nav)
- `php-project/css/beewin-theme.css` — full BeeWin-style redesign CSS
- `php-project/router.php` — PHP built-in server router (handles URL rewriting like Apache .htaccess)
- `start.sh` — startup script: initializes MySQL, imports schema, starts PHP
- `php-project/dizmaname1.sql` — database schema + initial data

## Architecture Decisions

- **mysql_* shim**: Rather than rewriting all PHP files, a compatibility shim (`inc/mysql_compat.php`) wraps the removed `mysql_*` functions to call `mysqli_*`. Only `inc/bd.php` was updated to use a real MySQLi connection. All game logic files are untouched.
- **Router**: PHP built-in server (`php -S`) with a custom `router.php` that sets `chdir()` to project root so all relative `require()` paths work correctly.
- **MySQL via Nix**: MySQL 8.0 installed via `installSystemDependencies(["mysql80"])`, data directory at `/home/runner/workspace/mysql-data`.
- **BeeWin theme**: CSS-only redesign layered over original styles, loaded last in header.php. Mobile bottom navigation injected in footer.php.
- **Error suppression**: `error_reporting(E_ERROR | E_PARSE)` in router.php — suppresses PHP 8 notices/warnings from the legacy codebase (undefined array keys etc.).

## User Preferences

- Do NOT change PHP game logic — only css/design and minimal db-layer code changes allowed
- Design must match beewin16.org style (light gray bg, blue accents, clean cards)
- Mobile-optimized with bottom tab navigation

## Gotchas

- MySQL must be started before PHP server — `start.sh` handles this in sequence
- MySQL socket is at `/home/runner/workspace/mysql-run/mysql.sock` (not `/tmp/mysql.sock`)
- The `inc/site_config.php` calls `session_start()` — suppress duplicate-session warnings with error_reporting
- PHP built-in server is single-threaded — not for production; use Apache/Nginx + PHP-FPM for production
- The `function.php` uses deprecated `mcrypt_*` functions — they are removed in PHP 8; if those code paths are hit they will error. The game logic doesn't seem to call them directly.

## Pointers

- New CSS theme: `php-project/css/beewin-theme.css`
- DB schema: `php-project/dizmaname1.sql`
- Startup: `start.sh`
