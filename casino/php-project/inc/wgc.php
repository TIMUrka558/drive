<?php
// World Gaming Connect (WGC) slots: config, API client, catalog sync
if (empty($GLOBALS['__mysql_conn'])) require_once __DIR__ . '/bd.php';

const WGC_BASE = 'https://api.worldgamingconnect.com';
const WGC_CURRENCY = 'RUB';

function wgc_env(string $k): string {
    $v = getenv($k);
    if ($v !== false && $v !== '') return $v;
    static $file = null;
    if ($file === null) {
        $file = [];
        foreach (@file('/app/backend/.env', FILE_IGNORE_NEW_LINES) ?: [] as $l) {
            if (preg_match('/^([A-Za-z0-9_]+)=(.*)$/', $l, $m)) $file[$m[1]] = trim($m[2], "\"'");
        }
    }
    return $file[$k] ?? '';
}
function wgc_api_key(): string { return wgc_env('WGC_API_KEY'); }
function wgc_secret(): string { return wgc_env('SLOTS_API_SECRET'); }
function wgc_is_test_key(): bool { return str_starts_with(wgc_api_key(), 'wgc_test_'); }

function wgc_player_id(int $uid): string { return 'rubin-' . $uid; }
function wgc_uid(string $playerId): int { return preg_match('/^rubin-(\d+)$/', $playerId, $m) ? (int)$m[1] : 0; }

function wgc_api(string $method, string $path, ?array $body = null, ?string $idem = null): array {
    $key = wgc_api_key();
    if ($key === '') return [0, ['ok' => false, 'error' => ['code' => 'no_api_key', 'message' => 'API-ключ WGC не задан']]];
    $h = ['X-API-Key: ' . $key, 'Accept: application/json'];
    if ($body !== null) $h[] = 'Content-Type: application/json';
    if ($idem) $h[] = 'Idempotency-Key: ' . $idem;
    $ch = curl_init(WGC_BASE . $path);
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $h,
        CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) return [0, ['ok' => false, 'error' => ['code' => 'network', 'message' => $err]]];
    return [$code, json_decode($raw, true) ?: ['ok' => false, 'error' => ['code' => 'bad_json', 'message' => substr($raw, 0, 200)]]];
}

function wgc_meta_get(string $k): string {
    $r = mysql_fetch_array(mysql_query("SELECT v FROM wgc_meta WHERE k='" . mysql_real_escape_string($k) . "'"));
    return $r ? (string)$r['v'] : '';
}
function wgc_meta_set(string $k, string $v): void {
    mysql_query("REPLACE INTO wgc_meta (k, v) VALUES ('" . mysql_real_escape_string($k) . "', '" . mysql_real_escape_string($v) . "')");
}

// Full sync (default) marks games missing from the feed as removed; delta uses updatedSince.
function wgc_sync(bool $delta = false): array {
    @set_time_limit(0);
    $since = $delta ? wgc_meta_get('synced_at') : '';
    $run = date('Y-m-d H:i:s');
    $page = 1; $n = 0; $syncedAt = '';
    do {
        $q = '/v1/games?page=' . $page . ($since !== '' ? '&updatedSince=' . rawurlencode($since) : '');
        [$code, $res] = wgc_api('GET', $q);
        if ($code === 429) { sleep(2); continue; }
        if (empty($res['ok'])) return ['ok' => false, 'error' => ($res['error']['code'] ?? 'error') . ': ' . ($res['error']['message'] ?? ''), 'count' => $n];
        $d = $res['data'];
        $syncedAt = $d['syncedAt'] ?? $syncedAt;
        foreach ($d['items'] ?? [] as $g) {
            $e = fn($v) => mysql_real_escape_string((string)$v);
            $removed = !empty($g['removed']) ? 1 : 0;
            $demo = !empty($g['demoSupported']) ? 1 : 0;
            mysql_query("INSERT INTO wgc_games (public_id, universal_id, name, brand, kind, image, demo, removed, seen_at, updated_at)
                VALUES ('{$e($g['publicId'])}', '{$e($g['universalId'] ?? '')}', '{$e($g['name'] ?? '')}', '{$e($g['brand'] ?? '')}', '{$e($g['kind'] ?? '')}', '{$e($g['image'] ?? '')}', $demo, $removed, '$run', NOW())
                ON DUPLICATE KEY UPDATE universal_id=VALUES(universal_id), name=VALUES(name), brand=VALUES(brand), kind=VALUES(kind),
                image=VALUES(image), demo=VALUES(demo), removed=VALUES(removed), seen_at=VALUES(seen_at), updated_at=NOW()");
            $n++;
        }
        $pages = (int)($d['pageCount'] ?? 1);
        $page++;
    } while ($page <= $pages);
    if (!$delta) mysql_query("UPDATE wgc_games SET removed=1 WHERE seen_at < '$run'");
    wgc_rank();
    if ($syncedAt !== '') wgc_meta_set('synced_at', $syncedAt);
    wgc_meta_set('last_sync_local', $run);
    return ['ok' => true, 'count' => $n];
}

function wgc_games(int $limit, int $offset = 0, string $search = '', string $brand = '', string $kind = 'slot'): array {
    $w = ["removed=0"];
    if ($kind !== '') $w[] = "kind='" . mysql_real_escape_string($kind) . "'";
    if ($search !== '') { $s = mysql_real_escape_string($search); $w[] = "(name LIKE '%$s%' OR brand LIKE '%$s%')"; }
    if ($brand !== '') $w[] = "brand='" . mysql_real_escape_string($brand) . "'";
    $where = implode(' AND ', $w);
    $total = (int)(mysql_fetch_array(mysql_query("SELECT COUNT(*) c FROM wgc_games WHERE $where"))['c'] ?? 0);
    $q = mysql_query("SELECT public_id, name, brand, image FROM wgc_games WHERE $where ORDER BY sort DESC, name LIMIT " . (int)$offset . ", " . (int)$limit);
    $out = [];
    while ($q && $r = mysql_fetch_assoc($q)) {
        $out[] = $r;
    }
    return [$out, $total];
}

// Order: well-known hits first, then top studios, games without a real picture last.
function wgc_rank(): void {
    $hits = ['gates-of-olympus', 'sweet-bonanza', 'sugar-rush', 'the-dog-house', 'big-bass-bonanza', 'starlight-princess',
        'wanted-dead-or-a-wild', 'book-of-dead', 'fruit-party', 'wolf-gold', 'zeus-vs-hades', 'madame-destiny-megaways',
        'big-bass-splash', 'the-dog-house-megaways', 'gates-of-olympus-1000', 'sweet-bonanza-1000', 'sugar-rush-1000', 'chaos-crew'];
    $brands = ['Pragmatic Play', 'Hacksaw Gaming', "Play'n GO", 'NetEnt', 'Nolimit City', 'Push Gaming', 'Relax Gaming', 'BGaming', 'Red Tiger', 'Big Time Gaming'];
    $e = fn($a) => implode(',', array_map(fn($x) => "'" . mysql_real_escape_string($x) . "'", $a));
    mysql_query("UPDATE wgc_games SET sort = 0");
    mysql_query("UPDATE wgc_games SET sort = 10 WHERE brand IN (" . $e($brands) . ")");
    mysql_query("UPDATE wgc_games SET sort = 100 - FIELD(universal_id, " . $e($hits) . ") WHERE universal_id IN (" . $e($hits) . ")");
    mysql_query("UPDATE wgc_games SET sort = -100 WHERE image LIKE '%/placeholder/%' OR image = ''");
}
