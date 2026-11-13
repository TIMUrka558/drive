<?php
session_start();
require '../inc/mysql_compat.php';
require '../inc/bd.php';
$page_title = 'Statistics';
require 'admin-head.php';

// Game IDs: 1=dice, 2=mines, 4=x50
$game_map   = ['Dice' => 1, 'Mines' => 2, 'X50' => 4];
$gname_map  = [1 => 'Dice', 2 => 'Mines', 3 => 'Bubbles', 4 => 'X50'];

// ── Overall stats ─────────────────────────────────────────────────────────
$total_players = (int) mysqli_fetch_assoc(mysqli_query($__conn, "SELECT COUNT(*) c FROM kot_user"))['c'];
$online_now    = (int) mysqli_fetch_assoc(mysqli_query($__conn, "SELECT COUNT(*) c FROM kot_user WHERE online=1"))['c'];
$banned        = (int) mysqli_fetch_assoc(mysqli_query($__conn, "SELECT COUNT(*) c FROM kot_user WHERE ban=1"))['c'];

$ggr_all = (float) mysqli_fetch_assoc(mysqli_query($__conn,
    "SELECT COALESCE(SUM(CAST(sum AS DECIMAL(12,2))),0)
          - COALESCE(SUM(CAST(win_summa AS DECIMAL(12,2))),0) v FROM kot_games"))['v'];

$dep_all = (float) mysqli_fetch_assoc(mysqli_query($__conn,
    "SELECT COALESCE(SUM(CAST(suma AS DECIMAL(12,2))),0) v FROM kot_payments"))['v'];

$wd_all = (float) mysqli_fetch_assoc(mysqli_query($__conn,
    "SELECT COALESCE(SUM(CAST(sum AS DECIMAL(12,2))),0) v FROM kot_withdraws WHERE status='1'"))['v'];

// ── Per-game breakdown ────────────────────────────────────────────────────
$game_data = [];
foreach ($game_map as $gname => $gid) {
    $r = mysqli_fetch_assoc(mysqli_query($__conn,
        "SELECT COUNT(*) cnt,
                COALESCE(SUM(CAST(sum AS DECIMAL(12,2))),0) bet,
                COALESCE(SUM(CAST(win_summa AS DECIMAL(12,2))),0) won
         FROM kot_games WHERE game=$gid"));
    $r['rtp'] = $r['bet'] > 0 ? round($r['won'] / $r['bet'] * 100, 2) : 0;
    $r['ggr'] = (float)$r['bet'] - (float)$r['won'];
    $game_data[$gname] = $r;
}

// ── Top players by GGR (casino wins from them) ────────────────────────────
$top_q = mysqli_query($__conn,
    "SELECT login,
            COALESCE(SUM(CAST(sum AS DECIMAL(12,2))),0)
          - COALESCE(SUM(CAST(win_summa AS DECIMAL(12,2))),0) ggr,
            COUNT(*) bets
     FROM kot_games
     GROUP BY user_id, login
     ORDER BY ggr DESC
     LIMIT 10");
$top = [];
while ($r = mysqli_fetch_assoc($top_q)) $top[] = $r;

// ── Signups by day (last 7 days) — uses date_reg column ──────────────────
$reg_q = mysqli_query($__conn,
    "SELECT DATE(date_reg) d, COUNT(*) c
     FROM kot_user
     WHERE date_reg >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(date_reg)
     ORDER BY d");
$regs = [];
while ($r = mysqli_fetch_assoc($reg_q)) $regs[] = $r;
?>

<div class="aw-cards">
  <div class="aw-card"><h3>Total Players</h3><div class="val"><?= $total_players ?></div></div>
  <div class="aw-card"><h3>Online Now</h3><div class="val" style="color:var(--green)"><?= $online_now ?></div></div>
  <div class="aw-card"><h3>Banned</h3><div class="val" style="color:var(--red)"><?= $banned ?></div></div>
  <div class="aw-card"><h3>GGR (all time)</h3><div class="val"><?= number_format($ggr_all, 0) ?> ₽</div></div>
  <div class="aw-card"><h3>Total Deposits</h3><div class="val" style="color:var(--green)"><?= number_format($dep_all, 0) ?> ₽</div></div>
  <div class="aw-card"><h3>Total Withdrawals</h3><div class="val" style="color:var(--red)"><?= number_format($wd_all, 0) ?> ₽</div></div>
</div>

<!-- Per-game table -->
<div class="aw-table-wrap" style="margin-bottom:20px">
  <div class="tw-head"><h2>Per-Game Stats (all time)</h2></div>
  <table class="aw-table">
    <tr><th>Game</th><th>Bets</th><th>Volume</th><th>Won by players</th><th>GGR</th><th>RTP achieved</th></tr>
    <?php foreach ($game_data as $gname => $gd): ?>
    <tr>
      <td><span class="badge badge-blue"><?= $gname ?></span></td>
      <td><?= number_format((int)$gd['cnt']) ?></td>
      <td><?= number_format((float)$gd['bet'], 2) ?> ₽</td>
      <td><?= number_format((float)$gd['won'], 2) ?> ₽</td>
      <td style="color:<?= $gd['ggr'] >= 0 ? 'var(--green)' : 'var(--red)' ?>">
        <?= number_format($gd['ggr'], 2) ?> ₽
      </td>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <div style="flex:1;height:6px;background:var(--border);border-radius:3px;overflow:hidden">
            <div style="width:<?= min(100, $gd['rtp']) ?>%;height:100%;background:var(--accent);border-radius:3px"></div>
          </div>
          <span><?= $gd['rtp'] ?>%</span>
        </div>
      </td>
    </tr>
    <?php endforeach ?>
  </table>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

<div class="aw-table-wrap">
  <div class="tw-head"><h2>Top Players by GGR</h2><span style="font-size:12px;color:var(--muted)">casino profit</span></div>
  <table class="aw-table">
    <tr><th>#</th><th>Player</th><th>Bets</th><th>GGR</th></tr>
    <?php foreach ($top as $i => $t): ?>
    <tr>
      <td style="color:var(--muted)"><?= $i + 1 ?></td>
      <td><?= htmlspecialchars($t['login']) ?></td>
      <td><?= number_format((int)$t['bets']) ?></td>
      <td style="color:<?= (float)$t['ggr'] >= 0 ? 'var(--green)' : 'var(--red)' ?>">
        <?= number_format((float)$t['ggr'], 2) ?> ₽
      </td>
    </tr>
    <?php endforeach ?>
    <?php if (empty($top)): ?>
    <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:20px">No bets yet</td></tr>
    <?php endif ?>
  </table>
</div>

<div class="aw-table-wrap">
  <div class="tw-head"><h2>Signups (last 7 days)</h2></div>
  <table class="aw-table">
    <tr><th>Date</th><th>New players</th><th>Bar</th></tr>
    <?php
    $maxreg = max(1, array_reduce($regs, fn($c, $r) => max($c, $r['c']), 0));
    foreach ($regs as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['d']) ?></td>
      <td><strong><?= $r['c'] ?></strong></td>
      <td>
        <div style="height:8px;background:var(--border);border-radius:4px;overflow:hidden;width:120px">
          <div style="width:<?= round($r['c'] / $maxreg * 100) ?>%;height:100%;background:var(--accent);border-radius:4px"></div>
        </div>
      </td>
    </tr>
    <?php endforeach ?>
    <?php if (empty($regs)): ?>
    <tr><td colspan="3" style="color:var(--muted);text-align:center;padding:16px">No registrations in last 7 days</td></tr>
    <?php endif ?>
  </table>
</div>

</div>
<?php require 'admin-foot.php'; ?>
