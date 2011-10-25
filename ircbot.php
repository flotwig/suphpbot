<?php
if (PHP_SAPI !== 'cli') { die('This script can\'t be run from a web browser.'); }
set_time_limit(0);
ini_set('error_reporting',E_ALL-E_NOTICE);
// thanks, tutorialnut.com, for the control codes!
define('C_BOLD', chr(2));
define('C_COLOR', chr(3));
define('C_ITALIC', chr(29));
define('C_REVERSE', chr(22));
define('C_UNDERLINE', chr(31)); 
$config = file_get_contents('ircconfig.conf');
$config = explode("\n",$config);
$settings = array();
foreach ($config as $setting) {
	$setting = explode("=",$setting,2);
	$settings[trim($setting[0])] = trim($setting[1]);
}
// add some essential command mappings
$commands = array(
	'load'=>'module_load',
	'unload'=>'module_unload'
);
// preload some modules
$premods = explode(',',$settings['module_preload']);
foreach ($premods as $premod) {
	include('./modules/' . trim($premod) . '.php');
	$commands = array_merge($commands,$function_map[trim($premod)]);
}
$admins = explode(',',$settings['admins']);
$ignore = explode(',',$settings['ignore']);
while (1) {
	$socket = fsockopen($settings['server'], $settings['port'], $errno, $errstr, 20);
	if (!$socket) {
		echo 'Unable to connect! Retrying in 5...' . "\n";
		sleep(5);
	} else {
		stream_set_timeout($socket, 0, 1);
		stream_set_blocking($socket, 0); 
		if ($settings['pass']!=='') {
			send('PASS ' . $settings['pass']);
		}
		send('USER ' . $settings['nick'] . ' 8 * :' . $settings['realname']);
		send('NICK ' . $settings['nick']);
		while (!feof($socket)) {
			$buffer = fgets($socket);
			if (strlen($buffer)>0) {
				echo '[IN]' . "\t" . $buffer;
			}
			$buffer = str_replace(array("\n","\r"),'',$buffer);
			$buffwords = explode(' ',$buffer);
			$nick = explode('!',$buffwords[0]);
			$nick = substr($nick[0],1);
			if ($buffwords[1]=='002') {
				// The server just sent us something. We're in.
				if ($settings['nickserv_pass']!=='') {
					// Let's identify before we join any channels.
					send_msg($settings['nickserv_nick'],'IDENTIFY ' . $settings['nickserv_pass']);
				}
				$channels = explode(',',$settings['channels']);
				foreach ($channels as $channel) {
					send('JOIN ' . trim($channel));
				}
			} elseif ($buffwords[0]=='PING') {
				send('PONG ' . str_replace(array("\n","\r"),'',end(explode(' ',$buffer,2))));
			} elseif ($buffwords[1]=='PRIVMSG'&&substr($buffwords[3],1,1)==$settings['commandchar']) {
				$admin = FALSE;
				if (in_array($nick,$admins)) {
					send('WHOIS ' . $nick);
					while(1) {
						$buffer = fgets($socket);
						$bufferq = $buffer;
						$buffer = explode(' ',$buffer);
						if (strlen($bufferq)>0) {
							echo '[IN]' . "\t" . $bufferq;
						}
						if ($buffer[1]=='307') {
							$admin = TRUE;
							break;
						} elseif ($buffer[1]=='318'||$buffer[1]=='431') {
							$admin = FALSE;
							break;
						}
					}
				}
				$command = trim(substr($buffwords[3],2));
				if (in_array($nick,$ignore)) {
					// do nothing - we're ignoring them :p
				} elseif ($command=="whoami") {
					send_msg($buffwords[2],$nick . ': You are ' . $nick . '.');
				} elseif ($command=='echo') {
					if ($admin) {
						$channel = $buffwords[2];
						$buffwords[0]=NULL; $buffwords[1]=NULL; $buffwords[2]=NULL; $buffwords[3]=NULL;
						$echo = trim(implode(' ',$buffwords));
						send_msg($channel,$nick . ': ' . $echo);
					} else {
						send_msg($buffwords[2],$nick . ': You don\'t have the permissions required to execute this command.');
					}
				} elseif ($command=='quit'||$command=='end'||$command=='close') {
					if ($admin) {
						send('QUIT :' . $settings['quitmsg']);
						die();
					} else {
						send_msg($buffwords[2],$nick . ': If you really want to quit so bad, maybe you should identify as an admin first!');
					}
				} else {
				if (function_exists($commands[$command])) {
					call_user_func($commands[$command]);
				} else {
					send_msg($buffwords[2],$nick . ': ' . $command . ' is not a valid command. Maybe you need to load a plugin?');
				}
				}
			}
		}
	}
}
// much thanks to gtoxic of avestribot for helping me realize my stupid mistake here
function send($raw) {
	global $socket;
	fwrite($socket,"{$raw}\n\r");
	echo '[OUT]' . "\t" . $raw . "\n";
}
function send_msg($target,$message) {
	send('PRIVMSG ' . $target . ' :' . $message);
}
function module_unload() {
	global $admin,$buffwords,$commands,$nick,$function_map;
	if ($admin) {
		$module = end(explode('/',$buffwords[4]));
		if (is_array($function_map[$module])) {
			/* I've decided not to runkit_function_remove the functions because not many configurations support it
			and it makes me feel dirty.
			foreach ($function_map[$module] as $command=>$function) {
				runkit_function_remove($function);
			} */
			$commands = array_diff($commands,$function_map[$module]);
			send_msg($buffwords[2],$nick . ': Module unloaded successfully!');
			return true;
		} else {
			send_msg($buffwords[2],$nick . ': Module not loaded.');
			return false;
		}
	} else {
		send_msg($buffwords[2],$nick . ': You need to be identified as an administrator to unload plugins.');
		return false;
	}
}
function module_load() {
	global $admin,$buffwords,$commands,$nick,$function_map;
	if ($admin) {
		$module = end(explode('/',$buffwords[4]));
		if (file_exists('./modules/' . $module . '.php')) {
			include('./modules/' . $module . '.php');
			$commands = array_merge($commands,$function_map[$module]);
			send_msg($buffwords[2],$nick . ': Plugin loaded.');
			return true;
		} else {
			send_msg($buffwords[2],$nick . ': Plugin not loaded. Does it exist?');
			return false;
		}
	} else {
		send_msg($buffwords[2],$nick . ': You need to be identified as an administrator to load plugins.');
		return false;
	}
}
function module_reload() {
	if (module_unload()) {
		return module_load();
	} else {
		return false;
	}
}
?> 
