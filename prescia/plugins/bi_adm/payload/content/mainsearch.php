<?

	$r = $core->checkHackAttempt(cleanString($_REQUEST['searchfield']));
	$parameters = array();
	foreach ($core->modules as $mname => &$module) {
		if (!$module->options[CONS_MODULE_SYSTEM] &&
			!$module->linker &&
			$module->name != CONS_AUTH_USERMODULE &&
			$module->name != CONS_AUTH_GROUPMODULE &&
			in_array("edit",$module->options[CONS_MODULE_NOADMINPANES])===false &&
			$module->keys[0] == "id" &&
			$core->authControl->checkPermission($module)
				) {
			$descriptionF = "";
			foreach ($module->fields as $fname => &$field) {
				if ($field[CONS_XML_TIPO] == CONS_TIPO_TEXT) {
					$descriptionF = $fname;
					break;
				} else if ($field[CONS_XML_TIPO] == CONS_TIPO_VC && $fname != $module->title)
					$descriptionF = $fname;
			}	
			$parameters[] = array('module' => $mname,
						  'link' => 'edit.php?module='.$mname."&id={id}",
						  'limit' => 10,
						  'description' => $descriptionF,
						  'where' => '('.$mname.".".$module->title." LIKE \"%".$r."%\"".($descriptionF!=''?" OR ".$mname.".".$descriptionF." LIKE \"%".$r."%\"":"").")");
		}	
	}
	
	$results = $core->fullSearch($parameters);
	
	$obj = $core->template->get("_results");
	$temp = "";
	$toShow = count($results)>50?50:count($results);
	for ($c=0;$c<$toShow;$c++) {
		$results[$c]['#'] = $c;
		$temp .= $obj->techo($results[$c]);
	}
	
	$core->template->assign("total",count($results));
	$core->template->assign("searchfield",$r);
	if (count($results)>50) $temp .= $core->langOut("toomany_search_results");
	
	$core->template->assign("_results",$temp);