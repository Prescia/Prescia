<?

	if (isset($_REQUEST['haveinfo'])){  
		if (isset($_REQUEST['makedir'])) {
			$core->storage['dir'] = '/';
			$theDir = trim($_REQUEST['makedir']);
			if ($theDir != "" && $theDir[strlen($theDir)-1] == "/")
				$theDir = substr($theDir,0,-1);
			$theDir = explode("/",$theDir);
			$coreDir = removeSimbols(array_pop($theDir),false,true);
			$theDir = implode("/",$theDir); // parent
			if (is_dir(CONS_FMANAGER.$theDir)) { 
				$core->storage['dir'] = $theDir;
				$theDir .= "/".$coreDir;			
				if ($this->canEdit($theDir)) {
					if (safe_mkdir(CONS_FMANAGER.$theDir)) {
						$core->log[] = "Folder ".$theDir." created";
						$core->errorControl->raise(506,"Created $theDir","fmanager");
						$core->storage['error'] = $core->langOut("create_folder_ok");
						$core->storage['dir'] = $theDir;
					} else {
						$core->logaction(CONS_ACTION_INCLUDE,$fm,false,false);
						$core->storage['error'] = $core->langOut("create_folder_error");
					}
				} else 
					$core->storage['error'] = $core->langOut("create_folder_pd");
			} else
				$core->storage['error'] = $core->langOut("create_folder_error_pnf");
		} else if (isset($_REQUEST['deldir'])) {
			$theDir = trim($_REQUEST['deldir']);
			if ($theDir != "" && $theDir[strlen($theDir)-1] == "/")
				$theDir = substr($theDir,0,-1);
			if ($theDir != "" && $theDir[0] == "/")
				$theDir = substr($theDir,1);	
			if ($theDir != "") {
				if ($this->canEdit($theDir)) {
					if (is_dir(CONS_FMANAGER.$theDir)) {
						$listFiles = listFiles(CONS_FMANAGER.$theDir);
						if (count($listFiles)==0) {
							@rmdir(CONS_FMANAGER.$theDir);
							$core->log[] = "Folder ".$theDir." deleted";
							$core->errorControl->raise(507,"Deleted $theDir","fmanager");
							$theDir = explode("/",$theDir);
							array_pop($theDir);
							$core->storage['dir'] = "/".implode("/",$theDir);
							$core->storage['error'] = $core->langOut("delete_folder_ok");
						} else
							$core->storage['error'] = $core->langOut("delete_folder_not_empty");
					} else
						$core->storage['error'] = $core->langOut("delete_folder_nf");
				} else
					$core->storage['error'] = $core->langOut("delete_folder_pd");
			} else
				$core->storage['error'] = $core->langOut("delete_folder_no_root");
		}
	}

?>