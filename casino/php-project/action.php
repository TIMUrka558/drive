<?php
  session_start();
$sid = $_SESSION['hash'];
require("inc/site_config.php");
require("inc/config.php");
require("inc/bd.php");
require_once("inc/rtp.php");

$pass = $_POST['pass'];
$login = $_POST['login'];
$type = $_POST['type'];
$email = $_POST['email'];
$error = 0;
$fa = "";





if ($type == "finishMines"){
	$sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
    $balance = $row['balance'];
$user_id=$row['id'];
$login = $row['login'];
}

$query = ("SELECT * FROM `mines-game` WHERE `id_users` = '$user_id' AND `onOff` = '1' ORDER BY `id` DESC LIMIT 1");
$result = mysql_query($query);
$minesgame = mysql_fetch_array($result);

if($minesgame){
$mines = $minesgame['mines'];
$click = $minesgame['click'];
$step = $minesgame['step'];
$num_mines = $minesgame['num_mines'];
$bet = $minesgame['bet'];
$win = $minesgame['win'];
$resultgame = $minesgame['result'];
$onOff = $minesgame['onOff'];

$mines = unserialize($mines);
$click = unserialize($click);
}else{
	$error = 1;
}


	
	if($error != 0){
		 $result = array(
'error' => array('text' => 'Ошибка'),
    );
	}
	if($error == 0){
		  if($step != "0"){
      if($minesgame != null){
      $newbalance = round(($balance + $win), 2);
      $query = ("UPDATE `kot_user` SET `balance` = '$newbalance' WHERE `hash` = '$sid'"); //выдаем баланс игроку за победу.
      mysql_query($query);
  
      $query = ("UPDATE `mines-game` SET `onOff` = '2' WHERE `id_users` = '$user_id'"); //отключаем игру.
      mysql_query($query);
      
      $insert_sql1 = "INSERT INTO `kot_games` (`game`,`user_id`, `login`, `sum`, `type`, `win_summa`) 
	VALUES ('2', '$user_id', '$login',  '$bet', 'win', '$win');";
mysql_query($insert_sql1);
bwTrackBet($user_id, 'mines', $bet, $win);

  
     $tamines = json_encode($mines);

  // для работы с антиминусом
   

    $win = $win + $bet;
    $money = $money + $win; //для правильного отображения баланса
    $ssa = "<span class='number result-win result'><span class='myBetsBox'>".$win."</span> <i class='fas fa-coins'></i></span>"; 
    $result = array(
'success' => array('new_balance' => $newbalance),
    );
    
  }else{
    $result = array(
'error' => array('text' => 'Ошибка'),
    );
  }
  }else{
    $result = array(
'error' => array('text' => 'Ошибка'),
    );
  }
		
		 
	}
}
if ($type == "chGameMines"){
	$mmines = $_POST['id'];
		$sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
    $balance = $row['balance'];
$user_id=$row['id'];
$login = $row['login'];
}

$query = ("SELECT * FROM `mines-game` WHERE `id_users` = '$user_id' AND `onOff` = '1' ORDER BY `id` DESC LIMIT 1");
$result = mysql_query($query);
$minesgame = mysql_fetch_array($result);

