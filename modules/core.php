<?php
// Core module. I recommend that you not unload this, as it contains the 'load'
// and 'unload' commands. It effectively neuters your bot.
$function_map['core'] = array(
	'load'=>'core_module_load',
	'unload'=>'core_module_unload',
	'list'=>'core_command_list',
	'quit'=>'core_quit',
	'echo'=>'core_echo',
	'whoami'=>'core_whoami',
	'ping'=>'core_ping',
	'reload'=>'core_reload'
);
function core_reload() {
	global $admin,$channel;
	if ($admin) {
		load_settings();
		send_msg($channel,'Settings reloaded.');
	} else {
		send_msg($channel,'You are merely a normal user. You shall not reload settings!');
	}
}
function core_ping() {
	global $channel;
	send_msg($channel,'pong');
}
function core_whoami() {
	global $channel,$nick;
	send_msg($channel,'You are ' . $nick . '.');
}
function core_echo() {
	global $admin,$buffwords,$channel,$arguments;
	if ($admin) {
		send_msg($channel,'' . $arguments);
	} else {
		send_msg($channel,'You don\'t have the permissions required to execute this command.');
	}
}
function core_module_unload() {
	global $admin,$buffwords,$commands,$nick,$function_map,$channel;
	if ($admin) {
		$module = end(explode('/',$buffwords[4]));
		if (is_array($function_map[$module])) {
			$commands = array_diff($commands,$function_map[$module]);
			send_msg($channel,'Module unloaded successfully!');
			return true;
		} else {
			send_msg($channel,'Module not loaded.');
			return false;
		}
	} else {
		send_msg($channel,'You need to be identified as an administrator to unload plugins.');
		return false;
	}
}
function core_module_load() {
	global $admin,$buffwords,$commands,$nick,$function_map,$channel;
	if ($admin) {
		$module = end(explode('/',$buffwords[4]));
		if (file_exists('./modules/' . $module . '.php')) {
			include('./modules/' . $module . '.php');
			$commands = array_merge($commands,$function_map[$module]);
			send_msg($channel,'Plugin loaded.');
			return true;
		} else {
			send_msg($channel,'Plugin not loaded. Does it exist?');
			return false;
		}
	} else {
		send_msg($channel,'You need to be identified as an administrator to load plugins.');
		return false;
	}
}
function core_command_list() {
	global $commands,$channel,$nick,$function_map,$arguments;
	if (!is_array($function_map[$arguments])) {
		foreach ($function_map as $module => $coms) {
			$ocomm[] = $module;
		}
		sort($ocomm);
		$ocomm = implode(', ',$ocomm);
		send_msg($channel,'Modules loaded: ' . $ocomm);
	} else {
		foreach ($function_map[$arguments] as $command => $function) {
			$ocomm[] = $command;
		}
		sort($ocomm);
		$ocomm = implode(', ',$ocomm);
		send_msg($channel,'Commands in ' . $arguments . ': ' . $ocomm);
	}
}
function core_quit() {
	global $admin,$settings,$socket,$buffwords;
	if ($admin) {
		send('QUIT :' . $settings['quitmsg']);
		fclose($socket);
		die();
	} else {
		send_msg($buffwords[2],'If you really want to quit so bad, maybe you should identify as an admin first!');
	}
}