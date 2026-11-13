# RUBIN casino iteration 2: admin /admin/rtp (RTP + anti-minus + max win) wired into dice/bubbles/x50/mines
import os, re, time, subprocess, pytest, requests

def _url():
    u = os.environ.get('REACT_APP_BACKEND_URL')
    if not u:
        for l in open('/app/frontend/.env'):
            if l.startswith('REACT_APP_BACKEND_URL='):
                u = l.split('=', 1)[1].strip()
    return u.rstrip('/')

BASE = _url()
SQL = ['/app/casino/.runtime/mysql/bin/mysql', '--socket=/app/casino/mysql-run/mysql.sock', '-uroot', 'beezename', '-N', '-e']
LOGIN, PASS = 'tst465641', 'pass1234'
UA = {'User-Agent': 'Mozilla/5.0 pytest-rubin'}

def db(q):
    return subprocess.run(SQL + [q], capture_output=True, text=True).stdout.strip()

def balance():
    return float(db(f"select balance from kot_user where login='{LOGIN}'"))

def new_session():
    se = requests.Session(); se.headers.update(UA)
    r = se.post(f'{BASE}/action.php', data={'type': 'login', 'login': LOGIN, 'pass': PASS})
    assert r.json()['success'] == 'success', r.text
    return se

def get_csrf(se):
    for _ in range(3):
        r = se.get(f'{BASE}/admin/rtp')
        m = re.search(r'name="csrf" content="([0-9a-f]+)"', r.text)
        if r.status_code == 200 and m:
            return m.group(1), r.text
        time.sleep(2)
    pytest.skip('admin rtp csrf not available')

def admin_post(se, csrf, data):
    return se.post(f'{BASE}/admin/admin_func.php', headers={'X-CSRF-Token': csrf}, data=data).json()

def rtp(se, csrf, dice=97, mines=97, bubbles=97, x50=97, max_win=0):
    return admin_post(se, csrf, {'type': 'saveRTP', 'rtp_dice': dice, 'rtp_mines': mines,
                                 'rtp_bubbles': bubbles, 'rtp_x50': x50, 'max_win': max_win})

def antim(se, csrf, **on):
    d = {'type': 'saveAntiMinus'}
    for g in ['dice', 'mines', 'bubbles', 'x50']:
        d['antim_' + g] = 1 if g in on else 0
        d['thresh_' + g] = on.get(g, 30)
    return admin_post(se, csrf, d)

@pytest.fixture(scope='module')
def adm():
    se = new_session()
    csrf, html = get_csrf(se)
    bal0 = balance()
    yield se, csrf, html
    db("update kot_config set rtp_dice=97,rtp_mines=97,rtp_bubbles=97,rtp_x50=97,max_win=0,antim_dice=0,antim_mines=0,antim_bubbles=0,antim_x50=0")
    db(f"update kot_user set balance={max(bal0, 2000)} where login='{LOGIN}'")

# Admin page structure
def test_admin_page(adm):
    _, _, html = adm
    for t in ['id="rtp_dice"', 'id="rtp_mines"', 'id="rtp_bubbles"', 'id="rtp_x50"', 'id="antim_dice"',
              'id="thresh_x50"', 'id="max_win"', 'saveRTP()', 'saveAntiMinus()']:
        assert t in html, t
    assert 'Fatal error' not in html and 'Warning:' not in html

# Unauthenticated admin_func is rejected
def test_admin_func_no_csrf():
    se = new_session()
    r = se.post(f'{BASE}/admin/admin_func.php', data={'type': 'saveRTP', 'rtp_dice': 10})
    assert db('select rtp_dice from kot_config') != '10.00'
    assert r.status_code in (200, 403)

# saveRTP persists + clamps
def test_save_rtp_persist(adm):
    se, csrf, _ = adm
    assert rtp(se, csrf, dice=91, mines=92, bubbles=93, x50=94, max_win=123)['success'] == 'success'
    assert db('select rtp_dice,rtp_mines,rtp_bubbles,rtp_x50,max_win from kot_config') == '91.00\t92.00\t93.00\t94.00\t123.00'
    html = se.get(f'{BASE}/admin/rtp').text
    assert 'value="91"' in html and 'value="123"' in html
    rtp(se, csrf, dice=500, max_win=-3)
    assert db('select rtp_dice,max_win from kot_config') == '99.00\t0.00'
    assert rtp(se, csrf)['success'] == 'success'

# saveAntiMinus persists
def test_save_antim_persist(adm):
    se, csrf, _ = adm
    assert antim(se, csrf, dice=30, x50=55)['success'] == 'success'
    assert db('select antim_dice,antim_thresh_dice,antim_mines,antim_x50,antim_thresh_x50 from kot_config') == '1\t30.00\t0\t1\t55.00'
    html = se.get(f'{BASE}/admin/rtp').text
    assert re.search(r'id="antim_dice"\s+checked', html)
    antim(se, csrf)
    assert db('select antim_dice+antim_mines+antim_bubbles+antim_x50 from kot_config') == '0'

