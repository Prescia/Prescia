<?

	$p = isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init'])?$_REQUEST['p_init']:0; // item starting this page
	
	$up = isset($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'])?$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']:false;
	if ($up !== false) {
		if (!is_array($up)) $up = @unserialize($up);
		$ipp = $up['pfim'];
	} else
		$ipp = 15; 

	function gimmepages(&$template, &$params, $data, $processed=false) {
		if ($processed) return $data;
		$data['pg'] = ceil($data['totalposts']/$params['ipp']);
		return $data;
	}

	$fdata = $core->runContent('forum',$core->template,$_REQUEST['id_forum']); // filled by udm
	$id = $_REQUEST['id_forum'];
	if ($fdata['id_parent'] > 0) $core->template->assign("separator","‒");
	$this->parent->template->constants['PAGE_TITLE'] .= " - ".$fdata['title'];

	$mode = $fdata['operationmode'];
	
	switch ($mode) {
		case "bb": // order by last post, gather data from last post, gather totals
			$sql = "SELECT t.id, t.title, t.image as image,t.date, t.urla as turla, a.login as author_login,
						   p.date as pdate, u.login, count(distinct p2.id) as totalposts
				    FROM (bb_thread as t, bb_post as p, auth_users as u, auth_users as a)
				    LEFT JOIN bb_post as p2 ON (p2.id_forumthread = t.id AND p2.id_forum = $id)
				    WHERE t.id_forum = $id AND t.publish='y' AND t.publish_after < NOW() AND
				    	  p.id_forumthread = t.id AND p.id_forum = $id AND
				    	  u.id = p.id_author AND
				    	  a.id = t.id_author
				    GROUP BY t.id
				    ORDER BY p.date DESC";
			$core->template->assign("_notbb");
		break;
		case "blog": // order by thread, gather data from first post, no totals
			$sql = "SELECT t.id, t.title, t.image as image,t.date, t.urla as turla, a.login as author_login,
						   p.date as pdate, p.content as pcontent, u.login
				    FROM (bb_thread as t, bb_post as p, auth_users as u, auth_users as a)
				    WHERE t.id_forum = $id AND t.publish='y' AND t.publish_after < NOW() AND
				    	  p.id_forumthread = t.id AND p.id_forum = $id AND
				    	  u.id = p.id_author AND
				    	  a.id = t.id_author
				    GROUP BY t.id
				    ORDER BY t.date DESC, p.date DESC";
			$core->template->assign("_bb");
		break;
		case "articles": // order by thread, gather data from first post, no totals, no users
			$sql = "SELECT t.id, t.title, t.image as image,t.date, t.urla as turla,
						   p.date as pdate, p.content as pcontent
				    FROM (bb_thread as t, bb_post as p)
				    WHERE t.id_forum = $id AND t.publish='y' AND t.publish_after < NOW() AND
				    	  p.id_forumthread = t.id AND p.id_forum = $id
				    GROUP BY t.id
				    ORDER BY t.date DESC, p.date DESC";
			$core->template->assign('_notarticles');
			$core->template->assign("_bb");
		break;
	}
	$core->template->assign("mode",$mode);

	$core->templateParams['ipp'] = $ipp;
	if ($mode == 'bb') // with pages
		$total = $core->runContent('forumthread',$core->template,$sql,"_thread",$ipp,"threadsfor".$id."p".$p,"gimmepages");
	else // w/o pages
		$total = $core->runContent('forumthread',$core->template,$sql,"_thread",$ipp,"threadsfor".$id."p".$p);
		
	if ($total > $ipp)
		$core->template->createPaging("_paginacao",$total,$p,$ipp);
	else
		$core->template->assign("_paginacao");

	if ($mode == 'bb') $core->template->assign("pg_2",$core->template->get("_paginacao"));

	if ($mode != 'articles' && !$this->noregistration) {
		$core->addLink("ckeditor/ckeditor.js",true);
		$core->addLink("validators.js");
	}

	
	
