<?

	if (!$core->authControl->checkPermission('bi_adm','can_logs'))
		$core->fastClose(403);

	function appendErrors(&$core,&$output,&$template,$data) {
		foreach ($data as $line) {
			$line = explode("|",$line);
			# date|id_client|uri|errCode|module|parameters|extended parameters|log[|...]
			$coreData = array();
			$coreData['date'] = array_shift($line);
			$coreData['id_client'] = array_shift($line);
			$coreData['uri'] = array_shift($line);
			$coreData['errCode'] = array_shift($line);
			$coreData['module'] = array_shift($line);
			$coreData['parameters'] = array_shift($line);
			$coreData['extended'] = array_shift($line);
			$coreData['log'] = implode("|",$line);
			if (is_numeric($coreData['errCode']) && isset($core->errorControl->ERRORS[$coreData['errCode']])) {
				$errorLevel = $core->errorControl->ERRORS[$coreData['errCode']];
				$coreData['level'] = $errorLevel < 10?0:($errorLevel<20?1:2);
				$output .= $template->techo($coreData);

			}
		}
	}

	$temp = "";
	$template = $core->template->get("_error");

	//3 day ago
	$previousDay = datecalc(date("Y-m-d"),0,0,-3);
	$previousDay = str_replace("-","",$previousDay);
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/err".$previousDay.".log"))
		appendErrors($core,$temp,$template,explode("\n",cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/err".$previousDay.".log")));

	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/out".$previousDay.".log"))
		$temp .= nl2br(cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/out".$previousDay.".log"));

	// 2 day ago
	$previousDay = datecalc(date("Y-m-d"),0,0,-2);
	$previousDay = str_replace("-","",$previousDay);
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/err".$previousDay.".log"))
		appendErrors($core,$temp,$template,explode("\n",cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/err".$previousDay.".log")));
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/out".$previousDay.".log"))
		$temp .= nl2br(cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/out".$previousDay.".log"));

	// 1 day ago
	$previousDay = datecalc(date("Y-m-d"),0,0,-1);
	$previousDay = str_replace("-","",$previousDay);
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/err".$previousDay.".log"))
		appendErrors($core,$temp,$template,explode("\n",cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/err".$previousDay.".log")));
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/out".$previousDay.".log"))
		$temp .= nl2br(cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/out".$previousDay.".log"));

	# Today
	$previousDay = date("Ymd");
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/err".$previousDay.".log"))
		appendErrors($core,$temp,$template,explode("\n",cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/err".$previousDay.".log")));
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/out".$previousDay.".log"))
		$temp .= nl2br(cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/out".$previousDay.".log"));

	if (CONS_HTTPD_ERRFILE != '') {
		$httpderrlog = str_replace("{Y}",date("Y"),CONS_HTTPD_ERRFILE);
		$httpderrlog = str_replace("{m}",date("m"),$httpderrlog);
		$httpderrlog = str_replace("{d}",date("d"),$httpderrlog);
		if (is_file(CONS_HTTPD_ERRDIR.$httpderrlog)) {
			$temp .= "<div style='font-color:red;margin-top:5px'>HTTPD errors detected on $httpderrlog log file:</div><br/><pre>";
			$temp .= cReadFile(CONS_HTTPD_ERRDIR.$httpderrlog);
			$temp .= "</pre>";
		}
	}


	$core->template->assign("_error",$temp);

	function appendActs(&$core,&$output,&$template,$data) {
		foreach ($data as $line) {
			$line = explode("|",$line);
			$time = substr($line[0],0,14);
			$line[0] = substr($line[0],14);
			if (count($line)>0 && isset($line[1]) && $line[1] != "") {
				$coreData = array();
				$coreData['hora'] = "<strong>".$time[6].$time[7]."/".$time[4].$time[5]."</strong> ".$time[8].$time[9].":".$time[10].$time[11].":".$time[12].$time[13];
				$coreData['module'] = $core->langOut($line[0]);
				$coreData['action'] = $core->langOut($line[1]);
				$coreData['parameters'] = $line[2];
				$coreData['author'] = $line[3];
				$output .= $template->techo($coreData);
			}
		}
	}


	$temp = "";
	$template = $core->template->get("_actions");

	//3 day ago
	$previousDay = datecalc(date("Y-m-d"),0,0,-3);
	$previousDay = str_replace("-","",$previousDay);
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/act".$previousDay.".log"))
		appendActs($core,$temp,$template,explode("\n",cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/act".$previousDay.".log")));

	// 2 day ago
	$previousDay = datecalc(date("Y-m-d"),0,0,-2);
	$previousDay = str_replace("-","",$previousDay);
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/act".$previousDay.".log"))
		appendActs($core,$temp,$template,explode("\n",cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/act".$previousDay.".log")));

	// 1 day ago
	$previousDay = datecalc(date("Y-m-d"),0,0,-1);
	$previousDay = str_replace("-","",$previousDay);
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/act".$previousDay.".log"))
		appendActs($core,$temp,$template,explode("\n",cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/act".$previousDay.".log")));

	# Today
	$previousDay = date("Ymd");
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/act".$previousDay.".log"))
		appendActs($core,$temp,$template,explode("\n",cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/act".$previousDay.".log")));

	$core->template->assign("_actions",$temp);

	//404 errors 24h
	if (isset($_REQUEST['clean404']))
		@unlink(CONS_PATH_LOGS.$_SESSION['CODE']."/404.log");
	else if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/404.log")) {
		$core->template->assignFile("log404",CONS_PATH_LOGS.$_SESSION['CODE']."/404.log",false,true);
	}


	//pm log 24h
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/pm.log"))
		$core->template->assignFile('logpm',CONS_PATH_LOGS.$_SESSION['CODE']."/pm.log",false,true);

