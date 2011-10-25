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
function text_bold() {
	global $buffwords,$buffer;
	$text = explode($settings['commandchar'] . 'bold ',$buffer,2);
	send_msg($buffwords[2],C_BOLD . $text[1]);
}
function text_reverse() {
	global $buffwords,$buffer;
	$text = explode($settings['commandchar'] . 'reverse ',$buffer,2);
	send_msg($buffwords[2],C_REVERSE . $text[1]);
}
function text_underline() {
	global $buffwords,$buffer;
	$text = explode($settings['commandchar'] . 'underline ',$buffer,2);
	send_msg($buffwords[2],C_UNDERLINE . $text[1]);
}
function text_italic() {
	global $buffwords,$buffer;
	$text = explode($settings['commandchar'] . 'italic ',$buffer,2);
	send_msg($buffwords[2],C_ITALIC . $text[1]);
}
