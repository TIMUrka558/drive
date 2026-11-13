<?php
// Webhook endpoint for CryptoBot (?p=cryptobot) and xRocket (?p=xrocket)
require __DIR__ . '/inc/site_config.php';
require_once __DIR__ . '/inc/payments.php';

$p = $_GET['p'] ?? '';
$raw = file_get_contents('php://input');

if ($p === 'cryptobot') {
    $token = rb_env('CRYPTOBOT_TOKEN');
    $sig = $_SERVER['HTTP_CRYPTO_PAY_API_SIGNATURE'] ?? '';
    $want = hash_hmac('sha256', $raw, hash('sha256', $token, true));
    if ($token === '' || $sig === '' || !hash_equals($want, $sig)) { http_response_code(401); exit('bad signature'); }
    $d = json_decode($raw, true);
    if (($d['update_type'] ?? '') === 'invoice_paid') {
        $ext = mysql_real_escape_string((string)($d['payload']['invoice_id'] ?? ''));
        $inv = mysql_fetch_array(mysql_query("SELECT * FROM rb_invoices WHERE provider='cryptobot' AND ext_id='$ext'"));
        if ($inv && ($d['payload']['status'] ?? '') === 'paid') rb_credit_invoice($inv['id']);
    }
    exit('ok');
}

if ($p === 'xrocket') {
    $token = rb_env('XROCKET_TOKEN');
    $sig = $_SERVER['HTTP_ROCKET_PAY_SIGNATURE'] ?? '';
    $want = hash_hmac('sha256', $raw, hash('sha256', $token, true));
    if ($token === '' || $sig === '' || !hash_equals($want, $sig)) { http_response_code(401); exit('bad signature'); }
    $d = json_decode($raw, true);
    $ext = (string)($d['data']['id'] ?? '');
    if ($ext !== '') {
        $e = mysql_real_escape_string($ext);
        $inv = mysql_fetch_array(mysql_query("SELECT * FROM rb_invoices WHERE provider='xrocket' AND ext_id='$e'"));
        // re-check status with xRocket instead of trusting the body
        if ($inv) { try { rb_check_invoice($inv); } catch (Throwable $ex) { http_response_code(500); exit('retry'); } }
    }
    exit('ok');
}

http_response_code(404);
