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
	'config'=>'core_config',
	'uptime'=>'core_uptime',
	'help'=>'core_help',
	'eval'=>'core_eval',
	'restart'=>'core_restart',
	'allow_user'=>'allow_user',
);
$help_map['core'] = array(
	'load'=>'Loads a module. Type "load modulename" as an admin to load modules.',
	'unload'=>'Unloads a module, rendering it unusable. Usage: "unload modulename"',
	'list'=>'If no arguments are specified, it lists available modules. Type "list modulename" to see a list of the commands in a module.',
	'quit'=>'Makes the bot quit IRC.',
	'echo'=>'The bot will echo any arguments back to you.',
	'whoami'=>'Tells you who you are to the bot.',
	'ping'=>'Returns "pong".',
	'reload'=>'Reloads settings.',
	'about'=>'Tells you about the bot. Run the command for more information.',
	'raw'=>'Send raw IRC commands to the server.',
	'config'=>'To view configuration: "config view optionname". To change configuration options, type "config optionname newvalue". You need to be an admin, obviously.',
	'uptime'=>'View the uptime of the bot and the server which it is running on, if available.',
	'help'=>'Get help for a command. You probably already know how to use this, because you\'re using it right now...?',
	'eval'=>'Runs raw arguments through PHP eval(). Disabled by default.',
	'restart'=>'Start another instance of the bot and kill this one.',
	'allow_user'=>'Adds given user to allowed_user option in the configuration file. This is for features that you only want certain people to use (non admin)',
);
function core_help() {
	global $channel, $args, $help, $commands, $settings, $nick;
	if ($args[0]!=='') {
		if (isset($help[$args[0]])) {
			$response = 'Help for ' . strtolower($args[0]) . ': ' . $help[$args[0]];
		} elseif (isset($commands[$args[0]])) {
			$response = 'The command "' . strtolower($args[0])  . '" exists, but there is no help for it.';
		} else {
			$response = 'The command "' . strtolower($args[0])  . '" does not exist.';
		}
	} else {
		$response = 'Usage: ' . $settings['commandchar'] . 'help ' . fx('BOLD','command') . ' - Use "help list" if you\'re just getting started with the bot.';
	}
	send_msg($nick,$response,1);
}
function core_uptime() {
	global $channel,$nick;
	$uptime = time()-START_TIME;
	$response = 'Bot uptime is: ' . floor($uptime/60/60/24) . ' days, ' . ($uptime/60/60%24) . ' hours, ' . ($uptime/60%60) . ' minutes, and ' . ($uptime%60) . ' seconds. ';
	$uptime = @file_get_contents('/proc/uptime');
	if ($uptime) {
		$uptime = explode('.',$uptime);
		$uptime = preg_replace('/\D/', '', $uptime[0]);
		$response .= 'Server uptime is: ' . floor($uptime/60/60/24) . ' days, ' . ($uptime/60/60%24) . ' hours, ' . ($uptime/60%60) . ' minutes, and ' . ($uptime%60) . ' seconds. ';
	}
	send_msg($channel,$response);
}
function core_about() {
	global $channel,$arguments,$settings,$nick;
	$abouts = array(
		'author'=>'suphpbot is coded by flotwig at http://za.chary.us/',
		'credits'=>'Thanks to the bot-obsessed folks at irc.x10hosting.com, I stole a lot of ideas from them. GtoXic, Dead-i, Sierra and stpvoice are just a few of the people who helped out a lot. Also, thanks to Sharky for not k-lining me :D',
		'download'=>'You can download suphpbot at https://github.com/flotwig/suphpbot',
		'the bot'=>'suphpbot is a modular IRC bot written entirely in PHP.',
		'version'=>IRC_VERSION
	);
	if (isset($abouts[$arguments])) {
		send_msg($nick,$abouts[$arguments],1);
	} else {
		send_msg($nick,'What would you like to know more about? ' . $settings['commandchar'] . 'about ' . implode(', ' . $settings['commandchar'] . 'about ', array_keys($abouts)),1);
	}
}
function core_reload() {
	global $admin,$channel,$nick;
	if ($admin) {
		load_settings();
		send_msg($nick,'Settings reloaded.',1);
	} else {
		noperms();
	}
}
function core_ping() {
	global $nick;
	send_msg($nick,'pong',1);
}
function core_whoami() {
	global $channel,$nick,$buffwords,$admin;
	send_msg($nick,'You are ' . $nick . ' (' . substr($buffwords[0],1) . '), and you are level ' . (int)$admin,1);
}
function core_echo() {
	global $admin,$buffwords,$channel,$arguments;
	if ($admin) {
		send_msg($channel,'' . $arguments);
	} else {
		noperms();
	}
}
function core_module_unload() {
	global $admin,$args,$buffwords,$commands,$nick,$function_map,$channel,$hook_map,$hooks,$help_map,$help,$loaded_modules;
	if ($admin) {
		$module = end(explode('/',$buffwords[4]));
		if($module == "core"&&$args[1] != "force") {
			send_msg($channel, 'You shouldn\'t unload the core module, it contains very important commands. If you want to do this anyway, type force after the command.');
			return false;
		}
		if (is_array($function_map[$module])) {
			unload_module($module);
			send_msg($channel,'Module unloaded successfully!');
			return true;
		} else {
			send_msg($channel,'Module is not loaded.');
			return false;
		}
	} else {
		noperms();
		return false;
	}
}
function core_module_load() {
	global $admin,$buffwords,$commands,$nick,$function_map,$channel,$hook_map,$hooks;
	if ($admin) {
		$module = end(explode('/',$buffwords[4]));
		if (file_exists('./modules/' . $module . '.php')) {
			load_module($module);
			send_msg($channel,'Plugin loaded.');
			return true;
		} else {
			send_msg($channel,'Plugin not loaded. Does it exist?');
			return false;
		}
	} else {
		noperms();
		return false;
	}
}
function core_command_list() {
	global $commands,$channel,$nick,$function_map,$arguments,$loaded_modules;
	if (!is_array($function_map[$arguments])) {
		$ocomm = implode(', ',$loaded_modules);
		send_msg($nick,'Modules loaded: ' . $ocomm,1);
	} else {
		foreach ($function_map[$arguments] as $command => $function) {
			$ocomm[] = $command;
		}
		if (count($ocomm) > 0 ) {
			sort($ocomm);
			$ocomm = implode(', ',$ocomm);
			send_msg($nick,'Commands in ' . $arguments . ': ' . $ocomm,1);
		} else {
			send_msg($nick,'No commands found in ' . $arguments,1);
		}
	}
}
function core_quit() {
	global $admin,$settings,$socket,$buffwords;
	if ($admin) {
		send('QUIT :' . $settings['quitmsg']);
		fclose($socket);
		shell_send('Bot killed by administrator - see above for details.');
		die();
	} else {
		noperms();
	}
}
function core_raw() {
	global $admin,$channel,$arguments;
	if ($admin) {
		send($arguments);
	} else {
		noperms();
	}
}
function core_config() {
	global $admin,$channel,$buffwords,$arguments,$settings,$config;
	if ($buffwords[4]=='view') {
		if (isset($settings[$buffwords[5]])) {
			$private = explode(',',$settings['configprivate']);
			if (in_array(strtolower($buffwords[5],$private))) {
				send_msg($channel,$buffwords[5] . ' is a private option.');
			} else {
				send_msg($channel,$buffwords[5] . ' is: ' . $settings[$buffwords[5]]);
			}
		} else {
			send_msg($channel,'That configuration key does not exist.');
		}
	} else {
		if (!$admin) {
			noperms();
		} elseif (empty($buffwords[4])) {
			send_msg($channel,'The option name is empty, please check and try again.');
		} else {
			$key = $buffwords[4];
			$value = implode(' ',array_slice($buffwords,5));
			$settings[$key] = $value;
			save_settings(array('phpbot'=>$settings),$config);
			send_msg($channel,"Your option has been changed.");
		}
	}
}
function core_eval() {
	global $admin,$channel,$arguments;
	if ($admin) {
		eval($arguments);
	} else {
		noperms();
	}
}
function core_restart() {
	global $admin,$settings,$channel,$socket;
	if (!$admin) {
		noperms();
	} else {
		send('QUIT ' . $settings['quitmsg']);
		shell_send('Attempting to restart the bot...');
		fclose($socket);
		fork_bot();
		die();
	}
}
function allow_user() {
	global $arguments,$settings,$channel,$admin;
	if ($admin) {
		if ($arguments) {
			$users = explode(',',$settings['allowed_users']);
			array_push($users,$arguments);
			$settings['allowed_users'] = join(',',$users);
			save_settings(array('phpbot'=>$settings),$config);
			send_msg($channel,'User has been added.');
		} else {
			send_msg($channel,"Please provide an argument.");			
		}
	} else {
		noperms();
	}
}