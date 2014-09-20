<?

	$usedSpace = quota(CONS_FMANAGER,true)*1024;
	$core->loadDimconfig(true);
	$quota = isset($core->dimconfig['quota'])?$core->dimconfig['quota']:CONS_MAX_QUOTA;
	$percent = $usedSpace / $quota;
	
	$core->template->assign('used',humanSize($usedSpace));
	$core->template->assign('quota',humanSize($quota));
	$core->template->assign("pct",number_format($percent*100,2));
	$pbar = ceil($percent*99);
	if ($pbar > 99) $pbar = 99;
	$core->template->assign("pctd",$pbar);
	
	
	$core->dimconfig['_usedquota'] = $usedSpace;
	$core->saveConfig();

?>