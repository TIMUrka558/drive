<?php
require ("components/header.php");
require ("components/gamebar.php");
require ("components/livewins.php");
?>
<script>
function bots() {
  if(navigator.onLine) {
    $.ajax({ url: 'bots.php', timeout: 10000, success: function(data) {
      try { var obj = jQuery.parseJSON(data); } catch(e){}
    }});
  }
}
function historys() {
  if(navigator.onLine) {
    $.ajax({ url: 'core.php', timeout: 10000, success: function(data) {
      try { var obj = jQuery.parseJSON(data); if(obj.online) $('#onlineCount').text(obj.online); } catch(e){}
    }});
  }
}

// Копировать реферальную ссылку
function copyRefLink() {
  var link = document.getElementById('bw-ref-link');
  if (!link) return;
  var val = link.value;
  if (navigator.clipboard) {
    navigator.clipboard.writeText(val).then(function() { showCopySuccess(); });
  } else {
    link.select(); document.execCommand('copy'); showCopySuccess();
  }
}
function showCopySuccess() {
  var btn = document.getElementById('bw-copy-btn');
  if (!btn) return;
  btn.textContent = 'Скопировано!';
  btn.style.background = '#10b981';
  setTimeout(function() {
    btn.textContent = 'Скопировать';
    btn.style.background = '';
  }, 2000);
}

// Загрузить список рефералов
function loadRefs() {
  $.ajax({
    url: '/getreflist.php', type: 'GET',
    success: function(data) {
      try {
        var obj = jQuery.parseJSON(data);
        var rows = obj.data || [];
        var tbody = document.getElementById('bw-ref-tbody');
        var total = document.getElementById('bw-ref-total');
        var income = document.getElementById('bw-ref-income');
        if (!tbody) return;
        tbody.innerHTML = '';
        var totalDeps = 0;
        if (rows.length === 0) {
          tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#9ca3af;padding:20px 0;">Рефералов пока нет</td></tr>';
        } else {
          rows.forEach(function(r) {
            var dep = parseFloat(r[4]) || 0;
            totalDeps += dep;
            var tr = '<tr style="border-top:1px solid #f0f2f7;">'
              + '<td style="padding:10px 12px;font-weight:600;color:#1a1d2e;">' + escHtml(r[1]) + '</td>'
              + '<td style="padding:10px 12px;font-size:12px;color:#6b7280;">' + escHtml(r[2]) + '</td>'
              + '<td style="padding:10px 12px;font-weight:700;color:#10b981;text-align:right;">+' + dep.toFixed(2) + ' р</td>'
              + '</tr>';
            tbody.innerHTML += tr;
          });
        }
        if (total) total.textContent = rows.length;
        if (income) income.textContent = (parseFloat(obj.income) || 0).toFixed(2) + ' р';
        var pct = parseFloat(obj.pct) || 0;
        document.querySelectorAll('.rb-ref-pct').forEach(function (e) { e.textContent = pct + '%'; });
      } catch(e) {}
    }
  });
}
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

setInterval(bots, 5000);
setInterval(historys, 5000);
</script>

<div class="bw-page">

  <div class="bw-page-title">
    <div class="bw-page-icon"><span class="material-icons">group_add</span></div>
    Рефералы
  </div>

