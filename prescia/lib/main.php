<?/* Main function pack
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto (www.prescia.net)
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Last update: 14.8.23
-*/

# Basic start
function getmicrotime() { list($usec, $sec) = explode(" ", microtime()); return ((float)$usec + (float)$sec); } # we want to time things ASAP
$temp = getmicrotime();
define ("CONS_STARTTIME",$temp);
error_reporting(E_ALL); // Error handling, leave E_ALL. Only the weak hide their mistakes and warnings - got a warning? FIX IT!
setlocale ( LC_CTYPE, 'C' ); // UTF-8 performance/compatibility improvement

# Get session kicking (if no session or valid PHPSESSID - change PHPSESSID if you use another)
if (!isset($_REQUEST['PHPSESSID']) || (isset($_REQUEST['PHPSESSID']) && preg_match('/^([a-zA-Z0-9,\-]+)$/',$_REQUEST['PHPSESSID']))) {
	session_start();
} else {
	unset($_REQUEST['PHPSESSID']);
	session_start();
}
if (isset($_REQUEST['nosession'])) {
	$_SESSION = array(); // redundant destruction
	session_destroy();
	session_start();
}

# Ip handling
include_once CONS_PATH_INCLUDE."ipv6.php";
if (!isset($_SESSION['ONSERVER']) || isset($_REQUEST['nocache'])) { // these are usually production IP's where we force debug mode
	$temp = array('10.0.', // common subnets:
				  '127.0.',
				  '192.168.',
				  '::ffff:c0a8:', // 192.168. in ipv6 compressed
				  '::ffff:0a00:', // 10.0. in ipv6 compressed
				  '::ffff:7f00:', // 127.0. in ipv6 compressed
				  '0:0:0:0:0:ffff:c0a8:', // 192.168. in ipv6 uncompressed
				  '0:0:0:0:0:ffff:0a00:', // 10.0. in ipv6 uncompressed
				  '0:0:0:0:0:ffff:7f00:', // 127.0. in ipv6 uncompressed
				  '::1', // ipv6 localhost compressed
				  '0:0:0:0:0:0:0:1'); // ipv6 localhost uncompressed
	$server_addr = GetIP(false);

	$onserver = true;
	foreach ($temp as $tip) {
		if (substr($server_addr,0,strlen($tip)) == $tip) {
			$onserver = false;
			break;
		}
	}

	define ("CONS_ONSERVER",$onserver);
	$_SESSION['ONSERVER'] = $onserver; // session cache
	unset($server_addr);
	unset($onserver);
} else
	define ("CONS_ONSERVER",$_SESSION['ONSERVER']);

$remote_addr = GetIP();
define ("CONS_IP",$remote_addr);
define ("CONS_IPV6",strpos($remote_addr,":")===false);
unset($remote_addr);

# Proper SWF object
if (strpos(isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"","MSIE") !== false) # IE
	define ("SWF_OBJECT","<object type=\"application/x-shockwave-flash\" classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0\" width=\"{W}\" height=\"{H}\">\n <param name=\"movie\" value=\"{FILE}\" /><param name=\"wmode\" value=\"transparent\" />\n<param name=\"pluginurl\" value=\"http://www.macromedia.com/go/getflashplayer\" />\n</object>\n");
else
	define ("SWF_OBJECT","<object type=\"application/x-shockwave-flash\" classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0\" width=\"{W}\" height=\"{H}\">\n <param name=\"movie\" value=\"{FILE}\" /><param name=\"wmode\" value=\"transparent\" />\n <object type=\"application/x-shockwave-flash\" width=\"{W}\" height=\"{H}\" data=\"{FILE}\">  <param name=\"movie\" value=\"{FILE}\" /><param name=\"wmode\" value=\"transparent\" />\n <param name=\"pluginurl\" value=\"http://www.macromedia.com/go/getflashplayer\" />\n</object>\n</object>\n");

# GZIP accepted
define ("CONS_GZIP_OK",isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strstr( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'));

# Force register globals and magic quotes OFF (@ php 5.4+ you can remove everything under this line)
if (version_compare(phpversion(), '5.4.0', '<')) {
	if (@ini_get('register_globals')) include_once CONS_PATH_INCLUDE."_regglobal.inc.php";
	if (@get_magic_quotes_gpc()) include_once CONS_PATH_INCLUDE."_mqgpc.inc.php";
}
