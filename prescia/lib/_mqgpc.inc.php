<? // removes magic quotes, function proposed at http://php.net/manual/en/security.magicquotes.disabling.php

# if (@get_magic_quotes_gpc()) include CONS_PATH_INCLUDE."_mqgpc.inc.php";

$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
while (list($key, $val) = each($process)) {
	foreach ($val as $k => $v) {
		unset($process[$key][$k]);
		if (is_array($v)) {
			$process[$key][stripslashes($k)] = $v;
			$process[] = &$process[$key][stripslashes($k)];
		} else {
			$process[$key][stripslashes($k)] = stripslashes($v);
		}
	}
}
unset($process);
