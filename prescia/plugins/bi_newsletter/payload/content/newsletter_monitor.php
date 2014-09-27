<?

	if (!$core->authControl->checkPermission('bi_newsletter')) $this->fastClose(403);

	function recipientCalc(&$tp,&$params,$data,$processed = false) {
		$r = unserialize($data['recipients']);
		$rc = count($r);
		$data['recipients_c'] = $rc;
		if ($rc == 0) $rc=1;
		$data['progress'] = ceil(100*$data['progress']/$rc);

		if ($data['recipients_received'] == '')
			$rc = 0;
		else {
			$r = unserialize($data['recipients_received']);
			$rc = count($r);
		}
		$data['received_c'] = $rc;
		return $data;
	}


	$p_size = 15;
	$p = isset($_REQUEST['p_init'])?$_REQUEST['p_init']:0;

	$total = $core->runContent('bi_newsletter_send',$core->template,"","_line",$p_size,false,"recipientCalc");

	$core->template->createPaging("_paginacao",$total,$p,$p_size);

	$core->template->assign("total",$total);