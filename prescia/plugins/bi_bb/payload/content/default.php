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

	// -- use showHeader on pages that are not controled by the bi_bb to fill the top menu, like this: $this->loadedPlugins['bi_bb']->showHeader();
	$bb->showHeader();

	if ($this->noregistration)
		$core->template->assign("_registration");
	
	if ($this->bbfolder == '/')
		$core->template->assign("_areaindex");