<? /* this file is captured by Prescia to check if a field is unique in a database, as per the functions on validators.js:
	Mandatory fields:
		module: which module we will look into
 		field: which field we will search for
		value: which value we are looking for
	This function will just look if there is a FIELD with the VALUE specified in MODULE and return "true" or "false" if it is UNIQUE
 */
 
	$this->layout = 2; // enforce ajax mode
	$this->ignore404 = true; // enforce 404 should not be auto-generated (though this script will auto close)
 	
	if (!isset($_REQUEST['module']) || !isset($_REQUEST['field']) || !isset($_REQUEST['value']))
		echo "error"; 
	else {
		$obj = $this->loaded($_REQUEST['module']);
		if (!$obj)
			echo "error (module not found)";
		else {
			$sql = "SELECT count(*) FROM ".$obj->dbname." WHERE ".addslashes($_REQUEST['field'])."=\"".addslashes($_REQUEST['value'])."\"";
			$n = $this->dbo->fetch($sql);
			if ($n === false) echo "error on SQL: ".$sql;
			else if ($n == 0) echo "true";
			else echo "false";
		}
	} 
	$this->close(true);
