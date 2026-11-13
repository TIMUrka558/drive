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
    <div class="bw-page-icon"><span class="material-icons">shield</span></div>
    Ответственная игра 18+
  </div>

  <!-- 18+ Предупреждение -->
  <div style="background:linear-gradient(135deg,#ef4444,#b91c1c);border-radius:16px;padding:20px;margin-bottom:12px;text-align:center;color:#fff;">
    <div style="font-size:48px;font-weight:900;line-height:1;margin-bottom:8px;">18+</div>
    <div style="font-size:14px;font-weight:700;">Только для совершеннолетних</div>
    <div style="font-size:12px;opacity:0.9;margin-top:4px;">Азартные игры могут вызывать зависимость. Играйте ответственно.</div>
  </div>

  <div class="bw-card" style="margin-bottom:12px;line-height:1.75;color:#374151;font-size:13px;">

    <div style="font-size:11px;color:#9ca3af;margin-bottom:18px;">Последнее обновление: 1 января 2024 г.</div>

    <p style="margin:0 0 14px;">RUBIN Casino искренне заботится о благополучии своих пользователей. Мы убеждены, что азартные игры должны быть развлечением, а не источником проблем. На этой странице мы рассказываем о принципах ответственной игры, признаках игровой зависимости и инструментах самоограничения.</p>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">1. Возрастное ограничение 18+</h3>
    <p style="margin:0 0 10px;">1.1. Участие в азартных играх на нашей платформе разрешено исключительно лицам, достигшим <strong>18-летнего возраста</strong>.</p>
    <p style="margin:0 0 10px;">1.2. Регистрация несовершеннолетних строго запрещена. Казино оставляет за собой право в любой момент запросить подтверждение возраста и заблокировать аккаунт при невозможности его подтвердить.</p>
    <p style="margin:0 0 10px;">1.3. Если вам известно о несовершеннолетнем, использующем нашу платформу, немедленно сообщите об этом в службу поддержки. Такой аккаунт будет немедленно заблокирован.</p>
    <p style="margin:0 0 10px;">1.4. Если в вашем доме есть дети или подростки, рекомендуем установить программы родительского контроля и не оставлять открытый доступ к аккаунту.</p>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">2. Принципы ответственной игры</h3>

    <div style="display:grid;gap:10px;margin-bottom:16px;">
      <div style="background:#2a1720;border-radius:12px;padding:14px;">
        <div style="font-size:13px;font-weight:700;color:#e0245e;margin-bottom:6px;">Играйте ради удовольствия</div>
        <div style="font-size:12px;color:#374151;">Азартные игры — это развлечение. Никогда не рассматривайте их как способ заработать или компенсировать финансовые потери.</div>
      </div>
      <div style="background:#17151c;border-radius:12px;padding:14px;">
        <div style="font-size:13px;font-weight:700;color:#10b981;margin-bottom:6px;">Устанавливайте лимиты заранее</div>
        <div style="font-size:12px;color:#374151;">Перед началом игры определите максимальную сумму, которую готовы потратить. Не превышайте её ни при каких обстоятельствах.</div>
      </div>
      <div style="background:#17151c;border-radius:12px;padding:14px;">
        <div style="font-size:13px;font-weight:700;color:#f59e0b;margin-bottom:6px;">Не гонитесь за проигрышем</div>
        <div style="font-size:12px;color:#374151;">Попытка «отыграться» — первый признак проблемы. Если вы проиграли установленный лимит, остановитесь.</div>
      </div>
      <div style="background:#17151c;border-radius:12px;padding:14px;">
        <div style="font-size:13px;font-weight:700;color:#ef4444;margin-bottom:6px;">Не играйте в долг</div>
        <div style="font-size:12px;color:#374151;">Никогда не используйте заёмные средства, кредиты или сбережения, предназначенные для жизненно важных нужд.</div>
      </div>
    </div>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">3. Признаки игровой зависимости</h3>
    <p style="margin:0 0 10px;">Обратите внимание, если вы замечаете у себя следующие признаки:</p>
    <?php
    $signs = [
      'Вы тратите на игру больше денег или времени, чем планировали',
      'Вы скрываете от близких факт игры или размер потраченных средств',
      'Вы продолжаете играть даже после достижения лимита',
      'Игра мешает вашей работе, учёбе или личным отношениям',
      'Вы используете игру как способ избавиться от стресса или негативных эмоций',
      'Вы берёте кредиты или одалживаете деньги для финансирования игры',
      'Вы чувствуете раздражение или беспокойство, когда не можете играть',
      'Вы безуспешно пытаетесь сократить или контролировать игру',
      'После крупного выигрыша у вас возникает непреодолимое желание играть ещё',
      'Вы думаете об игре большую часть времени',
    ];
    foreach ($signs as $s) echo "<p style='margin:0 0 6px;padding-left:20px;position:relative;'><span style='position:absolute;left:0;color:#ef4444;font-weight:700;'>!</span> " . htmlspecialchars($s) . "</p>";
    ?>
    <div style="background:#17151c;border-radius:12px;padding:14px;margin-top:14px;">
      <strong style="color:#ef4444;">Если вы ответили «да» на 3 и более пункта</strong> — рекомендуем воспользоваться инструментами самоограничения или обратиться за профессиональной помощью.
    </div>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">4. Инструменты самоограничения</h3>
    <p style="margin:0 0 10px;">RUBIN Casino предоставляет следующие инструменты для управления игрой:</p>

    <div style="display:grid;gap:8px;margin-bottom:14px;">
      <div style="background:#17151c;border:1px solid #e8ebf0;border-radius:12px;padding:12px 14px;display:flex;align-items:flex-start;gap:10px;">
        <span class="material-icons" style="color:#e0245e;font-size:20px;flex-shrink:0;">timer</span>
        <div>
          <div style="font-size:13px;font-weight:700;color:#1a1d2e;">Ограничение времени сессии</div>
          <div style="font-size:12px;color:#6b7280;margin-top:3px;">Установите напоминание о продолжительности игровой сессии</div>
        </div>
      </div>
      <div style="background:#17151c;border:1px solid #e8ebf0;border-radius:12px;padding:12px 14px;display:flex;align-items:flex-start;gap:10px;">
        <span class="material-icons" style="color:#e0245e;font-size:20px;flex-shrink:0;">account_balance_wallet</span>
        <div>
          <div style="font-size:13px;font-weight:700;color:#1a1d2e;">Лимит депозитов</div>
          <div style="font-size:12px;color:#6b7280;margin-top:3px;">Ограничьте сумму пополнений в день, неделю или месяц</div>
        </div>
      </div>
      <div style="background:#17151c;border:1px solid #e8ebf0;border-radius:12px;padding:12px 14px;display:flex;align-items:flex-start;gap:10px;">
        <span class="material-icons" style="color:#f59e0b;font-size:20px;flex-shrink:0;">pause_circle</span>
        <div>
          <div style="font-size:13px;font-weight:700;color:#1a1d2e;">Перерыв в игре</div>
          <div style="font-size:12px;color:#6b7280;margin-top:3px;">Временная блокировка аккаунта на срок от 1 дня до 6 месяцев</div>
        </div>
      </div>
      <div style="background:#17151c;border:1px solid #fee2e2;border-radius:12px;padding:12px 14px;display:flex;align-items:flex-start;gap:10px;">
        <span class="material-icons" style="color:#ef4444;font-size:20px;flex-shrink:0;">block</span>
        <div>
          <div style="font-size:13px;font-weight:700;color:#1a1d2e;">Самоисключение</div>
          <div style="font-size:12px;color:#6b7280;margin-top:3px;">Постоянная блокировка аккаунта. Повторная регистрация будет невозможна</div>
        </div>
      </div>
    </div>

    <p style="margin:0 0 10px;">Для активации любого из инструментов обратитесь в <a href="/support" style="color:#e0245e;">службу поддержки</a>. Мы обработаем запрос в течение 24 часов.</p>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">5. Правила для защиты пользователей</h3>
    <p style="margin:0 0 10px;">5.1. Казино вправе установить ограничения на аккаунт, если обнаружит признаки проблемной игры, даже без запроса пользователя.</p>
    <p style="margin:0 0 10px;">5.2. Мы не предоставляем кредиты и не принимаем ставки в долг.</p>
    <p style="margin:0 0 10px;">5.3. Казино не направляет маркетинговые материалы пользователям, которые активировали самоисключение.</p>
    <p style="margin:0 0 10px;">5.4. Мы регулярно обучаем сотрудников службы поддержки выявлению признаков игровой зависимости.</p>
    <p style="margin:0 0 10px;">5.5. Информация о самоисключении конфиденциальна и не разглашается третьим лицам (кроме случаев, предусмотренных законом).</p>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">6. Советы для безопасной игры</h3>
    <?php
    $tips = [
      'Играйте только на деньги, которые можете позволить себе потерять',
      'Делайте регулярные перерывы во время игровых сессий',
      'Не играйте под воздействием алкоголя или психоактивных веществ',
      'Не используйте азартные игры для борьбы с депрессией или тревогой',
      'Заранее установите бюджет и строго его соблюдайте',
      'Читайте условия бонусов перед их активацией',
      'Не позволяйте выигрышу изменить ваше отношение к риску',
      'Если чувствуете, что теряете контроль — немедленно остановитесь',
    ];
    foreach ($tips as $t) echo "<p style='margin:0 0 6px;padding-left:20px;position:relative;'><span style='position:absolute;left:0;color:#10b981;font-weight:700;'>✓</span> " . htmlspecialchars($t) . "</p>";
    ?>

    <h3 style="font-size:14px;font-weight:800;color:#1a1d2e;margin:20px 0 10px;">7. Организации по борьбе с зависимостью</h3>
    <p style="margin:0 0 10px;">Если вы или ваши близкие нуждаются в помощи, обратитесь в специализированные организации:</p>
    <div style="background:#17151c;border-radius:12px;padding:14px;margin-bottom:10px;">
      <div style="font-size:13px;font-weight:700;color:#1a1d2e;margin-bottom:6px;">Gamblers Anonymous</div>
      <div style="font-size:12px;color:#6b7280;">Международная организация самопомощи для людей с игровой зависимостью. Бесплатная поддержка и группы.</div>
    </div>
    <div style="background:#17151c;border-radius:12px;padding:14px;margin-bottom:10px;">
      <div style="font-size:13px;font-weight:700;color:#1a1d2e;margin-bottom:6px;">GamCare (gamcare.org.uk)</div>
      <div style="font-size:12px;color:#6b7280;">Поддержка, информация и консультации для людей, пострадавших от азартных игр.</div>
    </div>
    <div style="background:#17151c;border-radius:12px;padding:14px;">
      <div style="font-size:13px;font-weight:700;color:#1a1d2e;margin-bottom:6px;">BeGambleAware (begambleaware.org)</div>
      <div style="font-size:12px;color:#6b7280;">Независимая организация, предоставляющая бесплатные инструменты и консультации по ответственной игре.</div>
    </div>

  </div>

  <div style="background:linear-gradient(135deg,#e0245e,#a8143f);border-radius:16px;padding:20px;margin-bottom:12px;text-align:center;color:#fff;">
    <span class="material-icons" style="font-size:32px;margin-bottom:8px;display:block;">support_agent</span>
    <div style="font-size:14px;font-weight:700;margin-bottom:6px;">Нужна помощь?</div>
    <div style="font-size:12px;opacity:0.9;margin-bottom:14px;">Наша служба поддержки готова помочь вам настроить ограничения или ответить на вопросы</div>
    <a href="/support" style="display:inline-block;background:#17151c;color:#e0245e;font-weight:700;font-size:13px;padding:10px 24px;border-radius:10px;text-decoration:none;">Связаться с поддержкой</a>
  </div>

  <div style="display:flex;gap:10px;margin-bottom:12px;">
    <a href="/policy" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:12px;background:#17151c;border:1px solid #e8ebf0;border-radius:12px;font-size:13px;font-weight:600;color:#1a1d2e;text-decoration:none;">
      <span class="material-icons" style="font-size:18px;color:#e0245e;">gavel</span> Соглашение
    </a>
    <a href="/privacy" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:12px;background:#17151c;border:1px solid #e8ebf0;border-radius:12px;font-size:13px;font-weight:600;color:#1a1d2e;text-decoration:none;">
      <span class="material-icons" style="font-size:18px;color:#6b7280;">privacy_tip</span> Конфиденциальность
    </a>
  </div>
</div>

<?php
require ("components/outsidebar.php");
require ("components/modal.php");
require ("components/footer.php");
?>
<script>history.pushState(null, null, window.location.pathname.split("?")[0]);</script>
