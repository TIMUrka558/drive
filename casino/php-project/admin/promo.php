<?php
session_start();
require '../inc/mysql_compat.php';
require '../inc/bd.php';
$page_title = 'Promo Codes';
require 'admin-head.php';

$promos_q = mysqli_query($__conn, "SELECT * FROM kot_promo ORDER BY id DESC");
$promos   = [];
while ($r = mysqli_fetch_assoc($promos_q)) $promos[] = $r;
?>

<div style="margin-bottom:16px;display:flex;justify-content:flex-end">
  <button class="btn btn-primary"
          onclick="document.getElementById('addPromoModal').classList.add('open')">+ Add Promo Code</button>
</div>

<div id="promoAlert" class="aw-alert"></div>

<div class="aw-table-wrap">
  <div class="tw-head"><h2>All Promo Codes (<?= count($promos) ?>)</h2></div>
  <table class="aw-table">
    <tr><th>Code</th><th>Type</th><th>Amount</th><th>Wager ×</th><th>Used</th><th>Limit</th><th>Expires</th><th></th></tr>
    <?php if (empty($promos)): ?>
    <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px">No promo codes yet</td></tr>
    <?php else: foreach ($promos as $p): ?>
    <tr>
      <td><strong style="font-family:monospace"><?= htmlspecialchars($p['name']) ?></strong></td>
      <td>
        <span class="badge <?= ($p['promo_type'] ?? 'deposit') === 'nodeposit' ? 'badge-yellow' : 'badge-blue' ?>">
          <?= htmlspecialchars($p['promo_type'] ?? 'deposit') ?>
        </span>
      </td>
      <td>
        <?php if ((float)($p['min_amount'] ?? 0) > 0 && (float)($p['max_amount'] ?? 0) > 0): ?>
          <?= $p['min_amount'] ?>–<?= $p['max_amount'] ?> ₽
        <?php else: ?>
          <?= number_format((float)$p['sum'], 2) ?> ₽
        <?php endif ?>
      </td>
      <td>×<?= (int)($p['wager_mult'] ?? 1) ?></td>
      <td><?= (int)$p['actived'] ?></td>
      <td><?= (int)$p['active'] > 0 ? $p['active'] : '∞' ?></td>
      <td style="color:var(--muted);font-size:12px">
        <?= $p['expires_at'] ? htmlspecialchars($p['expires_at']) : '—' ?>
      </td>
      <td>
        <button class="btn btn-danger btn-sm" onclick="deletePromo(<?= (int)$p['id'] ?>)">Delete</button>
      </td>
    </tr>
    <?php endforeach; endif ?>
  </table>
</div>

<!-- Add Promo Modal -->
<div class="aw-modal-bg" id="addPromoModal">
  <div class="aw-modal">
    <h2>New Promo Code</h2>
    <div class="form-row">
      <div class="form-group">
        <label>Code name</label>
        <input type="text" id="pm_code" class="form-control" placeholder="FASTWIN100">
      </div>
      <div class="form-group">
        <label>Type</label>
        <select id="pm_type" class="form-control">
          <option value="deposit">Deposit bonus</option>
          <option value="nodeposit">No-deposit (free bonus)</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Fixed amount ₽ (0 = use random range)</label>
        <input type="number" id="pm_sum" class="form-control" value="100" step="0.01" min="0">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Random min ₽ (optional)</label>
        <input type="number" id="pm_min" class="form-control" value="0" step="0.01" min="0">
      </div>
      <div class="form-group">
        <label>Random max ₽ (optional)</label>
        <input type="number" id="pm_max" class="form-control" value="0" step="0.01" min="0">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Wager multiplier</label>
        <input type="number" id="pm_wager" class="form-control" value="3" min="1" max="100">
      </div>
      <div class="form-group">
        <label>Max uses (0 = unlimited)</label>
        <input type="number" id="pm_limit" class="form-control" value="0" min="0">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Expires (YYYY-MM-DD, blank = never)</label>
        <input type="date" id="pm_expires" class="form-control">
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost"
              onclick="document.getElementById('addPromoModal').classList.remove('open')">Cancel</button>
      <button class="btn btn-primary" onclick="addPromo()">Create</button>
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
  if (!code) { bwAlert('Enter a code name', 'error'); return; }
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
      bwAlert('Promo created!', 'success');
      setTimeout(() => location.reload(), 900);
    } else {
      bwAlert(d.error || 'Error', 'error');
    }
  }).catch(() => bwAlert('Request failed', 'error'));
}
function deletePromo(id) {
  if (!confirm('Delete this promo code?')) return;
  bwPost({ type: 'deletePromo', id: id })
    .then(d => {
      if (d.success === 'success') {
        bwAlert('Deleted', 'success');
        setTimeout(() => location.reload(), 700);
      } else {
        bwAlert(d.error || 'Error', 'error');
      }
    }).catch(() => bwAlert('Request failed', 'error'));
}
document.getElementById('addPromoModal').addEventListener('click', function (e) {
  if (e.target === this) this.classList.remove('open');
});
</script>
<?php require 'admin-foot.php'; ?>
