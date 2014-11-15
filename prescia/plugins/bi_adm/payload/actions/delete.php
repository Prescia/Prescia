<?

	if (!isset($_REQUEST['module']) || !$core->loaded($_REQUEST['module'])) {
		# master check if this is a valid module
		$core->log[] = "Module not found";
		$core->action = "index";
		$_REQUEST = array();
		$_GET = array();
		$_POST = array();
		return;
	}

	$module = $core->loaded($_REQUEST['module']);

 	$ok = $core->runAction($module,CONS_ACTION_DELETE,$_REQUEST);
 	if ($ok) {
		$core->setLog(CONS_LOGGING_SUCCESS,str_replace("{#}",$core->langOut($module->name),$core->langOut('delete_sucess')));
	}

	$core->headerControl->internalFoward('list.html?module='.$module->name);




?>