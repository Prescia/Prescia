<?

	if (!$core->authControl->checkPermission('bi_labels','can_editlabels')) {
		$core->action = 403;
	} else if (isset($_REQUEST['haveinfo']) && isset($_REQUEST['id'])) {
		$label_array = array(
			'name' => $_REQUEST['name'],
			'module' => $_REQUEST['module'],
			'content' => $_REQUEST['content'],
			'cols' => $_REQUEST['cols'],
			'rows' => $_REQUEST['rows'],
			'pfl' => $_REQUEST['pfl'],
			'pft' => $_REQUEST['pft'],
			'sw' => $_REQUEST['sw'],
			'sh' => $_REQUEST['sh'],
			'ol' => $_REQUEST['ol'],
			'ot' => $_REQUEST['ot'],
			'fontsize' => $_REQUEST['fontsize'],
						);
		$core->loadDimconfig(true);
		$currentLabels = isset($core->dimconfig['_labels'])?$core->dimconfig['_labels']:array();
		if ($_REQUEST['id'] != '' && isset($currentLabels[$_REQUEST['id']])) {
			$currentLabels[$_REQUEST['id']] = $label_array;
		} else {
			$id = 1;
			while (isset($currentLabels[$id]))
				$id++;
			$currentLabels[$id] = $label_array;
		}
		$core->dimconfig['_labels'] = $currentLabels;
		$core->saveConfig();
		$core->log[] = $core->langOut('label_saved');
	} else if (isset($_REQUEST['delete'])) {
		$core->loadDimconfig(true);
		$currentLabels = isset($core->dimconfig['_labels'])?$core->dimconfig['_labels']:array();
		$new = array();
		foreach ($currentLabels as $id => $cL) {
			if ($id != $_REQUEST['delete'])
				$new[$id] = $cL;
		}
		$core->dimconfig['_labels'] = $new;
		$core->saveConfig();
		$core->log[] = $core->langOut('label_deleted');
		
	} 
	
