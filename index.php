<?php
	define('ADMLOGIN','admin');
	define('ADMPASS','admin');
	define('MIKROTIKIP','192.168.88.1');
	define('MIKROTIKLOGIN','admin');
	define('MIKROTIKPASS','admin');
	define('ALLOWLISTADRES','dostup');
	define('CLIENTSCOMMENTPREFIX','client');
	define('ETHERNETINTERFACE','2_balkon');
	define('DHCPSERVER','Local');
	define('IPRANGE','192.168.88.');
	define('IPSERVER','192.168.88.1');
	define('DEFAULTBONUS','50');
	define('DEFAULTLIMIT','10M/10M');
	define('DEFAULTDATE', '1988-07-13');
	define('DEFAULTFREEIP', '192.168.88.0');
	define('DEFAULTMAC', 'DD:DD:DD:DD:DD:DD');
	define('DEFAULTINFO', 'new');
	define('HTML_FIRST','<!DOCTYPE HTML><html><head><meta charset="windows-1251"><title>Mikrotik Billing System (made by d0m1nat0r)</title><script type="text/javascript" src="main.js"></script><script type="text/javascript" src="jquery-2.0.1.min.js"></script><link rel="stylesheet" href="main.css"></head><body><center>');
	define('HTML_END','</center></body></html>');
	define('HTML_MENU','<div><a href="?log">Історія</a> | <a href="?control">Контроль</a> | <a href="?add">Додати</a> | <a href="?report">Активність</a> (<a href="?reportClear" style="color:red">очистити</a>) | <a href="?logout">Вихід</a></div><hr>');
	session_start();
	set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
	include('Net/SSH2.php');
	function sendCommand($command){
		$mikrotik = new Net_SSH2(MIKROTIKIP);
		$mikrotik->login(MIKROTIKLOGIN,MIKROTIKPASS);
		$result = $mikrotik->exec($command);
		return $result;
	};
	function getIdOnAddressList($ip) {
		$result = sendCommand('/ip firewall address-list print where address='.$ip.' list='.ALLOWLISTADRES);
		$parse = explode('                        ', $result);
		$parseVal = explode(';;;', $parse[2]);
		$parseVal = trim($parseVal[0]);
		$parseVal = explode(' ', $parseVal);
		return $parseVal[0];
	};
	if (isset($_SESSION['login']) && isset($_SESSION['pass'])) {
		if ($_SESSION['login'] == ADMLOGIN && $_SESSION['pass'] == ADMPASS) {
			if (count($_GET) == 0 && count($_POST) == 0) {
				header('Location: ?log');
			};
            if (isset($_POST['remove'])){
                $id = getIdOnAddressList($_POST['remove']);
                $result = sendCommand('/ip firewall address-list remove numbers='.$id);
                $result = sendCommand('/ip dhcp-server lease print where address="'.$_POST['remove'].'"');
                $parse = explode('STATUS', $result);
                $parse = trim($parse[1]);
                $id = explode(' ', $parse);
                $id = $id[0];
                $result = sendCommand('/ip dhcp-server lease remove numbers='.$id);
                $result = sendCommand('/ip arp print where address="'.$_POST['remove'].'"');
                $parse = explode('INTERFACE', $result);
                $parse = trim($parse[1]);
                $id = explode(' ', $parse);
                $id = $id[0];
                $result = sendCommand('/ip arp remove numbers='.$id);
                $result = sendCommand('/queue simple remove numbers="'.$_POST['remove'].'"');
                echo $result;
            };
            if (isset($_POST['disable'])){
                $id = getIdOnAddressList($_POST['disable']);
                $result = sendCommand('/ip firewall address-list disable numbers='.$id);
                echo $result;
            };
	    if (isset($_POST['enable'])){
                $id = getIdOnAddressList($_POST['enable']);
                $result = sendCommand('/ip firewall address-list enable numbers='.$id);
                echo $result;
            };
	    if (isset($_POST['editComment']) && isset($_POST['ip'])){
                $id = getIdOnAddressList($_POST['ip']);
                $result = sendCommand('/ip firewall address-list comment numbers='.$id.' comment="'.$_POST['editComment'].'"');
                echo $result;
            };
            if (isset($_POST['maxLimit']) && isset($_POST['ip'])){
                $result = sendCommand('/queue simple set numbers="'.$_POST['ip'].'" max-limit="'.$_POST['maxLimit'].'"');
                echo $result;
            };
            if (isset($_POST['add']) && isset($_POST['mac']) && isset($_POST['limit']) && isset($_POST['ip']) && isset($_POST['comment'])){
                if ($_POST['add'] == 'client') {
                   $comment = CLIENTSCOMMENTPREFIX.'|'.$_POST['comment'];
                } else {
                   $comment = $_POST['comment'];
                };
                $result = sendCommand('/ip arp print where address="'.$_POST['ip'].'"');
                $parse = explode('INTERFACE', $result);
                $parse = trim($parse[1]);
                $id = explode(' ', $parse);
                $id = $id[0];
                $result = sendCommand('/ip arp remove numbers='.$id);
                $result = sendCommand('/ip arp add address="'.$_POST['ip'].'" mac-address="'.$_POST['mac'].'" interface="'.ETHERNETINTERFACE.'"');
                $result = sendCommand('/ip dhcp-server lease add address="'.$_POST['ip'].'" mac-address="'.$_POST['mac'].'" server="'.DHCPSERVER.'" always-broadcast="yes"');
                $result = sendCommand('/queue simple add name="'.$_POST['ip'].'" target-addresses="'.$_POST['ip'].'" max-limit="'.$_POST['limit'].'"');
                $result = sendCommand('/ip firewall address-list add address="'.$_POST['ip'].'" list="'.ALLOWLISTADRES.'" comment="'.$comment.'"');
                echo $result;
            };
            if (isset($_POST['editMac']) && isset($_POST['ip'])){
                $result = sendCommand('/ip dhcp-server lease print where address="'.$_POST['ip'].'"');
                $parse = explode('STATUS', $result);
                $parse = trim($parse[1]);
                $id = explode(' ', $parse);
                $id = $id[0];
                $result = sendCommand('/ip dhcp-server lease set numbers='.$id.' mac-address="'.$_POST['editMac'].'"');
                echo $result;
                $result = sendCommand('/ip arp print where address="'.$_POST['ip'].'"');
                $parse = explode('INTERFACE', $result);
                $parse = trim($parse[1]);
                $id = explode(' ', $parse);
                $id = $id[0];
                $result = sendCommand('/ip arp set numbers='.$id.' mac-address="'.$_POST['editMac'].'"');
                echo $result;
            };
            if (isset($_GET['logout'])){
                session_destroy();
                header('Location: index.php');
            };
			if (isset($_GET['log'])){
				$result = sendCommand('/log print detail');
				$html = HTML_FIRST.HTML_MENU.'<div id="log"><h3>Історія команд:</h3>';
				$parse = explode('"', $result);
				$i=0;
				while($i<count($parse)-2) {
					$html .= '<p>'.$parse[$i].'"'.$parse[$i+1].'"</p>';
					$i += 2;
				};
				$html .= '</div>'.HTML_END;
				print($html);
			};
            if (isset($_GET['add'])){
                $htmlStatic = '<div id="staticArp"><h4>Список вільних адрес:</h4><p>';
                $htmlNew = '<div id="newDhcp"><h4>Список невідомих:</h4>';
                $result = sendCommand('/ip arp print detail');
                $result = explode('mac-address=', $result);
                $htmlStaticArr = Array();
                for($i=1; $i<count($result); $i++) {
                    $parse = explode('address=', $result[$i-1]);
                    $parseStatus = trim($parse[count($parse)-2]);
                    $valid = explode(' ', $parseStatus);
                    $valid = $valid[count($valid)-1];
                    $valid = trim($valid);
                    if (is_numeric($valid)) {
                        array_push($htmlStaticArr, $parse[count($parse)-1]);
                    } else {
                        if ($valid == 'H') {
                            $mac = explode(' ',$result[$i]);
                            $mac = trim($mac[0]);
                            $htmlNew .= '<p>'.$parse[count($parse)-1].'= '.$mac.'</p>';
                        }
                    }
                };
                rsort($htmlStaticArr);
                for($j=254; $j>0; $j--){
                    if(IPRANGE.$j != IPSERVER) {
                        $find = false;
                        for($i=0; $i<count($htmlStaticArr); $i++) {
                            $parse = explode('.',$htmlStaticArr[$i]);
                            $parse = (int) $parse[3];
                            if ($j == $parse) {
                                $find = true;
                            }
                        };
                        if (!$find) {
                           $htmlStatic .= IPRANGE.$j.' ';
                        }
                    }
                };
                $htmlStatic .= '</p></div>';
                $htmlNew .= '</div>';
                $html = HTML_FIRST.HTML_MENU;
                $html .= $htmlNew;
                $html .= '<div id="addForm"><fieldset><legend> Добавлення нового: </legend><label>Група:<select><option value="client" selected>Абонементи</option><option value="other">Інші</option></select></label><label>IP:<input type="text" value="'.DEFAULTFREEIP.'"></label><label>MAC:<input type="text" value="'.DEFAULTMAC.'"></label><label>Швидкість:<input type="text" value="'.DEFAULTLIMIT.'"></label><label>Інформація (латинськими):<input type="text" value="'.DEFAULTINFO.'"></label><label>Бонуси:<input type="number" value="'.DEFAULTBONUS.'"></label><label>Дата отримання бонусів:<input type="date" value="'.DEFAULTDATE.'"></label><label>Дата закінчення бонусів:<input type="date" value="'.DEFAULTDATE.'"></label><input type="button" value="Добавити"></fieldset></div>';
                $html .= $htmlStatic.HTML_END;
                print($html);
            };
			if (isset($_GET['control'])){
				$result = sendCommand('/ip firewall address-list print where list='.ALLOWLISTADRES);
				$parse = explode('                 ', $result);
				$html = HTML_FIRST.HTML_MENU;
				$elementsArr = array();
				$i=3;
				while($i<count($parse)) {
					$commentElement = trim($parse[$i]);
					$commentElementArr = explode(';;;',$commentElement);
					$commentElementArr[0] = trim($commentElementArr[0]);
					$commentElementArr[1] = trim($commentElementArr[1]); 
					$newElement = implode('|', $commentElementArr);
					$i++;$i++;
					$ip = trim($parse[$i]);
					$newElement .= '|'.$ip;
					$sshresult = sendCommand('/ip arp print where address='.$ip);
					$macArr = explode($ip,$sshresult);
					if (count($macArr)>1) {
						$macArr[1] = trim($macArr[1]);
						$macParse = explode(' ', $macArr[1]);
						$newElement .= '|'.$macParse[0];					
					} else {
						$newElement .= '|';
					};
					$sshresult = sendCommand('/queue simple print where target-addresses='.$ip.'/32');
					$limitExplode = explode('max-limit=', $sshresult);
					if (count($limitExplode)>1) {
						$limitVal = explode(' ', $limitExplode[1]);
						$newElement .= '|'.$limitVal[0];					
					} else {
						$newElement .= '|';
					};					
					$i++;
					array_push($elementsArr, $newElement);
				};
				array_pop($elementsArr);
				$clientshtml = '<table><thead><tr><th>Виключено</th><th>Бонусів</th><th>Бонуси з</th><th>Бонуси до</th><th>Інформація</th><th>ІП адреса</th><th>МАК адреса</th><th>Швидкість</th><th></th></tr></thead><tbody>';
				$othershtml = '<table><thead><tr><th>Виключено</th><th>Інформація</th><th>ІП адреса</th><th>МАК адреса</th><th>Швидкість</th><th></th></tr></thead><tbody>';
				for($i = 0; $i<count($elementsArr); $i++) {
					$rowData = $elementsArr[$i];
					$arrData = explode('|',$rowData);
					if ($arrData[1] == CLIENTSCOMMENTPREFIX) {
						$clientshtml .= '<tr><td>';
						$disabled = explode(' ',$arrData[0]);
						if (count($disabled)>1) {
							$clientshtml .= '<input type="checkbox" checked></td><td>';
						} else {
							$clientshtml .= '<input type="checkbox"></td><td>';
						};
						$clientshtml .= '<input class="comment" type="number" value="'.$arrData[2].'"></td><td>';
						$clientshtml .= '<input class="comment" type="date" value="'.$arrData[3].'"></td><td>';
						$clientshtml .= '<input class="comment end" type="date" value="'.$arrData[4].'"></td><td>';
						$info = explode(ALLOWLISTADRES, $arrData[5]);
						$info = trim($info[0]);
						$clientshtml .= '<input class="comment" type="text" value="'.$info.'"></td><td>';
						$clientshtml .= '<input type="text" value="'.$arrData[6].'" readonly></td><td>';
						$clientshtml .= '<input class="mac" type="text" value="'.$arrData[7].'"></td><td>';
						$clientshtml .= '<input class="limit" type="text" value="'.$arrData[8].'"></td><td>';
						$clientshtml .= '<input type="button" value="Стерти"></td>';
						$clientshtml .= '</tr>';
					} else {
						$othershtml .= '<tr><td>';
						$disabled = explode(' ',$arrData[0]);
						if (count($disabled)>1) {
							$othershtml .= '<input type="checkbox" checked></td><td>';
						} else {
							$othershtml .= '<input type="checkbox"></td><td>';
						};
						$info = explode(ALLOWLISTADRES, $arrData[1]);
						$info = trim($info[0]);
						$othershtml .= '<input class="comment" type="text" value="'.$info.'"></td><td>';
						$othershtml .= '<input type="text" value="'.$arrData[2].'" readonly></td><td>';
						$othershtml .= '<input class="mac" type="text" value="'.$arrData[3].'"></td><td>';
						$othershtml .= '<input class="limit" type="text" value="'.$arrData[4].'"></td><td>';
						$othershtml .= '<input type="button" value="Стерти"></td>';
						$othershtml .= '</tr>';
					}
				};
				$clientshtml .= '</tbody></table>';
				$othershtml .= '</tbody></table>';
				$html .= '<form><div id="clients"><h3>Абонементи:</h3>'.$clientshtml.'</div><div id="others"><h3>Інші:</h3>'.$othershtml.'</div></form>'.HTML_END;
				print($html);
			};
		if (isset($_GET['report'])){
			$html = HTML_FIRST.HTML_MENU.'<div id="reports">';
			$result = sendCommand('/file print detail');
                $parse = explode('name="', $result);
                $i=1;
                for($i=1; $i<count($parse); $i++) {
                    $parseVal = explode('" type="',$parse[$i]);
                    $isReport = substr($parseVal[0],0,12);
                    if ($isReport == 'mbillsReport') {
                        $file = 'ftp://'.MIKROTIKLOGIN.':'.MIKROTIKPASS.'@'.MIKROTIKIP.'/'.$parseVal[0];
                        $fileText = file_get_contents($file);
                        $date = substr($parseVal[0],13,-4);
                        $html .= '<table class="reportTable"><caption>'.$date.'</caption><thead><tr><th>IP адрес</th><th>Пер. (Мб)</th><th>Прий. (Мб)</th></tr></thead><tbody>';
                        $parseInfo = explode('target-addresses=', $fileText);
                        for($j=1; $j<count($parseInfo); $j++) {
                           $targetAddresses = explode('/32 dst-address=',$parseInfo[$j]);
                           $bytes = explode(' bytes=',$targetAddresses[1]);
                           $bytes = explode(' total-bytes=',$bytes[1]);
                           $totalMb = explode('/',$bytes[0]);
                           if ($bytes[0] != '0/0') {
                                $html .= '<tr><td>'.$targetAddresses[0].'</td><td>'.round(($totalMb[0]/1024/1024),2).'</td><td>'.round(($totalMb[1]/1024/1024),2).'</td></tr>';
                           }
                        };
                        $html .= '</tbody></table>';
                    }
                };
                $html .='</div>'.HTML_END;
                print($html);
            };
            if (isset($_GET['reportClear'])){
                $result = sendCommand('/file print detail');
                $parse = explode('name="', $result);
                $i=1;
                for($i=1; $i<count($parse); $i++) {
                    $parseVal = explode('" type="',$parse[$i]);
                    $isReport = substr($parseVal[0],0,12);
                    if ($isReport == 'mbillsReport') {
                        $resultDelete = sendCommand('/file remove '.$parseVal[0]);
                    }
                };
                header('Location: ?report');
            };
		};			
	} else {
		if (isset($_GET['deny'])){
			$ip = $_SERVER['REMOTE_ADDR'];
			print(HTML_FIRST.'<h1>У Вас немає дозволу на доступ</h1><h3><span>Ваш IP адрес: '.$ip.'</span></h3><h4>За доступом до мережі звертайтеся до адміністратора</h4>'.HTML_END);
		} else {
			if (isset($_POST['login']) && isset($_POST['pass'])) {
				$l = strip_tags($_POST['login']);
				$p = strip_tags($_POST['pass']);
					if ($l == ADMLOGIN && $p == ADMPASS) {
						$_SESSION['login'] = $l;
						$_SESSION['pass'] = $p;
						header('Location: ?log');
					} else {
						print(HTML_FIRST.'<h1>Ты кто такой ? ... давай досвидание</h1>'.HTML_END);
					};
			} else { 
				print(HTML_FIRST.'<form action="index.php" method="post"><input type="text" name="login"><input type="password" name="pass"><input type="submit"></form>'.HTML_END);
			};
		};
	};
?>
