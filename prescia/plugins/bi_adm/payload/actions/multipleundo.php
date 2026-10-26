<?php

/** @var CPrescia $core Runtime payload context injected by the framework. */
/** @var mod_bi_adm $this Runtime module context injected by the framework. */

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
					$id = filter_var($id, FILTER_VALIDATE_INT);
					if ($id === false || $id < 1) continue;
					$sql = "SELECT * FROM ".$undo->dbname." WHERE id=?";
				$r = false;
				$n = 0;
				$core->dbo->queryPrepared($sql,'i',array($id),$r,$n);
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
