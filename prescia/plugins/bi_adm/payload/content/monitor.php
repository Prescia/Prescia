<? 

	if (!$core->authControl->checkPermission('bi_adm','can_monitor'))		
		$core->fastClose(403);

	// We use the same scripts as in the list.html to handle ajax lists, get them
	$listHTML = new CKTemplate($core->template);
	$listHTML->fetch(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/list.html");
	$core->template->assign("commonscript",$listHTML->get("_commonScripts"));
	unset($listHTML);

	$monitorXML = $this->getMonitorArray();
	$monitorTP = $core->template->get("_monitorLists");
	$temp = "";
	
	$getOnlyThis = isset($_REQUEST['filter']) && is_numeric($_REQUEST['filter']) ? $_REQUEST['filter'] : -1;
	
	if (is_array($monitorXML)) {
		// 	now perform sql queries COUNTING items we are monitoring
		$c = 0;
		foreach ($monitorXML as $monitoredItem) {
			if ($getOnlyThis == -1 || $getOnlyThis == $c) {
				if (!isset($monitoredItem['monitor_level'])) $monitoredItem['monitor_level'] = 'low';
				$outputData = array("frommonitor"=>$c,
									"module"=>$monitoredItem['module'],
									"level"=>$monitoredItem['monitor_level'],
									"title"=>isset($monitoredItem['monitor_text_plural'])?$monitoredItem['monitor_text_plural']:(isset($monitoredItem['monitor_text'])?$monitoredItem['monitor_text']:$monitoredItem['module'])
									);
				$temp .= $monitorTP->techo($outputData);
			}
			$c++;
		}
		$core->template->assign("_monitorLists",$temp);
	}
	

