<?

	if (isset($_POST['haveinfo']) && isset($_POST['dir']) && isset($_FILES['newuploadfile'])) {
		$core->loadAllmodules();
		$dir = str_replace(".","",$_POST['dir']);
		if ($dir!= "" && $dir[0] == "/") $dir = substr($dir,1);
		if ($dir != "" && $dir[strlen($dir)-1] == "/")
			$dir = substr($dir,0,-1);
		$core->loadDimconfig(true);
		$quota = isset($core->dimconfig['quota'])?$core->dimconfig['quota']:CONS_MAX_QUOTA;
		$usedSpace = quota(CONS_FMANAGER,true)*1024;
		if ($usedSpace > $quota) {
			$core->log[] = $core->langOut("e508");
			$core->errorControl->raise(508,"Quota exceeded","fmanager");
		} else if ($this->canEdit($dir)) {
			if (is_dir(CONS_FMANAGER.$dir)) {
				$arquivo = CONS_FMANAGER.$dir."/".removeSimbols($_FILES['newuploadfile']['name']);
				$arquivo = explode(".",$arquivo);
				$ext = array_pop($arquivo);
				$arquivo = implode(".",$arquivo);
				if ($ext == "php" || $ext == "asp" || $ext == "jsp" || $ext == "htaccess")
					$ok = 5;
				else {
					$ok = storeFile($_FILES['newuploadfile'],$arquivo,"");
				}
				switch ($ok) {
					case 0:
						$core->log[] = $core->langOut("e200");
					break;
					case 1:
						$core->log[] = $core->langOut("e201");
					break;
					case 2:
						$core->log[] = $core->langOut("e202");
					break;
					case 3:
						$core->log[] = $core->langOut("e203");
					break;
					case 4:
						//$core->log[] = $core->langOut("upload_nothing_sent");
					break;
					case 5:
						$core->log[] = $core->langOut("e205");
					break;
				}
				if ($ok == 0) {
					$core->dimconfig['usedquota'] = $usedSpace + filesize($arquivo);
					$core->saveConfig(true);
					$fakeLink = false;
					$core->notifyEvent($fakeLink,'fmanager_upload',$arquivo,'bi_adm',false);
				}
				$core->errorControl->raise(509,"Uploading","fmanager","result = $ok");

			} else
				$core->log[] = $core->langOut('upload_folder_nf');
		} else
			$core->log[] = $core->langOut('upload_pd');
	}

?>