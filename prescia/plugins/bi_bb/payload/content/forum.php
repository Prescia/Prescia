<?

	$p = isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init'])?$_REQUEST['p_init']:0; // item starting this page
	$ipp = 30; // itens per page

	$fdata = $core->runContent('forum',$core->template,$_REQUEST['id']); // numeric id tested at default.php
	$id = $fdata['id'];

	$sql = "SELECT t.id, t.title, t.date, t.urla, t.lastupdate,
				   p.date as pdate, u.login, count(distinct p2.id) as totalposts
		    FROM (bb_thread as t, bb_post as p, auth_users as u)
		    LEFT JOIN bb_post as p2 ON (p2.id_forumthread = t.id AND p2.id_forum = $id)
		    WHERE t.id_forum = $id AND
		    	  p.id_forumthread = t.id AND p.id_forum = $id AND
		    	  u.id = p.id_author
		    ORDER BY p.date DESC";

	$total = $core->runContent('forumthread',$core->template,$sql,"_thread",$ipp,"threadsfor".$id."p".$p);
	if ($total > $ipp)
		$core->template->createPaging("_paginacao",$total,$p,$ipp);
	else
		$core->template->assign("_paginacao");

	$core->template->assign("pg_2",$core->template->get("_paginacao"));