if($minesgame){
$mines = $minesgame['mines'];
$click = $minesgame['click'];
$step = $minesgame['step'];
$num_mines = $minesgame['num_mines'];
$bet = $minesgame['bet'];
$win = $minesgame['win'];
$resultgame = $minesgame['result'];
$onOff = $minesgame['onOff'];

$mines = unserialize($mines);
$click = unserialize($click);
}else{
	$error = 1;
}
	
	
	if($error != 0){
		echo json_encode(['error' => ['text' => 'Нет активной игры']]);
		exit;
	}
	if($error == 0){
		if($mmines == "1" || $mmines == "2" ||  $mmines == "3"||  $mmines == "4" || $mmines == "5"  ||  $mmines == "6"||  $mmines == "7" ||  $mmines == "8" || $mmines == "9"  ||  $mmines == "10"|| $mmines == "11" || $mmines == "12"|| $mmines == "13" ||  $mmines == "14"||  $mmines == "15"|| $mmines == "16" ||  $mmines == "17" || $mmines == "18" ||  $mmines == "19"|| $mmines == "20" ||  $mmines == "21" || $mmines == "22" || $mmines == "23" ||  $mmines == "24" ||  $mmines == "25"){

		 $bombs2 = [1.09,1.19,1.3,1.43,1.58,1.75,1.96,2.21,2.5,2.86,3.3,3.85,4.55,5.45,6.67,8.33,10.71,14.29,20,30,50,100,300];
    $bombs3 = [1.14,1.3,1.49,1.73,2.02,2.37,2.82,3.38,4.11,5.05,6.32,8.04,10.45,13.94,19.17,27.38,41.07,65.71,115,230,575,2300];
    $bombs4 = [1.19,1.43,1.73,2.11,2.61,3.26,4.13,5.32,6.95,9.27,12.64,17.69,25.56,38.33,60.24,100.4,180.71,361.43,843.33,2530,12650];
    $bombs5 = [1.25,1.58,2.02,2.61,3.43,4.57,6.2,8.59,12.16,17.69,26.54,41.28,67.08,115,210.83,421.67,948.75,2530,8855,53130];
    $bombs6 = [1.32,1.75,2.37,3.26,4.57,6.53,9.54,14.31,22.12,35.38,58.97,103.21,191.67,383.33,843.33,2108.33];
    $bombs7 = [1.39,1.96,2.82,4.13,6.2,9.54,15.1,24.72,42.02,74.7,140.06,280.13,606.94,1456.67,4005.83,13352.78];
    $bombs8 = [1.47,2.21,3.38,5.32,8.59,14.31,24.72,44.49,84.04,168.08,360.16,840.38,2185,6555,24035,120175,1081575];
    $bombs9 = [1.56,2.5,4.11,6.95,12.16,22.12,42.02,84.04,178.58,408.19,1020.47,2857.31,9286.25,37145,204297.5,2042975];
    $bombs10 = [1.67,2.86,5.05,9.27,17.69,35.38,74.7,168.08,408.19,1088.5,3265.49,11429.23,49526.67,297160,3268760];
    $bombs11 = [1.79,3.3,6.32,12.64,26.54,58.97,140.06,360.16,1020.47,3265.49,12245.6,57146.15,371450,4457400];
    $bombs12 = [1.92,3.85,8.04,17.69,41.28,103.21,280.13,840.38,2857.31,11429.23,57146.15,400023.08,5200300];
    $bombs13 = [2.08,4.55,10.45,25.56,67.08,191.67,606.94,2185,9286.25,49526.67,371450,5200300];
    $bombs14 = [2.27,5.45,13.94,38.33,115,383.33,1456.67,6555,37145,297160,4457400];
    $bombs15 = [2.5,6.67,19.17,60.24,210.83,843.33,4005.83,24035,204297.5,3268760];
    $bombs16 = [2.78,8.33,27.38,100.4,421.67,2108.33,13352.78,120175,2042975];
    $bombs17 = [3.13,10.71,41.07,180.71,948.75,6325,60087.5,1081575];
    $bombs18 = [3.57,14.29,65.71,361.43,2530,25300,480700];
    $bombs19 = [4.17,20,115,843.33,8855,177100];
    $bombs20 = [5,30,230,2530,53130];
    $bombs21 = [6.25,50,575,12650];
    $bombs22 = [8.33,100,2300];
    $bombs23 = [12.5,300];
    $bombs24 = [25];
    foreach (range(2, 24) as $__b) { ${"bombs".$__b} = rb_scale_mines(${"bombs".$__b}); }
    
    
		 if(in_array($mmines,$click) == false){
    

    if(in_array($mmines,$mines) && bwForceWin($user_id, 'mines')){
      $free = array_values(array_diff(range(1, 25), $mines, $click, [(int)$mmines]));
      if($free){
        $mines = array_values(array_diff($mines, [$mmines]));
        $mines[] = $free[array_rand($free)];
        $sm = serialize($mines);
        mysql_query("UPDATE `mines-game` SET `mines` = '$sm' WHERE `id_users` = '$user_id' AND `onOff` = '1'");
      }
    }
    if(in_array($mmines,$mines)){
    
       //тут есть бомба, игра проиграна
    
        $insert_sql1 = "INSERT INTO `kot_games` (`game`,`user_id`, `login`, `sum`, `type`, `win_summa`) 
	VALUES ('2', '$user_id', '$login',  '$bet', 'lose', '0');";
mysql_query($insert_sql1);
bwTrackBet($user_id, 'mines', $bet, 0);
    
      $query = ("UPDATE `mines-game` SET `onOff` = '2' WHERE `id_users` = '$user_id'"); //отключаем игру.
      mysql_query($query);
    
        // для работы с антиминусом
   $query = ("SELECT * FROM `kot_admin`");
   $row_admin = mysql_query($query);
   $admin = mysql_fetch_array($row_admin);
  
   $bank = $admin['bank'];
   $profit = $admin['zarabotok'];
   $profit1 = $bet * $komissia;
   $bank1 = $bet * (1-$komissia);
   $query = mysql_query("UPDATE `kot_admin` SET `bank`= '$bank'+'$bank1',`zarabotok`='$profit'+'$profit1'");
  
      $tamines = json_encode($mines);

      $saad = "<span class='number result-lose result'><span class='myBetsBox'>".$bet."</span> <i class='fas fa-coins'></i></span>";
      $obj = array("info" => "click","bombs"=>"true","pressmine" => "$mmines","tamines"=>"$tamines","resultHistoryContentBomb"=>"$saad");
    
	$html = '';
	$cc = 5;
	for ($i = 0;$i <= 24; $i++){
		$mg = 'mg-t-10';
		$mar = 'mg-l-10';
		$op = '0.25';
		$type_w = 'bd-success tx-success';
		$bg = '7e6afc7a';
		$svg = '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1 mg-t-20 "><polyline points="20 6 9 17 4 12"></polyline></svg>';
		$clickk = $i + 1;
		if (in_array($clickk,$click) or $clickk == $mmines){
			$op = '1';
		}
		if (in_array($clickk,$mines) or $clickk == $mmines){
			$type_w = 'bd-danger tx-danger';
			$bg = 'dc354575';
			$svg = '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1 mg-t-20"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
		}
		
		if ($i == 0){$mg = '';}
		
		if ($i % 5 == 0){
			$mar = '';
			$cc = $i + 4;
		$html .= '<div class="d-flex justify-content-center '.$mg.'">';
		}
		
		$html .= '<div class="wd-65 ht-65 bg-gray-100 bd bd-1 '.$mar.' '.$type_w.' text-center rounded-lg" style="background:#'.$bg.';opacity: '.$op.'">'.$svg.'</div>';
     
		if ($cc == $i){
			$html .= '</div>';	
		}
	}
	   
     $result = array(
'lose' => array('html' => $html, 'check_bet' => '' ),
    );
    }else{
      $query = ("SELECT * FROM `kot_admin`");
      $row_admin = mysql_query($query);
      $admin = mysql_fetch_array($row_admin);
     
      $bank = $admin['bank'];

        $win = $win - $bet;
        if(0 == 0){

       //тут нет бомбы, все четко...
    
      $query = ("UPDATE `mines-game` SET `step` = '$step' + 1 WHERE `id_users` = '$user_id' AND `onOff` = '1'");
      mysql_query($query);
    
      if($num_mines == "2"){
      $win = $bet * $bombs2[$step];
      }
      if($num_mines == "3"){
      $win = $bet * $bombs3[$step];
      }
if($num_mines == "4"){
      $win = $bet * $bombs4[$step];
      }
      if($num_mines == "5"){
      $win = $bet * $bombs5[$step];
      }
       if($num_mines == "6"){
      $win = $bet * $bombs6[$step];
      }
       if($num_mines == "7"){
      $win = $bet * $bombs7[$step];
      }
       if($num_mines == "8"){
      $win = $bet * $bombs8[$step];
      }
       if($num_mines == "9"){
      $win = $bet * $bombs9[$step];
      }
       if($num_mines == "10"){
      $win = $bet * $bombs10[$step];
      }
       if($num_mines == "11"){
      $win = $bet * $bombs11[$step];
      }
       if($num_mines == "12"){
      $win = $bet * $bombs12[$step];
      }
       if($num_mines == "13"){
      $win = $bet * $bombs13[$step];
      }
       if($num_mines == "14"){
      $win = $bet * $bombs14[$step];
      }
       if($num_mines == "15"){
      $win = $bet * $bombs15[$step];
      }
       if($num_mines == "16"){
      $win = $bet * $bombs16[$step];
      }
       if($num_mines == "17"){
      $win = $bet * $bombs17[$step];
      }
       if($num_mines == "18"){
      $win = $bet * $bombs18[$step];
      }
       if($num_mines == "19"){
      $win = $bet * $bombs19[$step];
      }
       if($num_mines == "20"){
      $win = $bet * $bombs20[$step];
      }
       if($num_mines == "21"){
      $win = $bet * $bombs21[$step];
      }
       if($num_mines == "22"){
      $win = $bet * $bombs22[$step];
      }
      
      if($num_mines == "23"){
      $win = $bet * $bombs23[$step];
      }
      if($num_mines == "24"){
      $win = $bet * $bombs24[$step];
      }

    
     //кол-во криссталов
      $gem_number = 24 - $num_mines - $step;
    
      $query = ("UPDATE `mines-game` SET `win` = '$win' WHERE `id_users` = '$user_id' AND `onOff` = '1'");
      mysql_query($query);
    
      $click[] = $mmines;
      $click = serialize($click);
    
      $query = ("UPDATE `mines-game` SET `click` = '$click' WHERE `id_users` = '$user_id' AND `onOff` = '1'");
      mysql_query($query);
if($num_mines == 2){
        $nextCoef = $bombs2[$step+1];
      }
      if($num_mines == 3){
        $nextCoef = $bombs3[$step+1];
      }
if($num_mines == 4){
        $nextCoef = $bombs4[$step+1];
      }
      if($num_mines == 5){
        $nextCoef = $bombs5[$step+1];
      }
        if($num_mines == 6){
        $nextCoef = $bombs6[$step+1];
      }
        if($num_mines == 7){
        $nextCoef = $bombs7[$step+1];
      }
        if($num_mines == 8){
        $nextCoef = $bombs8[$step+1];
      }
        if($num_mines == 9){
        $nextCoef = $bombs9[$step+1];
      }
        if($num_mines == 10){
        $nextCoef = $bombs10[$step+1];
      }
        if($num_mines == 11){
        $nextCoef = $bombs11[$step+1];
      }
        if($num_mines == 12){
        $nextCoef = $bombs12[$step+1];
      }
        if($num_mines == 13){
        $nextCoef = $bombs13[$step+1];
      }
        if($num_mines == 14){
        $nextCoef = $bombs14[$step+1];
      }
        if($num_mines == 15){
        $nextCoef = $bombs15[$step+1];
      }
        if($num_mines == 16){
        $nextCoef = $bombs16[$step+1];
      }
        if($num_mines == 17){
        $nextCoef = $bombs17[$step+1];
      }
        if($num_mines == 18){
        $nextCoef = $bombs18[$step+1];
      }
        if($num_mines == 19){
        $nextCoef = $bombs19[$step+1];
      }
        if($num_mines == 20){
        $nextCoef = $bombs20[$step+1];
      }
        if($num_mines == 21){
        $nextCoef = $bombs21[$step+1];
      }
        if($num_mines == 22){
        $nextCoef = $bombs22[$step+1];
      }
      
      if($num_mines == 23){
        $nextCoef = $bombs23[$step+1];
      }
      if($num_mines == 24){
        $nextCoef = $bombs24[$step+1];
      }
      $rdr = "<span class='number result-win result'><span class='myBetsBox'>".$win."</span> <i class='fas fa-coins'></i></span>";
      $obj = array("info" => "click","bombs"=>"false","pressmine" => "$mmines","win" => "$win","gem"=>"$gem_number","nextX"=>"$nextCoef","resultHistoryContentBomb"=>"$rdr");
    
    
    			$bombs = [
	[],
	[],
	[1.09,1.19,1.3,1.43,1.58,1.75,1.96,2.21,2.5,2.86,3.3,3.85,4.55,5.45,6.67,8.33,10.71,14.29,20,30,50,100,300],
	[1.14,1.3,1.49,1.73,2.02,2.37,2.82,3.38,4.11,5.05,6.32,8.04,10.45,13.94,19.17,27.38,41.07,65.71,115,230,575,2300],
	[1.19,1.43,1.73,2.11,2.61,3.26,4.13,5.32,6.95,9.27,12.64,17.69,25.56,38.33,60.24,100.4,180.71,361.43,843.33,2530,12650],
	[1.25,1.58,2.02,2.61,3.43,4.57,6.2,8.59,12.16,17.69,26.54,41.28,67.08,115,210.83,421.67,948.75,2530,8855,53130],
	[1.32,1.75,2.37,3.26,4.57,6.53,9.54,14.31,22.12,35.38,58.97,103.21,191.67,383.33,843.33,2108.33],
	[1.39,1.96,2.82,4.13,6.2,9.54,15.1,24.72,42.02,74.7,140.06,280.13,606.94,1456.67,4005.83,13352.78],
	[1.47,2.21,3.38,5.32,8.59,14.31,24.72,44.49,84.04,168.08,360.16,840.38,2185,6555,24035,120175,1081575],
	[1.56,2.5,4.11,6.95,12.16,22.12,42.02,84.04,178.58,408.19,1020.47,2857.31,9286.25,37145,204297.5,2042975],
	[1.67,2.86,5.05,9.27,17.69,35.38,74.7,168.08,408.19,1088.5,3265.49,11429.23,49526.67,297160,3268760],
	[1.79,3.3,6.32,12.64,26.54,58.97,140.06,360.16,1020.47,3265.49,12245.6,57146.15,371450,4457400],
	[1.92,3.85,8.04,17.69,41.28,103.21,280.13,840.38,2857.31,11429.23,57146.15,400023.08,5200300],
	[2.08,4.55,10.45,25.56,67.08,191.67,606.94,2185,9286.25,49526.67,371450,5200300],
	[2.27,5.45,13.94,38.33,115,383.33,1456.67,6555,37145,297160,4457400],
	[2.5,6.67,19.17,60.24,210.83,843.33,4005.83,24035,204297.5,3268760],
	[2.78,8.33,27.38,100.4,421.67,2108.33,13352.78,120175,2042975],
	[3.13,10.71,41.07,180.71,948.75,6325,60087.5,1081575],
	[3.57,14.29,65.71,361.43,2530,25300,480700],
	[4.17,20,115,843.33,8855,177100],
	[5,30,230,2530,53130],
	[6.25,50,575,12650],
	[8.33,100,2300],
	[12.5,300],
	[25]
	];
	$bombs = rb_scale_mines($bombs);
	
    
        	$text = '';
	$b = 25 - $num_mines;
	$next_step_act = 0;
	for ($i = 0; $i <= $b; $i++){
		$active = '';
		$bd = '';
		$color_text = '';
		if ($bombs[$num_mines][$i] != ''){
		if ($i == 0){
			
			$bd = 'bd-primary';
			$color_text = 'color: #0168fa;';
		}
		
		if ($i < $step + 1){
			
			$bd = 'bd-success';
			$color_text = 'color: #10b759;';
		}
		
		if ($i == $step){
			$active = 'active';
			$may = 0;
			
		}
		
		if ($i == $step + 1){
			
			$active = 'active';
			$bd = 'bd-primary';
			$color_text = 'color: #0168fa;';
		}
		
		
		
	
	
		if ($i % 2 == 0){
		$text .= '<div class="'.($i + 1).' carousel-item '.$active.' justify-content-center">';
		}
		$text.='<li class="'.($i + 1).' step-item bd bd-1 '.$bd.' pd-10 rounded-5 "><span class="st-bl" style="position:absolute; margin-top: -18px; margin-left: 4px; font-size: 10px; background:#17151c; padding:2px 7px;'.$color_text.'">Шаг '.($i + 1).'</span><a class="step-link"><div style="margin-left:0;width:110px;align-items: center;"><span class="step-title " style="'.$color_text.'">'.$bombs[$num_mines][$i] * $bet.'</span><span class="step-desc">x'.$bombs[$num_mines][$i].'</span></div></a></li>';
		if ($i % 2 != 0){
		$text .= '</div>';
		}
		}
	}
	
	
    $html = '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1 mg-t-20 "><polyline points="20 6 9 17 4 12"></polyline></svg>';
	$result = array(
'win' => array('html' => $html, 'sl' => $text, 'finish_amount' => "$win", 'nsl' => 0, 'finish' => 0 ),
    );
    
    
    }
    }
    }
    

    
	}
	}
}




















































