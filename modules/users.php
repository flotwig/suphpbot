<?php
$function_map['users'] = array(
    'identify'=>'users_identify',
    'register'=>'users_register'
);
$hook_map['users'] = array(
	'data_in'=>'users_hook_data_in'
);
$user_sessions = array();
// csv format: username, password in sha256, hostmask, admin (1 or 0)
$user_handle = fopen('./data/users.csv','r');
$user_array = array();
while ($user_line = fgetcsv($user_handle)) {
    $user_array[] = $user_line;
}
fclose($user_handle);
function users_functions_save() {
    global $user_array;
    $handle = fopen('./data/users.csv','w');
    foreach ($user_array as $user_line) {
        $return = fputcsv($handle,$user_line);
    }
    fclose($handle);
	return $return;
}
function users_identify() {
    global $args,$channel,$user_array,$user_sessions,$hostname,$in_convo;
	if (!$in_convo) {
		send_msg($channel,'This command must be sent via /msg.');
	} elseif (is_array($user_sessions[$hostname])) {
		send_msg($channel,'You\'re already logged in.');
	} else {
		foreach ($user_array as $user_line) {
			if ($user_line[0]==$args[0]) {
				if ($user_line[1]==hash('sha256',$args[1])) {
					$user_sessions[$hostname] = $user_line;
					send_msg($channel,'You are now identified.');
					$happening = TRUE;
				} else {
					send_msg($channel,'Incorrect password.');
					$happening = TRUE;
				}
			}
		}
		if (!$happening) {
			send_msg($channel,'The specified user does not exist.');
		}
	}
} 
function users_register() {
	global $args,$channel,$user_array,$user_sessions,$hostname,$in_convo;
	if (!$in_convo) {
		send_msg($channel,'This command must be sent via /msg.');
	} elseif (is_array($user_sessions[$hostname])) {
		send_msg($channel,'You\'re already logged in.');
	} elseif (count($args)!==2) {
		send_msg($channel,'Command usage: register USERNAME PASSWORD');
	} else {
		$user_array[] = array($args[0],hash('sha256',$args[1]),$hostname,0);
		if (users_functions_save()) {
			send_msg($channel,'You were successfully signed up. Now, you can identify.');
		} else {
			send_msg($channel,'Registration failed.');
		}
	}
}
function users_hook_data_in() {
	global $buffwords,$user_sessions,$hostname,$admin;
	// if they quit, let's log them out
	if ($buffwords[1]=='QUIT') {
		$user_sessions[$hostname] = NULL;
	}
	// is our user an admin?
	if (is_array($user_sessions[$hostname])) {
		if ($user_sessions[$hostname][3]==1) {
			$admin = TRUE;
		} else {
			$admin = FALSE;
		}
	}
}