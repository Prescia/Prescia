<?
	// master options (remove if not master)
	if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<100) $core->template->assign("_master");
	if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<99) $core->template->assign("_highadmin");

	if ($core->context_str == "/")
		$core->template->assign("_hasSite");

	// module log --
	$temp = $core->cacheControl->getCachedContent('admindex_modules',30); // get cached content
	$shown = 0;
	if ($temp !== false) {
		$core->template->assign("_module",$temp);
	} else {
		$temp = "";
		$template = $core->template->get("_module");
		foreach ($core->modules as $name => &$module) {
			if (!$module->options[CONS_MODULE_SYSTEM] && !$module->linker) {
				if ($core->authControl->checkPermission($name)) {
					$sql = "SELECT count(*) FROM ".$module->dbname;
					$n = $core->dbo->fetch($sql);
					$temp .= $template->techo(array('module' => $name,
													'name' => $core->langOut($name).($module->options[CONS_MODULE_PARTOF]!=''?" (".$core->langOut($module->options[CONS_MODULE_PARTOF]).")":""),
													'n' => $n)
											 );
					$shown++;
				}
			}
		}
		$core->template->assign("_module",$temp);
		$core->cacheControl->addCachedContent('admindex_modules',$temp,true);
	}

	// Action log --
	$temp = $core->cacheControl->getCachedContent('admindex_actionlog',30);
	if ($temp !== false) {
		$core->template->assign("_actions",$temp);
	} else {
		$maxActions = $shown>0?$shown:10;
		$temp = "";
		$template = $core->template->get("_actions");
		function appendActs(&$core,&$output,&$template,$data,$limit=20) {
			$added = 0;
			$size = count($data);
			for ($c=$size-1;$c>=0;$c--) {
				$line = explode("|",$data[$c]);
				$time = substr($line[0],0,14);
				$line[0] = substr($line[0],14);
				if (count($line)>0 && isset($line[1]) && $line[1] != "") {
					$coreData = array();
					$coreData['hora'] = "<strong>".$time[6].$time[7]."/".$time[4].$time[5]."</strong> ".$time[8].$time[9].":".$time[10].$time[11].":".$time[12].$time[13];
					$coreData['module'] = $core->langOut($line[0]);
					$coreData['action'] = $core->langOut($line[1]);
					$coreData['parameters'] = $line[2];
					$coreData['author'] = $line[3];
					$added++;
					$output = $template->techo($coreData).$output;
					if ($added >= $limit) return $added;
				}
			}
			return $added;
		}
		// today's logs
		$previousDay = date("Ymd");
		$added = 0;
		if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/act".$previousDay.".log"))
			$added = appendActs($core,$temp,$template,explode("\n",cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/act".$previousDay.".log")),$maxActions);
		$maxActions -= $added;
		if ($maxActions>0) {
			// 1 day ago
			$previousDay = datecalc(date("Y-m-d"),0,0,-1);
			$previousDay = str_replace("-","",$previousDay);
			if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/act".$previousDay.".log"))
				appendActs($core,$temp,$template,explode("\n",cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/act".$previousDay.".log")),$maxActions);
		}
		$core->template->assign("_actions",$temp);
		$core->cacheControl->addCachedContent('admindex_actionlog',$temp,true);
	}

	// Warnings to developer
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/fulltest.log") && isset($core->loadedPlugins['bi_dev']))
		$core->template->assign("bi_dev",cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/fulltest.log"));
	else
		$core->template->assign("_devwarning");

	// Statistics
	if ($this->hasStats) {
		// stats installed, show today's hits
		$stp = $core->loadedPlugins['bi_stats']->getHits(7);
		$biggest = 0;
		foreach ($stp as $stpi) {
			if ($stpi[0]>$biggest)
				$biggest = $stpi[0];
		}
		if ($biggest == 0) $biggest = 1;
		for ($c=6; $c>=0;$c--) {
			$p=6-$c;
			$st = isset($stp[$p]) ? $stp[$p][0] : 0;
			$core->template->assign("stheight".$c,ceil(100*$st/$biggest));
			$core->template->assign("sttop".$c,100-ceil(100*$st/$biggest));
			$core->template->assign("sthits".$c,$st);
		}
	} else
		$core->template->assign("_statistics");

	// Mural
	if (is_file(CONS_PATH_DINCONFIG.$_SESSION['CODE']."/mural.txt"))
		$core->template->assignFile('admmural',CONS_PATH_DINCONFIG.$_SESSION['CODE']."/mural.txt");


	// Calendar
	if (!CONS_BROWSER_ISMOB || isset($_SESSION['NOMOBVER'])) { // on mob version, do not show the calendar
		if (!function_exists('echoCalendar')) include_once CONS_PATH_INCLUDE."calendar.php";
		$core->template->assign("calendar",echoCalendar($core->template,200));
	}