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
function slotdep() {
  new Noty({ type:'error', layout:'bottomLeft', theme:'mint', timeout:1500, text:'Для игры в слоты нужен депозит!', animation:{open:'animated bounceInLeft',close:'animated bounceOutLeft'} }).show();
}
</script>

<?php
// Данные слотов
$slots = [
  ['id'=>1, 'name'=>'The Dog House',        'img'=>'/slots_materials/TheDogHouseMegaways.svg', 'provider'=>'Pragmatic'],
  ['id'=>2, 'name'=>'Sweet Bonanza',         'img'=>'/slots_materials/SweetBonanza.svg',        'provider'=>'Pragmatic'],
  ['id'=>3, 'name'=>'Gates Of Olympus',      'img'=>'/slots_materials/GatesOfOlympys.svg',      'provider'=>'Pragmatic'],
  ['id'=>4, 'name'=>'Sugar Rush',            'img'=>'/slots_materials/SugarRush.svg',           'provider'=>'Pragmatic'],
  ['id'=>5, 'name'=>'Madame Destiny',        'img'=>'/slots_materials/MadameDestiny.svg',       'provider'=>'Pragmatic'],
  ['id'=>6, 'name'=>'Cleocatra',             'img'=>'/slots_materials/Cleocatra.svg',           'provider'=>'Pragmatic'],
  ['id'=>7, 'name'=>'Big Bass Bonanza',      'img'=>'/slots_materials/BigBass.svg',             'provider'=>'Pragmatic'],
  ['id'=>8, 'name'=>'Starlight Princess',   'img'=>'/slots_materials/StarlightW.svg',          'provider'=>'Pragmatic'],
  ['id'=>9, 'name'=>'Fruit Party',           'img'=>'/slots_materials/FruitParty.svg',          'provider'=>'Pragmatic'],
  ['id'=>10,'name'=>'Sweet Bonanza Xmas',   'img'=>'/slots_materials/SweetBonanzaXmas.svg',    'provider'=>'Pragmatic'],
  ['id'=>11,'name'=>'Extra Juicy',           'img'=>'/slots_materials/ExtraJuicy.svg',          'provider'=>'Pragmatic'],
  ['id'=>12,'name'=>'Zeus Of Hades',         'img'=>'/slots_materials/ZofH.svg',               'provider'=>'Pragmatic'],
  ['id'=>13,'name'=>'Dog House Megaways',    'img'=>'/slots_materials/DogHouseMega.svg',        'provider'=>'Pragmatic'],
  ['id'=>14,'name'=>'Santa\'s Great Gifts', 'img'=>'/slots_materials/SantasGreatGifts.svg',    'provider'=>'Pragmatic'],
  ['id'=>15,'name'=>'Wild West Gold',        'img'=>'/slots_materials/WWGM.svg',               'provider'=>'Pragmatic'],
];
?>

<div class="bw-page">

  <div class="bw-page-title">
    <div class="bw-page-icon"><span class="material-icons">casino</span></div>
    Слоты
  </div>

  <!-- Фильтры -->
  <div class="bw-filter-row">
    <button class="bw-filter-btn active">🔥 Горячие</button>
    <button class="bw-filter-btn">⭐ Популярные</button>
    <button class="bw-filter-btn">🆕 Новые</button>
    <button class="bw-filter-btn">🎰 Все слоты</button>
    <button class="bw-filter-btn">💎 Jackpot</button>
  </div>

  <!-- Сетка слотов -->
  <div class="bw-slot-grid">
    <?php foreach ($slots as $slot): ?>
    <a href="/game/<?=$slot['id']?>" style="text-decoration:none;" class="bw-slot-card">
      <img src="<?=htmlspecialchars($slot['img'])?>" alt="<?=htmlspecialchars($slot['name'])?>"
           onerror="this.style.cssText='width:100%;height:100%;background:linear-gradient(135deg,#e0245e 0%,#a8143f 100%);display:block;border-radius:14px'">
      <div class="bw-slot-badge"><span class="material-icons">local_fire_department</span></div>
      <div class="bw-slot-name">
        <p><?=htmlspecialchars($slot['name'])?></p>
        <span class="bw-slot-provider"><?=htmlspecialchars($slot['provider'])?></span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

</div>

<?php
require ("components/outsidebar.php");
require ("components/modal.php");
require ("components/footer.php");
?>
<script>history.pushState(null, null, window.location.pathname.split("?")[0]);</script>
