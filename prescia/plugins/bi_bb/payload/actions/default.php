<?

	if ($this->parent->action == "forum" && !$this->parent->queryOk(array("#id"))) {
		$this->parent->action = "index";
		return;
	}

	$ok = $this->parent->friendlyurl(array("module" => "forumthread",
							"page" => "thread",
							"keys" => "urla",
							"title" => "Forum - {forum_title} - {title}",
							"metadesc" => "Prescia Framework - Forums - {forum_title} - {title}",
							
							));

	//$this->parent->debugFile = CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/_debugarea.html"; // this is our debug area