<?php
require ("components/header.php");
require ("components/gamebar.php");
require ("components/livewins.php");

$game1  = rand(11,38);
$game2  = rand(22,76);
$game3  = rand(14,55);
$game4  = rand(18,43);
$gamew  = rand(30,80);
?>
<script>
function bots() {
  if(navigator.onLine) {
    $.ajax({ url: 'bots.php', timeout: 10000,
      success: function(data) {
        try {
          var obj = jQuery.parseJSON(data);
          if(obj.ok) { $('#response').prepend(obj.new); $('#response').children().slice(20).remove(); }
        } catch(e){}
      }
    });
  }
}
function historys() {
  if(navigator.onLine) {
    $.ajax({ url: 'core.php', timeout: 10000,
      success: function(data) {
        try {
          var obj = jQuery.parseJSON(data);
          if(obj.online) $('#onlineCount').text(obj.online);
          if(obj.game) {
            var tbody = $('#response');
            tbody.html(obj.game);
          }
        } catch(e){}
      }
    });
  }
}
historys();
function sendMesSupport() {
  if ($("#mesSupport").val() == "") {
    return $("#errorMesSupport").show().html("Введите сообщение");
  }
  $.ajax({
    type: 'POST', url: '/action.php',
    beforeSend: function() { $("#errorMesSupport").hide(); },
    data: { type: "sendMesSupport", sid: Cookies.get('sid'), mes: $("#mesSupport").val() },
    success: function(data) { location.reload(); }
  });
}
setInterval(bots, 5000);
setInterval(historys, 5000);
</script>

<div class="bw-page">

  <!-- Баннеры -->
  <div class="rb-banners" id="rbBanners" data-testid="home-banner-carousel">
    <div class="rb-banners-track" id="rbBannersTrack">
      <a class="rb-banner" href="/bonus" data-testid="home-banner-welcome"><img src="/images/banners/welcome.jpg" alt="Добро пожаловать в RUBIN"></a>
      <a class="rb-banner" href="<?= !empty($group) ? htmlspecialchars($group) : '/bonus' ?>" target="<?= !empty($group) ? '_blank' : '_self' ?>" data-testid="home-banner-channel"><img src="/images/banners/channel.jpg" alt="Наш канал"></a>
      <a class="rb-banner" href="/ref" data-testid="home-banner-invite"><img src="/images/banners/invite.jpg" alt="Приглашай друзей"></a>
    </div>
    <div class="rb-banners-dots" id="rbBannersDots"></div>
  </div>
  <script>
  (function () {
    var root = document.getElementById('rbBanners'), track = document.getElementById('rbBannersTrack');
    var slides = track.children, dots = document.getElementById('rbBannersDots'), i = 0, timer, sx = 0, dx = 0;
    for (var k = 0; k < slides.length; k++) {
      var d = document.createElement('button'); d.type = 'button'; d.setAttribute('data-testid', 'home-banner-dot-' + k);
      d.onclick = (function (n) { return function () { go(n); restart(); }; })(k); dots.appendChild(d);
    }
    function go(n) {
      i = (n + slides.length) % slides.length;
      track.style.transform = 'translateX(' + (-100 * i) + '%)';
      for (var k = 0; k < dots.children.length; k++) dots.children[k].classList.toggle('active', k === i);
    }
    function restart() { clearInterval(timer); timer = setInterval(function () { go(i + 1); }, 4000); }
    root.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; dx = 0; clearInterval(timer); }, { passive: true });
    root.addEventListener('touchmove', function (e) { dx = e.touches[0].clientX - sx; }, { passive: true });
    root.addEventListener('touchend', function () { if (Math.abs(dx) > 40) go(i + (dx < 0 ? 1 : -1)); restart(); });
    var mx = null;
    root.addEventListener('mousedown', function (e) { mx = e.clientX; });
    root.addEventListener('mouseup', function (e) { if (mx !== null && Math.abs(e.clientX - mx) > 40) { go(i + (e.clientX < mx ? 1 : -1)); restart(); } mx = null; });
    root.addEventListener('click', function (e) { if (Math.abs(dx) > 40) e.preventDefault(); }, true);
    go(0); restart();
  })();
  </script>

  <!-- Оригинальные игры -->
  <div class="bw-page-title">
    <div class="bw-page-icon"><span class="material-icons">star</span></div>
    Оригинальные игры
  </div>
  <div class="bw-orig-grid">
    <div class="bw-orig-card" onclick="location.href='/dice'">
      <img src="/images/game-dice-v2.jpg" alt="Dice">
    </div>
    <div class="bw-orig-card" onclick="location.href='/x50'">
      <img src="/images/game-x50-v2.jpg" alt="x50">
    </div>
    <div class="bw-orig-card" onclick="location.href='/bubbles'">
      <img src="/images/game-bubbles-v2.jpg" alt="Bubbles">
    </div>
    <div class="bw-orig-card" onclick="location.href='/mines'">
      <img src="/images/game-mines-v2.jpg" alt="Mines">
    </div>
  </div>

  <!-- Слоты -->
  <div class="bw-page-title bw-mt-12">
    <div class="bw-page-icon"><span class="material-icons">sports_esports</span></div>
    Популярные слоты
  </div>
  <?php require_once __DIR__ . '/inc/wgc.php'; [$home_slots] = wgc_games(9); ?>
  <?php if ($home_slots): ?>
  <div class="bw-slot-grid rb-wgc-grid" data-testid="home-slots-grid">
    <?php foreach ($home_slots as $s): ?>
    <a href="/playslot?g=<?= urlencode($s['public_id']) ?>" class="bw-slot-card rb-wgc-card" style="text-decoration:none;" title="<?= htmlspecialchars($s['name'] . ' · ' . $s['brand']) ?>">
      <img src="<?= htmlspecialchars($s['image']) ?>" alt="<?= htmlspecialchars($s['name']) ?>" loading="lazy">
    </a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="bw-slot-grid">
    <a href="/game/1" style="text-decoration:none;" class="bw-slot-card">
      <img src="/slots_materials/TheDogHouseMegaways.svg" alt="The Dog House">
      <div class="bw-slot-name"><p>The Dog House</p><span class="bw-slot-provider">Pragmatic</span></div>
    </a>
    <a href="/game/2" style="text-decoration:none;" class="bw-slot-card">
      <img src="/slots_materials/SweetBonanza.svg" alt="Sweet Bonanza">
      <div class="bw-slot-name"><p>Sweet Bonanza</p><span class="bw-slot-provider">Pragmatic</span></div>
    </a>
    <a href="/game/3" style="text-decoration:none;" class="bw-slot-card">
      <img src="/slots_materials/GatesOfOlympys.svg" alt="Gates Of Olympus">
      <div class="bw-slot-name"><p>Gates Of Olympus</p><span class="bw-slot-provider">Pragmatic</span></div>
    </a>
    <a href="/game/4" style="text-decoration:none;" class="bw-slot-card">
      <img src="/slots_materials/SugarRush.svg" alt="Sugar Rush">
      <div class="bw-slot-name"><p>Sugar Rush</p><span class="bw-slot-provider">Pragmatic</span></div>
    </a>
  </div>
  <?php endif; ?>
  <a href="/slot" class="bw-btn bw-btn-secondary bw-btn-full" style="margin-top:8px;">
    <span class="material-icons">apps</span> Все слоты
  </a>

</div>

<?php
require ("components/outsidebar.php");
require ("components/modal.php");
require ("components/footer.php");
?>
<script>history.pushState(null, null, window.location.pathname.split("?")[0]);</script>
