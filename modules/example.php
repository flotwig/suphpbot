<?php
// Define the command => function mappings here. The name of the array
// must be the same as the name of the .php file. So, the mapping array for
// example.php would be $function_map['example'].
$function_map['example'] = array(
	'test'=>'test_function'
);
$hook_map['example'] = array(
	'data_in'=>'test_hook_data_in'
);
// Define those functions, yo.
function test_hook_data_in() {
	shell_send('example hook');
}
function test_function() {
	// Let's globalize some of the variables we'll need for this command
	global $buffwords,$admin,$nick;
	if ($admin) {
		// He's an admin! Let's PRIVMSG the channel and tell them so.
		send_msg($buffwords[2],$nick . ' is an admin.');
	} else {
		// Not an admin...
		send_msg($buffwords[2],$nick . ' is not an admin. Too bad.');
	}
}