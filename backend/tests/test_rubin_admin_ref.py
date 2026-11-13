# RUBIN casino (PHP): admin panel pages, players edit, referral %, manual deposit, password change, RTP via public URL
import os, re, json, subprocess, time
import pytest
import requests

BASE_URL = os.environ.get('REACT_APP_BACKEND_URL', '').rstrip('/')
if not BASE_URL:
    for line in open('/app/frontend/.env'):
        if line.startswith('REACT_APP_BACKEND_URL='):
            BASE_URL = line.split('=', 1)[1].strip().rstrip('/')
MYSQL = ["/app/casino/.runtime/mysql/bin/mysql", "--socket=/app/casino/mysql-run/mysql.sock", "-u", "root", "beezename", "-N", "-e"]
ADMIN, APASS = "tst465641", "pass1234"
INVITEE = "tst752423"
UA = {"User-Agent": "Mozilla/5.0 pytest"}


def sql(q):
    return subprocess.run(MYSQL + [q], capture_output=True, text=True).stdout.strip()


def uid(login):
    return int(sql(f"select id from kot_user where login='{login}'"))


def pj(r):
    t = r.text.strip()
    i = t.find('{')
    return json.loads(t[i:]) if i >= 0 else {}


def get(s, path):
    for _ in range(3):
        r = s.get(f"{BASE_URL}{path}")
        if 'security verification' not in r.text.lower():
            return r
        time.sleep(3)
    return r


@pytest.fixture(scope="module")
def s():
    ss = requests.Session()
    ss.headers.update(UA)
    r = ss.post(f"{BASE_URL}/action.php", data={"type": "login", "login": ADMIN, "pass": APASS})
    assert r.status_code == 200
    return ss


@pytest.fixture(scope="module")
def csrf(s):
    r = get(s, "/admin/users")
    m = re.search(r'name="csrf" content="([^"]+)"', r.text)
    assert m, "csrf meta missing"
    return m.group(1)


def ap(s, csrf, data):
    return pj(s.post(f"{BASE_URL}/admin/admin_func.php", data=data, headers={"X-CSRF-Token": csrf}))


# Admin: logged-out shows login card, every menu page loads without PHP errors
class TestAdminPages:
    def test_admin_logged_out_login(self):
        r = get(requests.Session(), "/admin")
        assert r.status_code == 200, r.status_code
        assert 'admin-login-card' in r.text

    @pytest.mark.parametrize("p", ["/admin", "/admin/", "/admin/users", "/admin/stat", "/admin/rtp", "/admin/promo",
                                   "/admin/withdraws", "/admin/deps", "/admin/tickets", "/admin/settings", "/admin/index"])
    def test_admin_page(self, s, p):
        time.sleep(0.5)
        r = get(s, p)
        assert r.status_code == 200, f"{p} -> {r.status_code}"
        low = r.text
        for bad in ["Fatal error", "Warning:", "Parse error", "Uncaught", "Notice:", "Deprecated:"]:
            assert bad not in low, f"{p} contains {bad}"
        assert 'admin-login-card' not in low

    def test_users_default_list(self, s):
        t = get(s, "/admin/users").text
        assert 'Latest players' in t and ADMIN in t

    def test_admin_func_requires_csrf(self, s):
        d = pj(s.post(f"{BASE_URL}/admin/admin_func.php", data={"type": "saveRTP"}))
        assert d.get('error') == 'Invalid CSRF token'


