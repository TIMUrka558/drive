<?php
require ("components/header.php");
require ("components/gamebar.php");
require ("components/livewins.php");
?>
<script>
function bots(){if(navigator.onLine)$.ajax({url:'bots.php',timeout:10000});}
function historys(){if(navigator.onLine)$.ajax({url:'core.php',timeout:10000,success:function(d){try{var o=jQuery.parseJSON(d);if(o.online)$('#onlineCount').text(o.online);}catch(e){}}});}
setInterval(bots,5000); setInterval(historys,5000);

var currentTicketId = null;

// Показать список тикетов
function showTicketList() {
  currentTicketId = null;
  $('#bw-ticket-view').hide();
  $('#bw-new-ticket-form').hide();
  $('#bw-ticket-list-wrap').show();
  loadTickets();
}

// Загрузить список тикетов пользователя
function loadTickets() {
  $.ajax({ url:'/action.php', type:'POST', data:{type:'adminGetTickets', filter:'all'}, success:function(d){
    // use raw PHP-rendered ticket list on page load
  }});
}

// Открыть форму создания тикета
function showNewTicketForm() {
  $('#bw-ticket-list-wrap').hide();
  $('#bw-ticket-view').hide();
  $('#bw-new-ticket-form').show();
  $('#bw-new-subject').val('');
  $('#bw-new-msg').val('');
  $('#bw-create-error').hide();
}

// Создать тикет
function createTicket() {
  var subj = $('#bw-new-subject').val().trim();
  var msg  = $('#bw-new-msg').val().trim();
  if(subj.length < 3){ $('#bw-create-error').show().text('Тема слишком короткая'); return; }
  if(msg.length  < 5){ $('#bw-create-error').show().text('Опишите проблему подробнее'); return; }
  var btn = document.getElementById('bw-create-btn');
  btn.disabled = true;
  $.ajax({
    url:'/action.php', type:'POST',
    data:{type:'createTicket', subject:subj, message:msg},
    success:function(d){
      btn.disabled = false;
      try{
        var o=jQuery.parseJSON(d);
        if(o.success==='success'){ location.reload(); }
        else{ $('#bw-create-error').show().text(o.error||'Ошибка'); }
      }catch(e){ $('#bw-create-error').show().text('Ошибка сервера'); }
    }, error:function(){ btn.disabled=false; }
  });
}

// Открыть переписку по тикету
function openTicket(tid) {
  currentTicketId = tid;
  $('#bw-ticket-list-wrap').hide();
  $('#bw-new-ticket-form').hide();
  $('#bw-ticket-msgs').html('<div style="text-align:center;color:#9ca3af;padding:20px">Загрузка...</div>');
  $('#bw-ticket-view').show();
  loadMsgs(tid);
}

function loadMsgs(tid){
  $.ajax({
    url:'/action.php', type:'POST', data:{type:'getTicketMsgs',ticket_id:tid},
    success:function(d){
      try{
        var o=jQuery.parseJSON(d);
        if(o.success!=='success'){ $('#bw-ticket-msgs').html('<div style="color:#ef4444;padding:10px">Ошибка загрузки</div>'); return; }
        var t=o.ticket; var msgs=o.messages;
        // Status badge
        var sbadge={'open':'<span style="background:#17151c;color:#e0245e;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">Открыт</span>',
          'answered':'<span style="background:#17151c;color:#10b981;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">Отвечен</span>',
          'closed':'<span style="background:#17151c;color:#6b7280;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">Закрыт</span>'}[t.status]||'';
        $('#bw-ticket-title').html(escH(t.subject)+' '+sbadge);
        var html='';
        msgs.forEach(function(m){
          var isAdmin=parseInt(m.is_admin)===1;
          var name=isAdmin?'<strong style="color:#e0245e;">Поддержка</strong>':escH(m.login);
          var bg=isAdmin?'linear-gradient(135deg,#e0245e,#a8143f)':'#f8faff';
          var txtColor=isAdmin?'#fff':'#1a1d2e';
          var align=isAdmin?'flex-end':'flex-start';
          html+='<div style="display:flex;justify-content:'+align+';margin-bottom:12px;">'
            +'<div style="max-width:80%;background:'+bg+';border-radius:14px;padding:10px 14px;">'
            +'<div style="font-size:11px;font-weight:600;color:'+(isAdmin?'rgba(255,255,255,0.8)':'#6b7280')+';">'+name+' · '+escH(m.created_at)+'</div>'
            +'<div style="font-size:13px;margin-top:4px;color:'+txtColor+';">'+escH(m.message)+'</div>'
            +'</div></div>';
        });
        $('#bw-ticket-msgs').html(html||'<div style="color:#9ca3af;text-align:center;padding:20px">Нет сообщений</div>');
        // Show/hide reply depending on status
        if(t.status==='closed'){
          $('#bw-reply-area').hide();
          $('#bw-closed-notice').show();
        }else{
          $('#bw-reply-area').show();
          $('#bw-closed-notice').hide();
        }
        $('#bw-close-ticket-btn').toggle(t.status!=='closed');
      }catch(e){ $('#bw-ticket-msgs').html('<div style="color:#ef4444;padding:10px">Ошибка парсинга</div>'); }
    }
  });
}

