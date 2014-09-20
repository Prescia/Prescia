<?

	if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<99 || strpos(CONS_MASTERDOMAINS,$_SESSION['DOMAIN'])===false) $core->fastClose(403);
	
	$code = $_REQUEST['code'];
	
	$logs = "";
	$template = $core->template->get("_error");
	if (is_file(CONS_PATH_LOGS.$code."/err".date('Ymd').".log")) {
		
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
		appendErrors($core,$logs,$template,explode("\n",cReadFile(CONS_PATH_LOGS.$code."/err".date('Ymd').".log")));
		
		
	} else
		$logs = "No log";
	
	$core->template->assign("_error",$logs);
