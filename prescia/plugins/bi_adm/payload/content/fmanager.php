<?

	if (!$core->authControl->checkPermission('bi_adm','can_fm'))
		$core->fastClose(403);

	// in case the user clicks an image
	$core->addLink('shadowbox/shadowboxwhite.css');
	$core->addLink('shadowbox/shadowbox.js');
	$core->template->constants['HEADUSERTAGS'] .= "\n<script type=\"text/javascript\"><!--\nShadowbox.init({skipSetup:true});\n//--></script>";

	if (isset($_REQUEST['dir'])) {
		$dir = str_replace(".","",$_REQUEST['dir']);
		if ($dir != "" && $dir[strlen($dir)-1] == "/")
			$dir = substr($dir,0,-1);
		if ($dir != "" && $dir[0] = "/")
			$dir = substr($dir,1);
		if (!is_dir(CONS_FMANAGER.$dir))
			$dir = "/";
		else
			$dir = "/".$dir;
	} else
		$dir = "/";

	$core->template->assign("dir",$dir);
	$core->template->assign("maxupload",ini_get('upload_max_filesize'));

	# Quota:
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

	// safe fmanager?
	$fm = $core->loaded('bi_fm');
	if ($fm !== false) {
		$core->runContent(CONS_AUTH_USERMODULE,$core->template,"","_permusers",false,"permusersin");
		$core->runContent(CONS_AUTH_GROUPMODULE,$core->template,"","_permgroup",false,"permgroupsin");
		$core->template->assign("safefolder",CONS_FMANAGER_SAFE);
	} else
		$core->template->assign("_bi_fm");

?>