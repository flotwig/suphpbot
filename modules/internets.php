<?php
// attempt to clone Rizon's Internets bot
define('JMP_USERNAME','o_350gb401dt');
define('JMP_APIKEY','R_ab02f29376cbc60916f569645a9772a7');
define('WUNDERGROUND_APIKEY','208a003e3946c0ca');
define('ADM_ACCKEY','z3XaqGuQJBkfa5200/+DLsgJ69h7r76Wqj0PLXYCBcA='); // Azure Data Marketplace account key
define('REDDIT_BASEURL','http://www.reddit.com/');
$function_map['internets'] = array(
	'shorten'=>'internets_shorten',
	'urbandictionary'=>'internets_urban',
	'u'=>'internets_urban',
	'expand'=>'internets_expand',
	'ipinfo'=>'internets_ipinfo',
	'ip'=>'internets_ipinfo',
	'bash'=>'internets_bash',
	'qdb'=>'internets_qdb',
	'weather'=>'internets_weather',
	'w'=>'internets_weather',
	'fml'=>'internets_fml',
	'forecast'=>'internets_forecast',
	'f'=>'internets_forecast',
	'translate'=>'internets_translate',
	't'=>'internets_translate',
	'youtube'=>'internets_youtube',
	'yt'=>'internets_youtube',
	'bing'=>'internets_bing',
	'whatpulse'=>'internets_whatpulse',
	'pulse'=>'internets_whatpulse',
	'karma'=>'internets_karma',
);
$help_map['internets'] = array(
	'shorten'=>'Usage: "shorten [url]" - shortens the provided URL using j.mp',
	'urbandictionary'=>'Usage: "u [word]" - looks up the word on UrbanDictionary',
	'u'=>$help_map['internets']['urbandictionary'],
	'expand'=>'Usage: "expand [shortened url]" - expands a URL using longurl.org',
	'ipinfo'=>'Usage: "ip [ip address]" - gets info about an IP address',
	'ip'=>$help_map['internets']['ipinfo'],
	'bash'=>'Get a funny quote from bash.org',
	'qdb'=>'Get a funny quote from qdb.us',
	'weather'=>'Usage: "w [location]" - get the weather conditions for a location',
	'w'=>$help_map['internets']['weather'],
	'fml'=>'Pull a random fmylife quote off the web',
	'forecast'=>'Usage: "f [location]" - seven-day forecast for a locaton',
	'f'=>$help_map['internets']['forecast'],
	'translate'=>'Type "translate" with no arguments for full usage information.',
	't'=>$help_map['internets']['translate'],
	'youtube'=>'Search YouTube for videos.',
	'yt'=>$help_map['internets']['youtube'],
	'bing'=>'Search Bing!',
	'pulse'=>'Usage: "pulse [username]" - look up a WhatPulse user\'s stats!',
	'whatpulse'=>$help_map['internets']['pulse'],
	'karma'=>'Retrieves karma stats and other info about a redditor.',
);

$hook_map['internets'] = array(
	'data_in'=>'internets_hook_snarf'
);

function internets_bing_search($string) {

	$accountKey = ADM_ACCKEY;
            
	$ServiceRootURL =  'https://api.datamarket.azure.com/Bing/Search/';

	$WebSearchURL = $ServiceRootURL . 'Web?$format=json&$top=3&Query=';

	$request = $WebSearchURL . urlencode( '\'' . $string . '\'');

	echo($request);
	$context = stream_context_create(array(
		'http' => array(
		    'request_fulluri' => true,
		    'header'  => "Authorization: Basic " . base64_encode($accountKey . ":" . $accountKey)
		)
	));

	$response = file_get_contents($request, 0, $context);

	$jsonobj = json_decode($response,TRUE);

	return $jsonobj;
	
}

