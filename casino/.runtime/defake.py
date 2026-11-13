import re, pathlib
P = pathlib.Path('/app/casino/php-project')

def edit(rel, fn):
    f = P / rel
    s = f.read_text(encoding='utf-8', errors='surrogateescape')
    n = fn(s)
    assert n != s, rel
    f.write_text(n, encoding='utf-8', errors='surrogateescape')

def sub(pat, rep='', flags=re.S, count=0):
    return lambda s: re.sub(pat, rep, s, count=count, flags=flags)

# Fake bot bets -> disabled
(P / 'bots.php').write_text('<?php\necho json_encode(["ok" => false]);\n')
(P / 'admin/bot.php').write_text("<?php\nheader('Location: /admin');\nexit;\n")
edit('admin/admin-head.php', sub(r"[^\n]*'/admin/bot'[^\n]*\n"))

# Fake live wins feed -> removed
(P / 'components/livewins.php').write_text('')

# Fake online -> real count only
edit('core.php', lambda s: s.replace('$online_default + intval($fake_online)', '$online_default'))
edit('admin/settings.php', sub(r'\s*<div class="form-row">\s*<div class="form-group">\s*<label>Fake online count.*?</div>\s*</div>(?=\s*<button)', '', count=1))
edit('admin/settings.php', sub(r",\s*fakeonline:[^\n]*\n\s*fakeinterval:[^\n]*\n", "\n"))
edit('admin/admin_func.php', lambda s: s.replace(",\n             fake_online=$fo, fake_interval=$fi\");", "\");"))

# Fake "fairness check" modal (random hashes from txt files) -> removed
edit('components/modal.php', sub(r'^<\?\s*\n\s*\$f_contents3 = file\("hashverify\.txt"\);.*?\?>', '', count=1))
edit('components/modal.php', sub(r'<div class="modal fade" id="modalVerify".*?<!-- modal-dialog -->\s*</div>', '', count=1))
for g in ['dice.php', 'x50.php', 'mines.php', 'bubbles.php']:
    edit(g, sub(r'[^\n]*href="#modalVerify"[^\n]*\n', '', flags=0))

# Fake tournament -> page removed, links removed
(P / 'tournament.php').write_text("<?php\nheader('Location: /');\nexit;\n")
edit('index.php', sub(r'\s*<!-- Баннер турнира -->\s*<div class="bw-banner" onclick="location.href=\'/tournament\'">.*?</div>\s*</div>\s*</div>', '', count=1))
edit('components/footer.php', sub(r'[^\n]*<a href="/tournament" style[^\n]*\n', '', flags=0))
edit('components/footer.php', sub(r'\s*<a href="/tournament" class="bw-sidebar-link">.*?</a>', '', count=1))
edit('components/footer.php', sub(r'\s*<a href="/tournament" class="bw-nav-item".*?</a>', '', count=1))
edit('components/outsidebar.php', sub(r'\s*<a href="/tournament" class="mobile-menu__navlink ">.*?</a>', '', count=1))
for f in ['components/outsidebar.php', 'components/header.php', 'components/header_ban.php']:
    edit(f, sub(r'[^\n]*<a href="/tournament(no)?"\s*style[^\n]*\n', '', flags=0))
edit('components/switchpanel.php', sub(r'[^\n]*tooltip="Турнир"[^\n]*\n', '', flags=0))
print('done')
