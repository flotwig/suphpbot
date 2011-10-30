<?php
$function_map['channel'] = array(
	'join'=>'channel_join',
	'part'=>'channel_part'
);
function channel_join() {
	global $nick,$admin,$channel,$buffwords;
	if ($admin) {
		send('JOIN ' . $buffwords[4] . ' ' . $buffwords[5]);
	} else {
		send_msg($channel,$nick . ': You need to be an admin to tell ME to join channels!');
	}
}
function channel_part() {
	global $nick,$buffwords,$admin,$socket,$channel;
	if ($admin) {
		send('PART ' . $buffwords[4] . ' :' . $buffwords[5]);
	} else {
		send_msg($channel,$nick . ': Nice try, bro. I don\'t part channels unless you identify.');
	}
}
?>