<?php
$function_map['ctcp'] = array();
$hook_map['ctcp'] = array(
	'data_in'=>'ctcp_hook_data_in'
);
// let's define some usable hooks of our own
$hooks['ctcp_in'] = array();
$hooks['ctcp_out'] = array();
define('C_CTCP', chr(1)); // so the fx() function can do our CTCP wrapping for us
function ctcp_hook_data_in() {
	global $buffwords,$in_convo,$arguments,$channel;
	if (($buffwords[1]=='PRIVMSG'||$buffwords[1]=='NOTICE')&&$in_convo&&ord(trim(substr($buffwords[3],1)))==1) {
		// We're in a CTCP. Act like it.
		// acc. to http://www.irchelp.org/irchelp/rfc/ctcpspec.html
		$command = trim(substr($buffwords[3],2)); // Let's crop out the first two characters of what they sent, because it's just a colon and a C_CTCP.
		$command = strtoupper(rtrim($command,C_CTCP)); // Now we have to take off any trailing C_CTCPs
		$arguments = rtrim($arguments,C_CTCP); // Yes, the arguments too.
		call_hook('ctcp_in'); // we just got a ctcp, anybody want to hook up?
		if ($command=='VERSION'||$command=='FINGER'||$command=='USERINFO') {
			send_ctcp($channel,$command . ' ' . IRC_VERSION);
		} elseif ($command=='PING') {
			send_ctcp($channel,$command . ' ' . time());
		} elseif ($command=='TIME') {
			send_ctcp($channel,$command . ' ' . date('D M d H:i:s Y T'));
		} elseif ($command=='ERRMSG') {
			// I don't really understand this one, so Imma just echo, 'kay
			send_ctcp($channel,$command . ' ' . $arguments);
		} elseif ($command=='SOURCE') {
			send_ctcp($channel,$command . ' For a copy of me, visit https://github.com/flotwig/suphpbot');
		} elseif ($command=='CLIENTINFO') {
			send_ctcp($channel,$command . ' I know these CTCP commands: PING TIME ERRMSG SOURCE CLIENTINFO VERSION FINGER USERINFO');
		}
	}
}
function send_ctcp($target,$command) { // WE SEND CTCP USING THIS FUNCTION, BELIEVE IT OR NOT :O
	call_hook('ctcp_out');
	send('NOTICE ' . $target . ' :' . fx('CTCP',$command,TRUE));
}
