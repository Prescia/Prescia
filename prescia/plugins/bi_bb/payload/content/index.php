<?

	$sql = "SELECT f.id,f.title,
			count(distinct t.id) as t,count(distinct post.id) as p
			FROM bb_forum as f, bb_thread as t, bb_post as post
			WHERE f.lang='".$_SESSION[CONS_SESSION_LANG]."' AND
				  t.id_forum = f.id AND
				  post.id_forum = f.id AND post.id_forumthread = t.id
			GROUP BY f.id
			ORDER BY f.ordem ASC";

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
		}
		// now, get first thread per forum (same, could be cached)
		$sql = "SELECT t.title, t.date, t.urla, a.login FROM bb_thread as t, auth_users as a WHERE t.id_forum=".$data['id']." AND a.id = t.id_author ORDER BY t.date DESC LIMIT 1";
		if ($params['core']->dbo->query($sql,$r,$n) && $n>0) {
			$newData = $params['core']->dbo->fetch_row($r);
			$data['lt_title'] = $newData[0];
			$data['lt_date'] = $newData[1];
			$data['lt_urla'] = $newData[2];
			$data['lt_author'] = $newData[3];
		}
		
		return $data;
	}

	$core->runContent('forum',$core->template,$sql,"_forum",false,"presciaforum".$_SESSION[CONS_SESSION_LANG],"mycallback");