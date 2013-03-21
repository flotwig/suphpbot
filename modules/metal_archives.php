<?php
//** Author: StompingBrokenGlass (StompingBrokebGlass@Gmail.com) **//
/*
//   Description: Metal-Archives.com API Implementation in Suphpbot,
//   Based on The Java API for the Encyclopedia Metallum, the work of
//   Phillip Wirth (loooki@gulli.com).
//
//   Licence: Public Domain, with Beer-ware revision 42 extention.
//
//   If the user and Phillip Wirth ever met, and thought that this
//   plugin based on his work is worth it, then the user can buy 
//   a beer to Phillip Wirth as thanks for his work.
//
*/

// plugin required varibles
$scriptname = str_replace(".php","",basename(__FILE__));

$function_map[$scriptname]= array(
     'm-band' => 'ma_band'
);

$help_map[$scriptname] = array (
     'm-band' => 'Search Metal-Archives for band'
);

// Predefined Variables
$MA_BASE_URL = "http://www.metal-archives.com/";

function ma_band () {
     global $args,$channel,$nick;

     $band = $args[0];

     $str = "searching " . $band;
     
     send_msg($channel,$str);
}

?>
