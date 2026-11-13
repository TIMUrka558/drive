<?php $not_admin = !empty($_ar); ?><!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>RUBIN — Вход в админ-панель</title>
<style>
  body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#0d0c11; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; color:#fff; }
  .card { width:100%; max-width:360px; margin:16px; background:#17151c; border:1px solid #26222d; border-radius:18px; padding:28px; box-shadow:0 20px 60px rgba(0,0,0,.5); }
  .brand { display:flex; align-items:center; gap:10px; font-weight:800; font-size:22px; letter-spacing:2px; margin-bottom:6px; }
  .brand img { width:34px; }
  .sub { color:rgba(255,255,255,.6); font-size:13px; margin-bottom:22px; }
  input { width:100%; box-sizing:border-box; height:46px; margin-bottom:12px; padding:0 14px; border-radius:12px; border:1px solid #2f2a36; background:#100f14; color:#fff; font-size:15px; outline:none; }
  input:focus { border-color:#e0245e; }
  button { width:100%; height:48px; border:0; border-radius:12px; color:#fff; font-weight:700; font-size:15px; cursor:pointer; background:linear-gradient(135deg,#ff4a7d,#e0245e 45%,#a8143f); box-shadow:0 6px 20px rgba(224,36,94,.35); }
  .err { color:#ff5876; font-size:13px; min-height:18px; margin-bottom:8px; }
  a { color:rgba(255,255,255,.7); font-size:13px; display:block; text-align:center; margin-top:16px; }
</style>
</head>
<body>
<div class="card" data-testid="admin-login-card">
  <div class="brand"><img src="/images/rubin-gem.svg" alt="">RUBIN</div>
  <div class="sub"><?= $not_admin ? 'У аккаунта ' . htmlspecialchars($_ar['login']) . ' нет прав администратора. Войдите под админ-аккаунтом.' : 'Вход в админ-панель' ?></div>
  <form id="f" data-testid="admin-login-form">
    <input name="login" placeholder="Логин" autocomplete="username" required data-testid="admin-login-input">
    <input name="pass" type="password" placeholder="Пароль" autocomplete="current-password" required data-testid="admin-password-input">
    <div class="err" id="err" data-testid="admin-login-error"></div>
    <button type="submit" data-testid="admin-login-submit">Войти</button>
  </form>
  <a href="/" data-testid="admin-login-home">На сайт</a>
</div>
<script>
document.getElementById('f').addEventListener('submit', function (e) {
  e.preventDefault();
  var fd = new FormData(this); fd.append('type', 'login');
  fetch('/action.php', { method: 'POST', body: fd, credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (d.success === 'success') location.reload();
      else document.getElementById('err').textContent = d.error || 'Неверный логин или пароль';
    })
    .catch(function () { document.getElementById('err').textContent = 'Ошибка соединения'; });
});
</script>
</body>
</html>