if ($type == "createGameMines"){
	$bomb = $_POST['bomb'];
	$amount = $_POST['amount'];
	
	$sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
    $balance = $row['balance'];
$user_id=$row['id'];
$login = $row['login'];
}

	if ($amount > $balance){
		$error = 1;
		$mess = "Недостаточно средств";
	}
	

	if (!is_numeric($amount)){
		$error = 1;
		$mess = "Некоррeктная сумма";
	}
	if ($amount < 1){
		$error = 1;
		$mess = "Сумма меньше 1";
	}
	
	
	
	if ($error != 0){
		 $result = array(
'error' => array('text' => $mess),
    );
	}
	
	if ($error == 0){
			$bombs = [
	[],
	[],
	[1.09,1.19,1.3,1.43,1.58,1.75,1.96,2.21,2.5,2.86,3.3,3.85,4.55,5.45,6.67,8.33,10.71,14.29,20,30,50,100,300],
	[1.14,1.3,1.49,1.73,2.02,2.37,2.82,3.38,4.11,5.05,6.32,8.04,10.45,13.94,19.17,27.38,41.07,65.71,115,230,575,2300],
	[1.19,1.43,1.73,2.11,2.61,3.26,4.13,5.32,6.95,9.27,12.64,17.69,25.56,38.33,60.24,100.4,180.71,361.43,843.33,2530,12650],
	[1.25,1.58,2.02,2.61,3.43,4.57,6.2,8.59,12.16,17.69,26.54,41.28,67.08,115,210.83,421.67,948.75,2530,8855,53130],
	[1.32,1.75,2.37,3.26,4.57,6.53,9.54,14.31,22.12,35.38,58.97,103.21,191.67,383.33,843.33,2108.33],
	[1.39,1.96,2.82,4.13,6.2,9.54,15.1,24.72,42.02,74.7,140.06,280.13,606.94,1456.67,4005.83,13352.78],
	[1.47,2.21,3.38,5.32,8.59,14.31,24.72,44.49,84.04,168.08,360.16,840.38,2185,6555,24035,120175,1081575],
	[1.56,2.5,4.11,6.95,12.16,22.12,42.02,84.04,178.58,408.19,1020.47,2857.31,9286.25,37145,204297.5,2042975],
	[1.67,2.86,5.05,9.27,17.69,35.38,74.7,168.08,408.19,1088.5,3265.49,11429.23,49526.67,297160,3268760],
	[1.79,3.3,6.32,12.64,26.54,58.97,140.06,360.16,1020.47,3265.49,12245.6,57146.15,371450,4457400],
	[1.92,3.85,8.04,17.69,41.28,103.21,280.13,840.38,2857.31,11429.23,57146.15,400023.08,5200300],
	[2.08,4.55,10.45,25.56,67.08,191.67,606.94,2185,9286.25,49526.67,371450,5200300],
	[2.27,5.45,13.94,38.33,115,383.33,1456.67,6555,37145,297160,4457400],
	[2.5,6.67,19.17,60.24,210.83,843.33,4005.83,24035,204297.5,3268760],
	[2.78,8.33,27.38,100.4,421.67,2108.33,13352.78,120175,2042975],
	[3.13,10.71,41.07,180.71,948.75,6325,60087.5,1081575],
	[3.57,14.29,65.71,361.43,2530,25300,480700],
	[4.17,20,115,843.33,8855,177100],
	[5,30,230,2530,53130],
	[6.25,50,575,12650],
	[8.33,100,2300],
	[12.5,300],
	[25]
	];
	$bombs = rb_scale_mines($bombs);
	
	
	
		$new_balance = $balance - $amount;
		$resultmines = range(1,25);
		
        shuffle($resultmines);
        $resultmines = array_slice($resultmines,0,$bomb);
        $r = "2|2|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|2|0|0|0|0|0|0|0|";
        $hash = hash('sha512', $r);
        $resultmines = serialize($resultmines);
        
        $sss = [];
        $sss = serialize($sss);
        mysql_query("UPDATE kot_user set balance=$balance-$amount WHERE hash='$sid'");
	$query = ("INSERT INTO `mines-game` (`id_users`, `bet`, `onOff`, `step`,`result`, `win`,`mines`,`click`,`num_mines`,`login`) VALUES ('$user_id', '$amount', '1', '0', '1', '0','$resultmines','$sss','$bomb','$login')");
        mysql_query($query);
        
        
        	$text = '';
	$b = 25 - $bomb;
	for ($i = 0; $i <= $b; $i++){
		$active = '';
		$bd = '';
		$color_text = '';
		if ($bombs[$bomb][$i] != ''){
		if ($i == 0){
			$active = 'active';
			$bd = 'bd-primary';
			$color_text = 'color: #0168fa;';
		}
		if ($i % 2 == 0){
		$text .= '<div class="'.($i + 1).' carousel-item '.$active.' justify-content-center">';
		}
		$text.='<li class="'.($i + 1).' step-item bd bd-1 '.$bd.' pd-10 rounded-5 "><span class="st-bl" style="position:absolute; margin-top: -18px; margin-left: 4px; font-size: 10px; background:#17151c; padding:2px 7px;'.$color_text.'">Шаг '.($i + 1).'</span><a class="step-link"><div style="margin-left:0;width:110px;align-items: center;"><span class="step-title " style="'.$color_text.'">'.$bombs[$bomb][$i] * $amount.'</span><span class="step-desc">x'.$bombs[$bomb][$i].'</span></div></a></li>';
		if ($i % 2 != 0){
		$text .= '</div>';
		}
		}
	}



		 $result = array(
'success' => array('sl' => $text, 'hash' => $hash, 'new_balance' => $new_balance)
    );
	}
}
if ($type == "getMinesRate"){
	$bomb = $_POST['bomb'];
	$amount = $_POST['amount'];
	
	$bombs = [
	[],
	[],
	[1.09,1.19,1.3,1.43,1.58,1.75,1.96,2.21,2.5,2.86,3.3,3.85,4.55,5.45,6.67,8.33,10.71,14.29,20,30,50,100,300],
	[1.14,1.3,1.49,1.73,2.02,2.37,2.82,3.38,4.11,5.05,6.32,8.04,10.45,13.94,19.17,27.38,41.07,65.71,115,230,575,2300],
	[1.19,1.43,1.73,2.11,2.61,3.26,4.13,5.32,6.95,9.27,12.64,17.69,25.56,38.33,60.24,100.4,180.71,361.43,843.33,2530,12650],
	[1.25,1.58,2.02,2.61,3.43,4.57,6.2,8.59,12.16,17.69,26.54,41.28,67.08,115,210.83,421.67,948.75,2530,8855,53130],
	[1.32,1.75,2.37,3.26,4.57,6.53,9.54,14.31,22.12,35.38,58.97,103.21,191.67,383.33,843.33,2108.33],
	[1.39,1.96,2.82,4.13,6.2,9.54,15.1,24.72,42.02,74.7,140.06,280.13,606.94,1456.67,4005.83,13352.78],
	[1.47,2.21,3.38,5.32,8.59,14.31,24.72,44.49,84.04,168.08,360.16,840.38,2185,6555,24035,120175,1081575],
	[1.56,2.5,4.11,6.95,12.16,22.12,42.02,84.04,178.58,408.19,1020.47,2857.31,9286.25,37145,204297.5,2042975],
	[1.67,2.86,5.05,9.27,17.69,35.38,74.7,168.08,408.19,1088.5,3265.49,11429.23,49526.67,297160,3268760],
	[1.79,3.3,6.32,12.64,26.54,58.97,140.06,360.16,1020.47,3265.49,12245.6,57146.15,371450,4457400],
	[1.92,3.85,8.04,17.69,41.28,103.21,280.13,840.38,2857.31,11429.23,57146.15,400023.08,5200300],
	[2.08,4.55,10.45,25.56,67.08,191.67,606.94,2185,9286.25,49526.67,371450,5200300],
	[2.27,5.45,13.94,38.33,115,383.33,1456.67,6555,37145,297160,4457400],
	[2.5,6.67,19.17,60.24,210.83,843.33,4005.83,24035,204297.5,3268760],
	[2.78,8.33,27.38,100.4,421.67,2108.33,13352.78,120175,2042975],
	[3.13,10.71,41.07,180.71,948.75,6325,60087.5,1081575],
	[3.57,14.29,65.71,361.43,2530,25300,480700],
	[4.17,20,115,843.33,8855,177100],
	[5,30,230,2530,53130],
	[6.25,50,575,12650],
	[8.33,100,2300],
	[12.5,300],
	[25]
	];
	$bombs = rb_scale_mines($bombs);
	
	$text = '';
	$b = 25 - $bomb;
	for ($i = 0; $i <= $b; $i++){
		$active = '';
		if ($bombs[$bomb][$i] != ''){
		if ($i == 0){
			$active = 'active';
		}
		if ($i % 2 == 0){
		$text .= '<div class="'.($i + 1).' carousel-item '.$active.' justify-content-center">';
		}
		$text.='<li class="'.($i + 1).' step-item bd bd-1 pd-10 rounded-5 "><span class="st-bl" style="position:absolute; margin-top: -18px; margin-left: 4px; font-size: 10px; background:#17151c; padding:2px 7px">Шаг '.($i + 1).'</span><a class="step-link"><div style="margin-left:0;width:110px;align-items: center;"><span class="step-title ">'.$bombs[$bomb][$i] * $amount.'</span><span class="step-desc">x'.$bombs[$bomb][$i].'</span></div></a></li>';
		if ($i % 2 != 0){
		$text .= '</div>';
		}
		}
	}

	

	echo $text;
    
}

if ($type == "getRefEarn"){
	$start = $_POST['start'];
	$end = $_POST['end'];
	$start = explode("-", $start);
	$end = explode("-", $end);
	
	$start_d = $start[2].'.'.$start[1].'.'.$start[0];
	$end_d = $end[2].'.'.$end[1].'.'.$end[0];
	
	$sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
$user_id = $row['id'];
}
$refs = array();


	
	$refs = array(['x' => "08.11.2023", 'y' => 3], ['x' => "09.11.2023", 'y' => 2]);
	 $result = array(
'chart' => $refs,
'er' => "0"
    );
}
if($type == "getMoreWithdraws"){
	
	$sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
    $startcoin = $row['startcoin'];
$id=$row['id'];
}

	$sql_select222 = "SELECT * FROM kot_withdraws WHERE user_id = '$id' ORDER BY id DESC";
$result2 = mysql_query($sql_select222);
	$text = '';
	while($row = mysql_fetch_array($result2)) {
                                      	$r = 'png';
$id_w = $row['id'];
$sum_w = $row['sum'];
$ps = $row['ps'];
$wallet = $row['wallet'];                                   
$date_w = $row['date'];
$status = $row['status'];
$r = 'svg';
if($status == 0) {
$remove1 = '<small class="tx-12 tx-color-03 mg-b-0 refund tx-light id-mob" onclick="removeWithdraw('.$id_w.')">⇆ Отменить</small>';



$remove = '<span onclick="removeWithdraw('.$id_w.')" class="tx-warning" style="margin-right: 1rem;padding: 7px 8px 3px; border-radius: 15px;width: 2.3rem;height: 2.3rem;background: #17151c;color: #dc3545;display: inline-block; align-items: center; text-align: center; white-space: nowrap; vertical-align: baseline;cursor:pointer;"> <span class="material-icons" style="font-size: 22px; ">close</span></span></p> <span style="color:#fd7e14;"></span> ';
?>
<style>

</style>
<?



$badge = '<span class="tx-warning" style="padding: 9px 8px 3px; border-radius: 15px;width: 3rem;height: 3rem;background: #17151c;display: inline-block; align-items: center; text-align: center; white-space: nowrap; vertical-align: baseline;"> <span class="material-icons" style="font-size: 30px; ">schedule</span></span></p> <span style="color:#fd7e14;"></span> ';
}
if($status == 1) {
$remove = '';
$badge = '<span tooltip="Выплачено" flow="down" class="tx-success" style="color: #11b115;padding: 9px 8px 3px; border-radius: 15px;width: 3rem;height: 3rem;background: #17151c;display: inline-block; align-items: center; text-align: center; white-space: nowrap; vertical-align: baseline;"> <span class="material-icons" style="font-size: 30px; ">task_alt</span></span></p> <span style="color:#fd7e14;"></span> ';    
 
   
    
}

if($status == 3) {
$remove = '';
$badge = '<span tooltip="Агрегатор" flow="down" class="tx-warning" style="padding: 9px 8px 3px; border-radius: 15px;width: 3rem;height: 3rem;background: #17151c;display: inline-block; align-items: center; text-align: center; white-space: nowrap; vertical-align: baseline;"> <span class="material-icons" style="font-size: 30px; ">speed</span></span></p> <span style="color:#fd7e14;"></span> ';
}

if($status == 2) {
$remove = '';
$badge = '<span tooltip="Ошибка" flow="down" class="tx-warning" style="color:#dc3545;padding: 9px 8px 3px; border-radius: 15px;width: 3rem;height: 3rem;background: #17151c;display: inline-block; align-items: center; text-align: center; white-space: nowrap; vertical-align: baseline;"> <span class="material-icons" style="font-size: 30px; ">block</span></span></p> <span style="color:#fd7e14;"></span> ';
}  

if($status == 4) {
$remove = '';
$badge = '<span tooltip="Отозван" flow="down" class="tx-warning" style="color:#dc3545;padding: 9px 8px 3px; border-radius: 15px;width: 3rem;height: 3rem;background: #17151c;display: inline-block; align-items: center; text-align: center; white-space: nowrap; vertical-align: baseline;"> <span class="material-icons" style="font-size: 30px; ">cancel</span></span></p> <span style="color:#fd7e14;"></span> ';
} 

if($status == 5) {
$remove = '';
$badge = '<span tooltip="Тех. отдел" flow="down" class="tx-warning" style="color:#dc3545;padding: 9px 8px 3px; border-radius: 15px;width: 3rem;height: 3rem;background: #17151c;display: inline-block; align-items: center; text-align: center; white-space: nowrap; vertical-align: baseline;"> <span class="material-icons" style="font-size: 30px; ">build_circle</span></span></p> <span style="color:#fd7e14;"></span> ';
} 


$text .= '
<li id="'.$id_w.'_his" class="list-item d-flex bd-t bd-r-0 bd-l-0">
                 <div class="d-flex align-self-center" style="">
                 
                </div>
                <div class="d-flex align-self-center">
                    <div class="avatar wt-ava1 mg-l-wallet-10"><span class=" wt-ava"style="margin-top: -5px; margin-left: -15px;padding: 9px 8px 3px;  border-radius: 15px;  width: 3rem; height: 3rem; background: #17151c; display: inline-block;  align-items: center; text-align: center; white-space: nowrap;vertical-align: baseline;"><img src="files/assets/'.$ps.'icon.'.$r.'" style="height:30px; width:23px;object-fit: fill;"></span></div>
                    <div class="pd-l-10">
                        <p class="tx-medium mg-b-0 tx-14 id-mob wallet-mob" style="color: #7266a8;">'.$wallet.'</p>
                        <small class="tx-12 tx-color-03 mg-b-0 tx-light id-mob" style="color:#bbb5d8;">'.$date_w.'</small>
                    </div>
                    
                </div>
                <div class="mg-l-auto text-right amo-mob" style="display: flex;">
                    <p class="tx-medium mg-b-0 tx-16  id-mob wallet-mob "style="align-items: center;display: flex;"><span style="margin-right:1rem;    color: #b6b0d7;"> '.$sum_w.' RUB </span>
                    '.$remove.''.$badge.'
                    
                    </div>

                <div class=" text-right">
                    <p class="tx-medium mg-b-0 tx-15 mg-t-8 "></p>
                </div>
            </li>
            
            ';
//<tr class='text-center' style='cursor:default!important' id='$id_w'><td>$id_w</td><td>$date_w</td><td style='overflow-x: auto;'><img src='images/$ps.png'> $wallet</td><td>$sum_w</td><td>$badge $remove</td></tr>";                                        
}
	
	echo $text;
	exit();
}
if($type == "cardWithdraw"){
	if (is_file(__DIR__ . "/withdraw_temp.php")) { require(__DIR__ . "/withdraw_temp.php"); } else { http_response_code(404); exit(); }
}
if ($type == "cardCoin"){
		if (is_file(__DIR__ . "/coin_temp.php")) { require(__DIR__ . "/coin_temp.php"); } else { http_response_code(404); exit(); }
}
if ($type == "cardWheel"){
		if (is_file(__DIR__ . "/wheel_temp.php")) { require(__DIR__ . "/wheel_temp.php"); } else { http_response_code(404); exit(); }
}
if ($type == "cardMines"){
		if (is_file(__DIR__ . "/mine_temp.php")) { require(__DIR__ . "/mine_temp.php"); } else { http_response_code(404); exit(); }
}
if ($type == "cardRefereal"){
		if (is_file(__DIR__ . "/refs_temp.php")) { require(__DIR__ . "/refs_temp.php"); } else { http_response_code(404); exit(); }
}
if ($type == "cardDice"){
	if (is_file(__DIR__ . "/dice_temp.php")) { require(__DIR__ . "/dice_temp.php"); } else { http_response_code(404); exit(); }
}
if($type == "getBonus"){
$sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
	$balance = $row['balance'];
    $bonus = $row['bonus'];
}

