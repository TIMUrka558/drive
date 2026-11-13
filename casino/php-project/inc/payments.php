<?php
// RUBIN crypto payments: CryptoBot (Crypto Pay API) + xRocket (Legacy Pay API)
require_once __DIR__ . '/rtp.php';

function rb_env($k) {
    $v = getenv($k);
    if ($v !== false && $v !== '') return $v;
    static $env = null;
    if ($env === null) {
        $env = [];
        $f = '/app/backend/.env';
        if (is_readable($f)) {
            foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if ($line[0] === '#' || strpos($line, '=') === false) continue;
                [$a, $b] = explode('=', $line, 2);
                $env[trim($a)] = trim(trim($b), "\"'");
            }
        }
    }
    return $env[$k] ?? '';
}

function rb_pay_cfg() {
    $c = mysql_fetch_array(mysql_query("SELECT usdt_rub, min_sum_dep, min_withdraw_sum, cashback_pct FROM kot_config LIMIT 1"));
    return [
        'rate' => max(1, (float)($c['usdt_rub'] ?? 95)),
        'min_dep' => (float)($c['min_sum_dep'] ?? 100),
        'min_wd' => (float)($c['min_withdraw_sum'] ?? 500),
        'cashback' => (float)($c['cashback_pct'] ?? 5),
    ];
}

function rb_providers() {
    return [
        'cryptobot' => rb_env('CRYPTOBOT_TOKEN') !== '',
        'xrocket' => rb_env('XROCKET_TOKEN') !== '',
    ];
}

function rb_http($url, $headers, $method = 'POST', $body = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers),
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) throw new RuntimeException('Network error: ' . $err);
    $out = json_decode($raw, true);
    if (!is_array($out)) throw new RuntimeException('Invalid response');
    return $out;
}

// ── CryptoBot ────────────────────────────────────────────────────────────────
function rb_cb($method, $params = []) {
    $base = rb_env('CRYPTOBOT_TESTNET') === '1' ? 'https://testnet-pay.crypt.bot/api/' : 'https://pay.crypt.bot/api/';
    $out = rb_http($base . $method, ['Crypto-Pay-API-Token: ' . rb_env('CRYPTOBOT_TOKEN')], 'POST', $params);
    if (($out['ok'] ?? false) !== true) {
        $e = $out['error'] ?? [];
        throw new RuntimeException('CryptoBot: ' . (is_array($e) ? ($e['name'] ?? json_encode($e)) : $e));
    }
    return $out['result'];
}

// ── xRocket ──────────────────────────────────────────────────────────────────
function rb_xr($method, $path, $body = null) {
    $base = rtrim(rb_env('XROCKET_BASE') ?: 'https://pay.xrocket.tg', '/');
    $out = rb_http($base . $path, ['Rocket-Pay-Key: ' . rb_env('XROCKET_TOKEN')], $method, $body);
    if (($out['success'] ?? false) !== true) {
        $m = $out['message'] ?? 'request failed';
        if (!empty($out['errors'])) $m .= ' ' . json_encode($out['errors'], JSON_UNESCAPED_UNICODE);
        throw new RuntimeException('xRocket: ' . $m);
    }
    return $out['data'] ?? [];
}

function rb_site_url() {
    $proto = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : 'https');
    return $proto . '://' . ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost');
}

