<?php
  session_start();
$sid = $_SESSION['hash'];
require("inc/site_config.php");
require("inc/config.php");
require("inc/bd.php");
require_once("inc/rtp.php");
$komissia = 0.1;
// данные игрока
 $sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{
$login = $row['login'];
$money = $row['balance'];
$id = $row['id'];
}
//игровой массив
if(isset($_POST['wheel'])){
  $colorWheel = $_POST['wheel'];
  $bet = $_POST['bet'];



   $arrayWheel = [5,2,3,2,3,2,3,2,5,2,5,2,3,2,3,2,3,2,5,2,5,2,3,2,3,2,3,2,3,2,3,2,5,2,5,2,3,2,3,2,3,2,5,2,5,2,3,2,3,2,3,2,5,50];
  if($bet <= $money){
   if($bet >= "1"){

    $query = ("SELECT * FROM `kot_admin`");
    $row_admin = mysql_query($query);
    $admin = mysql_fetch_array($row_admin);

    $bank = $admin['bank'];


   $randWheel =  mt_rand(0,53); //цвет который выпадет
   if(bwForceWin($id, 'x50') && in_array((int)$colorWheel, [2,3,5,50])){ while($arrayWheel[$randWheel] != $colorWheel) $randWheel = mt_rand(0,53); }
   $valuesWheel = $arrayWheel[$randWheel];


   if ($arrayWheel[$randWheel] == 2) {
    $key2Wheel = [
       1,  3,  5,  7,  9, 11, 13, 15,
      17, 19, 21, 23, 25, 27, 29, 31,
      33, 35, 37, 39, 41, 43, 45, 47,
      49, 51
    ];
    $random = mt_rand(0,25);
    $key = $key2Wheel[$random];
  }
  if ($arrayWheel[$randWheel] == 3) {
    $key3Wheel = [
      2,4,6,
      12,14,16,
      22,24,26,
      28,30,36,
      38,40,46,
      48,50
    ];
    $random = mt_rand(0,16);
    $key = $key3Wheel[$random];
  }
  if ($arrayWheel[$randWheel] == 5) {
    $key5Wheel = [
      0,8,10,18,20,32,34,42,44,52
    ];
    $random = mt_rand(0,9);
    $key = $key5Wheel[$random];
  }
  if ($valuesWheel == 50) {
    $key5Wheel = [
      53
    ];
    $key = 53;
  }

  $vozm_win = ($bet * rb_x50_mult($colorWheel)) - $bet;
  if(rb_over_limit($vozm_win)){
    $obj = array("danger"=>"danger","mess"=>"Макс. выигрыш за ставку ".rb_max_win());
  }else{
  if($colorWheel == $valuesWheel){ // вы выиграли
    $win = round($bet * rb_x50_mult($colorWheel) - $bet, 2);
    bwTrackBet($id, 'x50', $bet, $bet + $win);
    $query = mysql_query("UPDATE `kot_user` SET `balance` = '$money'+'$win' WHERE `id` = '$id'");
    $win = round($bet * rb_x50_mult($colorWheel), 2);
    $money = $money + $win - $bet;



   // для работы с антиминусом
   $query = ("SELECT * FROM `kot_admin`");
   $row_admin = mysql_query($query);
   $admin = mysql_fetch_array($row_admin);

   $bank = $admin['bank'];
   $profit = $admin['zarabotok'];
   $bank1 = $bet;
   $query = mysql_query("UPDATE `kot_admin` SET `bank`= '$bank'-'$bank1'");



    $dfghjk = "<span class='number result-win result'><span class='myBetsBox'>".$win."</span> <i class='fas fa-coins'></i></span>";
    $obj = array("good"=>"good","mess"=>"Вы выиграли $win <i class='fas fa-coins'></i>","valuesWheel"=>"$valuesWheel","key"=>"$key","money"=>"$money","resultHistoryContentWheel"=>"$dfghjk");
    mysql_query("INSERT INTO `kot_games` (`game`,`user_id`,`login`,`number`,`cel`,`sum`,`chance`,`type`,`win_summa`) VALUES ('4','$id','$login','$valuesWheel','x$colorWheel','$bet','$colorWheel','win','$win')");
    $query = mysql_query("INSERT INTO `wheel-games` (`id_users`, `login`, `bet`, `colorWheel`, `result`) VALUES ('$id', '$login', '$bet', '$colorWheel', '$valuesWheel');");
  }else{ //вы проиграли
    bwTrackBet($id, 'x50', $bet, 0);

        // для работы с антиминусом
   $query = ("SELECT * FROM `kot_admin`");
   $row_admin = mysql_query($query);
   $admin = mysql_fetch_array($row_admin);

   $bank = $admin['bank'];
   $profit = $admin['zarabotok'];
   $profit1 = $bet * $komissia;
   $bank1 = $bet * (1-$komissia);
   $query = mysql_query("UPDATE `kot_admin` SET `bank`= '$bank'+'$bank1',`zarabotok`='$profit'+'$profit1'");

    $query = mysql_query("UPDATE `kot_user` SET `balance` = '$money'-'$bet' WHERE `id` = '$id'");
    $money = $money - $bet;
    $dfghjk = "<span class='number result-lose result'><span class='myBetsBox'>".$bet."</span> <i class='fas fa-coins'></i></span>";
    $obj = array("bad"=>"bad","mess"=>"Вы проиграли $bet <i class='fas fa-coins'></i>","valuesWheel"=>"$valuesWheel","key"=>"$key","money"=>"$money","resultHistoryContentWheel"=>"$dfghjk");
    mysql_query("INSERT INTO `kot_games` (`game`,`user_id`,`login`,`number`,`cel`,`sum`,`chance`,`type`,`win_summa`) VALUES ('4','$id','$login','$valuesWheel','x$colorWheel','$bet','$colorWheel','lose','0')");
    $query = mysql_query("INSERT INTO `wheel-games` (`id_users`, `login`, `bet`, `colorWheel`, `result`) VALUES ('$id', '$login', '$bet', '$colorWheel', '$valuesWheel');");
  }
 }
  }else{
    $obj = array("danger"=>"danger","mess"=>"Минимальная ставка 1 <i class='fas fa-coins'></i>");
  }
  }else{
    $obj = array("danger"=>"danger","mess"=>"Не хватает монет для игры");
  }
  }

echo json_encode($obj);
?>