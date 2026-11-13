<?php
/**
 * RUBIN Admin — shared layout header
 * Include at the top of every admin page.
 * Expects: $page_title (string)
 */

if (!isset($page_title)) $page_title = 'Admin';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) session_start();

// Auth check
$sid = $_SESSION['hash'] ?? '';
$_aq = mysqli_query($__conn,
    "SELECT admin, login FROM kot_user WHERE hash='" . mysqli_real_escape_string($__conn, $sid) . "'"
);
$_ar = mysqli_fetch_assoc($_aq);
if (!$_ar || (int)$_ar['admin'] !== 1) {
    require __DIR__ . '/login-view.php'; exit;
}
$admin_login = $_ar['login'];

// CSRF token — regenerate once per session
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}
$_csrf = $_SESSION['admin_csrf'];

$nav = [
    ['href' => '/admin',           'icon' => '📊', 'label' => 'Обзор'],
    ['href' => '/admin/find',      'icon' => '🔎', 'label' => 'Поиск по ID'],
    ['href' => '/admin/users',     'icon' => '👤', 'label' => 'Игроки'],
    ['href' => '/admin/stat',      'icon' => '📈', 'label' => 'Статистика'],
    ['href' => '/admin/rtp',       'icon' => '🎰', 'label' => 'Игры / RTP'],
    ['href' => '/admin/slots',     'icon' => '🎰', 'label' => 'Слоты (WGC)'],
    ['href' => '/admin/slotpromo', 'icon' => '🎡', 'label' => 'Промо на слоты'],
    ['href' => '/admin/promo',     'icon' => '🎁', 'label' => 'Промокоды'],
    ['href' => '/admin/withdraws', 'icon' => '💸', 'label' => 'Выводы'],
    ['href' => '/admin/deps',      'icon' => '💰', 'label' => 'Депозиты'],
    ['href' => '/admin/tickets',   'icon' => '🎫', 'label' => 'Тикеты'],
    ['href' => '/admin/settings',  'icon' => '⚙️', 'label' => 'Настройки'],
];