<?php if ($sid): ?>

  <!-- Реферальная ссылка -->
  <div class="bw-card" style="margin-bottom:12px;">
    <div style="font-size:13px;font-weight:700;color:#1a1d2e;margin-bottom:6px;">Ваша реферальная ссылка</div>
    <div style="font-size:12px;color:#6b7280;margin-bottom:12px;">Приглашайте друзей и получайте <strong style="color:#e0245e;"><span class="rb-ref-pct">5%</span> с каждого их пополнения</strong> на свой баланс</div>
    <div style="display:flex;gap:8px;align-items:stretch;">
      <input id="bw-ref-link" readonly
        value="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off'?'https':'http') . '://' . $_SERVER['HTTP_HOST'] . '/?i=' . $id ?>"
        style="flex:1;border:2px solid #2a1720;border-radius:10px;padding:10px 12px;font-size:12px;color:#374151;background:#17151c;outline:none;font-family:'Inter',sans-serif;">
      <button id="bw-copy-btn" onclick="copyRefLink()"
        style="flex-shrink:0;padding:10px 16px;border-radius:10px;background:linear-gradient(135deg,#e0245e,#a8143f);color:#fff;border:none;font-weight:700;font-size:12px;font-family:'Inter',sans-serif;cursor:pointer;white-space:nowrap;">
        Скопировать
      </button>
    </div>
  </div>

  <!-- Статистика -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
    <div class="bw-card" style="text-align:center;padding:16px 12px;">
      <div style="font-size:24px;font-weight:900;color:#e0245e;" id="bw-ref-total">—</div>
      <div style="font-size:12px;color:#6b7280;margin-top:4px;">Приглашено</div>
    </div>
    <div class="bw-card" style="text-align:center;padding:16px 12px;">
      <div style="font-size:22px;font-weight:900;color:#10b981;" id="bw-ref-income">—</div>
      <div style="font-size:12px;color:#6b7280;margin-top:4px;">Заработано (<span class="rb-ref-pct">5%</span>)</div>
    </div>
  </div>

  <!-- Как работает -->
  <div class="bw-card" style="margin-bottom:12px;">
    <div style="font-size:13px;font-weight:700;color:#1a1d2e;margin-bottom:12px;">Как работает программа</div>

    <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:12px;">
      <div style="width:32px;height:32px;border-radius:50%;background:#2a1720;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <span class="material-icons" style="font-size:16px;color:#e0245e;">link</span>
      </div>
      <div>
        <div style="font-size:13px;font-weight:600;color:#1a1d2e;">Поделитесь ссылкой</div>
        <div style="font-size:12px;color:#6b7280;margin-top:2px;">Отправьте реферальную ссылку другу в любом мессенджере</div>
      </div>
    </div>

    <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:12px;">
      <div style="width:32px;height:32px;border-radius:50%;background:#2a1720;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <span class="material-icons" style="font-size:16px;color:#e0245e;">person_add</span>
      </div>
      <div>
        <div style="font-size:13px;font-weight:600;color:#1a1d2e;">Друг регистрируется</div>
        <div style="font-size:12px;color:#6b7280;margin-top:2px;">Он переходит по вашей ссылке и создаёт аккаунт</div>
      </div>
    </div>

    <div style="display:flex;align-items:flex-start;gap:10px;">
      <div style="width:32px;height:32px;border-radius:50%;background:#17151c;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <span class="material-icons" style="font-size:16px;color:#10b981;">payments</span>
      </div>
      <div>
        <div style="font-size:13px;font-weight:600;color:#1a1d2e;">Вы получаете <span class="rb-ref-pct">5%</span></div>
        <div style="font-size:12px;color:#6b7280;margin-top:2px;">С каждого пополнения реферала вы автоматически получаете 5% на баланс</div>
      </div>
    </div>
  </div>

  <!-- Список рефералов -->
  <div class="bw-card">
    <div style="font-size:13px;font-weight:700;color:#1a1d2e;margin-bottom:14px;">Список рефералов</div>
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr>
          <th style="text-align:left;font-size:11px;font-weight:700;color:#9ca3af;padding:0 12px 10px;text-transform:uppercase;letter-spacing:.5px;">Игрок</th>
          <th style="text-align:left;font-size:11px;font-weight:700;color:#9ca3af;padding:0 12px 10px;text-transform:uppercase;letter-spacing:.5px;">Дата</th>
          <th style="text-align:right;font-size:11px;font-weight:700;color:#9ca3af;padding:0 12px 10px;text-transform:uppercase;letter-spacing:.5px;">Пополнил</th>
        </tr>
      </thead>
      <tbody id="bw-ref-tbody">
        <tr><td colspan="3" style="text-align:center;color:#9ca3af;padding:20px 0;">Загрузка...</td></tr>
      </tbody>
    </table>
  </div>

<?php else: ?>

  <!-- Гость — предложение войти -->
  <div class="bw-card" style="text-align:center;padding:40px 20px;">
    <span class="material-icons" style="font-size:48px;color:#e8ebf0;">group_add</span>
    <div style="font-size:16px;font-weight:700;color:#1a1d2e;margin-top:12px;">Войдите чтобы участвовать</div>
    <div style="font-size:13px;color:#6b7280;margin-top:6px;margin-bottom:20px;">Реферальная программа доступна только зарегистрированным пользователям</div>
    <a href="#modalLogIn" data-toggle="modal"
       style="display:inline-flex;align-items:center;justify-content:center;padding:12px 28px;border-radius:12px;background:linear-gradient(135deg,#e0245e,#a8143f);color:#fff;font-weight:700;font-size:14px;text-decoration:none;">
      Войти
    </a>
  </div>

<?php endif; ?>

</div>

<?php
require ("components/outsidebar.php");
require ("components/modal.php");
require ("components/footer.php");
?>

<?php if ($sid): ?>
<script>
// Загружаем список рефералов после загрузки страницы
document.addEventListener('DOMContentLoaded', function() { loadRefs(); });
</script>
<?php endif; ?>
<script>history.pushState(null, null, window.location.pathname.split("?")[0]);</script>