function internets_get_contents($url,$post=NULL) {
	$ch = curl_init();
	curl_setopt_array($ch,array(
		CURLOPT_FOLLOWLOCATION=>TRUE,
		CURLOPT_MAXREDIRS=>5,
		CURLOPT_RETURNTRANSFER=>TRUE,
		CURLOPT_URL=>$url,
		CURLOPT_USERAGENT=>IRC_VERSION,
		CURLOPT_CONNECTTIMEOUT=>10,
	));
	if (is_array($post)) {
		curl_setopt($ch,CURLOPT_POST,TRUE);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
	}
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}
function internets_yql_query($query) {
	$qurl = 'http://query.yahooapis.com/v1/public/yql?format=json&env=' . urlencode('store://datatables.org/alltableswithkeys') . '&q=' . urlencode($query);
	$q = internets_get_contents($qurl);
	return json_decode($q,TRUE);
}
function internets_shorten_url($url) {
	$surl = @internets_get_contents('http://api.bitly.com/v3/shorten?format=txt&domain=j.mp&login=' . JMP_USERNAME . '&apikey=' . JMP_APIKEY . '&longUrl=' . urlencode($url));
	return $surl;
}
function internets_karma() {
	global $user,$channel,$arguments;
	$u = preg_replace('/[^a-zA-Z0-9\_]/','',$arguments);
	$url = REDDIT_BASEURL . 'user/' . $u . '/about.json';
	$json = internets_get_contents($url);
	$json = json_decode($json,TRUE);
	if (!$json||isset($json['error'])||$json['kind']!=='t2') {
		send_msg($channel,'An error occured while trying to find that user. ' . $url);
	} else {
		$gold = '';
		if ($json['data']['is_gold']) { $gold = ' They are a ' . fx('BOLD','reddit gold') . ' member.'; }
		send_msg($channel,fx('BOLD',$json['data']['name']) . ' has ' . fx('BOLD',number_format($json['data']['link_karma'])) . ' link karma and ' . fx('BOLD',number_format($json['data']['comment_karma'])) . ' comment karma.' . $gold);
	}
}
function internets_expand() {
	global $args,$channel;
	if (!filter_var($args[0],FILTER_VALIDATE_URL)) {
		send_msg($channel,'Please enter a valid URL (like http://is.gd/w)');
	} else {
		$exp = json_decode(internets_get_contents('http://api.longurl.org/v2/expand?title=1&format=json&url=' . urlencode($args[0])),TRUE);
		if (!empty($exp['long-url'])) {
			send_msg($channel,'Expanded URL: ' . $exp['long-url'] . ' (' . $exp['title'] . ')');
		} else {
			send_msg($channel,'An error occured while trying to contact the longurl service.');
		}
	}
}
function internets_shorten() {
	global $args,$channel;
	$s = internets_shorten_url($args[0]);
	send_msg($channel,'j.mp URL: ' . $s);
}
function internets_urban() {
	global $arguments,$channel;
	$def = @internets_get_contents('http://www.urbandictionary.com/iphone/search/define?term=' . urlencode($arguments));
	$def = json_decode($def,TRUE);
	if ($def['result_type']!=='exact') {
		send_msg($channel,'UrbanDictionary: No results found!');
	} else {
		send_msg($channel,'UrbanDictionary: ' . $def['list'][0]['word'] . ': ' . $def['list'][0]['definition'] . ' ' . (count($def['list'])-1) . ' other definitions. ' . internets_shorten_url('http://www.urbandictionary.com/define.php?term=' . $def['list'][0]['word']));
	}
}
function internets_ipinfo() {
	global $args,$channel;
	if (!filter_var($args[0],FILTER_VALIDATE_IP)) {
		send_msg($channel,'Usage: ipinfo 192.168.200.1. Be sure that you are supplying a valid IP address.');
	} else {
		$host = gethostbyaddr($args[0]);
		$country = internets_get_contents('http://api.hostip.info/country.php?ip=' . $args[0]);
		send_msg($channel,'IP info: ' . $args[0] . ' (' . $host . ', ' . $country . ') ' . internets_shorten_url('http://whois.domaintools.com/' . $args[0]));
	}
}
function internets_bash() {
	global $channel;
	$quote = file_get_contents('http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20html%20where%20url%3D%22http%3A%2F%2Fbash.org%2F%3Frandom%22%20and%20xpath%3D%22%2F%2Fp%5B%40class%3D\'qt\'%5D%22%20LIMIT%201&format=json');
	$quote = json_decode($quote,TRUE);
	send_msg($channel,'' . str_replace(array("\n","\r"),' /',$quote['query']['results']['p']['content']));
}
function internets_qdb() {
	global $channel;
	$quote = internets_yql_query('select * from rss where url="http://qdb.us/qdb.xml?fixed=0&action=random&client=suphpbot"');
	$qu = $quote['query']['results']['item'][mt_rand(0,count($quote['query']['results']['item']))];
	$hnng = explode('<i>',$qu['description']);
	$quote = strip_tags($hnng[0]);
	$quote = str_replace('&lt;','<',$quote);
	$quote = str_replace('&gt;','>',$quote);
	$quote = str_replace('\n',' // ',$quote);
	$quote = str_replace(array("\n","\r"),'',$quote);
	send_msg($channel,$qu['link'] . ' - ' . $quote);
}

