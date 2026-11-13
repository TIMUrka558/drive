<?php
session_start();
require '../inc/mysql_compat.php';
require '../inc/bd.php';
$page_title = 'Players';
require 'admin-head.php';

$uid    = (int)($_GET['uid'] ?? 0);
$search = trim($_GET['q'] ?? '');

// ── Fetch selected player ─────────────────────────────────────────────────
$player = null;
if ($uid > 0) {
    $player = mysqli_fetch_assoc(mysqli_query($__conn,
        "SELECT * FROM kot_user WHERE id=$uid"));
}

// ── Last 10 bets for player ───────────────────────────────────────────────
// kot_games columns: id, user_id, login, number, cel, sum, chance, type, win_summa, game
$player_bets = [];
if ($player) {
    $br = mysqli_query($__conn,
        "SELECT id, game, type, sum, win_summa FROM kot_games
         WHERE user_id=" . (int)$player['id'] . "
         ORDER BY id DESC LIMIT 10");
    while ($b = mysqli_fetch_assoc($br)) $player_bets[] = $b;
}

// ── Search results ────────────────────────────────────────────────────────
$results = [];
if ($search !== '') {
    $s = mysqli_real_escape_string($__conn, ltrim($search, '#'));
    $qr = mysqli_query($__conn,
        "SELECT id, login, balance, ip, ban, admin, date_reg FROM kot_user
         WHERE id='$s' OR login LIKE '%$s%' OR ip LIKE '%$s%'
         LIMIT 30");
    while ($r = mysqli_fetch_assoc($qr)) $results[] = $r;
}

// ── Default list: latest players ──────────────────────────────────────────
if ($search === '' && !$player) {
    $qr = mysqli_query($__conn, "SELECT id, login, balance, ip, ban, admin, date_reg FROM kot_user ORDER BY id DESC LIMIT 50");
    while ($r = mysqli_fetch_assoc($qr)) $results[] = $r;
}

// ── Referral info for player ──────────────────────────────────────────────
$ref_info = null;
if ($player) {
    $pid = (int)$player['id'];
    $cnt = mysqli_fetch_assoc(mysqli_query($__conn, "SELECT COUNT(*) c FROM kot_user WHERE ref_id='$pid'"));
    $dep = mysqli_fetch_assoc(mysqli_query($__conn, "SELECT COALESCE(SUM(p.suma),0) s FROM kot_payments p JOIN kot_user u ON u.id=p.user_id WHERE u.ref_id='$pid'"));
    $gp  = mysqli_fetch_assoc(mysqli_query($__conn, "SELECT ref_pct FROM kot_config LIMIT 1"));
    $own = mysqli_fetch_assoc(mysqli_query($__conn, "SELECT COALESCE(SUM(suma),0) s FROM kot_payments WHERE user_id='$pid'"));
    $pa = mysqli_query($__conn, "SELECT * FROM kot_promo_act WHERE user_id='$pid' ORDER BY id DESC LIMIT 20");
    $player_promos = [];
    while ($r = mysqli_fetch_assoc($pa)) $player_promos[] = $r;
    $ref_info = ['count' => (int)$cnt['c'], 'deps' => (float)$dep['s'], 'global' => (float)$gp['ref_pct'], 'own_deps' => (float)$own['s']];
}

$gname_map = [1 => 'Dice', 2 => 'Mines', 3 => 'Bubbles', 4 => 'X50'];
?>

<div style="display:flex;gap:12px;margin-bottom:20px">
  <form method="get" style="display:flex;gap:8px;flex:1">
    <input name="q" value="<?= htmlspecialchars($search) ?>" class="form-control"
           style="max-width:340px" placeholder="Search by ID, login or IP…">
    <button class="btn btn-primary">Search</button>
    <?php if ($search): ?><a href="/admin/users" class="btn btn-ghost">Clear</a><?php endif ?>
  </form>
</div>

<?php if ($search !== '' && count($results) === 0): ?>
<div class="aw-alert error show">No players found for "<?= htmlspecialchars($search) ?>"</div>
<?php endif ?>

<?php if (!empty($results)): ?>
<div class="aw-table-wrap" style="margin-bottom:20px">
  <div class="tw-head"><h2><?= $search !== '' ? 'Search results' : 'Latest players' ?> (<?= count($results) ?>)</h2></div>
  <table class="aw-table">
    <tr><th>ID</th><th>Login</th><th>Balance</th><th>IP</th><th>Registered</th><th>Status</th><th></th></tr>
    <?php foreach ($results as $r): ?>
    <tr>
      <td style="color:var(--muted)">#<?= (int)$r['id'] ?></td>
      <td><strong><?= htmlspecialchars($r['login']) ?></strong></td>
      <td><?= number_format((float)$r['balance'], 2) ?> ₽</td>
      <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($r['ip']) ?></td>
      <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($r['date_reg']) ?></td>
      <td>
        <?php if ($r['ban']): ?>
          <span class="badge badge-red">Banned</span>
        <?php elseif ($r['admin']): ?>
          <span class="badge badge-yellow">Admin</span>
        <?php else: ?>
          <span class="badge badge-green">Active</span>
        <?php endif ?>
      </td>
      <td><a href="/admin/users?uid=<?= (int)$r['id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
    </tr>
    <?php endforeach ?>
  </table>
</div>
<?php endif ?>

<?php if ($player): ?>
<div id="bwAlert" class="aw-alert"></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

  <!-- Info card -->
  <div class="form-section">
    <h2>Player #<?= (int)$player['id'] ?> — <?= htmlspecialchars($player['login']) ?></h2>
    <div class="aw-cards" style="grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
      <div class="aw-card" style="padding:12px">
        <h3>Balance</h3>
        <div class="val" style="font-size:20px"><?= number_format((float)$player['balance'], 2) ?> ₽</div>
      </div>
      <div class="aw-card" style="padding:12px">
        <h3>Wager progress</h3>
        <div class="val" style="font-size:16px">
          <?= number_format((float)($player['wager_progress'] ?? 0), 0) ?>
          / <?= number_format((float)($player['wager_required'] ?? 0), 0) ?>
        </div>
      </div>
    </div>
    <table style="font-size:12px;color:var(--muted);width:100%;margin-bottom:14px;line-height:1.8">
      <tr><td style="width:100px">IP</td><td><?= htmlspecialchars($player['ip'] ?? '—') ?></td></tr>
      <tr><td>Registered</td><td><?= htmlspecialchars($player['date_reg'] ?? '—') ?></td></tr>
      <tr><td>Invited by</td><td><?= (int)($player['ref_id'] ?? 0) > 0 ? '<a href="/admin/users?uid=' . (int)$player['ref_id'] . '">#' . (int)$player['ref_id'] . '</a>' : '—' ?></td></tr>
      <tr><td>Deposits</td><td><?= number_format($ref_info['own_deps'], 2) ?> ₽</td></tr>
      <tr><td>Bonus active</td><td><?= $player['bonus'] ? 'Yes' : 'No' ?></td></tr>
      <tr><td>Status</td><td>
        <?= $player['ban']
            ? '<span class="badge badge-red">Banned</span>'
            : '<span class="badge badge-green">Active</span>' ?>
      </td></tr>
    </table>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <?php if ($player['ban']): ?>
      <button class="btn btn-ghost btn-sm" onclick="bwAction('unban', <?= (int)$player['id'] ?>)">✅ Unban</button>
      <?php else: ?>
      <button class="btn btn-danger btn-sm" onclick="bwAction('ban', <?= (int)$player['id'] ?>)">🚫 Ban</button>
      <?php endif ?>
      <button class="btn btn-ghost btn-sm" onclick="bwAction('resetWager', <?= (int)$player['id'] ?>)">🔄 Reset Wager</button>
    </div>
  </div>

  <!-- Referral card -->
  <div class="form-section">
    <h2>Referral system</h2>
    <div class="aw-cards" style="grid-template-columns:1fr 1fr 1fr;gap:10px">
      <div class="aw-card" style="padding:12px"><h3>Invited</h3><div class="val" style="font-size:20px" data-testid="admin-ref-count"><?= $ref_info['count'] ?></div></div>
      <div class="aw-card" style="padding:12px"><h3>Their deposits</h3><div class="val" style="font-size:16px"><?= number_format($ref_info['deps'], 2) ?> ₽</div></div>
      <div class="aw-card" style="padding:12px"><h3>Earned</h3><div class="val" style="font-size:16px" data-testid="admin-ref-earned"><?= number_format((float)($player['ref_balance'] ?? 0), 2) ?> ₽</div></div>
    </div>
    <p style="font-size:12px;color:var(--muted);margin-top:10px">Current rate: <strong><?= $player['ref_pct'] === null ? $ref_info['global'] . '% (global)' : (float)$player['ref_pct'] . '% (personal)' ?></strong>. The inviter gets this % of every deposit made by invited players.</p>
  </div>

  <!-- Edit card -->
  <div class="form-section">
    <h2>Edit Player</h2>
    <div class="form-group" style="margin-bottom:12px">
      <label>Balance (set exact value)</label>
      <input type="number" id="newBalance" class="form-control"
             value="<?= htmlspecialchars($player['balance']) ?>" step="0.01">
    </div>
    <div class="form-group" style="margin-bottom:12px">
      <label>Personal referral % (empty = global <?= $ref_info['global'] ?>%)</label>
      <input type="number" id="refPct" class="form-control" data-testid="admin-ref-pct-input"
             value="<?= $player['ref_pct'] === null ? '' : htmlspecialchars($player['ref_pct']) ?>" step="0.1" min="0" max="100" placeholder="<?= $ref_info['global'] ?>">
    </div>
    <div class="form-group" style="margin-bottom:12px">
      <label>Invited by (referrer player ID, 0 = none)</label>
      <input type="number" id="refId" class="form-control" data-testid="admin-ref-id-input" value="<?= (int)($player['ref_id'] ?? 0) ?>" min="0">
    </div>
    <div class="form-group" style="margin-bottom:12px">
      <label>Manual Deposit — amount to add</label>
      <input type="number" id="manDep" class="form-control" data-testid="admin-manual-dep-input" value="0" step="0.01" min="0">
      <label style="display:flex;align-items:center;gap:6px;margin-top:8px;font-size:12px"><input type="checkbox" id="manDepRef" checked> Pay referral % to inviter</label>
    </div>
    <div class="form-group" style="margin-bottom:12px">
      <label>New Password (leave blank to keep)</label>
      <input type="text" id="newPass" class="form-control" placeholder="New password…">
    </div>
    <div style="display:flex;gap:8px;margin-top:4px">
      <button class="btn btn-primary btn-sm" onclick="bwSavePlayer(<?= (int)$player['id'] ?>)">Save Changes</button>
      <button class="btn btn-ghost btn-sm" onclick="bwManualDep(<?= (int)$player['id'] ?>)">+ Add Deposit</button>
    </div>
  </div>

</div>

<div class="aw-table-wrap" style="margin-bottom:20px">
  <div class="tw-head"><h2>Активации промокодов</h2></div>
  <table class="aw-table" data-testid="player-promo-table">
    <tr><th>ID</th><th>Код</th><th>Бонус</th><th>Вагер</th><th>Статус</th><th>Дата</th></tr>
    <?php if (empty($player_promos)): ?>
    <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:20px">Нет активаций</td></tr>
    <?php else: foreach ($player_promos as $pp): ?>
    <tr>
      <td style="color:var(--muted)">#<?= (int)$pp['id'] ?></td>
      <td style="font-family:monospace"><?= htmlspecialchars($pp['code']) ?></td>
      <td><?= number_format((float)$pp['amount'], 2) ?> ₽</td>
      <td><?= number_format((float)$pp['wager'], 2) ?> ₽</td>
      <td><?= ['active' => 'Отыгрывается', 'done' => 'Отыгран', 'cancelled' => 'Отменён'][$pp['status']] ?? htmlspecialchars($pp['status']) ?></td>
      <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($pp['created_at']) ?></td>
    </tr>
    <?php endforeach; endif ?>
  </table>
</div>

<!-- Last 10 bets -->
<div class="aw-table-wrap">
  <div class="tw-head"><h2>Last 10 bets</h2></div>
  <table class="aw-table">
    <tr><th>ID</th><th>Game</th><th>Type</th><th>Bet</th><th>Won</th><th>P/L</th></tr>
    <?php if (empty($player_bets)): ?>
    <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:20px">No bets yet</td></tr>
    <?php else: foreach ($player_bets as $b):
      $bet = (float)$b['sum'];
      $won = (float)$b['win_summa'];
      $pl  = $won - $bet;
      $gl  = $gname_map[(int)$b['game']] ?? 'Game ' . $b['game'];
    ?>
    <tr>
      <td style="color:var(--muted)">#<?= (int)$b['id'] ?></td>
      <td><span class="badge badge-blue"><?= $gl ?></span></td>
      <td style="color:var(--muted)"><?= htmlspecialchars($b['type']) ?></td>
      <td><?= number_format($bet, 2) ?> ₽</td>
      <td><?= number_format($won, 2) ?> ₽</td>
      <td style="color:<?= $pl >= 0 ? 'var(--green)' : 'var(--red)' ?>">
        <?= $pl >= 0 ? '+' : '' ?><?= number_format($pl, 2) ?>
      </td>
    </tr>
    <?php endforeach; endif ?>
  </table>
</div>
<?php endif ?>

<script>
function bwAlert(msg, type) {
  var el = document.getElementById('bwAlert');
  el.textContent = msg;
  el.className = 'aw-alert show ' + type;
  setTimeout(() => el.className = 'aw-alert', 3500);
}
function bwAction(action, uid) {
  bwPost({ type: action, hashuser: uid })
    .then(d => {
      if (d.success === 'success') {
        bwAlert('Done!', 'success');
        if (action !== 'manualDep') setTimeout(() => location.reload(), 800);
      } else {
        bwAlert(d.error || 'Error', 'error');
      }
    }).catch(() => bwAlert('Request failed', 'error'));
}
function bwSavePlayer(uid) {
  bwPost({
    type: 'editPlayer',
    uid: uid,
    balance: document.getElementById('newBalance').value,
    ref_pct: document.getElementById('refPct').value,
    ref_id: document.getElementById('refId').value,
    password: document.getElementById('newPass').value
  }).then(d => {
    if (d.success === 'success') { bwAlert('Saved!', 'success'); setTimeout(() => location.reload(), 800); }
    else bwAlert(d.error || 'Error', 'error');
  }).catch(() => bwAlert('Request failed', 'error'));
}
function bwManualDep(uid) {
  var am = parseFloat(document.getElementById('manDep').value) || 0;
  if (am <= 0) { bwAlert('Enter a deposit amount > 0', 'error'); return; }
  bwPost({ type: 'manualDep', hashuser: uid, amount: am, ref_reward: document.getElementById('manDepRef').checked ? 1 : '' })
    .then(d => {
      if (d.success === 'success') {
        bwAlert('Deposit added!', 'success');
        setTimeout(() => location.reload(), 800);
      } else {
        bwAlert(d.error || 'Error', 'error');
      }
    }).catch(() => bwAlert('Request failed', 'error'));
}
</script>

<?php require 'admin-foot.php'; ?>
