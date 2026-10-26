<?php

/** @var CPrescia $core Runtime payload context injected by the framework. */

	$core->layout = 2;

	$statsfullObj = $core->loaded('statsdaily'); // per page and date, 5 years
	$statsrObj = $core->loaded('statsrefdaily'); // per referer, 5 years

	$outputArr = array();

	// hits, unique hits, browsing hits (interested), returning hits (24h)
	$sql = "SELECT data,sum(hits),sum(uhits),sum(bhits),sum(rhits) FROM ".$statsfullObj->dbname." GROUP BY data ORDER BY data DESC";
	$r = false;
	$n = 0;
	$core->dbo->queryPrepared($sql, "", array(), $r, $n);
	for ($c=0;$c<$n;$c++) {
		list($data,$hits,$uhits,$bhits,$rhits) = $core->dbo->fetch_row($r);
		$outputArr[$data] = array($hits,$uhits,$bhits,$rhits,0);
	}

	$sql = "SELECT data,sum(hits) FROM ".$statsrObj->dbname." WHERE referer=\"\" GROUP BY data ORDER BY data DESC";
	$r = false;
	$n = 0;
	$core->dbo->queryPrepared($sql, "", array(), $r, $n);
	for ($c=0;$c<$n;$c++) {
		list($data,$khits) = $core->dbo->fetch_row($r);
		if (isset($outputArr[$data]))
			$outputArr[$data][4] = $khits;
		else
			$outputArr[$data] = array($khits,$khits,0,0,$khits);
	}

	$yesterday = datecalc(date("Y-m-d"),0,0,-1);

	$output = array();
	while (isset($outputArr[$yesterday])) {
		$output[] = array($yesterday,$outputArr[$yesterday][0],$outputArr[$yesterday][1],$outputArr[$yesterday][2],$outputArr[$yesterday][3],$outputArr[$yesterday][4]);
		$yesterday = datecalc($yesterday,0,0,-1);
	}

	$csv = fopen('php://temp', 'w+');
	fputcsv($csv, array('# DATE', 'HITS', 'UNIQUE', 'ACCEPTED UNIQUE', 'RETURN IN 24h UNIQUE', 'CAME FROM BOOKMARK UNIQUE'));
	foreach ($output as $o) {
		$date = (string)$o[0];
		if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date) !== 1) {
			continue;
		}
		fputcsv($csv, array($date, (int)$o[1], (int)$o[2], (int)$o[3], (int)$o[4], (int)$o[5]));
	}
	rewind($csv);
	$outputstr = stream_get_contents($csv);
	fclose($csv);
	header("Content-Description: File Transfer");
	header("Content-Length: ".strlen($outputstr));
	header("Pragma: public");
	header("Content-Type: text/csv; charset=utf-8");
	header("X-Content-Type-Options: nosniff");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Content-Disposition: attachment; filename=\"statistics".date("Y-m-d").".csv\"");

	echo $outputstr;
	$core->close();
