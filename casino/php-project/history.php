<?
require ("components/header.php");
require ("components/gamebar.php");

$payouts = mysql_query("SELECT w.sum, w.date, w.ps, u.login FROM kot_withdraws w LEFT JOIN kot_user u ON u.id = w.user_id WHERE w.status = '1' ORDER BY w.id DESC LIMIT 50");
?>
<div class="bw-page">
  <div class="bw-page-title">
    <div class="bw-page-icon"><span class="material-icons">schedule</span></div>
    Все выплаты
  </div>
  <div class="bw-card" data-testid="payouts-list" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
      <tr style="text-align:left;color:#6b7280;">
        <th style="padding:12px 16px;">Игрок</th>
        <th style="padding:12px 16px;">Сумма</th>
        <th style="padding:12px 16px;">Дата</th>
      </tr>
      <?php $n = 0; while ($p = mysql_fetch_array($payouts)) { $n++;
        $name = (string)($p['login'] ?? '');
        $masked = htmlspecialchars(mb_substr($name, 0, 3) . '***'); ?>
      <tr style="border-top:1px solid #eef0f4;">
        <td style="padding:12px 16px;font-weight:600;"><?= $masked ?></td>
        <td style="padding:12px 16px;"><?= number_format((float)$p['sum'], 2, '.', ' ') ?> ₽</td>
        <td style="padding:12px 16px;color:#6b7280;"><?= htmlspecialchars($p['date']) ?></td>
      </tr>
      <?php } if ($n === 0) { ?>
      <tr><td colspan="3" data-testid="payouts-empty" style="padding:24px 16px;text-align:center;color:#6b7280;">Пока нет выполненных выплат</td></tr>
      <?php } ?>
    </table>
  </div>
</div>
<?
require ("components/outsidebar.php");
require ("components/modal.php");
require ("components/footer.php");
?>