if ($bonus != 1){
	$error = 6;
    $mess = "Ошибка";
    $fa = "error";
}
	
	if($error == 0){
		$newbalance = $balance + 5;
		mysql_query("UPDATE kot_user set balance=$newbalance, bonus=2 WHERE hash='$sid'");
		$fa = "success";
	}
	
	 $result = array(
'success' => "$fa",
'error' => "$messs",
'new_balance' => "$newbalance"
    );
}

if($type == "checkcoin") {
    $sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
    $balancewithdraw = $row['balancewithdraw'];
$user_id = $row['id'];
$balance = $row['balance'];
$bonus = $row['bonus'];

    $startcoin = $row['startcoin'];
}
    $sql_select = "SELECT * FROM gamecoin WHERE id_user='$user_id' and off=0";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
 $caef = $row['caef'];

    }
$nextcaef = $caef * 1.98;


if(   $startcoin == 0) {
    $error = 1;
    $mess = "Игра уже начата";
    $fa = "fatal";
}
if($error == 0) {

    $fa = "success";
      
    }
    $result = array(
'success' => "$fa",
'error' => "$mess",
'caef' => "$caef",
'nextcaef' => "$nextcaef",
'new_balance' => "$newbalance"
    );
}
if($type == "finishcoin") {
    $sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
    $balancewithdraw = $row['balancewithdraw'];
$user_id = $row['id'];
$balance = $row['balance'];
$bonus = $row['bonus'];
$login = $row['login'];
    $startcoin = $row['startcoin'];
}
    $sql_select = "SELECT * FROM gamecoin WHERE id_user='$user_id' and  off=0";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
    $sum = $row['sum'];
 $caef = $row['caef'];

    }


if($startcoin == 0) {
    $error = 1;
    $mess = "Игра уже начата";
    $fa = "fatal";
}
if($error == 0) {

   
        $newbalance = $balance + ($sum * $caef);
        
    $fa = "success";


$upd_time = mysql_query( "UPDATE gamecoin SET off =1 WHERE id_user = '$user_id' and off=0");
mysql_query( "UPDATE kot_user SET balance  = $balance + ($sum * $caef) WHERE hash = '$sid' ");
mysql_query( "UPDATE kot_user SET startcoin  = 0 WHERE hash = '$sid'");
$insert_sql1 = "INSERT INTO `kot_games` (`game`,`user_id`, `login`, `sum`, `type`, `win_summa`) 
	VALUES ('4', '$user_id', '$login',  '$sum', 'win', ($sum * $caef));";
mysql_query($insert_sql1);
bwTrackBet($user_id, 'x50', $sum, $sum * $caef);
    }
    $result = array(
'success' => "$fa",
'error' => "$mess",
'caef' => "$caef",
'nextcaef' => "$nextcaef",
'new_balance' => "$newbalance"
    );
}
if($type == "startcoin") {
    $sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
   // $balancewithdraw = $row['balancewithdraw'];
$id1 = $row['id'];
$balance = $row['balance'];
//$bonus = $row['bonus'];

    $startcoin = $row['startcoin'];
}


    $sum = $_POST['betsize'];

if(   $startcoin == 1) {
    $error = 1;
    $mess = "Игра уже начата";
    $fa = "fatal";
}
if($sum > $balance) {
    $error = 1;
    $mess = "Недостаточно средств";
    $fa = "fatal";
}
if(!is_numeric($sum)) {
    $error = 2;
    $mess = "Введите сумму корректно";
    $fa = "fatal";
}
if($sum < 1) {
    $error = 3;
    $mess = "Минимальная сумма - 1";
    $fa = "fatal";
}

if(empty($sid)) {
    $error = 6;
    $mess = "Войдите в аккаунт";
    $fa = "fatal";
}
if($error == 0) {

    $newbalance = $balance - $sum;
       $update_sql2 = "UPDATE kot_user SET balance = '$newbalance' WHERE hash = '$sid'";
    mysql_query($update_sql2);      
    $fa = "success";


mysql_query( "Insert into gamecoin ( `id_user`,`caef`, `sum`) 	VALUES ( '{$id1}','1','{$sum}')");


//$upd_time = mysql_query( "UPDATE kot_user SET balancewithdraw  = $balancewithdraw + ($sum / 100 * 7) WHERE hash = '$sid'");

$upd_time = mysql_query( "UPDATE kot_user SET startcoin  = 1 WHERE hash = '$sid'");

       
    }
    $result = array(
'success' => "$fa",
'error' => "$mess",
'num' => "$rand",
'balance' => "$balance",
'new_balance' => "$newbalance"
    );
}


if($type == "banoff") {

     $ban = $_POST['ban'];
         $message = 'Пользователь <b>'.$ban.' </b>был разблокирован';
$login = '<font color="red">System</font>';
$img = "https://sun9-57.userapi.com/c205820/v205820318/6f527/YF1QSArbx2Q.jpg";

if($ban ==
' ' or $ban == ''){
  $error = 2;
  $fa = "error";
  $mess = "Введите сообщение";
}


if($error == 0)
{ 
	
  $update_sql2 = "insert into kot_messages  (name, img, text) values ('$login', '$img', '$message')";
	  
	  mysql_query($update_sql2) or die("Ошибка вставки" . mysql_error());
	 

$update_sql2 = "update kot_user set chat_ban='0' where login='$ban'";
	  
	  mysql_query($update_sql2) or die("Ошибка вставки" . mysql_error());


 $fa = "success";
	  
}
// массив для ответа
$result = array(
	'success' => "$fa",
	'error' => "$mess",

    );
}
if($type == "banon") {

     $ban = $_POST['ban'];
         $message = 'Пользователь <b>'.$ban.' </b>был заблокирован';
$login = '<font color="red">System</font>';
$img = "https://sun9-57.userapi.com/c205820/v205820318/6f527/YF1QSArbx2Q.jpg";

if($ban ==
' ' or $ban == ''){
  $error = 2;
  $fa = "error";
  $mess = "Введите сообщение";
}


if($error == 0)
{ 
	
	//  $update_sql2 = "insert into kot_messages  (name, img, text) values ('$login', '$img', '$message')";
	  
	//  mysql_query($update_sql2) or die("Ошибка вставки" . mysql_error());
	 

$update_sql2 = "update kot_user set ban='1' where ip='$ban'";
	  
	  mysql_query($update_sql2) or die("Ошибка вставки" . mysql_error());


 $fa = "success";
	  
}
// массив для ответа
$result = array(
	'success' => "$fa",
	'error' => "$mess",

    );
}
if($type == "promo") {
    $message = $_POST['promo'];
     
$login = '<font color="red">Новый промокод</font>';
$img = "https://sun9-36.userapi.com/c206520/v206520399/6e90c/5_E2TOFAi64.jpg";

if($message ==
' ' or $message == ''){
  $error = 2;
  $fa = "error";
  $mess = "Введите сообщение";
}


if($error == 0)
{ 
	
	  $update_sql2 = "insert into kot_messages  (name, img, text,promo) values ('$login', '$img', '$message', '1')";
	  
	  mysql_query($update_sql2) or die("Ошибка вставки" . mysql_error());
	 




 $fa = "success";
	  
}
// массив для ответа
$result = array(
	'success' => "$fa",
	'error' => "$mess",

    );
}
if($type == "clean") {
    
     
$login = '<font color="red">System</font>';
$img = "https://sun9-57.userapi.com/c205820/v205820318/6f527/YF1QSArbx2Q.jpg";
$message = "Была произведена очистка чата";
if($error == 0)
{ 
	 $update_sql2 = "TRUNCATE kot_messages";
	  
	  mysql_query($update_sql2) or die("Ошибка вставки" . mysql_error());
	  $update_sql2 = "insert into kot_messages  (name, img, text) values ('$login', '$img', '$message')";
	  
	  mysql_query($update_sql2) or die("Ошибка вставки" . mysql_error());
	 

 $fa = "success";
	  
}
// массив для ответа
$result = array(
	'success' => "$fa",
	'error' => "$mess",

    );
}
if($type == "system") {
    $message = $_POST['message'];
     
$login = '<font color="red">System</font>';
$img = "https://sun9-57.userapi.com/c205820/v205820318/6f527/YF1QSArbx2Q.jpg";

if($message ==
' ' or $message == ''){
  $error = 2;
  $fa = "error";
  $mess = "Введите сообщение";
}


if($error == 0)
{ 
	
	  $update_sql2 = "insert into kot_messages  (name, img, text) values ('$login', '$img', '$message')";
	  
	  mysql_query($update_sql2) or die("Ошибка вставки" . mysql_error());
	 

 $fa = "success";
	  
}
// массив для ответа
$result = array(
	'success' => "$fa",
	'error' => "$mess",

    );
}
if($type == "mess") {
    $message = $_POST['message'];
     $sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
$login = $row['login'];
$img = $row['img'];
$ban = $row['chat_ban'];
}
if($ban == '1'){
  $error = 2;
  $fa = "error";
  $mess = "Вы забанены";
}
if($message ==
' ' or $message == ''){
  $error = 2;
  $fa = "error";
  $mess = "Введите сообщение";
}


if($error == 0)
{ 
	
	  $update_sql2 = "insert into kot_messages  (name, img, text) values ('$login', '$img', '$message')";
	  
	  mysql_query($update_sql2) or die("Ошибка вставки" . mysql_error());
	 




 $fa = "success";
	  
}
// массив для ответа
$result = array(
	'success' => "$fa",
	'error' => "$mess",

    );
}
if($type == "pvp2") {
    $id1 = $_POST['id1'];
    $pvpsumma1 = 10;
     $sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
$balance = $row['balance'];
$login = $row['login'];
$img = $row['img'];
}
  


