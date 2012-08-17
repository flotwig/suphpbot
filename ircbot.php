<?php
/*
	suphpbot - A modular, procedural IRC bot written entirely in PHP.
	https://github.com/flotwig/suphpbot
	(c)2011 Zachary Bloomquist zbloomq@live.com http://za.chary.us/
*/
if (PHP_SAPI !== 'cli') { die('This script can\'t be run from a web browser.'); }
define('START_TIME',time()); // so we can have core::uptime
set_time_limit(0); // so your bot doesn't die after 30 seconds
date_default_timezone_set(date_default_timezone_get()); // because PHP can be a bitch sometimes
ini_set('error_reporting',E_ALL-E_NOTICE);
if (count(getopt('c:'))>0) {
	$config = getopt('c:');
	$config = $config['c'];
} else {
	$config = 'ircconfig.ini';
}
load_settings();
if (count(getopt(NULL,array('running')))<1) {
	fork_bot();
	die();
}
shell_send('Script started.');
define('IRC_VERSION',$GLOBALS['settings']['version']);
// let's load up our interface
$interface = $GLOBALS['settings']['interface'];
if (empty($interface)) {
	$interface = 'irc';
}
$required_functions = array('interface_connect','interface_startup','interface_retrieve_buffer','interface_loop_extraction','interface_loop_upkeep','interface_loop_command','send','send_msg');
if (!file_exists('./interfaces/' . $interface . '.php')) {
	shell_send('The interface "' . $interface . '" was not found at ./interfaces/' . $interface . '.php.','FATAL');
	die();
}
require_once('./interfaces/' . $interface . '.php');
foreach ($required_functions as $required_function) {
	if (!function_exists($required_function)) {
		shell_send('The interface "' . $interface . '" does not specify the core function "' . $required_function . '".','FATAL');
	}
}
// hooks in the house! woo woo
$hooks = array('data_in'=>array(),
	'data_out'=>array());
