<?
	return; // EM MANUTENÇÃO

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
