<?

	if ($this->parent->action == "forum" && !$this->parent->queryOk(array("#id"))) {
		$this->parent->action = "index";
		return;
	}

	// this is a very active area, so we have to keep output cache to a minimum

	$ok = $this->parent->friendlyurl(array("module" => "forumthread",
							"page" => "thread",
							"keys" => "urla",
							"title" => "Forum - {forum_title} - {title}",
							"metadesc" => "Forums - {forum_title} - {title}",
							));
