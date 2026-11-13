<?php
// RTP + bet limits configured in admin (/admin/rtp)
function rb_cfg() {
    static $c = null;
    if ($c === null) {
        $q = mysql_query("SELECT * FROM kot_config LIMIT 1");
        $c = ($q ? mysql_fetch_array($q) : null) ?: [];
    }
    return $c;
}
function rb_rtp($game) {
    $v = (float)(rb_cfg()['rtp_' . $game] ?? 97);
    return max(1, min(99.9, $v)) / 100;
}
function rb_max_win() {
    return (float)(rb_cfg()['max_win'] ?? 0);
}
function rb_over_limit($profit) {
    $m = rb_max_win();
    return $m > 0 && $profit > $m;
}
function rb_scale_mines($table) {
    $k = rb_rtp('mines');
    foreach ($table as $i => $row) {
        if (is_array($row)) {
            foreach ($row as $j => $v) $table[$i][$j] = floor($v * $k * 100) / 100;
        } else {
            $table[$i] = floor($row * $k * 100) / 100;
        }
    }
    return $table;
}

// X50: payout multiplier so that the wheel returns rtp_x50 % (54 sectors: x2=26, x3=17, x5=10, x50=1)
// X50 pays exactly x2 / x3 / x5 / x50
function rb_x50_mult($color) {
    return (int)$color;
}

// Anti-minus (admin /admin/rtp): force a win once the player's session loss reaches the threshold
function bwForceWin($uid, $game) {
    $c = rb_cfg();
    if (empty($c['antim_' . $game])) return false;
    $thresh = (float)($c['antim_thresh_' . $game] ?? 0);
    $loss = (float)($_SESSION['bw_loss_' . $game . '_' . (int)$uid] ?? 0);
    return $thresh > 0 && $loss >= $thresh;
}

// ── RUBIN: wager tracking & anti-minus helpers ──────────────────────────
function bwTrackBet($uid, $game, $bet, $won) {
    $uid = (int)$uid;
    $bet = (float)$bet;
    $won = (float)$won;
    // Track wager progress (only if user has active wager requirement)
    $ru = mysql_fetch_array(mysql_query("SELECT wager_required,wager_progress FROM kot_user WHERE id=$uid"));
    if($ru && (float)$ru['wager_required'] > 0){
        mysql_query("UPDATE kot_user SET wager_progress=wager_progress+'$bet' WHERE id=$uid");
        $prog = (float)$ru['wager_progress'] + $bet;
        if($prog >= (float)$ru['wager_required']){
            mysql_query("UPDATE kot_user SET wager_required=0,wager_progress=0,bonus=0 WHERE id=$uid");
            mysql_query("UPDATE kot_promo_act SET status='done' WHERE user_id=$uid AND status='active'");
        }
    }
    // Track session losses for anti-minus
    $key = 'bw_loss_'.$game.'_'.$uid;
    $loss = max(0, $bet - $won);
    if($won > 0 && $won >= $bet){
        $_SESSION[$key] = 0; // reset on win
    } else {
        $_SESSION[$key] = ($_SESSION[$key] ?? 0) + $loss;
    }
}

// ─────────────────────────────────────────────────────────────────────────


// ── Referral reward: referrer gets personal % (kot_user.ref_pct) or global kot_config.ref_pct
function rb_ref_pct_for($referrer) {
    if ($referrer && $referrer['ref_pct'] !== null && $referrer['ref_pct'] !== '') return (float)$referrer['ref_pct'];
    $c = mysql_fetch_array(mysql_query("SELECT ref_pct FROM kot_config LIMIT 1"));
    return (float)($c['ref_pct'] ?? 0);
}
function rb_ref_reward($uid, $amount) {
    $uid = (int)$uid; $amount = (float)$amount;
    if ($uid <= 0 || $amount <= 0) return 0;
    $u = mysql_fetch_array(mysql_query("SELECT ref_id FROM kot_user WHERE id='$uid'"));
    $rid = (int)($u['ref_id'] ?? 0);
    if ($rid <= 0 || $rid === $uid) return 0;
    $r = mysql_fetch_array(mysql_query("SELECT id, ref_pct FROM kot_user WHERE id='$rid'"));
    if (!$r) return 0;
    $reward = round($amount * rb_ref_pct_for($r) / 100, 2);
    if ($reward <= 0) return 0;
    mysql_query("UPDATE kot_user SET balance = balance + '$reward', ref_balance = ref_balance + '$reward' WHERE id='$rid'");
    return $reward;
}
