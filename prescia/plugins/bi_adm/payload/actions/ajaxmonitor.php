<?

	// this code is taken from default.php

	$template = new CKTemplate($core->template);
	$template->fetch(CONS_PATH_SYSTEM."plugins/bi_adm/payload/template/skin/".$this->skin."/admframe.html");
	$mObj = $template->get("_monitor");
	
	// monitored items
	if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/monitor.xml") && $core->authControl->checkPermission('bi_adm','can_monitor')) {
		// we have monitored items AND can see them
	
		$monitorXml = $this->getMonitorArray();
		// now perform sql queries COUNTING items we are monitoring

		$monitorTxt = "";
		$c=0;
		$totalItems =0;
		foreach ($monitorXml as $monitoredItem) {
			$monitoredModule = $core->loaded($monitoredItem['module']);
			$monitoredItem['sql'] = str_replace("\$id_user",$_SESSION[CONS_SESSION_ACCESS_USER]['id'],$monitoredItem['sql']);
			$sql = $monitoredModule->get_base_sql("(".$monitoredItem['sql'].")","","");
			$sql['ORDER'] = array();
			$sql['SELECT'] = array("count(*) as myresult");
			if (!isset($monitoredItem['monitor_level'])) $monitoredItem['monitor_level'] = 'warning';
			$ok = $core->dbo->query($sql,$r,$n);
			if ($ok) {
				if ($n>0) list($n) = $core->dbo->fetch_row($r);
				$monitorData = array(
									'monitor' => $c,
									'level' => $monitoredItem['monitor_level'],
									'notifies' => $n,
									'txt' => $n == 1 ? (isset($monitoredItem['monitor_text'])?$core->langOut($monitoredItem['monitor_text']):'') : (isset($monitoredItem['monitor_text_plural'])?$core->langOut($monitoredItem['monitor_text_plural']):'') 
				);
				$totalItems += $n;
				$monitorTxt .= $mObj->techo($monitorData);
			} else
				$core->errorControl->raise(516,$core->dbo->sqlarray_echo($sql),$monitoredModule);
			$c++;
		}
		$monitorTxt .= "<script type=\"text/javascript\">\nsetMonitorItems($totalItems);\n</script>";
		$_SESSION['admstatistics_monwidth'] = 51 * count($monitorXml);
		$_SESSION['admstatistics_hasmon'] = true;
		$_SESSION['admstatistics_right'] = $monitorTxt;
	
	}
	
	echo $monitorTxt;
	$this->parent->close();
