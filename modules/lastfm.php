<?php
/* lastfm stuffs
kwamaking (kwamaking@gmail.com) http://github.com/kwamaking
this requires the internets module, as it uses a few functions from it.
*/
define('LASTFM_API_KEY','362a86ba35347c41a363b46dc32e333e');

$function_map['lastfm'] = array(
	'setuser'	=> 'setLastfmUser',
	'deluser'	=> 'deleteLastfmUser',
	'np'		=> 'getNowPlaying',
	'top'		=> 'getTopArtists',
	'band'       	=> 'getArtistInfo',
	'compare'	=> 'compare_users',
	'plays'		=> 'getPlays',
	'whois'      	=> 'whois',
	'genre'		=> 'getGenre',
	'event'		=> 'getEvent',
	'recs'		=> 'getUserRecommendations'
);
$help_map['lastfm'] = array(
	'setuser'	=> 'Set your last.fm username.',
	'deluser'	=> 'Removes your last.fm username.',
	'np'		=> 'Gets now playing of given user.',
	'top'		=> 'Displays top 8 artists in a given 7 day period of setuser or given user.',
	'band'		=> 'Returns some basic information about specified band.',
	'compare'	=> 'Compares register user with specified user, or two separate users.',
	'plays'		=> 'Displays user plays by given band, you can also view plays of given band by users other than yourself. I.E. plays username band.',
	'whois'		=> 'Returns users associated with Last.fm username, or any given nick.',
	'genre'		=> 'Returns brief description and similar genres and tags of given genre or tag.',
	'event'		=> 'Searches for an event and displays a short description.',
	'recs'		=> 'Displays a user\'s 10 top recommended artists.'
);
function throwWarning($channel) {
	$message = 'Either nick isn\'t associated or you need to specify arguments.';
	return send_msg($channel,$message);
}

function setLastfmUser() {
	global $args, $channel, $nick;

	if (!$args[0]) {
		$message = "Please provide your last.fm username.";
	} else {
		$file 	= './data/lastfm_data.json';
		$data 	= getLastfmData('user.getrecenttracks', 'user=' . urlencode($args[0]) . '&limit=1');
		if ($data['recenttracks']['track']) {
			$fc 	= file_get_contents($file);
			$users 	= json_decode($fc, true);
			if ($users['users'][$nick]) {
				$message = 'Your nick is already associated with ' . $users['users'][$nick]['lastfmuser'];
				$message .= ' If you want to change it, first delete your user.  See help for more information.';
			} else {
				$users['users'][$nick] = array('lastfmuser' => $args[0]);
				file_put_contents($file, json_encode($users));
				$message = $nick . ' is now associated with ' . $args[0] . '.';
			}
		} else {
			$message = $args[0] . ' either doesn\'t exist or has no recent plays.  Failed to create association.';
		}
		send_msg($channel,$message);
	}
}
function deleteLastfmUser() {
	global $channel, $nick;

	$file 	= './data/lastfm_data.json';
	$fc 	= file_get_contents($file);
	$users 	= json_decode($fc, true);
	if ($users['users'][$nick]) {
		unset($users['users'][$nick]);
		file_put_contents($file, json_encode($users));
		$message = 'Your username has been removed.';
	} else {
		$message = 'No association found with your nick!';
	}
	send_msg($channel,$message);
}

function getLastfmUser($nick) {
	global $channel;

	$file 		= './data/lastfm_data.json';
	$fc 		= file_get_contents($file);
	$users 		= json_decode($fc, true);
	$message 	= true;
	foreach ($users['users'] as $key => $lastfm) {
		if (preg_match('/' .$nick . '/i', $key)) {
			$message = $lastfm['lastfmuser'];
		}
	}
	return $message;
}

function getLastfmData($method, $parameters) {
	global $loaded_modules, $channel;

	if (in_array("internets", $loaded_modules)) {
		$message = internets_get_contents('http://ws.audioscrobbler.com/2.0/?format=json&api_key='. LASTFM_API_KEY .'&method=' . $method . '&' . $parameters);
		// Presumably $def was incorrect! - Mike
		$message = json_decode($message, TRUE);
	} else {
		send_msg($channel, "This will not work without the internets module.");
	}

	return $message;
}

/**
 * Rewritten, sensible version of getTopTags without insane loop madness.
 *
 * @param string $artist
 * @return string $top_tags
 */
