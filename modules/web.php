<?php
define('BITLY_USERNAME','o_350gb401dt');
define('BITLY_APIKEY','R_ab02f29376cbc60916f569645a9772a7');
define('BING_APIKEY','AC1581361F2ECCC01C4D49F143D4A14003712E4A');
define('PANDORABOTS_BOTID','e6f8f64f6e3428d8');
$function_map['web'] = array(
	'bing'=>'web_bing',
	'bitly'=>'web_bitly',
	'status'=>'web_status',
	'bash'=>'web_bash',
	'ai'=>'web_ai'
);
$help_map['web'] = array(
	'bing'=>'Search the web with Bing!, the best search engine around (and the only one with an API). Usage: "bing <search terms go here>"',
	'bitly'=>'Shorten URLs using bit.ly. Usage: "bitly http://long.url/',
	'status'=>'Type "status hostname" to get the full status of a server.',
	'bash'=>'Retrieve a random quote from bash.org. Warning: Most of these quotes are very inappropriate.',
	'ai'=>'Type "ai Conversational blabber goes here." to talk to the bot like it\'s a real human - because you can\'t get any real friends. Powered by PandoraBots!'
);
$bitly_cache = array();
function bingSearch($string) {
	$url = 'http://api.bing.net/json.aspx?AppId=' . BING_APIKEY;
	$url .= '&Query=' . urlencode($string);
	$url .= '&Sources=Web';
	return json_decode(file_get_contents($url),TRUE);
}
function shortUrl($url) {
	$surl = file_get_contents('http://api.bitly.com/v3/shorten?format=txt&login=' . BITLY_USERNAME . '&apikey=' . BITLY_APIKEY . '&longUrl=' . urlencode($url));
	if (!$surl) {
		$surl = 'Unable to contact the bit.ly API!';
	}
	return $surl;
}
function web_ai() {
	global $nick,$channel,$arguments;
	$ai = file_get_contents('http://query.yahooapis.com/v1/public/yql?q=' . urlencode('select * from xml where url="http://www.pandorabots.com/pandora/talk-xml?botid=' . PANDORABOTS_BOTID . '&input=' . urlencode($arguments) . '&custid=' . urlencode($nick) . '"') . '&format=json');
	$ai = json_decode($ai,TRUE);
	send_msg($channel,$ai['query']['results']['result']['that']);
}
function web_bash() {
	global $channel;
	$quote = file_get_contents('http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20html%20where%20url%3D%22http%3A%2F%2Fbash.org%2F%3Frandom%22%20and%20xpath%3D%22%2F%2Fp%5B%40class%3D\'qt\'%5D%22%20LIMIT%201&format=json');
	$quote = json_decode($quote,TRUE);
	send_msg($channel,'' . str_replace(array("\n","\r"),' /',$quote['query']['results']['p']['content']));
}
function web_bing() {
	global $channel,$arguments;
	$results = bingSearch($arguments);
	if (!$results) {
		$resultsirc = 'Unable to contact the Bing! API.';
	} else {
		foreach ($results['SearchResponse']['Web']['Results'] as $result) {
			$resultsirc[] = $result['Title'] . ' <' . $result['Url'] . '>';
		}
		$resultsirc = implode(', ',$resultsirc);
	}
	send_msg($channel,'Results: ' . $resultsirc);
}
function web_bitly() {
	global $channel,$arguments,$bitly_cache;
	if (isset($bitly_cache[$arguments])) {
		$surl = $bitly_cache[$arguments];
	} else {
		$surl = shortUrl($arguments);
		$bitly_cache[$arguments] = $surl;
	}
	send_msg($channel,$surl);
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