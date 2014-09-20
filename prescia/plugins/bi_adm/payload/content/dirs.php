<?

	if (!$core->authControl->checkPermission('bi_adm','can_fm'))
		$core->fastClose(403);

	$core->loadAllmodules();

	// where are we?
	$dir = isset($core->storage['dir']) ? $core->storage['dir'] : (isset($_REQUEST['dir'])?str_replace(".","",$_REQUEST['dir']):"");
	if ($dir != "" && $dir[strlen($dir)-1] == "/")
		$dir = substr($dir,0,-1);

	// Safe file manager
	$fm = $core->loaded('bi_fm');
	$hasSafeFm = $fm !== false; // safe file manager active?
	$isInsideSafePath = false;
	if ($hasSafeFm) {
		$fm = $core->loadedPlugins['bi_fm'];
		$isInsideSafePath = $fm->isInsideSafe(CONS_FMANAGER.$dir);
		$canchangepermissions = $core->authControl->checkPermission('bi_fm','change_fmp');
		$fm->cachePermissions($dir);
		$core->storage['fm'] = &$fm;
	} else {
		$canchangepermissions = false;

	}
	$core->storage['hassafefm'] = $hasSafeFm; // to the callback

	function fillInfo(&$tree,&$core,$path='') {
		$me = $path.$tree->data['id']."/";
		if ($me[0] == "/") $me=substr($me,1);
		if ($core->storage['hassafefm'] && isset($tree->data['id_parent']) && $tree->data['id_parent'] == '/' && $tree->data['id'] == CONS_FMANAGER_SAFE) {
			if (!$core->storage['fm']->canSee($me)) {
				$tree->data = false;
				for ($c=count($tree->branchs)-1;$c>=0;$c++)
					$tree->branchs[$c] = null; // clean up memory
				$tree->branchs = array();
				return false;
			}
		}
		$tree->data['issafe'] = 'false';
		if ((!isset($tree->data['id_parent']) || $tree->data['id_parent'] == '/') && isset($tree->data['id'])) {
			if ($core->loaded($tree->data['id']))
				$tree->data['issafe'] = 'true';
			else if ($core->storage['hassafefm'] && $tree->data['id'] == CONS_FMANAGER_SAFE)
				$tree->data['issafe'] = 'true';
		} else if (is_object($tree->parent) && isset($tree->parent->data['issafe']))
			$tree->data['issafe'] = $tree->parent->data['issafe'];

		$nb = array();
		if (isset($tree->data['id'])) {
			foreach ($tree->branchs as &$branch) {
				if ($branch !== false && fillInfo($branch,$core,$path.$tree->data['id'].'/')) {
					$nb[] = $branch;
				}
			}
		}
		$tree->branchs = $nb;
		return true;
	}

	$newTree = new ttree();
	if (!is_dir(CONS_FMANAGER))
		makeDirs(CONS_FMANAGER);

	$newTree->getFolderTree(CONS_FMANAGER,false,$dir,"",array("_thumbs","_undodata")); // thumbs is the fckfinder folder
	fillInfo($newTree,$core);
	$core->template->getTreeTemplate("_dirs","_subdirs",$newTree,"/","/");

	if ($dir == "") $dir = "/";
	$canEdit = $this->canEdit($dir);

	if (isset($core->storage['error']) || isset($core->storage['dir'])) {
		if (!isset($core->storage['dir'])) $core->storage['dir'] = "";
		$core->template->assign('script',"<script type=\"text/javascript\">alert(\"".$core->storage['error']."\");canChange=".($canEdit?"true":"false").";showFolder(\"".$core->storage['dir']."\");</script>");
	} else {
		$core->template->assign('script',"<script type=\"text/javascript\">canChange=".($canEdit?"true":"false").";showFolder(\"".$dir."\");</script>");
	}

?>