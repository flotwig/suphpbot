<?php
$function_map['misc'] = array(
	'password'=>'misc_password',
	'type'=>'misc_type',
);
$help_map['misc'] = array(
	'password'=>'Generate a random password. "password [length] ([type])", where "length" can be from 1 to 50 and "type" can be empty (for alphanumerasymbolic passwords), abc, 123, or abc123.',
	'type'=>'Find out which module a command belongs to: "type [command]"',
);
function misc_password() {
	global $args,$channel;
	$type=$args[1];
	$len=(int)$args[0];
	if ($len<1||$len>50) {
		$len=25;
	}
	$pass='';
	$inty=0;
	$i=0;
	while ($i<$len) {
		if ($type=='abc') {
			if (rand(0,1)=='0') {
				$inty = mt_rand(65,90);
			} else {
				$inty = mt_rand(97,122);
			}
		} elseif ($type=='123') {
			$inty = mt_rand(48,57);
		} elseif ($type=='abc123'||$type=='123abc') {
			$r = rand(0,2);
			if ($r=='0') {
				$inty = mt_rand(65,90);
			} elseif ($r=='1') {
				$inty = mt_rand(48,57);
			} else {
				$inty = mt_rand(97,122);
			}
		} else {
			$inty = mt_rand(33,126);
		}
		$i++;
		$pass .= chr($inty);
	}
	send_msg($channel,'Password (' . $len . '): ' . $pass);
}
function misc_type() {
	global $function_map,$commands,$channel,$args;
	if (!in_array(strtolower($args[0]),$commands)) {
		send_msg($channel,'You did not enter a valid command. Check "help type".');
	} else {
		foreach ($function_map as $module=>$map) {
			if (in_array(strtolower($args[0]),$map)) {
				$modname = $module;
				break;
			}
		}
		if (empty($modname)) {
			send_msg($channel,'An error occured while trying to process your request.');
		} else {
			send_msg($channel,'"' . strtolower($args[0]) . '" can be found in the module "' . $modname . '"');
		}
	}
}