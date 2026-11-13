<?php
/**
 * RUBIN Admin — AJAX handler
 *
 * Security:
 *  - Session admin check on every request
 *  - CSRF token validated via X-CSRF-Token header against $_SESSION['admin_csrf']
 *  - All user-supplied values cast or escaped before SQL use
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require '../inc/mysql_compat.php';
require '../inc/bd.php';

header('Content-Type: application/json');

// ── Auth ──────────────────────────────────────────────────────────────────
$sid  = $_SESSION['hash'] ?? '';
$_ar  = mysqli_fetch_assoc(mysqli_query($__conn,
    "SELECT admin FROM kot_user WHERE hash='" . mysqli_real_escape_string($__conn, $sid) . "'"));
if (!$_ar || (int)$_ar['admin'] !== 1) {
    echo json_encode(['success' => 'error', 'error' => 'Not authorized']);
    exit;
}

// ── CSRF ──────────────────────────────────────────────────────────────────
$csrf_header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$csrf_session = $_SESSION['admin_csrf'] ?? '';
if (!$csrf_session || !hash_equals($csrf_session, $csrf_header)) {
    echo json_encode(['success' => 'error', 'error' => 'Invalid CSRF token']);
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────────
function q(mysqli $c, string $sql): bool|mysqli_result {
    $r = mysqli_query($c, $sql);
    if ($r === false) {
        // Surface query errors instead of silently failing
        error_log('admin_func SQL error: ' . mysqli_error($c) . ' | SQL: ' . $sql);
    }
    return $r;
}
function esc(mysqli $c, string $v): string { return mysqli_real_escape_string($c, $v); }

$type   = $_POST['type'] ?? '';
$result = ['success' => 'error', 'error' => 'Unknown action'];

// ── Ban / Unban ────────────────────────────────────────────────────────────
if ($type === 'ban' || $type === 'unban') {
    $uid = (int)($_POST['hashuser'] ?? 0);
    $val = ($type === 'ban') ? 1 : 0;
    q($__conn, "UPDATE kot_user SET ban=$val WHERE id=$uid");
    $result = ['success' => 'success'];
}

// ── Edit player balance + optional password ───────────────────────────────
elseif ($type === 'editPlayer') {
    $uid  = (int)($_POST['uid'] ?? 0);
    $bal  = round(max(0, (float)($_POST['balance'] ?? 0)), 2);
    $pass = trim($_POST['password'] ?? '');
    if ($uid <= 0) {
        $result = ['success' => 'error', 'error' => 'Invalid user ID'];
    } else {
        q($__conn, "UPDATE kot_user SET balance='$bal' WHERE id=$uid");
        if (array_key_exists('ref_pct', $_POST)) {
            $rp = trim((string)$_POST['ref_pct']);
            $rpv = $rp === '' ? 'NULL' : "'" . round(max(0, min(100, (float)$rp)), 2) . "'";
            q($__conn, "UPDATE kot_user SET ref_pct=$rpv WHERE id=$uid");
        }
        if (array_key_exists('ref_id', $_POST)) {
            $ri = max(0, (int)$_POST['ref_id']);
            if ($ri !== $uid) q($__conn, "UPDATE kot_user SET ref_id='$ri' WHERE id=$uid");
        }
        if ($pass !== '') {
            $ph = esc($__conn, $pass);
            q($__conn, "UPDATE kot_user SET pass='$ph' WHERE id=$uid");
        }
        $result = ['success' => 'success'];
    }
}

// ── Manual deposit ─────────────────────────────────────────────────────────
// Adds balance to user and records in kot_payments (the site's deposit ledger).
elseif ($type === 'manualDep') {
    $uid    = (int)($_POST['hashuser'] ?? 0);
    $amount = round(max(0, (float)($_POST['amount'] ?? 0)), 2);
    if ($uid <= 0 || $amount <= 0) {
        $result = ['success' => 'error', 'error' => 'Invalid user ID or amount'];
    } else {
        // Verify user exists
        $ur = mysqli_fetch_assoc(q($__conn, "SELECT id FROM kot_user WHERE id=$uid"));
        if (!$ur) {
            $result = ['success' => 'error', 'error' => 'User not found'];
        } else {
            q($__conn, "UPDATE kot_user SET balance=balance+'$amount' WHERE id=$uid");
            // Record in kot_payments — columns: id, user_id, suma, data, qiwi, transaction
            $date = esc($__conn, date('d.m.Y H:i:s'));
            q($__conn,
                "INSERT INTO kot_payments (user_id, suma, data, qiwi, transaction)
                 VALUES ($uid, '$amount', '$date', 'admin', 'manual_deposit')");
            if (!empty($_POST['ref_reward'])) {
                require_once __DIR__ . '/../inc/rtp.php';
                rb_ref_reward($uid, $amount);
            }
            if (mysqli_affected_rows($__conn) < 1 && mysqli_errno($__conn)) {
                $result = ['success' => 'error', 'error' => 'DB error: ' . mysqli_error($__conn)];
            } else {
                $result = ['success' => 'success'];
            }
        }
    }
}

// ── Reset wager ────────────────────────────────────────────────────────────
elseif ($type === 'resetWager') {
    $uid = (int)($_POST['hashuser'] ?? 0);
    q($__conn, "UPDATE kot_user SET wager_required=0, wager_progress=0, bonus=0 WHERE id=$uid");
    q($__conn, "UPDATE kot_promo_act SET status='cancelled' WHERE user_id=$uid AND status='active'");
    $result = ['success' => 'success'];
}

// ── Save RTP settings ─────────────────────────────────────────────────────
elseif ($type === 'saveRTP') {
    $games = ['dice', 'mines', 'bubbles', 'x50'];
    $sets  = [];
    foreach ($games as $g) {
        $v = max(1, min(99, (float)($_POST['rtp_' . $g] ?? 97)));
        $sets[] = "rtp_$g='$v'";
    }
    if (isset($_POST['max_win'])) $sets[] = "max_win='" . max(0, round((float)$_POST['max_win'], 2)) . "'";
    q($__conn, "UPDATE kot_config SET " . implode(',', $sets));
    $result = ['success' => 'success'];
}

// ── Save Anti-minus settings ──────────────────────────────────────────────
elseif ($type === 'saveAntiMinus') {
    $games = ['dice', 'mines', 'bubbles', 'x50'];
    $sets  = [];
    foreach ($games as $g) {
        $on  = ((int)($_POST['antim_' . $g] ?? 0)) ? 1 : 0;
        $th  = max(0, (float)($_POST['thresh_' . $g] ?? 1000));
        $sets[] = "antim_$g=$on";
        $sets[] = "antim_thresh_$g='$th'";
    }
    q($__conn, "UPDATE kot_config SET " . implode(',', $sets));
    $result = ['success' => 'success'];
}

// ── Add promo code ─────────────────────────────────────────────────────────
elseif ($type === 'addPromo') {
    $code  = esc($__conn, trim($_POST['code'] ?? ''));
    $ptype = in_array($_POST['promo_type'] ?? '', ['deposit', 'nodeposit'])
             ? $_POST['promo_type'] : 'deposit';
    $sum   = round(max(0, (float)($_POST['sum']        ?? 0)), 2);
    $minA  = round(max(0, (float)($_POST['min_amount'] ?? 0)), 2);
    $maxA  = round(max(0, (float)($_POST['max_amount'] ?? 0)), 2);
    $wager = max(1, (int)($_POST['wager_mult'] ?? 1));
    $limit = max(0, (int)($_POST['max_uses']   ?? 0));
    $exp   = esc($__conn, trim($_POST['expires_at'] ?? ''));
    $minD  = round(max(0, (float)($_POST['min_dep'] ?? 0)), 2);
    $date  = esc($__conn, date('d.m.Y H:i:s'));

    if (!$code) {
        $result = ['success' => 'error', 'error' => 'Code name is required'];
    } else {
        $exists = mysqli_fetch_assoc(q($__conn, "SELECT id FROM kot_promo WHERE name='$code'"));
        if ($exists) {
            $result = ['success' => 'error', 'error' => 'Code already exists'];
        } else {
            q($__conn,
                "INSERT INTO kot_promo
                     (date, is_admin, name, sum, active, actived, id_active,
                      wager_mult, promo_type, min_amount, max_amount, expires_at, min_dep)
                 VALUES
                     ('$date', 1, '$code', '$sum', '$limit', 0, 0,
                      $wager, '$ptype', '$minA', '$maxA', '$exp', '$minD')");
            $result = ['success' => 'success'];
        }
    }
}

// ── Edit promo ────────────────────────────────────────────────────────────
elseif ($type === 'editPromo') {
    $id    = (int)($_POST['id'] ?? 0);
    $limit = max(0, (int)($_POST['max_uses'] ?? 0));
    $wager = max(1, (int)($_POST['wager_mult'] ?? 1));
    $minD  = round(max(0, (float)($_POST['min_dep'] ?? 0)), 2);
    $exp   = trim($_POST['expires_at'] ?? '');
    if ($exp !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $exp)) {
        $result = ['success' => 'error', 'error' => 'Неверная дата'];
    } else {
        $exp = esc($__conn, $exp);
        q($__conn, "UPDATE kot_promo SET active='$limit', wager_mult=$wager, min_dep='$minD', expires_at='$exp' WHERE id=$id");
        $result = ['success' => 'success'];
    }
}

// ── Delete promo ──────────────────────────────────────────────────────────
elseif ($type === 'deletePromo') {
    $id = (int)($_POST['id'] ?? 0);
    q($__conn, "DELETE FROM kot_promo WHERE id=$id");
    $result = ['success' => 'success'];
}

// ── Save / update bot ─────────────────────────────────────────────────────
elseif ($type === 'saveBot') {
    $id     = (int)($_POST['id']      ?? 0);
    $login  = esc($__conn, trim($_POST['login']   ?? ''));
    $minBet = round(max(0, (float)($_POST['min_bet'] ?? 10)), 2);
    $maxBet = round(max(0, (float)($_POST['max_bet'] ?? 500)), 2);
    $status = ((int)($_POST['status'] ?? 1)) ? 1 : 0;

    if (!$login) {
        $result = ['success' => 'error', 'error' => 'Bot login is required'];
    } elseif ($id > 0) {
        q($__conn,
            "UPDATE kot_bots
             SET bot_login='$login', bot_min_bet='$minBet', bot_max_bet='$maxBet', status=$status
             WHERE id=$id");
        $result = ['success' => 'success'];
    } else {
        q($__conn,
            "INSERT INTO kot_bots (bot_login, bot_min_bet, bot_max_bet, status)
             VALUES ('$login', '$minBet', '$maxBet', $status)");
        $result = ['success' => 'success'];
    }
}

// ── Toggle bot status ─────────────────────────────────────────────────────
elseif ($type === 'toggleBot') {
    $id     = (int)($_POST['id']     ?? 0);
    $status = ((int)($_POST['status'] ?? 0)) ? 1 : 0;
    q($__conn, "UPDATE kot_bots SET status=$status WHERE id=$id");
    $result = ['success' => 'success'];
}

// ── Delete bot ─────────────────────────────────────────────────────────────
elseif ($type === 'deleteBot') {
    $id = (int)($_POST['id'] ?? 0);
    q($__conn, "DELETE FROM kot_bots WHERE id=$id");
    $result = ['success' => 'success'];
}

// ── Confirm withdrawal ─────────────────────────────────────────────────────
elseif ($type === 'confirmWd') {
    $id = (int)($_POST['id'] ?? 0);
    $wd = mysqli_fetch_assoc(q($__conn, "SELECT * FROM kot_withdraws WHERE id=$id AND status='0'"));
    if (!$wd) {
        $result = ['success' => 'error', 'error' => 'Withdrawal not found or already processed'];
    } elseif (in_array($wd['ps'], ['cryptobot', 'xrocket'], true) && empty($_POST['manual'])) {
        require_once __DIR__ . '/../inc/payments.php';
        q($__conn, "UPDATE kot_withdraws SET status='3' WHERE id=$id AND status='0'");
        if (mysqli_affected_rows($__conn) < 1) {
            $result = ['success' => 'error', 'error' => 'Already processing'];
        } else {
            try {
                $tx = esc($__conn, rb_send_withdraw($wd));
                q($__conn, "UPDATE kot_withdraws SET status='1', tx_id='$tx', pay_error=NULL WHERE id=$id");
                $result = ['success' => 'success'];
            } catch (Throwable $e) {
                $em = esc($__conn, mb_substr($e->getMessage(), 0, 250));
                q($__conn, "UPDATE kot_withdraws SET status='0', pay_error='$em' WHERE id=$id");
                $result = ['success' => 'error', 'error' => 'Payout failed: ' . $e->getMessage()];
            }
        }
    } else {
        q($__conn, "UPDATE kot_withdraws SET status='1' WHERE id=$id");
        $result = ['success' => 'success'];
    }
}

// ── Cancel withdrawal (refund balance — atomic) ───────────────────────────
// Uses a transaction with an atomic claim (UPDATE ... WHERE status='0') to
// prevent double-refund from concurrent cancel requests.
elseif ($type === 'cancelWd') {
    $id = (int)($_POST['id'] ?? 0);
    mysqli_begin_transaction($__conn);
    try {
        // Atomically claim the pending row — only succeeds for exactly one caller
        q($__conn, "UPDATE kot_withdraws SET status='2' WHERE id=$id AND status='0'");
        $claimed = mysqli_affected_rows($__conn);
        if ($claimed < 1) {
            mysqli_rollback($__conn);
            $result = ['success' => 'error', 'error' => 'Withdrawal not found or already processed'];
        } else {
            // Safe to read now — we own it exclusively
            $wd = mysqli_fetch_assoc(q($__conn, "SELECT user_id, sum FROM kot_withdraws WHERE id=$id"));
            if (!$wd) {
                mysqli_rollback($__conn);
                $result = ['success' => 'error', 'error' => 'Could not read withdrawal record'];
            } else {
                $uid = (int)$wd['user_id'];
                $sum = round((float)$wd['sum'], 2);
                // Verify user exists before refunding
                q($__conn, "UPDATE kot_user SET balance=balance+'$sum' WHERE id=$uid");
                $balance_rows = mysqli_affected_rows($__conn);
                if ($balance_rows < 1) {
                    // User not found — rollback the status change too
                    mysqli_rollback($__conn);
                    $result = ['success' => 'error', 'error' => 'User not found — cancellation rolled back'];
                } else {
                    mysqli_commit($__conn);
                    $result = ['success' => 'success'];
                }
            }
        }
    } catch (Throwable $e) {
        mysqli_rollback($__conn);
        $result = ['success' => 'error', 'error' => 'Transaction failed'];
    }
}

// ── Save site settings ────────────────────────────────────────────────────
elseif ($type === 'saveSiteSettings') {
    $sn  = esc($__conn, $_POST['sitename']    ?? '');
    $sd  = esc($__conn, $_POST['sitedomen']   ?? '');
    $br  = round(max(0, (float)($_POST['bonusreg']    ?? 0)), 2);
    $mw  = round(max(0, (float)($_POST['minwithdraw'] ?? 300)), 2);
    $mb  = round(max(0, (float)($_POST['minbet']      ?? 1)), 2);
    $mxb = round(max(0, (float)($_POST['maxbet']      ?? 1000000)), 2);
    q($__conn,
        "UPDATE kot_config SET
             sitename='$sn', sitedomen='$sd', bonus_reg='$br',
             min_withdraw_sum='$mw', min_bet='$mb', max_bet='$mxb'");
    $result = ['success' => 'success'];
}

// ── Save cashback & referral ──────────────────────────────────────────────
elseif ($type === 'saveCashback') {
    $cp = round(max(0, min(100, (float)($_POST['cashback_pct'] ?? 5))), 2);
    $rp = round(max(0, min(100, (float)($_POST['ref_pct']      ?? 5))), 2);
    q($__conn, "UPDATE kot_config SET cashback_pct='$cp', ref_pct='$rp'");
    $result = ['success' => 'success'];
}

// ── Save crypto payment settings ──────────────────────────────────────────
elseif ($type === 'savePayments') {
    $rate = round(max(1, (float)($_POST['usdt_rub'] ?? 95)), 2);
    $md   = round(max(1, (float)($_POST['min_sum_dep'] ?? 100)), 2);
    $bmin = max(0, (int)($_POST['bonus_min'] ?? 10));
    $bmax = max($bmin, (int)($_POST['bonus_max'] ?? 25));
    q($__conn, "UPDATE kot_config SET usdt_rub='$rate', min_sum_dep='$md', min_bonus_size='$bmin', max_bonus_size='$bmax'");
    $result = ['success' => 'success'];
}

// ── Save withdrawal fees ──────────────────────────────────────────────────
elseif ($type === 'saveFees') {
    $fq = round(max(0, min(100, (float)($_POST['fee_qiwi']   ?? 0))), 2);
    $fc = round(max(0, min(100, (float)($_POST['fee_card']   ?? 0))), 2);
    $fx = round(max(0, min(100, (float)($_POST['fee_crypto'] ?? 0))), 2);
    q($__conn, "UPDATE kot_config SET fee_qiwi='$fq', fee_card='$fc', fee_crypto='$fx'");
    $result = ['success' => 'success'];
}

// ── Save integrations ─────────────────────────────────────────────────────
elseif ($type === 'saveIntegrations') {
    $ss  = esc($__conn, $_POST['sitesupport'] ?? '');
    $fi  = esc($__conn, $_POST['fkid']        ?? '');
    $fs1 = esc($__conn, $_POST['fksecret1']   ?? '');
    $fs2 = esc($__conn, $_POST['fksecret2']   ?? '');
    q($__conn,
        "UPDATE kot_config
         SET sitesupport='$ss', fk_id='$fi', fk_secret_1='$fs1', fk_secret_2='$fs2'");
    $result = ['success' => 'success'];
}

// ── Admin reply to ticket ─────────────────────────────────────────────────
elseif ($type === 'adminReplyTicket') {
    $tid = (int)($_POST['ticket_id'] ?? 0);
    $msg = esc($__conn, trim($_POST['message'] ?? ''));
    // Resolve admin user_id from session hash
    $admin_user = mysqli_fetch_assoc(q($__conn,
        "SELECT id FROM kot_user WHERE hash='" . esc($__conn, $sid) . "'"));
    if (!$admin_user) {
        $result = ['success' => 'error', 'error' => 'Admin user not found'];
    } elseif ($tid <= 0 || $msg === '') {
        $result = ['success' => 'error', 'error' => 'Invalid ticket ID or empty message'];
    } else {
        $admin_uid = (int)$admin_user['id'];
        q($__conn,
            "INSERT INTO kot_ticket_msgs (ticket_id, user_id, is_admin, message, created_at)
             VALUES ($tid, $admin_uid, 1, '$msg', NOW())");
        q($__conn,
            "UPDATE kot_tickets SET status='answered', updated_at=NOW() WHERE id=$tid");
        $result = ['success' => 'success'];
    }
}

// ── Admin close ticket ────────────────────────────────────────────────────
elseif ($type === 'adminCloseTicket') {
    $tid = (int)($_POST['ticket_id'] ?? 0);
    if ($tid <= 0) {
        $result = ['success' => 'error', 'error' => 'Invalid ticket ID'];
    } else {
        q($__conn, "UPDATE kot_tickets SET status='closed', updated_at=NOW() WHERE id=$tid");
        $result = ['success' => 'success'];
    }
}

// ── Legacy: saveeditper / selectper / addper ──────────────────────────────
elseif (in_array($type, ['saveeditper', 'selectper', 'addper'])) {
    $id     = (int)($_POST['id']        ?? 0);
    $per    = (float)($_POST['newper']  ?? $_POST['percent'] ?? 0);
    $chance = (float)($_POST['newchance'] ?? $_POST['chance'] ?? 0);
    $drop   = (float)($_POST['newdrop'] ?? $_POST['drop'] ?? 0);
    $status = (int)($_POST['newstatus'] ?? 1);

    if ($type === 'selectper' && $id > 0) {
        $r = mysqli_fetch_assoc(q($__conn, "SELECT * FROM kot_chance WHERE id=$id"));
        if ($r) {
            $result = [
                'success' => 'success', 'error' => '',
                'per' => $r['per'], 'chance' => $r['chance'],
                'drop' => $r['is_drop'], 'status' => $r['active'], 'id' => $r['id'],
            ];
        }
    } elseif ($type === 'saveeditper' && $id > 0) {
        q($__conn,
            "UPDATE kot_chance SET per='$per', chance='$chance', is_drop='$drop', active='$status'
             WHERE id=$id");
        $result = ['success' => 'success'];
    } elseif ($type === 'addper') {
        $ex = mysqli_fetch_assoc(q($__conn, "SELECT COUNT(*) c FROM kot_chance WHERE per='$per'"));
        if ((int)$ex['c'] > 0) {
            $result = ['success' => 'error', 'error' => 'Percent already exists'];
        } else {
            q($__conn,
                "INSERT INTO kot_chance (per, chance, is_drop, active)
                 VALUES ('$per', '$chance', '$drop', '$status')");
            $result = ['success' => 'success'];
        }
    }
}

echo json_encode($result);
exit;