$cur = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf" content="<?= htmlspecialchars($_csrf) ?>">
<title><?= htmlspecialchars($page_title) ?> — RUBIN Admin</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0d0c11;--sidebar:#141218;--card:#1a1720;--border:#2c2733;
  --accent:#e0245e;--accent2:#a8143f;--text:#e6edf3;--muted:#8b949e;
  --red:#f85149;--green:#3fb950;--yellow:#d29922;--sidebar-w:220px;
}
html,body{height:100%;font-family:'Inter',-apple-system,sans-serif;background:var(--bg);color:var(--text);font-size:14px}
a{color:var(--accent);text-decoration:none}
.aw-wrap{display:flex;min-height:100vh}
.aw-sidebar{
  width:var(--sidebar-w);flex-shrink:0;background:var(--sidebar);
  border-right:1px solid var(--border);display:flex;flex-direction:column;
  position:fixed;left:0;top:0;bottom:0;overflow-y:auto;z-index:100
}
.aw-logo{padding:18px 16px 14px;font-size:18px;font-weight:800;letter-spacing:-.5px;border-bottom:1px solid var(--border);color:#fff}
.aw-logo span{color:var(--accent)}
.aw-nav{list-style:none;padding:8px 0;flex:1}
.aw-nav li a{
  display:flex;align-items:center;gap:10px;padding:9px 16px;
  color:var(--muted);font-size:13px;font-weight:500;
  border-radius:6px;margin:1px 8px;transition:background .15s,color .15s
}
.aw-nav li a:hover{background:rgba(224,36,94,.1);color:var(--text)}
.aw-nav li a.active{background:rgba(224,36,94,.18);color:var(--accent)}
.aw-nav li a .ni{font-size:15px;width:20px;text-align:center}
.aw-sidebar-foot{padding:12px 16px;border-top:1px solid var(--border);font-size:12px;color:var(--muted)}
.aw-main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh}
.aw-topbar{
  padding:14px 24px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  background:var(--sidebar);position:sticky;top:0;z-index:50
}
.aw-topbar h1{font-size:16px;font-weight:700}
.aw-topbar .tb-right{display:flex;align-items:center;gap:12px;font-size:13px;color:var(--muted)}
.aw-topbar .tb-right a{color:var(--accent)}
.aw-content{padding:24px;flex:1;min-width:0;max-width:100%}
.aw-content [style*="grid"] > *, .aw-cards > *{min-width:0}
.aw-main{min-width:0}
.aw-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:24px}
.aw-card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:18px 16px}
.aw-card h3{font-size:12px;color:var(--muted);font-weight:500;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.aw-card .val{font-size:26px;font-weight:700;color:var(--text)}
.aw-card .sub{font-size:12px;color:var(--muted);margin-top:4px}
.aw-table-wrap{background:var(--card);border:1px solid var(--border);border-radius:10px;overflow:hidden}
.aw-table-wrap .tw-head{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.aw-table-wrap .tw-head h2{font-size:14px;font-weight:600}
.aw-table{width:100%;border-collapse:collapse}
.aw-table th{background:rgba(255,255,255,.03);padding:10px 14px;text-align:left;font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border)}
.aw-table td{padding:10px 14px;border-bottom:1px solid rgba(48,54,61,.5);font-size:13px;vertical-align:middle}
.aw-table tr:last-child td{border-bottom:none}
.aw-table tr:hover td{background:rgba(255,255,255,.02)}
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600}
.badge-green{background:rgba(63,185,80,.15);color:var(--green)}
.badge-red{background:rgba(248,81,73,.15);color:var(--red)}
.badge-yellow{background:rgba(210,153,34,.15);color:var(--yellow)}
.badge-blue{background:rgba(224,36,94,.15);color:var(--accent)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:opacity .15s}
.btn:hover{opacity:.85}
.btn-primary{background:var(--accent);color:#fff}
.btn-danger{background:var(--red);color:#fff}
.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--text)}
.btn-sm{padding:4px 10px;font-size:12px;border-radius:5px}
.form-row{display:flex;flex-wrap:wrap;gap:16px;margin-bottom:16px}
.form-group{display:flex;flex-direction:column;gap:5px;flex:1;min-width:180px}
.form-group label{font-size:12px;color:var(--muted);font-weight:500}
.form-control{background:#0d1117;border:1px solid var(--border);border-radius:7px;color:var(--text);padding:8px 12px;font-size:13px;width:100%;transition:border-color .15s}
.form-control:focus{outline:none;border-color:var(--accent)}
select.form-control option{background:#161b22}
.form-section{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:20px}
.form-section h2{font-size:14px;font-weight:600;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border)}
.aw-alert{padding:10px 14px;border-radius:7px;font-size:13px;margin-bottom:16px;display:none}
.aw-alert.show{display:block}
.aw-alert.success{background:rgba(63,185,80,.1);border:1px solid rgba(63,185,80,.3);color:var(--green)}
.aw-alert.error{background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.3);color:var(--red)}
.aw-modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;display:none;align-items:center;justify-content:center}
.aw-modal-bg.open{display:flex}
.aw-modal{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:24px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto}
.aw-modal h2{font-size:16px;font-weight:700;margin-bottom:16px}
.modal-foot{display:flex;gap:10px;justify-content:flex-end;margin-top:16px;padding-top:16px;border-top:1px solid var(--border)}
.aw-toggle{position:relative;display:inline-block;width:38px;height:20px}
.aw-toggle input{opacity:0;width:0;height:0}
.aw-toggle .slider{position:absolute;inset:0;cursor:pointer;background:#30363d;border-radius:20px;transition:.2s}
.aw-toggle .slider:before{content:'';position:absolute;height:14px;width:14px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s}
.aw-toggle input:checked+.slider{background:var(--accent)}
.aw-toggle input:checked+.slider:before{transform:translateX(18px)}
.aw-hamburger{display:none;flex-direction:column;gap:4px;cursor:pointer;padding:4px}
.aw-hamburger span{width:20px;height:2px;background:var(--text);border-radius:2px}
.aw-backdrop{display:none}
.aw-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
.btn-primary{background:linear-gradient(135deg,#ff4a7d,#e0245e 50%,#a8143f)}
@media(max-width:768px){
  html,body{font-size:15px;overflow-x:hidden}
  .aw-sidebar{transform:translateX(-100%);transition:transform .25s ease;width:260px;box-shadow:0 0 40px rgba(0,0,0,.6)}
  .aw-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:90}
  .aw-sidebar.open + .aw-backdrop{display:block}
  .aw-nav li a{padding:13px 16px;font-size:15px}
  .aw-topbar .tb-right span{display:none}
  .aw-topbar h1{font-size:17px}
  .aw-table{min-width:560px}
  .aw-table th,.aw-table td{padding:10px 10px;white-space:nowrap}
  .aw-content [style*="grid-template-columns"]{grid-template-columns:1fr !important}
  .aw-content .aw-cards[style*="grid-template-columns"]{grid-template-columns:repeat(2,1fr) !important}
  .aw-content [style*="display:flex"]{flex-wrap:wrap}
  .form-group{min-width:100%}
  .form-control{font-size:16px;padding:11px 12px}
  .btn{padding:11px 16px;font-size:14px}
  .btn-sm{padding:8px 12px;font-size:13px}
  .form-section{padding:16px}
  .aw-modal{margin:12px;max-width:calc(100% - 24px)}
  .aw-content input[style*="max-width"]{max-width:100% !important}
  .aw-content form[style*="display:flex"]{flex-wrap:nowrap}
  .aw-content form[style*="display:flex"] input{min-width:0}
  .aw-card .val{font-size:20px;word-break:break-word}
  .aw-sidebar.open{transform:translateX(0)}
  .aw-main{margin-left:0}
  .aw-topbar{padding:12px 16px}
  .aw-content{padding:14px}
  .aw-cards{grid-template-columns:repeat(2,1fr)}
  .aw-hamburger{display:flex!important}
}
</style>
</head>
<body>
<div class="aw-wrap">
<aside class="aw-sidebar" id="awSidebar">
  <div class="aw-logo"><img src="/images/rubin-gem.svg" alt="" style="width:24px;vertical-align:-4px;margin-right:6px">RUBIN <span style="font-size:11px;color:var(--muted);font-weight:400">Admin</span></div>
  <ul class="aw-nav">
    <?php foreach ($nav as $n):
        $active = ($cur === $n['href']) ||
                  ($n['href'] !== '/admin' && strpos($cur, $n['href']) === 0);
    ?>
    <li><a href="<?= $n['href'] ?>" class="<?= $active ? 'active' : '' ?>">
      <span class="ni"><?= $n['icon'] ?></span><?= $n['label'] ?>
    </a></li>
    <?php endforeach ?>
  </ul>
  <div class="aw-sidebar-foot">
    Вы вошли как <strong style="color:var(--text)"><?= htmlspecialchars($admin_login) ?></strong><br>
    <a href="/" style="color:var(--muted);font-size:12px">← На сайт</a>
  </div>
</aside>
<div class="aw-backdrop" onclick="document.getElementById('awSidebar').classList.remove('open')"></div>

<div class="aw-main">
<div class="aw-topbar">
  <div style="display:flex;align-items:center;gap:12px">
    <div class="aw-hamburger" data-testid="admin-menu-btn" onclick="document.getElementById('awSidebar').classList.toggle('open')">
      <span></span><span></span><span></span>
    </div>
    <h1><?= htmlspecialchars($page_title) ?></h1>
  </div>
  <div class="tb-right">
    <span><?= date('d M Y') ?></span>
    <a href="/">Сайт ↗</a>
  </div>
</div>
<div class="aw-content">
<script>
// Global CSRF-aware fetch for all admin pages
(function() {
  var CSRF = document.querySelector('meta[name=csrf]').content;
  window.bwPost = function(data) {
    return fetch('/admin/admin_func.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-Token': CSRF
      },
      body: new URLSearchParams(data)
    }).then(function(r) { return r.json(); });
  };
})();
</script>
