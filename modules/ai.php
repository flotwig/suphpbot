<?php
// Artificial Intelligence
$function_map['ai'] = array(
	'ai'=>'ai_send'
);
function ai_send() {
	global $buffwords,$nick;
	$channel = $buffwords[2];
	$buffwords[0]=NULL; $buffwords[1]=NULL; $buffwords[2]=NULL; // $buffwords[3]=NULL;
	$echo = trim(implode(' ',$buffwords));
	$ai = file_get_contents('http://query.yahooapis.com/v1/public/yql?q=' . urlencode('select * from xml where url="http://www.pandorabots.com/pandora/talk-xml?botid=f5d922d97e345aa1&input=' . urlencode($echo) . '&custid=' . $nick . '"') . '&format=json');
	$ai = json_decode($ai,TRUE);
	send_msg($channel,$ai['query']['results']['result']['that']);
}
?>