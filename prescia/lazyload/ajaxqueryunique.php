<?php /* this file is captured by Prescia to check if a field is unique in a database, as per the functions on validators.js:
	Mandatory fields:
		module: which module we will look into
 		field: which field we will search for
		value: which value we are looking for
	This function will just look if there is a FIELD with the VALUE specified in MODULE and return "true" or "false" if it is UNIQUE
 */
 
/** @var CPrescia $this Core context injected by CPrescia::checkActions(). */
	$this->layout = 2; // enforce ajax mode
	$this->ignore404 = true; // enforce 404 should not be auto-generated (though this script will auto close)
 	
	if (!isset($_REQUEST['module']) || !isset($_REQUEST['field']) || !isset($_REQUEST['value']) || !is_string($_REQUEST['module']) || !is_string($_REQUEST['field']) || !is_string($_REQUEST['value']))
		echo "error";
	else {
		$obj = $this->loaded($_REQUEST['module']);
		if (!$obj)
			echo "error (module not found)";
		else if (!isset($obj->fields[$_REQUEST['field']]) && !in_array($_REQUEST['field'],$obj->keys,true))
			echo "error (field not found)";
		else if (!$this->authControl->checkPermission($obj,CONS_ACTION_SELECT))
			echo "error (forbidden)";
		else {
			$sql = "SELECT count(*) FROM ".$obj->dbname." WHERE ".$_REQUEST['field']."=?";
			$n = $this->dbo->fetchPrepared($sql,'s',array($_REQUEST['value']));
			if ($n === false) echo "error";
			else if ($n == 0) echo "true";
			else echo "false";
		}
	} 
	$this->close(true);
