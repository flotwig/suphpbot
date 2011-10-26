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
$settings = parse_ini_file('ircconfig.ini');
// add some essential command mappings
$commands = array(
	'load'=>'module_load',
	'unload'=>'module_unload',
	'list'=>'command_list'
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
			$channel = $buffwords[2];
			$bw = $buffwords;
			$bw[0]=NULL; $bw[1]=NULL; $bw[2]=NULL; $bw[3]=NULL;
			$arguments = trim(implode(' ',$bw));
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
					send_msg($buffwords[2],'You are ' . $nick . '.');
				} elseif ($command=='echo') {
					if ($admin) {
						$channel = $buffwords[2];
						$buffwords[0]=NULL; $buffwords[1]=NULL; $buffwords[2]=NULL; $buffwords[3]=NULL;
						$echo = trim(implode(' ',$buffwords));
						send_msg($channel,'' . $echo);
					} else {
						send_msg($buffwords[2],'You don\'t have the permissions required to execute this command.');
					}
				} elseif ($command=='quit'||$command=='end'||$command=='close') {
					if ($admin) {
						send('QUIT :' . $settings['quitmsg']);
						die();
					} else {
						send_msg($buffwords[2],'If you really want to quit so bad, maybe you should identify as an admin first!');
					}
				} else {
				if (function_exists($commands[$command])) {
					call_user_func($commands[$command]);
				} else {
					send_msg($buffwords[2],'' . $command . ' is not a valid command. Maybe you need to load a plugin?');
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
	global $nick,$settings;
	// Let's chunk up the message so it all gets sent.
	$message = str_split($message,intval($settings['maxlen']));
	var_dump($message);
	if (count($message)>intval($settings['maxsend'])) {
		// We want to use maxsend-1 in this situation because we'll be appending an error telling the user what
		// exactly happened to the rest of their output.
		$message = array_slice($message,0,intval($settings['maxsend']-1));
		var_dump($message);
		$message[] = 'The output for your command was too long to send fully.';
		var_dump($message);
	}
	var_dump($message);
	foreach ($message as $msg) {
		send ('PRIVMSG ' . $target . ' :' . $nick .  ': ' . $msg);
	}
}
function module_unload() {
	global $admin,$buffwords,$commands,$nick,$function_map;
	if ($admin) {
		$module = end(explode('/',$buffwords[4]));
		if (is_array($function_map[$module])) {
			$commands = array_diff($commands,$function_map[$module]);
			send_msg($buffwords[2],'Module unloaded successfully!');
			return true;
		} else {
			send_msg($buffwords[2],'Module not loaded.');
			return false;
		}
	} else {
		send_msg($buffwords[2],'You need to be identified as an administrator to unload plugins.');
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
			send_msg($buffwords[2],'Plugin loaded.');
			return true;
		} else {
			send_msg($buffwords[2],'Plugin not loaded. Does it exist?');
			return false;
		}
	} else {
		send_msg($buffwords[2],'You need to be identified as an administrator to load plugins.');
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
function command_list() {
	global $commands,$buffwords,$nick;
	foreach ($commands as $command => $function) {
		$ocomm[] = $command;
	}
	$ocomm = implode(', ',$ocomm);
	send_msg($buffwords[2],'' . $ocomm);
}
// borrowed from gtoxic of avestribot, who borrowed it from somebody else...
function write_php_ini($array, $file) {
	$res = array();
	foreach($array as $key => $val) {
		if(is_array($val)) {
			$res[] = "[$key]";
			foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
		} else {
			$res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
		}
	}
	$res = implode("\r\n", $res);
	$res = '; IRC bot config file
	; For more info, check the README' . "\r\n" . $res;
}
?> 
