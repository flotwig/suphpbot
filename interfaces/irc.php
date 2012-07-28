<?php
// IRC interface for suphpbot
/*
	FUNCTIONS FOR INTERFACE CONNECTIONS, UPKEEP AND PARSING
*/
function interface_connect() { // should initialize global variable $socket as a file handle
	global $socket,$settings;
	if ($settings['ssl']==1) {
		$socket = @fsockopen('ssl://' . $settings['server'], $settings['port'], $errno, $errstr, 20);
	} else {
		$socket = @fsockopen($settings['server'], $settings['port'], $errno, $errstr, 20);
	}
}
function interface_startup() { // function to run if the connection is established. set $bnick to the bot's username/nick
	global $socket,$settings,$bnick;
	stream_set_blocking($socket, 1); // we fix the dreaded 100% CPU issue
	if ($settings['pass']!=='') {
		send('PASS ' . $settings['pass']);
	}
	send('USER ' . $settings['ident'] . ' 8 * :' . $settings['realname']);
	send('NICK ' . $bnick);
}
function interface_retrieve_buffer() { // $buffer = fgets($socket);
	global $buffer,$socket;
	$buffer = fgets($socket);
}
function interface_loop_extraction() { // function to extract data from the current global, $buffer into a bunch of vars
	global $bnick,$buffer,$admin,$buffwords,$nick,$channel,$in_convo,$hostname,$bw,$arguments,$args,$ignore,$settings,$hostmask;
	$buffer = str_replace(array("\n","\r"),'',$buffer);
	$buffer = xtrim($buffer); // get rid of doubles
	$admin = FALSE;
	$buffwords = explode(' ',$buffer);
	$nick = explode('!',$buffwords[0]);
	$nick = substr($nick[0],1);
	$channel = $buffwords[2];
	if ($channel==$bnick) {
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
}
function interface_loop_upkeep() { // do what you need to do to keep the connection alive and in good health here. in IRC, we check for nick collisions and pings and such
	global $buffwords,$bnick,$settings,$buffer;
	if ($buffwords[1]=='002') {
		// The server just sent us a connection message. We're in.
		sleep(1); // because IRC is serious business
		// usermodes are important
		if (!empty($settings['automode'])) {
			send('MODE ' . $bnick . ' ' . $settings['automode']);
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
		// Nick collision! a waooo. fix $bnick and renick, pronto!
		$bnick = $settings['nick'] . '_' . mt_rand(100,999);
		send('NICK ' . $bnick);
	} elseif ($buffwords[0]=='PING') {
		send('PONG ' . str_replace(array("\n","\r"),'',end(explode(' ',$buffer,2))));
	}
}
function interface_loop_command() { // return the string with the command name if we need to run a command, otherwise boolean FALSE
	global $buffwords,$settings,$in_convo;
	if ($buffwords[1]=='PRIVMSG'&&((substr($buffwords[3],1,strlen($settings['commandchar']))==$settings['commandchar'])||$in_convo)) {
		if ($in_convo) {
			$command = trim(substr($buffwords[3],strlen($settings['commandchar'])));
		} else {
			$command = trim(substr($buffwords[3],strlen($settings['commandchar'])+1));
		}
		$command = strtolower($command);
		return $command;
	} else {
		return FALSE;
	}
}
/*
	REQUIRED FUNCTIONS FOR GENERAL SCRIPT USAGE
*/
function send($raw) { // send raw data through the socket
	global $socket;
	call_hook('data_out');
	fwrite($socket,"{$raw}\n\r");
	shell_send($raw,'OUT');
}
function send_msg($target,$message,$type=0) { // format data all pretty-like and send it out
	global $nick,$settings;
	$types = array('PRIVMSG','NOTICE'); // not everything needa be sent to the channel
	// Let's chunk up the message so it all gets sent.
	$message = str_split($message,intval($settings['maxlen']));
	if (count($message)>intval($settings['maxsend'])) {
		// We want to use maxsend-1 in this situation because we'll be appending an error telling the user what
		// exactly happened to the rest of their output.
		$message = array_slice($message,0,intval($settings['maxsend']-1));
		$message[] = 'The output for your command was too long to send fully.';
	}
	$badwords = explode(',',$settings['censor_badwords']);
	foreach ($message as $msg) {
		if ($settings['censor_output']==1) {
			$msg = str_replace($badwords,$settings['censor_word'],$msg);
		}
		$msg = str_replace(array('%nick%','%message%'),array(fx('BOLD',$nick),xtrim($msg)),$settings['message_style']);
		send($types[$type] . ' ' . $target . ' :' . $msg);
	}
}
function noperms() { // we gonna use this to notify people trying to do things they shouldn't be doing
	global $nick;
	send_msg($nick,'Sorry, you don\'t have the appropriate permissions for that.',1);
}
/*
	WRAP CONSTANTS FOR FX()
*/
define('C_BOLD', chr(2));
define('C_COLOR', chr(3));
define('C_ITALIC', chr(29));
define('C_REVERSE', chr(22));
define('C_UNDERLINE', chr(31));
/*
	MISC. INTERFACE-SPECIFIC FUNCTIONS
*/
function get_response($command,$terminating,$musthave) { // this just returns an array with all the responses to a particular command with a particular precode
	global $socket;
	send($command);
	$response = array();
	while(1) {
		$buffer = trim(fgets($socket));
		$bw = explode(' ',$buffer);
		if ($bw[1]==$terminating) {
			break;
		}
		if (strlen($buffer)>0&&$bw[1]==$musthave) {
			$response[] = $buffer;
		}
	}
	return $response;
}
