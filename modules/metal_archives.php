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

     global $args,$channel,$nick,$arguments;

     $bandQuery = $arguments;

     $str = "";

     // Empty check
     if ((is_null($bandQuery)) || ($bandQuery == "")) {

          $str ="Kindly provide the band name";

     } else {

          $queryUrl = "search/ajax-advanced/searching/bands/?bandName=" .
          urlencode($bandQuery) . "&exactBandMatch=0&sEcho=1&iColumns=3&sColumns=&iDisplayStart=0&iDisplayLength=200&sNames=%2C%2C";

          $response = ma_getData($queryUrl);

          // Error handling
          if ($response['error']!=""){
              $str = "Error retriving the results:" . $response['error'];
          } elseif ($response["iTotalRecords"] <= 0) {
              $str = "No bands were found, please try a different band name";
          } else {
               
               //Processing bands

              $bands = $response["aaData"];

              $totalBands = $response["iTotalRecords"];

              // Limiting display to 5 bands, maybe use more in future
              $displayedBands = 5;

              if ($totalBands < 5) {
                 $displayedBands = $totalBands;
              }


              for ($i = 0 ; $i <  $displayedBands  ; $i++) {
                $band = $bands[$i];

                $bandLnN = $band[0];
		
                $bandLinkStart = strripos ($bandLnN,"href=\"");
		$bandLinkEnd = strripos ($bandLnN,"\">");
		$bandLinkLen = $bandLinkEnd - ($bandLinkStart + 6);
                $bandLink = substr ($bandLnN, $bandLinkStart + 6, $bandLinkLen );

		$bandNameEnd = strripos ($bandLnN,"<!--");
		$bandNameLen = $bandNameEnd - ($bandLinkEnd  + 2 );
                $bandName = substr ($bandLnN, $bandLinkEnd + 2, $bandNameLen );

		$unwanted = array ("</a>", "<strong>", "</strong>");
		$bandName = str_replace($unwanted, "" , $bandName);

                $bandGenre = $band[1];
                $bandCountry = $band[2];

		if ($str != ""){
		   $str .= ", ";
		}

		$str .= $bandName . " - " . $bandCountry . " - " . $bandGenre  ;
              }

          }
          
     }

     send_msg($channel,$str);
}

?>