function getTopTags($artist) {
	$tags = getLastfmData('artist.gettoptags','artist=' . urlencode($artist));
	$tags = $tags["toptags"]["tag"];
	$clean_tags = array();

	foreach ($tags as $tag) {
		array_push($clean_tags, $tag["name"]);
	} // foreach

	if ($tags) {
		$tag_slice = array_slice($clean_tags, 0, 4);
		$top_tags = implode($tag_slice, ", ");
	} else {
		$top_tags = "";
	} // else

	return $top_tags;
} // getTopTags()

//TODO:
//What the fuck was I on when I wrote this?
function getNowPlaying() {
	global $args, $channel, $nick;

	$user = getLastfmUser($nick);
	if ($args[0]) {
		$user = getLastfmUser($args[0]);
		if (!$user) {
			$user = $args[0];
		}
	}
	if (!$user) {
		return throwWarning($channel);
	}
	$json_data 	= getLastfmData('user.getrecenttracks','user=' . urlencode($user) . '&limit=1');
	$first_track 	= $json_data['recenttracks']['track'][0];
	if ($first_track['@attr']['nowplaying']) {
		$trackinfo	= getLastfmData('track.getinfo','artist=' . urlencode($first_track['artist']['#text']) . '&track=' . urlencode($first_track['name']) . '&username=' . urlencode($user));
		$top_tags 	= getTopTags($first_track['artist']['#text']);
		//$top_tags = implode($top_tags, ", ");
		$message 	=  ' "' . $user . '" is now playing '.$first_track['artist']['#text'];
		$message 	.= ' - ' . $first_track['name'];
		if ($first_track['album']['#text'])  {
			$message .= ' - ' . $first_track['album']['#text'];
		}
		if ($track_info['track']['duration']) {
			//TODO:
			//Un fuck this
				$total_seconds = $track_info['track']['duration'] / 1000;
				$minutes = floor($total_seconds / 60);
				$seconds = $total_seconds - ($minutes * 60);
				if ($seconds < 10) {
					$seconds = '0' . $seconds;
				}
				$message .= ' [' . $minutes . ':' . $seconds . ']';
		}
		if ($track_info['track']['userplaycount']) {
			$message .= ' [playcount: ' . $track_info['track']['userplaycount'] . 'x';

			if ($track_info['track']['userloved']) {
				$message .= ' - ♥';
			}
			$message .= ']';
		}
		if ($top_tags) {
			$message .= ' (' . $top_tags . ')';
		}
		if ($first_track['url']) {

			$message .= ' (' . internets_shorten_url($first_track['url']) . ')';
		}
	} else {
		if ($json_data['recenttracks']['track']) {
			$message  = ' "' . $user . '" is not listening to anything right now.  Last played track is ';
			$message .= $json_data['recenttracks']['track']['artist']['#text'];
			$message .= ' - ' . $json_data['recenttracks']['track']['name'];
			$message .= ' on ' . $json_data['recenttracks']['track']['date']['#text'];
		} else {
			$message = $user . ' not found or error accessing last.fm data';
		}
	}
	send_msg($channel,$message);
}

function getTopArtists() {
	global $args, $channel, $nick;

	$user = getLastfmUser($nick);
	if ($args[0]) {
		$user = getLastfmUser($args[0]);
		if (!$user) {
			$user = $args[0];
		}
	}
	if (!$user) {
		return throwWarning($channel);
	}
	$json_data = getLastfmData('user.gettopartists','limit=8&period=7day&user=' . urlencode($user));
	if ($json_data['topartists']['artist'][0]['name']) {
		$artists = array();
		foreach ($json_data['topartists']['artist'] as $artist) {
			array_push($artists, $artist['name']);
		}
		$message .= ' top artists for "' . $user . '" (' . join(', ', $artists) . ')';
	} else {
		$message = 'Specified user has no recent top artists.';
	}
	send_msg($channel, $message);
}

