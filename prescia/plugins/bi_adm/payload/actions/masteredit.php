<?

	if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<100 || strpos(CONS_MASTERDOMAINS,$_SESSION['DOMAIN'])===false) $core->fastClose(403);
	
	if (isset($_REQUEST['haveinfo'])) {
		
		$domains = cReadFile(CONS_PATH_SETTINGS."domains");
		$domains = explode("\n",str_replace("\r","",preg_replace("/(\t| ){1,}/"," ",$domains)));
		$output = "";
		
		$added  =false;
		foreach ($domains as $dline) {
			if (strlen($dline) == 0 || $dline[0] == "#")
				$output .= $dline."\n";
			else {
				$dline = explode(" ",$dline);
				if ($dline[0] == $_REQUEST['prevcode']) { 
					// it's the line we where editing
					$output .= trim($_REQUEST['code'])."\t".trim($_REQUEST['domains'])."\n";
					$added = true;
				} else
					$output .= implode(" ",$dline)."\n";
			}
		}
		
		if (!$added)
			$output .= trim($_REQUEST['code'])."\t".trim($_REQUEST['domains'])."\n";

		cWriteFile(CONS_PATH_SETTINGS."domains",$output);
		@unlink(CONS_PATH_CACHE."domains.dat");

		
		$core->close(false);
		header("location: master.php?debugmode=true&nocache=true");
		$core->action = "master";
		
		
	}
