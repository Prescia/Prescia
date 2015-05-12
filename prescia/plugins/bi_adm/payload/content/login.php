<?

	$b = getbrowser(false);
	$core->template->assign("browser",$b[0]);
	$core->template->assign("fullbrowser",isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"");
	$core->template->assign("islegacy",$b[1]?"1":"0");
	$core->template->assign("ismobile",$b[2]?"1":"0");
	$core->template->assign("ip",CONS_IP);
	$core->template->assign("servertime",date("H:i d/m/Y"));
	$core->template->assign("system",$b[3]);
	$core->template->assign("pversion",AFF_VERSION." #".AFF_BUILD);
