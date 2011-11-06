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
	'reload'=>'core_reload',
	'about'=>'core_about',
	'raw'=>'core_raw',
	'config'=>'core_config'
);
function core_about() {
	global $channel,$arguments,$settings;
	$abouts = array(
		'author'=>'suphpbot is coded by flotwig. http://za.chary.us/',
		'url'=>'You can download suphpbot at https://github.com/flotwig/suphpbot',
		'about'=>'suphpbot is a modular IRC bot written entirely in PHP.'
	);
	$abts=array();
	foreach ($abouts as $cmd => $abt) { $abts[] = $cmd; }
	if (isset($abouts[$arguments])) {
		send_msg($channel,$abouts[$arguments]);
	} else {
		send_msg($channel,'What would you like to know more about? ' . $settings['commandchar'] . implode(', ' . $settings['commandchar'],$abts));
	}
}
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
	global $admin,$buffwords,$commands,$nick,$function_map,$channel,$hook_map,$hooks;
	if ($admin) {
		$module = end(explode('/',$buffwords[4]));
		if (is_array($function_map[$module])) {
			$commands = array_diff($commands,$function_map[$module]);
			if (is_array($hook_map[$module])) {
				foreach ($hook_map[$module] as $hook_name => $hook_function) {
					$hooks[$hook_name] = array_diff($hooks[$hook_name],array($hook_function));
				}
			}
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
	global $admin,$buffwords,$commands,$nick,$function_map,$channel,$hook_map,$hooks;
	if ($admin) {
		$module = end(explode('/',$buffwords[4]));
		if (file_exists('./modules/' . $module . '.php')) {
			include_once('./modules/' . $module . '.php');
			$commands = array_merge($commands,$function_map[$module]);
			if (isset($hook_map[$module])) {
				foreach ($hook_map[$module] as $hook_id => $hook_function) {
					$hooks[$hook_id][] = $hook_function;
				}
			}
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
		send_msg($channel,'If you really want to quit so bad, maybe you should identify as an admin first!');
	}
}
function core_raw() {
	global $admin,$channel,$arguments;
	if ($admin) {
		send($arguments);
	} else {
		send_msg($channel,'Nice try.');
	}
}
function core_config() {
	global $admin,$channel,$buffwords,$arguments,$settings,$config;
	if ($buffwords[4]=='view') {
		if (isset($settings[$buffwords[5]])) {
			send_msg($channel,$buffwords[5] . ' is: ' . $settings[$buffwords[5]]);
		} else {
			send_msg($channel,'That configuration key does not exist.');
		}
	} else {
		if (!$admin) {
			send_msg($channel,'Sorry, but you need to be an admin to use that command.');
		} else {
			$key = $buffwords[4];
			$value = implode(' ',array_slice($buffwords,5));
			$settings[$key] = $value;
			save_settings($settings,$config);
		}
	}
}