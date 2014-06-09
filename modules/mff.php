<?php
$function_map['mff'] = array(
	'fact'=>'mff_fact',
);
$help_map['mff'] = array(
	'fact'=>'Returns a random fact from /r/MillenniumFalc0nFacts',
);
function mff_fact(){
	global $channel;
	$fact=file_get_contents('http://www.reddit.com/r/MillenniumFalc0nFacts/random.json?'.time());
	$fact=json_decode($fact,TRUE);
	send_msg($channel,$fact[0]['data']['children'][0]['data']['title']);
}