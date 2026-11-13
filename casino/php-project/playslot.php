<?php
require("components/header.php");
require_once __DIR__ . '/inc/wgc.php';

$gid = trim((string)($_GET['g'] ?? ''));
$game = null;
if ($gid !== '') {
    $game = mysql_fetch_assoc(mysql_query("SELECT * FROM wgc_games WHERE public_id='" . mysql_real_escape_string($gid) . "' AND removed=0"));
}
$err = '';
$url = '';
if (!$game) $err = 'Игра не найдена';
elseif (empty($id)) $err = 'login';
elseif ((int)$is_ban === 1) $err = 'Ваш аккаунт заблокирован';
else {
    $mobile = (bool)preg_match('/Mobile|Android|iPhone|iPad/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
    $host = 'https://' . ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST']);
    [$code, $res] = wgc_api('POST', '/v1/sessions', [
        'publicId' => $game['public_id'],
        'playerId' => wgc_player_id((int)$id),
        'currency' => WGC_CURRENCY,
        'mode' => wgc_is_test_key() ? 'demo' : 'real',
        'language' => 'ru',
        'returnUrl' => $host . '/slot',
        'platform' => $mobile ? 'mobile' : 'desktop',
    ], bin2hex(random_bytes(16)));
    if (!empty($res['ok'])) $url = $res['data']['url'];
    else {
        $c = $res['error']['code'] ?? 'error';
        $msgs = ['balance_required' => 'Игра временно недоступна. Попробуйте позже', 'player_blocked' => 'Игры недоступны для вашего аккаунта',
            'country_restricted' => 'Игра недоступна в вашей стране', 'not_found' => 'Игра недоступна', 'rate_limited' => 'Слишком много запросов, попробуйте через минуту',
            'supplier_unavailable' => 'Провайдер игры не отвечает, попробуйте позже', 'busy' => 'Сервер перегружен, попробуйте через минуту'];
        $err = $msgs[$c] ?? 'Не удалось открыть игру';
        error_log('WGC session error: ' . $c . ' ' . ($res['error']['message'] ?? ''));
    }
}
?>
<div class="bw-page" style="max-width:1180px;">
  <div class="bw-page-title" style="display:flex;align-items:center;gap:10px;">
    <a href="/slot" class="bw-page-icon" data-testid="slot-back-btn" style="text-decoration:none;"><span class="material-icons">arrow_back</span></a>
    <?= htmlspecialchars($game['name'] ?? 'Слот') ?>
    <?php if ($game): ?><span style="font-size:12px;opacity:.6;margin-left:auto;"><?= htmlspecialchars($game['brand']) ?></span><?php endif; ?>
  </div>
  <?php if ($url): ?>
    <div style="position:relative;width:100%;height:calc(100vh - 220px);min-height:420px;border-radius:16px;overflow:hidden;background:#000;">
      <iframe data-testid="slot-iframe" src="<?= htmlspecialchars($url) ?>" allow="fullscreen; autoplay" allowfullscreen style="border:0;width:100%;height:100%;"></iframe>
    </div>
  <?php elseif ($err === 'login'): ?>
    <div class="bw-card" data-testid="slot-login-required" style="padding:24px;text-align:center;">
      <div style="margin-bottom:14px;">Войдите или зарегистрируйтесь, чтобы играть</div>
      <a href="/" class="bw-btn bw-btn-primary">На главную</a>
    </div>
  <?php else: ?>
    <div class="bw-card" data-testid="slot-error" style="padding:24px;text-align:center;"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>
</div>
<?php
require("components/outsidebar.php");
require("components/modal.php");
require("components/footer.php");
?>
