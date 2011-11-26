<?php
//** Author: HiddenKnowledge (kevin@glazenburg.com) **//
// (c) 2011/2012

$scriptname = str_replace(".php","",basename(__FILE__));
$function_map[$scriptname] = array(
    'premium' => 'get_premium_info'
    'profile' => 'get_profile'
);

function bold($input) {
    return (CHAR_B . $input . CHAR_B);
}

function get_premium_info()
{
    // Let's globalize some of the variables we'll need for this command
    global $channel, $args;
    
    $jsonurl = "http://360api.chary.us/?gamertag=" . urlencode($args[0]);
    try {
        $json = file_get_contents($jsonurl, 0, null, null);
    }
    catch (Exception $e) {
        send_msg($channel, 'Error: ' . $e->getMessage());
    }
    $json_output = json_decode($json, TRUE);
    
    if ($json_output) {
        if ($json_output['GamertagExists']) {
            $premium = $json_output['Subscription'];
            send_msg($channel, "This user is a " tolower(bold($premium)) . " user.");
        } else {
            send_msg($channel, "No such user exists.");
        }
    } else {send_msg($channel, "Something went wrong. :(");}

}

function get_profile()
{
    // Let's globalize some of the variables we'll need for this command
    global $channel, $args;
    
    $jsonurl = "http://360api.chary.us/?gamertag=" . urlencode($args[0]);
    try {
        $json = file_get_contents($jsonurl, 0, null, null);
    }
    catch (Exception $e) {
        send_msg($channel, 'Error: ' . $e->getMessage());
    }
    $json_output = json_decode($json, TRUE);
    
    if ($json_output) {
        if ($json_output['GamertagExists']) {
            if($json_output['Gender'] == "Male") {$gender = "man";} else {$gender = "woman";}
            send_msg($channel, bold($jsonoutput['Gamertag']) . " is a " . bold($gender) . " that has a gamerscore of " . bold($jsonoutput['Gamerscore']) . " and earned " . bold($jsonoutput['EarnedAchievements']) . " archievements.");
        } else {
            send_msg($channel, "No such user exists.");
        }
    } else {send_msg($channel, "Something went wrong. :(");}

}

