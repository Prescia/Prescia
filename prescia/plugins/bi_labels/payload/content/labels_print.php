<?

	if (!isset($_REQUEST['module'])) $this->fastClose(404);
	$module = $core->loaded($_REQUEST['module']);
	
	if (!$module || !isset($_REQUEST['label_template']) || !isset($_REQUEST['label_skip'])) $this->fastClose(404);
	
	$label = $_REQUEST['label_template'];
	$offset = $_REQUEST['label_skip'];
	
	// load label data
	$currentLabels = isset($core->dimconfig['_labels'])?$core->dimconfig['_labels']:array();
	if (isset($currentLabels[$label]))
		$lData = $currentLabels[$label];
	else {
		$core->log[] = "Label not found";
		$this->fastClose(404);
	}
	
	$keys = array();
	$ereg_pattern = "^";
	$pos = 0;
	$keyscount = count($module->keys);
	$theKeys = explode(",",$_REQUEST['multiSelectedIds']);
	foreach($module->keys as $name) {
		$keys[$pos] = $name;
		switch($module->fields[$name][CONS_XML_TIPO]) {
			case CONS_TIPO_INT:
			case CONS_TIPO_LINK:
				$ereg_pattern .= "([0-9]+)_";
				break;
			case CONS_TIPO_FLOAT:
				$ereg_pattern .= "([0-9]+)(\.([0-9]+))?_";
				break;
			case CONS_TIPO_VC:
			case CONS_TIPO_ENUM:
			case CONS_TIPO_TEXT:
				$ereg_pattern .= "([^_]+)_"; # <-- what if enum/text has "_" ?
				break;
			case CONS_TIPO_DATE:
				$ereg_pattern .= "([0-9]{4}\-[0-9]{2}\-[0-9]{2})_";
				break;
			case CONS_TIPO_DATETIME:
				$ereg_pattern .= "([0-9]{4}\-[0-9]{2}\-[0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})_";
				break;
		}
		$pos++;
	}
	$ereg_pattern = substr($ereg_pattern,0,strlen($ereg_pattern)-1)."\$";
	
	// skip offset
	$col = 1;
	$line = 1;
	while ($offset>0) {
		$col++;
		if ($col > $lData['cols']) {
			$line++;
			$col = 1;
			if ($line > $lData['rows']) { // next page
				$line = 1;
			}
		}
		$offset--;
	}
	
	// print
	// prepare templates
	$aPage = clone $core->template->get("_page");
	$core->template->assign("fontsize",$lData['fontsize']);
	$aPage->assign("fontsize",$lData['fontsize']);
	$aPage->assign("fullwidth",$lData['pfl'] + ($lData['sw']*($lData['cols'])) + ($lData['ol']*($lData['cols']-1)) + 2);
	$aPage->assign("fullheight",$lData['pft'] + ($lData['sh']*($lData['rows'])) + ($lData['ot']*($lData['rows']-1)) + 2);	
	$aLabel = clone $core->template->get("_etiqueta");
	$content = new CKTemplate();
	$content->tbreak(nl2br($lData['content']));
	$output = "";
	$pageOutput = "";
	// get labels
	$basesql = $module->get_base_sql("","",1);
	foreach($theKeys as $ids) {
		if ($ids != "" && preg_match('/'.$ereg_pattern.'/',$ids,$regs)) { // valid multiple keys (checkboxes)
			$sql = $basesql;
			for($pos=0;$pos<$keyscount;$pos++) // build WHERE based on keys
				$sql['WHERE'][] = $module->name.".".$keys[$pos]."=\"".$regs[$pos+1]."\"";
			if ($core->dbo->query($sql,$r,$n) && $n>0) { // get data
				$data = $core->dbo->fetch_assoc($r);
				$data['width'] = $lData['sw'];
				$data['height'] = $lData['sh'];
				$data['left'] = $lData['pfl'] + (($lData['sw']+$lData['ol'])*($col-1));
				$data['top'] = $lData['pft'] + (($lData['sh']+$lData['ot'])*($line-1));
				$aLabel->assign("content",$content->techo($data));
				$pageOutput .= $aLabel->techo($data);
				$col++;
				if ($col > $lData['cols']) {
					$line++;
					$col=1;
					if ($line > $lData['rows']) { // end page, print it
						$output .= $aPage->techo(array("_etiqueta" => $pageOutput));
						$pageOutput = "";
						$line = 1;
					}
				}
			}
		}
	}
	if ($line > 1 || $col > 1) { // didn't print current page
		$output .= $aPage->techo(array("_etiqueta" => $pageOutput));
	}
	// echo
	$core->template->assign("_page",$output);
	
