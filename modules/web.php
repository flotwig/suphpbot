<?php
$function_map['web'] = array(
	'bing'=>'web_bing'
);
function bingSearch($string) {
	$url = 'http://api.bing.net/json.aspx?AppId=AC1581361F2ECCC01C4D49F143D4A14003712E4A';
	$url .= '&Query=' . urlencode($string);
	$url .= '&Sources=Web';
	echo '<span class="status">Processing ' . $url . '... done.</span><br/>';
	return json_decode(file_get_contents($url),TRUE);
}
function web_bing() {
	global $channel,$arguments;
	$results = bingSearch($arguments);
	foreach ($results['SearchResponse']['Web']['Results'] as $result) {
		$resultsirc[] = C_BOLD . $result['Title'] . C_BOLD . ' <' . $result['Url'] . '>';
	}
	$resultsirc = implode(', ',$resultsirc);
	send_msg($channel,'Results: ' . $resultsirc);
}
?>