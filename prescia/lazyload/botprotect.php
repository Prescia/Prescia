<?/* -------------------------------- Prescia BOTPROTECT - local protection against DOS or FLOOD
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
  | Requires: basic.php (will load datetime if needed)
  | Called from core::domainLoad
-*/

$freepass = (isset($_SESSION[CONS_SESSION_ACCESS_LEVEL]) && $_SESSION[CONS_SESSION_ACCESS_LEVEL] >= 90); // high-level admins get free pass 
if (CONS_CRAWLER_WHITELIST_ENABLE) { // whitelisted user agents get freepass (good bots, like google ... kind of)
	$ua = isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"";
	if ($ua != '' && preg_match(CONS_CRAWLER_WHITELIST,$ua) === 1) $freepass = true;
}
if (!$freepass) {

	$throttle = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>
	<error>
		<status>403</status>
		<timestamp>{TS}</timestamp>
		<error-code>0000</error-code>
		<message>Throttle limit for calls to this resource is reached - access denied for {MORE} more seconds.</message>
	</error>";
	
	$now = date("Y-m-d H:i:s");
	$filename = CONS_PATH_TEMP."throttle_".str_replace(":","_",CONS_IP).".dat";
	
	# step 1: easy check if this IP is banned, if so, bye
	if (isset($_SESSION['BOTPROTECT_BANNED'])) { // session controlled ban
		include_once CONS_PATH_INCLUDE."datetime.php";
		$td = time_diff($now,$_SESSION['BOTPROTECT_BANNED']);
		if ($td<(CONS_BOTPROTECT_BANTIME*60)) {
			echo str_replace("{MORE}",(CONS_BOTPROTECT_BANTIME*60)-$td,str_replace("{TS}",$_SESSION['BOTPROTECT_BANNED'],$throttle));
			die();
		} else
			unset($_SESSION['BOTPROTECT_BANNED']);
	}
	
	# step 2: load ip controller (non session related) and check it
	if (is_file($filename)) {
		$thd = @unserialize(cReadFile($filename));
		if (!is_array($thd)) $thd = array();
	} else
		$thd = array();
	
	if (isset($thd['banned'])) {
		include_once CONS_PATH_INCLUDE."datetime.php";
		$td = time_diff($now,$thd['banned']);
		if ($td<(CONS_BOTPROTECT_BANTIME*60)) {
			echo str_replace("{MORE}",(CONS_BOTPROTECT_BANTIME*60)-$td,str_replace("{TS}",$td,$throttle));
			die();
		} else
			unset($thd['banned']);
	}
	
	# if we got here, one is not banned - yet
	if (!isset($thd['hits'])) $thd['hits'] = array();  // no record of this IP yet
	$banned = false;
	// cleanup old entries
	while (isset($thd['hits'][0]) && time_diff($now,$thd['hits'][0])>60)
		array_shift($thd['hits']);
	// add new hit
	$thd['hits'][] = $now;
	if (isset($_POST['login'])) $thd['hits'][] = $now; // ya, we count twice the fault if you are trying to login
	if (count($thd['hits'])>=CONS_BOTPROTECT_MAXHITS) {
		// sorry guy, you are banned
		$_SESSION['BOTPROTECT_BANNED'] = $now;
		$thd['banned'] = $now;
		$banned = true;
		// prescia log
		$this->errorControl->raise(171,CONS_IP." made ".count($thd['hits'])." in 60 seconds","IP BANNED");
	} else if (count($thd['hits'])>CONS_BOTPROTECT_MAXHITS/2) {
		// throttle requests (guy going too fast)
		sleep(1);
	}
	// save throttle
	cWriteFile($filename,serialize($thd));
	if ($banned) {
		echo str_replace("{MORE}",CONS_BOTPROTECT_BANTIME*60,str_replace("{TS}",$now,$throttle));
		die();
	}
	unset($banned);
	unset($thd);
	unset($now);
	unset($throttle);
}