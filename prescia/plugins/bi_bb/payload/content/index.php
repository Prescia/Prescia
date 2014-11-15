<?

	if (!$this->blockforumlist) {

		$idF = isset($_REQUEST['id_forum'])?$_REQUEST['id_forum']:'';
		$lang = isset($_REQUEST['lang'])?$_REQUEST['lang']:$_SESSION[CONS_SESSION_LANG];

		$sql = "SELECT forum.id,forum.title,forum.urla,forum.id_parent,
				count(distinct t.id) as t,count(distinct post.id) as p
				FROM bb_forum as forum
				LEFT JOIN bb_thread as t ON t.id_forum = forum.id
				LEFT JOIN bb_forum as fp ON fp.id = forum.id_parent
				LEFT JOIN bb_post as post ON post.id_forum = forum.id AND post.id_forumthread = t.id
				WHERE ".($idF!=''?"forum.id_parent=$idF AND ":"")."
					  forum.lang='".$lang."'
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

		$forumObj = $core->loaded('forum');
		$tree = $forumObj->getContents("","urla","/","",$sql,true,'mycallback'); // perform the sql, but get it back in a tree style
		
		$core->template->getTreeTemplate("_forum","_subforum",$tree,0); // echo in tree style


		 

	}