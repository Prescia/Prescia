<?

	if (!isset($_REQUEST['module']) || !($module = $core->loaded($_REQUEST['module'])) || !$module) {
		# master check if this is a valid module
		$core->errorControl->raise(512,"laedit",(isset($_REQUEST['module'])?$_REQUEST['module']:''));
		$_REQUEST = array();
		$_GET = array();
		$_POST = array();
		echo "e: module not found";
		$core->close(true);
	}

	// remove labels as default values, if any
	foreach ($module->fields as $name => &$field) {
		if (isset($_POST[$name]) && $_POST[$name] == $this->parent->langOut($name))
			unset($_POST[$name]);
	}

	$ok = $module->runAction(CONS_ACTION_INCLUDE,$_POST,false,false);
	if ($ok)
		echo "ok";
	else
		echo "error: ".implode(", ",$core->log);
	$core->close(true);