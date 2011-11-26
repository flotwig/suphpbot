<?php
define('BITLY_USERNAME','o_350gb401dt');
define('BITLY_APIKEY','R_ab02f29376cbc60916f569645a9772a7');
define('BING_APIKEY','AC1581361F2ECCC01C4D49F143D4A14003712E4A');
$function_map['web'] = array(
	'bing'=>'web_bing',
	'bitly'=>'web_bitly'
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
?>