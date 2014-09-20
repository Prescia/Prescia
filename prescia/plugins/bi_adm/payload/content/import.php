<?

	$core->loadAllmodules();
	$tp = $core->template->get("_modules");
	$output = "";
	foreach ($core->modules as $name => $module) {
		if (!$module->options[CONS_MODULE_SYSTEM] && $module->dbname != "" && $core->authControl->checkPermission($module) && $core->authControl->checkPermission($module,CONS_ACTION_INCLUDE)) {
			$hasP = false;
			# removes modules that are fully automated
			if ($module->permissionOverride != "") {
				for ($c=0;$c<9;$c++)
				if ($module->permissionOverride[$c] == "c") {
				$hasP = true;
				break;
				}
			} else $hasP = true;
			if ($hasP)
			$output .= $tp->techo(array('module'=>$name));
		}
	}
	$core->template->assign("_modules",$output);

	// --

	$arquivos = listFiles(CONS_PATH_SYSTEM."import/");
	$tp = $core->template->get("_phpscript");
	$output = "";
	foreach ($arquivos as $arquivo) {
		$arquivo = explode(".",$arquivo);
		if ($arquivo[0][0] != '_')
			$output .= $tp->techo(array("script"=>$arquivo[0]));
	}
	$core->template->assign("_phpscript",$output);

	if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<100) $core->template->assign("_master");

	if (isset($core->storage['failed'])) {
		$core->template->assign("failed",implode("<br/>",$core->storage['failed']));
	} else
		$core->template->assign("_hasfailed");