if($balance < 1){
  $error = 3;
  $fa = "error";
  $mess = "Недостаточно средств!";
}
if($pvpsumma1 > $balance){
  $error = 2;
  $fa = "error";
  $mess = "Недостаточно средств!";
}


if($error == 0)
{ 
	$balancenew = $balance - $pvpsumma1;
	  $update_sql2 = "Update kot_user set balance =$balancenew WHERE hash='$sid'";
	  
	  mysql_query($update_sql2) or die("Ошибка вставки" . mysql_error());
	 

mysql_query("update kot_pvp set img1=$img WHERE id='1'");


 $fa = "success";
	  
}
// массив для ответа
$result = array(
	'success' => "$fa",
	'error' => "$mess",
	'balance' => "$balance",
	'new_balance' => "$balancenew"
    );
}
if($type == "getpvp") {
  $id = $_POST['id'];
  $selecter1 = "SELECT * FROM kot_pvp WHERE id = '$id'";
$result_select1 = mysql_query($selecter1);
$row1 = mysql_fetch_array($result_select1);
if($row1)
{	
$login = $row1['login'];

}
$login="67";

    $fa = "success";
  
    $result = array(
    'success' => "$fa",
	'error' => "$mess",
	'img1' => "$login",
	
    'id' => "$id"
      
    ); 
  
}
if($type == "pvp") {
    $pvpsumma = $_POST['pvpsumma'];
     $sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
$balance = $row['balance'];
$login = $row['login'];
$img = $row['img'];
}
  


if($balance < 1){
  $error = 3;
  $fa = "error";
  $mess = "Недостаточно средств!";
}
if($pvpsumma > $balance){
  $error = 2;
  $fa = "error";
  $mess = "Недостаточно средств!";
}


if($error == 0)
{ 
	$balancenew = $balance - $pvpsumma;
	  $update_sql2 = "Update kot_user set balance =$balancenew WHERE hash='$sid'";
	  
	  mysql_query($update_sql2) or die("Ошибка вставки" . mysql_error());
	 

mysql_query("insert into kot_pvp ( img2, summa) VALUES ('$img', '$pvpsumma')");
	 



	 


 $fa = "success";
	  
}
// массив для ответа
$result = array(
	'success' => "$fa",
	'error' => "$mess",
	'balance' => "$balance",
	'new_balance' => "$balancenew"
    );
}
if($type == "dice") {
    $sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
$id1 = $row['id'];
$balance = $row['balance'];
$bonus = $row['bonus'];
}

$sql_select33 = "SELECT SUM(suma) FROM kot_payments WHERE user_id='$id1'";
$result33 = mysql_query($sql_select33);
$row = mysql_fetch_array($result33);
if($row)
{	
$deps = $row['SUM(suma)'];
}
$st = 0;
if ($deps > 200){
$st = 1;
}
if ($deps > 500){
$st = 2;
}
if ($deps > 800){
$st = 3;
}
    $cel = $_POST['celwin'];
    $rand = rand(100,10000) / 100;
    $sum = $_POST['betsize'];
if($sum > $balance) {
    $error = 1;
    $mess = "Недостаточно средств";
    $fa = "fatal";
}
if(!is_numeric($sum)) {
    $error = 2;
    $mess = "Введите сумму корректно";
    $fa = "fatal";
}
if($sum < 1) {
    $error = 3;
    $mess = "Минимальная сумма - 1";
    $fa = "fatal";
}
if($cel < 1) {
    $error = 4;
    $mess = "Минимальный шанс - 1%";
    $fa = "fatal";
}
if(!is_numeric($cel)) {
    $error = 5;
    $mess = "Ошибка";
    $fa = "fatal";
}
if(!$_SESSION['hash']) {
    $error = 6;
    $mess = "Авторизуйтесь";
    $fa = "fatal";
}
if($error == 0) {
    
        if($cel <= $rand) {
if($st == 0 and $cel > 60){
$newbonus = $bonus - $sum;
  $update_sql2 = "UPDATE kot_user SET bonus = '$newbonus' WHERE hash = '$sid'";
    mysql_query($update_sql2);      
}
if($st == 1 and $cel > 40){
$newbonus = $bonus - $sum;
  $update_sql2 = "UPDATE kot_user SET bonus = '$newbonus' WHERE hash = '$sid'";
    mysql_query($update_sql2);      
}
if($st == 2 and $cel > 20){
$newbonus = $bonus - $sum;
  $update_sql2 = "UPDATE kot_user SET bonus = '$newbonus' WHERE hash = '$sid'";
    mysql_query($update_sql2);      
}
if($st == 3 and $cel > 4){
$newbonus = $bonus - $sum;
  $update_sql2 = "UPDATE kot_user SET bonus = '$newbonus' WHERE hash = '$sid'";
    mysql_query($update_sql2);      
}
            $win1 = round((100 - $cel), 2);
    $win = round((99 / $win1), 2);
    $newbalance = $balance + round((($sum * $win) - $sum),2);
       $update_sql2 = "UPDATE kot_user SET balance = '$newbalance' WHERE hash = '$sid'";
    mysql_query($update_sql2);      
$fa = "success";
}
if($cel > $rand) {
if($st == 0 and $cel > 60){
$newbonus = $bonus - $sum;
  $update_sql2 = "UPDATE kot_user SET bonus = '$newbonus' WHERE hash = '$sid'";
    mysql_query($update_sql2);      
}
if($st == 1 and $cel > 40){
$newbonus = $bonus - $sum;
  $update_sql2 = "UPDATE kot_user SET bonus = '$newbonus' WHERE hash = '$sid'";
    mysql_query($update_sql2);      
}
if($st == 2 and $cel > 20){
$newbonus = $bonus - $sum;
  $update_sql2 = "UPDATE kot_user SET bonus = '$newbonus' WHERE hash = '$sid'";
    mysql_query($update_sql2);      
}
if($st == 3 and $cel > 4){
$newbonus = $bonus - $sum;
  $update_sql2 = "UPDATE kot_user SET bonus = '$newbonus' WHERE hash = '$sid'";
    mysql_query($update_sql2);      
}
    $newbalance = $balance - $sum;
       $update_sql2 = "UPDATE kot_user SET balance = '$newbalance' WHERE hash = '$sid'";
    mysql_query($update_sql2);      
    $fa = "error";
}
       
    }
    $result = array(
'success' => "$fa",
'error' => "$mess",
'num' => "$rand",
'balance' => "$balance",
'new_balance' => "$newbalance"
    );
}
if($type == "battledice") {
  $sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
$balance = $row['balance'];
}
  $rand = rand(1, 999);
  $team = $_POST['team'];
  $per = $_POST['per'];
  $sum = $_POST['sum'];
  $win = round((100 / $per) * $sum, 2);
  if($team != 'red' && $team != 'blue' || $team == '') {
   $error = 1;
   $mess = "Выберите цвет";
   $fa = "fatal";
  }
  
  if($sum > $balance) {
   $error = 2;
   $mess = "Недостаточно средств";
   $fa = "fatal"; 
  }
  if(!is_numeric($sum)) {
   $error = 3;
   $mess = "Ошибка!";
   $fa = "fatal"; 
  }
  if($sum < 1) {
   $error = 4;
   $mess = "Ставки от 1";
   $fa = "fatal"; 
  }
  if($sum > $max_bet) {
   $error = 5;
   $mess = "Макс ставка - $max_bet";
   $fa = "fatal"; 
  }
  if($per < 1 || $per > 95) {
   $error = 6;
   $mess = "Шанс от 1 до 95";
   $fa = "fatal"; 
  }
  if(!is_numeric($per)) {
   $error = 7;
   $mess = "Ошибка!";
   $fa = "fatal"; 
  }
  if (!preg_match("#^[.0-9]+$#",$sum)) {
   $error = 8;
   $mess = "Недопустимые символы!";
   $fa = "fatal"; 
  }
  if (!preg_match("#^[.0-9]+$#",$per)) {
   $error = 8;
   $mess = "Недопустимые символы!";
   $fa = "fatal"; 
  }
  if(!$_SESSION['hash']) {
   $error = 9;
   $mess = "Авторизуйтесь";
   $fa = "fatal";  
  }
  
  if($error == 0) {
  if($team == "red") {
    $win_range = ($per / 100) * 999;
    if($rand > $win_range) {
       $newbalance = $balance - $sum;
     $update_sql2 = "UPDATE kot_user SET balance = '$newbalance' WHERE hash = '$sid'";
    mysql_query($update_sql2); 
     $mess = "Выпал билет $rand";
     $fa = "error"; 
    }
    if($rand <= $win_range) {
       $newbalance = ($balance + $win) - $sum;
     $update_sql2 = "UPDATE kot_user SET balance = '$newbalance' WHERE hash = '$sid'";
    mysql_query($update_sql2); 
     $fa = "success"; 
    }
  }
  
 if($team == "blue") {
   $win_range = 999 - ($per / 100) * 999;
    if($rand < $win_range) {
     $newbalance = $balance - $sum;
     $update_sql2 = "UPDATE kot_user SET balance = '$newbalance' WHERE hash = '$sid'";
    mysql_query($update_sql2); 
     $mess = "Выпал билет $rand";
     $fa = "error"; 
    }
    if($rand >= $win_range) {
       $newbalance = ($balance + $win) - $sum;
     $update_sql2 = "UPDATE kot_user SET balance = '$newbalance' WHERE hash = '$sid'";
    mysql_query($update_sql2); 
     $fa = "success"; 
    }
  }
  }
 
  $result = array(
	'success' => "$fa",
	'error' => "$mess",
    'number' => "$rand",
    'win' => "$win",
    'balance' => "$balance",
    'new_balance' => "$newbalance"
    );
}
if($type == "resetPassPanel") {
 $newpass = $_POST['newPass'];
  if (!preg_match("#^[aA-zZ0-9\-_]+$#",$newpass)) {
    $error = 1;
	$mess = "Недопустимые символы";
	$fa = "error";	
  }
  if($error == 0) {
 $update_sql2 = "UPDATE kot_user SET pass = '$newpass' WHERE hash = '$sid'";
    mysql_query($update_sql2); 
  $fa = "success";
  }
  $result = array(
	'success' => "$fa",
	'error' => "$mess"
    );
}















if($type == "deposit")
{
	
$size = $_POST['sum'];
$sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
$bala = $row['balance'];
$user_id = $row['id'];



$shop_id = '4291'; // ID кассы в fropay
$publickey = 'mw4f7yljoi596nz'; // публичный ключ в настройках кассы

$label = $user_id; // ID платежа в вашей системе
$amount = $size; // Сумма платежа
$amountnum = number_format($amount, 2, '.', ''); // перевод в формат 123.00

}
 $error=0;
$fa = "success";
  if($error == 0) {
$sign = hash('sha256', $shop_id.$amountnum.$publickey.$label); // генерация HASH
  $fa = "success";
}
    $result = array(
	'success' => "success",
	'locations' => "https://sci.fropay.bid/get?amount=".$amountnum."&desc=MTAyMTU=&shop_id=".$shop_id."&label=".$label."&hash=".$sign.""
    );
  

}



















