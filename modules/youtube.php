<?php
//** Author: StompingBrokenGlass (StompingBrokenGlass@Gmail.com) **//
/*
//   Description: Youtube module
//
//   Licence: Public Domain.
*/

$scriptname = str_replace(".php","",basename(__FILE__));

$function_map[$scriptname] = array(

);

$help_map[$scriptname] = array (

);

$hook_map[$scriptname] = array (
    'data_in' => 'youtube_sniffer',
);


// sniffs youtube URL and gets the title
function youtube_sniffer () {
	global $channel,$buffer;

	// removing unneeded information
	$bf = explode(' ',$buffer);
	$bf[0] = NULL; $bf[1] = NULL; $bf[2] = NULL;
	$context = trim(implode(' ',$bf));

	// Grabbing the URL

	// The Regular Expression filter
	$reg_exUrl = "/(http|https)\:\/\/(|www.)+(youtube\.com|youtu\.be)+(\/\S*)?/i";

	// Check if there is a url in the text
	if(preg_match($reg_exUrl, $context, $url)) {


		$urlContents = file_get_contents($url[0]);

		//send_msg($channel,$urlContents);
		
		$dom = new DOMDocument();
		$dom->loadHTML($urlContents);

		$title_node = $dom->getElementsByTagName('title');

		$title_elements = explode("\n",$title_node->item(0)->nodeValue);
		$title = trim($title_elements [1]);

       // make the urls hyper links
//       echo preg_replace($reg_exUrl, "<a href="{$url[0]}">{$url[0]}</a> ", $text);
		send('PRIVMSG ' . $channel . ' :' ."Youtube Video: ". $title);
	}

	

}

?>
