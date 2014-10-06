<?
	$core->loadDimconfig(true);

	// info
	$b = getbrowser(false);
	$core->template->assign("browser",$b[0]);
	$core->template->assign("fullbrowser",isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"");
	$core->template->assign("islegacy",$b[1]?"1":"0");
	$core->template->assign("ismobile",$b[2]?"1":"0");
	$core->template->assign("ip",CONS_IP);
	$core->template->assign("servertime",date("H:i d/m/Y"));
	$core->template->assign("system",$b[3]);
	if (!$core->loaded('stats')) $core->template->assign("_stats");

	# This page borrows a lot of code from edit.php
	$maxWidth = 750; // used when choosing to show or not an image field
	$maxHeight = 500;
	if ($core->layout == 0 && defined('CONS_USER_RESOLUTION') && isset($_SESSION[CONS_USER_RESOLUTION])) {
		$maxWidth = explode("x",$_SESSION[CONS_USER_RESOLUTION]);
		$maxHeight = $maxWidth[1] - 400;
		$maxWidth = $maxWidth[0] - 420;
	}


	if (!$core->authControl->checkPermission('bi_adm','can_options'))
		$core->fastClose(403);
	if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<$core->dimconfig['minlvltooptions'])
		$core->fastClose(403);

	$dimconfigMD = unserialize(cReadFile(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/_dimconfig.dat"));
	// fields that are NOT in the dimconfigMD are considered VC

	$hasImages = false; // if we have images, so we load shadowbox
	$hasCalendar = false; // if we have calendar popins
	$hasSlider = false; // if have a slider field
	$endScript = ""; // any javascript to be added at the end of the page, like CKE or ajaxHandler

	include CONS_PATH_INCLUDE."filetypeIcon.php"; // for files

	$objfield = $core->template->get("_FORM_field"); # how any single field shows
	$tempOutput = ""; // raw output for template

	$temp = ""; // hidden fields
	$objh = $core->template->get("_hiddenoptions"); // non editable or "hidden"

	# dimconfig
	$data = array();


	foreach ($core->dimconfig as $data['name'] => $data['value']) {

		if (is_array($data['value'])) continue; // not supported

		if (!isset($dimconfigMD[$data['name']])) {
			$fieldType = CONS_TIPO_VC;
			$dimconfigMD[$data['name']] = array(CONS_XML_TIPO => CONS_TIPO_VC);
		} else
			$fieldType = $dimconfigMD[$data['name']][CONS_XML_TIPO];

		// format dates
		if ($fieldType == CONS_TIPO_DATE && isset($data['value']))
			$data['value'] = fd($data['value'],$core->intlControl->getDate()); // format in language mode
		else if ($fieldType == CONS_TIPO_DATETIME && isset($data['value']))
			$data['value'] = fd($data['value'],"H:i:s ".$core->intlControl->getDate());  // format in language mode

		if (isset($dimconfigMD[$data['name']][CONS_XML_RESTRICT]) && $dimconfigMD[$data['name']][CONS_XML_RESTRICT]>$_SESSION[CONS_SESSION_ACCESS_LEVEL]) continue;

		if (((!isset($dimconfigMD[$data['name']][CONS_XML_READONLY]) && $data['name'][0] != "_") || $_SESSION[CONS_SESSION_ACCESS_LEVEL]==100) && strpos($data['name'],'_pluginStarter')===false) { // masters can edit it

			$fillDT = array('field' => $data['name'],
							'width' => '99%');
			switch($fieldType) {

				case CONS_TIPO_UPLOAD: // ############################################### FILE(s)

					$field_upload = $core->template->get("_upload_field");
					$using = clone $field_upload;
					$emptyme=array();

					$tobjTemp = $core->template->get("_thumb");
					$FirstfileName = CONS_FMANAGER.$dimconfigMD[$data['name']]['location'];

					$path = explode("/",$FirstfileName);
					$fileName = array_pop($path);
					$path = implode("/",$path)."/";
					$hasFile = locateAnyFile($FirstfileName,$ext);
					if (!$hasFile) {
						$emptyme[] = "_hasFile";
						if (isset($dimconfigMD[$data['name']][CONS_XML_THUMBNAILS])) {
							$hasImages = true;
							$fillDT['maxres'] = "max ".str_replace(",","x",$dimconfigMD[$data['name']][CONS_XML_THUMBNAILS][0]);
						}
					} else {
						$fillDT['filesize'] = humanSize(filesize($FirstfileName));
						$ext = strtolower($ext);
						$fillDT['download'] = CONS_INSTALL_ROOT.$FirstfileName."?r=".rand(0,999);
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
							if (isset($dimconfigMD[$data['name']][CONS_XML_THUMBNAILS])){
								$thumbVersions = count($dimconfigMD[$data['name']][CONS_XML_THUMBNAILS]);
								if ($thumbVersions > 1) {
									$hasLB = true;
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
					if (isset($dimconfigMD[$data['name']][CONS_XML_THUMBNAILS]))
						$fillDT['maxres'] = "max ".str_replace(",","x",$dimconfigMD[$data['name']][CONS_XML_THUMBNAILS][0]);
					$fillDT['maxsize'] = isset($dimconfigMD[$data['name']][CONS_XML_FILEMAXSIZE])?humanSize($dimconfigMD[$data['name']][CONS_XML_FILEMAXSIZE]):ini_get('upload_max_filesize');

					$content = $using->techo($fillDT,$emptyme);

					break;
				case CONS_TIPO_LINK: // ############################################### LINK TO ANOTHER MODULE
					$mod = $core->loaded($dimconfigMD[$data['name']][CONS_XML_MODULE]);
					if ($mod !== false) {
						$core->safety = false; // <-- in the select, we should always show every item
						$fillDT['rmodule'] = $dimconfigMD[$data['name']][CONS_XML_MODULE];

						if ($mod->options[CONS_MODULE_PARENT]) {

							$field_sel = $core->template->get("_selecttree_field");
							$using = clone $field_sel;

							$sql = $mod->get_base_sql();
							$sql['SELECT'][] = "if (".$mod->name.".".$mod->keys[0]."='".$data['value']."',1,0) as selected";

							$tree = $mod->getContents("","","","\\",$sql);
							$using->getTreeTemplate("_sdirs","_ssubdirs",$tree);

						} else {

							$field_sel = $core->template->get("_select_field");
							$using = clone $field_sel;

							$sql = $mod->get_base_sql();
							# TODO: this probably won't work on multiple keys
							$sql['SELECT'] = array($mod->name.".".$mod->keys[0]." as ids",$mod->name.".".$mod->title." as title");
							$sql['SELECT'][] = "if (".$mod->name.".".$mod->keys[0]."='".$data['value']."',1,0) as selected";
							if ($core->runContent($mod,$using,$sql,"_options")===false)
								$using->assign("_options");
						}
						$core->safety = true; // back to normal mode
					}
					$content = $using->techo($fillDT);
					break;
				case CONS_TIPO_TEXT: // ############################################### TEXT (textarea/cke)
					$field_txt = $core->template->get("_textarea_field");
					$using = clone $field_txt;
					$fillDT['value'] = $data['value'];
					$useCKE = isset($dimconfigMD[$data['name']][CONS_XML_HTML]); # CKEdit
					$content = $using->techo($fillDT);
					$endScript .= ($useCKE?"var CKE".$data['name']." = CKEDITOR.replace( '".$data['name']."' , { language : '".$_SESSION[CONS_SESSION_LANG]."'} );\n\tCKFinder.setupCKEditor( CKE".$data['name'].", '/pages/_js/ckfinder/' ) ;\n":''); # CKEdit + CKFinder
					break;
				case CONS_TIPO_ENUM: // ############################################### LIST OF ITEMS IN ENUM FORM
					preg_match("@ENUM \(([^)]*)\).*@",$dimconfigMD[$data['name']][CONS_XML_SQL],$regs);
					if (isset($dimconfigMD[$data['name']][CONS_XML_DEFAULT]) && $data['value'] == "") $data['value'] = $dimconfigMD[$data['name']][CONS_XML_DEFAULT];
					if ($data['name'] == 'bi_adm_skin') {
						$regs = array(1=>CONS_ADM_ACTIVESKINS);
					}
					$xtp = "<option value=\"{enum}\" {checked}>{enum_translated}</option>";
					$tp = new CKTemplate($core->template);
					$tp->tbreak($xtp);
					$temp = isset($dimconfigMD[$data['name']][CONS_XML_MANDATORY])?'':"<option value=''></option>";
					$enums = explode(",",$regs[1]);
					foreach ($enums as $x) {
						$x = str_replace("'","",$x);
						$db = array('enum' => $x,
									'enum_translated' => $core->langOut($x),
									'checked' => $data['value'] == $x?' selected="selected"':'');
									$temp .= $tp->techo($db);
					}
					$content =  "<select id=\"".$data['name']."\" name=\"".$data['name']."\" >".$temp."</select>";
					break;
				case CONS_TIPO_DATE: // ############################################### DATE / DATETIME
				case CONS_TIPO_DATETIME:
					$fillDT['calendar'] = "<img id='divcalendar_".$data['name']."' onclick=\"calendarHandler.showCalendar('".$data['name']."','divcalendar_".$data['name']."',-80,-8);\" src=\"".CONS_INSTALL_ROOT.CONS_PATH_PAGES."_js/calendar/gifs/dyncalendar.gif\" style=\"width:16px;height:16px;position:relative;top:3px;left:2px\" alt=\"".$core->langOut('calendar')."\"/>";
					$fillDT['width'] = "120px";
					$hasCalendar = true;
				case CONS_TIPO_VC: // ############################################### SIMPLE INPUT WITH HEAVY TYPESETTING
				case CONS_TIPO_INT:
				case CONS_TIPO_FLOAT:
					$field_sel = $core->template->get("_normal_field");
					$using = clone($field_sel);
					$fillDT['value'] = $data['value'];
					$fillDT['type'] = "text";

					if (isset($dimconfigMD[$data['name']][CONS_XML_META])) {
						if ($dimconfigMD[$data['name']][CONS_XML_META] == "masked")
							$fillDT['type'] = "password";
						if ($dimconfigMD[$data['name']][CONS_XML_META] == "password") {
							$fillDT['type'] = "password";
							if ($_SESSION[CONS_SESSION_ACCESS_LEVEL] != 100) {
								$data['value'] = "";
								$fillDT['value'] = "";
							}
						}
					}
					if (isset($dimconfigMD[$data['name']][CONS_XML_SPECIAL]) && strlen($dimconfigMD[$data['name']][CONS_XML_SPECIAL])>10 && substr($dimconfigMD[$data['name']][CONS_XML_SPECIAL],0,6) == "slider") {
						if (preg_match("@([0-9]*)\,([0-9]*)@",$dimconfigMD[$data['name']][CONS_XML_SPECIAL],$ereg)) {
							unset($using);
							unset($field_sel);
							$field_sel = $core->template->get("_slider_field");
							$using = clone $field_sel;
							$fillDT['minor'] = $ereg[1];
							$fillDT['major'] = $ereg[2];
							if (!is_numeric($fillDT['value'])) $fillDT['value'] = $ereg[1];
							$hasSlider = true;

						}
					}
					$content =  $using->techo($fillDT);
					break;
			} #switch

			$using = clone($objfield);
			$outdata = array('field' => $content,
							 'title' => $data['name']
			);

			if ($data['name'][0] == '_') // at the start
				$tempOutput .= $using->techo($outdata);
			else
				$tempOutput = $using->techo($outdata).$tempOutput; // at the end

		} else if ($data['name'] != '_forced' && strpos($data['name'],'_pluginStarter')===false) #else can't edit
			$temp .= $objh->techo($data);
	} # loop ---
	$core->template->assign("_FORM_field",$tempOutput);

	if ($hasImages) {
		$core->addLink('shadowbox/shadowbox.css');
		$core->addLink('shadowbox/shadowbox.js');
		$core->template->constants['HEADUSERTAGS'] .= "\n<script type=\"text/javascript\"><!--\nShadowbox.init();\n//--></script>";
	}
	if (strpos($endScript,'CKEDITOR.')!==false) {
		# some field loaded CKEDITOR, so add it
		$core->addLink("ckfinder/ckfinder.js",true);
		$core->addLink("ckeditor/ckeditor.js",true);
	}
	if ($hasCalendar) {
		$core->addLink("calendar/dyncalendar.css");
		$core->addLink("calendar/dyncalendar.js");
		$endScript .= "var calendarHandler = new dynCalendar('".CONS_INSTALL_ROOT.CONS_PATH_PAGES."_js/calendar/gifs/');\n";
	}
	if ($hasSlider) {
		$core->addLink("scriptaculous/slider.js");
	}
	$core->template->assign("endscript",$endScript);

	if ($_SESSION[CONS_SESSION_ACCESS_LEVEL] == 100) {
		$core->template->assign("_hiddenoptions",$temp);
		if (!CONS_ONSERVER && CONS_SITESELECTOR ) {
			$domains = unserialize(cReadFile(CONS_PATH_CACHE."domains.dat"));
			$codes = array();
			foreach ($domains as $url => $code) {
				if (!isset($codes[$code])) {
					$codes[$code] = array($url);
				} else
				$codes[$code][] = $url;
			}
			$obj = $core->template->get("_sites");
			$tempOutput = "";
			foreach ($codes as $code => $urls) {
				$tempOutput .= $obj->techo(array('code' => $code));
			}
			$core->template->assign("_sites",$tempOutput);
		} else
			$core->template->assign("_localmaster");
	} else {
		$core->template->assign("_master");
	}

	if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<$core->dimconfig['minlvltooptions'])
		$core->template->assign("_canchange");




	####################################### CLEAN-UP #####################################
	unset($tempOutput);
	// The following were just templates we don't need anymore (used to create the fields), remove from page
	$core->template->assign("_upload_field","");
	$core->template->assign("_select_field","");
	$core->template->assign("_normal_field","");
	$core->template->assign("_textarea_field","");
	$core->template->assign("_slider_field","");
	$core->template->assign("_selecttree_field","");