// ── Deposits ─────────────────────────────────────────────────────────────────
function rb_create_deposit($uid, $provider, $rub) {
    $uid = (int)$uid; $rub = round((float)$rub, 2);
    $cfg = rb_pay_cfg();
    $now = date('Y-m-d H:i:s');
    mysql_query("INSERT INTO rb_invoices (user_id, provider, amount_rub, status, created_at) VALUES ('$uid', '$provider', '$rub', 'pending', '$now')");
    $lid = mysql_insert_id();
    $back = rb_site_url() . '/deposit';
    if ($provider === 'cryptobot') {
        $r = rb_cb('createInvoice', [
            'currency_type' => 'fiat', 'fiat' => 'RUB', 'amount' => number_format($rub, 2, '.', ''),
            'accepted_assets' => 'USDT,TON,BTC,LTC,TRX',
            'description' => 'Пополнение баланса RUBIN #' . $lid,
            'payload' => (string)$lid, 'expires_in' => 3600,
            'paid_btn_name' => 'callback', 'paid_btn_url' => $back,
        ]);
        $ext = (string)$r['invoice_id'];
        $url = $r['bot_invoice_url'] ?? ($r['pay_url'] ?? '');
        $crypto = 'NULL'; $asset = 'RUB';
    } else {
        $usdt = floor($rub / $cfg['rate'] * 100) / 100;
        if ($usdt < 0.01) throw new RuntimeException('Слишком маленькая сумма');
        $r = rb_xr('POST', '/tg-invoices', [
            'amount' => $usdt, 'currency' => 'USDT', 'numPayments' => 1,
            'description' => 'Пополнение баланса RUBIN #' . $lid, 'payload' => (string)$lid,
            'callbackUrl' => $back, 'expiredIn' => 3600,
        ]);
        $ext = (string)$r['id'];
        $url = $r['link'] ?? '';
        $crypto = "'$usdt'"; $asset = 'USDT';
    }
    $ext_e = mysql_real_escape_string($ext); $url_e = mysql_real_escape_string($url);
    mysql_query("UPDATE rb_invoices SET ext_id='$ext_e', pay_url='$url_e', amount_crypto=$crypto, asset='$asset' WHERE id='$lid'");
    return ['id' => $lid, 'url' => $url];
}

// Idempotent: credits balance + referral + cashback exactly once
function rb_credit_invoice($lid) {
    $lid = (int)$lid; $now = date('Y-m-d H:i:s');
    mysql_query("UPDATE rb_invoices SET status='paid', paid_at='$now' WHERE id='$lid' AND status='pending'");
    if (mysql_affected_rows() < 1) return false;
    $inv = mysql_fetch_array(mysql_query("SELECT * FROM rb_invoices WHERE id='$lid'"));
    $uid = (int)$inv['user_id']; $rub = (float)$inv['amount_rub'];
    $cfg = rb_pay_cfg();
    $cb = round($rub * $cfg['cashback'] / 100, 2);
    mysql_query("UPDATE kot_user SET balance = balance + '$rub', cashback = cashback + '$cb' WHERE id='$uid'");
    $tx = mysql_real_escape_string($inv['provider'] . ':' . $inv['ext_id']);
    mysql_query("INSERT INTO kot_payments (user_id, suma, data, qiwi, transaction) VALUES ('$uid', '$rub', '" . date('d.m.Y H:i') . "', '" . $inv['provider'] . "', '$tx')");
    rb_ref_reward($uid, $rub);
    return true;
}

// Polls provider for a pending invoice; credits if paid. Returns status.
function rb_check_invoice($inv) {
    if ($inv['status'] !== 'pending' || !$inv['ext_id']) return $inv['status'];
    if ($inv['provider'] === 'cryptobot') {
        $r = rb_cb('getInvoices', ['invoice_ids' => $inv['ext_id']]);
        $st = $r['items'][0]['status'] ?? '';
    } else {
        $r = rb_xr('GET', '/tg-invoices/' . rawurlencode($inv['ext_id']));
        $st = $r['status'] ?? '';
    }
    if ($st === 'paid' || $st === 'completed') { rb_credit_invoice($inv['id']); return 'paid'; }
    if ($st === 'expired') { mysql_query("UPDATE rb_invoices SET status='expired' WHERE id='" . (int)$inv['id'] . "' AND status='pending'"); return 'expired'; }
    return 'pending';
}

// ── Withdrawals (sent on admin approval) ─────────────────────────────────────
function rb_send_withdraw($wd) {
    $tg = (int)$wd['wallet'];
    $usdt = (float)$wd['amount_crypto'];
    $spend = 'rubin_wd_' . (int)$wd['id'];
    if ($wd['ps'] === 'cryptobot') {
        $r = rb_cb('transfer', ['user_id' => $tg, 'asset' => 'USDT', 'amount' => number_format($usdt, 2, '.', ''),
            'spend_id' => $spend, 'comment' => 'Вывод RUBIN #' . (int)$wd['id']]);
        return (string)($r['transfer_id'] ?? '');
    }
    $r = rb_xr('POST', '/app/transfer', ['tgUserId' => $tg, 'currency' => 'USDT', 'amount' => $usdt,
        'transferId' => $spend, 'description' => 'Вывод RUBIN #' . (int)$wd['id']]);
    return (string)($r['id'] ?? $spend);
}
