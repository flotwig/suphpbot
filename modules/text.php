<?php
// Modify text
$function_map['text'] = array(
	'rainbow'=>'text_rainbow',
	'bold'=>'text_generic_modify',
	'reverse'=>'text_generic_modify',
	'underline'=>'text_generic_modify',
	'italic'=>'text_generic_modify'
);
function text_rainbow() {
	global $channel,$arguments;
	$echo = str_split($arguments);
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
function text_generic_modify() {
	global $channel,$arguments,$command;
	send_msg($channel,fx($command,$arguments));
}