<?php
// WGC Callback wallet: getBalance / writeBet / rollback (seamless, RUB)
require __DIR__ . '/inc/wgc.php';
require_once __DIR__ . '/inc/rtp.php';
ini_set('serialize_precision', '-1');
header('Content-Type: application/json');

function wreply(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}
function micros($v): ?int {
    if (is_int($v)) $v = (string)$v;
    elseif (is_float($v)) $v = number_format($v, 6, '.', '');
    if (!is_string($v) || !preg_match('/^(\d{1,12})(?:\.(\d+))?$/', trim($v), $m)) return null;
    $frac = str_pad($m[2] ?? '', 7, '0');
    return (int)$m[1] * 1000000 + (int)substr($frac, 0, 6) + ((int)$frac[6] >= 5 ? 1 : 0);
}
function money(int $micros): float { return round(max(0, $micros) / 1000000, 6); }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') wreply(404, ['ok' => false, 'error' => 'not_found']);
$secret = wgc_secret();
if ($secret === '') wreply(500, ['ok' => false, 'error' => 'server_misconfigured']);

$raw = (string)file_get_contents('php://input');
$sig = strtolower(trim((string)($_SERVER['HTTP_X_WGC_SIGNATURE'] ?? '')));
if ($sig === '' || strlen($raw) > 1000000 || !hash_equals(hash_hmac('sha256', $raw, $secret), $sig)) {
    wreply(401, ['ok' => false, 'error' => 'invalid_signature']);
}
$b = json_decode($raw, true, 64);
if (!is_array($b)) wreply(400, ['ok' => false, 'error' => 'invalid_request']);

$cmd = $b['command'] ?? '';
if (!in_array($cmd, ['getBalance', 'writeBet', 'rollback'], true)) wreply(404, ['ok' => false, 'error' => 'unknown_command']);
$pid = (string)($b['playerId'] ?? '');
$cur = (string)($b['currency'] ?? '');
$txId = (string)($b['transactionId'] ?? '');
if ($pid === '' || $cur === '' || ($cmd !== 'getBalance' && $txId === '')) wreply(400, ['ok' => false, 'error' => 'invalid_request']);
$isTest = (($b['test'] ?? false) === true) || (($_SERVER['HTTP_X_WGC_TEST'] ?? '') === '1');

$db = $GLOBALS['__mysql_conn'];
$db->query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
$uid = $isTest ? 0 : wgc_uid($pid);

// Balance in micros; real players use kot_user.balance, test calls a separate sandbox wallet.
$lockBalance = function () use ($db, $isTest, $pid, $uid): ?array {
    if ($isTest) {
        $st = $db->prepare("INSERT IGNORE INTO wgc_test_wallet (player_id, balance) VALUES (?, 1000000000)");
        $st->bind_param('s', $pid); $st->execute();
        $st = $db->prepare("SELECT balance FROM wgc_test_wallet WHERE player_id=? FOR UPDATE");
        $st->bind_param('s', $pid); $st->execute();
        $r = $st->get_result()->fetch_assoc();
        return ['bal' => (int)$r['balance'], 'ban' => false];
    }
    $st = $db->prepare("SELECT balance, ban FROM kot_user WHERE id=? FOR UPDATE");
    $st->bind_param('i', $uid); $st->execute();
    $r = $st->get_result()->fetch_assoc();
    if (!$r) return null;
    return ['bal' => micros(number_format(max(0, (float)$r['balance']), 6, '.', '')) ?? 0, 'ban' => (string)$r['ban'] === '1'];
};
$saveBalance = function (int $bal) use ($db, $isTest, $pid, $uid): void {
    if ($isTest) {
        $st = $db->prepare("UPDATE wgc_test_wallet SET balance=? WHERE player_id=?");
        $st->bind_param('is', $bal, $pid);
    } else {
        $s = rtrim(rtrim(number_format($bal / 1000000, 6, '.', ''), '0'), '.');
        $st = $db->prepare("UPDATE kot_user SET balance=? WHERE id=?");
        $st->bind_param('si', $s, $uid);
    }
    $st->execute();
};

