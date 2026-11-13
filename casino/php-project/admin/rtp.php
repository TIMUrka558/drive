<?php
session_start();
require '../inc/mysql_compat.php';
require '../inc/bd.php';
$page_title = 'Games / RTP';
require 'admin-head.php';

$cfg   = mysqli_fetch_assoc(mysqli_query($__conn, "SELECT * FROM kot_config LIMIT 1"));
// Games tracked: dice(1), mines(2), x50(4). Bubbles column kept for future.
$games = ['dice', 'mines', 'bubbles', 'x50'];
?>

<div id="rtpAlert" class="aw-alert"></div>

<!-- RTP Settings -->
<div class="form-section" style="margin-bottom:20px">
  <h2>RTP per game (target return-to-player %)</h2>
  <p style="color:var(--muted);font-size:12px;margin-bottom:16px">
    Controls how much players win back on average. 97% means the house keeps 3% of all bets.
  </p>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px">
    <?php foreach ($games as $g): $val = (float)($cfg['rtp_' . $g] ?? 97); ?>
    <div>
      <label style="font-size:12px;color:var(--muted);display:flex;justify-content:space-between;margin-bottom:6px">
        <strong><?= strtoupper($g) ?></strong>
        <span id="rtpVal_<?= $g ?>"><?= $val ?>%</span>
      </label>
      <input type="range" min="1" max="99" step="0.5" value="<?= $val ?>"
             id="rtp_<?= $g ?>"
             oninput="document.getElementById('rtpVal_<?= $g ?>').textContent=this.value+'%'"
             style="width:100%;accent-color:var(--accent)">
      <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--muted);margin-top:2px">
        <span>1%</span><span>50%</span><span>99%</span>
      </div>
    </div>
    <?php endforeach ?>
  </div>
  <button class="btn btn-primary" style="margin-top:16px" onclick="saveRTP()">Save RTP Settings</button>
</div>

<!-- Anti-minus Settings -->
<div class="form-section">
  <h2>Anti-minus — force win after threshold loss</h2>
  <p style="color:var(--muted);font-size:12px;margin-bottom:16px">
    When a player's cumulative session loss reaches the threshold, their next bet is forced to win.
    Session resets on win.
  </p>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px">
    <?php foreach ($games as $g):
      $on     = (int)($cfg['antim_' . $g] ?? 0);
      $thresh = (float)($cfg['antim_thresh_' . $g] ?? 1000);
    ?>
    <div class="aw-card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <strong><?= strtoupper($g) ?></strong>
        <label class="aw-toggle">
          <input type="checkbox" id="antim_<?= $g ?>" <?= $on ? 'checked' : '' ?>>
          <span class="slider"></span>
        </label>
      </div>
      <div class="form-group">
        <label>Loss threshold (₽)</label>
        <input type="number" id="thresh_<?= $g ?>" class="form-control"
               value="<?= $thresh ?>" min="0" step="50">
      </div>
      <div style="font-size:11px;color:var(--muted);margin-top:8px">
        Force win when session loss ≥ threshold
      </div>
    </div>
    <?php endforeach ?>
  </div>
  <button class="btn btn-primary" style="margin-top:16px" onclick="saveAntiMinus()">Save Anti-minus Settings</button>
</div>

<div class="form-section" style="margin-top:20px">
  <h2>Max win per bet</h2>
  <p style="color:var(--muted);font-size:12px;margin-bottom:12px">Bets with a possible win above this limit are rejected (Dice, Bubbles, X50). 0 = no limit.</p>
  <input type="number" id="max_win" class="form-control" style="max-width:220px" min="0" step="100" value="<?= (float)($cfg['max_win'] ?? 0) ?>">
</div>

<script>
function bwAlert(msg, type) {
  var el = document.getElementById('rtpAlert');
  el.textContent = msg;
  el.className = 'aw-alert show ' + type;
  setTimeout(() => el.className = 'aw-alert', 3000);
}
function saveRTP() {
  var data = { type: 'saveRTP', max_win: document.getElementById('max_win').value };
  <?php foreach ($games as $g): ?>
  data['rtp_<?= $g ?>'] = document.getElementById('rtp_<?= $g ?>').value;
  <?php endforeach ?>
  bwPost(data).then(d => {
    if (d.success === 'success') bwAlert('Saved!', 'success');
    else bwAlert(d.error || 'Error', 'error');
  }).catch(() => bwAlert('Request failed', 'error'));
}
function saveAntiMinus() {
  var data = { type: 'saveAntiMinus' };
  <?php foreach ($games as $g): ?>
  data['antim_<?= $g ?>'] = document.getElementById('antim_<?= $g ?>').checked ? 1 : 0;
  data['thresh_<?= $g ?>'] = document.getElementById('thresh_<?= $g ?>').value;
  <?php endforeach ?>
  bwPost(data).then(d => {
    if (d.success === 'success') bwAlert('Saved!', 'success');
    else bwAlert(d.error || 'Error', 'error');
  }).catch(() => bwAlert('Request failed', 'error'));
}
</script>
<?php require 'admin-foot.php'; ?>
