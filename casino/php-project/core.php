<?php
require("inc/bd.php");
require("inc/site_config.php");
session_start();
$sid = $_SESSION['hash'];

// Обновляем онлайн-статус всех юзеров
$sql_select = "SELECT * FROM kot_user";
$result = mysql_query($sql_select);
if ($result && mysql_num_rows($result) > 0) {
    $row = mysql_fetch_array($result);
    do {
        $time = time();
        if ($row && $time > $row['online_time']) {
            $update_sql1 = "UPDATE kot_user SET online='0' WHERE id=" . intval($row['id']);
            mysql_query($update_sql1);
        }
    } while ($row = mysql_fetch_array($result));
}

// Получаем онлайн-счётчик
$online = 0;
$result5 = mysql_query("SELECT COUNT(*) FROM kot_user WHERE online=1");
if ($result5) {
    $row2 = mysql_fetch_array($result5);
    $online = intval($row2['COUNT(*)']);
}

// Последние 15 игр
$game = '';
$sql_select = "SELECT * FROM kot_games ORDER BY id DESC LIMIT 15";
$result = mysql_query($sql_select);
if ($result && mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        if (!$row) continue;
        $game1 = $row['game'];
        $g = '';
        if ($game1 == 1) {
            $g = '<i class="ga fas fa-dice-d6" style="margin-top:4px;color:#e0245e" aria-hidden="true"></i>';
        } elseif ($game1 == 2) {
            $g = '<i class="ga fas fa-gem" style="margin-top:4px;color:#e0245e" aria-hidden="true"></i>';
        } elseif ($game1 == 3) {
            $g = '<i class="ga fas fa-circle-notch" style="margin-top:4px;color:#e0245e" aria-hidden="true"></i>';
        } elseif ($game1 == 4) {
            $g = '<i class="ga fas fa-coins" style="margin-top:4px;color:#e0245e" aria-hidden="true"></i>';
        }
        $log  = htmlspecialchars($row['login']);
        $bet  = floatval($row['sum']);
        $win  = floatval($row['win_summa']);
        $coef = ($bet > 0) ? round($win / $bet, 2) : 0;
        $type = ($row['type'] == 'win') ? 'tx-success2' : 'tx-err';
        $game .= "<tr>
            <td class=\"text-center\">$g</td>
            <td class=\"text-center\">$log</td>
            <td class=\"text-center\">$bet</td>
            <td class=\"text-center\">x$coef</td>
            <td class=\"text-center tx-semibold $type\">$win</td>
        </tr>";
    }
}

// Мои игры (только если авторизован)
$my_game = '';
if ($sid) {
    $selectUser = "SELECT id FROM kot_user WHERE hash='" . mysql_real_escape_string($sid) . "'";
    $resUser = mysql_query($selectUser);
    if ($resUser && $rowUser = mysql_fetch_array($resUser)) {
        $user_id = intval($rowUser['id']);
        $sql_select11 = "SELECT * FROM kot_games WHERE user_id='$user_id' ORDER BY id DESC LIMIT 15";
        $result11 = mysql_query($sql_select11);
        if ($result11) {
            while ($row = mysql_fetch_array($result11)) {
                if (!$row) continue;
                $game1 = $row['game'];
                $g = '';
                if ($game1 == 1) $g = '<i class="fas fa-dice-d6"></i>';
                elseif ($game1 == 2) $g = '<i class="fas fa-gem"></i>';
                elseif ($game1 == 3) $g = '<i class="fas fa-circle-notch"></i>';
                elseif ($game1 == 4) $g = '<i class="fas fa-coins"></i>';
                $log  = htmlspecialchars($row['login']);
                $bet  = floatval($row['sum']);
                $win  = floatval($row['win_summa']);
                $coef = ($bet > 0) ? round($win / $bet, 2) : 0;
                $type = ($row['type'] == 'win') ? 'tx-success' : '';
                $my_game .= "<tr>
                    <td class=\"text-center\">$g</td>
                    <td class=\"text-center\">$log</td>
                    <td class=\"text-center\">$bet</td>
                    <td class=\"text-center\">x$coef</td>
                    <td class=\"text-center tx-semibold $type\">$win</td>
                </tr>";
            }
        }
        // Обновляем онлайн-время
        $time = time() + 5;
        mysql_query("UPDATE kot_user SET online='1', online_time='$time' WHERE hash='" . mysql_real_escape_string($sid) . "'");
    }
}

// Финальный онлайн-счётчик
$result_o = mysql_query("SELECT COUNT(*) FROM kot_user WHERE online='1'");
if ($result_o) {
    $row_o = mysql_fetch_array($result_o);
    $online_default = intval($row_o['COUNT(*)']);
    $online = $online_default;
}

echo json_encode([
    'game'    => $game,
    'my_game' => $my_game,
    'online'  => $online,
]);
