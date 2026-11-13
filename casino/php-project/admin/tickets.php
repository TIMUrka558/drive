<?php
session_start();
require '../inc/mysql_compat.php';
require '../inc/bd.php';
$page_title = 'Tickets';
require 'admin-head.php';

$filter  = in_array($_GET['filter'] ?? '', ['open','answered','closed','all'])
           ? $_GET['filter'] : 'open';
$view_id = (int)($_GET['tid'] ?? 0);

$where   = ($filter === 'all') ? '' : "WHERE t.status='$filter'";
$tq      = mysqli_query($__conn,
    "SELECT t.id, t.subject, t.status, t.updated_at, u.login
     FROM kot_tickets t
     JOIN kot_user u ON u.id=t.user_id
     $where
     ORDER BY t.updated_at DESC LIMIT 100");
$tickets = [];
while ($r = mysqli_fetch_assoc($tq)) $tickets[] = $r;

$open_count = (int) mysqli_fetch_assoc(mysqli_query($__conn,
    "SELECT COUNT(*) c FROM kot_tickets WHERE status='open'"))['c'];

$view_ticket = null;
$view_msgs   = [];
if ($view_id > 0) {
    $view_ticket = mysqli_fetch_assoc(mysqli_query($__conn,
        "SELECT t.*, u.login FROM kot_tickets t
         JOIN kot_user u ON u.id=t.user_id
         WHERE t.id=$view_id"));
    if ($view_ticket) {
        $mq = mysqli_query($__conn,
            "SELECT m.*, u.login FROM kot_ticket_msgs m
             JOIN kot_user u ON u.id=m.user_id
             WHERE m.ticket_id=$view_id ORDER BY m.created_at ASC");
        while ($r = mysqli_fetch_assoc($mq)) $view_msgs[] = $r;
    }
}

$filter_tabs = [
    'open'     => 'Open ('.$open_count.')',
    'answered' => 'Answered',
    'closed'   => 'Closed',
    'all'      => 'All',
];
?>

<div id="tkAlert" class="aw-alert"></div>

<!-- Filter tabs -->
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
  <?php foreach ($filter_tabs as $f => $label): ?>
  <a href="/admin/tickets?filter=<?= $f ?><?= $view_id ? '&tid='.$view_id : '' ?>"
     class="btn <?= $filter === $f ? 'btn-primary' : 'btn-ghost' ?> btn-sm">
    <?= $label ?>
  </a>
  <?php endforeach ?>
</div>

<div style="display:grid;grid-template-columns:<?= $view_ticket ? '1fr 1fr' : '1fr' ?>;gap:20px">

<!-- Ticket list -->
<div class="aw-table-wrap">
  <div class="tw-head"><h2>Tickets (<?= count($tickets) ?>)</h2></div>
  <table class="aw-table">
    <tr><th>ID</th><th>Player</th><th>Subject</th><th>Status</th><th>Updated</th></tr>
    <?php if (empty($tickets)): ?>
    <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:24px">No tickets</td></tr>
    <?php else: foreach ($tickets as $t):
      $status_badge = match ($t['status']) {
          'answered' => '<span class="badge badge-green">Answered</span>',
          'closed'   => '<span class="badge" style="background:rgba(139,148,158,.15);color:var(--muted)">Closed</span>',
          default    => '<span class="badge badge-yellow">Open</span>',
      };
    ?>
    <tr onclick="location.href='/admin/tickets?tid=<?= (int)$t['id'] ?>&filter=<?= $filter ?>'"
        style="cursor:pointer;<?= $view_id === (int)$t['id'] ? 'background:rgba(26,111,255,.08)' : '' ?>">
      <td style="color:var(--muted)">#<?= (int)$t['id'] ?></td>
      <td><?= htmlspecialchars($t['login']) ?></td>
      <td><?= htmlspecialchars(mb_substr($t['subject'], 0, 40)) ?></td>
      <td><?= $status_badge ?></td>
      <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($t['updated_at']) ?></td>
    </tr>
    <?php endforeach; endif ?>
  </table>
</div>

<!-- Ticket detail -->
<?php if ($view_ticket): ?>
<div class="form-section" style="display:flex;flex-direction:column;height:fit-content">
  <h2>
    Ticket #<?= (int)$view_ticket['id'] ?> — <?= htmlspecialchars($view_ticket['subject']) ?>
    <span style="float:right;font-size:12px;font-weight:400;color:var(--muted)">
      <?= htmlspecialchars($view_ticket['login']) ?>
    </span>
  </h2>

  <!-- Messages -->
  <div id="msg-box" style="max-height:380px;overflow-y:auto;margin-bottom:16px;display:flex;flex-direction:column;gap:8px">
    <?php foreach ($view_msgs as $m):
      $is_admin = (int)$m['is_admin'];
    ?>
    <div style="display:flex;justify-content:<?= $is_admin ? 'flex-end' : 'flex-start' ?>">
      <div style="
        max-width:78%;padding:10px 14px;border-radius:12px;
        background:<?= $is_admin ? 'var(--accent)' : 'rgba(255,255,255,.06)' ?>;
        color:<?= $is_admin ? '#fff' : 'var(--text)' ?>;
      ">
        <div style="font-size:11px;font-weight:600;margin-bottom:4px;opacity:.7">
          <?= $is_admin ? 'Admin' : htmlspecialchars($m['login']) ?>
          · <?= htmlspecialchars($m['created_at']) ?>
        </div>
        <div style="font-size:13px;white-space:pre-wrap"><?= htmlspecialchars($m['message']) ?></div>
      </div>
    </div>
    <?php endforeach ?>
    <?php if (empty($view_msgs)): ?>
    <div style="color:var(--muted);text-align:center;padding:16px">No messages</div>
    <?php endif ?>
  </div>

  <?php if ($view_ticket['status'] !== 'closed'): ?>
  <!-- Reply form -->
  <div style="border-top:1px solid var(--border);padding-top:14px">
    <textarea id="admin-reply-msg" class="form-control"
              style="resize:vertical;min-height:80px;margin-bottom:10px"
              placeholder="Type your reply… (Ctrl+Enter to send)"></textarea>
    <div style="display:flex;gap:8px">
      <button class="btn btn-primary btn-sm" id="reply-btn"
              onclick="adminReply(<?= (int)$view_ticket['id'] ?>)">Send Reply</button>
      <button class="btn btn-danger btn-sm"
              onclick="adminClose(<?= (int)$view_ticket['id'] ?>)">Close Ticket</button>
    </div>
  </div>
  <?php else: ?>
  <div style="color:var(--muted);font-size:13px;padding:10px 0;border-top:1px solid var(--border)">
    This ticket is closed — no further replies.
  </div>
  <?php endif ?>
</div>
<?php endif ?>

</div>

<script>
function bwAlert(msg, type) {
  var el = document.getElementById('tkAlert');
  el.textContent = msg;
  el.className = 'aw-alert show ' + type;
  setTimeout(() => el.className = 'aw-alert', 3500);
}
function adminReply(tid) {
  var msg = document.getElementById('admin-reply-msg').value.trim();
  if (!msg) { bwAlert('Enter a message', 'error'); return; }
  var btn = document.getElementById('reply-btn');
  btn.disabled = true;
  btn.textContent = 'Sending…';
  bwPost({ type: 'adminReplyTicket', ticket_id: tid, message: msg })
    .then(d => {
      btn.disabled = false; btn.textContent = 'Send Reply';
      if (d.success === 'success') location.reload();
      else bwAlert(d.error || 'Error', 'error');
    }).catch(() => { btn.disabled=false; btn.textContent='Send Reply'; bwAlert('Request failed','error'); });
}
function adminClose(tid) {
  if (!confirm('Close this ticket?')) return;
  bwPost({ type: 'adminCloseTicket', ticket_id: tid })
    .then(d => {
      if (d.success === 'success') location.reload();
      else bwAlert(d.error || 'Error', 'error');
    }).catch(() => bwAlert('Request failed', 'error'));
}
// Ctrl+Enter shortcut
var ta = document.getElementById('admin-reply-msg');
if (ta) ta.addEventListener('keydown', function(e) {
  if (e.ctrlKey && e.key === 'Enter') adminReply(<?= (int)$view_id ?>);
});
// Scroll to bottom
var mb = document.getElementById('msg-box');
if (mb) mb.scrollTop = mb.scrollHeight;
</script>
<?php require 'admin-foot.php'; ?>
