<?php
// lastfm stuffs
// kwamaking (kwamaking@gmail.com) http://github.com/kwamaking
// this requires the internets module, as it uses a few functions from it.  
define('LASTFM_API_KEY','362a86ba35347c41a363b46dc32e333e');
$function_map['lastfm'] = array( 
	'setuser'=>'set_lastfm_user',
	'deluser'=>'del_lastfm_user',
	'np'=>'now_playing',
	'top'=>'top_artists',
	'band'=>'artist_info',
	'compare'=>'compare_users',
	'plays'=>'plays',
	'whois'=>'whois',
	'genre'=>'genre',
);
$help_map['lastfm'] = array(
	'setuser'=>'Set your last.fm username.',
	'deluser'=>'Removes your last.fm username.',
	'np'=>'Gets now playing of given user.',
	'top'=>'Displays top 8 artists in a given 7 day period of setuser or given user.',
	'band'=>'Returns some basic information about specified band.',
	'compare'=>'Compares register user with specified user, or two separate users.',
	'plays'=>'Displays user plays by given band, you can also view plays of given band by users other than yourself. I.E. plays username band.',
	'whois'=>'Returns users associated with Last.fm username, or any given nick.',
	'genre'=>'Returns brief description and similar genres and tags of given genre or tag.',
);
function nothing_met($channel) {
	$str = 'Either nick isn\'t associated or you need to specify arguments.';
	return send_msg($channel,$str);
}
// Adds nick and username association to cache file
function set_lastfm_user() {
	global $args,$channel,$nick;
	if (!$args[0]) {
		$str = "Please provide your last.fm username.";
	} else {
		$file = './data/lastfm_data.json';
		$data = get_lastfm_data('user.getrecenttracks','user=' . urlencode($args[0]) . '&limit=1');
		if ($data['recenttracks']['track']) { // Check for recent track, else user doesn't exist.
			$fc = file_get_contents($file);
			$users = json_decode($fc, true); // true returns an array
			if ($users['users'][$nick]) {
				$str = 'Your nick is already associated with ' . $users['users'][$nick]['lastfmuser'];
				$str .= ' If you want to change it, first delete your user.  See help for more information.';
			} else {
				// json format: {"users":{"nick":{"lastfmuser":"username"}}}
				$users['users'][$nick] = array('lastfmuser'=>$args[0]);
				file_put_contents($file, json_encode($users));
				$str = $nick . ' is now associated with ' . $args[0] . '.';
			}
		} else {
			$str = $args[0] . ' either doesn\'t exist or has no recent plays.  Failed to create association.';
		}
		send_msg($channel,$str);
	}
}
function del_lastfm_user() {
	global $channel,$nick;
	$file = './data/lastfm_data.json';
	$fc = file_get_contents($file);
	$users = json_decode($fc, true); // true returns an array
	if ($users['users'][$nick]) {
		unset($users['users'][$nick]);
		file_put_contents($file, json_encode($users));
		$str = 'Your username has been removed.';
	} else {
		$str = 'No association found with your nick!';
	}
	send_msg($channel,$str);
}
// This will return the user if an entry exists
function get_lastfm_user($nick) {
	global $channel;
	$file = './data/lastfm_data.json';
	$fc = file_get_contents($file);
	$users = json_decode($fc, true);
	foreach ($users['users'] as $key => $lastfm) { 
		if (preg_match('/' .$nick . '/i', $key)) { // Nicks can be case insensitive.
			return $lastfm['lastfmuser'];
		} 
	}
	return;
}
// This grabs the json from the last.fm api with given params
function get_lastfm_data($method,$paramstr) {
	global $loaded_modules,$channel;
	// lets make sure internets module is loaded before we go making calls to it's functions
	if (in_array("internets",$loaded_modules)) {
		// uses a  function in the internets module,.
		// It already does what I want it to, so no need to re-write one.
		$def = @internets_get_contents('http://ws.audioscrobbler.com/2.0/?format=json&api_key='. LASTFM_API_KEY .'&method=' . $method . '&' . $paramstr);
		$def = json_decode($def,TRUE);
		return $def;
	} else {
		send_msg($channel,"This will not work without the internets module.");
		return false;
	}
}
// I wanted a second function for this since i'll be using it often
// Eventually I'll write this to the cache file
function get_top_tags($artist) {
	$def = get_lastfm_data('artist.gettoptags','artist=' . urlencode($artist));
	$tagamount = 0;
	$tags = array();
	if ($def['toptags']) { // Check if artist has top tags
		if (isset($def['toptags']['tag']['name'])) { // Checks for a single tag
			$tags = $def['toptags']['tag']['name'];
		} else { // handles Multiple tags
			foreach ($def['toptags']['tag'] as $tag) {
				if ($tagamount++ == 5) {
					break;
				}
				array_push($tags,$tag['name']);
			}
			$tags = join(', ',$tags);
		}
		return $tags;
	} else {
		return 0; // return false if no top tags
	}
}
// gets user now playing
function now_playing() {
	global $args,$channel,$nick;
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
	$data = get_lastfm_data('user.getrecenttracks','user=' . urlencode($user) . '&limit=1');
	$firstTrack = $data['recenttracks']['track'][0]; // smaller is better
	if ($firstTrack['@attr']['nowplaying']) {// if now playing is true, use track.getinfo and top tags methods 
		// this gives us additional information about the track
		$trackinfo = get_lastfm_data('track.getinfo','artist=' . urlencode($firstTrack['artist']['#text']) . '&track=' . urlencode($firstTrack['name']) . '&username=' . urlencode($user));
		$toptags = get_top_tags($firstTrack['artist']['#text']);
		$str =  ' "' . $user . '" is now playing '.$firstTrack['artist']['#text'];
		$str .= ' - ' . $firstTrack['name'];
		if ($firstTrack['album']['#text'])  { // check if album exists
			$str .= ' - ' . $firstTrack['album']['#text'];
		}
		if ($trackinfo['track']['duration']) { // check if duration exists
				$totalSeconds = $trackinfo['track']['duration'] / 1000; // duration contains 3 trailing 0's
				$minutes = floor($totalSeconds / 60); // minutes
				$seconds = $totalSeconds - ($minutes * 60); //  seconds
				if ($seconds < 10) { // Don't know if there is a better way to do this...
					$seconds = '0' . $seconds;
				}
				$str .= ' [' . $minutes . ':' . $seconds . ']';
		}  
		if ($trackinfo['track']['userplaycount']) { // check if userplaycount exists
			$str .= ' [playcount: ' . $trackinfo['track']['userplaycount'] . 'x';
			// if user has 'loved' the track, show a heart. :)	
			if ($trackinfo['track']['userloved']) {
				$str .= ' - ♥';
			}
			$str .= ']';
		}
		if ($toptags) { // Check if artist has toptags
			$str .= ' (' . $toptags . ')';	
		}
		if ($firstTrack['url']) { // Check if URL exists
			// internets_shorten_url taken from internets module
			$str .= ' (' . internets_shorten_url($firstTrack['url']) . ')';
		}	
	} else {
		if ($data['recenttracks']['track']) {
			// if user exists now playing isn't true, get last track played and time.
			$str = ' "' . $user . '" is not listening to anything right now.  Last played track is ';
			$str .= $data['recenttracks']['track']['artist']['#text'];
			$str .= ' - ' . $data['recenttracks']['track']['name'];
			$str .= ' on ' . $data['recenttracks']['track']['date']['#text'];
		} else {
			$str = $user . ' not found or error accessing last.fm data'; 
		}
	}
	send_msg($channel,$str);
}
// gets users top artists
function top_artists() {
	global $args,$channel,$nick;
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
	$data = get_lastfm_data('user.gettopartists','limit=8&period=7day&user=' . urlencode($user));
	if ($data['topartists']['artist'][0]['name']) {
		$artistarray = array();
		foreach ($data['topartists']['artist'] as $artist) {
			array_push($artistarray, $artist['name']);
		}
		$str .= ' top artists for "' . $user . '" (' . join(', ',$artistarray) . ')';
	} else {
		$str = 'Specified user has no recent top artists.';
	}
	send_msg($channel,$str);
}
// gets basic artist information
function artist_info() {
	global $arguments,$channel;
	if (!$arguments) {
		$str = 'Please specify an artist.';
	} else {
		$data = get_lastfm_data('artist.getinfo','limit=1&autocorrect=1&artist=' . urlencode($arguments));
		if ($data['artist']['name']) { // Check if artist exists
			$str = $data['artist']['name'] . ' have ' . number_format($data['artist']['stats']['playcount']) . ' plays and ';
			$str .= number_format($data['artist']['stats']['listeners']) . ' listeners.';
			if ($data['artist']['similar']) { // Check if similar artists exist
				$artistarray = array();
				foreach ($data['artist']['similar']['artist'] as $similar) {
					array_push($artistarray, $similar['name']);
				}
				$str .= ' Similar artists include: (' . join(', ',$artistarray) . ')';
			}
			$toptags = get_top_tags($data['artist']['name']);
			if ($toptags) { // Check if artist has toptags
				$str .= ' Tags: (' . $toptags . ')';	
			}
		} else {
			$str = 'Last.fm could not find artist: ' . $arguments;
		}
	}
	send_msg($channel,$str);
}
// Uses lastfm tasteometer to compare users
function compare_users() {
	global $args,$channel,$nick;
	$user = get_lastfm_user($nick);
	// This rather large block of code checks to see if given users match set users
	// I did this so users can compare with each other without knowing their usernames, just nicks.
	if ($args[1]) {
		$user = get_lastfm_user($args[0]);
		$user2 = get_lastfm_user($args[1]);
		if (!$user) {
			$user = $args[0];
		}
		if (!$user2) {
			$user2 = $args[1];
		}
	} else {
		$user2 = get_lastfm_user($args[0]);
		if (!$user2) {
			$user2 = $args[0];
		}
	}
	if ((!$user && !$args[0]) || ($user && !$args[0])) { // no user or args, or user but no args.
		return nothing_met($channel);
	} 
	$data = get_lastfm_data('tasteometer.compare','type1=user&type2=user&value1=' . urlencode($user) . '&value2=' . urlencode($user2));
	if ($data['comparison']['result']['score'] > 0) { // Make sure there are artists and there is at least something to compare
		$str = '"' . $user . '" vs "' . $user2 . '": ' . round($data['comparison']['result']['score']*100, 1) . '% - ';
		if ($data['comparison']['result']['artists']['artist'][1]['name']) { // Check for more than one common artist
			$resultarray = array();
			foreach ($data['comparison']['result']['artists']['artist'] as $common) {
				array_push($resultarray, $common['name']);
			}
			$str .= ' Common artists include: (' . join(', ',$resultarray) . ')';
		} else {
				$str .= ' One common artist: (' . $data['comparison']['result']['artists']['artist']['name'] . ')';		
		}
	} else {
		$str = 'There are no common artists between ' . $user . ' and ' . $user2 . '.';
	}
	send_msg($channel,$str);
}
// Gets user plays of given band, also given user if specified.

