<?php
session_start();
require __DIR__ . '/inc/site_config.php';
require_once __DIR__ . '/inc/payments.php';
header('Content-Type: application/json; charset=utf-8');

function out($a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
function fail($m) { out(['success' => false, 'error' => $m]); }

$sid = mysql_real_escape_string($_SESSION['hash'] ?? '');
$u = $sid !== '' ? mysql_fetch_array(mysql_query("SELECT * FROM kot_user WHERE hash='$sid' LIMIT 1")) : null;
if (!$u) fail('Войдите в аккаунт');
if ((int)$u['ban'] === 1) fail('Ваш аккаунт заблокирован');
$uid = (int)$u['id'];
$type = $_POST['type'] ?? '';
$cfg = rb_pay_cfg();
$prov = rb_providers();

if ($type === 'createDeposit') {
    $p = $_POST['provider'] ?? '';
    $sum = round((float)($_POST['sum'] ?? 0), 2);
    if (!isset($prov[$p])) fail('Выберите способ оплаты');
    if (!$prov[$p]) fail('Этот способ оплаты пока не подключён');
    if ($sum < $cfg['min_dep']) fail('Минимальная сумма пополнения ' . $cfg['min_dep'] . ' ₽');
    if ($sum > 1000000) fail('Слишком большая сумма');
    $pending = mysql_fetch_array(mysql_query("SELECT COUNT(*) c FROM rb_invoices WHERE user_id='$uid' AND status='pending' AND created_at > NOW() - INTERVAL 1 HOUR"));
    if ((int)$pending['c'] >= 5) fail('Слишком много неоплаченных счетов. Оплатите или подождите час');
    try { $r = rb_create_deposit($uid, $p, $sum); }
    catch (Throwable $e) { error_log($e->getMessage()); fail('Не удалось создать счёт: ' . $e->getMessage()); }
    out(['success' => true, 'url' => $r['url'], 'id' => $r['id']]);
}

if ($type === 'checkDeposit') {
    $id = (int)($_POST['id'] ?? 0);
    $inv = mysql_fetch_array(mysql_query("SELECT * FROM rb_invoices WHERE id='$id' AND user_id='$uid'"));
    if (!$inv) fail('Счёт не найден');
    try { $st = rb_check_invoice($inv); }
    catch (Throwable $e) { fail('Не удалось проверить оплату'); }
    $bal = mysql_fetch_array(mysql_query("SELECT balance FROM kot_user WHERE id='$uid'"));
    out(['success' => true, 'status' => $st, 'balance' => (float)$bal['balance']]);
}

if ($type === 'history') {
    $items = [];
    $q = mysql_query("SELECT id, provider, amount_rub, amount_crypto, asset, status, pay_url, created_at FROM rb_invoices WHERE user_id='$uid' ORDER BY id DESC LIMIT 10");
    while ($r = mysql_fetch_assoc($q)) $items[] = $r;
    $wds = [];
    $q = mysql_query("SELECT id, ps, wallet, sum, amount_crypto, date, status FROM kot_withdraws WHERE user_id='$uid' ORDER BY id DESC LIMIT 10");
    while ($r = mysql_fetch_assoc($q)) $wds[] = $r;
    out(['success' => true, 'deposits' => $items, 'withdraws' => $wds]);
}

if ($type === 'withdraw') {
    $p = $_POST['provider'] ?? '';
    $sum = round((float)($_POST['sum'] ?? 0), 2);
    $tg = preg_replace('/\D/', '', (string)($_POST['tg_id'] ?? ''));
    if (!isset($prov[$p])) fail('Выберите способ вывода');
    if (!$prov[$p]) fail('Этот способ вывода пока не подключён');
    if (strlen($tg) < 5 || strlen($tg) > 15) fail('Укажите ваш числовой Telegram ID');
    if ($sum < $cfg['min_wd']) fail('Минимальная сумма вывода ' . $cfg['min_wd'] . ' ₽');
    $fee = (float)(mysql_fetch_array(mysql_query("SELECT fee_crypto FROM kot_config LIMIT 1"))['fee_crypto'] ?? 0);
    $usdt = floor($sum * (1 - $fee / 100) / $cfg['rate'] * 100) / 100;
    if ($usdt < 0.1) fail('Слишком маленькая сумма');
    mysql_query("UPDATE kot_user SET balance = balance - '$sum' WHERE id='$uid' AND balance >= '$sum'");
    if (mysql_affected_rows() < 1) fail('Недостаточно средств');
    mysql_query("UPDATE kot_user SET tg_id='$tg' WHERE id='$uid'");
    $date = date('d.m.Y H:i');
    mysql_query("INSERT INTO kot_withdraws (user_id, ps, wallet, sum, date, status, fake, login_fake, amount_crypto) VALUES ('$uid', '$p', '$tg', '$sum', '$date', '0', '0', '', '$usdt')");
    $bal = mysql_fetch_array(mysql_query("SELECT balance FROM kot_user WHERE id='$uid'"));
    out(['success' => true, 'balance' => (float)$bal['balance'], 'usdt' => $usdt]);
}

if ($type === 'cancelWithdraw') {
    $id = (int)($_POST['id'] ?? 0);
    mysql_query("UPDATE kot_withdraws SET status='2' WHERE id='$id' AND user_id='$uid' AND status='0'");
    if (mysql_affected_rows() < 1) fail('Заявку уже нельзя отменить');
    $wd = mysql_fetch_array(mysql_query("SELECT sum FROM kot_withdraws WHERE id='$id'"));
    $s = (float)$wd['sum'];
    mysql_query("UPDATE kot_user SET balance = balance + '$s' WHERE id='$uid'");
    $bal = mysql_fetch_array(mysql_query("SELECT balance FROM kot_user WHERE id='$uid'"));
    out(['success' => true, 'balance' => (float)$bal['balance']]);
}

if ($type === 'dailyBonus') {
    $last = (int)$u['bonus_ts'];
    $left = $last + 86400 - time();
    if ($left > 0) out(['success' => false, 'error' => 'Бонус будет доступен через ' . sprintf('%02d:%02d:%02d', intdiv($left, 3600), intdiv($left % 3600, 60), $left % 60), 'left' => $left]);
    $b = mysql_fetch_array(mysql_query("SELECT min_bonus_size, max_bonus_size FROM kot_config LIMIT 1"));
    $min = max(0, (int)$b['min_bonus_size']); $max = max($min, (int)$b['max_bonus_size']);
    $prize = random_int($min, $max);
    $now = time();
    mysql_query("UPDATE kot_user SET balance = balance + '$prize', bonus_ts='$now' WHERE id='$uid' AND bonus_ts='$last'");
    if (mysql_affected_rows() < 1) fail('Бонус уже получен');
    $bal = mysql_fetch_array(mysql_query("SELECT balance FROM kot_user WHERE id='$uid'"));
    out(['success' => true, 'prize' => $prize, 'balance' => (float)$bal['balance'], 'left' => 86400]);
}

if ($type === 'claimCashback') {
    $cb = round((float)$u['cashback'], 2);
    if ($cb <= 0) fail('Кешбэк пока не накоплен. Он начисляется с каждого депозита');
    mysql_query("UPDATE kot_user SET balance = balance + cashback, cashback = 0 WHERE id='$uid' AND cashback='$cb'");
    if (mysql_affected_rows() < 1) fail('Попробуйте ещё раз');
    $bal = mysql_fetch_array(mysql_query("SELECT balance FROM kot_user WHERE id='$uid'"));
    out(['success' => true, 'amount' => $cb, 'balance' => (float)$bal['balance']]);
}

fail('Unknown action');
