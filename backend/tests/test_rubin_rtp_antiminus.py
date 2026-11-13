# RUBIN casino (PHP): RTP + anti-minus + games regression via public URL
import os, re, json, subprocess, time
import pytest
import requests

BASE_URL = os.environ.get('REACT_APP_BACKEND_URL', '').rstrip('/')
if not BASE_URL:
    for line in open('/app/frontend/.env'):
        if line.startswith('REACT_APP_BACKEND_URL='):
            BASE_URL = line.split('=', 1)[1].strip().rstrip('/')
MYSQL = ["/app/casino/.runtime/mysql/bin/mysql", "--socket=/app/casino/mysql-run/mysql.sock", "-u", "root", "beezename", "-N", "-e"]
LOGIN, PASS = "tst465641", "pass1234"


def sql(q):
    return subprocess.run(MYSQL + [q], capture_output=True, text=True).stdout.strip()


def balance():
    return float(sql(f"select balance from kot_user where login='{LOGIN}'"))


def pj(r):
    t = r.text.strip()
    i = t.find('{')
    return json.loads(t[i:]) if i >= 0 else {}


@pytest.fixture(scope="module")
def s():
    sql(f"update kot_user set balance=1000 where login='{LOGIN}'")
    ss = requests.Session()
    ss.headers.update({"User-Agent": "Mozilla/5.0 pytest"})
    r = ss.post(f"{BASE_URL}/action.php", data={"type": "login", "login": LOGIN, "pass": PASS})
    assert r.status_code == 200, r.text
    time.sleep(0.5)
    yield ss


@pytest.fixture(scope="module")
def csrf(s):
    r = s.get(f"{BASE_URL}/admin/rtp")
    assert r.status_code == 200
    m = re.search(r'name="csrf" content="([^"]+)"', r.text)
    assert m, "csrf meta missing on /admin/rtp (not admin?)"
    return m.group(1)


def admin_post(s, csrf, data):
    r = s.post(f"{BASE_URL}/admin/admin_func.php", data=data, headers={"X-CSRF-Token": csrf})
    return pj(r)


def set_antim(s, csrf, game=None, thresh=30):
    data = {"type": "saveAntiMinus"}
    for g in ['dice', 'mines', 'bubbles', 'x50']:
        data[f"antim_{g}"] = 1 if g == game else 0
        data[f"thresh_{g}"] = thresh
    return admin_post(s, csrf, data)


def set_rtp(s, csrf, v=97, max_win=0):
    data = {"type": "saveRTP", "max_win": max_win}
    for g in ['dice', 'mines', 'bubbles', 'x50']:
        data[f"rtp_{g}"] = v
    return admin_post(s, csrf, data)


# Admin page + save/persist
class TestAdminRTP:
    def test_admin_page_has_controls(self, s):
        t = s.get(f"{BASE_URL}/admin/rtp").text
        for g in ['dice', 'mines', 'bubbles', 'x50']:
            assert f'id="rtp_{g}"' in t
            assert f'id="antim_{g}"' in t
            assert f'id="thresh_{g}"' in t
        assert 'id="max_win"' in t

    def test_save_rtp_persists(self, s, csrf):
        d = admin_post(s, csrf, {"type": "saveRTP", "rtp_dice": 90, "rtp_mines": 95, "rtp_bubbles": 96, "rtp_x50": 94, "max_win": 0})
        assert d.get("success") == "success"
        assert sql("select rtp_dice,rtp_mines,rtp_bubbles,rtp_x50 from kot_config").split() == ['90.00', '95.00', '96.00', '94.00']
        t = s.get(f"{BASE_URL}/admin/rtp").text
        assert re.search(r'id="rtp_dice"', t) and 'value="90"' in t
        set_rtp(s, csrf, 97)

    def test_no_csrf_rejected(self, s):
        r = s.post(f"{BASE_URL}/admin/admin_func.php", data={"type": "saveRTP", "rtp_dice": 10})
        assert pj(r).get("success") != "success"
        assert sql("select rtp_dice from kot_config") == '97.00'


