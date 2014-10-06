<? /* ----------------------------------------- Default script for bb pages
 * 
 */

 	// loads frame
 	$core->loadTemplate();
	$bb = $core->loadedPlugins['bi_bb'];
 
 	$core->addScript('bootstrap');
	$core->addLink('common.js');
	
	$qs = arrayToString($_GET,array('layout'));
	$core->template->assign("main_qs",$qs);
	
	$core->template->assign("areaname",$bb->areaname);
	$core->template->assign("homename",$bb->homename);
	
	$core->runContent('forum',$core->template,array('(forum.id_parent=0 OR forum.id_parent is NULL)  AND forum.urla<>"" AND forum.lang="'.$_SESSION[CONS_SESSION_LANG].'"','forum.ordem asc',''),'_forums',false,'frameforuns'.$_SESSION[CONS_SESSION_LANG]);