if($type == "continue_reg") {
  $login = $_POST['login'];
  $pass = $_POST['pass'];
  $dllogin = strlen($login);
  $dlpass = strlen($pass);
  $sql_select1 = sprintf("SELECT COUNT(*) FROM kot_user WHERE login='%s'", mysql_real_escape_string($login));
$result1 = mysql_query($sql_select1);
$row = mysql_fetch_array($result1);
if($row)
{
$count = $row['COUNT(*)'];
}  
  if($login == '' || $pass == '') {
   $error = 1;
   $mess = "Заполните все поля";
   $fa = "error";
  }
  if($login != '' && $pass != '') {
  if($dllogin < 5 || $dllogin > 15) {
   $error = 2;
   $mess = "Логин от 5 до 15 симв.";
   $fa = "error";
  }
  if($dlpass < 6 || $dlpass > 12) {
   $error = 3;
   $mess = "Пароль от 6 до 12 симв.";
   $fa = "error";
  }
  if (!preg_match("#^[aA-zZ0-9\-_]+$#",$login)) {
    $error = 4;
	$mess = "Недопустимые символы";
	$fa = "error";	
  }
  if (!preg_match("#^[aA-zZ0-9\-_]+$#",$pass)) {
    $error = 5;
	$mess = "Недопустимые символы";
	$fa = "error";	
  } 
  if($count >= 1) {
    $error = 6;
	$mess = "Логин занят";
	$fa = "error";	
  }
  }
  if($error == 0) {
    $update_sql1 = "UPDATE kot_user SET login = '$login' WHERE hash = '$sid'";
    mysql_query($update_sql1);
    $update_sql2 = "UPDATE kot_user SET pass = '$pass' WHERE hash = '$sid'";
    mysql_query($update_sql2);
    $fa = "success";
  }
  $result = array(
	'success' => "$fa",
	'error' => "$mess"
    );
}
if($type == "registration") {
	

	
  $login1 = $_POST['login'];
  $pass = $_POST['pass'];
  $repeatpass = $_POST['repeatpass'];
  $dllogin = strlen($login1);
  $dlpass = strlen($pass);
  $sql_select1 = sprintf("SELECT COUNT(*) FROM kot_user WHERE login='%s'", mysql_real_escape_string($login1));
$result1 = mysql_query($sql_select1);
$row = mysql_fetch_array($result1);
if($row)
{
$count = $row['COUNT(*)'];
}
$ip_c = $_SERVER['REMOTE_ADDR'];
$sql_select11 = sprintf("SELECT COUNT(*) FROM kot_user WHERE ip='%s'", mysql_real_escape_string($ip_c));
$result11 = mysql_query($sql_select11);
$row = mysql_fetch_array($result11);
if($row)
{
$count_ip = $row['COUNT(*)'];
}
  if($login1 == '' || $pass == '' || $repeatpass == '') {
    $error = 1;
    $mess = "Заполните все поля";
    $fa = "error";
  }
  if($pass != $repeatpass) {
    $error = 2;
    $mess = "Пароли не совпадают";
    $fa = "error";
  }
  if($login1 != '' && $pass != '' && $repeatpass != '') {
  if (!preg_match("#^[aA-zZ0-9\-_]+$#",$login1)) {
    $error = 3;
	$mess = "Недопустимые символы";
	$fa = "error";	
  }
  if (!preg_match("#^[aA-zZ0-9\-_]+$#",$pass)) {
    $error = 4;
	$mess = "Недопустимые символы";
	$fa = "error";	
  } 
  if($dllogin < 4 || $dllogin > 20) {
    $error = 5;
    $mess = "Логин от 4 до 20 симв.";
    $fa = "error";
  }

   
  if($count >= 1) {
    $error = 6;
    $mess = "Логин занят";
    $fa = "error";  
  }
    if($dlpass < 6 || $dlpass > 12) {
    $error = 8;
    $mess = "Пароль от 6 до 12 симв.";
    $fa = "error";
  }
  }
  if($error == 0) {
$ip = $_SERVER["REMOTE_ADDR"];
$ref = $_SESSION["ref_id"];
$datas = date("d.m.Y");
	$datass = date("H:i:s");
	$data = "$datas $datass";
	$chars3="qazxswedcvfrtgnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP"; 
$max3=32; 
$size3=StrLen($chars3)-1; 
$hash=''; 
while($max3--) 
$hash.=$chars3[rand(0,$size3)];


	$ref_id = $_SESSION['ref_id'];
	//$ref_id = 1;
	
	$insert_sql1 = "INSERT INTO `kot_user`
		(`date_reg`, `ip`, `ref_id`, `login`, `pass`, `hash`, `balance`, `social`,
		 `credit`, `youtube`, `ava`, `img`, `bdate`, `online_time`, `startcoin`, `vk_id`, `vk_bon`, `nybonus`)
	VALUES
		('{$data}', '{$ip}', '{$ref_id}', '{$login1}', '{$pass}', '{$hash}', '0', '',
		 '0', '0', '0', '', '', '', '0', '0', '0', '0');";
mysql_query($insert_sql1);
    $_SESSION['hash'] = $hash;
    $_SESSION['login'] = 1;
    $fa = "success";
  }
  $result = array(
	'success' => "$fa",
	'error' => "$mess"
    );
}
if($type == "login") {
  $login = $_POST['login'];
  $pass = $_POST['pass'];
  $sql_select1 = sprintf("SELECT COUNT(*) FROM kot_user WHERE login='%s' AND pass='%s'", mysql_real_escape_string($login), mysql_real_escape_string($pass));
$result1 = mysql_query($sql_select1);
$row = mysql_fetch_array($result1);
if($row)
{
$count = $row['COUNT(*)'];
}  
  if($login == '' || $pass == '') {
    $error = 1;
    $mess = "Заполните все поля";
    $fa = "error";
  }
  if($login != '' && $pass != '') {
  if($count == 0) {
    $error = 2;
    $mess = "Пользователь не найден";
    $fa = "error";
  }
  if (!preg_match("#^[aA-zZ0-9\-_]+$#",$login)) {
    $error = 3;
	$mess = "Недопустимые символы";
	$fa = "error";	
} 
  if (!preg_match("#^[aA-zZ0-9\-_]+$#",$pass)) {
    $error = 4;
	$mess = "Недопустимые символы";
	$fa = "error";
} 
  }
  if($error == 0) {
    $sql_select2 = sprintf("SELECT * FROM kot_user WHERE login='%s' AND pass='%s'", mysql_real_escape_string($login), mysql_real_escape_string($pass));
$result2 = mysql_query($sql_select2);
$row = mysql_fetch_array($result2);
if($row)
{
$hash = $row['hash'];   
}
    $_SESSION['hash'] = $hash;
    $_SESSION['login'] = 1;
    $fa = "success";
  }
  $result = array(
	'success' => "$fa",
	'error' => "$mess"
    );
}
if($type == "deletewithdraw") {
  $id_delete = $_POST['del'];
$sql_select2 = "SELECT * FROM kot_user WHERE hash='$sid'";
$result2 = mysql_query($sql_select2);
$row = mysql_fetch_array($result2);
if($row)
{
$user_id = $row['id'];
$login = $row['login'];
$ban = $row['ban'];
$balance = $row['balance'];
}
$sql_select3 = "SELECT * FROM kot_withdraws WHERE id='$id_delete'";
$result3 = mysql_query($sql_select3);
$row = mysql_fetch_array($result3);
if($row)
{
$user_id_w = $row['user_id'];
$sum = $row['sum'];
$status = $row['status'];
}  
if($status != 0) {
   $error = 1;
   $mess = "";
   $fa = "error";
}
if($user_id != $user_id_w) {
   $error = 2;
   $mess = "";
   $fa = "error";
}
  if($error == 0) {
    $delete = "DELETE FROM `kot_withdraws` WHERE id = '$id_delete'";
mysql_query($delete);
  $newbalance = $balance + $sum;
    $update_sql1 = "UPDATE kot_user SET balance = '$newbalance' WHERE hash = '$sid'";
    mysql_query($update_sql1);
    $fa = "success";
  }
  $result = array(
	'success' => "$fa",
	'error' => "$mess",
	'balance' => "$balance",
	'new_balance' => "$newbalance"
    );
}




