<?

	// This file is the same as edit.php, with removed features

	######################################## VARIABLES ####################################
	// module existence already performed at action. Load it up here
	$module = $core->loaded($_REQUEST['module']);
	$maxWidth = 700; // used when choosing to show or not an image field
	$maxHeight = 500;
	if ($core->layout == 0 && defined('CONS_USER_RESOLUTION') && isset($_SESSION[CONS_USER_RESOLUTION])) {
		$maxWidth = explode("x",$_SESSION[CONS_USER_RESOLUTION]);
		$maxHeight = $maxWidth[1] - 400;
		$maxWidth = $maxWidth[0] - 400;	
	}
	
	// Display the module name (template should have a {t} tag set already)
	$core->template->assign("module",$module->name);
	
	// base handlers
	$hasImages = false; // if we have images, so we load shadowbox
	
	#################################### PREPARES KEYS & isADD ##############################
	$sql = $module->get_base_sql(); // Prepare SQL to fetch data >>if<< this is an EDIT based on incomming keys (if keys fail, won't even use it, but load first to fill the WHERE field)
	foreach ($module->keys as $key) {
		// for each key for this module
		if (isset($_REQUEST[$key]) && !is_array($_REQUEST[$key]) && $_REQUEST[$key] != "") {
			// that came in the request
			// prepare keys and SQL
			$sql['WHERE'][] = $module->name.".$key = \"".$_REQUEST[$key]."\"";
		} else { // a key is missing, this is most certainly not an edit
			$core->fastClose(404); // this pane does not support ADD
		}
	}
	
	#################################### SAFETY / PERMISSION ##############################
	// Can I see this item?
	if (!$core->authControl->checkPermission($module))
		$core->fastClose(403);
	
	#################################### GET DATA TO SHOW #################################
	include CONS_PATH_INCLUDE."filetypeIcon.php";
	$ntp = new CKTemplate(); // some random template just to call runContent
	$data = $module->runContent($ntp,$sql); // Get all data using the SQL we built based on incomming keys
	unset($ntp); // trash the template (free memory)
	if ($core->errorState || $data===false) {
		// not found? how? keys probable are wrong .. so toggle to 404
		$core->fastClose(404);
	}
	
	####################################### PREPARES FORM #####################################
	$objfield = $core->template->get("_FORM_field"); # how any single field shows
	$tempOutput = ""; // raw output for template

	// cache which fields have different interfaces (better than loop all plugins inside the big loop)
	$cacheCustomFields = array();
	foreach ($module->plugins as $scriptname) {
		$cacheCustomFields = array_merge($cacheCustomFields,$core->loadedPlugins[$scriptname]->customFields);
	}
	
	
	
	######################################## BUILDS FORM #####################################
	foreach ($module->fields as $name => $field) { // FOR EACH FIELD ON THIS MODULE/DATABASE ...
		$content = ""; // content blank or false means this field will not be shown
		
		// have permission to see this field?
		if ($_SESSION[CONS_SESSION_ACCESS_LEVEL] < 100 && isset($module->fields[$name][CONS_XML_RESTRICT]) && $module->fields[$name][CONS_XML_RESTRICT] > $_SESSION[CONS_SESSION_ACCESS_LEVEL]) {
			$content = false;
			continue;
		} else {
			if (in_array($name,$cacheCustomFields)) {
				$content = false;
				continue;
			}
			$fillDT = array('field' => $name);		
			if ($content == '') {		
				switch ($module->fields[$name][CONS_XML_TIPO]) { // for each fields ...
				
					case CONS_TIPO_UPLOAD: // ############################################### FILE(s)
						
						$field_upload = $core->template->get("_upload_field");
						$using = clone $field_upload;
						$emptyme=array();
						
						$tobjTemp = $core->template->get("_thumb");
						$path = CONS_FMANAGER.$module->name."/";
						$fileName = $name."_";
						foreach ($module->keys as $key)
							$fileName .= $data[$key]."_";
						$FirstfileName = $path.$fileName."1";
						$hasFile = locateAnyFile($FirstfileName,$ext);
						if (!$hasFile) {
							$content = false;
							continue;
						} else {
							$fillDT['filesize'] = humanSize(filesize($FirstfileName));
							$ext = strtolower($ext);
							$fillDT['download'] = CONS_INSTALL_ROOT.$FirstfileName;
							$fillDT['ico'] = filetypeIcon($ext);
							if (in_array($ext,array("jpg","gif","swf","png","jpeg"))) {
								$hasImages = true;
								$h = getimagesize($FirstfileName);
								$fillDT['width'] = $h[0];
								$fillDT['height'] = $h[1];
								$fillDT['dim'] = $h[0]."x".$h[1];
								if ($h[0] <$maxWidth && $h[1]<$maxHeight) {
									$emptyme[] = "_downloadable";
									if ($ext != "swf") {
										$emptyme[] = "_swf";
									} else {
										$emptyme[] = "_img";
									}
								} else
									$emptyme[] = "_presentable";
								if (isset($module->fields[$name][CONS_XML_THUMBNAILS])){
									$thumbVersions = count($module->fields[$name][CONS_XML_THUMBNAILS]);
									if ($thumbVersions > 1) {
										$tObj = clone $tobjTemp;
										$fileName = $fileName;
										$tTemp= "";
										$h = getimagesize($path."t/".$fileName."2.jpg");
										for ($tv = 2; $tv <= $thumbVersions; $tv++) {
											$tTemp .= $tObj->techo(array('tdownload'=>CONS_INSTALL_ROOT.$path."t/".$fileName.$tv.".jpg"));
											$h = getimagesize($path."t/".$fileName.$tv.".jpg");
										}
										$using->assign("_thumb",$tTemp);
									} else
										$emptyme[] = "_hasThumbs";
								} else {
								 	$emptyme[] = "_hasThumbs";
								}
							} else {
								$emptyme[] = "_isImage";
								$emptyme[] = "_presentable";
							}
						}
						$content = $using->techo($fillDT,$emptyme);
					break;
					case CONS_TIPO_LINK: // ############################################### LINK TO ANOTHER MODULE
						$mod = $core->loaded($module->fields[$name][CONS_XML_MODULE]);
						$where = $module->getRemoteKeys($mod,$data);
						$sql = "SELECT ".$mod->title." FROM ".$mod->dbname." as ".$mod->name." WHERE ".implode(" AND ",$where);

						$field_select = $core->template->get("_select_field");
						$using = clone $field_select;
						
						$fillDT['title'] = $core->dbo->fetch($sql);
						 
						$content = $using->techo($fillDT);
						
					break;
					case CONS_TIPO_TEXT: // ############################################### TEXT (textarea/cke)
						$field_txt = $core->template->get("_textarea_field");
						$using = clone $field_txt;
						$fillDT['value'] = isset($data[$name])?$data[$name]:'';
						if ($fillDT['value'] == '') {
							$content = false;
							continue;
						}						
						$content = $using->techo($fillDT);
					break;
					case CONS_TIPO_ENUM: // ############################################### LIST OF ITEMS IN ENUM FORM
						$content = $core->langOut($data[$name]);
					break;
					case CONS_TIPO_DATE: // ############################################### DATE / DATETIME
						$content = fd($data[$name],$core->intlControl->getDate());
						if ($content == '') {
							$content = false;
							continue;
						}
					break;
					case CONS_TIPO_DATETIME:
						$content = fd($data[$name],"H:i:s ".$core->intlControl->getDate());
						if ($content == '') {
							$content = false;
							continue;
						}
					break;
					case CONS_TIPO_VC: // ############################################### SIMPLE INPUT WITH HEAVY TYPESETTING
					case CONS_TIPO_INT:
					case CONS_TIPO_FLOAT:
						$field_sel = $core->template->get("_normal_field");					
						$using = clone($field_sel);
						$fillDT['value'] = isset($data[$name])?$data[$name]:'';
						
						if (isset($module->fields[$name][CONS_XML_META])) {
							if ($module->fields[$name][CONS_XML_META] == "masked" || $module->fields[$name][CONS_XML_META] == "password")
								$fillDT['value'] = "******";
						} 
						
						
						if (isset($module->fields[$name][CONS_XML_SPECIAL]) && strlen($module->fields[$name][CONS_XML_SPECIAL])>10 && substr($module->fields[$name][CONS_XML_SPECIAL],0,6) == "slider") { 
							if (preg_match("@([0-9]*)\,([0-9]*)@",$module->fields[$name][CONS_XML_SPECIAL],$ereg)) {
								unset($using);
								unset($field_sel);
								$field_sel = $core->template->get("_slider_field");
								$using = clone $field_sel;
								if (!is_numeric($fillDT['value'])) $fillDT['value'] = $ereg[1];
								$fillDT['value'] = floor(200*($fillDT['value']-$ereg[1])/($ereg[2]-$ereg[1])); 
							}
						}
						if ($fillDT['value'] == '') {
							$content = false;
							continue;
						}
						$content =  $using->techo($fillDT);
					break;
				}
			}
		} # permission to see?

		if ($content === false) continue; // if content is false, ignore alltogether
		
		// build a template object with this field and print it out
		$using = clone($objfield);
		$outdata = array('field' => $content,
						 'title' => $name
						 );
		$tempOutput .= $using->techo($outdata);
	} // END of the BIG LOOP, we finished cicling every field of this module, so simply output the result
	$core->template->assign("_FORM_field",$tempOutput);
	
	
	#################################### EXTRA JS AND TEMPLATING ####################################
	
	// if we had some image, add and prepare shadowbox
	if ($hasImages && $core->layout != 2) {
		$core->addLink('shadowbox/shadowbox.css');
		$core->addLink('shadowbox/shadowbox.js');
		$core->template->constants['HEADUSERTAGS'] .= "\n<script type=\"text/javascript\"><!--\nShadowbox.init();\n//--></script>";
	}

	####################################### CLEAN-UP #####################################
	unset($tempOutput);
	// The following were just templates we don't need anymore (used to create the fields), remove from page
	$core->template->assign("_upload_field","");
	$core->template->assign("_select_field","");
	$core->template->assign("_normal_field","");
	$core->template->assign("_textarea_field","");
	$core->template->assign("_slider_field","");
	$core->template->assign("_selecttree_field","");
