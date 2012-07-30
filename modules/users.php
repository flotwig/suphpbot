<?php
$function_map['users'] = array(
    'identify'=>'users_identify',
    'register'=>'users_register',
	'setadmin'=>'users_setadmin'
);
$hook_map['users'] = array(
	'data_in'=>'users_hook_data_in'
);
$help_map['users'] = array(
    'identify'=>'In a private message, send "identify yourusername yourpassword" to the bot to log in. You must have registered an account with "register" previously.',
    'register'=>'In a private message, send "register username password" to the bot in order to create an account. You can then log in with "identify".',
	'setadmin'=>'Set a user as an administrator.'
);
$usersessions = array();
function users_functions_load() {
	// csv format: username, password in sha256, hostmask, admin (1 or 0)
	$userhandle = fopen('./data/users.csv','r');
	$userarray = array();
	while ($userline = fgetcsv($userhandle)) {
		$userarray[] = $userline;
	}
	fclose($userhandle);
	return $userarray;
}
function users_functions_save($userarray) {
    $handle = fopen('./data/users.csv','w');
    foreach ($userarray as $userline) {
        $return = fputcsv($handle,$userline);
    }
    fclose($handle);
	return $return;
}
function users_identify() {
    global $args,$channel,$usersessions,$hostname,$in_convo;
	$userarray = users_functions_load();
	if (!$in_convo) {
		send_msg($channel,'This command must be sent via /msg.');
	} else if (is_array($usersessions[$hostname])) {
		send_msg($channel,'You\'re already logged in.');
	} else {
		foreach ($userarray as $userline) {
			if ($userline[0]==$args[0]) {
				if ($userline[1]==hash('sha256',$args[1])) {
					$usersessions[$hostname] = $userline;
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
	global $args,$channel,$usersessions,$hostname,$in_convo;
	$userarray = users_functions_load();
	if (!$in_convo) {
		send_msg($channel,'This command must be sent via /msg.');
	} else if (is_array($usersessions[$hostname])) {
		send_msg($channel,'You\'re already logged in.');
	} else if (count($args)!==2) {
		send_msg($channel,'Command usage: register USERNAME PASSWORD');
	} else {
		foreach ($userarray as $userline) {
			if (strtolower($args[0])==strtolower($userline[0])) {
				$error = 'That username is already registered.';
			} elseif (strtolower($hostname)==strtolower($userline[2])) {
				$error = 'There is already an account registered to this hostname.';
			}
		}
		if ($error=='') {
			$userarray[] = array($args[0],hash('sha256',$args[1]),$hostname,0);
			if (users_functions_save($userarray)) {
				send_msg($channel,'You were successfully signed up. Now, you can identify.');
			} else {
				send_msg($channel,'Registration failed.');
			}
		} else {
			send_msg($channel,$error);
		}
	}
}
function users_setadmin() {
	global $args,$channel,$admin;
	$userarray = users_functions_load();
	if ($admin) {
		foreach ($userarray as &$userline) {
			if ($userline[0]==$args[0]) {
				$userline[2]=$args[1];
			}
		}
		users_functions_save($userarray);
	} else {
		send_msg($channel,'Didn\'t your mother teach you about permissions?');
	}
}
function users_hook_data_in() {
	global $buffwords,$usersessions,$hostname,$admin;
	// if they quit, let's log them out
	if ($buffwords[1]=='QUIT') {
		unset($usersessions[$hostname]);
	}
	// is our user an admin?
	if (is_array($usersessions[$hostname])) {
		if ($usersessions[$hostname][3]==1) {
			$admin = TRUE;
		} else {
			$admin = FALSE;
		}
	}
}