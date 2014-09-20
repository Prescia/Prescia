<?

	##################################################################
	# if you copy this to your project, replace $core with $this
	# also note $filter comes from the bi_blog folder, so you should handle it first
	##################################################################


	// apply proper extension
	$core->template->assign("ext2use",$core->layout==2?"ajax":"html");
	// reset filter if none came (defined by action, module or meta)
	if (!isset($filter)) $filter = "";

	// query string, used for cache modules
	$qs = arrayToString();
	// consider plain list for now
	$mode = 0;
	// get mode ...
	if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) $mode = 1; // showing a defined blog
	else if (isset($_REQUEST['id_category']) && is_numeric($_REQUEST['id_category'])) $mode = 2; // filtering by category
	else if (isset($_REQUEST['date']) && is_numeric($_REQUEST['date'])) $mode = 3; // filtering by date (should come as "Ym")
	else if (isset($_REQUEST['tag'])) $mode = 4; // filtering by tag
	else if (isset($_REQUEST['all'])) $mode = 5; // show ALL
	// load blog modules
	$blog = $core->loaded('blog');
	$blogPlugin = $core->loadedPlugins['bi_blog'];
	// remove about?
	if ($blogPlugin->hideAbout) $core->template->assign("_aboutarea");
	// add blog title, if any
	if ($blogPlugin->blogTitle != '')
		$core->template->assign("blogtitle",$blogPlugin->blogTitle);
	else
		$core->template->assign("_blogtitle","");
	// list filtered blogs
	$n = 0;
	// check for gallery
	if ($this->galleryObject !== false) {
		function embedGallery(&$template, &$params, $data, $processed = false) {
			if (!$processed)
				$data['GALLERY'] = $params['gO']->embedGallery($data[$params['module']->keys[0]]);
			return $data;
		}
		$core->templateParams['gO'] = $this->galleryObject;

		$callback = array('embedGallery');
	} else
		$callback = array();

	$titleCat = -1;
	switch ($mode) {
		case 0: // list
			$n = $blog->runContent($core->template,array("$filter","",""),"_blogItem",$blogPlugin->blogsPerPage,"blogItemList$filter$qs",$callback);
			$core->template->createPaging("_PAGING",$n, (isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init']) ? $_REQUEST['p_init'] : '0'),$blogPlugin->blogsPerPage);
			$core->template->assign("_hasFilter");
			$core->template->assign("_blogAll");
		break;
		case 1: // specific
			$n = 1; // should have been filled by friendlyurl
			$core->templateParams['module'] = $blog;
			if ($this->galleryObject !== false) $core->template->fill(embedGallery($core->template,$core->templateParams,$core->storage['friendlyurldata']));
			$core->template->assign("_hasFilter");
			$core->template->assign("_blogAll");
			$core->template->assign("_PAGING");

		break;
		case 2: // category
			$n = $blog->runContent($core->template,array("blog.id_category=".$_REQUEST['id_category'].($filter!=""?" AND $filter":""),"",""),"_blogItem",$blogPlugin->blogsPerPage,"blogItemListCat$filter$qs",$callback);
			$core->template->createPaging("_PAGING",$n, (isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init']) ? $_REQUEST['p_init'] : '0'),$blogPlugin->blogsPerPage);
			$bc = $core->loaded('blog_category');
			$core->template->assign("filtername","category");
			$core->template->assign("filter",$core->dbo->fetch("SELECT title FROM ".$bc->dbname." WHERE id=".$_REQUEST['id_category']));
			$core->template->assign("_blogAll");
			$titleCat = $_REQUEST['id_category'];
		break;
		case 3: // date
			if (strlen($_REQUEST['date'])>5) {
				$y = substr($_REQUEST['date'],0,4);
				$m = substr($_REQUEST['date'],4,2);
				$dstart = $y."-".$m."-01 00:00:00";
				$m++;
				if ($m==13) {
					$m="01";
					$y++;
				}
				$dend = $y."-".($m<10?"0":"").$m."-01 00:00:00";
				$n =$blog->runContent($core->template,array("blog.date>='$dstart' AND blog.date<'$dend'".($filter!=""?" AND $filter":""),"",""),"_blogItem",$blogPlugin->blogsPerPage,"blogItemListDate$filter$dstart",$callback);
			} else
				$n = 0;
			$core->template->createPaging("_PAGING",$n, (isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init']) ? $_REQUEST['p_init'] : '0'),$blogPlugin->blogsPerPage);
			$core->template->assign("filtername","date");
			$core->template->assign("filter",$_REQUEST['date']);
			$core->template->assign("_blogAll");

		break;
		case 4: // tag
			$tag = cleanString($_REQUEST['tag']);
			$n = $blog->runContent($core->template,array("blog.tags REGEXP '[[:<:]]".$tag."[[:>:]]'".($filter!=''?" AND $filter":""),"",""),"_blogItem",$blogPlugin->blogsPerPage,"blogItemListTag$filter$tag",$callback);
			$core->template->createPaging("_PAGING",$n, (isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init']) ? $_REQUEST['p_init'] : '0'),$blogPlugin->blogsPerPage);
			$core->template->assign("filtername","tag");
			$core->template->assign("filter",$tag);
			$core->template->assign("_blogAll");

		break;
		case 5: // all
			$n = $blog->runContent($core->template,array("$filter","",""),"_blogAllItem",$blogPlugin->blogsPerPage*10,"blogAll$filter$qs",$callback);
			$core->template->createPaging("_PAGING",$n, (isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init']) ? $_REQUEST['p_init'] : '0'),$blogPlugin->blogsPerPage*10);
			$core->template->assign("_hasFilter");
			$core->template->assign("_blogItem");

		break;
	}
	// found any? remove message about none found
	if ($n>0) $core->template->assign("_blogNone");
	// full blog?
	if ($core->layout == 0 || $mode == 0) {
		// category title?

		if ($mode == 0 || $mode == 2) {

			if ($titleCat != -1 && $mode == 2) {
				// detect page
				if (!isset($core->templateParams['p_init']) || !is_numeric($core->templateParams['p_init']) || $core->templateParams['p_init']<0) {
					if (isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init']) && $_REQUEST['p_init']>=0)
						$this->parent->templateParams['p_init'] = $_REQUEST['p_init'];
					else
						$this->parent->templateParams['p_init'] = 0;
				}
				// if on first page, show CATEGORY introduction
				if ($this->parent->templateParams['p_init'] == 0) {
					$blogc = $core->loaded('blog_category');
					$n = $blogc->runContent($core->template,array("blog_category.id=".$titleCat,"",1),"_blogIndexMsg");
					if ($n==0 || $core->lastReturnCode['introduction'] == '') $core->template->assign("_blogIndexMsg");
					else $core->template->assign("_hasFilter");
				} else {
					$core->template->assign("_blogIndexMsg"); // no category introduction
				}
				$core->template->assign("_mainIndex");
			} else if ($mode == 0) { // MAIN index
				if (!isset($core->templateParams['p_init']) || !is_numeric($core->templateParams['p_init']) || $core->templateParams['p_init']<0) {
					if (isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init']) && $_REQUEST['p_init']>=0)
						$this->parent->templateParams['p_init'] = $_REQUEST['p_init'];
					else
						$this->parent->templateParams['p_init'] = 0;
				}
				if ($this->parent->templateParams['p_init'] != 0)
					$core->template->assign("_mainIndex"); // not first page
			} else
				$core->template->assign("_blogIndexMsg"); // not category nor main index

		} else {
			$core->template->assign("_blogIndexMsg");
		}


		// list last 10 blogs. If we are filtering by category, filter them too
		$blog->runContent($core->template,array(($mode==2?"blog.id_category=".$_REQUEST['id_category']:"").($filter!=""?($mode==2?" AND ":"").$filter:""),"",10),"_blogList",false,"blogList".$filter.($mode==2?"-".$_REQUEST['id_category']:""));

		// list featured blogs. Again, filter category
		$blog->runContent($core->template,array("blog.is_featured='y'".($mode==2?" AND blog.id_category=".$_REQUEST['id_category']:"").($filter!=''?" AND $filter":""),"",0),"_featureList",false,"featureList".$filter.($mode==2?"-".$_REQUEST['id_category']:""));

		// list archieves
		$cH = $core->cacheControl->getCachedContent("blogArchieves$filter",600);
		if ($cH == false) {
			$AD = $blogPlugin->getArchieveDates($filter);
			$tObj = $core->template->get("_blogArchieves");
			$temp = "";
			foreach ($AD as $ADdate) {
				$temp .= $tObj->techo($ADdate);
			}
			$core->template->assign("_blogArchieves",$temp);
			$core->cacheControl->addCachedContent("blogArchieves$filter",$temp,true);
			unset($temp);
			unset($tObj);
		} else {
			$core->template->assign("_blogArchieves",$cH);
		}

		// list categories
		$core->runContent('blog_category',$core->template,array($langfilter,"",""),"_blogCategories");

		// list tags
		$cachetag = "blogTags".$filter.($mode==2?"-".$_REQUEST['id_category']:"");
		$cH = $core->cacheControl->getCachedContent($cachetag,900);
		if ($cH == false) {
			$AT = $blogPlugin->getTags(($mode==2?"blog.id_category=".$_REQUEST['id_category']." AND ":"").$filter);
			$tObj = $core->template->get("_blogTags");
			$temp = "";
			foreach ($AT as $tag => $size) {
				$temp .= $tObj->techo(array('tagsize'=>$size,'tag'=>$tag));
			}
			$core->template->assign("_blogTags",$temp);
			$core->cacheControl->addCachedContent($cachetag,$temp,true);
			unset($temp);
			unset($tObj);
		} else {
			$core->template->assign("_blogTags",$cH);
		}
	} else
		$core->template->assign("_removeonindexajax");

	if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template/_blogcustom.html")) {
		$core->template->assignFile("customarea",CONS_PATH_PAGES.$_SESSION['CODE']."/template/_blogcustom.html");
		$core->template->assign("customareaname",$this->customareaname);
	} else
		$core->template->assign("_customarea");