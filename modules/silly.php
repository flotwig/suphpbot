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
	'false' => 'Checks if a user is false.'
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
    global $channel,$nick;
	$allowed = array('BrutalN00dle','kwamaking','Skuld','StompinBroknGlas','Shamed','Mike','thegauntlet','nakedcups','Fenriz','BrutalMobile','PenetratorHammer');
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
}

// Checks if user is false

function user_false() {
	global $args,$channel,$nick;
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

?>