function plays() {
	global $args,$channel,$nick;
	$user = get_lastfm_user($nick);
	if (!$args[0] || !$user) { // No arguments or set user? no plays.
		return nothing_met($channel);
	} else {
		$artist = join(' ',$args);
	}
	$data = get_lastfm_data('artist.getinfo','autocorrect=1&username=' . urlencode($user) . '&artist=' . urlencode($artist));
	if ($data['artist']['name'] && $data['artist']['stats']['userplaycount']) { // Checks for playcount
		$str = '"' . $user . '" has ' . $data['artist']['stats']['userplaycount'];
		$str .= ' ' . $data['artist']['name'] . ' plays.';
	} else if ($data['artist']['name']) {
		$str = '"' . $user . '" has never listened to ' . $data['artist']['name'];
	} else {
		$str = 'Last.fm has no record of ' . $artist;
	}
	send_msg($channel,$str);
}
function whois() {
	global $args,$channel,$nick;
	if (!$args[0]) {
		$str = "Please provide a Last.fm username.";
	} else {
		$user = get_lastfm_user($args[0]);
		if (!$user) {
			$user = $args[0];
		}
		$file = './data/lastfm_data.json';
		$fc = file_get_contents($file);
		$users = json_decode($fc, true);
		$userarray = array();
		// I feel like this bit is pretty self explanatory
		foreach ($users['users'] as $key => $lastfm) {
			if ($lastfm['lastfmuser'] == $user) {
				array_push($userarray,$key);
			}
		}
		if ($userarray) { // if there are other users
			$str = ' "' . $user . '" is associated with: (';
			$str .= join(', ',$userarray);
			$str .= ')';
		} else {
			$str = 'Could not find any association for: ' . $user;
		}
	}
	send_msg($channel,$str);
}
function genre() {
	global $arguments,$channel,$args;
	$similar = get_lastfm_data('tag.getsimilar','tag=' . urlencode($arguments));
	$taginfo = get_lastfm_data('tag.getinfo','tag=' . urlencode($arguments));
	$tagartists = get_lastfm_data('tag.gettopartists','tag=' . urlencode($arguments));
	if (!$args[0]) {
		$str = 'Please include a genre or tag name.';
	} elseif (!is_array($taginfo)) { // just incase the response is not an array.
		$str = 'unexcpeted reponse for genre info, Contact admin';
	} else {
		if ($taginfo['tag']['name']) { // Grab tagname... 
			$str = '"' . $taginfo['tag']['name'] . '" - ';
		}
		if (is_array($taginfo['tag']['wiki'])) { // Check if tag description exists.
			$str .= html_entity_decode(substr(strip_tags($taginfo['tag']['wiki']['summary']), 0, 200)) . '...';
		}
		if (is_array($similar)) {
			if ($similar['similartags']['tag'] && !$similar['similartags']['#text']) { // Check if there are similar tags.
				$tagamount = 0;
				$tags = array();
				foreach ($similar['similartags']['tag'] as $tag) {
					if ($tagamount++ == 5) {
						break;
					}
					array_push($tags,$tag['name']);
				}
				$str .= ' Similar tags: (' . join(', ',$tags) . ')';
			}
		} else { // unexpected response from the server
			$str .= ' Similar tags weren\'t found ';
		}
		if (is_array($tagartists)) {
			if ($tagartists['topartists']['artist']) { // Check if there are similar artists.
				$artistnum = 0;
				$topartists = array();
				foreach ($tagartists['topartists']['artist'] as $artist) {
					if ($artistnum++ == 5) {
						break;
					}
					array_push($topartists,$artist['name']);
				}
				$str .= ' Top artists: (' . join(', ',$topartists) . ')';
			}
		} else { // unexcepted response from the server
			$str .= ' Top artists weren\'t found ';
		}
		if ($taginfo['tag']['url']) { // Make sure URL exists.
			$str .= ' (' . internets_shorten_url($taginfo['tag']['url']) . ')';
		}
		if ($taginfo['error'])	{
			$str = '"' . $arguments . '" Either doesn\'t exist or no description available.';
		}
	}
	send_msg($channel,$str);
}

