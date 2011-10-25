<?php
$function_map['channel'] = array(
	'join'=>'channel_join',
	'part'=>'channel_part'
);
function channel_join() {
	global $nick,$buffwords,$admin;
	if ($admin) {
		send('JOIN ' . $buffwords[4] . ' ' . $buffwords[5]);
	} else {
		send_msg($buffwords[2],$nick . ': You need to be an admin to tell ME to join channels!');
	}
}
function channel_part() {
	global $nick,$buffwords,$admin,$socket;
	if ($admin) {
		send('PART ' . $buffwords[4] . ' :' . $buffwords[5]);
	} else {
		send_msg($buffwords[2],$nick . ': Nice try, bro. I don\'t part channels unless you identify.');
	}
}
?>