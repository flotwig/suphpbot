<?php
// Artificial Intelligence
$function_map['ai'] = array(
	'ai'=>'ai_send'
);
function ai_send() {
	global $nick,$channel,$arguments;
	$ai = file_get_contents('http://query.yahooapis.com/v1/public/yql?q=' . urlencode('select * from xml where url="http://www.pandorabots.com/pandora/talk-xml?botid=e6f8f64f6e3428d8&input=' . urlencode($arguments) . '&custid=' . urlencode($nick) . '"') . '&format=json');
	$ai = json_decode($ai,TRUE);
	send_msg($channel,$ai['query']['results']['result']['that']);
}
?>