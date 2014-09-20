<?

	if (isset($core->loadedPlugins['bi_adm']))
		$core->template->constants['SKIN_PATH'] = CONS_INSTALL_ROOT.CONS_PATH_PAGES."_common/files/adm/skin/".$core->loadedPlugins['bi_adm']->skin."/";
	else
		$core->template->constants['SKIN_PATH'] = CONS_INSTALL_ROOT.CONS_PATH_PAGES.$_SESSION['CODE']."/files/";

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

	$currentLabels = isset($core->dimconfig['_labels'])?$core->dimconfig['_labels']:array();
	$temp = "";
	$tp = $core->template->get("_etiqueta");
	$l = 0;
	foreach ($currentLabels as $id => $et) {
		$et['id'] = $id;
		$et['CLASS'] = $l%2==0?'even':'odd';
		$l++;
		if ($l==2) $l=0; 
		$temp .= $tp->techo($et);
	}
	$core->template->assign("_etiqueta",$temp);