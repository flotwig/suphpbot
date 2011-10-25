<?php
// Define the command => function mappings here. The name of the array
// must be the same as the name of the .php file. So, the mapping array for
// example.php would be $function_map['example'].
$function_map['example'] = array(
	'rainbow'=>'rainbow6',
	'ai'=>'ai2'
);
// Define those functions, yo.
function rainbow6() {
	global $buffwords;
	$channel = $buffwords[2];
	$buffwords[0]=NULL; $buffwords[1]=NULL; $buffwords[2]=NULL; $buffwords[3]=NULL;
	$echo = trim(implode(' ',$buffwords));
	$echo = str_split($echo);
	$rainbows = '';
	$i=0;
	$rainbow = array('07','04','08','03','12','06','07');
	foreach ($echo as $char) {
		$rainbows .= C_COLOR . $rainbow[$i] . $char;
		$i++;
		if ($i==count($rainbow)) {
			$i=0;
		}
	}
	send_msg($channel,$rainbows);
}
function ai2() {
	global $buffwords,$nick;
	$channel = $buffwords[2];
	$buffwords[0]=NULL; $buffwords[1]=NULL; $buffwords[2]=NULL; // $buffwords[3]=NULL;
	$echo = trim(implode(' ',$buffwords));
	$ai = file_get_contents('http://query.yahooapis.com/v1/public/yql?q=' . urlencode('select * from xml where url="http://www.pandorabots.com/pandora/talk-xml?botid=f5d922d97e345aa1&input=' . urlencode($echo) . '&custid=' . $nick . '"') . '&format=json');
	$ai = json_decode($ai,TRUE);
	send_msg($channel,$ai['query']['results']['result']['that']);
}