# Dice RTP 90 -> fullwin 18 for 10 @ 50
def test_dice_rtp90(adm):
    se, csrf, _ = adm
    rtp(se, csrf, dice=90)
    assert 'dice:0.9' in se.get(f'{BASE}/dice').text
    won = False
    for _ in range(12):
        b0 = balance()
        d = se.post(f'{BASE}/action.php', data={'type': 'minbet', 'sum': 10, 'per': 50}).json()
        assert str(d['fullwin']) in ('18', '18.0'), d
        if d['success'] == 'success':
            assert round(balance() - b0, 2) == 8.0; won = True; break
        assert round(b0 - balance(), 2) == 10
    rtp(se, csrf)
    assert won

# Dice anti-minus: chance 2, bet 10, threshold 30 -> forced win once loss >= 30
@pytest.mark.parametrize('kind', ['minbet', 'maxbet'])
def test_dice_antiminus(adm, kind):
    se, csrf, _ = adm
    antim(se, csrf, dice=30)
    p = new_session()
    loss, forced = 0, 0
    for _ in range(15):
        d = p.post(f'{BASE}/action.php', data={'type': kind, 'sum': 10, 'per': 2}).json()
        if loss >= 30:
            assert d['success'] == 'success', f'expected forced win, got {d}'
            forced += 1; loss = 0
            if forced == 2: break
            continue
        if d['success'] == 'success': loss = 0
        else: loss += 10
    antim(se, csrf)
    assert forced == 2

# Bubbles anti-minus (x10, bet 10, thresh 30)
def test_bubbles_antiminus(adm):
    se, csrf, _ = adm
    antim(se, csrf, bubbles=30)
    p = new_session(); loss = 0; ok = False
    for _ in range(12):
        d = p.post(f'{BASE}/bubblesfunc.php', data={'type': 'bubble', 'sum': 10, 'per': 10}).json()
        if loss >= 30:
            assert d['success'] == 'success', d; ok = True; break
        loss = 0 if d['success'] == 'success' else loss + 10
    antim(se, csrf)
    assert ok

# X50 multipliers + anti-minus on x50 sector
def test_x50_mult_and_antiminus(adm):
    se, csrf, _ = adm
    html = se.get(f'{BASE}/x50').text
    for m in ['2.01', '3.08', '5.24', '52.38']:
        assert m in html, m
    antim(se, csrf, x50=30)
    p = new_session(); loss = 0; ok = False
    for _ in range(12):
        b0 = balance()
        d = p.post(f'{BASE}/wheel.php', data={'wheel': 50, 'bet': 10}).json()
        if loss >= 30:
            assert 'good' in d, d
            assert round(balance() - b0, 2) == round(10 * 52.38 - 10, 2)
            ok = True; break
        loss = 0 if 'good' in d else loss + 10
    antim(se, csrf)
    assert ok

# Mines anti-minus: 24 bombs, bet 10, thresh 30 -> 4th game first click safe
def test_mines_antiminus(adm):
    se, csrf, _ = adm
    antim(se, csrf, mines=30)
    p = new_session(); loss = 0; ok = False
    for _ in range(8):
        c = p.post(f'{BASE}/action.php', data={'type': 'createGameMines', 'bomb': 24, 'amount': 10}).json()
        assert 'success' in c, c
        r = p.post(f'{BASE}/action.php', data={'type': 'chGameMines', 'id': 13}).json()
        if loss >= 30:
            assert 'lose' not in r, r
            f = p.post(f'{BASE}/action.php', data={'type': 'finishMines'}).json()
            assert 'success' in f, f
            ok = True; break
        if 'lose' in r: loss += 10
        else:
            p.post(f'{BASE}/action.php', data={'type': 'finishMines'}); loss = 0
    antim(se, csrf)
    assert ok

# Max win limit
def test_max_win(adm):
    se, csrf, _ = adm
    rtp(se, csrf, max_win=50)
    b0 = balance()
    d = se.post(f'{BASE}/action.php', data={'type': 'minbet', 'sum': 10, 'per': 10}).json()
    assert d['success'] == 'error' and 'Макс. выигрыш за ставку 50' in d['error'], d
    w = se.post(f'{BASE}/wheel.php', data={'wheel': 50, 'bet': 10}).json()
    assert 'Макс. выигрыш' in w.get('mess', '')
    assert balance() == b0
    rtp(se, csrf)
    assert db('select max_win from kot_config') == '0.00'
