<?php
require ("components/header.php");
require ("components/gamebar.php");
require ("components/livewins.php");

$res41 = mysql_query("SELECT COUNT(*) FROM kot_games WHERE user_id='$id'");
$getx = mysql_fetch_row($res41);
$total = $getx[0];

$result2 = mysql_query("SELECT max(win_summa) as win_summa FROM kot_games WHERE user_id='$id'");
$res2 = mysql_fetch_assoc($result2);
$getx23 = $res2['win_summa'];

$result222 = mysql_query("SELECT sum(win_summa) as win_summa FROM kot_games WHERE user_id='$id'");
$res222 = mysql_fetch_assoc($result222);
$getx2323 = $res222['win_summa'];

$result22233 = mysql_query("SELECT sum(sum) as sum FROM kot_games WHERE user_id='$id'");
$res22233 = mysql_fetch_assoc($result22233);
$getfdsdf = $res22233['sum'];

// Дата регистрации
$user_reg_q = mysql_query("SELECT date_reg FROM kot_user WHERE id='$id'");
$user_reg_r = mysql_fetch_assoc($user_reg_q);
$regdate = $user_reg_r['date_reg'] ?: 'давно';

// История пополнений (таблица kot_payments)
$deps = mysql_query("SELECT * FROM kot_payments WHERE user_id='$id' ORDER BY id DESC LIMIT 5");

// История выводов
$withs = mysql_query("SELECT * FROM kot_withdraws WHERE user_id='$id' ORDER BY id DESC LIMIT 5");
?>
<script>
function promo3() {
  new Noty({ type:'error', layout:'bottomLeft', theme:'mint', timeout:1000, text:'Промокод не найден', animation:{open:'animated bounceInLeft',close:'animated bounceOutLeft'} }).show();
}
function tgError() {
  new Noty({ type:'error', layout:'bottomLeft', theme:'mint', timeout:1000, text:'Недоступно', animation:{open:'animated bounceInLeft',close:'animated bounceOutLeft'} }).show();
}
function promo2() {
  new Noty({ type:'success', layout:'bottomLeft', theme:'mint', timeout:1000, text:'Промокод активирован', animation:{open:'animated bounceInLeft',close:'animated bounceOutLeft'} }).show();
}
function sendMesSupport() {
  if ($("#mesSupport").val() == "") return $("#errorMesSupport").show().html("Введите сообщение");
  $.ajax({
    type: 'POST', url: '/action.php',
    beforeSend: function() { $("#errorMesSupport").hide(); },
    data: { type: "sendMesSupport", sid: Cookies.get('sid'), mes: $("#mesSupport").val() },
    success: function(data) { location.reload(); }
  });
}
</script>