$track = null;
$db->begin_transaction();
try {
    $w = $lockBalance();
    if ($w === null) { $db->rollback(); wreply(200, ['ok' => false, 'error' => 'player_blocked']); }
    if (!$isTest && $cur !== WGC_CURRENCY) { $db->rollback(); wreply(200, ['ok' => false, 'error' => 'currency_not_supported', 'balance' => money($w['bal'])]); }

    if ($cmd === 'getBalance') {
        $ans = ['ok' => true, 'balance' => money($w['bal']), 'currency' => $cur];
    } elseif ($cmd === 'writeBet') {
        $bet = array_key_exists('bet', $b) && $b['bet'] !== null ? micros($b['bet']) : null;
        $win = array_key_exists('win', $b) && $b['win'] !== null ? micros($b['win']) : null;
        if ($bet === null || $win === null) { $db->rollback(); wreply(400, ['ok' => false, 'error' => 'invalid_request']); }
        $kind = (string)($b['kind'] ?? 'play');
        $st = $db->prepare("SELECT answer FROM wgc_tx WHERE tx_id=? FOR UPDATE");
        $st->bind_param('s', $txId); $st->execute();
        $seen = $st->get_result()->fetch_assoc();
        if ($seen && $seen['answer'] !== null) {
            $ans = json_decode($seen['answer'], true);
        } elseif ($seen) {
            $ans = ['ok' => false, 'error' => 'transaction_rolled_back', 'balance' => money($w['bal'])];
        } else {
            $bal = $w['bal'];
            if (!in_array($kind, ['play', 'refund', 'jackpot'], true)) $ans = ['ok' => false, 'error' => 'kind_not_supported', 'balance' => money($bal)];
            elseif ($w['ban'] && $bet > 0) $ans = ['ok' => false, 'error' => 'player_blocked', 'balance' => money($bal)];
            elseif ($bal < $bet) $ans = ['ok' => false, 'error' => 'insufficient_funds', 'balance' => money($bal)];
            else {
                $bal = $bal - $bet + $win;
                $saveBalance($bal);
                $ans = ['ok' => true, 'balance' => money($bal)];
                if (!$isTest && $bet > 0) $track = [$uid, $bet / 1000000, $win / 1000000];
            }
            $ok = $ans['ok'] === true;
            $bk = $ok ? $bet : 0; $wk = $ok ? $win : 0; $booked = $ok ? 1 : 0; $t = $isTest ? 1 : 0;
            $round = (string)($b['roundId'] ?? ''); $game = (string)($b['gamePublicId'] ?? '');
            $aj = json_encode($ans);
            $st = $db->prepare("INSERT INTO wgc_tx (tx_id, user_id, player_id, round_id, game, kind, currency, bet, win, booked, rolled_back, is_test, answer, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,0,?,?,NOW())");
            $st->bind_param('sisssssiiiis', $txId, $uid, $pid, $round, $game, $kind, $cur, $bk, $wk, $booked, $t, $aj);
            $st->execute();
        }
    } else {
        $st = $db->prepare("SELECT bet, win, booked, rolled_back FROM wgc_tx WHERE tx_id=? FOR UPDATE");
        $st->bind_param('s', $txId); $st->execute();
        $tx = $st->get_result()->fetch_assoc();
        $bal = $w['bal'];
        if ($tx && (int)$tx['booked'] === 1 && (int)$tx['rolled_back'] === 0) {
            $bal = max(0, $bal + (int)$tx['bet'] - (int)$tx['win']);
            $saveBalance($bal);
            $st = $db->prepare("UPDATE wgc_tx SET rolled_back=1, rolled_back_at=NOW() WHERE tx_id=?");
            $st->bind_param('s', $txId); $st->execute();
        } elseif (!$tx) {
            $t = $isTest ? 1 : 0; $kind = 'rollback';
            $st = $db->prepare("INSERT INTO wgc_tx (tx_id, user_id, player_id, round_id, game, kind, currency, bet, win, booked, rolled_back, is_test, answer, created_at, rolled_back_at) VALUES (?,?,?,'','',?,?,0,0,0,1,?,NULL,NOW(),NOW())");
            $st->bind_param('sisssi', $txId, $uid, $pid, $kind, $cur, $t); $st->execute();
        }
        $ans = ['ok' => true, 'balance' => money($bal)];
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    error_log('wgc_wallet error: ' . $e->getMessage());
    wreply(500, ['ok' => false, 'error' => 'server_error']);
}

if ($track) {
    // Wager progress after the money is booked; never affects the answer.
    try { bwTrackBet($track[0], 'slots', $track[1], $track[2]); } catch (Throwable $e) {}
}
wreply(200, $ans);
