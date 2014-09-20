<?

	if (!$core->authControl->checkPermission('bi_adm','can_undo')) {
		$core->fastClose(403);
		return;
	}
	if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
		$core->fastClose(404);
		return;
	}
	
	// load up what we want to undo
	$undo = $core->loaded('bi_undo');
	$sql = $undo->get_base_sql($undo->name.".id=".$_REQUEST['id']);
	$core->dbo->query($sql,$r,$n);
	if ($n == 0) {
		$core->fastClose(404);
		return;
	}
	
	$plugin = $core->loadedPlugins['bi_undo'];
	$sucess = $plugin->undo($_REQUEST['id'],$r);
	
	if ($sucess) $core->action = "edit";
	else $core->action = "historymain";
	