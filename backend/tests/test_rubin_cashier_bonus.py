# RUBIN casino (PHP): cashier (payapi), daily bonus, cashback, X50 exact payout, pages/redirects, admin payments settings
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
UA = {"User-Agent": "Mozilla/5.0 pytest"}


def sql(q):
    return subprocess.run(MYSQL + [q], capture_output=True, text=True).stdout.strip()


def bal():
    return float(sql(f"select balance from kot_user where login='{LOGIN}'"))


def pj(r):
    t = r.text.strip()
    i = t.find('{')
    return json.loads(t[i:]) if i >= 0 else {}


def get(s, path, **kw):
    for _ in range(3):
        r = s.get(f"{BASE_URL}{path}", **kw)
        if 'security verification' not in r.text.lower():
            return r
        time.sleep(3)
    return r


@pytest.fixture(scope="module")
def s():
    ss = requests.Session(); ss.headers.update(UA)
    r = ss.post(f"{BASE_URL}/action.php", data={"type": "login", "login": LOGIN, "pass": PASS})
    assert r.status_code == 200
    return ss


@pytest.fixture(scope="module")
def csrf(s):
    m = re.search(r'name="csrf" content="([^"]+)"', get(s, "/admin/users").text)
    assert m
    return m.group(1)


def pay(s, data):
    return pj(s.post(f"{BASE_URL}/payapi.php", data=data))


# Cashier API
class TestCashier:
    def test_logged_out(self):
        d = pj(requests.post(f"{BASE_URL}/payapi.php", data={"type": "history"}, headers=UA))
        assert d.get('success') is False and 'Войдите' in d.get('error', '')

    @pytest.mark.parametrize("p", ["cryptobot", "xrocket"])
    def test_deposit_not_connected(self, s, p):
        d = pay(s, {"type": "createDeposit", "provider": p, "sum": 500})
        assert d == {"success": False, "error": "Этот способ оплаты пока не подключён"}, d

    def test_deposit_bad_provider(self, s):
        d = pay(s, {"type": "createDeposit", "provider": "qiwi", "sum": 500})
        assert d.get('success') is False and 'способ' in d['error']

    def test_withdraw_not_connected_no_balance_change(self, s):
        b0 = bal()
        d = pay(s, {"type": "withdraw", "provider": "xrocket", "sum": 500, "tg_id": "123456789"})
        assert d.get('success') is False and 'не подключён' in d['error'], d
        assert bal() == b0

    def test_history(self, s):
        d = pay(s, {"type": "history"})
        assert d.get('success') is True and isinstance(d['deposits'], list) and isinstance(d['withdraws'], list)

    def test_withdraw_redirect(self, s):
        r = get(s, "/withdraw", allow_redirects=False)
        loc = r.headers.get('location', '')
        assert r.status_code in (301, 302, 303, 307) and '/deposit#withdraw' in loc, (r.status_code, loc, r.text[:200])

    def test_deposit_page_methods(self, s):
        t = get(s, "/deposit").text
        assert 'method-cryptobot' in t and 'method-xrocket' in t
        assert len(re.findall(r'data-testid="method-', t)) == 2
        assert t.count('rb-soon') >= 2


