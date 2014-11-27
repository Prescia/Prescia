<? /* ----------------------------------------- Default script for bb pages
 *
 */

 	// loads frame
 	if ($core->action != "thread" || !isset($core->storage['friendlyurldata']))
 		$core->loadTemplate("",true);
	else { // template depends on operation mode
		$useTemplate = "";
		switch ($core->storage['friendlyurldata']['forum_operationmode']) {
			case "bb":
				$useTemplate = $this->bbpage;
			break;
			case "blog":
				$useTemplate = $this->blogpage;
			break;
			case "articles":
				$useTemplate = $this->articlepage;
			break;
		}
		$core->loadTemplate($useTemplate);
	}
	$bb = $core->loadedPlugins['bi_bb'];

 	$core->addScript('bootstrap');
	if ($core->action == 'profile') {
		$core->addLink('prototype_oop.js');
		$core->addLink('prototype_ajax.js');
	}
	$core->addLink('common.js');

	$qs = arrayToString($_GET,array('layout'));
	$core->template->assign("main_qs",$qs);

	$core->template->assign("areaname",$bb->areaname);
	$core->template->assign("homename",$bb->homename);

	if ($core->template->get("_topforums") !== false)
		$core->runContent('forum',$core->template,array('(forum.id_parent=0 OR forum.id_parent is NULL)  AND forum.urla<>"" AND forum.lang="'.$_SESSION[CONS_SESSION_LANG].'"','forum.ordem asc',''),'_topforums',false,'frameforuns');

	if ($this->noregistration)
		$core->template->assign("_registration");
	
	if ($this->bbfolder == '/')
		$core->template->assign("_areaindex");