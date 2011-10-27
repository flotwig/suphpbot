<?php
$function_map['users'] = array(
    'identify'=>'users_identify',
    'register'=>'users_register',
};
$hook_map['users'] = array(
	'data_in'=>'users_hook_data_in'
);
$user_sessions = array();
// csv format: username, password in sha256, admin (1 or 0)
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
        fputcsv($handle,$user_line);
    }
    fclose($handle);
}
function users_identify() {
    global $args,$channel,$user_array,$user_sessions,$hostname;
    foreach ($user_array as $user_line) {
        if ($user_line[0]==$args[0]) {
			if ($user_line[1]==hash('sha256',$args[1])) {
				$user_sessions[$hostname] = $user_line;
				send_msg($channel,'You are now identified.');
			} else {
				send_msg($channel,'Incorrect password.');
				$wrongpass = TRUE;
			}
		}
    }
	if (!$wrongpass) {
		send_msg($channel,'The specified user does not exist.');
	}
} 
function users_register() {
}
function users_hook_data_in() {
	global $buffwords,$user_sessions,$hostname,$admin;
	// if they quit, let's log them out
	if ($buffwords[1]=='QUIT') {
		$user_sessions[$hostname] = NULL;
	}
	// is our user an admin?
	if (is_array($user_sessions[$hostname])) {
		$admin = $user_sessions[$hostname][2];
	}
}