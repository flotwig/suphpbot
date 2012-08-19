<?php
//** Author: StompingBrokenGlass (StompingBrokenGlass@Gmail.com) **//
/*
//   Description: Silly Stuff ported from the old bot for snoonet's
//   #Metal Channel, Based on Kwamaking's work and HiddenKnowledge's 
//   example plugin.
//
//   Licence: Public Domain.
*/

$scriptname = str_replace(".php","",basename(__FILE__));

$function_map[$scriptname] = array(
    'police' => 'silly_five_O',
    'hello' => 'silly_hello',
    'false' => 'user_false'
);

$help_map[$scriptname] = array (
    'police' => 'Prints an ASCII of a police car, also can be called by dialing 911',
    'hello' => 'Prints Hello world',
	'false' => 'Checks if a user is false.',
	'sed'=>'Use sed command. I recommend reading the linux manual for Sed. This has very limited functionality: s/param/replace'
);

$hook_map[$scriptname] = array (
    'data_in' => 'silly_sniffer',

);

function silly_hello () {
   global $channel;

   $msg ="Hello World!";

   send_msg($channel,$msg);
}

function silly_five_O () {
    global $channel,$nick,$settings;
	$allowed = explode(',',$settings['allowed_users']);
	if (in_array($nick,$allowed)) {
		// Drawing the car using ACSII

		$line1 = "..........__\_@@\@__";
		$line2 = "..... ___//___?____\\________";
		$line3 = "...../--o-METAL-POLICE------@}";
		$line4 = "....`=={@}=====+===={@}--- ' WHAT SEEMS TO BE THE PROBLEM HERE?";


		//using raw send to avoid adding "nick:" infront of the message.

		send('PRIVMSG ' . $channel . ' :' . $line1);
		send('PRIVMSG ' . $channel . ' :' . $line2);
		send('PRIVMSG ' . $channel . ' :' . $line3);
		send('PRIVMSG ' . $channel . ' :' . $line4);
	}
	else {
		$line1 = "Calling the police on false pretenses is a crime...";
		send('PRIVMSG ' . $channel . ' :' . $line1);
	}
}

// sinffer for commands without the preceding command character,
// based on internets snarf function.

function silly_sniffer () {
    global $buffwords;
    $sniffed_command = strtolower(substr($buffwords[3],1));
    if ($sniffed_command == '911') {
         silly_five_O();
    }
	if ($sniffed_command == 'csi') {
         csi();
    } else {
		// Wanted to keep everything involving sed in it's own function.
		// I want all data_in to pound it like a man
		sed();
	}
}

// Checks if user is false
// uses lastfm module
function user_false() {
	global $args,$channel,$nick,$loaded_modules;
	if (!in_array("lastfm",$loaded_modules)) {
		return send_msg($channel,'lastfm module is required for this function.');
	}
	$artist = 'Judas Priest';
	$plays = 666; // if less than this, user false. 
	$user = get_lastfm_user($nick);
	if ($args[0]) { // if user specified user
		$user = get_lastfm_user($args[0]);
		if (!$user) {
			$user = $args[0];
		}
	} 
	if (!$user) { // if user is set and no specified nick
		return nothing_met($channel);
	} 
	$data = get_lastfm_data('artist.getinfo','username=' . urlencode($user) . '&artist=' . urlencode($artist));
	if ($data['artist']['stats']['userplaycount']) {
		if ($data['artist']['stats']['userplaycount'] < $plays) {
			$str = '"' . $user . '" only has ' . $data['artist']['stats']['userplaycount'] . ' ';
			$str .= $data['artist']['name'] . ' plays.  "' . $user . '" is false. ';
			$str .= $plays - $data['artist']['stats']['userplaycount'] . ' more plays required to be trve.';
		} else {
			$str = '"' . $user . '"  has ' . $data['artist']['stats']['userplaycount'] . ' ';
			$str .= $data['artist']['name'] . ' plays.  "' . $user . '" is trve.';
		}
	} else {
		$str = '"' . $user . '" has never listened to ' . $artist . '. "' . $user . '" should leave the hall.';
	}
	send_msg($channel,$str);
}

function csi() {
    global $channel,$nick,$settings;
	$allowed = explode(',',$settings['allowed_users']);
	if (in_array($nick,$allowed)) {
		$line1 = '( •_•)>⌐■-■';
		$line2 = '(⌐■_■)';
		$line3 = 'YEAAAAHHHH!!!';
		send('PRIVMSG ' . $channel . ' :' . $line1);
		send('PRIVMSG ' . $channel . ' :' . $line2);
		send('PRIVMSG ' . $channel . ' :' . $line3);
	}
	else {
		$line1 = "Nope...";
		send('PRIVMSG ' . $channel . ' :' . $line1);
	}
}
/*
Sed - (loosely based on the linux command, comparatively broken functionality)
kwamaking - kwamaking@gmail.com - http://github.com/kwamaking
*/
$lines = array();
function sed() {
    global $buffer,$channel,$buffwords,$lines,$settings,$nick;
	$matched_strings = array();
	$buffer_limit = 50; // number of lines (per room) to keep in memory.
	// Don't fill our cache full of nonsense
	if ($channel[0]=='#') {
		// This magical multidimensional array stores our chat buffer for instant retrieval.
		$lines[$channel][] = $buffer; 
	}
	if (count($lines[$channel]) > $buffer_limit) { // We can't have that much scrollback, we have memory to think about here.
		array_splice($lines[$channel],0,count($lines[$channel])-$buffer_limit);
	}
	// Strips unecessary text, formats our sed command properly.
	$sed_command = explode('/',substr(join(' ',array_slice($buffwords,3)),1));
	// All three parameters for sed must be met. s/item/item
	if ($sed_command['0'] == 's' && isset($sed_command['1']) && isset($sed_command['2'])) {
		$allowed = explode(',',$settings['allowed_users']);
		if (in_array($nick,$allowed)) {
			if (isset($sed_command['4'])) { // Argument is too damn big
				return send('PRIVMSG ' . $channel . ' :' . $nick . ': This has very limited functionality. RTFM.');
			}
			$param = preg_quote($sed_command['1']);
			$replace = $sed_command['2'];
			foreach ($lines[$channel] as $matches) {
				$matches = substr($matches,1); // stripping that pesky colon.
				if (preg_match('/ACTION/',$matches)) {
					preg_replace('/ACTION/','* ',$matches);
				}
				// Check if parameter matches any string in the buffer and only if it's PRIVMSG.
				if (preg_match('/' . $param . '/i',$matches)) { 
					$result = preg_replace("/" . $param . "/i",$replace,$matches);
					array_push($matched_strings, $result);
				} 
			}
			// Without this the last result will be the command itself, so we definitely don't want that.
			array_pop($matched_strings);
			if (empty($matched_strings)) { // if we empty the array, return nothing. 
				return;
			} else {
				$matched_string = end($matched_strings);
				$name = strstr($matched_string,'!', true); // extract name
				$quote = substr(strstr($matched_string,' :'),2); // extract matched quote
				send('PRIVMSG ' . $channel . ' :' . $name . ' >> ' . $quote);
			}
		}
		else {
			return;
		}
		array_pop($lines[$channel]); // removes sed command
		array_push($lines[$channel],':' . $name . '! :' . $quote); // Add changed sed to buffer.
	}
}
?>
