<?


	$highResponseTime = 1000; // ms

	if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<99 || strpos(CONS_MASTERDOMAINS,$_SESSION['DOMAIN'])===false) $core->fastClose(403);

	if (isset($_REQUEST['reset'])) {
		$core->cacheControl->dumpTemplateCaches($_REQUEST['reset'] == 'heavy',true);
		$core->log[] = $core->langOut('reset_complete');
	}

	if (!is_file(CONS_PATH_CACHE."domains.dat") && !isset($_REQUEST['debugmode'])) {
		$core->close(false);
		header("location: master.php?debugmode=true&nocache=true");
		die();
	}

	$admObj = $core->loadedPlugins['bi_adm'];

	$domains = unserialize(cReadFile(CONS_PATH_CACHE."domains.dat"));

	$codes = array();

	foreach ($domains as $url => $code) {
		if (!isset($codes[$code])) {
			$codes[$code] = array($url);
		} else
			$codes[$code][] = $url;
	}

	$siteObj = $core->template->get("_site");
	$temp = "";

	$week = array();
	for ($c=0;$c<7;$c++)
		$week[$c] = 0;
	$highest = 0;
	$total = 0;
	$totalHoje = 0;

	foreach ($codes as $code => $urls) {
		$outputData = array('favicon' => '',
							'code' => $code,
							'debug' => 2,
							'debug_str' => '',
							'config' => 0,
							'e404' => 2,
							'404_str' => '0',
							'config_str' => '',
							'quota' => 2,
							'quota_str' => '',
							'errors' => 2,
							'errors_str' => '',
							'cron' => 0,
							'cron_str' => '',
							'hitaverage' => 1,
							'hits_str' => '',
							'botvisit' => 1,
							'bots_str' => '',
							'scripttimei' => 2,
							'scripttime' => 0,
							'backup' => 0,
							'backup_str' => 'none',
							'scheduledCronDay' => '',
							'domains' => implode(",",$urls),
							'onedomain' => $urls[0]
				);

		$isTest = false;
		if (strpos($outputData['domains'],$admObj->testDomainHash) !== false) {
			$outputData['debug'] = 3;
			$outputData['quota'] = 3;
			$outputData['errors'] = 3;
			$outputData['cron'] = 3;
			$outputData['hitaverage'] = 3;
			$outputData['botvisit'] = 3;
			$outputData['backup'] = 3;
			$isTest =true;
		}

		// favicon
		$file = CONS_PATH_PAGES.$code."/files/favicon";
		if (locateFile($file,$ext)) {
			$outputData['favicon'] = "<img src=\"/".$file."\" alt=\"\" width=\"16\" height=\"16\"/>";
		}

		// 404
		$file = CONS_PATH_LOGS.$code."/404.log";
		if (is_file($file)) {
			$temp404 = explode("\n",cReadFile($file));
			$outputData['404_str'] = count($temp404)-1;
			if (count($temp404)>10) $outputData['e404']--;
			if (count($temp404)>100) $outputData['e404']--;
			unset($temp404);
		}

		// config
		$file = CONS_PATH_DINCONFIG.$code."/din.dat";
		$dinconfig = array();
		if (is_file($file)) {
			$dinconfig = unserialize(cReadFile($file));
			if (isset($dinconfig['_ignoreoncp'])) continue;
			if (is_array($dinconfig)) {
				if ((isset($dinconfig['adminmail']) && strpos($dinconfig['adminmail'],"@") !== false) &&
					(isset($dinconfig['pagetitle']) && trim($dinconfig['pagetitle']) != '') &&
					(isset($dinconfig['metakeys']) && trim($dinconfig['metakeys']) != '') &&
					(isset($dinconfig['metadesc']) && trim($dinconfig['metadesc']) != '')
					)
					$outputData['config'] ++;
				else {
					$outputData['config_str'] = 'din.dat incomplete, missing: ';
					if (!isset($dinconfig['adminmail']) || strpos($dinconfig['adminmail'],"@") === false) $outputData['config_str'] .= "adminmail ";
					if (!isset($dinconfig['pagetitle']) || trim($dinconfig['pagetitle']) == '') $outputData['config_str'] .= "pagetitle ";
					if (!isset($dinconfig['metakeys']) || trim($dinconfig['metakeys']) == '') $outputData['config_str'] .= "metakeys ";
					if (!isset($dinconfig['metadesc']) || trim($dinconfig['metadesc']) == '') $outputData['config_str'] .= "metadesc ";
				}
			} else
				$outputData['config_str'] = 'din.dat corrupt';
			if (is_file(CONS_PATH_PAGES.$code."/_config/config.php") && is_file(CONS_PATH_PAGES.$code."/_config/admin.xml"))
				$outputData['config'] ++;
			else
				$outputData['config_str'] .= ' files missing';
		} else
			$outputData['config_str'] = "din.dat not found";

		// debugmode
		if (!$isTest) {
			if (is_array($dinconfig) && isset($dinconfig['_debugmode'])) {
				$outputData['debug'] = 2-$dinconfig['_debugmode'];
				$outputData['debug_str'] = $dinconfig['_debugmode']==2?'full debug':($dinconfig['_debugmode']==1?'partial debug':'normal');
			} else {
				$outputData['debug'] = 0;
				$outputData['debug_str'] = 'din.dat not found';
			}
			// quota
			if (is_array($dinconfig) && isset($dinconfig['quota']) && isset($dinconfig['_usedquota'])) {
				if ($dinconfig['_usedquota'] > $dinconfig['quota']*0.8) {
					$outputData['quota'] --;
					if ($dinconfig['_usedquota'] > $dinconfig['quota']*0.99) {
						$outputData['quota'] --;
					}
				}
				$outputData['quota_str'] = ceil(100*$dinconfig['_usedquota']/$dinconfig['quota'])."%";
			} else
				$outputData['quota_str'] = 'din.dat missing or corrupt';

			// errors
			if (is_file(CONS_PATH_LOGS.$code."/err".date('Ymd').".log") || !is_array($dinconfig) || !isset($dinconfig['_errcontrol']) || $dinconfig['_errcontrol']>0) {
				$outputData['errors'] = 1;
				$outputData['errors_str'] = isset($dinconfig['_errcontrol'])?$dinconfig['_errcontrol'].' on din.dat':'no _errcontrol';
				if (is_file(CONS_PATH_LOGS.$code."/err".date('Ymd').".log") && filesize(CONS_PATH_LOGS.$code."/err".date('Ymd').".log") > (0.9*CONS_MAX_LOGFILESIZE) || (isset($dinconfig['_errcontrol']) &&  $dinconfig['_errcontrol'] > (0.9*CONS_MAX_ERRORS))) {
					$outputData['errors'] = 0; // really now ... TOO many errors
				}
			}

			// backup
			if (is_array($dinconfig) && isset($dinconfig['_scheduledCronDay']) && isset($dinconfig['_scheduledCronDay'])) {
				$outputData['scheduledCronDay'] = $dinconfig['_scheduledCronDay'];
				$outputData['backup']++; // configured
				$outputData['backup_str'] = "configured but not present";
				$bckups = listFiles(CONS_PATH_BACKUP.$code."/","/.*\.sql/");
				if (count($bckups)>0) {
					$outputData['backup']++;
					$outputData['backup_str'] = "present";
				}
			}

		}

		// cron
		$file = CONS_PATH_LOGS.$code."/scripttime.dat";
		if (is_file($file)) {
			$statsdata = unserialize(cReadFile($file));
			if ($statsdata !== false && !$isTest) $outputData['cron']++;
		} else {
			$statsdata = false;
			$outputData['cron_str'] = 'scripttime missing';
			$outputData['hits_str'] = 'scripttime missing';
			$outputData['bots_str'] = 'scripttime missing';
		}

		if (is_array($dinconfig) && isset($dinconfig['_cronH']) && isset($dinconfig['_cronD'])) {
			if ($dinconfig['_cronH'] == date("H") && $dinconfig['_cronD'] == date("d"))
				if (!$isTest) $outputData['cron']++;
		 	else
		 		$outputData['cron_str'] .= ' reported cronH/cronD obsolete';
			if ($statsdata === false || $statsdata[0] != date("H"))
				$outputData['cron_str'] .= ' scripttime cron obsolete';
		} else
			$outputData['cron_str'] .= ' din.dat corrupt or missing';

		// hitaverage
		if ($statsdata !== false) {
			$sum = 0;
			$max = 0;
			for ($c=0;$c<count($statsdata[5]);$c++) {
				$sum += $statsdata[5][$c][0];
				if ($statsdata[5][$c][0]>$max) $max = $statsdata[5][$c][0];
			}
			$average = $sum/7;
			$multiplier = 24/(date("H")+1);
			$expectedHits = $statsdata[4] * $multiplier;
			if ($expectedHits > $average*5) $outputData['hitaverage'] = 0;
			else if ($expectedHits > $average*2) $outputData['hitaverage'] = 1;
			else $outputData['hitaverage'] = 2;

			$outputData['hits_str'] = $statsdata[4].' ('.$sum.' in a week)';
			$outputData['hits_show'] = $statsdata[4].'/'.$sum;

			// hits
			if ($max ==0) $max=1;
			for ($c=0;$c<7;$c++) {
				if (isset($statsdata[5][6-$c])) {
					$pct = ceil(18*$statsdata[5][6-$c][0]/$max);
					$week[$c] += $statsdata[5][6-$c][0];
					if ($week[$c]>$highest) $highest = $week[$c];
					$total += $statsdata[5][6-$c][0];
				} else
					$pct = 0;
				$outputData['hits'.$c] = 18-$pct;
				$outputData['hits'.$c.'b'] = $pct;


			}
			if (isset($statsdata[5][0])) $totalHoje += $statsdata[5][0][0];

			// bots
			if (!$isTest) {
				if ($statsdata[3]>0 && $statsdata[3]>($sum/2)) $outputData['botvisit'] = 0; // sum/2 = metade das visitas da semana
				else if ($statsdata[3]>0 && $statsdata[3]>($sum/7)) $outputData['botvisit'] = 1; // sum/7 = visitas por dia
				else $outputData['botvisit'] = 2;
			}
			$outputData['bots_str'] = $statsdata[3];

			// scripttime
			$outputData['scripttime'] = number_format($statsdata[1],1);
			$outputData['scripttimecached'] = number_format($statsdata[2],1);
			$outputData['scripttimei'] = $statsdata[1] > $highResponseTime ? 0 : ( $statsdata[1] > $highResponseTime/2 ? 1 : 2);

		} else {
			for ($c=0;$c<7;$c++) {
				$outputData['hits'.$c] = 20;
				$outputData['hits'.$c.'b'] = 0;
			}
			$outputData['hits_show'] = "?";
			$outputData['hits_str'] = "?";
			$outputData['scripttime'] = "?";
			$outputData['scripttimecached'] = '?';

		}

		if ($isTest)
			$temp .= $siteObj->techo($outputData);
		else
			$temp = $siteObj->techo($outputData).$temp;
	}

	if ($highest == 0) $highest = 1;
	for ($c=0;$c<7;$c++) {
		$pct = ceil(38*$week[$c]/$highest);
		$core->template->assign("allhits".$c,38-$pct);
		$core->template->assign("allhits".$c.'b',$pct);
	}
	$core->template->assign("allvisits",$totalHoje." / ".$total);

	$core->template->assign("_site",$temp);

	# botprotect
	$throttleFiles = listFiles(CONS_PATH_TEMP,"@throttle_(.*)\.dat@i");
	$out = "";
	$IPs = 0;
	foreach ($throttleFiles as $tf) {
		$thd = @unserialize(cReadFile(CONS_PATH_TEMP.$tf));
		preg_match("@throttle_(.*)\.dat@",$tf,$regs);
		$ip = str_replace("_",":",$regs[1]);
		foreach ($thd as $thname => $thditem) {
			if (substr($thname,0,4)=="hits") {
				$IPs++;
			} else
				$out .= "<span style='color:#ee1111'>".$ip." BANNED SINCE ".$thditem."</span>\n";
		}
	}
	$out .= "IPs monitored: $IPs\n";
	$core->template->assign("botprotect",$out);

