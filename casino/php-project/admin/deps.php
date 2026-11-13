<?php
session_start();
require '../inc/mysql_compat.php';
require '../inc/bd.php';
$page_title = 'Deposits';
require 'admin-head.php';

/**
 * Deposits are stored in kot_payments
 * Columns: id, user_id, suma, data, qiwi, transaction
 */

$page   = max(1, (int)($_GET['p'] ?? 1));
$per    = 50;
$off    = ($page - 1) * $per;
$search = trim($_GET['q'] ?? '');

$where = '';
if ($search !== '') {
    $s     = mysqli_real_escape_string($__conn, $search);
    $where = "WHERE p.user_id='$s' OR u.login LIKE '%$s%'";
}

$total = (int) mysqli_fetch_assoc(mysqli_query($__conn,
    "SELECT COUNT(*) c FROM kot_payments p
     LEFT JOIN kot_user u ON u.id=p.user_id $where"))['c'];
$pages = max(1, ceil($total / $per));

$q = mysqli_query($__conn,
    "SELECT p.*, u.login
     FROM kot_payments p
     LEFT JOIN kot_user u ON u.id=p.user_id
     $where
     ORDER BY p.id DESC
     LIMIT $per OFFSET $off");
$rows = [];
while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;

$sum_r = mysqli_fetch_assoc(mysqli_query($__conn,
    "SELECT COALESCE(SUM(CAST(p.suma AS DECIMAL(12,2))),0) s
     FROM kot_payments p LEFT JOIN kot_user u ON u.id=p.user_id $where"));
$total_sum = number_format((float)$sum_r['s'], 2);
?>

<div style="display:flex;gap:12px;margin-bottom:20px;align-items:center;flex-wrap:wrap">
  <form method="get" style="display:flex;gap:8px">
    <input name="q" value="<?= htmlspecialchars($search) ?>" class="form-control"
           style="width:280px" placeholder="Search by player ID or login…">
    <button class="btn btn-primary">Search</button>
    <?php if ($search): ?><a href="/admin/deps" class="btn btn-ghost">Clear</a><?php endif ?>
  </form>
  <div style="margin-left:auto;color:var(--muted);font-size:13px">
    Total: <strong style="color:var(--green)"><?= $total_sum ?> ₽</strong> · <?= $total ?> records
  </div>
</div>

<div class="aw-table-wrap">
  <div class="tw-head"><h2>Deposits (kot_payments)</h2></div>
  <table class="aw-table">
    <tr><th>ID</th><th>Player</th><th>Amount</th><th>QIWI / Wallet</th><th>Transaction</th><th>Date</th></tr>
    <?php if (empty($rows)): ?>
    <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">No deposits found</td></tr>
    <?php else: foreach ($rows as $d): ?>
    <tr>
      <td style="color:var(--muted)">#<?= (int)$d['id'] ?></td>
      <td><a href="/admin/users?uid=<?= (int)$d['user_id'] ?>">
        <?= htmlspecialchars($d['login'] ?? '#'.$d['user_id']) ?>
      </a></td>
      <td><strong><?= number_format((float)$d['suma'], 2) ?> ₽</strong></td>
      <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($d['qiwi'] ?? '—') ?></td>
      <td style="color:var(--muted);font-size:12px;max-width:140px;overflow:hidden;text-overflow:ellipsis">
        <?= htmlspecialchars($d['transaction'] ?? '—') ?>
      </td>
      <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($d['data'] ?? '') ?></td>
    </tr>
    <?php endforeach; endif ?>
  </table>
</div>

<?php if ($pages > 1): ?>
<div style="display:flex;gap:6px;margin-top:16px;flex-wrap:wrap">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
  <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>"
     class="btn btn-ghost btn-sm"
     style="<?= $i === $page ? 'background:var(--accent);color:#fff' : '' ?>"><?= $i ?></a>
  <?php endfor ?>
</div>
<?php endif ?>

<?php require 'admin-foot.php'; ?>
