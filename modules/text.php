<?php
// Modify text
$function_map['text'] = array(
	'rainbow'=>'text_rainbow',
	'bold'=>'text_bold',
	'reverse'=>'text_reverse',
	'underline'=>'text_underline',
	'italic'=>'text_italic'
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
function text_bold() {
	global $buffwords,$arguments;
	send_msg($buffwords[2],C_BOLD . $arguments);
}
function text_reverse() {
	global $buffwords,$arguments;
	send_msg($buffwords[2],C_REVERSE . $arguments);
}
function text_underline() {
	global $buffwords,$arguments;
	send_msg($buffwords[2],C_UNDERLINE . $arguments);
}
function text_italic() {
	global $buffwords,$arguments;
	send_msg($buffwords[2],C_ITALIC . $arguments);
}
