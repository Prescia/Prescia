<?

	if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<99 || strpos(CONS_MASTERDOMAINS,$_SESSION['DOMAIN'])===false) $core->fastClose(403);

	$domains = unserialize(cReadFile(CONS_PATH_CACHE."domains.dat"));
	$innerWidth = 390;
	$graphHeight = 50;
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

	$browsers = array();
	$highestb = 0;
	$sum = 0;
	$hasBrowser = false;

	foreach ($codes as $code => $urls) {

		// cron
		$file = CONS_PATH_LOGS.$code."/scripttime.dat";
		if (is_file($file)) {

			$statsdata = unserialize(cReadFile($file));
			for ($c=0;$c<count($statsdata[5]);$c++) {
				$sum += $statsdata[5][$c][0];
			}

			// hits
			for ($c=0;$c<7;$c++) {
				if (isset($statsdata[5][6-$c])) {
					$week[$c] += $statsdata[5][6-$c][0];
					if ($week[$c]>$highest) $highest = $week[$c];

				}
			}
			// browsers (last month)
			if (isset($statsdata[6])) {
				foreach ($statsdata[6] as $browser => $h) {
					if (!isset($browsers[$browser]))
						$browsers[$browser] = $h;
					else
						$browsers[$browser] += $h;
					if ($browsers[$browser] > $highestb)
						$highestb = $browsers[$browser];
				}
				$hasBrowser = true;
			}
		}
	}
	for ($c=0;$c<7;$c++) {
		$pct = ceil($graphHeight*$week[$c]/$highest);
		$core->template->assign('allhits'.$c,$graphHeight-$pct);
		$core->template->assign('allhits'.$c.'b',$pct);
	}
	$core->template->assign("total",$sum);

	if ($hasBrowser) {
		// now we merge browsers
		$simpleList = array(array('browser' => 'Internet Explorer','hits' => 0,'code' => '0', 'color' => "3333EE"), // code = image code
								array('browser' => 'Firefox','hits' => 0,'code' => '1', 'color' => "FF9933"),
								array('browser' => 'Safari','hits' => 0,'code' => '3', 'color' => "AAAAEE"),
								array('browser' => 'Chrome','hits' => 0,'code' => '4', 'color' => "99EE44"),
								array('browser' => 'Opera','hits' => 0,'code' => '2', 'color' => "EE0033"),
								array('browser' => $core->langOut('others'),'hits' => 0,'code' => '5', 'color' => '666666'), // <- not count towards STATEDbrowsers
								);
		$total = 0;
		foreach ($browsers as $browser => $hits) {
			$total += $hits;
			if (strpos($browser,'xplorer') !== false) {
				$simpleList[0]['hits'] += $hits;
			} else if (strpos($browser,'irefox') !== false) {
				$simpleList[1]['hits'] += $hits;
			} else if (strpos($browser,'hrome') !== false) {
				$simpleList[3]['hits'] += $hits;
			} else if (strpos($browser,'afari') !== false) {
				$simpleList[2]['hits'] += $hits;
			} else if (strpos($browser,'pera') !== false) {
				$simpleList[4]['hits'] += $hits;
			} else {
				$simpleList[5]['hits'] += $hits;
			}
		}
		if ($total == 0) $total = 1;

		# main browsers simplified list
		$obj = $core->template->get("_browser");
		$output = "";
		for ($c=0;$c<count($simpleList);$c++) {
			$simpleList[$c]['percent'] = 100*$simpleList[$c]['hits'] / $total;
			$simpleList[$c]['width'] = ceil($innerWidth* $simpleList[$c]['hits'] / $total);
			$output .= $obj->techo($simpleList[$c]);
		}
		$core->template->assign("_browser",$output);

		# Browser complete list
		$obj = $core->template->get("_browserEX");
		$output = "";

		function browsersort($a,$b) {
			return ($a<$b)?1:(($a>$b)?-1:0);
		}
		uasort($browsers,'browsersort');

		foreach ($browsers as $browser => $hits) {
			$dataOut = array('browser' => $browser, 'hits' => $hits);
			$dataOut['percent'] = 100*$hits / $total;
			$dataOut['width'] = ceil($innerWidth * $hits / $total);
			$output .= $obj->techo($dataOut);
			if ($hits < 5) break; # we don't care
		}
		$core->template->assign("_browserEX",$output);
	}
