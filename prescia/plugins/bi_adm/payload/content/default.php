<? /* ----------------------------------------- Default script for admin pages
 *
 */

 	// loads frame
 	
 	$core->loadTemplate();
	$core->addLink('common.js');
	
	if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]>=$this->admRestrictionLevel) {

		$core->template->assign("LOGGED_USER",$_SESSION[CONS_SESSION_ACCESS_USER]['name']);
		$core->template->assign("LOGGED_USERID",$_SESSION[CONS_SESSION_ACCESS_USER]['id']);
		// user image
		$image = CONS_PATH_PAGES.$_SESSION['CODE'].'/files/users/t/image_'.$_SESSION[CONS_SESSION_ACCESS_USER]['id']."_2";
		if ($_SESSION[CONS_SESSION_ACCESS_USER]['image']=='n' || !locateFile($image,$ext)) {
			$core->template->assign("_imageyes");
		} else {
			$core->template->assign("LOGGED_USERIMAGE",$image);
		}
		
		if ($core->layout != 2 && $core->action != 'login') {

			// version
			$core->template->assign("AFF_VERSION",AFF_BUILD." ".AFF_VERSION);

			// query string
			$qs = arrayToString($_GET,array('layout'));
			$core->template->assign("main_qs",$qs);

			// master options (remove if not master)
			if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<100) $core->template->assign("_master");
			if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<99) $core->template->assign("_highadmin");

			// information on admin status bar
			if (isset($_REQUEST['nocache']) || !isset($_SESSION['admstatisticsminute']) || $_SESSION['admstatisticsminute'] != date("i")) { // cache is invalid or non-existent
				$_SESSION['admstatisticsminute'] = date("i");

				// main statistics - if we have stats plugin use it, otherwise some random mambo-jambo
				if ($this->hasStats) { // stats installed, show today's hits
					$stp = $core->loadedPlugins['bi_stats']->getHits(1);
					if (count($stp)>0) {
						$stp = $stp[0][1];
						if (!is_numeric($stp) || $stp == '') $stp = 0;
					} else
						$stp = 0;
					$_SESSION['admstatistics_left'] = $stp;
				} else
					$_SESSION['admstatistics_left'] = '';


				// monitored items
				if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/monitor.xml") && $core->authControl->checkPermission('bi_adm','can_monitor')) { // we have monitored items AND can see them

					$monitorXml = $this->getMonitorArray();
					// now perform sql queries COUNTING items we are monitoring

					$mObj = $core->template->get("_monitor");
					$monitorTxt = "";
					$c=0;
					$totalItems = 0;
					foreach ($monitorXml as $monitoredItem) {
						$monitoredModule = $core->loaded($monitoredItem['module'],true);
						$monitoredItem['sql'] = str_replace("\$id_user",$_SESSION[CONS_SESSION_ACCESS_USER]['id'],$monitoredItem['sql']);
						$sql = $monitoredModule->get_base_sql("(".$monitoredItem['sql'].")","","");
						$sql['ORDER'] = array();
						$sql['SELECT'] = array("count(*) as myresult");
						if (!isset($monitoredItem['monitor_level'])) $monitoredItem['monitor_level'] = 'low';
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

					$_SESSION['admstatistics_monwidth'] = 22 * count($monitorXml);
					$_SESSION['admstatistics_hasmon'] = true;
					$_SESSION['admstatistics_right'] = $monitorTxt;

				} else {
					$_SESSION['admstatistics_monwidth'] = 0;
					$_SESSION['admstatistics_right'] = "";
					$_SESSION['admstatistics_hasmon'] = false;
				}

			}

			$core->template->assign("monitorareawidth",$_SESSION['admstatistics_monwidth']);
			$core->template->assign("framedstatistics",$_SESSION['admstatistics_left']);
			$core->template->assign("_monitor",$_SESSION['admstatistics_right']);

			// permissions and systems
			$hasSomething = false;
			if (!$this->hasStats)
				$core->template->assign("_has_stats");
			else
				$hasSomething = true;
			if (!$_SESSION['admstatistics_hasmon'])
				$core->template->assign("_hasMonitor");
			if (!$core->authControl->checkPermission('bi_adm','can_monitor'))
				$core->template->assign("_can_monitor");
			else
				$hasSomething = true;
			if (!$this->hasUndo)
				$core->template->assign("_hasUndo");
			else if (!$core->authControl->checkPermission('bi_adm','can_undo'))
				$core->template->assign("_can_undo");
			else
				$hasSomething = true;
			if (!$core->authControl->checkPermission('bi_adm','can_importexport'))
				$core->template->assign("_can_importex");
			else
				$hasSomething = true;
			if (!$core->authControl->checkPermission('bi_adm','can_fm'))
				$core->template->assign("_can_fm");
			else
				$hasSomething = true;
			if (!$core->authControl->checkPermission('bi_adm','can_options'))
				$core->template->assign("_can_options");
			else
				$hasSomething = true;
			if (!$core->authControl->checkPermission('bi_adm','can_logs'))
				$core->template->assign("_can_logs");
			else
				$hasSomething = true;
			if (!$hasSomething)
				$core->template->assign("_can_something");
			if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/files/adm/logo.png"))
				$core->template->assign("CLIENT_LOGO","<img src=\"/pages/".$_SESSION['CODE']."/files/adm/logo.png\" alt=\"\"/>");
			elseif (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/files/adm/logo.jpg"))
				$core->template->assign("CLIENT_LOGO","<img src=\"/pages/".$_SESSION['CODE']."/files/adm/logo.jpg\" alt=\"\"/>");
			elseif (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/files/adm/logo.gif"))
				$core->template->assign("CLIENT_LOGO","<img src=\"/pages/".$_SESSION['CODE']."/files/adm/logo.gif\" alt=\"\"/>");
		}
	}