<div class="bw-page">

  <!-- Заголовок -->
  <div class="bw-page-title">
    <div class="bw-page-icon"><span class="material-icons">person</span></div>
    Профиль
  </div>

  <!-- Герой профиля -->
  <?php
    $ava_url = file_exists(__DIR__.'/extra/avatars/'.$id.'.jpg')
      ? '/extra/avatars/'.$id.'.jpg'
      : '/extra/profile/default_ava.png';
  ?>
  <div class="bw-profile-hero">
    <div class="bw-avatar-wrap" onclick="document.getElementById('bw-ava-input').click()" style="cursor:pointer;position:relative;">
      <img class="bw-avatar" id="bw-ava-img" src="<?=$ava_url?>" alt="Аватар" onerror="this.onerror=null;this.src='/extra/profile/avatar.png'">
      <div class="bw-avatar-badge" style="background:#e0245e;">
        <span class="material-icons" style="font-size:13px;">photo_camera</span>
      </div>
      <input type="file" id="bw-ava-input" accept="image/*" style="display:none" onchange="bwUploadAva(this)">
    </div>
    <div id="bw-ava-msg" style="font-size:11px;color:#9ca3af;margin-top:4px;font-family:'Inter',sans-serif;display:none;">Загрузка...</div>
    <div class="bw-profile-name"><?=$login?></div>
    <div class="bw-profile-since">На сайте с <?=$regdate?></div>
    <div class="bw-profile-actions">
      <a href="/deposit" class="bw-btn bw-btn-primary bw-btn-sm">
        <span class="material-icons" style="font-size:16px;">account_balance_wallet</span>
        Пополнить
      </a>
      <a href="/withdraw" class="bw-btn bw-btn-secondary bw-btn-sm">
        <span class="material-icons" style="font-size:16px;">payments</span>
        Вывод
      </a>
      <a href="/logout" class="bw-btn bw-btn-ghost bw-btn-sm">
        <span class="material-icons" style="font-size:16px;">logout</span>
        Выход
      </a>
    </div>
  </div>

  <!-- Статистика -->
  <div class="bw-section-title">Статистика</div>
  <div class="bw-stat-grid">
    <div class="bw-stat-card">
      <div class="bw-stat-label">Всего игр</div>
      <div class="bw-stat-value"><?=$total?></div>
    </div>
    <div class="bw-stat-card">
      <div class="bw-stat-label">Сумма ставок</div>
      <div class="bw-stat-value"><?=$getfdsdf?></div>
    </div>
    <div class="bw-stat-card">
      <div class="bw-stat-label">Сумма выигрышей</div>
      <div class="bw-stat-value green"><?=$getx2323?></div>
    </div>
    <div class="bw-stat-card">
      <div class="bw-stat-label">Макс. выигрыш</div>
      <div class="bw-stat-value gold"><?=$getx23?></div>
    </div>
  </div>

  <!-- Последние пополнения -->
  <div class="bw-section-title">Последние пополнения</div>
  <div class="bw-card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="background:var(--blue-xs);">
          <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);">Сумма</th>
          <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);">Метод</th>
          <th style="padding:10px 14px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);">Дата</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $dep_count = 0;
        if ($deps) {
          while ($dep_row = mysql_fetch_assoc($deps)) {
            $dep_count++;
            ?>
            <tr style="border-top:1px solid var(--border);">
              <td style="padding:12px 14px;font-weight:700;color:var(--green);">+<?=htmlspecialchars($dep_row['suma'])?> р</td>
              <td style="padding:12px 14px;font-size:13px;color:var(--muted);"><?=htmlspecialchars($dep_row['qiwi'] ?: $dep_row['transaction'])?></td>
              <td style="padding:12px 14px;font-size:12px;color:var(--muted);text-align:right;"><?=htmlspecialchars($dep_row['data'])?></td>
            </tr>
            <?php
          }
        }
        if ($dep_count == 0):
        ?>
        <tr><td colspan="3" style="padding:20px;text-align:center;color:var(--muted);font-size:13px;">Пополнений пока нет</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Последние выводы -->
  <div class="bw-section-title">Последние выводы</div>
  <div class="bw-card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="background:var(--blue-xs);">
          <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);">Сумма</th>
          <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);">Способ</th>
          <th style="padding:10px 14px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);">Статус</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $with_count = 0;
        if ($withs) {
          while ($with_row = mysql_fetch_assoc($withs)) {
            $with_count++;
            $status_text = $with_row['status'] == '1' ? 'Выплачен' : 'В обработке';
            $status_color = $with_row['status'] == '1' ? 'var(--green)' : 'var(--gold)';
            ?>
            <tr style="border-top:1px solid var(--border);">
              <td style="padding:12px 14px;font-weight:700;color:var(--red);">-<?=htmlspecialchars($with_row['sum'])?> р</td>
              <td style="padding:12px 14px;font-size:13px;color:var(--muted);"><?=htmlspecialchars($with_row['ps'])?></td>
              <td style="padding:12px 14px;text-align:right;font-size:12px;font-weight:600;color:<?=$status_color?>;"><?=$status_text?></td>
            </tr>
            <?php
          }
        }
        if ($with_count == 0):
        ?>
        <tr><td colspan="3" style="padding:20px;text-align:center;color:var(--muted);font-size:13px;">Выводов пока нет</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<?php
require ("components/outsidebar.php");
require ("components/modal.php");
require ("components/footer.php");
?>
<script>
history.pushState(null, null, window.location.pathname.split("?")[0]);

function bwUploadAva(input) {
  if (!input.files || !input.files[0]) return;
  var msg = document.getElementById('bw-ava-msg');
  msg.style.display = 'block';
  msg.textContent = 'Загрузка...';
  var reader = new FileReader();
  reader.onload = function(e) {
    $.ajax({
      type: 'POST',
      url: '/action.php',
      data: { type: 'setAva', img: e.target.result },
      success: function(data) {
        try {
          var obj = JSON.parse(data);
          if (obj.success === 'success') {
            document.getElementById('bw-ava-img').src = obj.url + '?' + Date.now();
            // обновить топбар если есть
            var tb = document.getElementById('bw-topbar-ava');
            if (tb) tb.src = obj.url + '?' + Date.now();
            msg.textContent = 'Фото обновлено!';
            setTimeout(function(){ msg.style.display='none'; }, 2000);
          } else {
            msg.textContent = 'Ошибка: ' + (obj.error || 'неизвестно');
          }
        } catch(err) { msg.textContent = 'Ошибка сервера'; }
      },
      error: function() { msg.textContent = 'Ошибка соединения'; }
    });
  };
  reader.readAsDataURL(input.files[0]);
}
</script>
