<?

	if (isset($_REQUEST['delfile'])) {
		$dir = str_replace(".","",$_REQUEST['dir']);
		if ($dir!= "" && $dir[0] == "/") $dir = substr($dir,1);
		if ($dir != "" && $dir[strlen($dir)-1] == "/")
			$dir = substr($dir,0,-1);
		$core->storage['dir'] = "/".$dir;
		if ($this->canEdit($dir)) {
			$file = removeSimbols($_REQUEST['delfile']);
			if (!is_file(CONS_FMANAGER.$dir."/".$file) && is_file(CONS_FMANAGER.$dir."/".$_REQUEST['delfile']))
				$file = $_REQUEST['delfile'];
			if (is_file(CONS_FMANAGER.$dir."/".$file)) {
				if (unlink(CONS_FMANAGER.$dir."/".$file)) {
					$core->log[] = "File $file deleted at $dir";
					$core->storage['error'] = $core->langOut("delete_file_ok");
					$core->errorControl->raise(510,"Delete file $file at $dir","fmanager");
					$fakeLink = false;
					$core->notifyEvent($fakeLink,'fmanager_delete',CONS_FMANAGER.$dir."/".$file,'bi_adm',false);
				} else {
					$core->log[] = "Error deleting $file deleted at $dir";
					$core->storage['error'] = $core->langOut("delete_file_err");
					$core->errorControl->raise(511,"Delete file FAIL: $file at $dir","fmanager");
				}
			} else
				$core->storage['error'] = $core->langOut("delete_file_nf").": ".CONS_FMANAGER.$dir."/".$file;
		} else
			$core->storage['error'] = $core->langOut("delete_file_pd");
	}

