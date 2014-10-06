<?

	if (!isset($_REQUEST['module']) || !($module = $core->loaded($_REQUEST['module'],true)) || !$module) {
		# master check if this is a valid module
		$core->errorControl->raise(512,"ajaxedit",(isset($_REQUEST['module'])?$_REQUEST['module']:''));
		$core->action = "404";
		$_REQUEST = array();
		$_GET = array();
		$_POST = array();
		return;
	}

	if (isset($_REQUEST['field']) && isset($module->fields[$_REQUEST['field']]) && isset($_REQUEST['keys']) && isset($_REQUEST['value'])) {
		$editArray = array();
		$keys = explode("_",$_REQUEST['keys']);
		foreach	($module->keys as $key) {
			$editArray[$key] = array_shift($keys);
		}
		$editArray[$_REQUEST['field']] = $_REQUEST['value'];
		$ok = $module->runAction(CONS_ACTION_UPDATE,$editArray,true,true);


		echo $ok?"o":"e";
	} else
		echo "e";

	$core->close(true);