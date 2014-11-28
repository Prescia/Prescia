<?

	$showFullList = isset($_REQUEST['all']) && $_REQUEST['all'] == "true"; // send all=true to show the lists regardless of parenting, and the last threads with paging 

	if (!$this->blockforumlist) {

		$idF = isset($_REQUEST['id_forum']) && !$showFullList?$_REQUEST['id_forum']:'';
		$lang = isset($_REQUEST['lang'])?$_REQUEST['lang']:$_SESSION[CONS_SESSION_LANG];

		$forumObj = $core->loaded('forum');
		// foruns
		if ($core->template->get("_forum") !== false) {
			$sql = "SELECT forum.id,forum.title,forum.urla,forum.id_parent,
					count(distinct t.id) as t,count(distinct post.id) as p
					FROM bb_forum as forum
					LEFT JOIN bb_thread as t ON t.id_forum = forum.id
					LEFT JOIN bb_forum as fp ON fp.id = forum.id_parent
					LEFT JOIN bb_post as post ON post.id_forum = forum.id AND post.id_forumthread = t.id
					WHERE ".($idF!=''?"forum.id_parent=$idF AND ":"")."
						  forum.operationmode='bb' AND forum.lang='".$lang."'
					GROUP BY forum.id"; // order auto-filled by tree system
					
			function mycallback(&$template, &$params, $data, $processed=false) {
				if ($processed) return $data;
				// now, get latest post per forum (we could cache the last post on the database eventually)
				$sql = "SELECT t.id, t.title, t.urla, p.date, a.login FROM bb_post as p, bb_thread as t, auth_users as a WHERE p.id_forum=".$data['id']." AND t.id = p.id_forumthread AND a.id = p.id_author ORDER BY p.date DESC LIMIT 1";
				if ($params['core']->dbo->query($sql,$r,$n) && $n>0) {
					$newData = $params['core']->dbo->fetch_row($r);
					$data['lp_id'] = $newData[0];
					$data['lp_title'] = $newData[1];
					$data['lp_urla'] = $newData[2];
					$data['lp_date'] = $newData[3];
					$data['lp_author'] = $newData[4];
				} else {
					$data['lp_id'] = "";
					$data['lp_title'] = "";
					$data['lp_urla'] = "";
					$data['lp_date'] = "";
					$data['lp_author'] = "";
				}
				// now, get first thread per forum (same, could be cached)
				$sql = "SELECT t.title, t.date, t.urla, a.login FROM bb_thread as t, auth_users as a WHERE t.id_forum=".$data['id']." AND a.id = t.id_author ORDER BY t.date DESC LIMIT 1";
				if ($params['core']->dbo->query($sql,$r,$n) && $n>0) {
					$newData = $params['core']->dbo->fetch_row($r);
					$data['lt_title'] = $newData[0];
					$data['lt_date'] = $newData[1];
					$data['lt_urla'] = $newData[2];
					$data['lt_author'] = $newData[3];
				} else {
					$data['lt_title'] = "";
					$data['lt_date'] = "";
					$data['lt_urla'] = "";
					$data['lt_author'] = "";
				}
	
				return $data;
			}
	
			
			$tree = $forumObj->getContents("","urla","/","",$sql,true,'mycallback'); // perform the sql, but get it back in a tree style
			
			$core->template->getTreeTemplate("_forum","_subforum",$tree,0); // echo in tree style
		}

		// non-foruns
		if ($core->template->get("_others") !== false) {
			$sql = "SELECT forum.id,forum.title,forum.urla,forum.id_parent,
					count(distinct t.id) as t
					FROM bb_forum as forum
					LEFT JOIN bb_thread as t ON t.id_forum = forum.id
					LEFT JOIN bb_forum as fp ON fp.id = forum.id_parent
					WHERE ".($idF!=''?"forum.id_parent=$idF AND ":"")."
						  forum.operationmode<>'bb' AND forum.lang='".$lang."'
					GROUP BY forum.id"; // order auto-filled by tree system
					
			function mycallback2(&$template, &$params, $data, $processed=false) {
				if ($processed) return $data;
				// now, get first thread per forum 
				$sql = "SELECT t.title, t.date, t.urla, a.login FROM bb_thread as t, auth_users as a WHERE t.id_forum=".$data['id']." AND a.id = t.id_author ORDER BY t.date DESC LIMIT 1";
				if ($params['core']->dbo->query($sql,$r,$n) && $n>0) {
					$newData = $params['core']->dbo->fetch_row($r);
					$data['lt_title'] = $newData[0];
					$data['lt_date'] = $newData[1];
					$data['lt_urla'] = $newData[2];
					$data['lt_author'] = $newData[3];
				} else {
					$data['lt_title'] = "";
					$data['lt_date'] = "";
					$data['lt_urla'] = "";
					$data['lt_author'] = "";
				}
	
				return $data;
			}
	
			$tree = $forumObj->getContents("","urla","/","",$sql,true,'mycallback2'); // perform the sql, but get it back in a tree style
			
			$core->template->getTreeTemplate("_others","_subothers",$tree,0); // echo in tree style
		}

	} else {
		$core->template->assign("_others");
		$core->template->assign("_subothers");
		$core->template->assign("_forum");
		$core->template->assign("_subforum");
		
	}
	
	if ($this->showlastthreads > 0) {
	
		function gimmepages(&$template, &$params, $data, $processed=false) {
			if ($processed) return $data;
			$data['pg'] = ceil($data['totalposts']/$params['ipp']);
			return $data;
		}

		$p = isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init'])?$_REQUEST['p_init']:0; // item starting this page (if showing all)

		$core->template->assign("mode",$this->mainthreadsAsBB?"bb":"articles");
		$lang = $_SESSION[CONS_SESSION_LANG];
		if ($this->mainthreadsAsBB) {
			$this->templateParams['ipp'] = $this->showlastthreads;
			$sql = "SELECT t.id, t.title, t.image as image,t.date, t.urla as turla, a.login as author_login,
						   p.date as pdate, u.login, count(distinct p2.id) as totalposts,
						   f.title as forum_title, f.urla as urla
				    FROM (bb_thread as t,bb_forum as f, bb_post as p, auth_users as u, auth_users as a)
				    LEFT JOIN bb_post as p2 ON (p2.id_forumthread = t.id AND p2.id_forum = t.id_forum)
				    WHERE f.lang='$lang' AND
				    	  t.id_forum = f.id AND
				    	  p.id_forumthread = t.id AND p.id_forum = t.id_forum AND
				    	  u.id = p.id_author AND
				    	  a.id = t.id_author 
				    GROUP BY t.id
				    ORDER BY p.date DESC".(!$showFullList?" LIMIT ".$this->showlastthreads:"");
			$core->template->assign("_notbb");
			$total = $core->runContent('forumthread',$core->template,$sql,"_thread",$showFullList?$this->showlastthreads:false,"threadsAtIndex".$p,"gimmepages");
		} else {
			$sql = "SELECT t.id, t.title, t.image as image,t.date, t.urla as turla,
						   p.date as pdate, p.content as pcontent,
						   f.title as forum_title, f.urla as urla
				    FROM (bb_thread as t,bb_forum as f, bb_post as p)
				    WHERE f.lang='$lang' AND
				    	  t.id_forum = f.id AND
				    	  p.id_forumthread = t.id AND p.id_forum = t.id_forum 
				    GROUP BY t.id
				    ORDER BY t.date DESC, p.date DESC".(!$showFullList?" LIMIT ".$this->showlastthreads:"");
			$core->template->assign("_bb");
			$total = $core->runContent('forumthread',$core->template,$sql,"_thread",$showFullList?$this->showlastthreads:false,"threadsAtIndex".$p);
		}
		
		if ($showFullList && $total > $this->showlastthreads)
			$core->template->createPaging("_paginacao",$total,$p,$this->showlastthreads);
		else
			$core->template->assign("_paginacao");
			
	} else
	
		$core->template->assign("_thread");
		
