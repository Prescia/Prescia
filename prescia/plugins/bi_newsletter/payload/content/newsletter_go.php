<?

	if (!$core->authControl->checkPermission('bi_newsletter')) {
		$this->fastClose(403);
		return;
	}

	$core->addLink('validators.js');
	$nldata = $core->runContent('bi_newsletter_send',$core->template,$_REQUEST['id']);
	$rec = unserialize($nldata['recipients']);
	$core->template->assign("dest",count($rec));

	$core->template->assign("newslettersourcemail",$core->dimconfig['newslettersourcemail']);