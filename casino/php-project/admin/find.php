<?php
session_start();
require '../inc/mysql_compat.php';
require '../inc/bd.php';
$page_title = 'Поиск по ID';
require 'admin-head.php';

$raw = trim($_GET['q'] ?? '');
$qid = ltrim($raw, '#');
$s   = mysqli_real_escape_string($__conn, $qid);
$sections = [];
if ($qid !== '') {
    $find = function ($sql) use ($__conn) {
        $out = [];
        $r = mysqli_query($__conn, $sql);
        while ($r && $row = mysqli_fetch_assoc($r)) $out[] = $row;
        return $out;
    };
    $sections = [
        ['Игроки', $find("SELECT id, login, balance, date_reg FROM kot_user WHERE id='$s' OR login='$s' LIMIT 20"),
            fn($r) => ['#' . $r['id'], htmlspecialchars($r['login']), number_format((float)$r['balance'], 2) . ' ₽', htmlspecialchars($r['date_reg'] ?? ''), '/admin/users?uid=' . (int)$r['id']]],
        ['Депозиты', $find("SELECT p.id, p.user_id, p.suma, p.data, p.qiwi, p.transaction FROM kot_payments p WHERE p.id='$s' OR p.transaction='$s' LIMIT 20"),
            fn($r) => ['#' . $r['id'], 'Игрок #' . (int)$r['user_id'], number_format((float)$r['suma'], 2) . ' ₽ · ' . htmlspecialchars($r['qiwi']), htmlspecialchars($r['data']), '/admin/users?uid=' . (int)$r['user_id']]],
        ['Счета на оплату', $find("SELECT id, user_id, provider, amount_rub, status, created_at FROM rb_invoices WHERE id='$s' OR ext_id='$s' LIMIT 20"),
            fn($r) => ['#' . $r['id'], 'Игрок #' . (int)$r['user_id'], number_format((float)$r['amount_rub'], 2) . ' ₽ · ' . htmlspecialchars($r['provider']) . ' · ' . htmlspecialchars($r['status']), htmlspecialchars($r['created_at']), '/admin/users?uid=' . (int)$r['user_id']]],
        ['Выводы', $find("SELECT id, user_id, sum, ps, status, date FROM kot_withdraws WHERE id='$s' LIMIT 20"),
            fn($r) => ['#' . $r['id'], 'Игрок #' . (int)$r['user_id'], number_format((float)$r['sum'], 2) . ' ₽ · ' . htmlspecialchars($r['ps']) . ' · ' . (['0' => 'ожидает', '1' => 'выплачен', '2' => 'отменён', '3' => 'отправляется'][$r['status']] ?? $r['status']), htmlspecialchars($r['date']), '/admin/withdraws?q=' . (int)$r['id']]],
        ['Активации промокодов', $find("SELECT id, user_id, code, amount, status, created_at FROM kot_promo_act WHERE id='$s' LIMIT 20"),
            fn($r) => ['#' . $r['id'], 'Игрок #' . (int)$r['user_id'], htmlspecialchars($r['code']) . ' · ' . (float)$r['amount'] . ' ₽ · ' . htmlspecialchars($r['status']), htmlspecialchars($r['created_at']), '/admin/promo?aq=' . (int)$r['id']]],
        ['Транзакции слотов', $find("SELECT id, user_id, tx_id, round_id, game, bet, win, booked, rolled_back, created_at FROM wgc_tx WHERE id='$s' OR tx_id='$s' OR round_id='$s' LIMIT 20"),
            fn($r) => ['#' . $r['id'], 'Игрок #' . (int)$r['user_id'], htmlspecialchars($r['game']) . ' · ставка ' . round($r['bet'] / 1e6, 2) . ' ₽ · выигрыш ' . round($r['win'] / 1e6, 2) . ' ₽' . ($r['rolled_back'] ? ' · откат' : ''), htmlspecialchars($r['created_at']), '/admin/slots?q=' . urlencode($r['tx_id'])]],
        ['Ставки', $find("SELECT id, user_id, sum, win_summa, game FROM kot_games WHERE id='$s' LIMIT 20"),
            fn($r) => ['#' . $r['id'], 'Игрок #' . (int)$r['user_id'], 'Ставка ' . (float)$r['sum'] . ' ₽ · выигрыш ' . (float)$r['win_summa'] . ' ₽', 'игра ' . htmlspecialchars($r['game']), '/admin/users?uid=' . (int)$r['user_id']]],
    ];
}
$found = 0;
foreach ($sections as $sec) $found += count($sec[1]);
?>

<form method="get" style="display:flex;gap:8px;margin-bottom:20px">
  <input name="q" value="<?= htmlspecialchars($raw) ?>" class="form-control" style="max-width:340px" data-testid="find-input" placeholder="Введите ID (игрок, депозит, вывод, промо, ставка)…" autofocus>
  <button class="btn btn-primary" data-testid="find-submit">Найти</button>
</form>

<?php if ($qid !== '' && $found === 0): ?>
<div class="aw-alert error show" data-testid="find-empty">По ID «<?= htmlspecialchars($raw) ?>» ничего не найдено</div>
<?php endif ?>

<?php foreach ($sections as [$title, $rows, $fmt]): if (!$rows) continue; ?>
<div class="aw-table-wrap" style="margin-bottom:16px" data-testid="find-section">
  <div class="tw-head"><h2><?= $title ?> (<?= count($rows) ?>)</h2></div>
  <table class="aw-table">
    <?php foreach ($rows as $r): [$a, $b, $c, $d, $link] = $fmt($r); ?>
    <tr>
      <td style="color:var(--muted)"><?= $a ?></td><td><?= $b ?></td><td><?= $c ?></td>
      <td style="color:var(--muted);font-size:12px"><?= $d ?></td>
      <td><a href="<?= $link ?>" class="btn btn-ghost btn-sm">Открыть</a></td>
    </tr>
    <?php endforeach ?>
  </table>
</div>
<?php endforeach ?>

<?php require 'admin-foot.php'; ?>
