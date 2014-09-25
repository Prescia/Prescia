<?

	if ($_POST['bbaction'] == 'tpreview') { // previewing a THREAD
		$core->template->assign("_previewPOST");
		$core->template->assign("ftitle",$_POST['ftitle']);
	} else { // previewing a POST, so we must load all the thread data
		$core->template->assign("_previewTHREAD");
		$core->template->assign("id_forumthread",$_POST['id_forumthread']);
		$core->dbo->query("SELECT title,urla FROM bb_thread WHERE id_forum=".$_POST['id_forum']." AND id=".$_POST['id_forumthread'],$r,$n);
		if ($n == 0) {
			$core->fastClose(503);
		}
		list($fdata,$furl) = $core->dbo->fetch_row($r);
		$core->template->assign("ftitle",$fdata);
		$core->template->assign("urla",$furl);
	}

	$core->template->assign("forum_title",$core->dbo->fetch("SELECT title FROM bb_forum WHERE id=".$_POST['id_forum']));	
	$core->template->assign("date",date("Y-m-d H:i:s")); // so this will result in "now"
	$core->template->assign("fmessage",$_POST['fmessage']);
	$core->template->assign("id_forum",$_POST['id_forum']);
	$core->template->assign("bbaction",$_POST['bbaction']=='tpreview'?'tpost':'post');
	$core->template->assign("bbactionpreview",$_POST['bbaction']);
	
	// user image?
	$image = CONS_PATH_PAGES.$_SESSION['CODE'].'/files/users/t/image_'.$_SESSION[CONS_SESSION_ACCESS_USER]['id']."_2";
	if ($_SESSION[CONS_SESSION_ACCESS_USER]['image']=='n' || !locateFile($image,$ext)) {
		$core->template->assign("_imageyes");
	} else {
		$core->template->assign("_imageno");
		$core->template->assign("image",$image);
	}

	$core->addLink("ckeditor/ckeditor.js",true);
	$core->addLink("validators.js");
	
		