<?php
session_start();
require '../inc/mysql_compat.php';
require '../inc/bd.php';
$page_title = 'Dashboard';
require 'admin-head.php';

// ── Metric cards ──────────────────────────────────────────────────────────
$total_players = (int) mysqli_fetch_assoc(mysqli_query($__conn, "SELECT COUNT(*) c FROM kot_user"))['c'];
$online_players = (int) mysqli_fetch_assoc(mysqli_query($__conn, "SELECT COUNT(*) c FROM kot_user WHERE online=1"))['c'];
$total_bets = (int) mysqli_fetch_assoc(mysqli_query($__conn, "SELECT COUNT(*) c FROM kot_games"))['c'];

$ggr_r = mysqli_fetch_assoc(mysqli_query($__conn,
    "SELECT COALESCE(SUM(CAST(sum AS DECIMAL(12,2))),0)
          - COALESCE(SUM(CAST(win_summa AS DECIMAL(12,2))),0) ggr FROM kot_games"));
$ggr = number_format((float)$ggr_r['ggr'], 2);

// Deposits → kot_payments (columns: id, user_id, suma, data, qiwi, transaction)
$dep_r = mysqli_fetch_assoc(mysqli_query($__conn,
    "SELECT COALESCE(SUM(CAST(suma AS DECIMAL(12,2))),0) s FROM kot_payments"));
$total_dep = number_format((float)$dep_r['s'], 2);

// Withdrawals → kot_withdraws (columns: id, user_id, ps, wallet, sum, date, status, fake, login_fake)
$wd_r = mysqli_fetch_assoc(mysqli_query($__conn,
    "SELECT COALESCE(SUM(CAST(sum AS DECIMAL(12,2))),0) s FROM kot_withdraws WHERE status='1'"));
$total_wd = number_format((float)$wd_r['s'], 2);

// ── Per-game stats — game IDs: 1=dice, 2=mines, 4=x50 ───────────────────
$game_map = ['Dice' => 1, 'Mines' => 2, 'Bubbles' => 3, 'X50' => 4];
$game_stats = [];
foreach ($game_map as $label => $gid) {
    $r = mysqli_fetch_assoc(mysqli_query($__conn,
        "SELECT COUNT(*) c,
                COALESCE(SUM(CAST(sum AS DECIMAL(12,2))),0) bet,
                COALESCE(SUM(CAST(win_summa AS DECIMAL(12,2))),0) won
         FROM kot_games WHERE game=$gid"));
    $game_stats[$label] = $r;
}

// ── Recent players ────────────────────────────────────────────────────────
$recent_q = mysqli_query($__conn,
    "SELECT id, login, balance, date_reg, ban FROM kot_user ORDER BY id DESC LIMIT 8");

// ── Recent bets ───────────────────────────────────────────────────────────
$bets_q = mysqli_query($__conn,
    "SELECT login, sum, win_summa, game, type FROM kot_games ORDER BY id DESC LIMIT 10");
?>

<div class="aw-cards">
  <div class="aw-card">
    <h3>Total Players</h3>
    <div class="val"><?= $total_players ?></div>
  </div>
  <div class="aw-card">
    <h3>Online Now</h3>
    <div class="val" style="color:var(--green)"><?= $online_players ?></div>
  </div>
  <div class="aw-card">
    <h3>GGR (all time)</h3>
    <div class="val"><?= $ggr ?> ₽</div>
    <div class="sub">House profit</div>
  </div>
  <div class="aw-card">
    <h3>Total Bets</h3>
    <div class="val"><?= $total_bets ?></div>
  </div>
  <div class="aw-card">
    <h3>Deposits</h3>
    <div class="val" style="color:var(--green)"><?= $total_dep ?> ₽</div>
  </div>
  <div class="aw-card">
    <h3>Withdrawals (paid)</h3>
    <div class="val" style="color:var(--red)"><?= $total_wd ?> ₽</div>
  </div>
</div>

<!-- Per-game stats row -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:24px">
  <?php foreach ($game_stats as $gname => $gs):
    $rtp = ($gs['bet'] > 0) ? round($gs['won'] / $gs['bet'] * 100, 1) : 0;
  ?>
  <div class="aw-card">
    <h3><?= $gname ?> (all time)</h3>
    <div class="val"><?= number_format((float)$gs['bet'], 0) ?> ₽</div>
    <div class="sub"><?= (int)$gs['c'] ?> bets · RTP <?= $rtp ?>%</div>
  </div>
  <?php endforeach ?>
</div>

<!-- Two-column -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

<div class="aw-table-wrap">
  <div class="tw-head">
    <h2>Recent Players</h2>
    <a href="/admin/users" class="btn btn-ghost btn-sm">All →</a>
  </div>
  <table class="aw-table">
    <tr><th>ID</th><th>Login</th><th>Balance</th><th>Status</th></tr>
    <?php while ($r = mysqli_fetch_assoc($recent_q)): ?>
    <tr>
      <td style="color:var(--muted)">#<?= $r['id'] ?></td>
      <td><a href="/admin/users?uid=<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['login']) ?></a></td>
      <td><?= number_format((float)$r['balance'], 2) ?> ₽</td>
      <td><?= $r['ban'] ? '<span class="badge badge-red">Banned</span>' : '<span class="badge badge-green">Active</span>' ?></td>
    </tr>
    <?php endwhile ?>
  </table>
</div>

<div class="aw-table-wrap">
  <div class="tw-head"><h2>Recent Bets</h2></div>
  <table class="aw-table">
    <tr><th>Player</th><th>Game</th><th>Bet</th><th>Won</th></tr>
    <?php
    $gname_map = [1 => 'Dice', 2 => 'Mines', 4 => 'X50', 3 => 'Bubbles'];
    while ($r = mysqli_fetch_assoc($bets_q)):
      $bet  = (float)$r['sum'];
      $won  = (float)$r['win_summa'];
      $diff = $won - $bet;
      $gl   = $gname_map[(int)$r['game']] ?? 'Game '.$r['game'];
    ?>
    <tr>
      <td><?= htmlspecialchars($r['login']) ?></td>
      <td><span class="badge badge-blue"><?= $gl ?></span></td>
      <td><?= number_format($bet, 2) ?> ₽</td>
      <td style="color:<?= $diff >= 0 ? 'var(--green)' : 'var(--red)' ?>"><?= number_format($won, 2) ?> ₽</td>
    </tr>
    <?php endwhile ?>
  </table>
</div>

</div>

<?php require 'admin-foot.php'; ?>
