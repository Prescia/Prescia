<?
	
	##################################################################
	# if you copy this to your project, replace $core with $this
	##################################################################
	
	
	// apply proper extension
	$core->template->assign("ext2use",$core->layout==2?"ajax":"html");
	// get the gallery set
	if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) $set = $_REQUEST['id'];
	else $core->fastClose(404);
	// ajax?
	if ($core->layout != 0) 
		$core->template->assign("_removeonindexajax");
	// load modules
	$gal = $core->loaded('gallery');
	$gals = $core->loadedPlugins['gallery_set'];

	$n = $gal->runContent($core->template,array("gallery.id_set='".$set."'","gallery.date ASC",""),"_images",false,"GalleryAll$set");
	
	
		
?>