function sendTicketMsg(){
  var msg=$('#bw-reply-input').val().trim();
  if(!msg){ return; }
  var btn=document.getElementById('bw-send-btn');
  btn.disabled=true;
  $.ajax({
    url:'/action.php', type:'POST', data:{type:'sendTicketMsg',ticket_id:currentTicketId,message:msg},
    success:function(d){
      btn.disabled=false;
      try{
        var o=jQuery.parseJSON(d);
        if(o.success==='success'){ $('#bw-reply-input').val(''); loadMsgs(currentTicketId); }
      }catch(e){}
    }, error:function(){ btn.disabled=false; }
  });
}

function closeTicket(){
  if(!confirm('Закрыть обращение?')) return;
  $.ajax({
    url:'/action.php', type:'POST', data:{type:'closeTicketUser',ticket_id:currentTicketId},
    success:function(){ loadMsgs(currentTicketId); }
  });
}

function escH(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
</script>

<div class="bw-page">
  <div class="bw-page-title">
    <div class="bw-page-icon"><span class="material-icons">support_agent</span></div>
    Поддержка
  </div>

<?php if (!$sid): ?>
  <!-- Гость -->
  <div class="bw-card" style="text-align:center;padding:40px 20px;">
    <span class="material-icons" style="font-size:48px;color:#e8ebf0;">lock</span>
    <div style="font-size:16px;font-weight:700;color:#1a1d2e;margin-top:12px;">Войдите чтобы написать в поддержку</div>
    <div style="font-size:13px;color:#6b7280;margin:6px 0 20px;">Поддержка доступна только зарегистрированным пользователям</div>
    <a href="#modalLogIn" data-toggle="modal"
       style="display:inline-flex;align-items:center;justify-content:center;padding:12px 28px;border-radius:12px;background:linear-gradient(135deg,#e0245e,#a8143f);color:#fff;font-weight:700;font-size:14px;text-decoration:none;">
      Войти
    </a>
  </div>

<?php else: ?>

  <!-- ===== СПИСОК ТИКЕТОВ ===== -->
  <div id="bw-ticket-list-wrap">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
      <div style="font-size:13px;color:#6b7280;">Ваши обращения</div>
      <button onclick="showNewTicketForm()" class="bw-btn bw-btn-primary" style="font-size:13px;padding:8px 16px;">
        <span class="material-icons" style="font-size:16px;vertical-align:middle;">add</span> Новое обращение
      </button>
    </div>

    <?php
    $tq = mysql_query("SELECT * FROM kot_tickets WHERE user_id='$id' ORDER BY updated_at DESC LIMIT 30");
    $ticket_count = 0;
    while ($tr = mysql_fetch_assoc($tq)):
      $ticket_count++;
      $statusLabel = ['open'=>'Открыт','answered'=>'Отвечен','closed'=>'Закрыт'][$tr['status']] ?? $tr['status'];
      $statusStyle = [
        'open'     => 'background:#17151c;color:#e0245e;',
        'answered' => 'background:#17151c;color:#10b981;',
        'closed'   => 'background:#17151c;color:#6b7280;',
      ][$tr['status']] ?? '';
    ?>
    <div class="bw-card" onclick="openTicket(<?= $tr['id'] ?>)"
         style="cursor:pointer;margin-bottom:8px;display:flex;align-items:center;gap:12px;">
      <span class="material-icons" style="color:#e0245e;font-size:24px;flex-shrink:0;">chat_bubble_outline</span>
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:700;color:#1a1d2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          <?= htmlspecialchars($tr['subject']) ?>
        </div>
        <div style="font-size:11px;color:#9ca3af;margin-top:2px;"><?= $tr['updated_at'] ?></div>
      </div>
      <span style="flex-shrink:0;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;<?= $statusStyle ?>">
        <?= $statusLabel ?>
      </span>
      <span class="material-icons" style="color:#d1d5db;font-size:18px;flex-shrink:0;">chevron_right</span>
    </div>
    <?php endwhile; ?>

    <?php if ($ticket_count === 0): ?>
    <div class="bw-card" style="text-align:center;padding:40px 20px;">
      <span class="material-icons" style="font-size:48px;color:#e8ebf0;">inbox</span>
      <div style="font-size:15px;font-weight:700;color:#1a1d2e;margin-top:12px;">Обращений пока нет</div>
      <div style="font-size:13px;color:#6b7280;margin:6px 0 20px;">Если у вас есть вопрос — создайте новое обращение</div>
      <button onclick="showNewTicketForm()" class="bw-btn bw-btn-primary">Написать в поддержку</button>
    </div>
    <?php endif; ?>

    <!-- Инфо-блок -->
    <div class="bw-card bw-bonus-card-blue" style="margin-top:12px;">
      <div style="display:flex;align-items:flex-start;gap:10px;">
        <span class="material-icons" style="font-size:22px;color:rgba(255,255,255,0.9);flex-shrink:0;margin-top:2px;">schedule</span>
        <div>
          <div style="font-size:13px;font-weight:700;color:#fff;">Время ответа</div>
          <div style="font-size:12px;color:rgba(255,255,255,0.85);margin-top:4px;">
            Мы отвечаем в течение 2–12 часов. Среднее время ответа — <strong>3 часа</strong>. Поддержка работает 24/7.
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== ФОРМА СОЗДАНИЯ ТИКЕТА ===== -->
  <div id="bw-new-ticket-form" style="display:none;">
    <button onclick="showTicketList()" style="display:flex;align-items:center;gap:4px;background:none;border:none;color:#e0245e;font-weight:600;font-size:13px;cursor:pointer;margin-bottom:12px;padding:0;">
      <span class="material-icons" style="font-size:18px;">arrow_back</span> Назад к списку
    </button>

    <div class="bw-card">
      <div style="font-size:14px;font-weight:800;color:#1a1d2e;margin-bottom:16px;">Новое обращение</div>

      <div style="margin-bottom:12px;">
        <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:6px;">Тема обращения</label>
        <input id="bw-new-subject" class="bw-input" placeholder="Например: Не зачислился депозит" maxlength="150">
      </div>

      <div style="margin-bottom:14px;">
        <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:6px;">Описание проблемы</label>
        <textarea id="bw-new-msg" class="bw-input" rows="5" placeholder="Опишите проблему максимально подробно: что произошло, когда, какая сумма и т.д."
          style="resize:vertical;min-height:100px;font-family:'Inter',sans-serif;"></textarea>
      </div>

      <div id="bw-create-error" style="display:none;color:#ef4444;font-size:12px;margin-bottom:10px;"></div>

      <button id="bw-create-btn" onclick="createTicket()" class="bw-btn bw-btn-primary bw-btn-full">
        <span class="material-icons" style="font-size:18px;vertical-align:middle;">send</span>
        Отправить обращение
      </button>
    </div>

    <div class="bw-card" style="margin-top:12px;">
      <div style="font-size:12px;color:#6b7280;line-height:1.6;">
        <strong style="color:#1a1d2e;">Рекомендации:</strong><br>
        • Указывайте точную сумму и дату операции<br>
        • Прикладывайте ID транзакции при проблемах с депозитом<br>
        • Одно обращение — одна проблема
      </div>
    </div>
  </div>

  <!-- ===== ПРОСМОТР ТИКЕТА ===== -->
  <div id="bw-ticket-view" style="display:none;">
    <button onclick="showTicketList()" style="display:flex;align-items:center;gap:4px;background:none;border:none;color:#e0245e;font-weight:600;font-size:13px;cursor:pointer;margin-bottom:12px;padding:0;">
      <span class="material-icons" style="font-size:18px;">arrow_back</span> Назад к списку
    </button>

    <div class="bw-card" style="margin-bottom:12px;">
      <div style="font-size:14px;font-weight:800;color:#1a1d2e;margin-bottom:14px;" id="bw-ticket-title"></div>
      <div id="bw-ticket-msgs" style="max-height:400px;overflow-y:auto;margin-bottom:4px;"></div>
    </div>

    <div id="bw-closed-notice" style="display:none;background:#17151c;border-radius:12px;padding:12px 14px;margin-bottom:12px;text-align:center;font-size:13px;color:#6b7280;">
      <span class="material-icons" style="font-size:18px;vertical-align:middle;margin-right:4px;">lock</span>
      Обращение закрыто. Если проблема повторилась — создайте новое.
    </div>

    <div id="bw-reply-area" class="bw-card">
      <textarea id="bw-reply-input" class="bw-input" rows="3" placeholder="Ваш ответ..."
        style="resize:none;margin-bottom:10px;font-family:'Inter',sans-serif;"
        onkeydown="if(event.ctrlKey&&event.key==='Enter')sendTicketMsg();"></textarea>
      <div style="display:flex;gap:8px;">
        <button id="bw-send-btn" onclick="sendTicketMsg()" class="bw-btn bw-btn-primary" style="flex:1;">
          <span class="material-icons" style="font-size:18px;vertical-align:middle;">send</span> Отправить
        </button>
        <button id="bw-close-ticket-btn" onclick="closeTicket()"
          style="padding:10px 14px;border-radius:10px;border:1px solid #e8ebf0;background:#17151c;color:#6b7280;font-size:12px;font-weight:600;cursor:pointer;">
          Закрыть
        </button>
      </div>
      <div style="font-size:11px;color:#9ca3af;margin-top:6px;">Ctrl+Enter для отправки</div>
    </div>
  </div>

<?php endif; ?>
</div>

<?php
require ("components/outsidebar.php");
require ("components/modal.php");
require ("components/footer.php");
?>
<script>history.pushState(null, null, window.location.pathname.split("?")[0]);</script>
