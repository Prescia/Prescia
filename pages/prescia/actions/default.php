<?

	// change how the debug messages show?
	# $this->debugFile = CONS_PATH_PAGES.$_SESSION['CODE']."/template/_debugarea.html";

	// perform UDM (routes)?
	/*
	$ok = $this->udm(array(array('module' => 'BLOG_CATEGORY',
								 'key' => 'urla',
								 'convertquery' => 'category_id', // if the URL is the key, put keys this $_REQUEST
								 'fillqueries' => 'lang' // also, fill the $_REQUEST for these fields
								)
							) // we can have multiple folders, just put in descending order
					,true); // true, trash all others, we don't care about them (false would cause 404)
	// if you have more than one UDM structure, just keep calling it with the different structures until you get an $ok
	if ($ok) $this->virtualFolder = false;
	//*/

	// Do we have friendly URL's?
	/*
	$ok = $this->friendlyurl(array("module" => "blog",
							"page" => "blog",
							"keys" => "urla",
							"title" => "Simpla - {category_title} - {title}",
							"queryfilter" => "category_id",
							"metadesc" => "{title}",
							"metakeys" => "{category_title} {tags}"
							));
	// if more than one friendlyurl can route here, test $ok before continuing
	//*/
