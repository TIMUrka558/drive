<?php
session_start();
require '../inc/mysql_compat.php';
require '../inc/bd.php';
require_once '../inc/wgc.php';
$page_title = 'Промокоды на слоты';
require 'admin-head.php';

[$pc, $pr] = wgc_api('POST', '/v1/freespins', ['probe' => true]);
$fsDisabled = $pc === 404 && stripos($pr['error']['message'] ?? '', 'endpoint') !== false;

$promos = [];
$r = mysqli_query($__conn, "SELECT * FROM wgc_fs_promo ORDER BY id DESC");
while ($r && $row = mysqli_fetch_assoc($r)) $promos[] = $row;
$gq = trim($_GET['gq'] ?? '');
$gw = '';
if ($gq !== '') {
    $s = mysqli_real_escape_string($__conn, ltrim($gq, '#'));
    $gw = "WHERE g.id='$s' OR g.user_id='$s' OR g.freespin_id='$s' OR u.login LIKE '%$s%' OR p.code LIKE '%$s%'";
}
$grants = [];
$r = mysqli_query($__conn, "SELECT g.*, u.login, p.code FROM wgc_fs_grant g LEFT JOIN kot_user u ON u.id=g.user_id LEFT JOIN wgc_fs_promo p ON p.id=g.promo_id $gw ORDER BY g.id DESC LIMIT 100");
while ($r && $row = mysqli_fetch_assoc($r)) $grants[] = $row;
$stl = ['active' => ['badge-green', 'Начислены'], 'failed' => ['badge-red', 'Ошибка'], 'removed' => ['badge-yellow', 'Сняты']];
?>
<div id="fsAlert" class="aw-alert"></div>

<?php if ($fsDisabled): ?>
<div class="aw-alert error show" data-testid="fs-api-disabled" style="position:static;margin-bottom:16px">
  Выдача фриспинов через API у вашего аккаунта WGC сейчас выключена: сервер отвечает «No such endpoint». Попросите менеджера WGC включить фриспины (Freespins / cap.account.freespins.grant) для API-ключа. До этого игроки увидят сообщение «Фриспины временно недоступны», и ничего не спишется.
</div>
<?php endif ?>

<div style="margin-bottom:16px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center">
  <div style="color:var(--muted);font-size:12px">Игрок вводит код в том же поле, что и обычные промокоды. Вагер = выигрыш с фриспинов × множитель. Лимит общий: не больше 3 промокодов за 24 часа.</div>
  <button class="btn btn-primary" data-testid="fs-add-btn" onclick="document.getElementById('fsModal').classList.add('open')">+ Создать промокод на слот</button>
</div>

<div class="aw-table-wrap" style="margin-bottom:20px">
  <div class="tw-head"><h2>Промокоды на фриспины (<?= count($promos) ?>)</h2></div>
  <table class="aw-table" data-testid="fs-promo-table">
    <tr><th>ID</th><th>Код</th><th>Слот</th><th>Спинов</th><th>Ставка</th><th>Вагер ×</th><th>Активировано</th><th>Лимит</th><th>До</th><th></th></tr>
    <?php if (!$promos): ?><tr><td colspan="10" style="text-align:center;color:var(--muted);padding:24px">Пока нет</td></tr>
    <?php else: foreach ($promos as $p): ?>
    <tr>
      <td style="color:var(--muted)">#<?= (int)$p['id'] ?></td>
      <td style="font-family:monospace"><strong><?= htmlspecialchars($p['code']) ?></strong></td>
      <td><?= htmlspecialchars($p['game_name']) ?><br><small style="color:var(--muted)"><?= htmlspecialchars($p['public_id']) ?></small></td>
      <td><?= (int)$p['spins'] ?></td>
      <td><?= number_format((float)$p['bet'], 2) ?> ₽</td>
      <td>×<?= (int)$p['wager_mult'] ?></td>
      <td><?= (int)$p['used'] ?></td>
      <td><?= (int)$p['max_uses'] > 0 ? (int)$p['max_uses'] : '∞' ?></td>
      <td style="font-size:12px;color:var(--muted)"><?= $p['expires_at'] ?: '—' ?></td>
      <td><button class="btn btn-danger btn-sm" data-testid="fs-delete-<?= (int)$p['id'] ?>" onclick="fsDel(<?= (int)$p['id'] ?>)">Удалить</button></td>
    </tr>
    <?php endforeach; endif ?>
  </table>
</div>

<div class="aw-table-wrap">
  <div class="tw-head" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
    <h2>Выданные фриспины</h2>
    <form method="get" style="display:flex;gap:8px">
      <input name="gq" value="<?= htmlspecialchars($gq) ?>" class="form-control" style="width:240px" data-testid="fs-grant-search" placeholder="ID, ID игрока, логин, код…">
      <button class="btn btn-primary btn-sm">Найти</button>
    </form>
  </div>
  <table class="aw-table" data-testid="fs-grant-table">
    <tr><th>ID</th><th>Игрок</th><th>Код</th><th>Слот</th><th>Спины × ставка</th><th>Выигрыш</th><th>Статус</th><th>Дата</th><th></th></tr>
    <?php if (!$grants): ?><tr><td colspan="9" style="text-align:center;color:var(--muted);padding:24px">Пока нет</td></tr>
    <?php else: foreach ($grants as $g): [$bc, $bl] = $stl[$g['status']] ?? ['badge-blue', $g['status']]; ?>
    <tr>
      <td style="color:var(--muted)">#<?= (int)$g['id'] ?></td>
      <td><a href="/admin/users?uid=<?= (int)$g['user_id'] ?>"><?= htmlspecialchars($g['login'] ?? '') ?> #<?= (int)$g['user_id'] ?></a></td>
      <td style="font-family:monospace"><?= htmlspecialchars($g['code'] ?? '') ?></td>
      <td style="font-size:12px"><?= htmlspecialchars($g['public_id']) ?></td>
      <td><?= (int)$g['spins'] ?> × <?= (float)$g['bet'] ?> ₽</td>
      <td><?= number_format($g['win_total'] / 1000000, 2) ?> ₽</td>
      <td><span class="badge <?= $bc ?>"><?= $bl ?></span><?php if ($g['error']): ?><br><small style="color:var(--red)"><?= htmlspecialchars($g['error']) ?></small><?php endif ?></td>
      <td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($g['created_at']) ?></td>
      <td><?php if ($g['status'] === 'active' && $g['freespin_id']): ?><button class="btn btn-ghost btn-sm" onclick="fsRemove(<?= (int)$g['id'] ?>)">Снять</button><?php endif ?></td>
    </tr>
    <?php endforeach; endif ?>
  </table>
