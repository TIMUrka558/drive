<?php
require ("components/header.php");
require ("components/gamebar.php");
require ("components/livewins.php");
?>
<script>
function bots() {
  if(navigator.onLine) {
    $.ajax({ url: 'bots.php', timeout: 10000, success: function(data) {
      try { var obj = jQuery.parseJSON(data); if(obj.ok) { $('#response').prepend(obj.new); $('#response').children().slice(20).remove(); } } catch(e){}
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
function createPromo() {
  new Noty({ type:'error', layout:'bottomLeft', theme:'mint', timeout:1000, text:'Для создания промо-кодов нужен депозит от 1000р за всё время.', animation:{open:'animated bounceInLeft',close:'animated bounceOutLeft'} }).show();
}
function promo3() {
  new Noty({ type:'error', layout:'bottomLeft', theme:'mint', timeout:1000, text:'Промокод закончился или вы его уже активировали', animation:{open:'animated bounceInLeft',close:'animated bounceOutLeft'} }).show();
}
function nyerror() {
  new Noty({ type:'error', layout:'bottomLeft', theme:'mint', timeout:1000, text:'Данный бонус выдается 1 раз за все время.', animation:{open:'animated bounceInLeft',close:'animated bounceOutLeft'} }).show();
}
function promo2() {
  new Noty({ type:'success', layout:'bottomLeft', theme:'mint', timeout:1000, text:'Промокод активирован', animation:{open:'animated bounceInLeft',close:'animated bounceOutLeft'} }).show();
}
function nysoon() {
  new Noty({ type:'error', layout:'bottomLeft', theme:'mint', timeout:1000, text:'Бонус не активен', animation:{open:'animated bounceInLeft',close:'animated bounceOutLeft'} }).show();
}
function nysucces() {
  new Noty({ type:'success', layout:'bottomLeft', theme:'mint', timeout:1000, text:'Бонус успешно получен!', animation:{open:'animated bounceInLeft',close:'animated bounceOutLeft'} }).show();
}
function actPromokod() {
  if ($("#prNum123").val() == "") return;
  $.ajax({
    type: 'POST', url: '/action.php',
    beforeSend: function() { $('.btn-get-actpromo').addClass("disabled"); },
    data: { type: "activePromo", promoactive: $("#prNum123").val() },
    success: function(data) {
      $('.btn-get-actpromo').removeClass("disabled");
      $('#error_actpromo').hide();
      var obj = jQuery.parseJSON(data);
      if (obj.success == "success") {
        promo2();
        $(".maksksmaksks").hide(); $(".btn-get-actpromo").hide();
        updateBalance(obj.balance, obj.new_balance);
        updateBalanceMobile(obj.balance, obj.new_balance);
      } else { promo3(); }
    }
  });
}
function actPromo() {
  if ($("#prNum").val() == "") {
    $('#error_actpromo').show(); return $('#error_actpromo').html('Введите промокод');
  }
  $.ajax({
    type: 'POST', url: '/action.php',
    beforeSend: function() { $('.btn-get-actpromo').addClass("disabled"); },
    data: { type: "activePromo", promoactive: $("#prNum").val() },
    success: function(data) {
      $('.btn-get-actpromo').removeClass("disabled");
      $('#error_actpromo').hide();
      var obj = jQuery.parseJSON(data);
      if (obj.success == "success") {
        $("#succes_actpromo").show(); $(".maksksmaksks").hide(); $(".btn-get-actpromo").hide();
        $('#succes_actpromo').html("Промокод активирован!");
        updateBalance(obj.balance, obj.new_balance);
        updateBalanceMobile(obj.balance, obj.new_balance);
      } else { $('#error_actpromo').show(); $("#succes_actpromo").hide(); return $('#error_actpromo').html(obj.error); }
    }
  });
}
function getPromo() {
  var btn = document.querySelector('[onClick="getPromo()"]');
  if (btn) btn.disabled = true;
  $.ajax({
    type: 'POST', url: '/action.php',
    data: { type: "bonus" },
    success: function(data) {
      try {
        var obj = jQuery.parseJSON(data);
        if (obj.success == "success") {
          var amt = parseFloat(obj.bonussize) || 0;
          new Noty({ type:'success', layout:'bottomLeft', theme:'mint', timeout:2500,
            text: 'Бонус получен: <b>+' + amt.toFixed(2) + ' р</b>',
            animation:{open:'animated bounceInLeft',close:'animated bounceOutLeft'}
          }).show();
          if (typeof updateBalance === 'function') updateBalance(obj.balance, obj.new_balance);
          if (typeof updateBalanceMobile === 'function') updateBalanceMobile(obj.balance, obj.new_balance);
          if (btn) { btn.disabled = true; btn.textContent = 'Получено'; }
        } else {
          new Noty({ type:'error', layout:'bottomLeft', theme:'mint', timeout:2500,
            text: obj.error || 'Попробуйте позже',
            animation:{open:'animated bounceInLeft',close:'animated bounceOutLeft'}
          }).show();
          if (btn) btn.disabled = false;
        }
      } catch(e) {
        new Noty({ type:'error', layout:'bottomLeft', theme:'mint', timeout:2000,
          text:'Ошибка запроса', animation:{open:'animated bounceInLeft',close:'animated bounceOutLeft'}
        }).show();
        if (btn) btn.disabled = false;
      }
    },
    error: function() {
      if (btn) btn.disabled = false;
    }
  });
}
setInterval(bots, 5000);
setInterval(historys, 5000);
</script>

<div class="bw-page">

  <div class="bw-page-title">
    <div class="bw-page-icon"><span class="material-icons">redeem</span></div>
    Бонусы
  </div>

  <div style="text-align:center; margin:12px 0 18px;">
    <span style="font-size:18px; font-weight:900; text-transform:uppercase; letter-spacing:2px; background:linear-gradient(135deg,#e0245e,#a8143f); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">Бонусы от RUBIN</span>
  </div>

<?php
  $rbU = null;
  if (!empty($_SESSION['hash'])) { $sh = mysql_real_escape_string($_SESSION['hash']); $rbU = mysql_fetch_array(mysql_query("SELECT bonus_ts, cashback FROM kot_user WHERE hash='$sh'")); }
  $rbB = mysql_fetch_array(mysql_query("SELECT min_bonus_size, max_bonus_size, cashback_pct FROM kot_config LIMIT 1"));
  $rbLeft = $rbU ? max(0, (int)$rbU['bonus_ts'] + 86400 - time()) : 0;
  $rbCb = $rbU ? (float)$rbU['cashback'] : 0;
  ?>
  <!-- Ежедневный бонус -->
  <div class="bw-bonus-card bw-bonus-card-blue" data-testid="daily-bonus-card">
    <div class="bw-bonus-header">
      <div class="bw-bonus-icon"><span class="material-icons">today</span></div>
      <div class="bw-bonus-title">Ежедневный бонус</div>
    </div>
    <div class="bw-bonus-desc">Раз в 24 часа нажмите кнопку и выбейте случайную сумму от <?= (int)$rbB['min_bonus_size'] ?> до <?= (int)$rbB['max_bonus_size'] ?> ₽ на баланс</div>
    <div class="bw-bonus-meta" id="rbDailyMeta" data-testid="daily-bonus-timer"><?= $rbLeft > 0 ? 'Следующий бонус через ' . sprintf('%02d:%02d:%02d', intdiv($rbLeft, 3600), intdiv($rbLeft % 3600, 60), $rbLeft % 60) : 'Бонус доступен' ?></div>
    <div class="bw-bonus-footer">
      <div class="bw-bonus-amount"><span class="material-icons" style="font-size:18px;">casino</span> <?= (int)$rbB['min_bonus_size'] ?>–<?= (int)$rbB['max_bonus_size'] ?> ₽</div>
      <button class="bw-btn bw-btn-gold" id="rbDailyBtn" onclick="rbDaily()" <?= $rbLeft > 0 ? 'disabled' : '' ?> data-testid="daily-bonus-button">Получить</button>
    </div>
  </div>

  <!-- Кешбэк -->
  <div class="bw-bonus-card bw-bonus-card-blue" data-testid="cashback-card">
    <div class="bw-bonus-header">
      <div class="bw-bonus-icon"><span class="material-icons">currency_exchange</span></div>
      <div class="bw-bonus-title">Кешбэк <?= (float)$rbB['cashback_pct'] ?>%</div>
    </div>
    <div class="bw-bonus-desc">С каждого вашего депозита начисляется <?= (float)$rbB['cashback_pct'] ?>% кешбэка. Забирайте накопленное на баланс в любой момент.</div>
    <div class="bw-bonus-footer">
      <div class="bw-bonus-amount"><span class="material-icons" style="font-size:18px;">savings</span> <span id="rbCbAmount" data-testid="cashback-amount"><?= number_format($rbCb, 2, '.', ' ') ?></span> ₽</div>
      <button class="bw-btn bw-btn-gold" id="rbCbBtn" onclick="rbCashback()" <?= $rbCb > 0 ? '' : 'disabled' ?> data-testid="cashback-claim-button">Забрать</button>
    </div>
  </div>

  <!-- Промокод -->
  <div class="bw-bonus-card bw-bonus-card-dark">
    <div class="bw-bonus-header">
      <div class="bw-bonus-icon"><span class="material-icons">local_offer</span></div>
      <div class="bw-bonus-title">Промокод</div>
    </div>
    <div class="bw-bonus-desc">Если у вас есть промокод, вы можете его активировать тут</div>
    <div class="bw-promo-input-row">
      <input class="bw-input" id="prNum123" type="text" placeholder="Введите промокод" data-testid="promo-input">
    </div>
    <button class="bw-btn bw-btn-gold bw-btn-full btn-get-actpromo" onClick="actPromokod();" data-testid="promo-activate-button">Активировать</button>
    <div id="error_actpromo" style="font-size:12px;margin-top:8px;display:none;"></div>
  </div>

  <script>
  var rbLeft = <?= (int)$rbLeft ?>;
  function rbFmt(s) { var h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60), x = s % 60; return [h, m, x].map(function (v) { return (v < 10 ? '0' : '') + v; }).join(':'); }
  setInterval(function () { if (rbLeft > 0) { rbLeft--; document.getElementById('rbDailyMeta').textContent = rbLeft > 0 ? 'Следующий бонус через ' + rbFmt(rbLeft) : 'Бонус доступен'; if (!rbLeft) document.getElementById('rbDailyBtn').disabled = false; } }, 1000);
  function rbNoty(t, ok) { new Noty({ type: ok ? 'success' : 'error', layout: 'bottomLeft', theme: 'mint', timeout: 3000, text: t }).show(); }
  function rbDaily() {
    var b = document.getElementById('rbDailyBtn'); b.disabled = true;
    $.post('/payapi.php', { type: 'dailyBonus' }, function (d) {
      if (d.success) { rbNoty('Вы выбили ' + d.prize + ' ₽!', true); updateBalance(0, d.balance); rbLeft = d.left; }
      else { rbNoty(d.error); if (d.left) rbLeft = d.left; else b.disabled = false; }
    }, 'json').fail(function () { b.disabled = false; rbNoty('Ошибка соединения'); });
  }
  function rbCashback() {
    var b = document.getElementById('rbCbBtn'); b.disabled = true;
    $.post('/payapi.php', { type: 'claimCashback' }, function (d) {
      if (d.success) { rbNoty('Кешбэк ' + d.amount + ' ₽ зачислен', true); updateBalance(0, d.balance); document.getElementById('rbCbAmount').textContent = '0.00'; }
      else { rbNoty(d.error); b.disabled = false; }
    }, 'json').fail(function () { b.disabled = false; });
  }
  </script>

</div>

<?php
require ("components/outsidebar.php");
require ("components/modal.php");
require ("components/footer.php");
?>
<script>history.pushState(null, null, window.location.pathname.split("?")[0]);</script>