if($type == "withdrawuser") {

$sql_select2 = "SELECT * FROM kot_user WHERE hash='$sid'";
$result2 = mysql_query($sql_select2);
$row = mysql_fetch_array($result2);
if($row)
{
$user_id = $row['id'];
$login = $row['login'];
$ban = $row['ban'];
$balance = $row['balance'];
}
$sql_select23 = "SELECT SUM(suma) FROM kot_payments WHERE user_id='$user_id'";
$result23 = mysql_query($sql_select23);
$row = mysql_fetch_array($result23);
if($row)
{
$sumdep = $row['SUM(suma)'];
}
if($sumdep == '') {
 $sumdep = 0; 
}
$system = $_POST['system'];
$wallet = $_POST['wallet'];
$sum = $_POST['sum'];
  if($system == 4) {
      $ps = "qiwi";
    }
    
      if($system == '') {
      $ps = "qiwi";
    } 
    if($system == 2) {
      $ps = "payeer";
    }
    if($system == 3) {
      $ps = "wm";
    }
    if($system == 1) {
      $ps = "ya";
    }
    if($system == 5) {
      $ps = "beeline";
    }
    if($system == 6) {
      $ps = "megafon";
    }
    if($system == 7) {
      $ps = "mts";
    }
    if($system == 8) {
      $ps = "tele";
    }
    if($system == 9) {
      $ps = "visa";
    }
    if($system == 10) {
      $ps = "pias";
    }
$dwallet = strlen($wallet);
if($wallet == '' || $sum == '') {
  $error = 1;
  $mess = "Выберите платежную систему";
  $fa = "error";
}
if($sum > $balance) {
  $error = 2;
  $mess = "Недостаточно средств";
  $fa = "error";
}
if($ban == 1) {
  $error = 3;
  $mess = "Ваш аккаунт заблокирован";
  $fa = "error";
}
$wgu = mysql_fetch_array(mysql_query("SELECT wager_required, wager_progress FROM kot_user WHERE id='$user_id'"));
if($wgu && (float)$wgu['wager_required'] > (float)$wgu['wager_progress']) {
  $error = 9;
  $mess = "Сначала отыграйте вагер: осталось поставить " . round((float)$wgu['wager_required'] - (float)$wgu['wager_progress'], 2) . " ₽";
  $fa = "error";
}
  if($sum != '' && $wallet != '') {
if(!is_numeric($sum)) {
  $error = 4;
  $mess = "Введите корректную сумму";
  $fa = "error";
}
if($dwallet < 8 || $dwallet > 20) {
  $error = 5;
  $mess = "Кошелек от 8 до 20 символов";
  $fa = "error";
}
  
if($sum < $min_withdraw_sum) {
  $error = 6;
  $mess = "Минимальная сумма вывода $min_withdraw_sum";
  $fa = "error";
}
if (!preg_match("#^[aA-zZ0-9\-_.]+$#",$sum)) 
{
	$mess = "Недопустимые символы в сумме";
	$fa = "error";
	$error = 7;
} 
if (!preg_match("#^[+0-9PpWw]+$#",$wallet)) 
{
	$mess = "Недопустимые символы в реквизитах";
	$fa = "error";
	$error = 8;
}
if($sumdep < $dep_withdraw) {
    $mess = "Пополните баланс на $dep_withdraw";
    $error = 9;
	$fa = "error";
	
  }
  }
  if($error == 0) {
    $summ = round($sum, 2);
    $newbalance = $balance - $sum;
    $datas = date("d.m.Y");
	$datass = date("H:i:s");
	$data = "$datas $datass";
    $insert_sql11 = "INSERT INTO `kot_withdraws` (`id`, `user_id`, `ps`, `wallet`, `sum`, `date`, `status`) VALUES (NULL, '$user_id', '$ps', '$wallet', '$summ', '$data', '0');";
    mysql_query($insert_sql11); 
    $update_sql1 = "UPDATE kot_user SET balance = '$newbalance' WHERE hash = '$sid'";
    mysql_query($update_sql1);
    $fa = "success";
}
  $result = array(
	'success' => "$fa",
	'error' => "$mess",
	'balance' => "$balance",
	'new_balance' => "$newbalance"
    );
}
if($type == "createPromoUser") {

$sql_select2 = "SELECT * FROM kot_user WHERE hash='$sid'";
$result2 = mysql_query($sql_select2);
$row = mysql_fetch_array($result2);
if($row)
{
    $login = $row['login'];
$user_id = $row['id'];
$ban = $row['ban'];
$balance = $row['balance'];
}
$name = $_POST['createname'];
$sum = $_POST['createsum'];
$act = $_POST['createactive'];
$sql_select4 = sprintf("SELECT COUNT(*) FROM kot_promo WHERE name='%s'", mysql_real_escape_string($name));
$result4 = mysql_query($sql_select4);
$row = mysql_fetch_array($result4);

if($row)
{
$count = $row['COUNT(*)'];
}
$sql_select23 = "SELECT SUM(suma) FROM kot_payments WHERE user_id='$user_id'";
$result23 = mysql_query($sql_select23);
$row = mysql_fetch_array($result23);
if($row)
{
$sumdep = $row['SUM(suma)'];
}
if($sumdep == '') {
 $sumdep = 0; 
}
if($sumdep < 40 && $adm == 0 ){
  $error = 1;
  $mess = "Для создания промо пополните баланс на 40₽";
  $fa = "error";
}
if($name == '' || $sum == '' || $act == '') {
  $error = 1;
  $mess = "Заполните все поля";
  $fa = "error";
}
if($ban == 1) {
  $error = 2;
  $mess = "Ваш аккаунт заблокирован";
  $fa = "error";
}
if(($sum * $act) > $balance) {
  $error = 3;
  $mess = "Недостаточно средств";
  $fa = "error";
}
  if($name != '' && $sum != '' && $act != '') {
  if($sum < 1) {
  $error = 4;
  $mess = "Сумма от 1";
  $fa = "error";
}
  if($act < 1) {
  $error = 5;
  $mess = "Кол-во от 1";
  $fa = "error";
}
  if(!is_numeric($sum)) {
  $error = 6;
  $mess = "Сумма цифрами";
  $fa = "error";
}
  if(!is_numeric($act)) {
  $error = 7;
  $mess = "Кол-во цифрами";
  $fa = "error";
}
  if (!preg_match("#^[а-яА-ЯaA-zZ0-9\-_]+$#",$name)) {
   $error = 8;
   $mess = "Недопустимые символы";
   $fa = "error";
}   
  if($count > 0) {
  $error = 9;
  $mess = "Промокод уже существует";
  $fa = "error";
}  
}
  if($error == 0) {
    $datas = date("d.m.Y");
	$datass = date("H:i:s");
	$data = "$datas $datass";
  $newbalance = $balance - ($sum * $act);
  $insert_sql1 = "INSERT INTO `kot_promo` (`id`, `date`, `name`, `sum`, `active`, `actived`, `id_active`, `login`) VALUES (NULL, '$data', '$name', '$sum', '$act', '0', '', '$login');";
    mysql_query($insert_sql1);
    $update_sql1 = "UPDATE kot_user SET balance = '$newbalance' WHERE hash = '$sid'";
    mysql_query($update_sql1);
    $fa = "success";
}
$result = array(
	'success' => "$fa",
	'error' => "$mess",
	'balance' => "$balance",
	'new_balance' => "$newbalance"
    );
}
if($type == "activePromo") {
    $fa = "error"; $mess = ""; $balance = 0; $newbalance = 0;
    $u = mysql_fetch_array(mysql_query("SELECT * FROM kot_user WHERE hash='" . mysql_real_escape_string($sid) . "' LIMIT 1"));
    $code = mysql_real_escape_string(trim((string)($_POST['promoactive'] ?? '')));
    if (!$u) $mess = "Авторизуйтесь";
    elseif ($code === '') $mess = "Введите промокод";
    elseif ((int)$u['ban'] === 1) $mess = "Ваш аккаунт заблокирован";
    else {
        $uid = (int)$u['id'];
        $balance = $u['balance'];
        $p = mysql_fetch_array(mysql_query("SELECT * FROM kot_promo WHERE name='$code' LIMIT 1"));
        if (!$p) $mess = "Промокод не найден";
        else {
            $pid = (int)$p['id'];
            $limit = (int)$p['active'];
            $used = mysql_fetch_array(mysql_query("SELECT COUNT(*) c FROM kot_promo_act WHERE user_id=$uid AND promo_id=$pid"));
            $day = mysql_fetch_array(mysql_query("SELECT COUNT(*) c FROM kot_promo_act WHERE user_id=$uid AND created_at > NOW() - INTERVAL 1 DAY"));
            $exp = trim((string)$p['expires_at']);
            if ($exp !== '' && strtotime($exp . ' 23:59:59') < time()) $mess = "Срок действия промокода истёк";
            elseif ($limit > 0 && (int)$p['actived'] >= $limit) $mess = "Активации закончились";
            elseif ((int)$used['c'] > 0 || preg_match("/(^| )$uid /", (string)$p['id_active'])) $mess = "Вы уже активировали этот код";
            elseif ((int)$day['c'] >= 3) $mess = "Можно активировать не больше 3 промокодов за 24 часа";
            elseif (($p['promo_type'] ?? '') === 'deposit' && (float)$p['min_dep'] > 0 && !mysql_fetch_array(mysql_query("SELECT id FROM kot_payments WHERE user_id=$uid AND CAST(suma AS DECIMAL(12,2)) >= " . (float)$p['min_dep'] . " AND COALESCE(STR_TO_DATE(data, '%d.%m.%Y %H:%i:%s'), STR_TO_DATE(data, '%d.%m.%Y %H:%i')) > NOW() - INTERVAL 1 DAY LIMIT 1"))) $mess = "Этот промокод работает после пополнения от " . (float)$p['min_dep'] . " ₽ за последние 24 часа";
            else {
                $minA = (float)$p['min_amount']; $maxA = (float)$p['max_amount'];
                $amount = ($minA > 0 && $maxA >= $minA) ? random_int((int)round($minA * 100), (int)round($maxA * 100)) / 100 : round((float)$p['sum'], 2);
                $wager = round($amount * max(1, (int)$p['wager_mult']), 2);
                mysql_query("UPDATE kot_promo SET actived = actived + 1, id_active = CONCAT('$uid ', id_active) WHERE id=$pid AND (CAST(active AS SIGNED) = 0 OR CAST(actived AS SIGNED) < CAST(active AS SIGNED))");
                if (mysql_affected_rows() < 1) $mess = "Активации закончились";
                else {
                    mysql_query("UPDATE kot_user SET balance = balance + '$amount', wager_required = wager_required + '$wager' WHERE id=$uid");
                    mysql_query("INSERT INTO kot_promo_act (user_id, promo_id, code, amount, wager, status, created_at) VALUES ($uid, $pid, '$code', '$amount', '$wager', 'active', NOW())");
                    $newbalance = round((float)$balance + $amount, 2);
                    $fa = "success";
                    $mess = "Промокод активирован: +$amount ₽, вагер $wager ₽";
                }
            }
        }
    }
    $result = array(
        'success' => "$fa",
        'error' => "$mess",
        'balance' => "$balance",
        'new_balance' => "$newbalance"
    );
}
if($type == "bonus")	
{
    $datab = Date("dmY");
$min_bonus_size = $min_bonus_s * 100;
$max_bonus_size = $max_bonus_s * 100;
	
$sql_select = "SELECT * FROM kot_user WHERE hash='$sid'";
$result = mysql_query($sql_select);
$row = mysql_fetch_array($result);
if($row)
{	
$bonus = $row['bdate'];
$balance = $row['balance'];
$vk = $row['social'];
}

if($bonus == $datab){
$error = 1;
$fa = "error";
$mess = "Вы уже получали ежедневный бонус! Возвращайтесь через 24 часа.";
}

if($vk != '') {
    $user = explode( 'vk.com', $vk )[1];
$http = "http://";
$vks = str_replace($user, '', $vk);
$vks = str_replace($http, '', $vks);
if($vks == "vk.com" || $vks == "m.vk.com")
{
	//good
		$domainvk = explode( 'http://vk.com/id', $vk )[1];
if (!is_numeric($domainvk))
{
	$domainvk = explode( 'com/', $vk )[1];
}

		$vk1 = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$domainvk}&access_token=".$tokenvk."&v=5.74"));
        $vk1 = $vk1->response[0]->id;
	$resp = file_get_contents("https://api.vk.com/method/groups.isMember?group_id=".$idvk."&user_id={$vk1}&access_token=".$tokenvk."&v=5.74");
$data = json_decode($resp, true);
    

if($data['response']=='0')
{
$error = 3;
$fa = "error";
$mess = "Подпишитесь на группу";    
}
if($data['response']=='1')
{
}
}
}
if($error == 0) {
    $randomb = rand($min_bonus_size,$max_bonus_size) / 100;
    $balancenew = $balance + $randomb;
$update_sql = "Update kot_user set balance='$balancenew' WHERE hash='$sid'";
      mysql_query($update_sql) or die("Ошибка вставки" . mysql_error());
$update_sql1 = "Update kot_user set bdate='$datab' WHERE hash='$sid'";
      mysql_query($update_sql1) or die("Ошибка вставки" . mysql_error());
$fa = "success";

}
$result = array(
	'success' => "$fa",
	'error' => "$mess",
	'balance' => "$balance",
	'new_balance' => "$balancenew",
	'bonussize' => "$randomb"
    );
}

