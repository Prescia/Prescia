<? // ------------------------ Honeypot list bootup (catching is done inside core::checkDirectLink)
   // do not call this if you already know the useragent is a bot (double check is pointless)

if (!isset($_SESSION[CONS_SESSION_HONEYPOTLIST])) {
	$_SESSION[CONS_SESSION_HONEYPOTLIST] = @unserialize(cReadFile(CONS_PATH_TEMP."honeypot.dat")); // this file is reset DAILY on cron
	if (!is_array($_SESSION[CONS_SESSION_HONEYPOTLIST])) $_SESSION[CONS_SESSION_HONEYPOTLIST] = array();	
}
if (in_array($_SERVER['HTTP_USER_AGENT'],$_SESSION[CONS_SESSION_HONEYPOTLIST])) // we know user agent is set otherwise it would be a bot already
	$core->isbot = true;
