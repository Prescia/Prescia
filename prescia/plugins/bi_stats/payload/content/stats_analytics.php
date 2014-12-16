<?


	$core->addLink("calendar/dyncalendar.css");
	$core->addLink("calendar/dyncalendar.js");

	// ######################################## MAIN #########################################
	// -- outputs the raw data in javascript, so the client can build as per period request (easier on the server)
	// -- also allows the client side to draw the graphics in the canvas

	$statsfullObj = $core->loaded('statsdaily'); // per page and date, 5 years
	$statsrObj = $core->loaded('statsrefdaily'); // per referer, 5 years

	// this will output ALL data from 5 years
	// which is, worst case, 1830 entries (~55Kb)
	$outputArr = array();

	// hits, unique hits, browsing hits (interested), returning hits (24h)
	$sql = "SELECT data,sum(hits),sum(uhits),sum(bhits),sum(rhits) FROM ".$statsfullObj->dbname." GROUP BY data ORDER BY data DESC";
	$core->dbo->query($sql,$r,$n);
	for ($c=0;$c<$n;$c++) {
		list($data,$hits,$uhits,$bhits,$rhits) = $core->dbo->fetch_row($r);
		if ($uhits<$bhits) $uhits = $bhits; // weird bugs not getting uhits (uhit might have started on the day before, bhit the day next)
		$outputArr[$data] = array($hits,$uhits,$bhits,$rhits,0);
	}

	// khits = bookmarks
	$sql = "SELECT data,sum(hits) FROM ".$statsrObj->dbname." WHERE referer=\"\" GROUP BY data ORDER BY data DESC";
	$core->dbo->query($sql,$r,$n);
	for ($c=0;$c<$n;$c++) {
		list($data,$khits) = $core->dbo->fetch_row($r);
		if (isset($outputArr[$data]))
			$outputArr[$data][4] = $khits;
		else
			$outputArr[$data] = array($khits,$khits,0,0,$khits);
		if ($outputArr[$data][1] < $outputArr[$data][4]) $outputArr[$data][4] = $outputArr[$data][1]; // weird bug counting more khits than uhits (script aborted before counting uhit?)
	}

	$yesterday = datecalc(date("Y-m-d"),0,0,-1);
	$core->template->assign("yesterday",$yesterday);

	$echoedData = 0;
	$totalData = count($outputArr);
	$output = array();
	$antiLoop = 0;
	while (isset($outputArr[$yesterday]) || $echoedData < $totalData) {
		if (isset($outputArr[$yesterday])) {
			$output[] = array($yesterday,$outputArr[$yesterday][0],$outputArr[$yesterday][1],$outputArr[$yesterday][2],$outputArr[$yesterday][3],$outputArr[$yesterday][4]);
			$echoedData++;
		} else
			$output[] = array($yesterday,0,0,0,0,0);
		$yesterday = datecalc($yesterday,0,0,-1);
		$antiLoop++;
		if ($antiLoop>1850) break; // 5 years
	}

	$outputstr = "[";
	$addComma = false;
	foreach ($output as $o) {
		if ($addComma) $outputstr .= ",";
		else $addComma = true;
		$outputstr .= "['".array_shift($o)."',".implode(",",$o)."]";
	}
	$outputstr .= "]";
	
	$core->template->assign("data",$outputstr);

	// ########################################### 24h ######################################

	$statsObj = $core->loaded('stats'); // per hour, last 4 weeks
	$hours = array();
	for($h=0;$h<24;$h++) {
		$hours[$h] = array(0,0);
	}

	$yesterday = datecalc(date("Y-m-d"),0,0,-1);

	$sql = "SELECT data,hour,sum(hits),sum(uhits) FROM ".$statsObj->dbname." WHERE data>='$yesterday' GROUP BY data,hour ORDER BY data DESC";
	$core->dbo->query($sql,$r,$n);
	for ($c=0;$c<$n;$c++) {
		list($data,$hour,$hits,$uhits) = $core->dbo->fetch_row($r);
		if ($data != date("Y-m-d") && $hour <= date("H")) continue; // more than 24h
		$hours[$hour] = array($hits,$uhits);
	}

	$outputstr = "[";
	$addComma = false;
	foreach ($hours as $h => $o) {
		if ($addComma) $outputstr .= ",";
		else $addComma = true;
		$outputstr .= "[".implode(",",$o)."]";
	}
	$outputstr .= "]";
	$core->template->assign("statstoday",$outputstr);


	// ########################################## TIME ######################################
	// -- outputs the graphic, since this is fixed and does not creates graphics

	$datebuffer = array();
	$df = date("Y-m-d")." 00:00:00";
	$di = datecalc($df,0,0,-28); // 4 weeks, the database holds 29 days
	$output = "";
	$output2 = "";
	$daywidth = floor(1000 / 8);
	$hourheight = floor(240 / 24);

	$max = 1;
	$maxu = 1;
	$horarios = array();
	$core->dbo->query("SELECT sum(hits) as hits,sum(uhits) as uhits, data, hour FROM ".$statsObj->dbname." WHERE data<'$df' AND data>='$di' GROUP BY data, hour",$r,$n);
	for ($c=0;$c<$n;$c++) {
		$data = $core->dbo->fetch_assoc($r);
		if (!isset($datebuffer[$data['data']])) {
			$datebuffer[$data['data']] = date("w",strtotime($data['data']));
		}
		$weekday = $datebuffer[$data['data']];
		if (!isset($horarios[$weekday."_".$data['hour']])) {
			$horarios[$weekday."_".$data['hour']] = $data;
			$horarios[$weekday."_".$data['hour']]['sum'] = 1;
		} else {
			$horarios[$weekday."_".$data['hour']]['hits'] += $data['hits'];
			$horarios[$weekday."_".$data['hour']]['uhits'] += $data['uhits'];
			$horarios[$weekday."_".$data['hour']]['sum']++;
		}
	}

	foreach ($horarios as $horario => $hdata) {
		if ($hdata['hits'] / $hdata['sum'] > $max) $max = $hdata['hits'] / $hdata['sum'];
		if ($hdata['uhits'] / $hdata['sum'] > $maxu) $maxu = $hdata['uhits'] / $hdata['sum'];
	}

	$translateweek = array(0 => 'Domingo',
						   1 => 'Segunda',
						   2 => 'Terça',
						   3 => 'Quarta',
						   4 => 'Quinta',
						   5 => 'Sexta',
						   6 => 'Sábado'
						   );

	foreach ($horarios as $horario => $hdata) {
		// hits
		$corRED = 255*($hdata['hits']/$hdata['sum'])/$max;
		$corBLUE = 255-$corRED;
		$corRED = dechex($corRED);
		$corGREEN = dechex(floor($corBLUE/2));
		$corBLUE = dechex($corBLUE);
		if (strlen($corRED)==1) $corRED = "0".$corRED;
		if (strlen($corGREEN)==1) $corGREEN = "0".$corGREEN;
		if (strlen($corBLUE)==1) $corBLUE = "0".$corBLUE;
		$horario = explode("_",$horario); // weekday, hour
		$output .= "<div title=\"".$translateweek[$horario[0]]." ".$horario[1]."h: ".(floor($hdata['hits']/$hdata['sum']))." visitas\" style=\"width:".$daywidth."px;height:".$hourheight."px;left:".($daywidth*($horario[0]+1))."px;top:".($hourheight*$horario[1])."px;position:absolute;background:#".$corRED.$corGREEN.$corBLUE."\">&nbsp;</div>";

		// uhits
		$corRED = 255*($hdata['uhits']/$hdata['sum'])/$maxu;
		$corBLUE = 255-$corRED;
		$corRED = dechex($corRED);
		$corGREEN = dechex(floor($corBLUE/2));
		$corBLUE = dechex($corBLUE);
		if (strlen($corRED)==1) $corRED = "0".$corRED;
		if (strlen($corGREEN)==1) $corGREEN = "0".$corGREEN;
		if (strlen($corBLUE)==1) $corBLUE = "0".$corBLUE;
		$output2 .= "<div title=\"".$translateweek[$horario[0]]." ".$horario[1]."h: ".(floor($hdata['uhits']/$hdata['sum']))." pessoas\" style=\"width:".$daywidth."px;height:".$hourheight."px;left:".($daywidth*($horario[0]+1))."px;top:".($hourheight*$horario[1])."px;position:absolute;background:#".$corRED.$corGREEN.$corBLUE."\">&nbsp;</div>";

	}
	for ($c=0;$c<7;$c++) { // day names
		$output .= "<div title=\"".$translateweek[$c]."\" style=\"width:".$daywidth."px;height:16px;text-align:center;left:".($daywidth*($c+1))."px;top:".($hourheight*24)."px;position:absolute;font-size:10px\">".$translateweek[$c]."</div>";
		$output2 .= "<div title=\"".$translateweek[$c]."\" style=\"width:".$daywidth."px;height:16px;text-align:center;left:".($daywidth*($c+1))."px;top:".($hourheight*24)."px;position:absolute;font-size:10px\">".$translateweek[$c]."</div>";
	}
	for ($c=0;$c<24;$c+=2) { // hour
		$output .= "<div title=\"".$c.":00\" style=\"width:".$daywidth."px;border-bottom:1px solid #eeeeff;height:19px;text-align:center;left:0px;top:".($hourheight*$c)."px;position:absolute;font-size:10px\">".$c.":00</div>";
		$output2 .= "<div title=\"".$c.":00\" style=\"width:".$daywidth."px;border-bottom:1px solid #eeeeff;height:19px;text-align:center;left:0px;top:".($hourheight*$c)."px;position:absolute;font-size:10px\">".$c.":00</div>";
	}
	$core->template->assign('timelapse',$output);
	$core->template->assign('timelapse2',$output2);

	// ############################# top 20 pages ###########################
	// outputs raw data for top 20 pages, so the client can build them
	// -- also allows the client side to draw the graphics in the canvas

	// worst case scenario is a full graphic: 20 pages * 30 days = 600 entries, each with (date, hit, pageid, page) with a total about  24Kb
	$yesterday = datecalc(date("Y-m-d"),0,0,-1);
	$moduletranslator = array(); // action name => module
	$pages = array(); // (invert place) => [sum, page, hid] // <- outputed
	$pagesRV = array();
	//$previousMonth = datecalc(date("Y-m-d"),0,0,-31);

	// locate which pages are related to which module my HID
	foreach ($core->modules as $mname => &$module) {
		if (isset($module->options[CONS_MODULE_PUBLIC]) && $module->options[CONS_MODULE_PUBLIC] != '') {
			$p = explode("?",$module->options[CONS_MODULE_PUBLIC][0] == "/" ? substr($module->options[CONS_MODULE_PUBLIC],1) : $module->options[CONS_MODULE_PUBLIC]);
			if (count($p)==2 && strpos($p[1],"id=")!==false) { // can only process public pages in ? format that assume an id
				$p = explode(".",$p[0]);
				$p = $p[0];
				$moduletranslator[$p] = $mname;
			}

		}
	}

	$statsh = $core->loaded('statsdaily');

	# gets the top pages on this month
	$sql = "SELECT sum( hits ) AS h, page,hid FROM ".$statsh->dbname." WHERE DATA > NOW() - INTERVAL 32 DAY AND page <> 'setres' AND page <> '' GROUP BY page,hid ORDER BY h DESC LIMIT 20";
	$core->dbo->query($sql,$r,$n);
	$pagesTotal = $n;
	$where = array();
	for ($c=0;$c<$n;$c++) {
		$pages[$c] = $core->dbo->fetch_row($r); // hits, page, hid
		$where[] = "(page=\"".$pages[$c][1]."\" AND hid=\"".$pages[$c][2]."\")";
		$pagesRV[$pages[$c][1]."_".$pages[$c][2]] = $c; // locates index of pages per page_hid
	}

	// fill empty data for each
	for ($c=0;$c<$pagesTotal;$c++) {
		$pages[$c][3] = $core->langOut($pages[$c][1]).($pages[$c][2]==0?'':' ('.$pages[$c][2].')'); // translation
		$pages[$c][4] = array();
		for ($d=0;$d<31;$d++) $pages[$c][4][] = 0;
	}

	# now fill up the stats for each valid page
	$sql = "SELECT hits, page, data, hid FROM ".$statsh->dbname." WHERE data > NOW( ) - INTERVAL 32 DAY AND (".implode(" OR ",$where).") ORDER BY DATA ASC";
	$core->dbo->query($sql,$r,$n);
	for ($c=0;$c<$n;$c++) {
		list($hits,$page,$data,$hid) = $core->dbo->fetch_row($r);
		$datediff = date_diff_ex($yesterday,$data);
		$pages[$pagesRV[$page."_".$hid]][4][$datediff] = $hits;
	}

	# get page translations from modules
	for ($c=0;$c<$pagesTotal;$c++) {
		if (isset($moduletranslator[$pages[$c][1]]) && $pages[$c][2] != 0) {
			$mod = $core->loaded($moduletranslator[$pages[$c][1]]);
			$name = $core->dbo->fetch("SELECT ".$mod->title." FROM ".$mod->dbname." WHERE id=".$pages[$c][2]);
			if ($name == '') $name = $pages[$c][1].($pages[$c][2]==0?'':' ('.$pages[$c][2].')');
			$pages[$c][3] = trim($name);
		}
	}


	# output pages
	$outputstr = "[";
	$addComma = false;
	foreach ($pages as $pdata) {
		if ($addComma) $outputstr .= ",";
		else $addComma = true;
		$outputstr .= "[";
		$outputstr .= "'".str_replace("'","",$pdata[3])."',[".implode(',',$pdata[4])."]";
		$outputstr .= "]";
	}
	$outputstr .= "]";
	$core->template->assign("datapages",$outputstr);


	################################################ REFERRERS #################################

	$refD = $core->loaded("statsref");
	$refH = $core->loaded("statsrefdaily");

	$graphWidth = 400;

	# day #
	$sql = "SELECT sum(hits) as hits, referer FROM ".$refD->dbname." WHERE data > NOW() - INTERVAL 2 DAY GROUP BY referer ORDER BY hits DESC LIMIT 100";
	$core->dbo->query($sql,$r,$n);
	$refs = array();
	$total = 0;
	for($c=0;$c<$n;$c++) {
		$ref = $core->dbo->fetch_assoc($r);
		if ($ref['referer'] == '') $ref['referer'] = 'BOOKMARK';
		$refs[] = $ref;
		$total += $ref['hits'];
	}
	if ($total == 0) $total = 1;
	$refdObj = $core->template->get("_refd");
	$output = "";
	for($c=0;$c<$n;$c++) {
		$refs[$c]['width'] = ceil($graphWidth*$refs[$c]['hits']/$total);
		$output .= $refdObj->techo($refs[$c]);
	}
	$core->template->assign("_refd",$output);

	# month #
	$sql = "SELECT sum(hits) as h, referer FROM ".$refH->dbname." WHERE data > NOW() - INTERVAL 31 DAY  GROUP BY referer HAVING h>2 ORDER BY h DESC LIMIT 100";
	$core->dbo->query($sql,$r,$n);
	$refs = array();
	$total = 0;
	for($c=0;$c<$n;$c++) {
		$ref = $core->dbo->fetch_assoc($r);
		if ($ref['referer'] == '') $ref['referer'] = 'BOOKMARK';
		$refs[] = $ref;
		$total += $ref['h'];
	}
	if ($total == 0) $total = 1;
	$refdObj = $core->template->get("_refh");
	$output = "";
	for($c=0;$c<$n;$c++) {
		$refs[$c]['width'] = ceil($graphWidth*$refs[$c]['h']/$total);
		$output .= $refdObj->techo($refs[$c]);
	}
	$core->template->assign("_refh",$output);

	# entry #
	$sql = "SELECT sum(hits) as h, referer, entrypage FROM ".$refH->dbname." WHERE data > NOW() - INTERVAL 31 DAY GROUP BY referer,entrypage ORDER BY h DESC LIMIT 100";
	$core->dbo->query($sql,$r,$n);
	$refs = array();
	$total = 0;
	for($c=0;$c<$n;$c++) {
		$ref = $core->dbo->fetch_assoc($r);
		$refs[] = $ref;
		$total += $ref['h'];
	}
	if ($total == 0) $total = 1;
	$refdObj = $core->template->get("_refe");
	$output = "";
	for($c=0;$c<$n;$c++) {
		$refs[$c]['width'] = ceil($graphWidth*$refs[$c]['h']/$total);
		$output .= $refdObj->techo($refs[$c]);
	}
	$core->template->assign("_refe",$output);

    ########################################### PATH #####################################

    $pathmod = $core->loaded('statspath');
	$sql = "SELECT DISTINCT(page) FROM ".$pathmod->dbname." WHERE data > NOW() - INTERVAL 31 DAY";
	$pagesObj = $core->template->get("_pages");
	$output = "";
	$core->dbo->query($sql,$r,$n);
	for ($c=0;$c<$n;$c++) {
		$data = $core->dbo->fetch_assoc($r);
		$data['checked'] = $data['page'] == "index"?"selected":"";
		if ($data['page'] != "nl_track") // don't want it =p
			$output .= $pagesObj->techo($data);
	}
	$core->template->assign("_pages",$output);

	########################################## TECH ####################################

	$STATEDbrowsers = 5; // IE(0) FF(2) SA(3) CH(1) OP(4)
	$simpleList = array(array('browser' => 'Internet Explorer','hits' => 0,'code' => '0', 'color' => "3333EE"), // code = image code
						array('browser' => 'Chrome','hits' => 0,'code' => '4', 'color' => "99EE44"),
						array('browser' => 'Firefox','hits' => 0,'code' => '1', 'color' => "FF9933"),
						array('browser' => 'Safari','hits' => 0,'code' => '3', 'color' => "AAAAEE"),
						array('browser' => 'Opera','hits' => 0,'code' => '2', 'color' => "EE0033"),
						array('browser' => $core->langOut('others'),'hits' => 0,'code' => '5', 'color' => '666666'), // <- not count towards STATEDbrowsers
						);

	$innerWidth = 390;

	$statsb = $core->loaded('statsbrowser');

	# Load data
	$dias = array();
	$day = datecalc(date("Y-m-d"),0,0,-30);
	for ($c=0;$c<30;$c++) {
		$dias[$day] = array();
		for ($i=0;$i<$STATEDbrowsers;$i++) $dias[$day][$i] = 0;
		$day = datecalc($day,0,0,1);
	}
	$dias[$day] = array();
	for ($i=0;$i<$STATEDbrowsers;$i++) $dias[$day][$i] = 0;


	$sql = "SELECT hits, browser, data FROM ".$statsb->dbname." WHERE data>=NOW() - INTERVAL 31 DAY ORDER BY data ASC";

	$core->dbo->query($sql,$r,$n);
	$total  =0;
	$mobtotal = 0;
	for ($c=0;$c<$n;$c++) {
		$abrowser = $core->dbo->fetch_assoc($r);
		$total += $abrowser['hits'];
		if (strpos($abrowser['browser'],'(mob)') !== false || strpos($abrowser['browser'],'MOBILE/') !== false) $mobtotal += $abrowser['hits'];
		if (strpos($abrowser['browser'],'xplorer') !== false) { // explorer
			$dias[$abrowser['data']][0] += $abrowser['hits'];
			$simpleList[0]['hits'] += $abrowser['hits'];
		} else if (strpos($abrowser['browser'],'irefox') !== false) { // firefox
			$dias[$abrowser['data']][2] += $abrowser['hits'];
			$simpleList[2]['hits'] += $abrowser['hits'];
		} else if (strpos($abrowser['browser'],'hrome') !== false) { // chrome
			$dias[$abrowser['data']][1] += $abrowser['hits'];
			$simpleList[1]['hits'] += $abrowser['hits'];
		} else if (strpos($abrowser['browser'],'afari') !== false) { // safari
			$dias[$abrowser['data']][3] += $abrowser['hits'];
			$simpleList[3]['hits'] += $abrowser['hits'];
		} else if (strpos($abrowser['browser'],'pera') !== false) { // opera
			$dias[$abrowser['data']][4] += $abrowser['hits'];
			$simpleList[4]['hits'] += $abrowser['hits'];
		} else { // others
			$simpleList[5]['hits'] += $abrowser['hits'];
		}
	}
	if ($total == 0) $total = 1;
	$core->template->assign("mobpercent",100*$mobtotal/$total);

	# main browsers textual data
	$obj = $core->template->get("_browser");
	$output = "";
	for ($c=0;$c<count($simpleList);$c++) {
		$simpleList[$c]['percent'] = 100*$simpleList[$c]['hits'] / $total;
		$simpleList[$c]['width'] = ceil($innerWidth* $simpleList[$c]['hits'] / $total);
		$output .= $obj->techo($simpleList[$c]);
	}
	$core->template->assign("_browser",$output);

	# graph
	$yesterday = datecalc(date("Y-m-d"),0,0,-1);
	$day = $yesterday;
	$output = array();

	$outputstr = "[";
	for ($i=0;$i<$STATEDbrowsers;$i++) {
		$outputstr .= ($i!=0?",":"").'["'.$simpleList[$i]['browser'].'","'.$simpleList[$i]['color'].'"]';
	}
	$core->template->assign("browsernames",$outputstr."]");

	for ($c=0;$c<31;$c++) {
		$dados = array();
		$dados[0] = $day;
		for ($i=0;$i<$STATEDbrowsers;$i++) {
			$dados[$i+1] = isset($dias[$day])?$dias[$day][$i]:0;
		}
		$output[] = $dados;
		$day = datecalc($day,0,0,-1);
	}

	$outputstr = "[";
	$addComma = false;
	foreach ($output as $data) {
		if ($addComma) $outputstr .= ",";
		else $addComma = true;
		$outputstr .= "[".implode(",",$data)."]";
	}
	$core->template->assign("statsbrowser",$outputstr."]");


	// complete stats
	$sql = "SELECT sum(hits) as hits, browser FROM ".$statsb->dbname." WHERE data>=NOW() - INTERVAL 31 DAY GROUP BY browser ORDER BY hits DESC";
	$core->dbo->query($sql,$r,$n);
	$obj = $core->template->get("_browserEX");
	$output = "";
	for ($c=0;$c<$n;$c++) {
		$browser = $core->dbo->fetch_assoc($r);
		$browser['percent'] = 100*$browser['hits'] / $total;
		$browser['width'] = ceil($innerWidth * $browser['hits'] / $total);
		$output .= $obj->techo($browser);
		if ($browser['percent'] < 0.25) break; # we don't care
	}
	$core->template->assign("_browserEX",$output);


	#################################### RESOLUTION ########################################

	define("DIM_ST",0);
	define("DIM_WS",1);
	define("DIM_WS85",2);
	define("DIM_54",3);
	define("DIM_NS",4);

	// REMEMBER: some stupid mobile report in portrait mode (H:W)
	$cpack[DIM_ST] = array("2048x1536","1920x1440","1600x1200","1400x1050", "1280x960", "1152x864", "1024x768", "800x600", "640x480","320x240","768:1024"); // (4:3)
	$cpack[DIM_WS] = array("2560x1440,1280x720","1360x768","1366x768","1600x900","1920x1080","720x1280"); // WS (16:9)
	$cpack[DIM_WS85] = array("1280x800","1440x900","1680x1050","1920x1200"); // WS (8:5)
	$cpack[DIM_54] = array("2000x1600","1280x1024"); // (5:4)

	$statsb = $core->loaded('statsres');
	$sql = "SELECT sum(hits) as hits, resolution FROM ".$statsb->dbname." WHERE data>=NOW() - INTERVAL 31 DAY GROUP BY resolution ORDER BY hits DESC";
	$core->dbo->query($sql,$r,$n);
	$res=array();
	$total  =0;
	$simpleList = array(array('prop' => 'Standard 4:3','hits' => 0,'code' => DIM_ST),
					array('prop' => 'Widescreen 16:9','hits' => 0,'code' =>DIM_WS),
				    array('prop' => 'Widescreen 8:5','hits' => 0,'code' => DIM_WS85),
				    array('prop' => 'Standard 5:4','hits' => 0,'code' => DIM_54),
				    array('prop' => 'Non-standard','hits' => 0,'code' => DIM_NS)


				   );
	for ($c=0;$c<$n;$c++) {
	$ares = $core->dbo->fetch_assoc($r);
		$res[] = $ares;
		$total += $ares['hits'];
		if (in_array($ares['resolution'],$cpack[DIM_ST])) {
			$simpleList[0]['hits'] += $ares['hits'];
		} else if (in_array($ares['resolution'],$cpack[DIM_WS])) {
			$simpleList[1]['hits'] += $ares['hits'];
		} else if (in_array($ares['resolution'],$cpack[DIM_WS85])) {
			$simpleList[2]['hits'] += $ares['hits'];
		} else if (in_array($ares['resolution'],$cpack[DIM_54])) {
			$simpleList[3]['hits'] += $ares['hits'];
		} else {
			$simpleList[4]['hits'] += $ares['hits'];
		}
	}
	if ($total == 0) $total = 1;
	$obj = $core->template->get("_res");
	$output = "";
	for ($c=0;$c<$n;$c++) {
		$res[$c]['percent'] = 100*$res[$c]['hits'] / $total;
		$res[$c]['width'] = ceil($innerWidth * $res[$c]['hits'] / $total);
		$output .= $obj->techo($res[$c]);
		if ($res[$c]['percent'] < 0.25) break; # we don't care
	}
	$core->template->assign("_res",$output);

	$obj = $core->template->get("_prop");
	$output = "";
	for ($c=0;$c<count($simpleList);$c++) {
		$simpleList[$c]['percent'] = 100*$simpleList[$c]['hits'] / $total;
		$simpleList[$c]['width'] = ceil($innerWidth * $simpleList[$c]['hits'] / $total);
		$output .= $obj->techo($simpleList[$c]);
	}
	$core->template->assign("_prop",$output);

	#################################### REALTIME ########################################
	function counthitsrt($template, $params, $data, $processed = false) {
		if (!$processed)
			$data['hits'] = isset($data['fullpath'])?count(explode(",",$data['fullpath'])):1;
		return $data;
	}
	$core->runContent('STATSRT',$core->template,array("data > NOW() - INTERVAL 30 MINUTE","data_ini DESC",""),"_rvisitor",false,false,'counthitsrt');
	
	
	
	 