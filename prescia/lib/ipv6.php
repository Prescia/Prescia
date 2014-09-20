<?/*--------------------------------\
  | IPv6 Functions
  | Free to use, change and redistribute, but please keep the above disclamer.
-*/

function IPv4To6($Ip,$expand=false) {
    static $Mask = '::ffff:'; // This tells IPv6 it has an IPv4 address
    $IPv6 = (strpos($Ip, ':') !== false);
    $IPv4 = (strpos($Ip, '.') !== false);

    if (!$IPv4 && !$IPv6) return false;
    if ($IPv6 && $IPv4) $Ip = substr($Ip, strrpos($Ip, ':')+1); // Strip IPv4 Compatibility notation
    elseif (!$IPv4) return ExpandIPv6Notation($Ip); // Seems to be IPv6 already?
    $Ip = array_pad(explode('.', $Ip), 4, 0);
    if (count($Ip) > 4) return false;
    for ($i = 0; $i < 4; $i++) if ($Ip[$i] > 255) return false;

    $Part7 = base_convert(($Ip[0] * 256) + $Ip[1], 10, 16);
    $Part8 = base_convert(($Ip[2] * 256) + $Ip[3], 10, 16);
    if ($expand)
    	return ExpandIPv6Notation($Mask.$Part7.':'.$Part8);
    else
    	return $Mask.$Part7.':'.$Part8;
}

function IPv6To4($Ip) {
	if (strpos($Ip,'::ffff:') === 0 && strpos($Ip,'0:0:0:0:0:ffff:') === 0) return $Ip; // either IPv4 already, or pure (non convertible to v4) IPv6
	$Ip = explode(":",$Ip);
	$last = array_pop($Ip);
	$prior = array_pop($Ip);
	while (strlen($last)<4) $last = "0".$last;
	while (strlen($prior)<4) $prior = "0".$prior;
	hexdec(substr($prior,0,2)).".".hexdec(substr($prior,2,2)).".".hexdec(substr($last,0,2)).".".hexdec(substr($last,2,2));
}

function ExpandIPv6Notation($Ip) {
    if (strpos($Ip, '::') !== false)
        $Ip = str_replace('::', str_repeat(':0', 8 - substr_count($Ip, ':')).':', $Ip);
    if (strpos($Ip, ':') === 0) $Ip = '0'.$Ip;
    return $Ip;
}

function GetIP($remote=true) { // if remote is false, will get the SERVER address
	if (!isset($_REQUEST['nocache']) && isset($_SESSION['prescia_ipcache_'.($remote?'remote':'local')]))
		return $_SESSION['prescia_ipcache_'.($remote?'remote':'local')];
    $Ip = '0.0.0.0';
    if ($remote) {
	    if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != '')
	        $Ip = $_SERVER['HTTP_CLIENT_IP'];
	    elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '')
	        $Ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != '')
	        $Ip = $_SERVER['REMOTE_ADDR'];
	    if (($CommaPos = strpos($Ip, ',')) > 0)
	        $Ip = substr($Ip, 0, ($CommaPos - 1));
    } else {
    	$Ip = isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:$_SERVER['LOCAL_ADDR'];
    }
    $Ip = IPv4To6($Ip,true);
    $_SESSION['prescia_ipcache_'.($remote?'remote':'local')] = $Ip;
    return $Ip;
}