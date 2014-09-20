<?

	if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<100 || strpos(CONS_MASTERDOMAINS,$_SESSION['DOMAIN'])===false) $core->fastClose(403);
	$isADD = !isset($_REQUEST['code']);
	
	$domains = unserialize(cReadFile(CONS_PATH_CACHE."domains.dat"));
	$codes = array();
	foreach ($domains as $url => $code) {
		if (!isset($codes[$code])) {
			$codes[$code] = array($url);
		} else
		$codes[$code][] = $url;
	}
	
	
	if (!$isADD && !isset($codes[$_REQUEST['code']])) $core->fastClose(404);

	if (!$isADD) {
		$core->template->assign("code",$_REQUEST['code']);
		$core->template->assign("domains",implode(",",$codes[$_REQUEST['code']]));
	}
		
		
		
	
	