</div>

<div class="aw-modal-bg" id="fsModal">
  <div class="aw-modal">
    <h2>Новый промокод на фриспины</h2>
    <div class="form-group" style="position:relative">
      <label>Слот (поиск по названию, только игры с фриспинами)</label>
      <input type="text" id="fs_game_q" class="form-control" data-testid="fs-game-search" placeholder="Например, Sweet Bonanza" autocomplete="off" oninput="fsSearch(this.value)">
      <input type="hidden" id="fs_game">
      <div id="fs_game_list" data-testid="fs-game-results" style="max-height:220px;overflow-y:auto;margin-top:6px"></div>
      <div id="fs_game_sel" data-testid="fs-game-selected" style="font-size:12px;color:var(--green);margin-top:4px"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Код</label><input type="text" id="fs_code" class="form-control" data-testid="fs-code" placeholder="SPINS50"></div>
      <div class="form-group"><label>Количество спинов</label><input type="number" id="fs_spins" class="form-control" data-testid="fs-spins" value="10" min="1" max="500"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Ставка за спин, ₽</label><input type="number" id="fs_bet" class="form-control" data-testid="fs-bet" value="10" step="0.01" min="0.01"></div>
      <div class="form-group"><label>Вагер на выигрыш (×)</label><input type="number" id="fs_wager" class="form-control" data-testid="fs-wager" value="20" min="1" max="100"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Количество активаций (0 = без лимита)</label><input type="number" id="fs_limit" class="form-control" data-testid="fs-limit" value="100" min="0"></div>
      <div class="form-group"><label>Действует до (пусто = бессрочно)</label><input type="date" id="fs_exp" class="form-control" data-testid="fs-expires"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="document.getElementById('fsModal').classList.remove('open')">Отмена</button>
      <button class="btn btn-primary" data-testid="fs-create-btn" onclick="fsCreate()">Создать</button>
    </div>
  </div>
</div>

<script>
function fsAlert(m, t) { var e = document.getElementById('fsAlert'); e.textContent = m; e.className = 'aw-alert show ' + t; setTimeout(() => e.className = 'aw-alert', 4000); }
var fsT;
function fsSearch(v) {
  clearTimeout(fsT);
  fsT = setTimeout(() => bwPost({ type: 'wgcGameSearch', q: v }).then(d => {
    var box = document.getElementById('fs_game_list'); box.innerHTML = '';
    (d.items || []).forEach(g => {
      var a = document.createElement('div');
      a.style.cssText = 'display:flex;gap:8px;align-items:center;padding:6px;border-radius:8px;cursor:pointer';
      a.onmouseover = () => a.style.background = 'rgba(224,36,94,.15)'; a.onmouseout = () => a.style.background = '';
      a.innerHTML = '<img src="' + g.image + '" style="width:36px;height:36px;border-radius:6px;object-fit:cover"><span></span>';
      a.querySelector('span').textContent = g.name + ' · ' + g.brand;
      a.onclick = () => { document.getElementById('fs_game').value = g.public_id; document.getElementById('fs_game_sel').textContent = 'Выбрано: ' + g.name + ' (' + g.brand + ')'; box.innerHTML = ''; };
      box.appendChild(a);
    });
    if (!(d.items || []).length) box.innerHTML = '<div style="font-size:12px;color:var(--muted);padding:6px">Ничего не найдено</div>';
  }), 250);
}
function fsCreate() {
  bwPost({ type: 'addFsPromo', code: fs_code.value.trim(), public_id: fs_game.value, spins: fs_spins.value, bet: fs_bet.value,
           wager_mult: fs_wager.value, max_uses: fs_limit.value, expires_at: fs_exp.value })
    .then(d => { if (d.success === 'success') { fsAlert('Создано', 'success'); setTimeout(() => location.reload(), 800); } else fsAlert(d.error || 'Ошибка', 'error'); });
}
function fsDel(id) { if (!confirm('Удалить промокод?')) return; bwPost({ type: 'deleteFsPromo', id: id }).then(() => location.reload()); }
function fsRemove(id) { if (!confirm('Снять фриспины у игрока?')) return; bwPost({ type: 'removeFsGrant', id: id }).then(d => { if (d.success === 'success') location.reload(); else fsAlert(d.error || 'Ошибка', 'error'); }); }
document.getElementById('fsModal').addEventListener('click', function (e) { if (e.target === this) this.classList.remove('open'); });
</script>
<?php require 'admin-foot.php'; ?>
