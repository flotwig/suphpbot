<?php
// horribly unfun games
$function_map['games'] = array(
	'coin'=>'games_coin',
	'roulette'=>'games_roulette',
	'barkeep'=>'games_barkeep',
	'eightball'=>'games_eightball',
	'noodles'=>'games_noodles',
);
$help_map['games'] = array(
	'coin'=>'For those of you who can\'t make your own decisions, "coin" can be used to... flip a coin. Am I really having to explain this to you?',
	'roulette'=>'Simulate the gentlemanly game of Russian Roulette with a six-shot virtual revolver.',
	'barkeep'=>'Our very own interactive AI barkeep. "barkeep [drink]" to request a drink, "barkeep [drink] [flavor]" to get something special, and just "barkeep" to view our selection.',
	'eightball'=>'"eightball [question]" - All the answers you seek.',
	'noodles'=>'Our very own interactive noodle bar.',
);
$trap = rand(0,5);
$tries = 0;
function games_coin() {
	global $channel;
	if (rand(0,1)==0) {
		send_msg($channel,'Heads!');
	} else {
		send_msg($channel,'Tails!');
	}
}
function games_roulette() {
	global $channel,$trap,$tries,$nick;
	if ($trap==$tries) {
		send_msg($channel,'BANG! Looks like you lost! *reloading*');
		send('KICK ' . $channel . ' ' . $nick . ' :Get outta here!');
		$tries = 0;
		$trap = rand(0,5);
	} else {
		$tries++;
		send_msg($channel,'*click*');
	}
}
function games_barkeep() {
	global $channel,$args;
	$drank = array('vodka'=>'a shot of %s vodka','coke'=>'an ice cold glass of %s coke','pepsi'=>'some %s Pepsi (the superior choice in soft drinks)','coffee'=>'a cuppa %s coffee','tea'=>'a cuppa %s tea','water'=>'a %s glass of water','martini'=>'an ice-cold %s martini','daiquiri'=>'a %s daiquiri','whiskey'=>'a shot of whisky %s','brandy'=>'a bottle of %s brandy','beer'=>'a %s beer');
	ksort($drank);
	if (isset($drank[$args[0]])&&!empty($args[1])) {
		$message = 'Just for you, here\'s ' . sprintf($drank[$args[0]],substr($args[1],0,15)) . '!';
	} elseif (isset($drank[$args[0]])) {
		$message = 'Have ' . sprintf($drank[$args[0]],'') . '.';
	} else {
		$message = 'Our selection: ' . implode(', ',array_keys($drank));
	}
	send_msg($channel,'Barkeep: ' . $message);
}
function games_noodles() {
	global $channel,$args;
	$drank = array('ramen'=>'a rectangle of %s ramen noodles','spaghetti'=>'a pipin\' hot dish of our %s spaghetti!','instant'=>'a styrofoam cup of %s instant noodles. they\'re really quick!','fried'=>'a bowl of %s fried noodles. fun!','lasagna'=>'a dishful of %s lasagna... i mean, i wouldn\'t eat it, but hey.','chicken'=>'a %s bowl of chicken noodle soup!');
	ksort($drank);
	if (isset($drank[$args[0]])&&!empty($args[1])) {
		$message = 'Just for you, here\'s ' . sprintf($drank[$args[0]],substr($args[1],0,15));
	} elseif (isset($drank[$args[0]])) {
		$message = 'Have ' . sprintf($drank[$args[0]],'');
	} else {
		$message = 'Our selection: ' . implode(', ',array_keys($drank));
	}
	send_msg($channel,'Noodle bar: ' . $message);
}
function games_eightball() {
	global $channel;
	$balls = array('It is certain.','It is decidedly so.','Without a doubt.','Yes - definitely.','You may rely on it.','As I see it, yes.','Most likely.','Outlook good.','Signs point to yes.','Yes.','Reply hazy, try again.','Ask again later.','Better not tell you now.','Cannot predict now.','Concentrate and ask again.','Don\'t count on it.','My reply is no.','My sources say no.','Outlook not so good.','Very doubtful.');
	shuffle($balls);
	send_msg($channel,$balls[0]);
}
?>