# Dice RTP effect on payout
class TestDiceRTP:
    def test_dice_win_uses_rtp(self, s, csrf):
        set_rtp(s, csrf, 90)
        set_antim(s, csrf, 'dice', 1)  # threshold 1 -> after a loss next is forced win
        b0 = balance()
        won = None
        for _ in range(10):
            d = pj(s.post(f"{BASE_URL}/action.php", data={"type": "minbet", "sum": 10, "per": 50}))
            if d.get("success") == "success":
                won = d
                break
            time.sleep(0.2)
        set_antim(s, csrf, None)
        set_rtp(s, csrf, 97)
        assert won, "no win in 10 tries"
        # profit at 50%, rtp 0.9 = 10*2*0.9-10 = 8
        assert abs(float(won.get("new_balance", 0)) - balance()) < 0.01
        last = sql(f"select win_summa from kot_games where login='{LOGIN}' and game=1 and type='win' order by id desc limit 1")
        assert abs(float(last) - 18.0) < 0.01, last


# Anti-minus forced win per game
class TestAntiMinus:
    def _run(self, s, csrf, game, url, data, is_win):
        set_antim(s, csrf, game, 30)
        sql(f"update kot_user set balance=1000 where login='{LOGIN}'")
        losses, forced_ok, log = 0.0, False, []
        for i in range(25):
            pre_loss = losses
            d = pj(s.post(f"{BASE_URL}{url}", data=data))
            w = is_win(d)
            log.append((pre_loss, w))
            if pre_loss >= 30:
                forced_ok = w
                break
            losses = 0 if w else losses + 10
            time.sleep(0.15)
        set_antim(s, csrf, None)
        assert any(p >= 30 for p, _ in log), f"never reached threshold: {log}"
        assert forced_ok, f"bet after loss>=30 lost: {log}"

    def test_dice(self, s, csrf):
        self._run(s, csrf, 'dice', '/action.php', {"type": "minbet", "sum": 10, "per": 2}, lambda d: d.get("success") == "success")

    def test_bubbles(self, s, csrf):
        self._run(s, csrf, 'bubbles', '/bubblesfunc.php', {"type": "bubble", "sum": 10, "per": 50}, lambda d: d.get("success") == "success")

    def test_x50(self, s, csrf):
        self._run(s, csrf, 'x50', '/wheel.php', {"wheel": 50, "bet": 10}, lambda d: "good" in d)


# X50 payout uses RTP multiplier
class TestX50:
    def test_x50_win_payout(self, s, csrf):
        set_antim(s, csrf, 'x50', 1)
        sql(f"update kot_user set balance=1000 where login='{LOGIN}'")
        won = None
        for _ in range(10):
            b0 = balance()
            d = pj(s.post(f"{BASE_URL}/wheel.php", data={"wheel": 2, "bet": 10}))
            if "good" in d:
                won = (b0, d)
                break
            time.sleep(0.15)
        set_antim(s, csrf, None)
        assert won
        b0, d = won
        assert abs(balance() - (b0 + 10)) < 0.01, (b0, balance(), d)  # exact x2 (net +10)


# Mines regression (strict mode)
class TestMines:
    def _create(self, s):
        d = pj(s.post(f"{BASE_URL}/action.php", data={"type": "createGameMines", "bomb": 3, "amount": 10}))
        return d

    def test_mines_lose(self, s):
        sql(f"update kot_user set balance=1000 where login='{LOGIN}'")
        d = self._create(s)
        assert "error" not in d or not d.get("error"), d
        mines = sql(f"select mines from `mines-game` where login='{LOGIN}' and onOff=1 order by id desc limit 1") or \
            sql(f"select m.mines from `mines-game` m join kot_user u on u.id=m.id_users where u.login='{LOGIN}' and m.onOff=1 order by m.id desc limit 1")
        cell = re.findall(r'i:\d+;i:(\d+);', mines)[0]
        d = pj(s.post(f"{BASE_URL}/action.php", data={"type": "chGameMines", "id": cell}))
        assert "lose" in d and "html" in d["lose"], d
        assert 'bd-danger' in d["lose"]["html"]

    def test_mines_win_cashout(self, s):
        d = self._create(s)
        mines = sql(f"select m.mines from `mines-game` m join kot_user u on u.id=m.id_users where u.login='{LOGIN}' and m.onOff=1 order by m.id desc limit 1")
        bombs = set(int(x) for x in re.findall(r'i:\d+;i:(\d+);', mines))
        cell = next(c for c in range(1, 26) if c not in bombs)
        b_before = balance()
        d = pj(s.post(f"{BASE_URL}/action.php", data={"type": "chGameMines", "id": cell}))
        assert d.get("bombs") != "true", d
        d = pj(s.post(f"{BASE_URL}/action.php", data={"type": "finishMines"}))
        assert "success" in d, d
        assert balance() > b_before