# Players: editPlayer (balance, ref_pct, ref_id, password) + manual deposit with referral reward
class TestPlayersRef:
    def test_edit_and_ref_reward(self, s, csrf):
        aid, iid = uid(ADMIN), uid(INVITEE)
        orig_ref = sql(f"select ifnull(ref_id,'') from kot_user where id={iid}")
        maxpay = int(sql("select max(id) from kot_payments"))
        a_bal0 = float(sql(f"select balance from kot_user where id={aid}"))
        a_rb0 = float(sql(f"select ref_balance from kot_user where id={aid}"))
        try:
            # set admin user personal 12%
            d = ap(s, csrf, {"type": "editPlayer", "uid": aid, "balance": a_bal0, "ref_pct": "12", "ref_id": 0})
            assert d.get('success') == 'success', d
            assert sql(f"select ref_pct from kot_user where id={aid}") == '12.00'
            # invitee: ref_id=admin, balance 50
            d = ap(s, csrf, {"type": "editPlayer", "uid": iid, "balance": 50, "ref_pct": "", "ref_id": aid})
            assert d.get('success') == 'success', d
            assert sql(f"select ref_id from kot_user where id={iid}") == str(aid)
            assert float(sql(f"select balance from kot_user where id={iid}")) == 50
            # manual deposit 100 with ref reward
            d = ap(s, csrf, {"type": "manualDep", "hashuser": iid, "amount": 100, "ref_reward": 1})
            assert d.get('success') == 'success', d
            assert float(sql(f"select balance from kot_user where id={iid}")) == 150
            assert round(float(sql(f"select balance from kot_user where id={aid}")) - a_bal0, 2) == 12.0
            assert round(float(sql(f"select ref_balance from kot_user where id={aid}")) - a_rb0, 2) == 12.0
            # admin page reflects
            t = get(s, f"/admin/users?uid={aid}").text
            m = re.search(r'data-testid="admin-ref-earned">([^<]+)<', t)
            assert m and '12.00' in m.group(1), m and m.group(1)
            m = re.search(r'data-testid="admin-ref-count">([^<]+)<', t)
            assert m and int(m.group(1)) >= 1
            # inviter's /ref page
            t = get(s, "/ref").text
            assert '12' in t
            # global % when personal empty
            ap(s, csrf, {"type": "editPlayer", "uid": aid, "balance": float(sql(f"select balance from kot_user where id={aid}")), "ref_pct": "", "ref_id": 0})
            assert sql(f"select ifnull(ref_pct,'NULL') from kot_user where id={aid}") == 'NULL'
            g = float(sql("select ref_pct from kot_config"))
            b1 = float(sql(f"select balance from kot_user where id={aid}"))
            ap(s, csrf, {"type": "manualDep", "hashuser": iid, "amount": 100, "ref_reward": 1})
            assert round(float(sql(f"select balance from kot_user where id={aid}")) - b1, 2) == round(g, 2)
            # no reward when unchecked
            b2 = float(sql(f"select balance from kot_user where id={aid}"))
            ap(s, csrf, {"type": "manualDep", "hashuser": iid, "amount": 10, "ref_reward": ""})
            assert float(sql(f"select balance from kot_user where id={aid}")) == b2
        finally:
            sql(f"delete from kot_payments where id>{maxpay} and qiwi='admin' and transaction='manual_deposit' and user_id={iid}")
            sql(f"update kot_user set balance={a_bal0}, ref_balance={a_rb0}, ref_pct=NULL where id={aid}")
            sql(f"update kot_user set balance=0, ref_pct=NULL, ref_id='{orig_ref}' where id={iid}")

    def test_password_change_login(self, s, csrf):
        iid = uid(INVITEE)
        try:
            d = ap(s, csrf, {"type": "editPlayer", "uid": iid, "balance": 0, "password": "newpw777"})
            assert d.get('success') == 'success'
            ss = requests.Session(); ss.headers.update(UA)
            r = ss.post(f"{BASE_URL}/action.php", data={"type": "login", "login": INVITEE, "pass": "newpw777"})
            d = pj(r)
            assert d.get('success') == 'success' or 'success' in r.text, r.text[:200]
            ss2 = requests.Session(); ss2.headers.update(UA)
            r2 = ss2.post(f"{BASE_URL}/action.php", data={"type": "login", "login": INVITEE, "pass": "pass1234"})
            assert pj(r2).get('success') != 'success', "old password still works"
        finally:
            sql(f"update kot_user set pass='pass1234' where id={iid}")


# RTP: dice 90 reflected on /dice (RB_RTP) and dice payout
class TestRTP:
    def test_dice_rtp_90(self, s, csrf):
        try:
            d = ap(s, csrf, {"type": "saveRTP", "rtp_dice": 90, "rtp_mines": 97, "rtp_bubbles": 97, "rtp_x50": 97, "max_win": 0})
            assert d.get('success') == 'success'
            time.sleep(1)
            t = get(s, "/dice").text
            assert 'dice:0.9,' in t, re.search(r'RB_RTP=\{[^}]*\}', t)
        finally:
            ap(s, csrf, {"type": "saveRTP", "rtp_dice": 97, "rtp_mines": 97, "rtp_bubbles": 97, "rtp_x50": 97, "max_win": 0})
        assert sql("select rtp_dice from kot_config") == '97.00'

    def test_antiminus_persist(self, s, csrf):
        data = {"type": "saveAntiMinus"}
        for g in ['dice', 'mines', 'bubbles', 'x50']:
            data[f"antim_{g}"] = 1 if g == 'mines' else 0
            data[f"thresh_{g}"] = 55
        try:
            assert ap(s, csrf, data).get('success') == 'success'
            assert sql("select antim_mines, antim_thresh_mines from kot_config") .split()[0] == '1'
            t = get(s, "/admin/rtp").text
            assert re.search(r'id="antim_mines"[^>]*checked', t), "toggle not checked after reload"
        finally:
            for g in ['dice', 'mines', 'bubbles', 'x50']:
                data[f"antim_{g}"] = 0
                data[f"thresh_{g}"] = 30
            ap(s, csrf, data)
