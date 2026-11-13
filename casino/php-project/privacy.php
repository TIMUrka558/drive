<?php
require ("components/header.php");
require ("components/gamebar.php");
require ("components/livewins.php");
?>
<script>
function bots() { if(navigator.onLine) $.ajax({ url:'bots.php', timeout:10000 }); }
function historys() { if(navigator.onLine) $.ajax({ url:'core.php', timeout:10000, success:function(data){ try{ var o=jQuery.parseJSON(data); if(o.online) $('#onlineCount').text(o.online); }catch(e){} }}); }
setInterval(bots, 5000); setInterval(historys, 5000);
</script>

<div class="bw-page">
  <div class="bw-page-title">
    <div class="bw-page-icon"><span class="material-icons">privacy_tip</span></div>
    Политика конфиденциальности
  </div>

  <div class="bw-card" style="margin-bottom:12px;line-height:1.75;color:#374151;font-size:13px;">

    <div style="font-size:11px;color:#9ca3af;margin-bottom:18px;">Последнее обновление: 1 января 2024 г.</div>

    <p style="margin:0 0 14px;">Настоящая Политика конфиденциальности описывает, как <strong>RUBIN Casino</strong> (Novaspin N.V.) собирает, использует, хранит и защищает персональные данные пользователей в соответствии с применимым законодательством.</p>

    <div style="background:#17151c;border-left:3px solid #10b981;border-radius:0 8px 8px 0;padding:12px 14px;margin-bottom:18px;">
      <strong style="color:#10b981;">Мы серьёзно относимся к конфиденциальности</strong> — ваши данные никогда не продаются третьим лицам в рекламных целях.
    </div>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">1. Какие данные мы собираем</h3>

    <p style="margin:0 0 8px;font-weight:600;color:#1a1d2e;">1.1. Данные, которые вы предоставляете напрямую:</p>
    <?php
    $direct = [
      'Логин и пароль при регистрации',
      'Контактные данные (email, номер телефона — при верификации)',
      'Платёжные реквизиты при пополнении/выводе средств',
      'Документы для верификации личности (паспорт, удостоверение)',
      'Сообщения в чате поддержки',
    ];
    foreach ($direct as $d) echo "<p style='margin:0 0 6px;padding-left:16px;position:relative;'><span style='position:absolute;left:0;color:#e0245e;'>•</span> " . htmlspecialchars($d) . "</p>";
    ?>

    <p style="margin:14px 0 8px;font-weight:600;color:#1a1d2e;">1.2. Данные, собираемые автоматически:</p>
    <?php
    $auto = [
      'IP-адрес и сведения о местоположении',
      'Тип браузера и версия операционной системы',
      'Данные об устройстве (модель, разрешение экрана)',
      'Журналы посещений страниц и время сессий',
      'Куки (cookies) и идентификаторы сессий',
      'История ставок, пополнений и выводов',
    ];
    foreach ($auto as $d) echo "<p style='margin:0 0 6px;padding-left:16px;position:relative;'><span style='position:absolute;left:0;color:#e0245e;'>•</span> " . htmlspecialchars($d) . "</p>";
    ?>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">2. Цели использования данных</h3>
    <?php
    $purposes = [
      'Предоставление услуг и обслуживание аккаунта',
      'Верификация личности и предотвращение мошенничества',
      'Обработка финансовых операций (пополнения, выводы)',
      'Обеспечение безопасности платформы и выявление нарушений',
      'Отправка уведомлений о транзакциях и важных изменениях',
      'Соблюдение требований законодательства (AML, KYC)',
      'Анализ и улучшение работы Сайта',
      'Рассмотрение жалоб и обращений в службу поддержки',
    ];
    foreach ($purposes as $d) echo "<p style='margin:0 0 6px;padding-left:16px;position:relative;'><span style='position:absolute;left:0;color:#10b981;'>✓</span> " . htmlspecialchars($d) . "</p>";
    ?>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">3. Передача данных третьим лицам</h3>
    <p style="margin:0 0 10px;">3.1. Мы не продаём и не передаём ваши персональные данные третьим лицам в коммерческих целях.</p>
    <p style="margin:0 0 10px;">3.2. Данные могут быть переданы в следующих случаях:</p>
    <p style="margin:0 0 6px;padding-left:16px;position:relative;"><span style="position:absolute;left:0;color:#f59e0b;">!</span> Платёжным провайдерам для обработки транзакций</p>
    <p style="margin:0 0 6px;padding-left:16px;position:relative;"><span style="position:absolute;left:0;color:#f59e0b;">!</span> Регуляторным органам по требованию закона</p>
    <p style="margin:0 0 6px;padding-left:16px;position:relative;"><span style="position:absolute;left:0;color:#f59e0b;">!</span> Правоохранительным органам при наличии судебного запроса</p>
    <p style="margin:0 0 6px;padding-left:16px;position:relative;"><span style="position:absolute;left:0;color:#f59e0b;">!</span> Аудиторским и лицензирующим органам Кюрасао</p>
    <p style="margin:0 0 10px;padding-left:16px;position:relative;"><span style="position:absolute;left:0;color:#f59e0b;">!</span> Службам по предотвращению мошенничества</p>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">4. Файлы cookie</h3>
    <p style="margin:0 0 10px;">4.1. Мы используем файлы cookie для обеспечения работы Сайта, сохранения сессий и аналитики.</p>
    <p style="margin:0 0 10px;">4.2. Вы можете настроить или отключить cookie в браузере. Однако отключение cookie может нарушить работу некоторых функций Сайта.</p>
    <p style="margin:0 0 10px;">4.3. Мы используем следующие типы cookie: обязательные (сессионные), функциональные (запоминание настроек) и аналитические.</p>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">5. Безопасность данных</h3>
    <p style="margin:0 0 10px;">5.1. Мы применяем технические и организационные меры защиты данных: шифрование SSL/TLS, ограничение доступа сотрудников, регулярный аудит безопасности.</p>
    <p style="margin:0 0 10px;">5.2. Несмотря на принимаемые меры, ни одна система не может гарантировать абсолютную безопасность. Рекомендуем использовать надёжный уникальный пароль и не передавать его третьим лицам.</p>
    <p style="margin:0 0 10px;">5.3. При обнаружении утечки данных мы уведомим пострадавших пользователей в разумные сроки.</p>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">6. Сроки хранения данных</h3>
    <p style="margin:0 0 10px;">6.1. Персональные данные хранятся в течение срока действия аккаунта и в течение 5 лет после его закрытия — в соответствии с требованиями AML-законодательства.</p>
    <p style="margin:0 0 10px;">6.2. Данные о транзакциях хранятся не менее 5 лет согласно требованиям лицензирующих органов.</p>
    <p style="margin:0 0 10px;">6.3. По истечении срока хранения данные удаляются или обезличиваются.</p>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">7. Ваши права</h3>
    <?php
    $rights = [
      'Право на доступ — запросить копию своих данных',
      'Право на исправление — потребовать уточнения неверных данных',
      'Право на удаление — запросить удаление данных (с учётом законодательных ограничений)',
      'Право на ограничение обработки',
      'Право на возражение против обработки в маркетинговых целях',
      'Право на перенос данных в машиночитаемом формате',
    ];
    foreach ($rights as $r) echo "<p style='margin:0 0 6px;padding-left:16px;position:relative;'><span style='position:absolute;left:0;color:#e0245e;'>→</span> " . htmlspecialchars($r) . "</p>";
    ?>
    <p style="margin:12px 0 0;">Для реализации прав обращайтесь в <a href="/support" style="color:#e0245e;">службу поддержки</a>. Мы ответим в течение 30 дней.</p>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">8. Несовершеннолетние</h3>
    <p style="margin:0 0 10px;">Наши услуги предназначены исключительно для лиц старше 18 лет. Мы не собираем намеренно данные несовершеннолетних. При обнаружении такого аккаунта мы незамедлительно его блокируем и удаляем соответствующие данные.</p>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">9. Изменения политики</h3>
    <p style="margin:0 0 10px;">Мы оставляем за собой право обновлять настоящую Политику. Актуальная версия всегда доступна на Сайте. Продолжение использования Сайта означает согласие с обновлённой Политикой.</p>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">10. Контакты</h3>
    <p style="margin:0;">По вопросам защиты данных: <a href="/support" style="color:#e0245e;">служба поддержки</a>. Оператор данных: Novaspin N.V., Zuikertuintjeweg Z/N, Кюрасао.</p>

  </div>

  <div style="display:flex;gap:10px;margin-bottom:12px;">
    <a href="/policy" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:12px;background:#17151c;border:1px solid #e8ebf0;border-radius:12px;font-size:13px;font-weight:600;color:#1a1d2e;text-decoration:none;">
      <span class="material-icons" style="font-size:18px;color:#e0245e;">gavel</span> Соглашение
    </a>
    <a href="/responsible" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:12px;background:#17151c;border:1px solid #e8ebf0;border-radius:12px;font-size:13px;font-weight:600;color:#1a1d2e;text-decoration:none;">
      <span class="material-icons" style="font-size:18px;color:#10b981;">shield</span> Отв. игра
    </a>
  </div>
</div>

<?php
require ("components/outsidebar.php");
require ("components/modal.php");
require ("components/footer.php");
?>
<script>history.pushState(null, null, window.location.pathname.split("?")[0]);</script>
