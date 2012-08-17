<?php
//** Author: StompingBrokenGlass (StompingBrokenGlass@Gmail.com) **//
/*
//   Description: link_announcer module
//
//   Licence: Public Domain.
*/

$scriptname = str_replace(".php","",basename(__FILE__));

$function_map[$scriptname] = array(

);

$help_map[$scriptname] = array (

);

$hook_map[$scriptname] = array (
    'data_in' => 'link_sniffer',
);


// sniffs the URL and gets the title
function link_sniffer () {
	global $channel,$buffer;

	// removing unneeded information from the received text
	$bf = explode(' ',$buffer);
	$bf[0] = NULL; $bf[1] = NULL; $bf[2] = NULL;
	$context = trim(implode(' ',$bf));

	// Grabbing the URL

	// The Regular Expression filter
	$reg_exUrl = "/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";

	// Check if there is a url in the text
	if(preg_match($reg_exUrl, $context, $url)) {

		// Get the HTML and Parse it

		$HTMLContents = file_get_contents($url[0]);
		
		$dom = new DOMDocument();

		$dom->loadHTML($HTMLContents);

		$title_node = $dom->getElementsByTagName('title');
	
		// Handle Multiline titles, like youtube

		$title_elements = explode("\n",$title_node->item(0)->nodeValue);
		
		$title = "";

		foreach ($title_elements as $element) {

			$title .= trim($element) . " ";

		}

		// Removes the extra space from the last loop

		$title = trim($title);
		
		// Send the title to the channel

		send('PRIVMSG ' . $channel . ' :' ."Link Title: ". $title);
	}

	

}
