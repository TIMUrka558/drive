#!/bin/bash
# Smoke test: page status, PHP errors, tournament/fake markers
U=$(grep REACT_APP_BACKEND_URL /app/frontend/.env | cut -d= -f2)
for p in / /dice /mines /bubbles /x50 /x100wheel /slot /live /bonus /ref /faq /about /history /ranks /me /deposit /tournament /bots.php; do
  body=$(curl -s -L -o /tmp/pg.html -w "%{http_code} %{url_effective}" "$U$p")
  sz=$(wc -c </tmp/pg.html)
  err=$(grep -oiE "(Fatal error|Parse error|Warning:|Notice:|Uncaught)[^<]{0,80}" /tmp/pg.html | head -2 | tr '\n' ' ')
  tour=$(grep -oiE "турнир[а-яё]*" /tmp/pg.html | sort -u | tr '\n' ' ')
  fair=$(grep -oiE "проверка на честность" /tmp/pg.html | head -1)
  echo "$p -> $body size=$sz err=[$err] tour=[$tour] fair=[$fair]"
done
echo "bots.php:"; curl -s "$U/bots.php"; echo
