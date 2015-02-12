<?

	if (!$core->authControl->checkPermission('bi_adm','can_undo'))
		$core->fastClose(403);

	if (isset($_REQUEST['haveinfo'])) {
			
			
		if (!isset($_POST['undo']) || count($_POST['undo']) == 0) {
			$core->log[] = $this->langOut("nothing_selected_to_undo");
		} else {
			// load up what we want to undo
			$undo = $core->loaded('bi_undo');
			$plugin = $core->loadedPlugins['bi_undo'];
			
			$u = $_POST['undo'];
			$ok = 0;
			foreach ($u as $id) {
				$sql = $undo->get_base_sql($undo->name.".id=".$id);
				$core->dbo->query($sql,$r,$n);
				if ($n != 0) {
					$sucess = $plugin->undo($id,$r);
					if ($sucess) {
						$ok++;
					}
				}
			}
			
			$core->log[] = $core->langOut("multiple_undo_success")." ".$ok."/".count($u);
			$core->action= "historymain";
			$core->headerControl->internalFoward("historymain.php");
		}
	} else $core->action = 404;
	