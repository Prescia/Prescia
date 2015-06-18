<? /* ----- BB thread
 * Behaves 3 different ways depending on operationmode of the thread
 * Note that the correct template must have been loaded (that is done on default.php)
 * + BB MODE: list of posts, just like a normal forum
 * + BLOG MODE: the first post is the BLOG POST, the rest are COMMENTS. The BLOG POST always shows even on "n" pages
 * + ARTICLES MODE: there are no comments, only ONE post, so no pagination and stuff, just the _masterpost
 */

	if (!isset($core->storage['friendlyurldata']) ||
	    !isset($core->storage['friendlyurlmodule'])) $core->fastClose(404);

	// ############################# URL handling
	// this is necessary because all the virtual and SEO names changed what will be served, but what the script runs is this file.
	// we need to change what will be logged and the canonical or it will be logged/canonical as "thread.php"
	// this script also detects ill formed forum links (missing parents) and handle them
	
	$pageToBelogged = substr($core->original_context_str,1); // <-- will be used to get statistics, but we also use to detect wrong URL (missing parent)
	if ($pageToBelogged != "" && $pageToBelogged[strlen($pageToBelogged)-1] != "/") $pageToBelogged .= "/";
	$act = $core->original_action;
	if (strpos($act,".")!==false) {
		$act = explode(".",$act); // remove extension:
		array_pop($act);
		$act = implode(".",$act);
	}
	$rootLog = $pageToBelogged;
	$pageToBelogged .= $act;
	$root = $this->bbfolder;
	if ($root != "") {
		$root = substr($root,1);
		$pageToBelogged = substr($pageToBelogged,strlen($root));
	}
	// fix canonical
	$core->template->constants['CANONICAL'] = "http://".$_SESSION['CANONICAL']."/".$root.$pageToBelogged.".html";				
	// change context back so statistics work (was consumed on UDM)
	$core->original_context_str = "/".$root.$rootLog; // for the statistics
	// detect wrong parent
	if ($rootLog == $root && $core->storage['friendlyurldata']['forum_urla'] != '') {	
		$core->template->constants['CANONICAL'] = "http://".$_SESSION['CANONICAL']."/".$root.$core->storage['friendlyurldata']['forum_urla']."/".$act.".html"; 
		if ($this->forceParentFolder) $core->headerControl->internalFoward($core->template->constants['CANONICAL']."?lang=".$_SESSION[CONS_SESSION_LANG],301); 
	} 
	// ############################# END URL HANDLING


	$this->parent->template->constants['PAGE_TITLE'] .= " - ".$core->storage['friendlyurldata']['forum_title']." - ".$core->storage['friendlyurldata']['title'];

	// user options (ipp)
	$up = isset($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'])?$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']:false;
	if ($up !== false) {
		if (!is_array($up)) $up = @unserialize($up);
		$ipp = $up['pfim'];
	} else
		$ipp = 15;

	$mode = $core->storage['friendlyurldata']['forum_operationmode']; 
	if ($mode != 'bb' && $core->template->get("_masterpost") === false) $mode = "bb"; // force bb mode if we don't have master post (personalized template fail)

	// ckeditor if we can add data, shadowbox if image
	if (!$this->noregistration || $mode == "articles") { // if we don't allow registration, we also don't allow postings
		$core->addLink("ckeditor/ckeditor.js",true);
		$core->addLink("validators.js");
	}

	// -- this will apply image tags and use _toggles
	$core->templateParams['core'] = &$this->parent;
	$core->templateParams['module'] = $core->loaded($core->storage['friendlyurlmodule']);
	$core->storage['friendlyurldata'] = prepareDataToOutput($core->template,$core->templateParams,$core->storage['friendlyurldata']);
	$core->template->fill($core->storage['friendlyurldata']);
	// --
	// image meta
	if ($core->storage['friendlyurldata']['image'] == 'y') {
		$core->addScript("shadowbox"); 
		$core->template->constants['METAFIGURE'] = $core->storage['friendlyurldata']['image_2'];
	}
	
	// add data from this FORUM data
	$core->template->assign("forumurla",$core->storage['friendlyurldata']['forum_urla'] != ''?$core->storage['friendlyurldata']['forum_urla']."/":"");
	// add video from FORUMTHREAD (image handled on prepareDataToOutput)
	if ($core->storage['friendlyurldata']['video']!='') {
		$core->template->assign("videoembed",getVideoFrame($core->storage['friendlyurldata']['video'],0,0,'embed-responsive-item'));
	} else
		$core->template->assign("_hasvideo");

	
			
	// prepare to get posts
	$idf = $core->storage['friendlyurldata']['id_forum'];
	$idt = $core->storage['friendlyurldata']['id'];
	
	// count posts
	$totalPost = $mode != "articles"?$core->dbo->fetch("SELECT count(id) FROM bb_post WHERE id_forum=$idf AND id_forumthread=$idt GROUP BY id_forumthread"):1;
	// NOTE: if we are not on bb mode, the FIRST post must be IGNORED, that's why we reduce one from totalPost in that case, EVERYWHERE
	if (isset($_REQUEST['lastpage'])) { // if requested last page, calculate first post of last page
		$_REQUEST['p_init'] = floor(($mode=='bb'?$totalPost:$totalPost-1)/$ipp)*$ipp;
	}
	$core->template->assign("pg",ceil(($mode=='bb'?$totalPost:$totalPost-1)/$ipp));

	
	$v = $core->loadedPlugins['bi_stats']->getCounter($pageToBelogged);
	$core->template->assign("v",$v>0?$v:1);

	// posts (count) - note again the first post of a non-bb mode is not counted
	$core->template->assign("p",$mode == 'bb'?$totalPost:$totalPost-1);

	// posts
	$sql = "SELECT p.*,u.login, u.image, u.name
		    FROM (bb_post as p, auth_users as u)
		    WHERE p.id_forumthread = $idt AND p.id_forum = $idf AND
		    	  u.id = p.id_author
		    ORDER BY p.date ASC";
	
	// where we start?
	$tempini = isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init'])? $_REQUEST['p_init']:0; // apparent start 
	if ($mode != 'bb') {
		// always show the FIRST main post
		$_REQUEST['p_init'] = 0;
		$core->templateParams['mainpost'] = true; // we will use on callback to treat includehtml
		$mainPost = $core->runContent('forumpost',$core->template,$sql,"_masterpost",1,"masterpost".$idt."idf".$idf,"getuseravatar");
		unset($core->templateParams['mainpost']);
		// this is the real starting point (add one because we showed the main post already)
		$_REQUEST['p_init'] = $tempini + 1;
	} else
		// bb mode works normally
		$_REQUEST['p_init'] = $tempini;

	// comments	
	if ($mode != "articles") {
		$total = $core->runContent('forumpost',$core->template,$sql,"_post",$ipp,"postsforidt".$idt."idf".$idf."p".$tempini,"getuseravatar");
		// paging
		if ($mode != 'bb') $total--; // removes main post of non bb, so totals are ok
		if ($total > $ipp)
			$core->template->createPaging("_paginacao",$total,$tempini,$ipp);
		else
			$core->template->assign("_paginacao");
		$core->template->assign("pg_2",$core->template->get("_paginacao"));
	}

	// callback that loads user avatars or default image
	function getuseravatar(&$template, &$params, $data, $processed=false) {
		if ($processed) return $data;
		if ($data['image'] == 'n')
			$params['excludes'][] = "_imageyes";
		else {
			$params['excludes'][] = "_imageno";
			$data['image'] = CONS_PATH_PAGES.$_SESSION['CODE']."/files/users/t/image_".$data['id_author']."_2";
			$ext = "";
			locateFile($data['image'],$ext);
		}
		if (isset($params['mainpost']) && $data['includehtml'] != '') {
			$file = "";			
			if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template/".$data['includehtml'])) {
				$file = CONS_PATH_PAGES.$_SESSION['CODE']."/template/".$data['includehtml'];
			} else if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template/".$data['includehtml'].".html")) {
				$file = CONS_PATH_PAGES.$_SESSION['CODE']."/template/".$data['includehtml'].".html";
			}
			if ($file != '') {
				$tmpTP = new CKTemplate($params['core']->template);
				$tmpInner = new CKTemplate($params['core']->template);
				$tmpTP->append($data['content']);
				$tmpInner->fetch($file);
				$tmpTP->append($tmpInner);
				$params['core']->removeAutoTags($tmpTP);
				$data['content'] = $tmpTP;
			} else {
				$data['content'] .= "<br/><small>File not found: ".$data['includehtml']."</small>";
			}
		}
		return $data;
	}

