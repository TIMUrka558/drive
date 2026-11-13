<?php
/**
 * FastWin — idempotent DB migration runner
 * Run once via CLI: php admin/migrations.php
 * Or via browser as admin: /admin/migrations
 *
 * Each migration is a pure ALTER TABLE that succeeds on fresh DBs
 * and is safe to re-run (duplicates caught and skipped).
 */

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    session_start();
}

require __DIR__ . '/../inc/mysql_compat.php';
require __DIR__ . '/../inc/bd.php';

// Browser access requires: admin session + POST method + valid CSRF token
if (!$isCli) {
    // Only allow POST — prevents CSRF via a simple GET/img request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die('Method Not Allowed — use POST with X-CSRF-Token header');
    }
    $sid = $_SESSION['hash'] ?? '';
    $ar = mysqli_fetch_assoc(mysqli_query($__conn,
        "SELECT admin FROM kot_user WHERE hash='" . mysqli_real_escape_string($__conn, $sid) . "'"));
    if (!$ar || (int)$ar['admin'] !== 1) {
        http_response_code(403);
        die('Forbidden');
    }
    // CSRF check
    $csrf_header  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $csrf_session = $_SESSION['admin_csrf'] ?? '';
    if (!$csrf_session || !hash_equals($csrf_session, $csrf_header)) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
    header('Content-Type: text/plain');
}

function run_migration(mysqli $conn, string $label, string $table, string $column, string $definition): void {
    // Check if column already exists
    $check = mysqli_query($conn,
        "SELECT COUNT(*) c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table' AND COLUMN_NAME='$column'"
    );
    $row = mysqli_fetch_assoc($check);
    if ((int)$row['c'] > 0) {
        echo "[SKIP] $label — already exists\n";
        return;
    }
    $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
    if (mysqli_query($conn, $sql)) {
        echo "[OK]   $label\n";
    } else {
        echo "[ERR]  $label — ".mysqli_error($conn)."\n";
    }
}

$migrations = [
    // kot_user
    ['kot_user.ref_balance',   'kot_user',   'ref_balance',   'DECIMAL(10,2) NOT NULL DEFAULT 0'],
    ['kot_user.cashback_pct',  'kot_user',   'cashback_pct',  'DECIMAL(5,2)  NOT NULL DEFAULT 0'],
    ['kot_user.wager_required','kot_user',   'wager_required','DECIMAL(12,2) NOT NULL DEFAULT 0'],
    ['kot_user.wager_progress','kot_user',   'wager_progress','DECIMAL(12,2) NOT NULL DEFAULT 0'],

    // kot_config
    ['kot_config.cashback_pct',        'kot_config','cashback_pct',        'DECIMAL(5,2) NOT NULL DEFAULT 5'],
    ['kot_config.ref_pct',             'kot_config','ref_pct',             'DECIMAL(5,2) NOT NULL DEFAULT 5'],
    ['kot_config.antim_dice',          'kot_config','antim_dice',          'TINYINT NOT NULL DEFAULT 0'],
    ['kot_config.antim_mines',         'kot_config','antim_mines',         'TINYINT NOT NULL DEFAULT 0'],
    ['kot_config.antim_bubbles',       'kot_config','antim_bubbles',       'TINYINT NOT NULL DEFAULT 0'],
    ['kot_config.antim_x50',           'kot_config','antim_x50',           'TINYINT NOT NULL DEFAULT 0'],
    ['kot_config.antim_thresh_dice',   'kot_config','antim_thresh_dice',   'DECIMAL(10,2) NOT NULL DEFAULT 1000'],
    ['kot_config.antim_thresh_mines',  'kot_config','antim_thresh_mines',  'DECIMAL(10,2) NOT NULL DEFAULT 1000'],
    ['kot_config.antim_thresh_bubbles','kot_config','antim_thresh_bubbles','DECIMAL(10,2) NOT NULL DEFAULT 1000'],
    ['kot_config.antim_thresh_x50',    'kot_config','antim_thresh_x50',    'DECIMAL(10,2) NOT NULL DEFAULT 1000'],
    ['kot_config.rtp_dice',            'kot_config','rtp_dice',            'DECIMAL(5,2) NOT NULL DEFAULT 97'],
    ['kot_config.rtp_mines',           'kot_config','rtp_mines',           'DECIMAL(5,2) NOT NULL DEFAULT 97'],
    ['kot_config.rtp_bubbles',         'kot_config','rtp_bubbles',         'DECIMAL(5,2) NOT NULL DEFAULT 97'],
    ['kot_config.rtp_x50',             'kot_config','rtp_x50',             'DECIMAL(5,2) NOT NULL DEFAULT 97'],
    ['kot_user.ref_pct',               'kot_user','ref_pct',               'DECIMAL(5,2) NULL DEFAULT NULL'],
    ['kot_config.max_win',             'kot_config','max_win',             'DECIMAL(12,2) NOT NULL DEFAULT 0'],
    ['kot_config.fee_qiwi',            'kot_config','fee_qiwi',            'DECIMAL(5,2) NOT NULL DEFAULT 0'],
    ['kot_config.fee_card',            'kot_config','fee_card',            'DECIMAL(5,2) NOT NULL DEFAULT 0'],
    ['kot_config.fee_crypto',          'kot_config','fee_crypto',          'DECIMAL(5,2) NOT NULL DEFAULT 0'],

    // kot_promo
    ['kot_promo.wager_mult', 'kot_promo','wager_mult', 'INT NOT NULL DEFAULT 1'],
    ['kot_promo.promo_type', 'kot_promo','promo_type', "VARCHAR(20) NOT NULL DEFAULT 'deposit'"],
    ['kot_promo.min_amount', 'kot_promo','min_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0'],
    ['kot_promo.max_amount', 'kot_promo','max_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0'],
    ['kot_promo.min_dep',    'kot_promo','min_dep',    'DECIMAL(12,2) NOT NULL DEFAULT 0'],
    ['kot_promo.expires_at', 'kot_promo','expires_at', "VARCHAR(50) NOT NULL DEFAULT ''"],
];

mysqli_query($__conn, "CREATE TABLE IF NOT EXISTS kot_promo_act (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    promo_id INT NOT NULL,
    code VARCHAR(50) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    wager DECIMAL(12,2) NOT NULL DEFAULT 0,
    status VARCHAR(12) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    KEY user_time (user_id, created_at),
    KEY promo (promo_id)
)");

echo "FastWin DB Migrations\n";
echo str_repeat('-', 40)."\n";
foreach ($migrations as [$label, $table, $column, $def]) {
    run_migration($__conn, $label, $table, $column, $def);
}
echo str_repeat('-', 40)."\n";
echo "Done.\n";
