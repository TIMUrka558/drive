<?php
session_start();
require '../inc/mysql_compat.php';
require '../inc/bd.php';
$page_title = 'Withdrawals';
require 'admin-head.php';

/**
 * kot_withdraws columns:
 *   id, user_id, ps, wallet, sum, date, status, fake, login_fake
 *
 * login_fake = display name (may be fake or real); join kot_user for real login.
 */

$page   = max(1, (int)($_GET['p'] ?? 1));
$per    = 50;
$off    = ($page - 1) * $per;
$search = trim($_GET['q'] ?? '');
$filter = $_GET['status'] ?? 'all';

$where_parts = [];
if ($search !== '') {
    $s = mysqli_real_escape_string($__conn, $search);
    $where_parts[] = "(w.user_id='$s' OR u.login LIKE '%$s%' OR w.login_fake LIKE '%$s%')";
}
if ($filter === 'pending')   $where_parts[] = "w.status='0'";
if ($filter === 'confirmed') $where_parts[] = "w.status='1'";
if ($filter === 'cancelled') $where_parts[] = "w.status='2'";
$where = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$total = (int) mysqli_fetch_assoc(mysqli_query($__conn,
    "SELECT COUNT(*) c FROM kot_withdraws w
     LEFT JOIN kot_user u ON u.id=w.user_id $where"))['c'];
$pages = max(1, ceil($total / $per));

$wds_q = mysqli_query($__conn,
    "SELECT w.*, u.login real_login
     FROM kot_withdraws w
     LEFT JOIN kot_user u ON u.id=w.user_id
     $where
     ORDER BY w.id DESC
     LIMIT $per OFFSET $off");
$wds = [];
while ($r = mysqli_fetch_assoc($wds_q)) $wds[] = $r;

$pending_count = (int) mysqli_fetch_assoc(mysqli_query($__conn,
    "SELECT COUNT(*) c FROM kot_withdraws WHERE status='0'"))['c'];
?>

<div id="wdAlert" class="aw-alert"></div>

<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center">
  <form method="get" style="display:flex;gap:8px;flex-wrap:wrap">
    <input name="q" value="<?= htmlspecialchars($search) ?>" class="form-control"
           style="width:220px" placeholder="Login / ID…">
    <select name="status" class="form-control" style="width:160px">
      <option value="all"       <?= $filter === 'all'       ? 'selected' : '' ?>>All statuses</option>
      <option value="pending"   <?= $filter === 'pending'   ? 'selected' : '' ?>>Pending (<?= $pending_count ?>)</option>
      <option value="confirmed" <?= $filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
      <option value="cancelled" <?= $filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>
    <button class="btn btn-primary">Filter</button>
    <?php if ($search || $filter !== 'all'): ?>
    <a href="/admin/withdraws" class="btn btn-ghost">Clear</a>
    <?php endif ?>
  </form>
  <span style="color:var(--muted);font-size:13px;margin-left:auto"><?= $total ?> records</span>
</div>

<div class="aw-table-wrap">
  <div class="tw-head"><h2>Withdrawals</h2></div>
  <table class="aw-table">
    <tr><th>ID</th><th>Player</th><th>Amount</th><th>Wallet</th><th>Method</th><th>Status</th><th>Date</th><th>Action</th></tr>
    <?php if (empty($wds)): ?>
    <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px">No withdrawals</td></tr>
    <?php else: foreach ($wds as $w):
      $st = (int)$w['status'];
      $status_html = match ($st) {
          1       => '<span class="badge badge-green">Confirmed</span>',
          2       => '<span class="badge badge-red">Cancelled</span>',
          3       => '<span class="badge badge-yellow">Sending…</span>',
          default => '<span class="badge badge-yellow">Pending</span>',
      };
      $display_login = htmlspecialchars($w['real_login'] ?? $w['login_fake'] ?? '#'.$w['user_id']);
    ?>
    <tr>
      <td style="color:var(--muted)">#<?= (int)$w['id'] ?></td>
      <td><a href="/admin/users?uid=<?= (int)$w['user_id'] ?>"><?= $display_login ?></a></td>
      <td><strong><?= number_format((float)$w['sum'], 2) ?> ₽</strong><?php if (!empty($w['amount_crypto'])): ?><br><small style="color:var(--muted)"><?= (float)$w['amount_crypto'] ?> USDT</small><?php endif ?></td>
      <td style="font-size:12px;color:var(--muted);max-width:140px;overflow:hidden;text-overflow:ellipsis">
        <?= htmlspecialchars($w['wallet'] ?? '—') ?>
      </td>
      <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($w['ps'] ?? '—') ?></td>
      <td><?= $status_html ?><?php if (!empty($w['pay_error'])): ?><br><small style="color:var(--red)" title="<?= htmlspecialchars($w['pay_error']) ?>">Error: <?= htmlspecialchars(mb_substr($w['pay_error'], 0, 60)) ?></small><?php endif ?></td>
      <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($w['date'] ?? '') ?></td>
      <td>
        <?php if ($st === 0): ?>
        <div style="display:flex;gap:4px">
          <button class="btn btn-ghost btn-sm" style="color:var(--green)"
                  onclick="wdAction(<?= (int)$w['id'] ?>,'confirmWd')">✓ Confirm</button>
          <button class="btn btn-ghost btn-sm" style="color:var(--red)"
                  onclick="wdAction(<?= (int)$w['id'] ?>,'cancelWd')">✗ Cancel</button>
        </div>
        <?php else: ?>—<?php endif ?>
      </td>
    </tr>
    <?php endforeach; endif ?>
  </table>
</div>

<?php if ($pages > 1): ?>
<div style="display:flex;gap:6px;margin-top:16px;flex-wrap:wrap">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
  <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($filter) ?>"
     class="btn btn-ghost btn-sm"
     style="<?= $i === $page ? 'background:var(--accent);color:#fff' : '' ?>"><?= $i ?></a>
  <?php endfor ?>
</div>
<?php endif ?>

<script>
function bwAlert(msg, type) {
  var el = document.getElementById('wdAlert');
  el.textContent = msg;
  el.className = 'aw-alert show ' + type;
  setTimeout(() => el.className = 'aw-alert', 3000);
}
function wdAction(id, action) {
  bwPost({ type: action, id: id })
    .then(d => {
      if (d.success === 'success') {
        bwAlert('Done!', 'success');
        setTimeout(() => location.reload(), 700);
      } else {
        bwAlert(d.error || 'Error', 'error');
      }
    }).catch(() => bwAlert('Request failed', 'error'));
}
</script>
<?php require 'admin-foot.php'; ?>