// preload some modules
$commands = array();
$help = array();
$loaded_modules = array();
$strikes = array();
foreach ($premods as $premod) {
	$premod = trim($premod);
	load_module($premod);
}
$lastsent = array(); // Array of timestamps for last command from nicks - helps prevent flooding
$bnick = $settings['nick'];
$tries = 0;
$socket = NULL; // doot doot
while (1) {
	$tries++;
	interface_connect(); // refer to interface file for connection command, yo
	if (!$socket) {
		shell_send('Unable to connect! Retrying in ' . round(pow(5,.5*$tries)) . ' seconds...');
	} else {
		interface_startup();
		while (!feof($socket)) {
			interface_retrieve_buffer();
			interface_loop_extraction();
			if (!in_array($hostname,$ignore)) {
				call_hook('data_in');
				shell_send($buffer,'IN');
				interface_loop_upkeep();
				$command = interface_loop_command();
				if ($command) {
					$blocked = explode(',',$settings['blockedcommands']);
					if ($lastsent[$hostname]<(time()-$settings['floodtimer'])||$admin) { // we let admins flood the bot lul
						$lastsent[$hostname]=time();
						if (in_array($hostname,$ignore)) {
							// do nothing - we're ignoring them :p
						} else {
							if (in_array($command,$blocked)) {
								send_msg($nick,$command . ' is a blocked command. Contact a bot administrator for guidance.',1);
							} elseif (function_exists($commands[$command])) {
								call_hook('command_' . $command);
								call_user_func($commands[$command]);
							} else {
								send_msg($nick,$command . ' is not a valid command. Maybe you need to load a plugin?',1);
							}
						}
					} else {
						// they dun goofed - bot spamming? not on my watch, let's add a strike to they
						if (!isset($strikes[$hostmask])) {
							$strikes[$hostmask] = 1;
						} else {
							$strikes[$hostmask]++;
						}
						if ($strikes[$hostmask]==$settings['strikes']) {
							$ignore[] = $hostmask;
							$settings['ignore'] = implode(',',$ignore);
							save_settings($settings,$config);
							send_msg($nick,'Hi, you\'ve been added to my ignore list for flooding! Congratulations! Contact a bot administrator for guidance.',1);
							$strikes[$hostmask]=0;
						}
					}
				}
			}
		}
	}
/*
Uncomment this if you want it to stop attempting to reconnect.
If you're gone for extended periods of time it's generally a good idea to allow it to reconnect indefinitely.

	sleep(round(pow(5,.5*$tries))); // reconnecting too fast like woah. exponential growth is quick to occur. if your irc server is down for a day and this bot is trying to reconnect the whole time, you should probably just restart the process :p
	
*/
}
// borrowed from gtoxic of avestribot, who borrowed it from somebody else...
function save_settings($array, $file) {
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
	$res = '; IRC bot config file' . "\r\n" . '; For more info, check the README' . "\r\n" . $res;
	file_put_contents($file,$res);
}
// supertrim function by jose cruz
// josecruz at josecruz dot com dot br
function xtrim($str) {
    $str = trim($str);
    for($i=0;$i < strlen($str);$i++) {
        if(substr($str, $i, 1) != " ") {
            $ret_str .= trim(substr($str, $i, 1));
        } else  {
            while(substr($str,$i,1) == " ") {
                $i++;
            }
            $ret_str.= " ";
            $i--; // ***
        }
    }
    return $ret_str;
} 
function load_settings() {
	global $settings,$premods,$ignore,$config;
	$settings = parse_ini_file($config);
	$premods = explode(',',$settings['module_preload']);
	$ignore = explode(',',$settings['ignore']);
}
function call_hook($hook) {
	global $hooks;
	$hook = $hooks[$hook];
	if (is_array($hook)) {
		foreach ($hook as $hookah) {
			call_user_func($hookah);
		}
	}
}
function shell_send($message,$type='NOTE') {
	echo '[' . date('H:i:s m-d-Y') . '] [' . $type . ']' . "\t" . $message . "\n";
}
function load_module($modname) {
	global $commands, $function_map, $hook_map, $hooks;
	global $help, $help_map, $loaded_modules;
	$load = @include_once('./modules/' . trim($modname) . '.php');
	if ($load) {
		$commands = array_merge($commands,$function_map[trim($modname)]);
		if (isset($hook_map[trim($modname)])) {
			foreach ($hook_map[trim($modname)] as $hook_id => $hook_function) {
				$hooks[$hook_id][] = $hook_function;
			}
		}
		$loaded_modules[] = $modname;
		if (is_array($help_map[trim($modname)])) {
			$help = array_merge($help,$help_map[trim($modname)]);
		}
	}
}
function unload_module($modname) {
	global $commands,$function_map,$hook_map,$help_map,$loaded_modules,$hooks,$help;
	$function_map = array_diff($function_map,array($modname));
	if (is_array($hook_map[$modname])) {
		foreach ($hook_map[$modname] as $hook_name => $hook_function) {
			$hooks[$hook_name] = array_diff($hooks[$hook_name],array($hook_function));
		}
	}
	if (is_array($help_map[$modname])) {
		$help = array_diff($help,$help_map[$modname]);
	}
	$loaded_modules = array_diff($loaded_modules,array($modname));
}
function fx($filter,$text,$ignorecc=FALSE) {
	global $settings;
	if (defined('C_' . strtoupper($filter)) && $settings['control_codes']!==0 && $ignorecc) {
		return constant('C_' . strtoupper($filter)) . $text . constant('C_' . strtoupper($filter));
	} else {
		return $text;
	}
}
function fork_bot() {
	global $settings,$config;
	if ($settings['logging']) {
		shell_send(shell_exec('echo "php ' . basename(__FILE__) . ' --running -c ' . $config . ' >> ' . $settings['logfile'] . '" | at now'));
		shell_send('Forked ' . basename(__FILE__) . ' into background using at. Logging to raw.log.');
	} else {
		shell_send(shell_exec('echo "php ' . basename(__FILE__) . ' --running -c ' . $config . '" | at now'));
		shell_send('Forked ' . basename(__FILE__) . ' into background using at.');
	}
}
?> 
