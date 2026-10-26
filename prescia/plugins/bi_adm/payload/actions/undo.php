<?php

/** @var CPrescia $core Runtime payload context injected by the framework. */

	if (!$core->authControl->checkPermission('bi_adm','can_undo')) {
		$core->fastClose(403);
		return;
	}
		if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
			$core->fastClose(404);
			return;
		}
		$id = filter_var($_REQUEST['id'], FILTER_VALIDATE_INT);
		if ($id === false || $id < 1) {
			$core->fastClose(404);
			return;
		}

	// load up what we want to undo
	$undo = $core->loaded('bi_undo');
			$sql = "SELECT * FROM ".$undo->dbname." WHERE id=?";
		$r = false;
		$n = 0;
		$core->dbo->queryPrepared($sql,'i',array($id),$r,$n);
	if ($n == 0) {
		$core->fastClose(404);
		return;
	}

	$plugin = $core->loadedPlugins['bi_undo'];
		$sucess = $plugin->undo($id,$r);

	if ($sucess) $core->action = "edit";
	else $core->action = "historymain";
