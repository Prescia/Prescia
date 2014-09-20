<? /* ---------------------------------
   | PART OF stats MODULE
--*/

	$graphWidth = 400;
	
	$refD = $core->loaded("statsref");
	$refH = $core->loaded("statsrefdaily");
	
	# day #
	$sql = "SELECT sum(hits) as hits, referer FROM ".$refD->dbname." WHERE data > NOW() - INTERVAL 2 DAY GROUP BY referer ORDER BY hits DESC";
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
	$sql = "SELECT sum(hits) as h, referer FROM ".$refH->dbname." WHERE data > NOW() - INTERVAL 1 MONTH  GROUP BY referer HAVING h>2 ORDER BY h DESC";
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
	$sql = "SELECT sum(hits) as h, referer, entrypage FROM ".$refH->dbname." WHERE data > NOW() - INTERVAL 1 MONTH GROUP BY referer,entrypage HAVING h>1 ORDER BY h DESC LIMIT 100";
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

	# Query
	$statsq = $core->loaded('statsquery');
	$sql = "SELECT sum(count) as hits, query, engine FROM ".$statsq->dbname." WHERE data>NOW() - INTERVAL 1 MONTH GROUP BY engine, query ORDER BY hits DESC LIMIT 100";
	$core->dbo->query($sql,$r,$n);
	$query=array();
	$biggest = 1;
	$total  =0;
	for ($c=0;$c<$n;$c++) {
		$aquery = $core->dbo->fetch_assoc($r);
		$query[] = $aquery;
		if ($aquery['hits'] > $biggest) $biggest = $aquery['hits'];
		$total += $aquery['hits'];
	}
	if ($total == 0) $total = 1;
	$obj = $core->template->get("_query");
	$output = "";
	for ($c=0;$c<$n;$c++) {
		$query[$c]['percent'] = 100*$query[$c]['hits'] / $total;
		$query[$c]['width'] = ceil($graphWidth * $query[$c]['hits'] / $biggest);
		$output .= $obj->techo($query[$c]);
	}
	$core->template->assign("_query",$output);

?>