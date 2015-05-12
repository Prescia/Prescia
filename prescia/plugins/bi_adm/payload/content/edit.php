<? /* EDIT PANEL TOC (you can search these strings):
 *
 * VARIABLES
 * PREPARATION
 * PREPARES KEYS & isADD
 * SAFETY / PERMISSION    (based on isADD / variables)
 * GET DATA TO SHOW    (!isADD)
 * POSTACTION
 * PREPARES FORM
 * BUILDS FORM    <--- most of this file
 * EXTRA JS AND TEMPLATING
 * RELATED MODULES
 * ENDSCRIPTS
 * CLEAN-UP
-*/

	// We use the same scripts as in the list.html to handle ajax lists, get them
	$listHTML = new CKTemplate($core->template);
	$listHTML->fetch(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/list.html");
	$core->template->assign("commonscript",$listHTML->get("_commonScripts"));
	unset($listHTML);

	######################################## VARIABLES ####################################
	// module existence already performed at action. Load it up here
	$module = $core->loaded($_REQUEST['module']);
	// tests noadminpanes
	if (in_array("edit",$module->options[CONS_MODULE_NOADMINPANES])===true) {
		$core->fastClose(403);
	}

	$p = array(); // parameters, we will need them in an array
	$p['allKeys'] = ""; // arranged in a query format, like a=a&b=b etc
	$p['hideKeys'] = array(); // keys you should not be able to change, if this is ADD will hide them, on EDIT will be readonly. Neither cases are "mandatory" (automatic)
	$p['refererKeys'] = array(); // the keys for this item, to be used on the affrefererkeys so we can return to this window if the user chooses to return to referer
	$p['hasImages'] = false; // if we have images, so we load shadowbox
	$p['hasCalendar'] = false; // if we have calendar popins
	$p['hasSlider'] = false; // if have a slider field
	$p['hasSerializedArray'] = false; // system for serialized arrays
	$p['serializedArrays'] = array(); // name of each serialized array
	$p['mfs'] = 0; // max file size
	$p['endScript'] = ""; // any javascript to be added at the end of the page, like CKE or ajaxHandler
	$p['ajaxContextHandler'] = array(); // this will be filled for ajax filter system (select inputs that fills out other selects when something is chosen)
	$p['condHandlers'] = array(); // conditionals
	$p['objfield'] = $core->template->get("_FORM_field"); # how any single field shows
	$p['tempOutput'] = ""; // raw output for template
	$p['validators'] = array('mandatory'=>array(), // autoform javascript validator system
							'translation'=>array(),
							'defaults'=>array(),
							'is_id'=>array(),
							'is_cpf'=>array(),
							'is_cnpj'=>array(),
							'integer'=>array(),
							'float'=>array(),
							'mail'=>array(),
							'date'=>array(),
							'datetime'=>array(),
							'time'=>array(),
							'login'=>array()
					   );
	$p['maxMUPupload'] = 32; // Mb, it should not be too large or the ZIP processing will hang, and since the maximum upload size is way larger, you control it here
	$p['maxReductionSize'] = 10; // images can be sent this times bigger than the max size, will be reduced automaticaly to fit
	$p['maxWidth'] = 750; // used when choosing to show or not an image field
	$p['maxHeight'] = 500;
	if ($core->layout == 0 && defined('CONS_USER_RESOLUTION') && isset($_SESSION[CONS_USER_RESOLUTION])) {
		$p['maxWidth'] = explode("x",$_SESSION[CONS_USER_RESOLUTION]);
		$p['maxHeight'] = $p['maxWidth'][1] - 400;
		$p['maxWidth'] = $p['maxWidth'][0] - 400;
	}

	// login also comes as cookie, so we must differentiate them by disabling the request/post/get if what we have is the cookie login
	if (isset($_COOKIE['login']) &&
	((isset($_POST['login']) && $_POST['login'] != $_COOKIE['login']) ||
	(isset($_GET['login']) && $_GET['login'] != $_COOKIE['login']) ||
	(!isset($_POST['login']) && !isset($_GET['login']))
	)
	) {
		unset($_REQUEST['login']);
		if (isset($_POST['login'])) $_REQUEST['login'] = $_POST['login'];
		if (isset($_GET['login'])) $_REQUEST['login'] = $_GET['login'];
	}


	######################################## PREPARATION ##################################
	// MODULE common scripts
	$core->addLink('validators.js'); // javascript used on validation forms
	$core->addLink('autoform/autoform.js'); // autoform object

	// Display the module name (template should have a {t} tag set already)
	$core->template->assign("module",$module->name);

	// conditional handlers
	$hasMup = false; // mup = multiple upload
	foreach ($module->fields as $name => $field) {
		if ($field[CONS_XML_TIPO] == CONS_TIPO_UPLOAD && isset($field[CONS_XML_THUMBNAILS]) && isset($field[CONS_XML_MANDATORY])) {
			$hasMup = true;
			break;
		}
	}
	$p['isMup'] = $hasMup && isset($_REQUEST['mup']); # mup = multiple uploads
	$p['isMultiple'] = !$p['isMup'] && isset($_REQUEST['multiSelectedIds']) && isset($core->storage['actionflag']) && $core->storage['actionflag'] == "multiedit"; # multiple edits

	#################################### PREPARES KEYS & isADD ##############################
	// Is this an ADD or EDIT action (just check if we have all the keys for this module, and this returns something ... if so, this is edit)
	$sql = $module->get_base_sql(); // Prepare SQL to fetch data >>if<< this is an EDIT based on incomming keys (if keys fail, won't even use it, but load first to fill the WHERE field)
	if (!$p['isMultiple']) {
		$p['isADD'] = false; // assume this is edit (alas just prep the variable here)
		// on add, some data might be comming already defined. Do not show them, while still considering ADD and not EDIT
		// also remember to remove autoincrement key if present (we never change these)
		foreach ($module->keys as $key) {
			// for each key for this module
			if (isset($_REQUEST[$key]) && !is_array($_REQUEST[$key]) && $_REQUEST[$key] != "") {
				// that came in the request
				// prepare keys and SQL
				$p['allKeys'] .= "&".$key."=".$_REQUEST[$key];
				$p['hideKeys'][] = $key;
				$p['refererKeys'][] = $_REQUEST[$key];
				$sql['WHERE'][] = $module->name.".$key = \"".$_REQUEST[$key]."\"";
			} else { // a key is missing, this is most certainly not an edit
				$p['isADD'] = true;
				if (strpos($module->fields[$key][CONS_XML_SQL],"AUTO_INCREMENT")!==false) {
					// the item missing is the main auto_increment key, which is always hidden
					$p['hideKeys'][] = $key; // hide auto_increment fields
				}
			}
		}
	} else {
		$p['isADD'] = false; // multiple EDIT, so behave as an EDIT
		foreach ($module->keys as $key) {
			// locate autoincrement keys so we never show them
			if (strpos($module->fields[$key][CONS_XML_SQL],"AUTO_INCREMENT")!==false) {
				$p['hideKeys'][] = $key;
			}
		}
		// items that are selected for multiple edit. Treat them, check it they exist, etc...
		$core->template->assign("multiSelectedIds",$_REQUEST['multiSelectedIds']);
		$msi_n = explode(",",$_REQUEST['multiSelectedIds']);
		$msi_nfiltered = array();
		$valid_msi = 0;
		foreach ($msi_n as $msi_nx) {
			// create a copy of the list with only VALID keys (it comes with trailing ",")
			if (trim($msi_nx) != '') {
				$valid_msi++;
				$msi_nfiltered[] = $msi_nx;
			}
		}
		if ($valid_msi == 0) {
			// no valid keys? send back to list
			$core->log[] = $core->langOut("me_select_items");
			$core->headerControl->internalFoward("list.php?module=".$module->name);
			$core->fastClose(404);
		}
		$core->template->assign("multipleCount",$valid_msi);

		// get all the id/names of the items being edited:

		# TODO: not working for multiple keys!

		$msi_nfiltered = implode(",",$msi_nfiltered);
		$sql = "SELECT ".$module->title." as title, ".$module->keys[0]." as id FROM ".$module->dbname." WHERE ".$module->keys[0]." IN ($msi_nfiltered)";
		$core->dbo->query($sql,$r,$n);
		if ($n == 0) {
			// no return? some error or the keys specified don't exist
			$core->errorControl->raise(513,$msi_nfiltered,$module->name,"multiSelectedIds=".$_REQUEST['multiSelectedIds']);
		}
		// show item names/ids being edited
		$itemlist = array();
		for ($c=0;$c<$n;$c++) {
			$item = $core->dbo->fetch_assoc($r);
			$itemlist[] = $item['title']." (".$item['id'].")";
		}
		$core->template->assign("itemlist",implode(",",$itemlist));
		// memory cleanup
		unset($item);
		unset($msi_n);
		unset($msi_nfiltered);
		unset($itemlist);
		unset($r);
	}


	#################################### SAFETY / PERMISSION ##############################
	// Can I perform this action (check permissions based on isADD)?
	if (!$core->authControl->checkPermission($module,$p['isADD']?CONS_ACTION_INCLUDE:CONS_ACTION_UPDATE)) {
		$core->fastClose(403);
	}

	#################################### GET DATA TO SHOW #################################
	// We know what we are doing (isADD) and if we can. Start up
	if (!$p['isADD'] && !$p['isMultiple']) {
		// normal (non-multiple) EDIT, get data and add keys to hidden fields
		$ntp = new CKTemplate(); // some random template just to call runContent
		$data = $module->runContent($ntp,$sql); // Get all data using the SQL we built based on incomming keys
		unset($ntp); // trash the template (free memory)
		if ($core->errorState || $data===false) {
			// not found? how? keys probable are wrong .. so toggle to 404
			$core->fastClose(404);
		}

		// includes the filetype icon translation for upload field display (used only during edits)
		include CONS_PATH_INCLUDE."filetypeIcon.php";
	} else {
		$data = $_REQUEST; // ADD or multiple, but we might have received some pre-defined data to fill in values
	}
	// Add hidden inputs for all hideKeys (keys on edit, or pre-defined values from $_REQUEST on add)
	$hidden = ""; // this will be outputed to the template
	if (!$p['isMultiple']) {
		foreach ($p['hideKeys'] as $key) {
			if (isset($data[$key])) {
				$hidden .= '<input type="hidden" name="'.$key.'" value="'.$data[$key].'"/>';
			}
		}
	}


	########################################## POSTACTION #######################################
	// postaction control and hidden finals
	if (!$p['isMultiple'] && !$p['isMup'] && isset($_REQUEST['affreferer'])) {
		// do we have a referer to this page (comming from somewhere not main menu?)
		$core->template->assign("affreferer",$_REQUEST['affreferer']);
		$hidden .= "<input type=\"hidden\" name=\"affreferer\" value=\"".$_REQUEST['affreferer']."\"/>";
		if (isset($_REQUEST['affrefererkeys'])) {
			// which keys where sent as referer?
			$core->template->assign("affrefererkeys",$_REQUEST['affrefererkeys']);
			$hidden .= "<input type=\"hidden\" name=\"affrefererkeys\" value=\"".$_REQUEST['affrefererkeys']."\"/>";
		}
	} else
		$core->template->assign("_referer");
	if ($p['isMultiple'] || $p['isMup'] || $module->options[CONS_MODULE_PUBLIC] == '') // does this module have a public page? if not, remove from template the link
		$core->template->assign("_public");
	else {
		$url = new CKTemplate($core->template);
		$url->tbreak($module->options[CONS_MODULE_PUBLIC]);
		$url = $url->techo($data);
		$url = CONS_INSTALL_ROOT.$url;
		$core->template->assign("publicpage",$url);
		unset($url);
	}
	$core->template->assign("hidden",$hidden); // done on postaction, hidden fields and referers
	unset($hidden);
	$core->template->assign($p['isMultiple']?"_nmultiple":"_multiple",""); // remove _multiple or _nmultiple according to isMultple

	####################################### PREPARES FORM #####################################
	// Prepare form with basic data
  	$core->template->assign("allkeys",$p['allKeys']); // pre-defined keys in key=x&key=x... format (used mostly on DELETE button)
  	if ($p['isADD']) $core->template->assign("_edit");
  	else $core->template->assign("_add");
  	if (!$p['isMultiple'] && !$p['isMup'])
  		$core->template->assign("vaction",$p['isADD']?"add":"edit");
  	else if ($p['isMultiple'])
  		$core->template->assign("vaction","multiple");
  	else
  		$core->template->assign("vaction","mup");
	// warning?
	if (isset($module->options['warning'])) {
		$core->template->assign("warning",$module->options['warning']);
	} else
		$core->template->assign("_warning");

	// cache which fields have different interfaces (better than loop all plugins inside the big loop)
	$p['cacheCustomFields'] = array();
	foreach ($module->plugins as $scriptname) {
		$p['cacheCustomFields'] = array_merge($p['cacheCustomFields'],$core->loadedPlugins[$scriptname]->customFields);
	}


	######################################## BUILDS FORM #####################################
	// this function will be called in the loop, and recursivelly on serialized content

	function fillField(&$core,&$module,$name,&$field,&$data,&$p,$isSerialized=false,$basename="") {
		$content = "";
		// load l10n for datetimes
		if (!$p['isADD'] && ($field[CONS_XML_TIPO] == CONS_TIPO_DATE) && isset($data[$name]))
			$data[$name] = fd($data[$name],$core->intlControl->getDate()); // format in language mode
		else if (!$p['isADD'] && ($field[CONS_XML_TIPO] == CONS_TIPO_DATETIME) && isset($data[$name]))
			$data[$name] = fd($data[$name],"H:i:s ".$core->intlControl->getDate());  // format in language mode

		// pre-fill option arrays
		if (!$p['isADD'] && ($field[CONS_XML_TIPO] == CONS_TIPO_OPTIONS) && isset($data[$name])) {
			if (isset($data[$name])) {
				$l = strlen($data[$name]);
				for ($c=0;$c<$l;$c++) {
					$data[$name.$c] = (isset($data[$name.$c]) || $data[$name][$c] == "1");
				}
				unset($l);
			}
		}

		// If we are adding, check default values
		if ($p['isADD']) {
			if (strpos($field[CONS_XML_SQL],"AUTO_INCREMENT")!==false)
				return; // do not put autoincrement keys on add
			if (isset($field[CONS_XML_DEFAULT]) && !isset($data[$name])) {
				if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK && $field[CONS_XML_DEFAULT] == "%UID%" && defined("CONS_AUTH_USERMODULE") && $field[CONS_XML_MODULE] == CONS_AUTH_USERMODULE && $_SESSION[CONS_SESSION_ACCESS_LEVEL]>0 && isset($_SESSION[CONS_SESSION_ACCESS_USER]['id']))
					$data[$name] = $_SESSION[CONS_SESSION_ACCESS_USER]['id'];
				else if ($field[CONS_XML_TIPO] == CONS_TIPO_DATE)
					$data[$name] = fd($field[CONS_XML_DEFAULT],$core->intlControl->getDate());
				else
					$data[$name] = $field[CONS_XML_DEFAULT];
			} else if (isset($field[CONS_XML_TIMESTAMP]) || isset($field[CONS_XML_UPDATESTAMP])) {
				if ($field[CONS_XML_TIPO] == CONS_TIPO_DATE)
					$data[$name] = date($core->intlControl->getDate());
				else
					$data[$name] = date("H:i:s ".$core->intlControl->getDate());
			}
		} else if (isset($field[CONS_XML_UPDATESTAMP])) {
			if ($field[CONS_XML_TIPO] == CONS_TIPO_DATE)
				$data[$name] = date($core->intlControl->getDate());
			else
				$data[$name] = date("H:i:s ".$core->intlControl->getDate());
		}

		// ajax exceptions?
		if ($core->layout == 2 && $field[CONS_XML_TIPO] == CONS_TIPO_UPLOAD) { // ajax mode does not accept uploads
			return;
		}

		// have permission to see this field?
		if ($_SESSION[CONS_SESSION_ACCESS_LEVEL] < 100 && isset($field[CONS_XML_RESTRICT]) && $field[CONS_XML_RESTRICT] > $_SESSION[CONS_SESSION_ACCESS_LEVEL]) {
			return;
		} else {

			// Does this field have a different interface handler? if so use it and continue to the next
			if (in_array($name,$p['cacheCustomFields'])) {
				// detect which plugin has the customHandler and use it. Use the first and leave, if there is more than one, ignore the conflict
				foreach ($module->plugins as $scriptname) { // if we didn't have the cache, this would run always, see?
					if (in_array($name,$core->loadedPlugins[$scriptname]->customFields)) {
						// ok, handle it (if it returns TRUE, ignore this handler)
						$content = $core->loadedPlugins[$scriptname]->field_interface($name,$p['isADD'],$data);
						if ($content === false) return;
						else if ($content !== true) {
							break; // we will break this foreach plugin search, but continue normally the rest
						}
						$content = ""; // if we didn't continue (use the field or ignore), proceed as if this field was normal, thus erase the custom content
					}
				}
			}

			// This field is read-only?
			if (in_array($name,$p['hideKeys']) || isset($field[CONS_XML_READONLY])) {
				if ($p['isADD']) {
					return;
				} else if (!$p['isMultiple']) {
					$content = isset($data[$name])?$data[$name]:' ';
				} else {
					return;
				}
			}
			$fillDT = array('field' => $name,
							'isADD' => $p['isADD']?"true":"false",
							'affreferer' => $module->name,
							'affrefererkeys' => implode("_",$p['refererKeys']),
							'width' => '99%',
							'helper' => ''
			);
			if ($content == '') {
				// format according to type
				$helper = $core->langOut('helper_'.$module->name."_".$name);
				if ($helper != 'helper_'.$module->name."_".$name) $fillDT['helper'] = $helper;

				switch ($field[CONS_XML_TIPO]) { // for each fields ...

					case CONS_TIPO_UPLOAD: // ############################################### FILE(s)
						if ($p['isMultiple'] || $p['isMup']) {
							$content = false;
							continue; // continue will leave only the switch, since switch is considered a loop (??? WHY PHP, WHY ???)
						}

						$field_upload = $core->template->get("_upload_field");
						$using = clone $field_upload;
						$emptyme=array();
						if (!$p['isADD']) {
							$tobjTemp = $core->template->get("_thumb");
							$path = CONS_FMANAGER.$module->name."/";
							$fileName = $name."_";
							foreach ($module->keys as $key)
								$fileName .= $data[$key]."_";
							$FirstfileName = $path.$fileName."1";
							$hasFile = locateAnyFile($FirstfileName,$ext);
							if (!$hasFile) {
								$emptyme[] = "_hasFile";
								if (isset($module->fields[$name][CONS_XML_THUMBNAILS])) {
									$p['hasImages'] = true;
									$fillDT['maxres'] = "max ".str_replace(",","x",$module->fields[$name][CONS_XML_THUMBNAILS][0]);
								}
							} else {
								$fillDT['filesize'] = humanSize(filesize($FirstfileName));
								$ext = strtolower($ext);
								$fillDT['download'] = CONS_INSTALL_ROOT.$FirstfileName."?r=".rand(0,9990);
								$fillDT['ico'] = filetypeIcon($ext);
								if (in_array($ext,array("jpg","gif","swf","png","jpeg"))) {
									$p['hasImages'] = true;
									$h = getimagesize($FirstfileName);
									$fillDT['width'] = $h[0];
									$fillDT['height'] = $h[1];
									$fillDT['dim'] = $h[0]."x".$h[1];
									if ($h[0] <$p['maxWidth'] && $h[1]< $p['maxHeight']) {
										$emptyme[] = "_downloadable";
										if ($ext != "swf") {
											$emptyme[] = "_swf";
										} else {
											$emptyme[] = "_img";
										}
									} else
										$emptyme[] = "_presentable";
									if (isset($field[CONS_XML_THUMBNAILS])){
										$thumbVersions = count($field[CONS_XML_THUMBNAILS]);
										if ($thumbVersions > 1) {
											$tObj = clone $tobjTemp;
											$tTemp= "";
											for ($tv = 2; $tv <= $thumbVersions; $tv++) {
												$thumbFile = $path."t/".$fileName.$tv;
												locateFile($thumbFile,$ext);
												$h = getimagesize($thumbFile);
												$tTemp .= $tObj->techo(array('tdownload'=>CONS_INSTALL_ROOT.$thumbFile));
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
						} else {
							$emptyme[] = "_hasFile";
						}
						if (isset($field[CONS_XML_THUMBNAILS]))
							$fillDT['maxres'] = "max ".str_replace(",","x",$field[CONS_XML_THUMBNAILS][0]);
						if (isset($field[CONS_XML_FILETYPES]))
							$fillDT['exts'] = "(".$field[CONS_XML_FILETYPES].")";
						$fillDT['maxsize'] = isset($field[CONS_XML_FILEMAXSIZE])?humanSize($field[CONS_XML_FILEMAXSIZE]):ini_get('upload_max_filesize');
						if (isset($field[CONS_XML_FILEMAXSIZE]) && $field[CONS_XML_FILEMAXSIZE]>$p['mfs']) $p['mfs'] = $field[CONS_XML_FILEMAXSIZE];

						$content = $using->techo($fillDT,$emptyme);
						unset($emptyme);
					break;
					case CONS_TIPO_LINK: // ############################################### LINK TO ANOTHER MODULE
						$mod = $core->loaded($field[CONS_XML_MODULE]);
						if ($mod !== false) {
							$core->safety = false; // <-- in the select, we should always show every item
							$fillDT['rmodule'] = $field[CONS_XML_MODULE];

							if ($mod->options[CONS_MODULE_PARENT]) {

								$field_sel = $core->template->get("_selecttree_field");

								$using = clone $field_sel;

								$sql = $mod->get_base_sql();
								if (isset($data[$name]))
									$sql['SELECT'][] = "if (".$mod->name.".".$mod->keys[0]."='".$data[$name]."',1,0) as selected";
								$sql['SELECT'][] = $mod->name.".".$mod->title." as treetitle";

								$tree = $mod->getContents("","treetitle","","\\",$sql);
								$using->getTreeTemplate("_sdirs","_ssubdirs",$tree);

							} else {

								$field_sel = $core->template->get("_select_field");
								$using = clone $field_sel;

								// checks if this field is/can be filtered by another, if can, leave empty on ADD
								$canBeFilteredBy = array();
								if (isset($field[CONS_XML_FILTEREDBY])) {
									$canBeFilteredBy = $field[CONS_XML_FILTEREDBY]; // already a list of local fields
									$using->assign('helper', $core->langOut("filtered_by").": ".implode(",",$canBeFilteredBy));
									$havePreqs = true;
									// either on add or edit, field that filter this could be present ... check them!
									for ($cbf=0;$cbf<count($canBeFilteredBy);$cbf++)
										if (!isset($data[$canBeFilteredBy[$cbf]]) || $data[$canBeFilteredBy[$cbf]] == '' || $data[$canBeFilteredBy[$cbf]] == '0') {
											$havePreqs = false;
											break;
										}

									if (!$havePreqs) { // we can't fill it, so display the select_other_field message
										$using->assign("_optional","");
										$canBeFilteredBy_translated = array();
										for ($cbf=0;$cbf<count($canBeFilteredBy);$cbf++)
											$canBeFilteredBy_translated[$cbf] = $core->langOut($canBeFilteredBy[$cbf]);
										$using->assign("_options","<option value=\"\">".$core->langOut("select_other_field").": ".implode(", ",$canBeFilteredBy_translated)."</option>");
									} else  { // we can fill this since all prerequisites are present!
										$sql = $mod->get_base_sql();
										$sql['SELECT'] = array($mod->name.".".$mod->keys[0]." as ids",$mod->name.".".$mod->title." as title");
										if (isset($data[$name]))
											$sql['SELECT'][] = "if (".$mod->name.".".$mod->keys[0]."='".$data[$name]."',1,0) as selected";
										// add filters
										foreach ($canBeFilteredBy as $filterfield) { // we know the data exists because this is an edit, but it could be empty
											if ($data[$filterfield] != '') {
												$remodeField = $mod->get_key_from($module->fields[$filterfield][CONS_XML_MODULE]);
												$sql['WHERE'][] = $mod->name.".".$remodeField."=\"".$data[$filterfield]."\"";
											}
										}
										if ($core->runContent($mod,$using,$sql,"_options")===false)
											$using->assign("_options");
									}
									// add the corresponding data for the ajaxContextHandler
									$p['ajaxContextHandler'][$name] = $canBeFilteredBy;
								} else {
									$sql = $mod->get_base_sql();

									# TODO: this probably won't work on multiple keys

									$sql['SELECT'] = array($mod->name.".".$mod->keys[0]." as ids",$mod->name.".".$mod->title." as title");
									if (isset($data[$name]))
										$sql['SELECT'][] = "if (".$mod->name.".".$mod->keys[0]."='".$data[$name]."',1,0) as selected";
									//print_r($sql);
									//die();
									if ($core->runContent($mod,$using,$sql,"_options")===false)
										$using->assign("_options");
								}
							}
							$content = $using->techo($fillDT);
							$core->safety = true; // back to normal mode
							unset($using);
						}
					break;
					case CONS_TIPO_TEXT: // ############################################### TEXT (textarea/cke)
						$field_txt = $core->template->get("_textarea_field");
						$using = clone $field_txt;
						$fillDT['value'] = isset($data[$name])?$data[$name]:'';
						if (isset($field[CONS_XML_HTML]))
							$fillDT['value'] = htmlspecialchars($fillDT['value']); // ckedit will remove entities, so we add an extra layer!
						$useCKE = isset($field[CONS_XML_HTML]); # CKEdit
						$content = $using->techo($fillDT);
						$p['endScript'] .= ($useCKE?"var CKE$name = CKEDITOR.replace( '$name' , { language : '".$_SESSION[CONS_SESSION_LANG]."'".(isset($module->fields[$name][CONS_XML_SIMPLEEDITFORCE])?",toolbar : 'MiniToolbar'":"")."} );\n\tCKFinder.setupCKEditor( CKE$name, '/pages/_js/ckfinder/' ) ;\n":''); # CKEdit + CKFinder
						unset($using);
					break;
					case CONS_TIPO_ENUM: // ############################################### LIST OF ITEMS IN ENUM FORM
						preg_match("@ENUM \(([^)]*)\).*@",$field[CONS_XML_SQL],$regs);
						if ($p['isADD'] && isset($field[CONS_XML_DEFAULT]) && (!isset($data[$name]) || $data[$name] == "")) $data[$name] = $module->fields[$name][CONS_XML_DEFAULT];
						$xtp = "<option value=\"{enum}\" {checked}>{enum_translated}</option>";
			    		$tp = new CKTemplate($core->template);
			    		$tp->tbreak($xtp);
			    		$temp = isset($field[CONS_XML_MANDATORY])?'':"<option value=''></option>";
						$enums = explode(",",$regs[1]);
						foreach ($enums as $x) {
							$x = str_replace("'","",$x);
							$db = array('enum' => $x,
										'enum_translated' => $core->langOut($x),
										'checked' => isset($data[$name]) && $data[$name] == $x?' selected="selected"':'');
							$temp .= $tp->techo($db);
						}
						$content =  "<select ".($p['isMultiple']?"onchange=\"$('me_edit_".$name."').checked = true;\"":"onchange=\"checkConditions();\"")." id=\"$name\" name=\"$name\" >".$temp."</select>";
						unset($temp);
						unset($enums);
					break;
					case CONS_TIPO_OPTIONS: // ############################################# CHECKBOX LIST
						$xtp = "<input type=\"checkbox\" onclick=\"checkopts('{field}');\" name=\"{name}\" id=\"{name}\" {checked}/><label for=\"{name}\">{translated}</label><br/>";
						$tp = new CKTemplate($core->template);
						$tp->tbreak($xtp);
						$citem = 0;
						$temp = "<input type='hidden' name='$name' id='$name' value=\"".(isset($data[$name])?$data[$name]:"")."\"/>";
						foreach ($field[CONS_XML_OPTIONS] as $opt) {
							$db = array('name' => $name."_".$citem,
										'field' => $name,
										'translated' => $core->langOut(str_replace("'","",$opt)),
									    'checked' => isset($data[$name]) && strlen($data[$name])>=$citem && $data[$name][$citem] == 1 ? ' checked="checked"':'');
							$temp .= $tp->techo($db);
							$citem++;
						}
						$content = $temp;
						unset($temp);
					break;
					case CONS_TIPO_DATE: // ############################################### DATE / DATETIME
					case CONS_TIPO_DATETIME:
						// updatestamp & includestap already treated befpre switch
						if ($core->layout != 2)
							$fillDT['calendar'] = "<img id='divcalendar_".$name."' onclick=\"calendarHandler.showCalendar('".$name."','divcalendar_".$name."',-80,-8);\" src=\"".CONS_INSTALL_ROOT.CONS_PATH_PAGES."_js/calendar/gifs/dyncalendar.gif\" style=\"width:16px;height:16px;position:relative;top:3px;left:2px\" alt=\"".$core->langOut('calendar')."\"/>";
						$fillDT['width'] = "120px";
						$p['hasCalendar'] = true;
					case CONS_TIPO_VC: // ############################################### SIMPLE INPUT WITH HEAVY TYPESETTING
					case CONS_TIPO_INT:
					case CONS_TIPO_FLOAT:
						$field_sel = $core->template->get("_normal_field");
						$using = clone($field_sel);
						$fillDT['value'] = isset($data[$name])?$data[$name]:'';
						$fillDT['type'] = "text";

						if (isset($field[CONS_XML_META])) {
							if ($field[CONS_XML_META] == "masked")
								$fillDT['type'] = "password";
							if ($field[CONS_XML_META] == "password") {
								$fillDT['type'] = "password";
								if ($_SESSION[CONS_SESSION_ACCESS_LEVEL] != 100) {
									$data[$name] = "";
									$fillDT['value'] = "";
								}
							}
						}

						if ($field[CONS_XML_TIPO] == CONS_TIPO_INT) {
							if ($fillDT['helper'] == '') $fillDT['helper'] = $core->langOut('helper_integer');
							$p['validators']['integer'][] = "'$name'";
						} else if ($field[CONS_XML_TIPO] == CONS_TIPO_FLOAT) {
							if ($fillDT['helper'] == '') $fillDT['helper'] = $core->langOut('helper_float');
							$p['validators']['float'][] = "'$name'";
						} else if ($field[CONS_XML_TIPO] == CONS_TIPO_DATE) {
							$p['validators']['date'][] = "'$name'";
							if ($fillDT['helper'] == '') $fillDT['helper'] = '('.$core->intlControl->getDate().')';
						} else if ($field[CONS_XML_TIPO] == CONS_TIPO_DATETIME) {
							if ($fillDT['helper'] == '') $fillDT['helper'] = $core->langOut('helper_time').' '.$core->intlControl->getDate().')';
							$p['validators']['datetime'][] = "'$name'";
						}
						if (isset($field[CONS_XML_SPECIAL])) {
							switch ($field[CONS_XML_SPECIAL]) {
								case 'login':
									if ($fillDT['helper'] == '') $fillDT['helper'] = $core->langOut('helper_login');
									$p['validators']['login'][] = "'$name'";
								break;
								case 'mail':
									if ($fillDT['helper'] == '') $fillDT['helper'] = '(ex: login@servidor.com)';
									$p['validators']['mail'][] = "'$name'";
								break;
								case 'number':
									if ($fillDT['helper'] == '') $fillDT['helper'] = $core->langOut('helper_integer');
									$p['validators']['integer'][] = "'$name'";
								break;
								case 'float':
									if ($fillDT['helper'] == '') $fillDT['helper'] = $core->langOut('helper_float');
									$p['validators']['float'][] = "'$name'";
								break;
								case 'cpf':
									if ($fillDT['helper'] == '') $fillDT['helper'] = $core->langOut('helper_cpf');
									$p['validators']['is_cpf'][] = "'$name'";
								break;
								case 'cnpj':
									if ($fillDT['helper'] == '') $fillDT['helper'] = $core->langOut('helper_cnpj');
									$p['validators']['is_cnpj'][] = "'$name'";
								break;
								case 'id':
									if ($fillDT['helper'] == '') $fillDT['helper'] = $core->langOut('helper_id');
									$p['validators']['is_id'][] = "'$name'";
								break;
								case 'date':
									if ($fillDT['helper'] == '') $fillDT['helper'] = $core->intlControl->getDate();
									$p['validators']['date'][] = "'$name'";
								break;
								case 'datetime':
									if ($fillDT['helper'] == '') $fillDT['helper'] = $core->langOut('helper_time')." ".$core->langOut('helper_followedby')." ".$core->intlControl->getDate().')';
									$p['validators']['datetime'][] = "'$name'";
								break;
								break;
								case 'onlinevideo':
									if ($fillDT['helper'] == '') $fillDT['helper'] = $core->langOut('helper_video');
								break;
								case 'time':
									if ($fillDT['helper'] == '') $fillDT['helper'] = $core->langOut('helper_time');
									$p['validators']['time'][] = "'$name'";
								break;
								default:
									if (strlen($field[CONS_XML_SPECIAL])>10 && substr($field[CONS_XML_SPECIAL],0,6) == "slider") {
										if (preg_match("@([0-9]*)\,([0-9]*)@",$field[CONS_XML_SPECIAL],$ereg)) {
											unset($using);
											unset($field_sel);
											$field_sel = $core->template->get("_slider_field");

											$using = clone $field_sel;
											$fillDT['minor'] = $ereg[1];
											$fillDT['major'] = $ereg[2];
											if (!is_numeric($fillDT['value'])) $fillDT['value'] = $ereg[1];
											$p['hasSlider'] = true;
											if ($fillDT['helper'] == '') $fillDT['helper'] = $ereg[1]." - ".$ereg[2];
										}

									}

								break;
							}
						}
						$content = $using->techo($fillDT);
					break;
					case CONS_TIPO_ARRAY: # data is an array
						$p['hasSerializedArray'] = true;
						$p['serializedArrays'][] = '"'.$name.'"';
						$p['endScript'] .= "CScontroler.fillData('$name',".JSON_encode($field[CONS_XML_OPTIONS]).",".JSON_encode(isset($data[$name])?$data[$name]:'').");\n";
						$field_ser = $core->template->get("_serializearray_field");
						$using = clone($field_ser);
						$content = $using->techo($fillDT);
						unset($using);
					break;
				} # switch

				if (isset($field['conditional']) && strpos($field['conditional'],"=")!==false) {
					$temp = explode("=",$field['conditional']);
					$ltemp = trim(strtolower($temp[0]));
					$negation = strpos($ltemp,"!")!==false;
					if ($negation) $ltemp = trim(str_replace("!","",$ltemp));
					$rtemp = str_replace("'","",trim($temp[1]));
					$p['condHandlers'][] = "$('tableitem".$name."').style.display = $('$ltemp').value ".($negation?"!=":"==")."'$rtemp' ? '' : 'none';";
				}
			} # content not blank
		} # permission to see?

		if ($content === false) return ''; // if content is false, ignore alltogether

		// build a template object with this field and print it out
		$using = clone($p['objfield']);
		$outdata = array('field' => $content,
						 'title' => $isSerialized?substr($name,strlen($basename)+1):$name,
						 'mandatory' => (in_array($name,$module->keys) || (isset($field[CONS_XML_MANDATORY]) && $field[CONS_XML_MANDATORY]))?"y":"n");
		if (!$p['isMultiple'] && (!$p['isMup'] || $name != $module->title) && $outdata['mandatory']=='y' && !in_array($name,$p['hideKeys']) && $field[CONS_XML_TIPO] != CONS_TIPO_UPLOAD && !isset($field[CONS_XML_READONLY])) {
			// EXCEPTION: a ignorenedit field CAN be blank during EDIT only
			if (!isset($field[CONS_XML_IGNORENEDIT]) || $p['isADD']) {
				$p['$validators']['mandatory'][] = "'$name'";
				$p['$validators']['translation'][] = "'".$core->langOut($name)."'";
				$p['$validators']['defaults'][] =  (isset($field[CONS_XML_DEFAULT])?"'".$field[CONS_XML_DEFAULT]."'":"''");
			}
		}
		$p['tempOutput'] .= $using->techo($outdata);
		unset($using);

	} # end fillField

	// here we go!
	foreach ($module->fields as $name => $field) { // FOR EACH FIELD ON THIS MODULE/DATABASE ...
		if ($field[CONS_XML_TIPO] == CONS_TIPO_SERIALIZED) {
			if (isset($data[$name]) && $data[$name] != '') {
				$data[$name] = @unserialize($data[$name]);
				if ($data[$name] === false) {
					unset($data[$name]);
					$core->errorControl->raise(188,$name,$module->name);
				}
			}
			foreach ($field[CONS_XML_SERIALIZEDMODEL] as $exname => $exfield) {
				fillField($core,$module,$name."_".$exname,$exfield,$data,$p,true,$name);
			}
		} else
			fillField($core,$module,$name,$field,$data,$p);
	}

	#################################### EXTRA JS AND TEMPLATING ####################################

	// if we had some image, add and prepare shadowbox
	if ($p['hasImages']) {
		$core->addScript('shadowbox',array('handleOversize' => '"resize"'));
	}

	// max file size
	if ($p['isMup'])
		$p['mfs'] = 1048576*$p['maxMUPupload'];
	else {
		$phpmfs = ini_get('upload_max_filesize'); // detect hard limit
		if (strpos($phpmfs,"M")!==false) $phpmfs = substr($phpmfs,0,-1)*1048576;
		else if (strpos($phpmfs,"K")!==false) $phpmfs = substr($phpmfs,0,-1)*1024;
		else if (strpos($phpmfs,"G")!==false) $phpmfs = substr($phpmfs,0,-1)*1073741824;

		if ($p['mfs']>0) $p['mfs'] *=$p['maxReductionSize']; // allows automatic reduction up to X times
		else $p['mfs'] = $phpmfs; // no automatic reduction, use raw phpmfs
		if ($p['mfs']>$phpmfs) $p['mfs'] = $phpmfs; // automatic reduction control above might have made mfs higher than hard limit
	}
	$core->template->assign("MFS",$p['mfs']);

	// Mup/Multiple
	if (!$p['isMup']) {
		if (!$hasMup) $core->template->assign("_mup");
		$core->template->assign("_mup_field");
	} else {
		$core->template->assign("maxsize",HumanSize($p['mfs']));
		$core->template->assign("_mup");
		$core->template->assign("_nmup");
	}

	// Validators on AutoForm fields
	foreach ($p['validators'] as $tag => $fields) {
		$core->template->assign("af_".$tag,implode(",",$fields));
	}
	$core->template->assign("af_datepattern",$core->intlControl->getDatePreg());

	// if we had CKEDITOR being loaded, add ckeditor on the javascript links:
	if (strpos($p['endScript'],'CKEDITOR.')!==false) { # some field loaded CKEDITOR, so add it
		$core->addLink("ckfinder/ckfinder.js",true);
		$core->addLink("ckeditor/ckeditor.js",true);
	}

	// if we had a calendar, add js
	if ($p['hasCalendar']) {
		$core->addLink("calendar/dyncalendar.css");
		$core->addLink("calendar/dyncalendar.js");
		$p['endScript'] .= "\tvar calendarHandler = new dynCalendar('".CONS_INSTALL_ROOT.CONS_PATH_PAGES."_js/calendar/gifs/');\n";
	}

	// if we had a slider, add js
	if ($p['hasSlider']) {
		$core->addLink("scriptaculous/slider.js");
	}

	// serialized arrays?
	if ($p['hasSerializedArray']) {
		$core->template->assign("serialized_arrays",implode(",",$p['serializedArrays']));

	}
	// conditions?
	$core->template->assign("conditioncheck",implode("\n",$p['condHandlers']));
	if (count($p['condHandlers'])!=0) $p['endScript'] .= "\tcheckConditions();\n";

	// if we have ajax handler, build the proper javascript for them at the end of the endScript
	$p['endScript'] .= "\tvar ajaxHandlers = new Array();\n"; // the array should exist to prevent JS errors
	if (count($p['ajaxContextHandler'])>0) { // we have ajaxHandlers
		foreach ($p['ajaxContextHandler'] as $field => $childfields) {
			// each ajaxHandler will have: MODULE (which will be filled), FIELD (in this module/form), PRE-REQUISITE FIELDS...
			$p['endScript'] .= "var tmp = new Array();\n";
			$p['endScript'] .= "tmp.push('".$module->fields[$field][CONS_XML_MODULE]."');\n";
			$p['endScript'] .= "tmp.push('$field');\n";
			foreach ($childfields as $cf) {
				$p['endScript'] .= "tmp.push('$cf');\n";
			}
			$p['endScript'] .= "ajaxHandlers.push(tmp);\n";
		}
	}

	##################################### RELATED MODULES #####################################
	if (CONS_BROWSER_ISMOB && !isset($_SESSION['NOMOBVER'])) {
		$core->template->assign("_FORM_field",$p['tempOutput']); // no merged in this
		$core->template->assign("_relatedmodules");
	} else {
		$rm = 0;
		$addedAsRelate = array();
		if (!$p['isADD'] && !$p['isMultiple'] && !$p['isMup']) { # TODO: work with multiple keys or filtered keys
			$rmt = $core->template->get("_rm");
			$temp = '';
			foreach ($core->modules as $name => &$rmodule) {
				// is it a valid module? do we have permission to see it?
				if (!$rmodule->options[CONS_MODULE_SYSTEM] && $core->authControl->checkPermission($rmodule)) {
					foreach ($rmodule->fields as $fname => &$field) {
						if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK && $field[CONS_XML_MODULE] == $module->name) {
							if (in_array($name,$addedAsRelate) && $rmodule->linker) break; // add only once linker modules (A<->A relations)
							$rm++;
							
							$output = array('module'=>$name,
											'referer' => 'affreferer='.$module->name."&affrefererkeys=".$data[$module->keys[0]],
											'keys'=>$fname."=".$data[$module->keys[0]]);

							if (isset($field[CONS_XML_FILTEREDBY]) && $field[CONS_XML_FILTEREDBY] != '') {
								foreach ($field[CONS_XML_FILTEREDBY] as $fbname) {
									if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK)
										$mykey = $module->get_key_from($rmodule->fields[$fbname][CONS_XML_MODULE]);
									else 
										$mykey = $fbname;
									if (isset($data[$mykey]))
										$output['keys'] .= "&".$fbname."=".$data[$mykey];
									
								
								}
							} 

							if (in_array($name,$module->options[CONS_MODULE_MERGE])) { // fill inside of MERGE
								$content = "";
								if (in_array("merged_".$name,$p['cacheCustomFields'])) { // CUSTOM MERGE items after list?
									$customCT = "";
									// detect which plugin has the customHandler and use it. Use the first and leave, if there is more than one, ignore the conflict
									foreach ($module->plugins as $scriptname) { // if we didn't have the cache, this would run always, see?
										if (in_array("merged_".$name,$core->loadedPlugins[$scriptname]->customFields)) {
											// there is more to come in this merged
											$content = $core->loadedPlugins[$scriptname]->field_interface("merged_".$name,false,$data);
											if ($content !== false && $content !== true) $customCT .= $content;
										}
									}
								}

								$outdata = array('field' => $rmt->techo($output,array("_merged")).$content,
											'title' => $name,
											'mandatory' => "n");
								$using = clone($p['objfield']);
								$p['tempOutput'] .= $using->techo($outdata);
								unset($using);
								$p['endScript'] .= "toggleRM('$name','".$fname."=".$data[$module->keys[0]]."&".$output['referer']."');\n";
							} else // fill at the end of the script
								$temp .= $rmt->techo($output);
							$addedAsRelate[] = $name;
						}
					}
				}
			}
			if ($temp != '') // merged items might have eaten this away
				$core->template->assign("_rm",$temp);
			else
				$core->template->assign("_relatedmodules");

		}
		$core->template->assign("_FORM_field",$p['tempOutput']); // no merged in this
		if ($rm==0) $core->template->assign("_relatedmodules");
	}

	####################################### ENDSCRIPTS #####################################

	$core->template->assign("endscripts",$p['endScript']);

	####################################### CLEAN-UP ######################################
	unset($p);
	// The following were just templates we don't need anymore (used to create the fields), remove from page
	$core->template->assign("_upload_field","");
	$core->template->assign("_select_field","");
	$core->template->assign("_normal_field","");
	$core->template->assign("_textarea_field","");
	$core->template->assign("_slider_field","");
	$core->template->assign("_selecttree_field","");
	$core->template->assign("_serializearray_field","");
	####################################### -------- #######################################

