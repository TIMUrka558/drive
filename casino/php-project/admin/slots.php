<?php
session_start();
require '../inc/mysql_compat.php';
require '../inc/bd.php';
require_once '../inc/wgc.php';
$page_title = 'Слоты (WGC)';
require 'admin-head.php';

$host = 'https://' . ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST']);
$cnt = mysqli_fetch_assoc(mysqli_query($__conn, "SELECT COUNT(*) c, SUM(removed=0) a FROM wgc_games"));
$stats = mysqli_fetch_assoc(mysqli_query($__conn, "SELECT COALESCE(SUM(bet),0) b, COALESCE(SUM(win),0) w, COUNT(*) n FROM wgc_tx WHERE booked=1 AND rolled_back=0 AND is_test=0"));
$tq = trim($_GET['q'] ?? '');
$where = 'WHERE 1';
if ($tq !== '') {
    $s = mysqli_real_escape_string($__conn, ltrim($tq, '#'));
    $where = "WHERE t.tx_id='$s' OR t.round_id='$s' OR t.id='$s' OR t.user_id='$s' OR t.game LIKE '%$s%'";
}
$txs = [];
$r = mysqli_query($__conn, "SELECT t.*, u.login FROM wgc_tx t LEFT JOIN kot_user u ON u.id=t.user_id $where ORDER BY t.id DESC LIMIT 100");
while ($r && $row = mysqli_fetch_assoc($r)) $txs[] = $row;
$m = fn($v) => number_format($v / 1000000, 2, '.', ' ');
?>
<div id="slAlert" class="aw-alert"></div>

<div class="aw-cards" style="margin-bottom:20px">
  <div class="aw-card"><h3>API-ключ</h3><div class="val" style="font-size:15px" data-testid="wgc-key-status"><?= wgc_api_key() === '' ? '❌ не задан' : (wgc_is_test_key() ? '🧪 тестовый' : '✅ боевой') ?></div></div>
  <div class="aw-card"><h3>Callback Secret</h3><div class="val" style="font-size:15px" data-testid="wgc-secret-status"><?= wgc_secret() === '' ? '❌ не задан' : '✅ задан' ?></div></div>
  <div class="aw-card"><h3>Игр в каталоге</h3><div class="val" data-testid="wgc-games-count"><?= (int)$cnt['a'] ?></div></div>
  <div class="aw-card"><h3>Ставки / выигрыши</h3><div class="val" style="font-size:15px"><?= $m($stats['b']) ?> / <?= $m($stats['w']) ?> ₽</div></div>
</div>

<div class="form-section" style="margin-bottom:20px">
  <h2>Настройки в бэкофисе WGC</h2>
  <p style="font-size:13px;color:var(--muted);margin-bottom:8px">Settings → Integration → Callback URL:</p>
  <code data-testid="wgc-callback-url" style="display:block;padding:10px;background:rgba(255,255,255,.05);border-radius:8px;word-break:break-all"><?= htmlspecialchars($host) ?>/wgc_wallet.php</code>
  <p style="font-size:12px;color:var(--muted);margin-top:8px">Валюта: <?= WGC_CURRENCY ?>. Последняя синхронизация: <?= htmlspecialchars(wgc_meta_get('last_sync_local') ?: '—') ?></p>
  <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
    <button class="btn btn-primary btn-sm" data-testid="wgc-sync-full" onclick="wgcSync(0, this)">Полная синхронизация каталога</button>
    <button class="btn btn-ghost btn-sm" data-testid="wgc-sync-delta" onclick="wgcSync(1, this)">Только изменения</button>
  </div>
</div>

<div class="aw-table-wrap">
  <div class="tw-head" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
    <h2>Транзакции слотов</h2>
    <form method="get" style="display:flex;gap:8px">
      <input name="q" value="<?= htmlspecialchars($tq) ?>" class="form-control" style="width:260px" data-testid="wgc-tx-search" placeholder="ID транзакции, раунда, игрока, игра…">
      <button class="btn btn-primary btn-sm">Найти</button>
      <?php if ($tq !== ''): ?><a href="/admin/slots" class="btn btn-ghost btn-sm">Сброс</a><?php endif ?>
    </form>
  </div>
  <table class="aw-table" data-testid="wgc-tx-table">
    <tr><th>ID</th><th>Игрок</th><th>Игра</th><th>Ставка</th><th>Выигрыш</th><th>Статус</th><th>Транзакция / раунд</th><th>Дата</th></tr>
    <?php if (!$txs): ?>
    <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px">Транзакций пока нет</td></tr>
    <?php else: foreach ($txs as $t):
      $st = $t['rolled_back'] ? '<span class="badge badge-yellow">Откат</span>' : ($t['booked'] ? '<span class="badge badge-green">Принята</span>' : '<span class="badge badge-red">Отказ</span>');
      if ($t['is_test']) $st .= ' <span class="badge badge-blue">test</span>';
    ?>
    <tr>
      <td style="color:var(--muted)">#<?= (int)$t['id'] ?></td>
      <td><?= $t['user_id'] ? '<a href="/admin/users?uid=' . (int)$t['user_id'] . '">' . htmlspecialchars($t['login'] ?? '') . ' #' . (int)$t['user_id'] . '</a>' : htmlspecialchars($t['player_id']) ?></td>
      <td style="font-size:12px"><?= htmlspecialchars($t['game']) ?></td>
      <td><?= $m($t['bet']) ?></td>
      <td><?= $m($t['win']) ?></td>
      <td><?= $st ?></td>
      <td style="font-size:11px;color:var(--muted);font-family:monospace"><?= htmlspecialchars($t['tx_id']) ?><br><?= htmlspecialchars($t['round_id']) ?></td>
      <td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($t['created_at']) ?></td>
    </tr>
    <?php endforeach; endif ?>
  </table>
</div>

<script>
function wgcSync(delta, btn) {
  var el = document.getElementById('slAlert');
  btn.disabled = true; btn.textContent = 'Синхронизация…';
  bwPost({ type: 'wgcSync', delta: delta }).then(d => {
    el.textContent = d.success === 'success' ? ('Готово: обработано игр ' + d.count) : (d.error || 'Ошибка');
    el.className = 'aw-alert show ' + (d.success === 'success' ? 'success' : 'error');
    if (d.success === 'success') setTimeout(() => location.reload(), 1200); else { btn.disabled = false; btn.textContent = 'Повторить'; }
  }).catch(() => { el.textContent = 'Ошибка запроса'; el.className = 'aw-alert show error'; btn.disabled = false; });
}
</script>
<?php require 'admin-foot.php'; ?>
