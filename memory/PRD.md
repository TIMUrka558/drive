# PRD — BeeWin/FastWin PHP casino (migrated from Replit)
## Original request
Download zip from Google Drive, run the user's Replit project as-is (no rewrite), configure everything so it is visible in preview. Payment systems: do not touch. Then: remove all fake online, fake payouts, fake tournaments, everything fake.
## Architecture
- PHP 8.2 built-in server (router.php) on port 3000, started by /app/casino/start-emergent.sh (via /app/frontend/package.json "start")
- MySQL 8.0.46 binary in /app/casino/.runtime/mysql, original Replit datadir /app/casino/mysql-data (auto-upgraded from 8.0.42)
- short_open_tag=On, PHP_CLI_SERVER_WORKERS=4
## Done (2026-06)
- Project downloaded, DB with original data restored, site running in preview
- Removed: fake online (fake_online, rand counters, hardcoded 628), bot fake bets (bots.php, admin bots page), fake live-wins feed, fake tournament page/links/banners, fake payouts page (/history now real completed withdrawals), fake "fairness check" modal with random hashes, fake txt data files
## Backlog
- P1: configure payment providers (user will instruct)
- P2: blank first slot card on home; header balance refresh after bet (original behavior)
- P2: production deploy needs a proper PHP+MySQL host (Emergent deploy targets React/FastAPI)

## Iteration 2 (2026-06)
- RUBIN dark theme (css/rubin-theme.css loaded last), ruby buttons, white text, brand RUBIN + gem logo, desktop games centered (640px column)
- Admin /admin/rtp restored (original): RTP sliders Dice/Mines/Bubbles/X50 + Anti-minus (force win after session-loss threshold) — now wired to all 4 games via inc/rtp.php; plus Max win per bet
- Removed hidden forced-loss code (dice sliv/kot_chance, bubbles 1/500, wheel bank reroll)
- Fixed: Mines inserts failing under MySQL strict mode (sql_mode set in inc/bd.php)

## Iteration 3 (2026-06)
- Admin login page at /admin, mobile-friendly admin (RU nav), Players: balance, manual deposit (+ref reward), personal ref %, inviter
- Referral system unified: inc/rtp.php rb_ref_reward (personal % or global), used by deposits
- Home: banner carousel (4s autoplay, swipe, dots), game history removed from home and games
- Bottom nav: Главная, Игры, Депозит (center), Бонусы, Рефералы; side menu removed
- Cashier /deposit: CryptoBot + xRocket deposits (inc/payments.php, payapi.php, payhook.php webhooks), withdrawals in USDT to Telegram ID, sent on admin approve
- Bonuses: daily random 10–25 ₽ once per 24h, 5% cashback from each deposit (claim button)
- Needs secrets: CRYPTOBOT_TOKEN, XROCKET_TOKEN in /app/backend/.env

## Iteration 4 (2026-11)
- Project restored into new environment: casino/ folder pulled from old env (GitHub repo had it only as an empty gitlink), DB datadir + dump_all.sql (full mysqldump) included
- Site running in preview via /app/casino/start-emergent.sh (PHP 8.2 + MySQL 8.0.46); casino/ now a normal folder (no nested .git), no files >90MB
- Pending: CRYPTOBOT_TOKEN, XROCKET_TOKEN not set yet (user will add later)

## Iteration 5 (2026-11)
- Promo activation rewritten (action.php activePromo): per-promo activation limit (0=unlimited), wager = bonus × wager_mult added to wager_required, random amount range, expiry, max 3 promo activations per player per 24h, log in new table kot_promo_act
- Profile /me: player ID shown, active wager card (progress, promos) + "Отменить бонус и вагер" (payapi cancelWager, allowed when balance < 1 ₽; zeroes wager & balance)
- Withdraw blocked while wager not completed (payapi withdraw + legacy withdrawuser)
- Admin: promo page in Russian with activations log + search; new /admin/find global search by ID (players, deposits, invoices, withdrawals, promo activations, bets); deposits/withdrawals searchable by record ID; player page shows promo activations and bet IDs
