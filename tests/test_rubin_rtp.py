# RUBIN casino: RTP + max-win limit regression tests (dice, bubbles, x50 wheel, mines, admin saveRTP)
import os, re, time, subprocess, pytest, requests

def _url():
    u = os.environ.get('REACT_APP_BACKEND_URL')
    if not u:
        for l in open('/app/frontend/.env'):
            if l.startswith('REACT_APP_BACKEND_URL='):
                u = l.split('=', 1)[1].strip()
    return u.rstrip('/')

BASE = _url()
SQL = ['mysql', '-S', '/app/casino/mysql-run/mysql.sock', '-uroot', 'beezename', '-N', '-e']
LOGIN, PASS = 'tst465641', 'pass1234'

def db(q):
    return subprocess.run(SQL + [q], capture_output=True, text=True).stdout.strip()

def balance():
    return float(db(f"select balance from kot_user where login='{LOGIN}'"))

@pytest.fixture(scope='module')
def s():
    se = requests.Session()
    se.headers['User-Agent'] = 'Mozilla/5.0 pytest'
    r = se.post(f'{BASE}/action.php', data={'type': 'login', 'login': LOGIN, 'pass': PASS})
    assert r.json()['success'] == 'success'
    return se

@pytest.fixture(scope='module')
def csrf(s):
    html = ''
    for _ in range(3):
        r = s.get(f'{BASE}/admin/rtp')
        if r.status_code == 200: html = r.text; break
        time.sleep(2)
    m = re.search(r'name="csrf" content="([0-9a-f]+)"', html)
    if not m:
        pytest.skip('admin rtp page/csrf not available')
    return m.group(1)

def save_rtp(s, csrf, dice=97, mines=97, bubbles=97, max_win=0):
    r = s.post(f'{BASE}/admin/admin_func.php', headers={'X-CSRF-Token': csrf},
               data={'type': 'saveRTP', 'rtp_dice': dice, 'rtp_mines': mines, 'rtp_bubbles': bubbles, 'max_win': max_win})
    return r

@pytest.fixture(scope='module', autouse=True)
def restore():
    yield
    db("update kot_config set rtp_dice=97,rtp_mines=97,rtp_bubbles=97,max_win=0")

# Admin RTP page
def test_admin_rtp_page(s, csrf):
    html = s.get(f'{BASE}/admin/rtp').text
    for t in ['rtp-dice', 'rtp-mines', 'rtp-bubbles', 'save-rtp-btn', 'max_win']:
        assert t in html, t
    assert 'nti-minus' not in html and 'Антиминус' not in html

# Dice RTP 97 -> fullwin 19.4 and payout consistent
def test_dice_rtp97(s, csrf):
    assert save_rtp(s, csrf).json()['success'] == 'success'
    for _ in range(12):
        b0 = balance()
        d = s.post(f'{BASE}/action.php', data={'type': 'minbet', 'sum': 10, 'per': 50}).json()
        assert d['fullwin'] == '19.4'
        b1 = balance()
        if d['success'] == 'success':
            assert round(b1 - b0, 2) == 9.4
            return
        assert round(b0 - b1, 2) == 10
    pytest.fail('no win in 12 tries (p=0.0002)')

# Dice RTP 90 -> 18.00
def test_dice_rtp90(s, csrf):
    assert save_rtp(s, csrf, dice=90).json()['success'] == 'success'
    assert db('select rtp_dice from kot_config') == '90.00'
    html = s.get(f'{BASE}/dice').text
    assert 'dice:0.9' in html
    for _ in range(12):
        b0 = balance()
        d = s.post(f'{BASE}/action.php', data={'type': 'maxbet', 'sum': 10, 'per': 50}).json()
        assert d['fullwin'] == '18'
        if d['success'] == 'success':
            assert round(balance() - b0, 2) == 8.0
            break
    save_rtp(s, csrf, dice=97)

# Max win 50 rejects
def test_max_win_limit(s, csrf):
    assert save_rtp(s, csrf, max_win=50).json()['success'] == 'success'
    b0 = balance()
    d = s.post(f'{BASE}/action.php', data={'type': 'minbet', 'sum': 10, 'per': 10}).json()
    assert d['success'] == 'error' and 'Макс. выигрыш за ставку 50' in d['error']
    w = s.post(f'{BASE}/wheel.php', data={'wheel': 50, 'bet': 10}).json()
    assert 'Макс. выигрыш' in w.get('mess', '') and 'key' not in w
    bb = s.post(f'{BASE}/bubblesfunc.php', data={'type': 'bubble', 'sum': 10, 'per': 10}).json()
    assert 'Макс. выигрыш' in bb['number']
    assert balance() == b0
    save_rtp(s, csrf, max_win=0)

# Bubbles result format and balance
def test_bubbles(s):
    b0 = balance()
    d = s.post(f'{BASE}/bubblesfunc.php', data={'type': 'bubble', 'sum': 1, 'per': 2}).json()
    assert d['number'].startswith('Выпало x')
    exp = 1 if d['success'] == 'success' else -1
    assert round(balance() - b0, 2) == exp

# X50 wheel
def test_wheel(s):
    b0 = balance()
    d = s.post(f'{BASE}/wheel.php', data={'wheel': 2, 'bet': 1}).json()
    assert 'key' in d and ('good' in d or 'bad' in d)
    exp = 1 if 'good' in d else -1
    assert round(balance() - b0, 2) == exp

# Mines rate strip scaled (3 bombs step1 = 1.1)
def test_mines_rate(s):
    t = s.post(f'{BASE}/action.php', data={'type': 'getMinesRate', 'bomb': 3, 'amount': 1}).text
    assert 'x1.1<' in t and 'x1.14' not in t

# Max win 0 restored and save validation clamps
def test_save_clamps(s, csrf):
    save_rtp(s, csrf, dice=200, max_win=-5)
    assert db('select rtp_dice,max_win from kot_config') == '99.90\t0.00'
    save_rtp(s, csrf)
    assert db('select rtp_dice,max_win from kot_config') == '97.00\t0.00'
