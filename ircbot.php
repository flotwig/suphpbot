<?php
/*
	suphpbot - A modular, procedural IRC bot written entirely in PHP.
	https://github.com/flotwig/suphpbot
	(c)2011 Zachary Bloomquist zbloomq@live.com http://za.chary.us/
*/
if (PHP_SAPI !== 'cli') { die('This script can\'t be run from a web browser.'); }
set_time_limit(0);
ini_set('error_reporting',E_ALL-E_NOTICE);
$config = 'ircconfig.ini';
load_settings();
if (count(getopt(NULL,array('running')))<1) {
	fork_bot();
	die();
}
shell_send('Script started.');
define('START_TIME',time()); // so we can have core::uptime
define('IRC_VERSION','suphpbot version 0.3b - https://github.com/flotwig/suphpbot');
define('C_CTCP', chr(1));
// thanks, tutorialnut.com, for the control codes!
define('C_BOLD', chr(2));
define('C_COLOR', chr(3));
define('C_ITALIC', chr(29));
define('C_REVERSE', chr(22));
define('C_UNDERLINE', chr(31)); 
// hooks in the house! woo woo
$hooks = array('data_in'=>array(),
	'data_out'=>array(),
	'ctcp_in'=>array(),
	'ctcp_out'=>array());
// preload some modules
$commands = array();
$help = array();
$loaded_modules = array();
$strikes = array();
foreach ($premods as $premod) {
	load_module($premod);
}
$lastsent = array(); // Array of timestamps for last command from nicks - helps prevent flooding
$bnick = $settings['nick'];
$tries = 0;
while (1) {
	$tries++;
	$socket = @fsockopen($settings['server'], $settings['port'], $errno, $errstr, 20);
	if (!$socket) {
		shell_send('Unable to connect! Retrying in ' . round(pow(5,.5*$tries)) . ' seconds...');
	} else {
		stream_set_blocking($socket, 1); // we fix the dreaded 100% CPU issue
		if ($settings['pass']!=='') {
			send('PASS ' . $settings['pass']);
		}
		send('USER ' . $settings['ident'] . ' 8 * :' . $settings['realname']);
		send('NICK ' . $bnick);
		while (!feof($socket)) {
			$buffer = fgets($socket);
			$buffer = str_replace(array("\n","\r"),'',$buffer);
			if (strlen($buffer)>0) {	// we don't want to process anything if there's no new data. derp	
				$buffer = xtrim($buffer); // get rid of doubles
				$admin = FALSE;
				$buffwords = explode(' ',$buffer);
				$nick = explode('!',$buffwords[0]);
				$nick = substr($nick[0],1);
				$channel = $buffwords[2];
				if ($channel==$settings['nick']) {
					$in_convo = TRUE;
					$channel = $nick;
				} else {
					$in_convo = FALSE;
				}
				$hostname = end(explode('@',$buffwords[0]));
				$hostmask = $hostname;
				$bw = $buffwords;
				$bw[0]=NULL; $bw[1]=NULL; $bw[2]=NULL; $bw[3]=NULL;
				$arguments = trim(implode(' ',$bw));
				$args = explode(' ',$arguments);
				$ignore = explode(',',$settings['ignore']);
				if (!in_array($hostname,$ignore)) {
					call_hook('data_in');
					shell_send($buffer,'IN');
					if ($buffwords[1]=='002') {
						// The server just sent us something. We're in.
						// usermodes are important
						if (!empty($settings['automode'])) {
							send('MODE ' . $bnick . ' ' . $automode);
						}
						if ($settings['nickserv_pass']!=='') {
							// Let's identify before we join any channels.
							send_msg($settings['nickserv_nick'],'IDENTIFY ' . $settings['nickserv_pass']);
						}
						$channels = explode(',',$settings['channels']);
						foreach ($channels as $channel) {
							send('JOIN ' . trim($channel));
						}
					} elseif ($buffwords[1]=='433') {
						// Nick collision! a waooo
						$bnick = $settings['nick'] . '_' . mt_rand(100,999);
						send('NICK ' . $bnick);
					} elseif ($buffwords[0]=='PING') {
						send('PONG ' . str_replace(array("\n","\r"),'',end(explode(' ',$buffer,2))));
					} elseif (($buffwords[1]=='PRIVMSG'||$buffwords[1]=='NOTICE')&&$in_convo&&ord(trim(substr($buffwords[3],1)))==1) {
						// We're in a CTCP. Act like it.
						// acc to http://www.irchelp.org/irchelp/rfc/ctcpspec.html
						$command = trim(substr($buffwords[3],2)); // Let's crop out the first two characters of what they sent, because it's just a colon and a C_CTCP.
						$command = strtoupper(rtrim($command,C_CTCP)); // Now we have to take off any trailing C_CTCPs
						$arguments = rtrim($arguments,C_CTCP); // Yes, the arguments too.
						call_hook('ctcp_in'); // we just got a ctcp, anybody want to hook up?
						if ($command=='VERSION'||$command=='FINGER'||$command=='USERINFO') {
							send_ctcp($channel,$command . ' ' . IRC_VERSION);
						} elseif ($command=='PING') {
							send_ctcp($channel,$command . ' ' . time());
						} elseif ($command=='TIME') {
							send_ctcp($channel,$command . ' ' . date('D M d H:i:s Y T'));
						} elseif ($command=='ERRMSG') {
							// I don't really understand this one, so Imma just echo, 'kay
							send_ctcp($channel,$command . ' ' . $arguments);
						} elseif ($command=='SOURCE') {
							send_ctcp($channel,$command . ' For a copy of me, visit https://github.com/flotwig/suphpbot');
						} elseif ($command=='CLIENTINFO') {
							send_ctcp($channel,$command . ' I know these CTCP commands: PING TIME ERRMSG SOURCE CLIENTINFO VERSION FINGER USERINFO');
						}
					} elseif ($buffwords[1]=='PRIVMSG'&&((substr($buffwords[3],1,strlen($settings['commandchar']))==$settings['commandchar'])||$in_convo)) {
						if ($in_convo) {
							$command = trim(substr($buffwords[3],1));
						} else {
							$command = trim(substr($buffwords[3],2));
						}
						$command = strtolower($command);
						$blocked = explode(',',$settings['blockedcommands']);
						if ($lastsent[$hostname]<(time()-$settings['floodtimer'])||$admin) { // we let admins flood the bot lul
							$lastsent[$hostname]=time();
							if (in_array($hostname,$ignore)) {
								// do nothing - we're ignoring them :p
							} else {
								if (in_array($command,$blocked)) {
									send_msg($channel,$command . ' is a blocked command. Contact a bot administrator for guidance.');
								} elseif (function_exists($commands[$command])) {
									call_hook('command_' . $command);
									call_user_func($commands[$command]);
								} else {
									send_msg($channel,$command . ' is not a valid command. Maybe you need to load a plugin?');
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
								send_msg($channel,'Hi, you\'ve been ignored by the bot for flooding! Congratulations! Contact a bot administrator for guidance.');
								$strikes[$hostmask]=0;
							}
						}
					}
				}
			}
		}
	}
	sleep(round(pow(5,.5*$tries))); // reconnecting too fast like woah
}
// much thanks to gtoxic of avestribot for helping me realize my stupid mistake here
function send($raw) {
	global $socket;
	call_hook('data_out');
	fwrite($socket,"{$raw}\n\r");
	shell_send($raw,'OUT');
}
function send_msg($target,$message) {
	global $nick,$settings;
	// Let's chunk up the message so it all gets sent.
	$message = str_split($message,intval($settings['maxlen']));
	if (count($message)>intval($settings['maxsend'])) {
		// We want to use maxsend-1 in this situation because we'll be appending an error telling the user what
		// exactly happened to the rest of their output.
		$message = array_slice($message,0,intval($settings['maxsend']-1));
		$message[] = 'The output for your command was too long to send fully.';
	}
	foreach ($message as $msg) {
		if ($settings['censor_output']==1) {
			// so we don't hurt somebody's feelings with our wizard swears
			$badwords = explode(',',$settings['censor_badwords']);
			send ('PRIVMSG ' . $target . ' :' . fx('BOLD',$nick . ': ') . xtrim(str_replace($badwords,$settings['censor_word'],$msg)));
		} else {
			send ('PRIVMSG ' . $target . ' :' . fx('BOLD',$nick . ': ') . xtrim($msg)); // we use xtrim so that we don't send out funky whitespace
		}
	}
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
	global $settings,$premods,$admins,$ignore,$config;
	$settings = parse_ini_file($config);
	$premods = explode(',',$settings['module_preload']);
	$admins = explode(',',$settings['admins']);
	$ignore = explode(',',$settings['ignore']);
}
function call_hook($hook) {
	global $hooks;
	$hook = $hooks[$hook];
	foreach ($hook as $hookah) {
		call_user_func($hookah);
	}
}
function shell_send($message,$type='NOTE') {
	echo '[' . date('H:i:s m-d-Y') . '] [' . $type . ']' . "\t" . $message . "\n";
}
function send_ctcp($target,$command) {
	call_hook('ctcp_out');
	send('NOTICE ' . $target . ' :' . fx('CTCP',$command,TRUE));
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
	$commands = array_diff($commands,$function_map[$modname]);
	unset($function_map[$modname]);
	if (is_array($hook_map[$modname])) {
		foreach ($hook_map[$modname] as $hook_name => $hook_function) {
			$hooks[$hook_name] = array_diff($hooks[$hook_name],array($hook_function));
		}
	}
	if (is_array($help_map[$modname])) {
		$help = array_diff($help,$help_map[$modname]);
	}
	$loaded_modules = array_diff(array($modname),$loaded_modules);
}
function fx($filter,$text,$ignorecc=FALSE) {
	global $settings;
	if (defined('C_' . strtoupper($filter)) && ($settings['control_codes']!==0&&!$ignorecc)) {
		return constant('C_' . strtoupper($filter)) . $text . constant('C_' . strtoupper($filter));
	} else {
		return $text;
	}
}
function fork_bot() {
	global $settings;
	if ($settings['logging']) {
		shell_send(shell_exec('echo "php ' . basename(__FILE__) . ' --running >> ' . $settings['logfile'] . '" | at now'));
		shell_send('Forked ' . basename(__FILE__) . ' into background using at. Logging to raw.log.');
	} else {
		shell_send(shell_exec('echo "php ' . basename(__FILE__) . ' --running" | at now'));
		shell_send('Forked ' . basename(__FILE__) . ' into background using at.');
	}
}
?> 