function internets_weather() {
	global $channel,$arguments;
	if (empty($arguments)) {
		send_msg($channel,'Usage: "w [location]" ("w City", "w City, US State", "w City, Country" ,"w zipcode")');
	} else {

		// preparing query to be sent

		$query = str_replace(" ","_", $arguments);
		$query = urlencode($query);

		// Retriving the data

		$w = json_decode(internets_get_contents('http://api.wunderground.com/api/' . WUNDERGROUND_APIKEY . '/conditions/q/' . $query . '.json'),TRUE);
		
		if (isset($w['response']['error'])) {
			
			// Handling errors

			send_msg($channel,"Error: ".$w['response']['error']['description']);

		} elseif (isset($w['current_observation'])) {

			// Displaying the weather information

			$response = array(
				$w['current_observation']['display_location']['full'],
				'Conditions: ' . $w['current_observation']['weather'],
				'Temperature: ' . $w['current_observation']['temperature_string'],
				'Wind chill: ' . $w['current_observation']['windchill_string'],
				'Dew point: ' . $w['current_observation']['dewpoint_string'],
				'Humidity: ' . $w['current_observation']['relative_humidity'],
				'Wind: ' . $w['current_observation']['wind_string'],
				'Visibility: ' . $w['current_observation']['visibility_mi'] . 'mi, ' . $w['current_observation']['visibility_km'] . 'km',
				'Pressure: ' . $w['current_observation']['pressure_mb'] . 'mb (' . $w['current_observation']['pressure_in'] . 'in)',
				'Precipitation today: ' . $w['current_observation']['precip_today_string'],
			);

			send_msg($channel,implode(', ',$response));

		} elseif (isset($w['response']['results'])) {

			// Handles city clarification

			$cities = array();

			// creating the list while limiting to 10 cities

			for ($i=0; ($i < count($w['response']['results'])) && ($i < 10) ; $i++) {

				$location = $w['response']['results'][$i];

				if ($location['country_name'] == 'USA' ) {

					// Display the state instead of the country for USA

					$cities[] = $location['name'].','.$location['state'];

				} else {

					$cities[] = $location['name'].','.$location['country_name'];

				}
				

			}
			
			// Perparing the message & sending it.

			$message = "Kindly clarify the location, for example: ";
			$message .= implode(' - ',$cities);

			send_msg($channel,$message);

		} else {

			send_msg($channel,"Unknown response, Kindly contact the developers to report the issue.");
		}
	}
}
function internets_forecast() {
	global $channel,$arguments;

	if (empty($arguments)) {

		// Display instructions if no arrguments were provided

		send_msg($channel,'Usage: "f [location]" ("f City", "f City, US State", "f City, Country" ,"f zipcode")');

	} else {
		
		// preparing query to be sent

		$query = str_replace(" ","_", $arguments);
		$query = urlencode($query);

		// Retriving the data

		$w = json_decode(internets_get_contents('http://api.wunderground.com/api/' . WUNDERGROUND_APIKEY . '/geolookup/forecast7day/q/' . $query . '.json'),TRUE);

		if (isset($w['response']['error'])) {

			// Handling errors

			send_msg($channel,"Error: ".$w['response']['error']['description']);

		} elseif (isset($w['location'])){

			// Displaying forcast information

			// Building the city full name

			$fullName = '';

			if ($w['location']['type'] == 'CITY') {

				// A USA city
				$fullName = $w['location']['city'].', '.$w['location']['state'];

			} elseif ($w['location']['type'] == 'INTLCITY') {

				// Internation city
				$fullName = $w['location']['city'].', '.$w['location']['country_name'];

			} else {

				// Unhandled type (Fail over)
				$fullName = $w['location']['city'];
			}


			// Bundling the forcast

			$response = array();
			$response[] = $fullName . ' forecast';

			foreach ($w['forecast']['simpleforecast']['forecastday'] as $day) {
				$response[] = $day['date']['weekday'] . ': ' . $day['conditions'] . ' ' . $day['low']['fahrenheit'] . '-' . $day['high']['fahrenheit'] . 'F ('  . $day['low']['celsius'] . '-' . $day['high']['celsius'] . 'C)';
			}

			// Sending the forcast

			send_msg($channel,implode(', ',$response));

		} elseif (isset($w['response']['results'])) {

			// Handles city clarification

			$cities = array();

			// creating the list while limiting to 10 cities

			for ($i=0; ($i < count($w['response']['results'])) && ($i < 10) ; $i++) {

				$location = $w['response']['results'][$i];

				if ($location['country_name'] == 'USA' ) {


					// Display the state instead of the country for USA

					$cities[] = $location['name'].','.$location['state'];

				} else {

					$cities[] = $location['name'].','.$location['country_name'];

				}
				

			}
			
			// Perparing the message & sending it.

			$message = "Kindly clarify the location, for example: ";
			$message .= implode(' - ',$cities);

			send_msg($channel,$message);


		} else {

			// Handled an unknown response

			send_msg($channel,"Unknown response, Kindly contact the developers to report the issue.");
		}
	}
}
function internets_fml() {
	global $channel;
	$fml = internets_yql_query('select * from html where url="http://m.fmylife.com/random"');
	send_msg($channel,'FML: ' . $fml['query']['results']['body']['ul'][2]['li'][0]['p'][0]['content']);
}
function internets_translate() {
	global $channel,$args;
	$langs=array('ar','bg','zhCHS','zhCHT','cs','da','nl','en','ht','fi','fr','de','el','he','hu','it','ja','ko','lt','no','pl','pt','ro','ru','sk','sl','es','sv','th','tr');
	if (!in_array($args[0],$langs)||!in_array($args[1],$langs)) {
		send_msg($channel,'Usage: "t [from-language] [to-language] [text to translate]". A list of valid language codes: ' . implode(', ',$langs));
	} else {
		$from = $args[0];
		$to = $args[1];
		unset($args[0]); unset($args[1]);
		$t = internets_get_contents('http://api.microsofttranslator.com/v2/Ajax.svc/Translate?appId=' . BING_APPID . '&from=' . $from . '&to=' . $to . '&text=' . urlencode(implode(' ',$args)));
		send_msg($channel,'Translation: ' . trim(substr($t,1)));
	}
}
function internets_youtube() {
	global $channel,$arguments;
	if (empty($arguments)) {
		send_msg($channel,'Usage: "yt [search terms go here]"');
	} else {
		$yt = internets_yql_query('select * from youtube.search where query="' . addslashes($arguments) . '" limit 1;');
		if ($yt['query']['results']==NULL) {
			send_msg($channel,'No YouTube search results found.');
		} else {
			$yt = $yt['query']['results']['video'];
			send_msg($channel,'YouTube: ' . $yt['title'] . ' ( http://youtu.be/' . $yt['id'] . ' ) Duration: ' . floor($yt['duration']/60) . ':' . str_pad($yt['duration']%60,2,'0',STR_PAD_LEFT) . ' Uploader: ' . $yt['author'] . ' Comments: ' . number_format($yt['comments']));
		}
	}
}
function internets_bing() {
	global $channel,$arguments;
	if (empty($arguments)) {
		send_msg($channel,'Usage: "Bing [search terms go here]"');
	} else {
		$results = internets_bing_search($arguments);
		if (!$results) {
			$resultsirc = 'Unable to contact the Bing! API.';
		} elseif (isset($results['SearchResponse']['Errors'])) {
			$resultsirc = "Error: Code ". $results['SearchResponse']['Errors'][0]['Code'];
		} else {
			foreach ($results['d']['results'] as $result) {
				$resultsirc[] = $result['Title'] . ' <' . $result['Url'] . '>';
			}
			$resultsirc = 'Results: ' . implode(', ',$resultsirc);
		}
		send_msg($channel, $resultsirc);
	}
	
}
function internets_whatpulse() {
	global $channel,$arguments;
	$wpapi = internets_get_contents('https://whatpulse.org/api/user.php?UserID=' . urlencode($arguments));
	$wpxml = simplexml_load_string($wpapi);
	if (!$wpxml) {
		send_msg($channel,'The username specified does not exist.');
	} else {
		$wpout = (string)$wpxml->AccountName . ' joined WhatPulse on ' . (string)$wpxml->DateJoined . '. Since then, he has typed ' . number_format((int)$wpxml->TotalKeyCount) . ' keys, clicked ' . number_format((int)$wpxml->TotalMouseClicks) . ' times, and has moved his mouse ' . number_format((int)$wpxml->TotalMiles) . ' miles. He is ranked #' . number_format((int)$wpxml->Rank) . ' overall.';
		if ((int)$wpxml->TeamID!==0) {
			$wpout .= ' He is a member of the team "' . (string)$wpxml->TeamName . '", where he is ranked at #' . number_format((int)$wpxml->RankInTeam) . '.';
		}
		send_msg($channel,$wpout);
	}
}
// snarf shits yo!
function internets_hook_snarf() {
	global $channel,$args,$arguments,$buffwords,$settings;

	if ($settings['direct_search'] == 1){
		$snarf_command = strtolower(substr($buffwords[3],1));

		//Checks for Bing or google and redirect to internets_bing
		if ($snarf_command=='bing'||$snarf_command=='google') {
		
			internets_bing();

		}
	}
}
