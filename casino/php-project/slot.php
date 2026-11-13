<?php
require ("components/header.php");
require ("components/gamebar.php");
require_once __DIR__ . '/inc/wgc.php';

$q = trim((string)($_GET['q'] ?? ''));
$brand = trim((string)($_GET['brand'] ?? ''));
$page = max(1, (int)($_GET['p'] ?? 1));
$per = 48;
[$slots, $total] = wgc_games($per, ($page - 1) * $per, $q, $brand);
$pages = max(1, (int)ceil($total / $per));
$brands = [];
$bq = mysql_query("SELECT brand, COUNT(*) c FROM wgc_games WHERE removed=0 AND kind='slot' AND brand<>'' GROUP BY brand ORDER BY MAX(sort) DESC, c DESC");
while ($bq && $r = mysql_fetch_assoc($bq)) $brands[] = $r;
$link = fn($p, $b = null) => '/slot?' . http_build_query(array_filter(['q' => $q, 'brand' => $b ?? $brand, 'p' => $p > 1 ? $p : null]));
?>
<div class="bw-page">
  <div class="bw-page-title">
    <div class="bw-page-icon"><span class="material-icons">casino</span></div>
    Слоты <span style="font-size:12px;opacity:.6;margin-left:8px;" data-testid="slots-total"><?= $total ?></span>
  </div>

  <form method="get" action="/slot" style="display:flex;gap:8px;margin-bottom:12px;">
    <input name="q" value="<?= htmlspecialchars($q) ?>" data-testid="slots-search-input" placeholder="Поиск игры или провайдера"
           style="flex:1;height:42px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:#fff;padding:0 14px;">
    <?php if ($brand !== ''): ?><input type="hidden" name="brand" value="<?= htmlspecialchars($brand) ?>"><?php endif; ?>
    <button class="bw-btn bw-btn-primary bw-btn-sm" data-testid="slots-search-btn"><span class="material-icons" style="font-size:18px;">search</span></button>
  </form>

  <div class="rb-prov" data-testid="slots-provider-dropdown">
    <button type="button" class="rb-prov-btn" data-testid="slots-provider-toggle" onclick="this.parentNode.classList.toggle('open')">
      <span class="material-icons" style="font-size:18px;">business</span>
      <span>Провайдер: <b><?= $brand !== '' ? htmlspecialchars($brand) : 'Все' ?></b></span>
      <span class="material-icons rb-prov-arrow">expand_more</span>
    </button>
    <div class="rb-prov-panel">
      <div class="rb-prov-list" data-testid="slots-provider-list">
        <a href="<?= $link(1, '') ?>" class="rb-prov-item <?= $brand === '' ? 'active' : '' ?>" data-testid="slots-provider-all"><span>Все провайдеры</span></a>
        <?php foreach ($brands as $b): ?>
        <a href="<?= $link(1, $b['brand']) ?>" class="rb-prov-item <?= $brand === $b['brand'] ? 'active' : '' ?>" data-name="<?= htmlspecialchars(mb_strtolower($b['brand'])) ?>"><span><?= htmlspecialchars($b['brand']) ?></span><small><?= (int)$b['c'] ?></small></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <script>
  document.addEventListener('click', function (e) { var d = document.querySelector('.rb-prov'); if (d && !d.contains(e.target)) d.classList.remove('open'); });
  </script>

  <?php if (!$slots): ?>
  <div class="bw-card" data-testid="slots-empty" style="padding:24px;text-align:center;opacity:.8;">
    <?= ($q !== '' || $brand !== '') ? 'Ничего не найдено' : 'Каталог слотов скоро появится' ?>
  </div>
  <?php else: ?>
  <div class="bw-slot-grid rb-wgc-grid" data-testid="slots-grid">
    <?php foreach ($slots as $s): ?>
    <a href="/playslot?g=<?= urlencode($s['public_id']) ?>" class="bw-slot-card rb-wgc-card" style="text-decoration:none;" data-testid="slot-card" title="<?= htmlspecialchars($s['name'] . ' · ' . $s['brand']) ?>">
      <img src="<?= htmlspecialchars($s['image']) ?>" alt="<?= htmlspecialchars($s['name']) ?>" loading="lazy"
           onerror="this.style.cssText='width:100%;height:100%;background:linear-gradient(135deg,#e0245e 0%,#a8143f 100%);display:block'">
    </a>
    <?php endforeach; ?>
  </div>
  <?php if ($pages > 1): ?>
  <div style="display:flex;gap:8px;justify-content:center;margin-top:16px;">
    <?php if ($page > 1): ?><a class="bw-btn bw-btn-secondary bw-btn-sm" data-testid="slots-prev" href="<?= $link($page - 1) ?>">Назад</a><?php endif; ?>
    <span style="align-self:center;opacity:.7;font-size:13px;"><?= $page ?> / <?= $pages ?></span>
    <?php if ($page < $pages): ?><a class="bw-btn bw-btn-secondary bw-btn-sm" data-testid="slots-next" href="<?= $link($page + 1) ?>">Дальше</a><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php
require ("components/outsidebar.php");
require ("components/modal.php");
require ("components/footer.php");
?>
