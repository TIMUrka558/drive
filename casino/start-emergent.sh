#!/bin/bash
BASE=/app/casino
MY=$BASE/.runtime/mysql
RUN=$BASE/mysql-run
DATA=$BASE/mysql-data
PHP_ROOT=$BASE/php-project
PORT=3000

if ! command -v php >/dev/null 2>&1 || ! php -m 2>/dev/null | grep -qi "^mysqli$" || ! php -m 2>/dev/null | grep -qi "^mbstring$"; then
  apt-get update -qq && DEBIAN_FRONTEND=noninteractive apt-get install -y -qq php8.2-cli php8.2-mysql php8.2-curl php8.2-mbstring php8.2-gd php8.2-xml php8.2-bcmath libaio1 libnuma1
fi

mkdir -p "$RUN"
if ! $MY/bin/mysqladmin --socket=$RUN/mysql.sock -u root ping --silent 2>/dev/null; then
  rm -f $RUN/mysql.sock $RUN/mysql.sock.lock $RUN/mysql.pid
  $MY/bin/mysqld --no-defaults --basedir=$MY --datadir=$DATA \
    --socket=$RUN/mysql.sock --pid-file=$RUN/mysql.pid \
    --port=3306 --bind-address=127.0.0.1 --mysqlx=OFF --user=root \
    --skip-log-bin --log-error=$RUN/err.log &
  for i in $(seq 1 60); do
    $MY/bin/mysqladmin --socket=$RUN/mysql.sock -u root ping --silent 2>/dev/null && break
    sleep 1
  done
fi

export PHP_CLI_SERVER_WORKERS=4
exec php -d short_open_tag=On -d memory_limit=512M -S "0.0.0.0:$PORT" -t "$PHP_ROOT" "$PHP_ROOT/router.php"
