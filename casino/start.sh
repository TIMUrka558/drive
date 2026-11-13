#!/bin/bash
set -e

MYSQL_DATA="/home/runner/workspace/mysql-data"
MYSQL_RUN="/home/runner/workspace/mysql-run"
PHP_ROOT="/home/runner/workspace/php-project"
PORT="${PORT:-3000}"

mkdir -p "$MYSQL_RUN"

# Start MySQL if not already running
if ! mysqladmin --socket="$MYSQL_RUN/mysql.sock" ping --silent 2>/dev/null; then
  echo "[start] Launching MySQL..."
  mysqld \
    --datadir="$MYSQL_DATA" \
    --pid-file="$MYSQL_RUN/mysql.pid" \
    --socket="$MYSQL_RUN/mysql.sock" \
    --port=3306 \
    --user=runner \
    --skip-log-bin \
    --general-log=0 \
    --slow-query-log=0 \
    --daemonize=ON 2>&1
  # Wait for MySQL to be ready
  for i in $(seq 1 30); do
    if mysqladmin --socket="$MYSQL_RUN/mysql.sock" ping --silent 2>/dev/null; then
      echo "[start] MySQL ready."
      break
    fi
    sleep 1
  done
fi

# Create DB + user if not exists
mysql --socket="$MYSQL_RUN/mysql.sock" -u root --skip-password 2>/dev/null <<'SQL'
CREATE DATABASE IF NOT EXISTS beezename CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'beezeuser'@'localhost' IDENTIFIED BY 'beezepass123';
CREATE USER IF NOT EXISTS 'beezeuser'@'127.0.0.1' IDENTIFIED BY 'beezepass123';
GRANT ALL PRIVILEGES ON beezename.* TO 'beezeuser'@'localhost';
GRANT ALL PRIVILEGES ON beezename.* TO 'beezeuser'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

# Import schema if tables don't exist
TABLE_COUNT=$(mysql --socket="$MYSQL_RUN/mysql.sock" -u root --skip-password beezename -sNe "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='beezename'" 2>/dev/null)
if [ "${TABLE_COUNT:-0}" -lt "5" ]; then
  echo "[start] Importing SQL schema..."
  mysql --socket="$MYSQL_RUN/mysql.sock" -u root --skip-password beezename < "$PHP_ROOT/dizmaname1.sql" 2>/dev/null || true
  echo "[start] Schema imported."
fi

echo "[start] Starting PHP on port $PORT..."
exec php -S "0.0.0.0:$PORT" -t "$PHP_ROOT" "$PHP_ROOT/router.php"