if($type == "exit") {
 unset($_SESSION['hash']); 
 unset($_SESSION['login']);
  $fa = "success";
  $result = array(
	'success' => "$fa",
	'error' => "$mess"
    );
}
if($type == "minbet") {
 // $winsum = $_POST['win'];
 
    
  $sum = $_POST['sum'];
  $per = $_POST['per'];
  $nwin = ($per * 10000) - 1;
  $winsum = round(((100 / $per * $sum * rb_rtp('dice')) - $sum), 2);
  //$nwin = $_POST['nwin'];
  $sql_select2 = "SELECT * FROM kot_user WHERE hash='$sid'";
$result2 = mysql_query($sql_select2);
$row = mysql_fetch_array($result2);
if($row)
{	
$balance = $row['balance'];
$ban = $row['ban'];
$sliv = $row['sliv'];
$fart = $row['win'];
$login = $row['login'];
$user_id = $row['id'];
}
  $rand = random_int(0, 999999);
  if(bwForceWin($user_id, 'dice')) $rand = random_int(0, max(0, $nwin));
  $hash = hash('sha512', $rand);
  if((empty($_SESSION['hash'])) || $_SESSION['login'] != 1){
    $error = 2;
    $mess = "Авторизуйтесь";
    $fa = "error";
  }
     if($_SESSION['hash'] != '') {
       if($sum > $balance) {
         $newbalance = $balance;
         $error = 4;
         $mess = "Недостаточно средств";
         $fa = "error";
       }
       if($per > $max_per || $per < $min_per) {
         $newbalance = $balance;
         $error = 98;
         $mess = "% Шанс от $min_per до $max_per";
         $fa = "error";
       }
       if($ban == 1) {
         $newbalance = $balance;
         $error = 97;
         $mess = "Ваш аккаунт заблокирован";
         $fa = "error";
       }
       if($sum < $min_bet) {
         $newbalance = $balance;
         $error = 64;
         $mess = "Ставки от $min_bet";
         $fa = "error";
       }
       if($sum > $max_bet) {
         $newbalance = $balance;
         $error = 69;
         $mess = "Макс. ставка $max_bet";
         $fa = "error";
       }
       if(rb_over_limit($winsum)) {
         $newbalance = $balance;
         $error = 70;
         $mess = "Макс. выигрыш за ставку " . rb_max_win();
         $fa = "error";
       }
       if(!is_numeric($sum)) {
           $newbalance = $balance;
         $error = 77;
         $mess = "Введите сумму корректно";
         $fa = "error";
          
       }
       if($error == 0) {
  if($rand <= $nwin)
  {
    $summ = round($sum, 2);
    $winsumm = round($winsum, 2) + $sum;
  $insert_sql1 = "INSERT INTO `kot_games` (`game`,`user_id`, `login`, `number`, `cel`, `sum`, `chance`, `type`, `win_summa`) 
	VALUES ('1', '$user_id', '$login', '$rand', '0 - $nwin', '$summ', '$per', 'win', '$winsumm');";
mysql_query($insert_sql1);
bwTrackBet($user_id, 'dice', $summ, $winsumm);
  $newbalance = $balance + $winsum;
    $update_sql4 = "Update kot_user set balance='$newbalance' WHERE hash='$sid'";
      mysql_query($update_sql4);
  $fa = "success";
  }
       }
       
       if($error == 0) {
  if($rand > $nwin)
  {
    $summ = round($sum, 2);
    $winsumm = round($winsum, 2) + $sum;
  $insert_sql1 = "INSERT INTO `kot_games` (`game`,`user_id`, `login`, `number`, `cel`, `sum`, `chance`, `type`, `win_summa`) 
	VALUES ('1', '$user_id', '$login', '$rand', '0 - $nwin', '$summ', '$per', 'lose', '0');";
mysql_query($insert_sql1);
bwTrackBet($user_id, 'dice', $summ, 0);
  $newbalance = $balance - $sum;
    $update_sql4 = "Update kot_user set balance='$newbalance' WHERE hash='$sid'";
      mysql_query($update_sql4);
  $error = 1;
  $mess = "😤 Выпало $rand";
  $fa = "error";
  }
   }  
     }
     
  $winning = $winsum + $sum;
  $result = array(
	'success' => "$fa",
	'error' => "$mess",
	'number' => "$rand",
    'hash' => "$hash",
    'fullwin' => "$winning",
    'balance' => "$balance",
    'new_balance' => "$newbalance"

    );
}
if($type == "maxbet") {
  // $winsum = $_POST['win'];
  $per = $_POST['per'];
  $nwin = 1000000 - ($per * 10000);
  //$nwin = $_POST['nwin'];
  $sum = $_POST['sum'];
  $winsum = round(((100 / $per * $sum * rb_rtp('dice')) - $sum), 2);
  $sql_select2 = "SELECT * FROM kot_user WHERE hash='$sid'";
$result2 = mysql_query($sql_select2);
$row = mysql_fetch_array($result2);
if($row)
{
$balance = $row['balance'];
$ban = $row['ban'];
$sliv = $row['sliv'];
$fart = $row['win'];
$login = $row['login'];
$user_id = $row['id'];
}
 $rand = random_int(0, 999999);
  if(bwForceWin($user_id, 'dice')) $rand = random_int(min(999999, $nwin), 999999);
  $hash = hash('sha512', $rand);
  if((empty($_SESSION['hash'])) || $_SESSION['login'] != 1){
    $error = 2;
    $mess = "Авторизуйтесь";
    $fa = "error";
  }
  
     if($_SESSION['hash'] != '') {
       if($sum > $balance) {
         $newbalance = $balance;
         $error = 4;
         $mess = "Недостаточно средств";
         $fa = "error";
       }
       if($per > $max_per || $per < $min_per) {
         $newbalance = $balance;
         $error = 98;
         $mess = "% Шанс от $min_per до $max_per";
         $fa = "error";
       }
       if($ban == 1) {
         $newbalance = $balance;
         $error = 97;
         $mess = "Ваш аккаунт заблокирован";
         $fa = "error";
       }
       if($sum < $min_bet) {
         $newbalance = $balance;
         $error = 64;
         $mess = "Ставки от $min_bet";
         $fa = "error";
       }
       if($sum > $max_bet) {
         $newbalance = $balance;
         $error = 69;
         $mess = "Макс. ставка $max_bet";
         $fa = "error";
       }
       if(rb_over_limit($winsum)) {
         $newbalance = $balance;
         $error = 70;
         $mess = "Макс. выигрыш за ставку " . rb_max_win();
         $fa = "error";
       }
       if(!is_numeric($sum)) {
           $newbalance = $balance;
         $error = 77;
         $mess = "Введите сумму корректно";
         $fa = "error";
          
       }
       if($error == 0) {
  if($rand >= $nwin)
  {
    $summ = round($sum, 2);
    $winsumm = round($winsum, 2) + $sum;
  $insert_sql1 = "INSERT INTO `kot_games` (`game`,`user_id`, `login`, `number`, `cel`, `sum`, `chance`, `type`, `win_summa`) 
	VALUES ('1', '$user_id', '$login', '$rand', '$nwin - 999999', '$summ', '$per', 'win', '$winsumm');";
mysql_query($insert_sql1);
bwTrackBet($user_id, 'dice', $summ, $winsumm);
  $newbalance = $balance + $winsum;
    $update_sql4 = "Update kot_user set balance='$newbalance' WHERE hash='$sid'";
      mysql_query($update_sql4);
  $fa = "success";
  }
       } 
     }
    
       
       if($error == 0) {
  if($rand < $nwin)
  {
    $summ = round($sum, 2);
    $winsumm = round($winsum, 2) + $sum;
  $insert_sql1 = "INSERT INTO `kot_games` (`game`,`user_id`, `login`, `number`, `cel`, `sum`, `chance`, `type`, `win_summa`) 
	VALUES ('1', '$user_id', '$login', '$rand', '$nwin - 999999', '$summ', '$per', 'lose', '0');";
mysql_query($insert_sql1);
bwTrackBet($user_id, 'dice', $summ, 0);
  $newbalance = $balance - $sum;
    $update_sql4 = "Update kot_user set balance='$newbalance' WHERE hash='$sid'";
      mysql_query($update_sql4);
  $error = 1;
  $mess = "Выпало $rand";
  $fa = "error";
  }
       }
     
     
  $winning = $winsum + $sum;
  $result = array(
	'success' => "$fa",
	'error' => "$mess",
	'number' => "$rand",
    'hash' => "$hash",
    'fullwin' => "$winning",
    'balance' => "$balance",
    'new_balance' => "$newbalance"

    );
}

// ===================== ТИКЕТЫ =====================

if ($type == "createTicket") {
  if (!$sid) { echo json_encode(['success'=>'error','error'=>'Не авторизован']); exit; }
  $subject = trim(mysql_real_escape_string($_POST['subject'] ?? ''));
  $message = trim(mysql_real_escape_string($_POST['message'] ?? ''));
  if (strlen($subject) < 3) { echo json_encode(['success'=>'error','error'=>'Тема слишком короткая']); exit; }
  if (strlen($message) < 5) { echo json_encode(['success'=>'error','error'=>'Опишите проблему подробнее']); exit; }
  mysql_query("INSERT INTO kot_tickets (user_id, subject, status, created_at, updated_at) VALUES ('$id', '$subject', 'open', NOW(), NOW())");
  $tid = mysql_insert_id();
  mysql_query("INSERT INTO kot_ticket_msgs (ticket_id, user_id, is_admin, message, created_at) VALUES ('$tid', '$id', 0, '$message', NOW())");
  echo json_encode(['success'=>'success','ticket_id'=>$tid]); exit;
}

if ($type == "sendTicketMsg") {
  if (!$sid) { echo json_encode(['success'=>'error','error'=>'Не авторизован']); exit; }
  $tid = (int)($_POST['ticket_id'] ?? 0);
  $msg = trim(mysql_real_escape_string($_POST['message'] ?? ''));
  if (!$tid || strlen($msg) < 1) { echo json_encode(['success'=>'error','error'=>'Пустое сообщение']); exit; }
  $trow = mysql_fetch_array(mysql_query("SELECT * FROM kot_tickets WHERE id='$tid' AND user_id='$id'"));
  if (!$trow) { echo json_encode(['success'=>'error','error'=>'Тикет не найден']); exit; }
  mysql_query("INSERT INTO kot_ticket_msgs (ticket_id, user_id, is_admin, message, created_at) VALUES ('$tid', '$id', 0, '$msg', NOW())");
  mysql_query("UPDATE kot_tickets SET status='open', updated_at=NOW() WHERE id='$tid'");
  echo json_encode(['success'=>'success']); exit;
}

if ($type == "closeTicketUser") {
  if (!$sid) { echo json_encode(['success'=>'error']); exit; }
  $tid = (int)($_POST['ticket_id'] ?? 0);
  mysql_query("UPDATE kot_tickets SET status='closed', updated_at=NOW() WHERE id='$tid' AND user_id='$id'");
  echo json_encode(['success'=>'success']); exit;
}

if ($type == "adminGetTickets") {
  if (!$sid || $admin != 1) { echo json_encode(['success'=>'error']); exit; }
  $filter = mysql_real_escape_string($_POST['filter'] ?? 'open');
  if (!in_array($filter, ['open','answered','closed','all'])) $filter = 'open';
  $where = $filter === 'all' ? '' : "WHERE t.status='$filter'";
  $res = mysql_query("SELECT t.*, u.login FROM kot_tickets t JOIN kot_user u ON u.id=t.user_id $where ORDER BY t.updated_at DESC LIMIT 100");
  $rows = [];
  while ($r = mysql_fetch_assoc($res)) $rows[] = $r;
  echo json_encode(['success'=>'success','tickets'=>$rows]); exit;
}

if ($type == "adminReplyTicket") {
  if (!$sid || $admin != 1) { echo json_encode(['success'=>'error']); exit; }
  $tid = (int)($_POST['ticket_id'] ?? 0);
  $msg = trim(mysql_real_escape_string($_POST['message'] ?? ''));
  if (!$tid || strlen($msg) < 1) { echo json_encode(['success'=>'error','error'=>'Пустое сообщение']); exit; }
  mysql_query("INSERT INTO kot_ticket_msgs (ticket_id, user_id, is_admin, message, created_at) VALUES ('$tid', '$id', 1, '$msg', NOW())");
  mysql_query("UPDATE kot_tickets SET status='answered', updated_at=NOW() WHERE id='$tid'");
  echo json_encode(['success'=>'success']); exit;
}

if ($type == "adminCloseTicket") {
  if (!$sid || $admin != 1) { echo json_encode(['success'=>'error']); exit; }
  $tid = (int)($_POST['ticket_id'] ?? 0);
  mysql_query("UPDATE kot_tickets SET status='closed', updated_at=NOW() WHERE id='$tid'");
  echo json_encode(['success'=>'success']); exit;
}

if ($type == "getTicketMsgs") {
  if (!$sid) { echo json_encode(['success'=>'error']); exit; }
  $tid = (int)($_POST['ticket_id'] ?? 0);
  $trow = mysql_fetch_array(mysql_query("SELECT * FROM kot_tickets WHERE id='$tid'"));
  if (!$trow || ($trow['user_id'] != $id && $admin != 1)) { echo json_encode(['success'=>'error']); exit; }
  $res  = mysql_query("SELECT m.*, u.login FROM kot_ticket_msgs m JOIN kot_user u ON u.id=m.user_id WHERE m.ticket_id='$tid' ORDER BY m.created_at ASC");
  $msgs = [];
  while ($r = mysql_fetch_assoc($res)) $msgs[] = $r;
  echo json_encode(['success'=>'success','ticket'=>$trow,'messages'=>$msgs]); exit;
}

// ===================== END ТИКЕТЫ =====================

	
if ($type == "setAva") {
  if (!$sid) { echo json_encode(['success'=>'error']); exit; }
  $imgData = $_POST['img'] ?? '';
  if (!$imgData) { echo json_encode(['success'=>'error','error'=>'Нет изображения']); exit; }
  // Strip data:image/...;base64, prefix
  $imgData = preg_replace('/^data:image\/[a-z]+;base64,/', '', $imgData);
  $decoded = base64_decode($imgData);
  if (!$decoded) { echo json_encode(['success'=>'error','error'=>'Ошибка декодирования']); exit; }
  $dir = __DIR__ . '/extra/avatars/';
  if (!is_dir($dir)) mkdir($dir, 0755, true);
  $path = $dir . $id . '.jpg';
  file_put_contents($path, $decoded);
  mysql_query("UPDATE kot_user SET ava=1 WHERE id='$id'");
  echo json_encode(['success'=>'success','url'=>'/extra/avatars/'.$id.'.jpg']); exit;
}

if ($type != "cardWithdraw" and $type != "cardCoin" and $type != "cardWheel" and $type != "getMinesRate" and $type != "cardDice" and $type != "cardRefereal" and $type != "cardMines"){
    echo json_encode($result);
	}
?>