<?php
session_start();
require '../inc/mysql_compat.php';
require '../inc/bd.php';
$page_title = 'Промокоды';
require 'admin-head.php';

$promos_q = mysqli_query($__conn, "SELECT * FROM kot_promo ORDER BY id DESC");
$promos   = [];
while ($r = mysqli_fetch_assoc($promos_q)) $promos[] = $r;

$aq = trim($_GET['aq'] ?? '');
$aw = '';
if ($aq !== '') {
    $s  = mysqli_real_escape_string($__conn, ltrim($aq, '#'));
    $aw = "WHERE a.id='$s' OR a.user_id='$s' OR a.code LIKE '%$s%' OR u.login LIKE '%$s%'";
}
$acts_q = mysqli_query($__conn,
    "SELECT a.*, u.login FROM kot_promo_act a LEFT JOIN kot_user u ON u.id=a.user_id $aw ORDER BY a.id DESC LIMIT 100");
$acts = [];
while ($r = mysqli_fetch_assoc($acts_q)) $acts[] = $r;
$st_badge = ['active' => 'badge-yellow', 'done' => 'badge-green', 'cancelled' => 'badge-red'];
$st_label = ['active' => 'Отыгрывается', 'done' => 'Отыгран', 'cancelled' => 'Отменён'];
?>

<div style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
  <div style="color:var(--muted);font-size:12px">Лимит для игрока: не больше 3 активаций промокодов за 24 часа.</div>
  <button class="btn btn-primary" data-testid="promo-add-btn"
          onclick="document.getElementById('addPromoModal').classList.add('open')">+ Создать промокод</button>
</div>

<div id="promoAlert" class="aw-alert"></div>

<div class="aw-table-wrap" style="margin-bottom:20px">
  <div class="tw-head"><h2>Все промокоды (<?= count($promos) ?>)</h2></div>
  <table class="aw-table" data-testid="promo-table">
    <tr><th>ID</th><th>Код</th><th>Тип</th><th>Сумма</th><th>Вагер ×</th><th>Активировано</th><th>Лимит</th><th>Действует до</th><th></th></tr>
    <?php if (empty($promos)): ?>
    <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:24px">Промокодов пока нет</td></tr>
    <?php else: foreach ($promos as $p): ?>
    <tr data-testid="promo-row-<?= (int)$p['id'] ?>">
      <td style="color:var(--muted)">#<?= (int)$p['id'] ?></td>
      <td><strong style="font-family:monospace"><?= htmlspecialchars($p['name']) ?></strong></td>
      <td>
        <span class="badge <?= ($p['promo_type'] ?? 'deposit') === 'nodeposit' ? 'badge-yellow' : 'badge-blue' ?>">
          <?= ($p['promo_type'] ?? 'deposit') === 'nodeposit' ? 'Бездеп' : 'Депозитный' ?>
        </span>
      </td>
      <td>
        <?php if ((float)($p['min_amount'] ?? 0) > 0 && (float)($p['max_amount'] ?? 0) > 0): ?>
          <?= (float)$p['min_amount'] ?>–<?= (float)$p['max_amount'] ?> ₽
        <?php else: ?>
          <?= number_format((float)$p['sum'], 2) ?> ₽
        <?php endif ?>
      </td>
      <td>×<?= (int)($p['wager_mult'] ?? 1) ?></td>
      <td><?= (int)$p['actived'] ?></td>
      <td><?= (int)$p['active'] > 0 ? (int)$p['active'] : '∞' ?></td>
      <td style="color:var(--muted);font-size:12px"><?= $p['expires_at'] ? htmlspecialchars($p['expires_at']) : '—' ?></td>
      <td>
        <button class="btn btn-danger btn-sm" data-testid="promo-delete-<?= (int)$p['id'] ?>" onclick="deletePromo(<?= (int)$p['id'] ?>)">Удалить</button>
      </td>
    </tr>
    <?php endforeach; endif ?>
  </table>
</div>

