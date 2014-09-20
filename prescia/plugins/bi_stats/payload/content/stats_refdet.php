<? /* ---------------------------------
   | PART OF stats MODULE
--*/

	$core->layout = 2;
	$refD = $core->loaded('statsref');
	$sql = "SELECT pages FROM ".$refD->dbname." WHERE referer=\"".$_REQUEST['referer']."\"";
	$r = implode("<br/>",explode(",",$core->dbo->fetch($sql)));
	if ($r == "")
		echo $core->langOut("no_details");
	else
		echo $r;
	$core->close(true);