# Daily bonus + cashback
class TestBonus:
    def test_daily_bonus_once(self, s):
        ts0 = sql(f"select bonus_ts from kot_user where login='{LOGIN}'")
        b0 = bal()
        sql(f"update kot_user set bonus_ts=0 where login='{LOGIN}'")
        try:
            d = pay(s, {"type": "dailyBonus"})
            assert d.get('success') is True, d
            assert 10 <= d['prize'] <= 25
            assert round(bal() - b0, 2) == d['prize']
            assert abs(d['balance'] - bal()) < 0.01
            d2 = pay(s, {"type": "dailyBonus"})
            assert d2.get('success') is False and d2.get('left', 0) > 86000, d2
            assert round(bal() - b0, 2) == d['prize']
            t = get(s, "/bonus").text
            assert re.search(r'Следующий бонус через (2[0-3]:\d\d:\d\d|24:00:00)', t)
            assert re.search(r'id="rbDailyBtn"[^>]*disabled', t)
        finally:
            sql(f"update kot_user set balance={b0} where login='{LOGIN}'")

    def test_cashback_claim(self, s):
        b0 = bal()
        cb0 = sql(f"select cashback from kot_user where login='{LOGIN}'")
        sql(f"update kot_user set cashback=12.34 where login='{LOGIN}'")
        try:
            d = pay(s, {"type": "claimCashback"})
            assert d.get('success') is True, d
            assert abs(d['amount'] - 12.34) < 0.001
            assert round(bal() - b0, 2) == 12.34
            assert sql(f"select cashback from kot_user where login='{LOGIN}'") == '0.00'
            d2 = pay(s, {"type": "claimCashback"})
            assert d2.get('success') is False
        finally:
            sql(f"update kot_user set balance={b0}, cashback={cb0} where login='{LOGIN}'")


# X50 exact multipliers (anti-minus forces win)
class TestX50Exact:
    def test_buttons(self, s):
        t = get(s, "/x50").text
        for m in ['>x2<', '>x3<', '>x5<', '>x50<']:
            assert m in t, m

    @pytest.mark.parametrize("color,mult", [(2, 2), (3, 3), (5, 5)])
    def test_payout(self, s, csrf, color, mult):
        def antim(on):
            data = {"type": "saveAntiMinus"}
            for g in ['dice', 'mines', 'bubbles', 'x50']:
                data[f"antim_{g}"] = 1 if (on and g == 'x50') else 0
                data[f"thresh_{g}"] = 1 if (on and g == 'x50') else 30
            pj(s.post(f"{BASE_URL}/admin/admin_func.php", data=data, headers={"X-CSRF-Token": csrf}))
        b_orig = bal()
        sql(f"update kot_user set balance=1000 where login='{LOGIN}'")
        antim(True)
        try:
            won = None
            for _ in range(15):
                b0 = bal()
                d = pj(s.post(f"{BASE_URL}/wheel.php", data={"wheel": color, "bet": 10}))
                if "good" in d:
                    won = (b0, d); break
                assert abs(bal() - (b0 - 10)) < 0.01, "loss must be -10"
                time.sleep(0.2)
            assert won, "never won"
            b0, d = won
            assert abs(bal() - (b0 + 10 * (mult - 1))) < 0.01, (b0, bal(), d)
        finally:
            antim(False)
            sql(f"update kot_user set balance={b_orig} where login='{LOGIN}'")


# Admin payments settings save + pages without history
class TestMisc:
    def test_save_payments(self, s, csrf):
        orig = sql("select usdt_rub, min_sum_dep, min_bonus_size, max_bonus_size from kot_config").split()
        try:
            d = pj(s.post(f"{BASE_URL}/admin/admin_func.php", headers={"X-CSRF-Token": csrf},
                          data={"type": "savePayments", "usdt_rub": 99, "min_sum_dep": 150, "bonus_min": 11, "bonus_max": 22}))
            assert d.get('success') == 'success', d
            assert sql("select usdt_rub, min_sum_dep, min_bonus_size, max_bonus_size from kot_config").split() == ['99.00', '150', '11', '22']
            t = get(s, "/admin/settings").text
            assert 'Крипто-платежи' in t
        finally:
            pj(s.post(f"{BASE_URL}/admin/admin_func.php", headers={"X-CSRF-Token": csrf},
                      data={"type": "savePayments", "usdt_rub": orig[0], "min_sum_dep": orig[1], "bonus_min": orig[2], "bonus_max": orig[3]}))

    @pytest.mark.parametrize("p", ["/", "/dice", "/mines", "/bubbles", "/x50"])
    def test_no_history_table(self, s, p):
        time.sleep(0.5)
        t = get(s, p).text
        assert 'Fatal error' not in t and 'Warning:' not in t
        assert 'История игр' not in t and 'Последние игры' not in t, p
