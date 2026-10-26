<?php

/** @var CPrescia $core Core context assigned by mod_bi_bb::onRender(). */
/** @var mod_bi_bb $this Module context assigned by the framework include. */

		$core->addLink("ckeditor/ckeditor.js",true);
		$core->addLink("validators.js");
		$forumId = (int) ($_POST['id_forum'] ?? 0);
		$threadId = (int) ($_POST['id_forumthread'] ?? 0);

		if ($_POST['bbaction'] == 'tpreview') { // previewing a THREAD
			$core->template->assign("_previewPOST");
			$core->template->assign("ttitle",$_POST['ttitle']);
		} else { // previewing a POST, so we must load all the thread data
			$core->template->assign("_previewTHREAD");
			$core->template->assign("id_forumthread",$threadId);
			$r = false;
			$n = 0;
			$core->dbo->queryPrepared(
				"SELECT title,urla FROM bb_thread WHERE id_forum=? AND id=?",
				'ii',
				[$forumId, $threadId],
				$r,
				$n
			);
			if ($n == 0) {
				$core->fastClose(503);
			}
			list($tdata,$furl) = $core->dbo->fetch_row($r);
			$core->template->assign("ttitle",$tdata);
			$core->template->assign("urla",$furl);
		}

		// get FORUM data
		$r = false;
		$n = 0;
		$core->dbo->queryPrepared(
			"SELECT f.title,f.operationmode, f2.title FROM (bb_forum as f) LEFT JOIN bb_forum as f2 ON (f2.id = f.id_parent) WHERE f.id=?",
			'i',
			[$forumId],
			$r,
			$n
		);
		list($ftitle, $op, $ptitle) = $core->dbo->fetch_row($r);

		$core->template->fill($_POST);
		$core->template->assign("forum_title",$ftitle);
		$core->template->assign("parent_title",$ptitle.(($ptitle != '')?" - ":""));
		$core->template->assign("date",date("Y-m-d H:i:s")); // so this will result in "now"
		$core->template->assign("bbaction",$_POST['bbaction']=='tpreview'?'tpost':'post');
		$core->template->assign("bbactionpreview",$_POST['bbaction']);
		if (isset($_POST['video']) && $_POST['video'] != '') {
			$embed = getVideoFrame(trim($_POST['video']),420,315);
			if ($embed != '') $core->template->assign('videoembed',$embed);
			else $core->template->assign("_hasvideo");
		} else
				 $core->template->assign("_hasvideo");


		// user image?
		$image = CONS_PATH_PAGES.$_SESSION['CODE'].'/files/users/t/image_'.$_SESSION[CONS_SESSION_ACCESS_USER]['id']."_2";
		$ext = '';
		if ($_SESSION[CONS_SESSION_ACCESS_USER]['image']=='n' || !locateFile($image,$ext)) {
			$core->template->assign("_imageyes");
		} else {
			$core->template->assign("_imageno");
			$core->template->assign("image",$image);
		}


		if ($op == 'bb') $core->template->assign("_notbb");
