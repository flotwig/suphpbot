<?php
define('BITLY_USERNAME','o_350gb401dt');
define('BITLY_APIKEY','R_ab02f29376cbc60916f569645a9772a7');
define('BING_APIKEY','AC1581361F2ECCC01C4D49F143D4A14003712E4A');
define('PANDORABOTS_BOTID','e6f8f64f6e3428d8');
$function_map['web'] = array(
	'status'=>'web_status',
	'ai'=>'web_ai'
);
$help_map['web'] = array(
	'status'=>'Type "status hostname" to get the full status of a server.',
	'ai'=>'Type "ai Conversational blabber goes here." to talk to the bot like it\'s a real human - because you can\'t get any real friends. Powered by PandoraBots!'
);
function web_ai() {
	global $nick,$channel,$arguments;
	$ai = file_get_contents('http://query.yahooapis.com/v1/public/yql?q=' . urlencode('select * from xml where url="http://www.pandorabots.com/pandora/talk-xml?botid=' . PANDORABOTS_BOTID . '&input=' . urlencode($arguments) . '&custid=' . urlencode($nick) . '"') . '&format=json');
	$ai = json_decode($ai,TRUE);
	send_msg($channel,$ai['query']['results']['result']['that']);
}
function web_status() {
	global $channel,$args;
	if (empty($args[0])) {
		$response = 'Type "status hostname" to get the full status of a server.';
	} elseif (!filter_var('a@' . $args[0],FILTER_VALIDATE_EMAIL)&&!filter_var($args[0],FILTER_VALIDATE_IP)) {
		$response = 'That is not a valid hostname or IP address. A valid hostname looks like this: hostigation.chary.us';
	} else {
		send_msg($channel,'Now probing server status. Be aware that this command only displays the information available remotely, so it may not be 100% accurate.');
		$response = 'Server status: ';
		$http = fsockopen($args[0],80,$errno,$errstr,1);
		if ($http) {
			$response .= 'HTTP is ' . C_COLOR . '3up' . C_COLOR . ', ';
			if (file_get_contents('http://' . $args[0])) {
				$response .= 'and it appears to be ' . C_COLOR . '3functioning' . C_COLOR . '. ';
			} else {
				$response .= 'but it is ' . C_COLOR . '4not serving pages' . C_COLOR . '. ';
			}
			fclose($http);
		} else {
			$response .= 'HTTP is ' . C_COLOR . '4down' . C_COLOR . '. ';
		}
		$ssh = fsockopen($args[0],22,$errno,$errstr,1);
		$response .= 'SSH and SFTP are ';
		if ($ssh) {
			$response .= C_COLOR . '3up' . C_COLOR . '. ';
			fclose($ssh);
		} else {
			$response .= C_COLOR . '4down' . C_COLOR . '. ';
		}
		$ftp = fsockopen($args[0],21,$errno,$errstr,1);
		$response .= 'FTP is ';
		if ($ftp) {
			$response .= C_COLOR . '3up' . C_COLOR . '. ';
			fclose($ftp);
		} else {
			$response .= C_COLOR . '4down' . C_COLOR . '. ';
		}
		$cpanel = fsockopen($args[0],2082,$errno,$errstr,1);
		if ($cpanel) {
			$response .= 'cPanel appears to be ' . C_COLOR . '3up' . C_COLOR . '. ';
			fclose($cpanel);
		} else {
			$response .= 'cPanel is ' . C_COLOR . '4down' . C_COLOR . '. ';
		}
		$mysql = fsockopen($args[0],3306,$errno,$errstr,1);
		if ($mysql) {
			$response .= 'MySQL looks ' . C_COLOR . '3up' . C_COLOR . ' from here. ';
			fclose($mysql);
		} else {
			$response .= 'I was ' . C_COLOR . '4unable to ping' . C_COLOR . ' the MySQL server. ';
		}
	}
	send_msg($channel,$response);
}
?>