<? /* ----------------------------------------- Default script for bb pages
 * 
 */

 	// loads frame
 	$core->loadTemplate();
 
	$core->addLink('common.js');
	
	$qs = arrayToString($_GET,array('layout'));
	$core->template->assign("main_qs",$qs);

