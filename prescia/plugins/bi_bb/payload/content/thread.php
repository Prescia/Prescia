<?

	$ipp = 2; // itens per page
	
	if (!isset($core->storage['friendlyurldata']) ||
	    !isset($core->storage['friendlyurlmodule'])) $core->fastClose(404);

	$core->template->fill($core->storage['friendlyurldata']);
	$idf = $core->storage['friendlyurldata']['id_forum'];
	$idt = $core->storage['friendlyurldata']['id'];
	
	$totalPost = $core->dbo->fetch("SELECT count(id) FROM bb_post WHERE id_forum=$idf AND id_forumthread=$idt");
	if (isset($_REQUEST['lastpage'])) {
		$_REQUEST['p_init'] = floor($totalPost/$ipp)*$ipp; 
	}
	$p = isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init'])?$_REQUEST['p_init']:0; // item starting this page
	$core->template->assign("pg",ceil($totalPost/$ipp));
	
	// views
	$pageToBelogged = substr($core->original_context_str,1);
	if ($pageToBelogged != "" && $pageToBelogged[strlen($pageToBelogged)-1] != "/") $pageToBelogged .= "/";
	$act = $core->original_action;
	if (strpos($act,".")!==false) {
		$act = explode(".",$act); // remove extension:
		array_pop($act);
		$act = implode(".",$act);
	}
	$pageToBelogged .= $act;	
	$v = $core->loadedPlugins['bi_stats']->getCounter($pageToBelogged);
	$core->template->assign("v",$v>0?$v:1);
	
	// posts (count)
	$core->template->assign("p",$core->dbo->fetch("SELECT count(distinct id) FROM bb_post WHERE id_forum=$idf AND id_forumthread=$idt GROUP BY id_forumthread"));
	
	// posts
	$sql = "SELECT p.*,u.login, u.image
		    FROM (bb_post as p, auth_users as u)
		    WHERE p.id_forumthread = $idt AND p.id_forum = $idf AND
		    	  u.id = p.id_author
		    ORDER BY p.date ASC";

	$total = $core->runContent('forumpost',$core->template,$sql,"_post",$ipp,"postsforidt".$idt."idf".$idf."p".$p,"getuseravatar");
	if ($total > $ipp)
		$core->template->createPaging("_paginacao",$total,$p,$ipp);
	else
		$core->template->assign("_paginacao");

	$core->template->assign("pg_2",$core->template->get("_paginacao"));
	
	function getuseravatar(&$template, &$params, $data, $processed=false) {
		if ($processed) return $data;
		if ($data['image'] == 'n')
			$params['excludes'][] = "_imageyes";
		else {
			$params['excludes'][] = "_imageno";
			$data['image'] = CONS_PATH_PAGES.$_SESSION['CODE']."/files/users/t/image_".$data['id_author']."_2";
			$ext = "";
			locateFile($data['image'],$ext);
		}
		return $data;
	}
	
	$core->addLink("ckeditor/ckeditor.js",true);
	$core->addLink("validators.js");