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

	// Loading required global variables
	global $channel,$buffer,$loaded_modules ;

	// removing unneeded information from the received text
	$bf = explode(' ',$buffer);
	$bf[0] = NULL; $bf[1] = NULL; $bf[2] = NULL;
	$context = trim(implode(' ',$bf));

	// Grabbing the URL

	// The Regular Expression filter
	$reg_exUrl = "/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";

	// Check if there is a url in the text
	if(preg_match($reg_exUrl, $context, $url)) {

		// Check for internets module is loaded

		if (!in_array("internets",$loaded_modules)) {

			send_msg($channel,"internets module not found, Kindly load the internets module");

		} else {

			// Get the HTML

			$HTMLContents = internets_get_contents($url[0]);
	
			// Parsing the HTML and handling errors

			$dom = new DOMDocument();

			libxml_use_internal_errors(true);

			if (! $dom->loadHTML($HTMLContents) ){

				foreach (libxml_get_errors() as $error) {

					// suppressing warnings, while throwing error and fatal levels

					if ( $error->level != LIBXML_ERR_WARNING) {
						
						// Building up the error message

						$err_message = "";

						switch ($error->level) {
							case LIBXML_ERR_ERROR:
								$err_message .= "Error $error->code: ";
								break;
							case LIBXML_ERR_FATAL:
								$err_message .= "Fatal Error $error->code: ";
								break;
						}

						$err_message .= trim($error->message) ;
						$err_message .= ", line: " . $error->line;
						$err_message .= ", column: " . $error->column;

						// Sending the message using PHP error log file

						error_log ($err_message);
						
					}
				}

				// clearing the error buffer to save memory

				libxml_clear_errors();
			}

			$title_node = $dom->getElementsByTagName('title');

			// Handle Multiline titles, like youtube

			$title_elements = explode("\n",$title_node->item(0)->nodeValue);
	
			$title = "";

			foreach ($title_elements as $element) {

				$title .= trim($element) . " ";

			}

			// Removes the extra space from the last loop

			$title = trim($title);
	
			// Checks if the title is not empty, and send the title to the channel

			if (!empty($title)) {
	
				send('PRIVMSG ' . $channel . ' :' ."Link Title: ". $title);

			}
		}

	}	

}