function getArtistInfo() {
	global $arguments, $channel;

	if (!$arguments) {
		$message = 'Please specify an artist.';
	} else {

		$json_data = getLastfmData('artist.getinfo','limit=1&autocorrect=1&artist=' . urlencode($arguments));
		if ($json_data['artist']['name']) {
			$message = $json_data['artist']['name'] . ' have ' . number_format($json_data['artist']['stats']['playcount']) . ' plays and ';
			$message .= number_format($json_data['artist']['stats']['listeners']) . ' listeners.';
			if ($json_data['artist']['similar']) {
				$artists = array();
				foreach ($json_data['artist']['similar']['artist'] as $similar_artist) {
					array_push($artists, $similar_artist['name']);
				}
				$message .= ' Similar artists include: (' . join(', ',$artists) . ')';
			}
			$top_tags = getTopTags($json_data['artist']['name']);
			if ($top_tags) {
				$message .= ' Tags: (' . $top_tags . ')';
			}
		} else {
			$message = 'Last.fm could not find artist: ' . $arguments;
		}
	}
	send_msg($channel, $message);
}

function compare_users() {
	global $args, $channel, $nick;
	$user 			= getLastfmUser($nick);
	$comparison_results 	= array();
	if ($args[1]) {
		$user 			= getLastfmUser($args[0]);
		$compared_user 	= getLastfmUser($args[1]);
		if (!$user) {
			$user = $args[0];
		}
		if (!$compared_user) {
			$compared_user = $args[1];
		}
	} else {
		$compared_user = getLastfmUser($args[0]);
		if (!$compared_user) {
			$compared_user = $args[0];
		}
	}
	if ((!$user && !$args[0]) || ($user && !$args[0])) {
		return throwWarning($channel);
	}
	$json_data = getLastfmData('tasteometer.compare','type1=user&type2=user&value1=' . urlencode($user) . '&value2=' . urlencode($compared_user));
	if ($json_data['comparison']['result']['score'] > 0) {
		$message = '"' . $user . '" vs "' . $compared_user . '": ' . round($json_data['comparison']['result']['score']*100, 1) . '% - ';
		if ($json_data['comparison']['result']['artists']['artist'][1]['name']) {
			$resultarray = array();
			foreach ($json_data['comparison']['result']['artists']['artist'] as $artist_in_common) {
				array_push($comparison_results, $artist_in_common['name']);
			}
			$message .= ' Common artists include: (' . join(', ',$comparison_results) . ')';
		} else {
				$message .= ' One common artist: (' . $json_data['comparison']['result']['artists']['artist']['name'] . ')';
		}
	} else {
		$message = 'There are no common artists between ' . $user . ' and ' . $compared_user . '.';
	}
	send_msg($channel, $message);
}

function getPlays() {
	global $args, $channel, $nick;
	$user = getLastfmUser($nick);

	if (!$args[0] || !$user) {
		return throwWarning($channel);
	} else {
		$artist = join(' ',$args);
	}
	$json_data = getLastfmData('artist.getinfo','autocorrect=1&username=' . urlencode($user) . '&artist=' . urlencode($artist));
	if ($json_data['artist']['name'] && $json_data['artist']['stats']['userplaycount']) {
		$message = '"' . $user . '" has ' . $json_data['artist']['stats']['userplaycount'];
		$message .= ' ' . $json_data['artist']['name'] . ' plays.';
	} else if ($json_data['artist']['name']) {
		$message = '"' . $user . '" has never listened to ' . $json_data['artist']['name'];
	} else {
		$message = 'Last.fm has no record of ' . $artist;
	}
	send_msg($channel,$message);
}
function whois() {
	global $args,$channel,$nick;
	if (!$args[0] || !preg_match('/^(?=.{4})(?!.{21})[\w.-]*[a-z][\w-.]*$/i', $args[0])) {
		$str = "Please provide a Last.fm username.";
	} else {
		$user = getLastfmUser($args[0]);
		if (!$user) {
			$user = $args[0];
		}
		$file = './data/lastfm_data.json';
		$fc = file_get_contents($file);
		$users = json_decode($fc, true);
		$userarray = array();
		foreach ($users['users'] as $key => $lastfm) {
			if ($lastfm['lastfmuser'] == $user) {
				array_push($userarray,$key);
			}
		}
		if ($userarray) { 
			$str = ' "' . $user . '" is associated with: (';
			$str .= join(', ',$userarray);
			$str .= ')';
		} else {
			$str = 'Could not find any association for: ' . $user;
		}
	}
	send_msg($channel,$str);
}

