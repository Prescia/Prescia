<?

	$core->layout = 2;
	if (!isset($_REQUEST['module'])) echo "ERRO";
	else {
		$module = $core->loaded($_REQUEST['module']);
		if ($module === false) echo "ERRO";
		else {
			include_once(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/importer.php");
			$importerObj = new Cimporter($core);
			$iFields = $importerObj->fields($module,isset($_REQUEST['isexport']));
			
			$tp = $core->template->get("_field");
			$output = "";
			
			foreach ($iFields as $field) {
				$output .= $tp->techo($field);
			}
			
			$core->template->assign("_field",$output);
			$core->template->assign("total",count($iFields));
			if (!$importerObj->hasRemotes($iFields))
				$core->template->assign("_hasLinks","");	
		}
	}

