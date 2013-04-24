<?php
//** Author: StompingBrokenGlass (StompingBrokebGlass@Gmail.com) **//
/*
//   Description: Metal-Archives.com API Implementation in Suphpbot,
//   Based on The Java API for the Encyclopedia Metallum, the work of
//   Phillip Wirth (loooki@gulli.com), and last.fm plugin by 
//   Kwamaking (kwamaking@gmail.com).
//
//   Licence: Public Domain, with Beer-ware revision 42 extention.
//
//   If the user and Phillip Wirth ever met, and thought that this
//   plugin based on his work is worth it, then the user can buy 
//   a beer to Phillip Wirth as thanks for his work.
//
*/

// Plugin required varibles
$scriptname = str_replace(".php","",basename(__FILE__));

$function_map[$scriptname]= array(
     'ma-band' => 'ma_band'
);

$help_map[$scriptname] = array (
     'ma-band' => 'Search Metal-Archives for band'
);

// Functions

function ma_getData($queryURL) {
	global $loaded_modules, $channel,$MA_BASE_URL ;

	$MA_BASE_URL = "http://www.metal-archives.com/";

	if (in_array("internets", $loaded_modules)) {
		$message = $MA_BASE_URL . $queryURL;

		$message = @internets_get_contents($MA_BASE_URL . $queryURL);

		$message = json_decode($message, TRUE);
	} else {
		send_msg($channel, "This will not work without the internets module.");
	}

	return $message;
}

function ma_band () {

     /**************************************************************
     ************************* Sample Data *************************

     * Sample query URL
	http://www.metal-archives.com/search/ajax-advanced/searching/bands/?bandName=test&exactBandMatch=0&sEcho=1&iColumns=3&sColumns=&iDisplayStart=0&iDisplayLength=20&sNames=%2C%2C

     * Sample response
	{ 
		"error": "",
		"iTotalRecords": 11,
		"iTotalDisplayRecords": 11,
		"sEcho": 0,
		"aaData": [
					[ 
				"<a href=\"http://www.metal-archives.com/bands/Test/3540355114\">Test</a>  <!-- 8.284618 -->" ,	
				"Grindcore/Death Metal/Crustcore" ,
				"Brazil"    		]
					,
							[ 
				"<a href=\"http://www.metal-archives.com/bands/De-Test/22778\">De/Test</a> (<strong>a.k.a.</strong> DeTest) <!-- 3.9795065 -->" ,	
				"Thrash Metal" ,
				"Germany"    		]
					]
	}

     ************************* End Sample Data *************************
     ******************************************************************/

     global $args,$channel,$nick,$arguments;

     $bandName = $arguments;

     $str = "";

     // Empty check
     if ((is_null($bandName)) || ($bandName == "")) {

          $str ="Kindly provide the band name";

     } else {
          $str = "searching " . $band;
          $queryUrl = "search/ajax-advanced/searching/bands/?bandName=" .
          urlencode($bandName) . "&exactBandMatch=0&sEcho=1&iColumns=3&sColumns=&iDisplayStart=0&iDisplayLength=20&sNames=%2C%2C";

          $response = ma_getData($queryUrl);

          // Error handling
          if ($response['error']!=""){
              $str = "Error retriving the results:" . $response['error'];
          } else {
              $str = $response["iTotalRecords"];
          }
          
     }

     send_msg($channel,$str);

}

?>
