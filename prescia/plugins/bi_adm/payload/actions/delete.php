<?php

/** @var CPrescia $core Runtime payload context injected by the framework. */

	if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
		header('Allow: POST');
		http_response_code(405);
		exit;
	}
	presciaValidateCsrf();

	if (!isset($_POST['module']) || !is_string($_POST['module']) || !$core->loaded($_POST['module'])) {
		# master check if this is a valid module
		$core->log[] = "Module not found";
		$core->action = "index";
		$_REQUEST = array();
		$_GET = array();
		$_POST = array();
		return;
	}

	$module = $core->loaded($_POST['module']);

	$ok = $core->runAction($module,CONS_ACTION_DELETE,$_POST);
 	if ($ok) {
		$core->setLog(CONS_LOGGING_SUCCESS,str_replace("{#}",$core->langOut($module->name),$core->langOut('delete_sucess')));
	}

	$core->headerControl->internalFoward('list.html?module='.$module->name);




?>
