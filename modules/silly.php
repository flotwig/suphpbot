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
    'hello' => 'silly_hello'
);

$help_map[$scriptname] = array (
    'police' => 'Prints an ASCII of a police car, also can be called by dialing 911',
    'hello' => 'Prints Hello world'
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
    global $channel;

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

// sinffer for commands without the preceding command character,
// based on internets snarf function.

function silly_sniffer () {

    global $buffwords;

    $sniffed_command = strtolower(substr($buffwords[3],1));

    if ($sniffed_command == '911') {
         silly_five_O();
    }
}
?>
