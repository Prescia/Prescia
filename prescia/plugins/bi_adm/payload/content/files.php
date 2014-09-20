<?

	if (!$core->authControl->checkPermission('bi_adm','can_fm'))
		$core->fastClose(403);

	$dir = str_replace(".","",$_REQUEST['dir']); // no hacks here please
	if ($dir != '' && $dir[0] == '/') $dir = substr($dir,1); // removes initial /
	if ($dir != "" && $dir[strlen($dir)-1] == "/") $dir = substr($dir,0,-1); // removes trailing /
	$type = "/^.*\..*$/i";
	if (isset($_REQUEST['type'])) {
		switch ($_REQUEST['type']) {
			case "image":
				$type = "/^.*\\.(jpg|jpeg|gif|png|bmp)$/i";
			break;
			case "swf":
				$type = "/^.*\\.(swf|flv)$/i";
			break;
		}
	}

	// Safe file manager
	$fm = $core->loaded('bi_fm');
	$hasSafeFm = $fm !== false; // safe file manager active?
	$isInsideSafePath = false;
	$permissions = '0,[]'; // folder permissions
	if ($hasSafeFm) {
		$fm = $core->loadedPlugins['bi_fm'];
		$isInsideSafePath = $fm->isInsideSafe(CONS_FMANAGER.$dir);
		$canchangepermissions = $core->authControl->checkPermission('bi_fm','change_fmp');
		if ($isInsideSafePath && $dir != CONS_FMANAGER_SAFE) {
			$p = $fm->getPermissions($dir."/");
			$permissions = $p[0].",[".implode(',',$p[1]).']';
		}
	} else
		$canchangepermissions = false;

	$files = listFiles(CONS_FMANAGER.$dir,$type);

	$canEdit = $this->canEdit($dir);
	if ($canEdit)
		$core->template->assign("_warning","");
	else {
		$core->template->assign("_editable","");
		$pd = explode("/",$dir);
		$core->template->assign("modulo",array_shift($pd));
	}

	if ($dir != "") { // adds trailing /
		$dir .= "/";
		$core->template->assign("_root");
	}
	if (!$isInsideSafePath)
		$core->template->assign("_perm");

	$tp = $core->template->get('_file');
	$output = "";
	$c = 0;
	if (!function_exists('filetypeIcon')) include CONS_PATH_INCLUDE."filetypeIcon.php";


	// separated in two loops pratically the same for performance reasons:
	if ($hasSafeFm && $isInsideSafePath) {
		$fm->cachePermissions($dir);
		foreach ($files as $file) {
			if (!$fm->canSee($dir.$file)) continue; // cannot see this
			$ext = explode(".",$file);
			$ext = strtolower(array_pop($ext));
			if (is_file(CONS_FMANAGER.$dir.$file) && $file[0] != "." && $ext != "php") {
				$c++;
				$p = $fm->getPermissions($dir.$file);
				$dados = array('absurl' => CONS_INSTALL_ROOT.CONS_FMANAGER.$dir.$file,
							   'domain' => $core->domain,
							   'file' => $file,
							   'ico' => filetypeIcon($ext),
							   'CLASS' => $c%2==0?"lxmladm_lineeven":"lxmladm_lineodd",
							   'size' => humanSize(filesize(CONS_FMANAGER.$dir.$file)),
							   'id_allowed_group' => $p[0],
							   'allowed_users' => implode(",",$p[1]),
							   'expiration_date' => fd($p[2])
							  );
				if ($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'ico' || $ext == 'bmp') {
					$output .= $tp->techo($dados,!$canchangepermissions || !$isInsideSafePath?array('_perm'):array());
				} else {
					$output .= $tp->techo($dados,!$canchangepermissions || !$isInsideSafePath?array('_perm',"_isImage"):array("_isImage"));
				}
			}
		}
	} else {
		foreach ($files as $file) {
			$ext = explode(".",$file);
			$ext = strtolower(array_pop($ext));
			if (is_file(CONS_FMANAGER.$dir.$file) && $file[0] != "." && $ext != "php") {
				$c++;
				$dados = array('absurl' => CONS_INSTALL_ROOT.CONS_FMANAGER.$dir.$file,
							   'domain' => $core->domain,
							   'file' => $file,
							   'ico' => filetypeIcon($ext),
							   'CLASS' => $c%2==0?"lxmladm_lineeven":"lxmladm_lineodd",
							   'size' => humanSize(filesize(CONS_FMANAGER.$dir.$file)),
							  );
				if ($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'ico' || $ext == 'bmp') {
					$output .= $tp->techo($dados,!$canchangepermissions || !$isInsideSafePath?array('_perm'):array());
				} else {
					$output .= $tp->techo($dados,!$canchangepermissions || !$isInsideSafePath?array('_perm',"_isImage"):array("_isImage"));
				}
			}
		}
	}
	$core->template->assign("_file",$output);

	$dir = "/".$dir;
	if (isset($core->storage['error']) || isset($core->storage['dir'])) {
		$core->template->assign('script',"<script type=\"text/javascript\">alert(\"".$core->storage['error']."\");canChange=".($canEdit?"true":"false").";showFolder(\"".$dir."\",true);$('btnPerm').style.display='".($isInsideSafePath && $canchangepermissions && $dir != '/'.CONS_FMANAGER_SAFE."/"?'':'none')."';lastFolderPermission=[$permissions];</script>");
	} else {
		$core->template->assign('script',"<script type=\"text/javascript\">canChange=".($canEdit?"true":"false").";showFolder(\"".$dir."\",true);$('btnPerm').style.display='".($isInsideSafePath && $canchangepermissions &&  $dir != '/'.CONS_FMANAGER_SAFE."/"?'':'none')."';lastFolderPermission=[$permissions];</script>");
	}

?>