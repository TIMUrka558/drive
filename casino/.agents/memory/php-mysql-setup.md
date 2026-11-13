---
name: PHP+MySQL setup on Replit
description: How to run a legacy PHP+MySQL project on Replit NixOS using Nix packages and a compatibility shim for mysql_* functions.
---

# PHP + MySQL on Replit

## Installation
- PHP 8.2: `installProgrammingLanguage({ language: "php-8.2" })` → binary at `/nix/store/.../bin/php`
- MySQL 8.0: `installSystemDependencies({ packages: ["mysql80"] })` → `mysqld`, `mysql`, `mysqladmin` available

## MySQL initialization
```bash
mysqld --initialize-insecure --datadir=/home/runner/workspace/mysql-data --user=runner
mysqld --datadir=... --socket=.../mysql.sock --port=3306 --user=runner --skip-log-bin --daemonize=ON
```

## mysql_* → mysqli_* shim
Create `inc/mysql_compat.php` that defines the removed `mysql_connect`, `mysql_query`, etc. as wrappers around `mysqli_*` using a global `$GLOBALS['__mysql_conn']`. Then update only `inc/bd.php` to create the real `mysqli` connection and store it in `$GLOBALS['__mysql_conn']`. All other files stay untouched.

**Why:** Avoids modifying 30+ game files; only the DB layer needs to change.

**How to apply:** When a legacy PHP project uses `mysql_*` on PHP 8+, use this shim pattern first. Only move to full rewrite if the shim causes issues.

## Error suppression
Legacy code generates many PHP 8 notices. Add to router.php:
```php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
```
