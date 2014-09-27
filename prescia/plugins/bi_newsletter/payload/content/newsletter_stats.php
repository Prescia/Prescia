<?
	if (!$core->authControl->checkPermission('bi_newsletter'))
		$core->fastClose(403);

	if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']))
		$core->fastClose(404);

	$data = $core->runContent('bi_newsletter_send',$core->template,$_REQUEST['id']);
	if ($data === false) $core->fastClose(404);

	if ($core->layout != 2) $core->addLink("misc/wz_jsgraphics.js");

	$rArray = $data['recipients']!=''?unserialize($data['recipients']):array();
	$rrArray = $data['recipients_received']!=''?unserialize($data['recipients_received']):array();
	$r = count($rArray);
	$rr = count($rrArray);
	$s = $data['progress'];

	$rrtxt = "";
	foreach ($rrArray as $mail => $one) {
		$rrtxt .= $mail."\n";
	}

	$percentSeen = $rr/(($r==0)?1:$r);

	$core->template->assign("recipients",$r);
	$core->template->assign("recipients_sent",$s);
	$core->template->assign("recipients_rec",$rr);
	$core->template->assign("recipients_rectxt",$rrtxt);

	$core->template->assign("percentseen",ceil(100*$percentSeen));
	if ($percentSeen == 0) $core->template->assign("_removeIfZero");
