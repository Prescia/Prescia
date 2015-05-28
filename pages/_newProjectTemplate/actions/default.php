<? /* -------------------------------------------------
 * action/default.php
 * This file runs ALWAYS (if on the proper folder, ofc), and is the VERY FIRST to run. Only codes that run before are onMeta and onCheckActions from plugins
 * Right after this, the page/action file (if any) will run.
 * All files inside action/ run BEFORE THE TEMPLATE IS LOADED, so don't even try to access or change what you need to echo
 * Also, since the template was not loaded, you CAN change what page to serve by directly changhing $this->action
 * AGAIN: All files in this folder are for ACTIONS only, no OUTPUT can be performed here (unless this is an ajax request).
 * 
 * If you want this to run for sub-folders, just call this from them (like include CONS_PATH_PAGES.$_SESSION['CODE']."/actions/default.php")
 * 
 * Did I say you should not OUTPUT or try to access the TEMPLATE in here? ok...
 * 
 * Yes you can remove all this comments when you are done
*/ 

	// change how the debug messages show?
	# $this->debugFile = CONS_PATH_PAGES.$_SESSION['CODE']."/template/_debugarea.html";

	// perform UDM (routes)?
	/*
	$ok = $this->udm(array(array('module' => 'BLOG_CATEGORY',
								 'key' => 'urla',
								 'convertquery' => 'category_id', // if the URL is the key, put keys this $_REQUEST
								 'fillqueries' => 'lang', // also, fill the $_REQUEST for these fields
								 'filter' => 'x=0' // add this SQL to the WHERE statement to match the udm
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
