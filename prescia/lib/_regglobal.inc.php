<?
# if (@ini_get('register_globals')) include CONS_PATH_INCLUDE."_regglobal.inc.php";
# if REGISTER GLOBALS ON, force unregistering of variables.
# If you code using REGISTER GLOBALS ON, learn not to, you moron. 
foreach($_REQUEST as $name => $trash) {
	unset(${$name});
}
if (isset($_SESSION)) {
	foreach($_SESSION as $name => $trash) { # to be sure
		unset(${$name});
	}
}