<?php
session_start();
require("inc/site_config.php");
require("inc/config.php");
require("inc/bd.php");
require_once("inc/rtp.php");
$type = $_POST['type'];
$defType = $_POST['deft'];
$sid = $_SESSION['hash'];
if ($type == "bubble") {
	$sum = $_POST['sum'];
	$per = $_POST['per'];
	$sql_select2 = "SELECT * FROM kot_user WHERE hash='$sid'";
	$result2 = mysql_query($sql_select2);
	$row = mysql_fetch_array($result2);
	if ($row) {
		$balance = $row['balance'];
		$ban = $row['ban'];
		$sliv = $row['sliv'];
		$fart = $row['win'];
		$login = $row['login'];
		$user_id = $row['id'];
	}
	if (!$sid) {
		$error = 1;
		$coef = "Авторизуйтесь";
		$fa = "error";
	} else {
		if ($per < 1.05 || $per > 10000 || !is_numeric($per)) {
			$error = 1;
			$coef = "Коэффицент от 1.05 до 10000";
			$fa = "error";
		}
		if ($sum > $balance) {
			$error = 1;
			$coef = "Недостаточно средств";
			$fa = "error";
		}
		if ($sum < 1 || $sum > 100000 || !is_numeric($sum)) {
			$error = 1;
			$coef = "Сумма от 1 до 100000";
			$fa = "error";
		}
		if ($error == 0) {
		    
            if (rb_over_limit($sum * $per - $sum)) {
                $coef = "Макс. выигрыш за ставку " . rb_max_win();
                $newbalance = $balance;
                $error = 1;
                $fa = "error";
            } else {
            $u = random_int(1, 1000000) / 1000000;
            $point = max(1, floor(rb_rtp('bubbles') / $u * 100) / 100);
            if (bwForceWin($user_id, 'bubbles') && $point < $per) $point = round($per * (1 + random_int(0, 50) / 100), 2);
            if ($point >= $per) {
                $newbalance = round($balance + $sum * $per - $sum, 2);
                $resultwin = round($sum * $per - $sum, 2);
                $coef = "Выпало x" . $point;
                mysql_query("Update kot_user set balance='$newbalance' WHERE hash='$sid'");
                bwTrackBet($user_id, 'bubbles', $sum, $sum * $per);
                $pw = round($sum * $per, 2);
                mysql_query("INSERT INTO `kot_games` (`game`,`user_id`,`login`,`number`,`cel`,`sum`,`chance`,`type`,`win_summa`) VALUES ('3','$user_id','$login','$point','x$per','$sum','$per','win','$pw')");
                $fa = "success";
            } else {
                $newbalance = $balance - $sum;
                $coef = "Выпало x" . $point;
                mysql_query("Update kot_user set balance='$newbalance' WHERE hash='$sid'");
                bwTrackBet($user_id, 'bubbles', $sum, 0);
                mysql_query("INSERT INTO `kot_games` (`game`,`user_id`,`login`,`number`,`cel`,`sum`,`chance`,`type`,`win_summa`) VALUES ('3','$user_id','$login','$point','x$per','$sum','$per','lose','0')");
                $error = 1;
                $fa = "error";
            }
            }
			$hash = hash('sha512', $coef);
		} else {
			$newbalance = $balance;
		}
	}
	$result = array(
		'success' => "$fa",
		'number' => "$coef", // Мне лень писать нормальные уведомления
		'new_balance' => "$newbalance",
		'resultwin' => $resultwin,
		'hash' => $hash

	);
}

echo json_encode($result);
?>