<div class="aw-table-wrap">
  <div class="tw-head" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
    <h2>Активации промокодов</h2>
    <form method="get" style="display:flex;gap:8px">
      <input name="aq" value="<?= htmlspecialchars($aq) ?>" class="form-control" style="width:240px" data-testid="promo-act-search" placeholder="ID активации, ID игрока, логин, код…">
      <button class="btn btn-primary btn-sm">Найти</button>
      <?php if ($aq !== ''): ?><a href="/admin/promo" class="btn btn-ghost btn-sm">Сброс</a><?php endif ?>
    </form>
  </div>
  <table class="aw-table" data-testid="promo-act-table">
    <tr><th>ID</th><th>Игрок</th><th>Код</th><th>Бонус</th><th>Вагер</th><th>Статус</th><th>Дата</th></tr>
    <?php if (empty($acts)): ?>
    <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:24px">Активаций нет</td></tr>
    <?php else: foreach ($acts as $a): ?>
    <tr>
      <td style="color:var(--muted)">#<?= (int)$a['id'] ?></td>
      <td><a href="/admin/users?uid=<?= (int)$a['user_id'] ?>"><?= htmlspecialchars($a['login'] ?? '') ?> #<?= (int)$a['user_id'] ?></a></td>
      <td style="font-family:monospace"><?= htmlspecialchars($a['code']) ?></td>
      <td><?= number_format((float)$a['amount'], 2) ?> ₽</td>
      <td><?= number_format((float)$a['wager'], 2) ?> ₽</td>
      <td><span class="badge <?= $st_badge[$a['status']] ?? 'badge-blue' ?>"><?= $st_label[$a['status']] ?? htmlspecialchars($a['status']) ?></span></td>
      <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($a['created_at']) ?></td>
    </tr>
    <?php endforeach; endif ?>
  </table>
</div>

<!-- Add Promo Modal -->
<div class="aw-modal-bg" id="addPromoModal">
  <div class="aw-modal">
    <h2>Новый промокод</h2>
    <div class="form-row">
      <div class="form-group">
        <label>Код</label>
        <input type="text" id="pm_code" class="form-control" data-testid="pm-code" placeholder="RUBIN100">
      </div>
      <div class="form-group">
        <label>Тип</label>
        <select id="pm_type" class="form-control" data-testid="pm-type">
          <option value="deposit">Депозитный</option>
          <option value="nodeposit">Бездепозитный</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Сумма бонуса ₽ (фиксированная)</label>
        <input type="number" id="pm_sum" class="form-control" data-testid="pm-sum" value="100" step="0.01" min="0">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Случайная сумма: от ₽ (необязательно)</label>
        <input type="number" id="pm_min" class="form-control" data-testid="pm-min" value="0" step="0.01" min="0">
      </div>
      <div class="form-group">
        <label>до ₽</label>
        <input type="number" id="pm_max" class="form-control" data-testid="pm-max" value="0" step="0.01" min="0">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Вагер (×, во сколько раз отыграть бонус)</label>
        <input type="number" id="pm_wager" class="form-control" data-testid="pm-wager" value="3" min="1" max="100">
      </div>
      <div class="form-group">
        <label>Количество активаций (0 = без лимита)</label>
        <input type="number" id="pm_limit" class="form-control" data-testid="pm-limit" value="100" min="0">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Действует до (пусто = бессрочно)</label>
        <input type="date" id="pm_expires" class="form-control" data-testid="pm-expires">
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost"
              onclick="document.getElementById('addPromoModal').classList.remove('open')">Отмена</button>
      <button class="btn btn-primary" data-testid="pm-create-btn" onclick="addPromo()">Создать</button>
    </div>
  </div>
</div>

<script>
function bwAlert(msg, type) {
  var el = document.getElementById('promoAlert');
  el.textContent = msg;
  el.className = 'aw-alert show ' + type;
  setTimeout(() => el.className = 'aw-alert', 3500);
}
function addPromo() {
  var code = document.getElementById('pm_code').value.trim();
  if (!code) { bwAlert('Введите код', 'error'); return; }
  bwPost({
    type: 'addPromo',
    code: code,
    promo_type: document.getElementById('pm_type').value,
    sum: document.getElementById('pm_sum').value,
    min_amount: document.getElementById('pm_min').value,
    max_amount: document.getElementById('pm_max').value,
    wager_mult: document.getElementById('pm_wager').value,
    max_uses: document.getElementById('pm_limit').value,
    expires_at: document.getElementById('pm_expires').value
  }).then(d => {
    if (d.success === 'success') {
      bwAlert('Промокод создан', 'success');
      setTimeout(() => location.reload(), 900);
    } else {
      bwAlert(d.error || 'Ошибка', 'error');
    }
  }).catch(() => bwAlert('Ошибка запроса', 'error'));
}
function deletePromo(id) {
  if (!confirm('Удалить этот промокод?')) return;
  bwPost({ type: 'deletePromo', id: id })
    .then(d => {
      if (d.success === 'success') {
        bwAlert('Удалено', 'success');
        setTimeout(() => location.reload(), 700);
      } else {
        bwAlert(d.error || 'Ошибка', 'error');
      }
    }).catch(() => bwAlert('Ошибка запроса', 'error'));
}
document.getElementById('addPromoModal').addEventListener('click', function (e) {
  if (e.target === this) this.classList.remove('open');
});
</script>
<?php require 'admin-foot.php'; ?>
