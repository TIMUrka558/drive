<?php
session_start();
require '../inc/mysql_compat.php';
require '../inc/bd.php';
$page_title = 'Settings';
require 'admin-head.php';

$cfg = mysqli_fetch_assoc(mysqli_query($__conn, "SELECT * FROM kot_config LIMIT 1"));
?>

<div id="settAlert" class="aw-alert"></div>

<!-- Site settings -->
<div class="form-section">
  <h2>Site Settings</h2>
  <div class="form-row">
    <div class="form-group">
      <label>Site name</label>
      <input type="text" id="sitename" class="form-control"
             value="<?= htmlspecialchars($cfg['sitename'] ?? 'FastWin') ?>">
    </div>
    <div class="form-group">
      <label>Site domain</label>
      <input type="text" id="sitedomen" class="form-control"
             value="<?= htmlspecialchars($cfg['sitedomen'] ?? '') ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Registration bonus (₽)</label>
      <input type="number" id="bonusreg" class="form-control"
             value="<?= (float)($cfg['bonus_reg'] ?? 0) ?>" min="0" step="0.01">
    </div>
    <div class="form-group">
      <label>Min withdrawal (₽)</label>
      <input type="number" id="minwithdraw" class="form-control"
             value="<?= (float)($cfg['min_withdraw_sum'] ?? 300) ?>" min="0">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Min bet (₽)</label>
      <input type="number" id="minbet" class="form-control"
             value="<?= (float)($cfg['min_bet'] ?? 1) ?>" min="0">
    </div>
    <div class="form-group">
      <label>Max bet (₽)</label>
      <input type="number" id="maxbet" class="form-control"
             value="<?= (float)($cfg['max_bet'] ?? 1000000) ?>" min="0">
    </div>
  </div>
  <button class="btn btn-primary" onclick="saveSite()">Save Site Settings</button>
</div>

<!-- Cashback & Referral -->
<div class="form-section" data-testid="admin-payments-section">
  <h2>Крипто-платежи (CryptoBot / xRocket)</h2>
  <?php require_once __DIR__ . '/../inc/payments.php'; $rbp = rb_providers(); ?>
  <p style="font-size:12px;color:var(--muted);margin-bottom:12px">
    CryptoBot: <b style="color:<?= $rbp['cryptobot'] ? '#2fd187' : '#ff5876' ?>"><?= $rbp['cryptobot'] ? 'подключён' : 'нет токена' ?></b> ·
    xRocket: <b style="color:<?= $rbp['xrocket'] ? '#2fd187' : '#ff5876' ?>"><?= $rbp['xrocket'] ? 'подключён' : 'нет токена' ?></b><br>
    Webhook CryptoBot: <code><?= htmlspecialchars(rb_site_url()) ?>/payhook.php?p=cryptobot</code><br>
    Webhook xRocket: <code><?= htmlspecialchars(rb_site_url()) ?>/payhook.php?p=xrocket</code>
  </p>
  <div class="form-row">
    <div class="form-group"><label>Курс 1 USDT в ₽</label><input type="number" id="usdt_rub" class="form-control" step="0.01" value="<?= (float)($cfg['usdt_rub'] ?? 95) ?>"></div>
    <div class="form-group"><label>Мин. депозит, ₽</label><input type="number" id="min_sum_dep" class="form-control" step="1" value="<?= (float)($cfg['min_sum_dep'] ?? 100) ?>"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Ежедневный бонус от, ₽</label><input type="number" id="bonus_min" class="form-control" step="1" value="<?= (int)($cfg['min_bonus_size'] ?? 10) ?>"></div>
    <div class="form-group"><label>Ежедневный бонус до, ₽</label><input type="number" id="bonus_max" class="form-control" step="1" value="<?= (int)($cfg['max_bonus_size'] ?? 25) ?>"></div>
  </div>
  <button class="btn btn-primary" onclick="bwPost({type:'savePayments',usdt_rub:usdt_rub.value,min_sum_dep:min_sum_dep.value,bonus_min:bonus_min.value,bonus_max:bonus_max.value}).then(d=>bwAlert(d.success==='success'?'Saved!':(d.error||'Error'),d.success==='success'?'success':'error'))" data-testid="admin-save-payments">Save</button>
</div>

<div class="form-section">
  <h2>Cashback &amp; Referral</h2>
  <div class="form-row">
    <div class="form-group">
      <label>Cashback % (site-wide default)</label>
      <input type="number" id="cashback_pct" class="form-control"
             value="<?= (float)($cfg['cashback_pct'] ?? 5) ?>" min="0" max="100" step="0.1">
    </div>
    <div class="form-group">
      <label>Referral reward % of deposits</label>
      <input type="number" id="ref_pct" class="form-control"
             value="<?= (float)($cfg['ref_pct'] ?? 5) ?>" min="0" max="100" step="0.1">
    </div>
  </div>
  <button class="btn btn-primary" onclick="saveCashback()">Save</button>
