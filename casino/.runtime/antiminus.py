import re, pathlib
P = pathlib.Path('/app/casino/php-project')
def rd(f): return (P/f).read_text(encoding='utf-8', errors='surrogateescape')
def wr(f, s): (P/f).write_text(s, encoding='utf-8', errors='surrogateescape')

# 1) action.php: move bwTrackBet into inc/rtp.php
s = rd('action.php')
a = s.index('// ── RUBIN: wager tracking & anti-minus helpers')
b = s.index('$pass = $_POST[\'pass\'];')
track = s[a:b]
s = s[:a] + s[b:]

# Dice: anti-minus forced win (minbet: win when rand <= nwin; maxbet: win when rand >= nwin)
mb = s.index('if($type == "minbet")'); xb = s.index('if($type == "maxbet")')
seg1 = s[mb:xb].replace("$rand = random_int(0, 999999);", "$rand = random_int(0, 999999);\n  if(bwForceWin($user_id, 'dice')) $rand = random_int(0, max(0, $nwin));", 1)
s = s[:mb] + seg1 + s[xb:]
xb = s.index('if($type == "maxbet")')
seg2 = s[xb:].replace("$rand = random_int(0, 999999);", "$rand = random_int(0, 999999);\n  if(bwForceWin($user_id, 'dice')) $rand = random_int(min(999999, $nwin), 999999);", 1)
s = s[:xb] + seg2

# Mines: anti-minus — if forced win and player hits a mine, move that mine to a free cell
old = "    if(in_array($mmines,$mines)){\n    \n       //тут есть бомба, игра проиграна"
assert old in s
new = """    if(in_array($mmines,$mines) && bwForceWin($user_id, 'mines')){
      $free = array_values(array_diff(range(1, 25), $mines, $click, [(int)$mmines]));
      if($free){
        $mines = array_values(array_diff($mines, [$mmines]));
        $mines[] = $free[array_rand($free)];
        $sm = serialize($mines);
        mysql_query("UPDATE `mines-game` SET `mines` = '$sm' WHERE `id_users` = '$user_id' AND `onOff` = '1'");
      }
    }
""" + old
s = s.replace(old, new, 1)
wr('action.php', s)

# 2) inc/rtp.php: helpers (RTP for all 4 games, anti-minus, tracking)
r = rd('inc/rtp.php')
r = r.replace('SELECT rtp_dice, rtp_mines, rtp_bubbles, max_win FROM kot_config LIMIT 1', 'SELECT * FROM kot_config LIMIT 1')
r += '''
// X50: payout multiplier so that the wheel returns rtp_x50 % (54 sectors: x2=26, x3=17, x5=10, x50=1)
function rb_x50_mult($color) {
    $cnt = [2 => 26, 3 => 17, 5 => 10, 50 => 1];
    if (!isset($cnt[(int)$color])) return (float)$color;
    return round(rb_rtp('x50') * 54 / $cnt[(int)$color], 2);
}

// Anti-minus (admin /admin/rtp): force a win once the player's session loss reaches the threshold
function bwForceWin($uid, $game) {
    $c = rb_cfg();
    if (empty($c['antim_' . $game])) return false;
    $thresh = (float)($c['antim_thresh_' . $game] ?? 0);
    $loss = (float)($_SESSION['bw_loss_' . $game . '_' . (int)$uid] ?? 0);
    return $thresh > 0 && $loss >= $thresh;
}
''' + '\n' + track.replace('<?php', '')
wr('inc/rtp.php', r)

# 3) Bubbles: anti-minus + tracking
b = rd('bubblesfunc.php')
old = "            $point = max(1, floor(rb_rtp('bubbles') / $u * 100) / 100);"
assert old in b
b = b.replace(old, old + "\n            if (bwForceWin($user_id, 'bubbles') && $point < $per) $point = round($per * (1 + random_int(0, 50) / 100), 2);", 1)
b = b.replace('''                $coef = "Выпало x" . $point;
                mysql_query("Update kot_user set balance='$newbalance' WHERE hash='$sid'");
                $fa = "success";''', '''                $coef = "Выпало x" . $point;
                mysql_query("Update kot_user set balance='$newbalance' WHERE hash='$sid'");
                bwTrackBet($user_id, 'bubbles', $sum, $sum * $per);
                $fa = "success";''', 1)
b = b.replace('''                $coef = "Выпало x" . $point;
                mysql_query("Update kot_user set balance='$newbalance' WHERE hash='$sid'");
                $error = 1;''', '''                $coef = "Выпало x" . $point;
                mysql_query("Update kot_user set balance='$newbalance' WHERE hash='$sid'");
                bwTrackBet($user_id, 'bubbles', $sum, 0);
                $error = 1;''', 1)
assert b.count("bwTrackBet") == 2
wr('bubblesfunc.php', b)

# 4) Wheel X50: RTP multiplier, anti-minus, tracking
w = rd('wheel.php')
old = "   $randWheel =  mt_rand(0,53); //цвет который выпадет"
assert old in w
w = w.replace(old, old + "\n   if(bwForceWin($id, 'x50') && in_array((int)$colorWheel, [2,3,5,50])){ while($arrayWheel[$randWheel] != $colorWheel) $randWheel = mt_rand(0,53); }", 1)
w = w.replace("  $vozm_win = ($bet * $colorWheel) - $bet;", "  $vozm_win = ($bet * rb_x50_mult($colorWheel)) - $bet;", 1)
w = w.replace("    $win = $bet * $colorWheel - $bet;", "    $win = round($bet * rb_x50_mult($colorWheel) - $bet, 2);\n    bwTrackBet($id, 'x50', $bet, $bet + $win);", 1)
w = w.replace("    $win = $bet * $colorWheel;", "    $win = round($bet * rb_x50_mult($colorWheel), 2);", 1)
old = "  }else{ //вы проиграли"
assert old in w
w = w.replace(old, old + "\n    bwTrackBet($id, 'x50', $bet, 0);", 1)
wr('wheel.php', w)
print('ok')
