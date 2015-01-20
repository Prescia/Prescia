<? /* ---------------------------------
   | PART OF stats MODULE
--*/

	$graphHeight = 150;
	$graphWidth = 80;

	$page = isset($_REQUEST['page'])?cleanString($_REQUEST['page']):"index";

	$dataini = datecalc(date("Y-m-d"),0,0,-7);
	$datafim = date("Y-m-d");

	$core->template->assign("page",$page);

	## PAGE STATS ##
	$dias = array();
	$weekAgo = datecalc(date("Y-m-d"),0,0,-7);

	$day = $weekAgo;
	for ($c=0;$c<30;$c++) {
		$dias[$day] = array('hits' => 0, 'day' => substr($day,8,2));
		$day = datecalc($day,0,0,1);
	}

	$statsh = $core->loaded('statsdaily');
	$core->dbo->query("SELECT data,SUM(hits) as shits FROM ".$statsh->dbname." WHERE data >= '$weekAgo' AND page=\"$page\" GROUP BY data ORDER BY data ASC",$r,$n);
	$biggest = 1;
	for ($c=0;$c<$n;$c++) {
		$data=$core->dbo->fetch_row($r);
		$dias[$data[0]]['hits'] = $data[1];
		if ($data[1] > $biggest) $biggest = $data[1];
	}

	$graphObj = $core->template->get("_day");
	$dataObj = $core->template->get("_day2");
	$outputTop = "";
	$outputBottom = "";
	$day = $weekAgo;
	for ($c=0;$c<7;$c++) {
		$ph = $dias[$day]['hits'] / $biggest;
		$dias[$day]['top'] = $graphHeight - ceil($ph * $graphHeight);
		$dias[$day]['height'] = ceil($ph * $graphHeight);
		$outputTop .= $graphObj->techo($dias[$day]);
		$outputBottom .= $dataObj->techo($dias[$day]);
		$day = datecalc($day,0,0,1);
	}
	$core->template->assign("_day",$outputTop);
	$core->template->assign("_day2",$outputBottom);
	$outputTop = "";
	$outputBorrom = "";
	unset($dias);

	## how many hits in this interval the page had? ##
	$phits = $core->dbo->fetch("SELECT sum(hits) FROM ".$statsh->dbname." WHERE data >= '$dataini' AND data < '$datafim' AND page=\"$page\"");

	## ENTRY PAGES ##
	$statspath = $core->loaded('statspath');
	$sql = "SELECT sum(hits) as shits, page FROM ".$statspath->dbname." WHERE pagefoward='$page' AND data >= '$dataini' AND data < '$datafim' GROUP BY page ORDER BY shits DESC";
	$core->dbo->query($sql,$r,$n);
	$graphObj = $core->template->get("_pg");
	$output = "";
	$pages = array();
	$biggest = 0;
	$total = 0;
	for($c=0;$c<$n;$c++) {
		$data=$core->dbo->fetch_assoc($r);
		$data['hits'] = $data['shits'];
		$pages[] = $data;
		if ($data['hits'] > $biggest) $biggest = $data['hits'];
		$total += $data['hits'];
	}
	$outsideEntry = $phits - $total; # this is the number of ENTRY visits to the page
	if ($outsideEntry > $biggest) $biggest= $outsideEntry;
	$showOE = false;
	if ($total == 0) $total = 1;
	for($c=0;$c<$n;$c++) {
		if ($outsideEntry > $pages[$c]['hits'] && !$showOE) {
			$pw = $outsideEntry / $biggest;
			$data = array('width' => ceil($graphWidth * $pw),
						  'percent' => 100*$outsideEntry/$phits,
						  'hits' => $outsideEntry,
						  'page' => "INTERNET"
						  );
			$output .= $graphObj->techo($data);
			$showOE = true;
		}
		$pw = $pages[$c]['hits'] / $biggest;
		$pages[$c]['width'] = ceil($graphWidth * $pw);
		$pages[$c]['percent'] = 100*$pages[$c]['hits']/($phits==0?1:$phits);
		if ($pages[$c]['percent']<1) break;
		$output .= $graphObj->techo($pages[$c]);
	}
	$core->template->assign("_pg",$output);

	## EXIT PAGES ##
	$sql = "SELECT sum(hits) as shits, pagefoward FROM ".$statspath->dbname." WHERE page='$page' AND data >= '$dataini' AND data < '$datafim' GROUP BY pagefoward ORDER BY shits DESC";
	$core->dbo->query($sql,$r,$n);
	$graphObj = $core->template->get("_pg2");
	$output = "";
	$pages = array();
	$biggest = 0;
	$total = 0;
	for($c=0;$c<$n;$c++) {
		$data=$core->dbo->fetch_assoc($r);
		$data['hits'] = $data['shits'];
		$pages[] = $data;
		if ($data['hits'] > $biggest) $biggest = $data['hits'];
		$total += $data['hits'];
	}

	$outsideExits = $phits - $total; # this is the number of EXIT visits to the page
	if ($outsideExits > $biggest) $biggest= $outsideExits;
	$showOE = false;
	if ($total == 0) $total = 1;
	for($c=0;$c<$n;$c++) {
		if ($outsideExits > $pages[$c]['hits'] && !$showOE) {
			$pw = $outsideExits / $biggest;
			$data = array('width' => ceil($graphWidth * $pw),
						  'percent' => 100*$outsideExits/($phits==0?1:$phits),
						  'hits' => $outsideExits,
						  'pagefoward' => "INTERNET"
						  );
			$output .= $graphObj->techo($data);
			$showOE = true;
		}
		$pw = $pages[$c]['hits'] / $biggest;
		$pages[$c]['width'] = ceil($graphWidth * $pw);
		$pages[$c]['percent'] = 100*$pages[$c]['hits']/$total;
		if ($pages[$c]['percent']<1) break;
		$output .= $graphObj->techo($pages[$c]);
	}
	$core->template->assign("_pg2",$output);

?>