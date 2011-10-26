<?php
// Funny Internet quotes
$function_map['bash'] = array(
	'bash'=>'grab_bash'
);
function grab_bash() {
	global $buffwords, $socket, $nick;
	$quote = file_get_contents('http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20html%20where%20url%3D%22http%3A%2F%2Fbash.org%2F%3Frandom%22%20and%20xpath%3D%22%2F%2Fp%5B%40class%3D\'qt\'%5D%22%20LIMIT%201&format=json');
	$quote = json_decode($quote,TRUE);
	send_msg($buffwords[2],'' . str_replace(array("\n","\r"),' /',$quote['query']['results']['p']['content']));
}
