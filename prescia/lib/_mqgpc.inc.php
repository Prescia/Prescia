<?php // Legacy input-normalization helper retained only for old installations.

# This helper is intentionally not loaded on PHP 8+.

$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
foreach ($process as $key => $val) {
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
