<?

	// change how the debug messages show?
	$this->debugFile = CONS_PATH_PAGES.$_SESSION['CODE']."/template/_debugarea.html";

	// perform UDM (routes)?

	$ok = $this->udm(array(array('module' => 'PRESCIATOR',
								 'key' => 'autourl',
								 'convertquery' => 'magic', // if the URL is the key, put keys this $_REQUEST
								 'fillqueries' => 'readmeonly', // also, fill the $_REQUEST for these fields
								 'filter' => 'alpha="key"' // add this SQL to the WHERE statement to match the udm
								)
							) // we can have multiple folders, just put in descending order
					,true); // true, trash all others, we don't care about them (false would cause 404)
	// if you have more than one UDM structure, just keep calling it with the different structures until you get an $ok
	if ($ok) $this->virtualFolder = false;
	//*/

	// Do we have friendly URL's?

	$ok = $this->friendlyurl(array("module" => "PRESCIATOR",
							"page" => "urlapage",
							"keys" => "manualurl",
							"title" => "We redirected! - {title} - {ignoreme} - {date|date|d.m.Y}",
							"queryfilter" => "alpha<>'key'",
							"metadesc" => "{title}",
							"metakeys" => "{category_title} {tags}"
							));
	// if more than one friendlyurl can route here, test $ok before continuing
	//*/

	if (!isset($this->dimconfig['presciastage'])) {
		$this->log[] = "Something went wrong with custom.xml";
	} else if ($this->dimconfig['presciastage'] == '') {
		$this->dimconfig['presciastage'] = 'start';
		$this->saveConfig();
	}