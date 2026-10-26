<?php /* ---------------------------------
   | PART OF stats MODULE
--*/

/** @var CPrescia $core Runtime payload context injected by the framework. */

	$core->layout = 2;
	$refD = $core->loaded('statsref');
	$referer = isset($_REQUEST['referer']) && is_string($_REQUEST['referer']) ? $_REQUEST['referer'] : '';
	$sql = "SELECT pages FROM ".$refD->dbname." WHERE referer=?";
	$pages = $core->dbo->fetchPrepared($sql, 's', array($referer));
	$r = is_string($pages) ? implode("<br/>",explode(",",$pages)) : '';
	if ($r == "")
		echo $core->langOut("no_details");
	else
		echo $r;
	$core->close(true);