function getGenre() {
	global $arguments, $channel, $args;
	$similar_tags 	= getLastfmData('tag.getsimilar','tag=' . urlencode($arguments));
	$tag_info 	= getLastfmData('tag.getinfo','tag=' . urlencode($arguments));
	$top_artists 	= getLastfmData('tag.gettopartists','tag=' . urlencode($arguments));
	$text_length 	= 200;
	if (!$args[0]) {
		$message = 'Please include a genre or tag name.';
	} elseif (!is_array($tag_info)) {
		$message = 'unexcpeted reponse for genre info, Contact admin';
	} else {
		if ($tag_info['tag']['name']) {
			$message = '"' . $tag_info['tag']['name'] . '" - ';
		}
		if (is_array($tag_info['tag']['wiki'])) {
			$message .= html_entity_decode(substr(strip_tags($tag_info['tag']['wiki']['summary']), 0, $text_length)) . '...';
		}
		if (is_array($similar_tags)) {
			if ($similar_tags['similartags']['tag'] && !$similar_tags['similartags']['#text']) {
				$tag_amount = 0;
				$tags = array();
				foreach ($similar_tags['similartags']['tag'] as $tag) {
					if ($tag_amount++ == 5) {
						break;
					}
					array_push($tags, $tag['name']);
				}
				$message .= ' Similar tags: (' . join(', ',$tags) . ')';
			}
		} else {
			$message .= ' Similar tags weren\'t found ';
		}
		if (is_array($top_artists)) {
			if ($top_artists['topartists']['artist']) { // Check if there are similar artists.
				$artist_amount = 0;
				$top_artists 	= array();
				foreach ($top_artists['topartists']['artist'] as $artist) {
					if ($artist_amount++ == 5) {
						break;
					}
					array_push($top_artists, $artist['name']);
				}
				$message .= ' Top artists: (' . join(', ',$top_artists) . ')';
			}
		} else {
			$message .= ' Top artists weren\'t found ';
		}
		if ($tag_info['tag']['url']) {
			$message .= ' (' . internets_shorten_url($tag_info['tag']['url']) . ')';
		}
		if ($tag_info['error'])	{
			$message = '"' . $arguments . '" Either doesn\'t exist or no description available.';
		}
	}
	send_msg($channel, $message);
}

/**
 * Search for an event by ID.
 *
 * @global array $arguments - content of the rest of the command.
 * @global string $channel - where the command was issued.
 * @global array $args - content of the rest of the command.
 * @return boolean 
 */
function getEvent() {
	global $arguments, $channel, $args;

	$response = "";

	if (!$args[0] || !is_numeric($args[0])) {
		send_msg($channel, "Event ID is either non-numeric or missing.");
		return false;
	} // if

	$event = getLastfmData("event.getInfo", "event=".urlencode($args[0]));
	$event = $event["event"];
	$venue = $event["venue"];
	$artists = $event["artists"];
	$top_artists = array_slice($artists["artist"], 0, 4);
	$top_artists = implode($top_artists, ", ");
	$tags = implode($event["tags"]["tag"], ", ");
	$url = internets_shorten_url($event["url"]);

	$response .= "{$event["title"]} at {$venue["name"]}, {$venue["location"]["city"]}."
		." {$event["attendance"]} attendees. Artists: {$top_artists}. Tags: {$tags}."
		." {$url}";


	send_msg($channel, $response);
} // getEvent()

/**
 * Discover [10 of] a user's recommended artists.
 *
 * @global string $channel
 * @global array $args
 * @return
 */
function getUserRecommendations() {
	global $channel, $args, $nick;
	$user = getLastfmUser($nick);

	if ($args[0]) {
		$user = getLastfmUser($args[0]);
		if (!$user) {
			$user = $args[0];
		} // if
	} // if
	if (!$user) {
		return throwWarning($channel);
	} // if

	$recs = new SimpleXmlElement(@file_get_contents("http://ws.audioscrobbler.com/1.0/user/{$user}/systemrecs.rss"));
	$recs = $recs->xpath('channel/item');
	$recs = array_slice($recs, 0, 9);
	$artists = array();

	foreach ($recs as $r) {
		$artists[] = (string) $r->title;
	} // foreach

	$output = "Recommendations for {$user}: ".implode($artists, ", ").".";

	send_msg($channel, $output);
} // getUserRecommendations()
