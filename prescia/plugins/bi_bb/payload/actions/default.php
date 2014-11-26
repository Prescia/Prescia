<?

	if ($this->parent->action == "preview" || $this->parent->action == "profile") $_REQUEST['nocache'] = true; 

	$permaFolders = count(explode("/",$this->bbfolder))-2;
	$ok = $this->parent->udm(array(array('module' => 'FORUM',
								 'key' => 'urla',
								 'convertquery' => 'id_forum', // if the URL is the key, put keys this $_REQUEST
								 'filter' => 'forum.lang = "'.$_SESSION[CONS_SESSION_LANG].'"', // also, fill the $_REQUEST for these fields
								 'treemode' => true, // will use the parenting system to check for folder tree structures
								 'treeoffset' => $permaFolders // either to ignore or not the root virtual folder that contains the forum 
								)
							) // we can have multiple folders, just put in descending order
					,true); // true, trash all others, we don't care about them (false would cause 404)
	// if you have more than one UDM structure, just keep calling it with the different structures until you get an $ok
	if ($ok) {
		$this->parent->virtualFolder = false;
		// if we are at index, internal-foward to forum
		if ($this->parent->action == 'index') $this->parent->action = 'forum';
	} else if ($this->parent->action == 'forum') 
		$this->parent->action = 'index';

	// checks if the file is a thread (don't even run on obvious actions)
	if (!in_array($this->parent->action,array("index","forum","thread","profile","preview")))
		$ok = $this->parent->friendlyurl(array("module" => "forumthread",
								"page" => "thread",
								"keys" => "urla",
								"title" => "Forum - {forum_title} - {title}",
								"queryfilter" => "forumthread.id_forum",
								"metadesc" => "Forums - {forum_title} - {title}",
								));

	//$this->parent->debugFile = CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/_debugarea.html"; // this is our debug area