</div>

<!-- Withdrawal Fees -->
<div class="form-section">
  <h2>Withdrawal Fees</h2>
  <div class="form-row">
    <div class="form-group">
      <label>QIWI fee (%)</label>
      <input type="number" id="fee_qiwi" class="form-control"
             value="<?= (float)($cfg['fee_qiwi'] ?? 0) ?>" min="0" max="100" step="0.1">
    </div>
    <div class="form-group">
      <label>Card fee (%)</label>
      <input type="number" id="fee_card" class="form-control"
             value="<?= (float)($cfg['fee_card'] ?? 0) ?>" min="0" max="100" step="0.1">
    </div>
    <div class="form-group">
      <label>Crypto fee (%)</label>
      <input type="number" id="fee_crypto" class="form-control"
             value="<?= (float)($cfg['fee_crypto'] ?? 0) ?>" min="0" max="100" step="0.1">
    </div>
  </div>
  <button class="btn btn-primary" onclick="saveFees()">Save Fees</button>
</div>

<!-- Integrations -->
<div class="form-section">
  <h2>Integrations</h2>
  <div class="form-row">
    <div class="form-group">
      <label>Support link / VK group URL</label>
      <input type="text" id="sitesupport" class="form-control"
             value="<?= htmlspecialchars($cfg['sitesupport'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>FreKassa Wallet ID</label>
      <input type="text" id="fkid" class="form-control"
             value="<?= htmlspecialchars($cfg['fk_id'] ?? '') ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>FK Secret 1</label>
      <input type="password" id="fksecret1" class="form-control"
             value="<?= htmlspecialchars($cfg['fk_secret_1'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>FK Secret 2</label>
      <input type="password" id="fksecret2" class="form-control"
             value="<?= htmlspecialchars($cfg['fk_secret_2'] ?? '') ?>">
    </div>
  </div>
  <button class="btn btn-primary" onclick="saveIntegrations()">Save Integrations</button>
</div>

<script>
function bwAlert(msg, type) {
  var el = document.getElementById('settAlert');
  el.textContent = msg;
  el.className = 'aw-alert show ' + type;
  setTimeout(() => el.className = 'aw-alert', 3000);
}
function saveSite() {
  bwPost({
    type: 'saveSiteSettings',
    sitename:    document.getElementById('sitename').value,
    sitedomen:   document.getElementById('sitedomen').value,
    bonusreg:    document.getElementById('bonusreg').value,
    minwithdraw: document.getElementById('minwithdraw').value,
    minbet:      document.getElementById('minbet').value,
    maxbet:      document.getElementById('maxbet').value
  }).then(d => {
    if (d.success === 'success') bwAlert('Saved!', 'success');
    else bwAlert(d.error || 'Error', 'error');
  }).catch(() => bwAlert('Request failed', 'error'));
}
function saveCashback() {
  bwPost({
    type: 'saveCashback',
    cashback_pct: document.getElementById('cashback_pct').value,
    ref_pct:      document.getElementById('ref_pct').value
  }).then(d => {
    if (d.success === 'success') bwAlert('Saved!', 'success');
    else bwAlert(d.error || 'Error', 'error');
  }).catch(() => bwAlert('Request failed', 'error'));
}
function saveFees() {
  bwPost({
    type: 'saveFees',
    fee_qiwi:   document.getElementById('fee_qiwi').value,
    fee_card:   document.getElementById('fee_card').value,
    fee_crypto: document.getElementById('fee_crypto').value
  }).then(d => {
    if (d.success === 'success') bwAlert('Saved!', 'success');
    else bwAlert(d.error || 'Error', 'error');
  }).catch(() => bwAlert('Request failed', 'error'));
}
function saveIntegrations() {
  bwPost({
    type: 'saveIntegrations',
    sitesupport: document.getElementById('sitesupport').value,
    fkid:        document.getElementById('fkid').value,
    fksecret1:   document.getElementById('fksecret1').value,
    fksecret2:   document.getElementById('fksecret2').value
  }).then(d => {
    if (d.success === 'success') bwAlert('Saved!', 'success');
    else bwAlert(d.error || 'Error', 'error');
  }).catch(() => bwAlert('Request failed', 'error'));
}
</script>
<?php require 'admin-foot.php'; ?>
