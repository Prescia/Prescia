<?

	// We can make some nasty multiple inner selects, but let's face it, its slow, and we are not here to show how fancy our SQL is. So, step by step please
	$sql = "SELECT f.id,f.title,count(distinct thread.id) as t,count(distinct post.id) as p
			FROM bb_forum as f, bb_thread as thread, bb_post as post
			WHERE f.lang='".$_SESSION[CONS_SESSION_LANG]."' AND
				  thread.id_forum = f.id AND
				  post.id_forum = f.id
			GROUP BY f.id
			ORDER BY f.ordem ASC";

	function mycallback(&$template, &$params, $data, $processed=false) {
		if ($processed) return $data;
		// now, get latest thread per forum
		$sql = "SELECT id,title,urla,date FROM bb_thread WHERE id_forum=".$data['id']." ORDER BY date DESC LIMIT 1";
		if ($params['core']->dbo->query($sql,$r,$n) && $n>0) {
			$newData = $params['core']->dbo->fetch_row($r);
			$data['lt_id'] = $newData[0];
			$data['lt_title'] = $newData[1];
			$data['lt_urla'] = $newData[2];
			$data['lt_date'] = $newData[3];
			$sql = "SELECT t.id, t.title, t.urla, p.date, a.login FROM bb_post as p, bb_thread as t, auth_users as a WHERE p.id_forum=".$data['id']." AND p.id_forumthread=".$data['lt_id']." AND t.id = p.id_forumthread AND a.id = p.id_author ORDER BY p.date DESC LIMIT 1";
			if ($params['core']->dbo->query($sql,$r,$n) && $n>0) {
				$newData = $params['core']->dbo->fetch_row($r);
				$data['lp_id'] = $newData[0];
				$data['lp_title'] = $newData[1];
				$data['lp_urla'] = $newData[2];
				$data['lp_date'] = $newData[3];
				$data['lp_author'] = $newData[4];
			}
		}
		return $data;
	}
	
	$core->runContent('forum',$core->template,$sql,"_forum",false,"presciaforum".$_SESSION[CONS_SESSION_LANG],"mycallback");