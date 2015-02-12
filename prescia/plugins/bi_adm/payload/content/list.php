<? /* EDIT PANEL TOC (you can search these strings):
 * CONSTANTS AND VARIABLES
 * CALLBACK
 * USER PREFERENCES
-*/


	############################ CONSTANTS AND VARIABLES #####################

	if (CONS_BROWSER_ISMOB && !isset($_SESSION['NOMOBVER'])) // mob version does not have ajaxeditor
		define("CONS_LIST_AJAXEDITOR",false); //  ajax editor on ENUM fields
	else
		define("CONS_LIST_AJAXEDITOR",true); //  ajax editor on ENUM fields

	define("CONS_MAX_RELATESIZE",1000); //  maximum size of popup lists


	$notitle = isset($_REQUEST['notitle']) && $_REQUEST['notitle'] == '1'; // will fill list title (notitle if we are just paging)
	$embeded = isset($_REQUEST['embeded']) && $_REQUEST['embeded'] == '1'; // embedding this in a div, at another page, so we need the form
	$nocheckbox = isset($_REQUEST['nocheckboxes']) && $_REQUEST['nocheckboxes'] == '1'; // do not show checkboxes (will also disable on embed)
	if ($embeded) $nocheckbox = true;
	$nopaging = isset($_REQUEST['nopaging']) && $_REQUEST['nopaging'] == '1'; // don't bother with paging/actions
	$skeys = "";

	$module = $core->loaded($_REQUEST['module']);
	$endScript = ""; // any javascript to be added at the end of the page, usually ajaxHandlers

	// test noadminpanes
	if (in_array("list",$module->options[CONS_MODULE_NOADMINPANES])===true) {
		$core->fastClose(403);
	}

	$maxColumns = ($core->layout > 0)?6:8;
	$listMarginOffset = 300; // how many pixels are NOT in the list width (menu + margins + icons)

	// control's maximum size
	if ($core->layout == 0 && defined('CONS_USER_RESOLUTION') && isset($_SESSION[CONS_USER_RESOLUTION]) && !isset($_REQUEST['cellwidth'])) {
		$maxcellwidth = explode("x",$_SESSION[CONS_USER_RESOLUTION]);
		$maxcellwidth = floor(($maxcellwidth[0] - $listMarginOffset)/$maxColumns);
	} else {
		$maxcellwidth = isset($_REQUEST['cellwidth'])?$_REQUEST['cellwidth']:floor((1024-$listMarginOffset)/$maxColumns); // 1024 considered
	}

	$hasCalendar = false; // if true, will add css/js to calendar
	$filtering = 0; // no items are being filtered

	// login is also stored as a cookie, so let's separate that
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

	// check if we have other items selected
	$temp = isset($_REQUEST['multiSelectedIds'])?explode(",",str_replace(",,",",",$_REQUEST['multiSelectedIds'])):array();
	$_REQUEST['multiSelectedIds'] = array();
	foreach ($temp as $msi)
		if ($msi != "") $_REQUEST['multiSelectedIds'][] = $msi;


	// use listADD system at the end of the list?
	$haveListADD = CONS_LIST_AJAXEDITOR && isset($module->options[CONS_MODULE_LISTADD]) && $module->options[CONS_MODULE_LISTADD] == "true";

	$hasMup = false; // mup = multiple upload
	foreach ($module->fields as $name => $field) {
		if ($field[CONS_XML_TIPO] == CONS_TIPO_UPLOAD && isset($field[CONS_XML_THUMBNAILS]) && isset($field[CONS_XML_MANDATORY])) {
			$hasMup = true;
			break;
		}
	}

	############################ CALLBACK #####################

	// default callback for each item listed
	function lcallback(&$template, &$params, $data, $processed = false) {
		$skeys = "";
		$checkkeys=  "";
		foreach($params['callbackModule']->keys as $key) {
			$skeys .= $key."=".$data[$key]."&amp;";
			$checkkeys .= $data[$key]."_";
		}
		$data['CLASS'] = $data['#']%2==0?'even':'odd';
		$data['skeys'] = $skeys;
		$data['checkkeys'] = substr($checkkeys,0,strlen($checkkeys)-1);
		if ((isset($_REQUEST['multiSelectedIds']) && in_array($data['checkkeys'],$_REQUEST['multiSelectedIds']))) {
			$data['checked'] = '1';
		} else
			$data['checked'] = '0';
		$data['module'] = $params['callbackModule']->name;
		// puts referer?
		if (isset($_REQUEST['affreferer']) && $_REQUEST['affreferer'] != "")
			$data['skeys'] .= "affreferer=".$_REQUEST['affreferer']."&amp;affrefererkeys=".$_REQUEST['affrefererkeys'];
		$temp = array();
		foreach ($data as $name => $content) {
			$temp[$name."_title"] = $content;
		}
		$data = array_merge($temp,$data);
		return $data;
	}

	############################ USER PREFERENCES #####################

	$up = $_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'];
	if (!is_array($up)) $up = @unserialize($up);
	$smartFields = array();
	$mustReset= false;
	$useSF = false;

	if (is_array($up)) {
		if (isset($up['sf']) && $up['sf'] == 0)
			$useSF = false;
		else {
			$useSF = true;
			if (!isset($up['smartfields'])) $up['smartfields'] = array();
			if (!isset($up['smartfields'][$module->name])) $up['smartfields'][$module->name] = array();
			foreach ($up['smartfields'][$module->name] as $smf => $smf_weight) {
				if ($smf_weight > 1) $smartFields[] = $smf; // only fields with higher than 1 weight
			}
		}
		if (isset($up['floating']) && $up['floating']=='1')
			$endScript .= "floaterbarSW();\n";
	}

	// if we do not have listing options in the metadata, try use the cache or auto-build

	#################### LISTING ##########################

	if (isset($module->options[CONS_MODULE_LISTING]) && !is_array($module->options[CONS_MODULE_LISTING]) && $module->options[CONS_MODULE_LISTING] != "")
		$module->options[CONS_MODULE_LISTING] = explode(",",$module->options[CONS_MODULE_LISTING]);


	if (!isset($module->options[CONS_MODULE_LISTING]) || !is_array($module->options[CONS_MODULE_LISTING]) || count($module->options[CONS_MODULE_LISTING]) == 0) {
		$toShow = false;

		// check admin cache for listing (created at module[bi_adm]::buildAdminMenu when at the index, usually)
		$file = CONS_PATH_CACHE.$_SESSION['CODE']."/admin".$_SESSION[CONS_SESSION_ACCESS_USER]['id_group'].".cache"; // HTML output with normal menu
		if (!is_file($file)) $this->buildAdminMenu(); // no cache, create it
		if (is_file($file)) { // we have the cache of admin.xml (might had some error above)
			$admxml = unserialize(cReadFile($file));
			if (is_object($admxml)) {
				function checkXMLlisting(&$xml,$moduleName) {
					// does the current node have the listing for my module
					if (isset($xml->data['module']) && $xml->data['module'] == $moduleName && isset($xml->data['listing'])) {
						// yes ... return (explode)
						return explode(",",$xml->data['listing']);
					}
					$total = $xml->total();

					for ($c=0;$c<$total;$c++) {
						$response = checkXMLlisting($xml->branchs[$c],$moduleName);
						if ($response !== false) return $response; // found it!
					}
					// nothing found, sorry
					return false;
				}
				$toShow = checkXMLlisting($admxml,$module->name); // I want the listing for this module, if any

			}
		}

		// if we had the listing in cache, $toShow has it, otherwise it's false
		if ($toShow == false || count($toShow) == 0) {
			// ok no cache ... so build a generic one
			$toShow = array();
			$usedColumns = 0;
			foreach ($module->fields as $name => $field) {
				if ($field[CONS_XML_TIPO] != CONS_TIPO_UPLOAD && (!isset($field[CONS_XML_META]) || $field[CONS_XML_META] != "password")) { // only explicit uploads can go
					$toShow[] = $name;
					$usedColumns ++;
					if ($usedColumns >= $maxColumns) break;
				}
			}
		}
	} else {
	 	$toShow = $module->options[CONS_MODULE_LISTING];
	}

	$usedColumns = count($toShow);
	
	############################ START UP SQL #####################

	// Get SQL ready because we might want extra fields (counters, search fields...)
	// fill what fields we will need on select
	$sqltoShow = array();
	foreach ($toShow as $key)
		$sqltoShow[$key] = 1;
	foreach ($module->keys as $key)
		if (!in_array($key,$toShow)) $sqltoShow[$key] = 1;
	$sql = $module->get_advanced_sql($sqltoShow,"","","","adminlist_".$module->name);
	unset($sqltoShow);

	// Order and query controls
	$hasOrder = false; // this module has a "ordem" field
	$qs = arrayToString(false,array("order","layout","p_init","login"));
	$fullqs = arrayToString(false,array("layout","p_init","login"));
	$core->template->assign("qs",$fullqs);
	$markOrder = array('','');
	$ord = array();
	if (isset($_REQUEST['order']) && $_REQUEST['order'] != "") {
		$ord = array($_REQUEST['order']);
		$sql['ORDER'] = array();
	} else if ($module->order != "") {
		$ord = explode(",",$module->order);
		$ord = $ord[0];
		$markOrder = array(substr($ord,0,strlen($ord)-1),$ord[strlen($ord)-1]);
		$ord = array();
	}

	// builds order into SQL
	foreach ($ord as $orditem) {
		$orditem = trim($orditem);
		if (strpos($orditem,"+") !== false || strpos($orditem,"\\") !== false) {
			$orditem = str_replace("+","",str_replace("\\","",$orditem));
			if (isset($module->fields[$orditem]))
				$sql['ORDER'][] = $module->name.".".$orditem." ASC";
			else
				$sql['ORDER'][] = $orditem." ASC";
			$markOrder = array($orditem,"+");
		} else {
			$orditem = str_replace("-","",str_replace("/","",$orditem));
			if (isset($module->fields[$orditem]))
				$sql['ORDER'][] = $module->name.".".$orditem." DESC";
			else
				$sql['ORDER'][] = $orditem." DESC";
			$markOrder = array($orditem,"-");
		}
	}

	// monitoring
	if (isset($_REQUEST['frommonitor'])) {
		$monitorXML = $this->getMonitorArray();
		$c = 0;
		foreach ($monitorXML as $monitor) {
			if ($c == $_REQUEST['frommonitor']) {
				$sql['WHERE'][] = "(".str_replace("\$id_user",$_SESSION[CONS_SESSION_ACCESS_USER]['id'],$monitor['sql']).")";
				$skeys .= "<input type='hidden' name='frommonitor' value='".$c."'/>";
				$filtering++;
				break;
			}
			$c++;
		}
	}

	############################# PREPARE TEMPLATE #####################

	// Header and general module data
	$core->template->assign("name",$module->name);
	if ($core->layout > 1) {
		$core->template->assign("_removeonpopup"); // the default remover comes after, we want before so the list also is affected
	}
	if ($notitle) {
		$core->template->assign("_listtitle"); // list title only on the first ajax page
	}
	if (!$embeded) {
		if ($core->layout != 0)
			$core->template->assign("_embeded"); // no embeded contents on popup/ajax unless requested
		$core->template->assign("_embededCall");
	}
	if ($embeded && $nopaging) $core->template->assign("_embededCall");
	if ($nopaging) $core->template->assign("_paging");
	if ($nocheckbox) {
		$core->template->assign("_checkbox");
		$skeys .= "<input type=\"hidden\" name=\"nocheckboxes\" value=\"1\"/>";
	}
	if (!$hasMup)
		$core->template->assign("_mup");

	if ($core->layout != 2) $core->addLink('validators.js'); // javascript used on validation forms

	// if we reset ordering ...
	if (isset($_REQUEST['noorder'])) $core->template->assign("_order","");

	// show search field open? are we already filtering stuff?
	$core->template->assign("searchFieldOn",isset($_REQUEST['searchFieldOn'])?1:0);

	// if we do not have edit pane, remove them
	if (in_array("edit",$module->options[CONS_MODULE_NOADMINPANES])===true) {
		$core->template->assign("_editbtn");
		$core->template->assign("_can_multiple");
		$core->template->assign("_mup");
	}
	
	// if set CONS_MODULE_DISALLOWMULTIPLE or multi-key, remove can_multiple
	if (isset($module->options[CONS_MODULE_DISALLOWMULTIPLE]) || count($module->keys)>1) $core->template->assign("_can_multiple"); 

	if (!isset($module->fields[CONS_FIELD_ORDER])) $core->template->assign("_can_reorder");
	if (!$hasOrder || count($module->keys)>1) $core->template->assign("_hasOrder","");
	if (isset($_REQUEST['order'])) $core->template->assign('order',$_REQUEST['order']);
	if (isset($_REQUEST['affreferer'])) $core->template->assign("affreferer",$_REQUEST['affreferer']);
	if (isset($_REQUEST['affrefererkeys'])) $core->template->assign("affrefererkeys",$_REQUEST['affrefererkeys']);
	if ($module->options[CONS_MODULE_AUTOCLEAN] == '') $core->template->assign("_hasAutoclean");


	############################ SHOW TABS? #####################

	if (!$notitle) {
		// tabs
		if (count($module->options[CONS_MODULE_TABS])>0 && $core->template->get("_tab") !== false) { // monitoring/ajax might not have these
			$tab = isset($_REQUEST['bi_adm_tab'])?$_REQUEST['bi_adm_tab']:'none';
			$core->template->assign("bi_adm_tab",$tab);
			$objTab = $core->template->get("_tab");
			$temp = "";
			$counter = 0;
			foreach ($module->options[CONS_MODULE_TABS] as $atab) {
				$atab = explode(":",$atab);
				$tabdata = array("#" => $counter,
								 "bi_adm_tab" => $tab!='none' && $counter==$tab?"current":"other",
								 "module" => $module->name,
								 "tabname" => $atab[0]);
				$temp .= $objTab->techo($tabdata);
				if ($tab != 'none' && $counter == $tab) {
					$sql['WHERE'][] = "(".$atab[1].")";
					$filtering++;
				}
				$counter++;
			}
			if ($tab != 'none') $skeys .= "<input type=\"hidden\" name=\"bi_adm_tab\" id=\"bi_adm_tab\" value=\"$tab\" />";
			$core->template->assign("_tab",$temp);
		} else
			$core->template->assign("_hastabs");
	} else
		$core->template->assign("_hastabs");

	############################ PREPARE TO BUILD FILTER (SEARCH) #####################

	# Filter Options --
	# search for linker modules (only if not popup/ajax show options, otherwise just filter)
	$linkerModules = array();
	foreach ($core->modules as $mname => $lmodule) {
		if ($lmodule->linker) {
			#check if this linker links to this module
			foreach ($lmodule->fields as $fname => $field) {
				if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK && $field[CONS_XML_MODULE] == $module->name) {
					# yes, this linker module links to this module, add to linkerList
					$linkerModules[] = $mname;
					break;
				}
			}
		}
	}

	// Search fields
	if ($core->layout == 0)
		$objfield = $core->template->get("_FORM_field"); # how any single field shows
	//else
		//$objfield = new CKTemplate(); // the _FORM_field was removed earlier whenremoving items not to shown on popup
	$tempOutput = ""; // raw output for template
	$tempOutputHidden = ""; // raw output for fields hidden by smart filter
	$ajaxContextHandler = array(); // this will be filled for ajax filter system (select inputs that fills out other selects when something is chosen)

	############################ BUILD FILTER (SEARCH) FORM #####################


	// The big SEARCH loop starts HERE
	$upFields = 0;
	$counter = 0;
	foreach ($module->fields as $name => $field) {
		if ($name == "ordem") $hasOrder = true;
		$content = ""; // content blank or false means this field will not be shown
		$fillDT = array('field' => $name,
						'width' => '90%'); // data for the INPUT html
		$outdata = array('name' => $name); // data for the whole search HTML (content =$fillDT)
		if (isset($_REQUEST['match_'.$name])) {
			switch ($_REQUEST['match_'.$name]) {
				case "i":
				case "=":
					$compare = "=";
					$outdata['selected'] = "i";
				break;
				case "d":
					$compare = "<>";
					$outdata['selected'] = "d";
				break;
				case "g":
					$compare = ">";
					$outdata['selected'] = "g";
				break;
				case "l":
					$compare = "<";
					$outdata['selected'] = "l";
				break;
				case "c":
					$compare = " LIKE ";
					$outdata['selected'] = "c";
				break;
				case "m":
					$compare = "m"; // <-- search for MONTH in a DATE[time]
					$outdata['selected'] = "m";
				break;
				case "b":
					$compare = "b"; // <-- search BETWEEN two DATE[time]
					$outdata['selected'] = "b";
				break;
			}
		} else {
			$compare = "=";
			$_REQUEST['match_'.$name] = "=";
			$outdata['selected'] = "";
		}
		$isFilteringThis = false;

		// check what is being filtered and how
		$emptyOutput = array();
		switch ($field[CONS_XML_TIPO]) {
			case CONS_TIPO_LINK: ################################################################ LINKS
				$mod = $core->loaded($module->fields[$name][CONS_XML_MODULE]);
				$sqlin = $mod->get_base_sql();

				// prepare filters

				if (isset($_REQUEST[$name]) && !is_array($_REQUEST[$name]) && $_REQUEST[$name] != '0' && $_REQUEST[$name] != "") {
					// filter this on, so we will fill this select instead of showing "select other fields ..."
					$sql['WHERE'][] = $module->name.".".$name."$compare\"".$_REQUEST[$name]."\"";
					$sqlin['SELECT'][] = "if (".$mod->name.".".$mod->keys[0].$compare."'".$_REQUEST[$name]."',1,0) as selected";
					$filtering++;
					$isFilteringThis = true;
					if ($useSF) {
						if (isset($up['smartfields'][$module->name][$name])) {
							$up['smartfields'][$module->name][$name]++;
							if ($up['smartfields'][$module->name][$name]==2) $smartFields[] = $name; // did not include before
							if ($up['smartfields'][$module->name][$name]>=10) $mustReset = true;
						} else
							$up['smartfields'][$module->name][$name] = 1;
					}
				}
				if ($core->layout != 0) continue; // do not treat this interface

				// load select field as the template we will use
				$field_sel = $core->template->get("_select_field");
				$using = clone $field_sel;
				$emptyOutput[] = "_string";
				$emptyOutput[] = "_numeric";
				$emptyOutput[] = "_month";
				$emptyOutput[] = "_between";
				// checks if this field is/can be filtered by another, if can, leave empty on ADD
				$canBeFilteredBy = array();
				if (isset($field[CONS_XML_FILTEREDBY])) {
					if ($field[CONS_XML_FILTEREDBY][0] != "-") {
						// if this is not set to NONE (if it is, just skip ... already out of automatic anyway)
						$canBeFilteredBy = $field[CONS_XML_FILTEREDBY]; // already a list of local fields
					}
				} else {
					// automatic
					foreach ($mod->options[CONS_MODULE_CANBEFILTERED] as $rname) {
						$myfield = $module->get_key_from($rname,"id_".$rname,true);
						if (count($myfield)>0) {
							$canBeFilteredBy = array_merge($canBeFilteredBy,$myfield);
						}
					}
					// checks for repetition, if any, we have an ambiguity and thus cannot proceed automaticaly
					if (count($canBeFilteredBy)>0) {
						for ($cbf=0;$cbf<count($canBeFilteredBy);$cbf++)
							for ($cbf2=$cbf+1;$cbf2<count($canBeFilteredBy);$cbf2++)
								if ($module->fields[$canBeFilteredBy[$cbf]][CONS_XML_MODULE] == $module->fields[$canBeFilteredBy[$cbf2]][CONS_XML_MODULE]) {
									$canBeFilteredBy = array();
									break;
								}
					}
				}

				// either automatic or manual filterby set to run ... so run
				if (count($canBeFilteredBy)>0) {
					$ajaxContextHandler[$name] = $canBeFilteredBy;
					$using->assign("_optional",""); // optional will already by the "select item ..."
					if ($haveListADD) {
						$canBeFilteredBy_la = array();
						foreach ($canBeFilteredBy as $cBFB)
							$canBeFilteredBy_la[] = "la_".$cBFB;
						$ajaxContextHandler['la_'.$name] = $canBeFilteredBy_la;
					}
				}

				if (count($canBeFilteredBy)>0 && !$isFilteringThis) {
					$using->assign("_optional","");
					$canBeFilteredBy_translated = array();
					for ($cbf=0;$cbf<count($canBeFilteredBy);$cbf++)
						$canBeFilteredBy_translated[$cbf] = $core->langOut($canBeFilteredBy[$cbf]);
					$using->assign("_options","<option value=\"\">".$core->langOut("select_other_field").": ".implode(", ",$canBeFilteredBy_translated)."</option>");
				} else {
					// show options as per filters (or show all)
					$sqlin['SELECT'][] = $mod->name.".".$mod->keys[0]." as ids";
					if (!isset($mod->fields['title']))
						$sqlin['SELECT'][] = $mod->name.".".$mod->title." as title";

					$core->safety = false; // <-- show all fields we can list
					$core->runContent($mod,$using,$sqlin,"_options");
					$core->safety = true; // <-- back to normal

				}

				$content = $using->techo($fillDT);

			break;
			case CONS_TIPO_ENUM:  ################################################################ ENUM
				if (isset($_REQUEST[$name]) && !is_array($_REQUEST[$name]) &&$_REQUEST[$name]!="") {
					$sql['WHERE'][] = $module->name.".".$name."$compare\"".$_REQUEST[$name]."\"";
					$filtering++;
					$isFilteringThis = true;
					if ($useSF) {
						if (isset($up['smartfields'][$module->name][$name])) {
							$up['smartfields'][$module->name][$name]++;
							if ($up['smartfields'][$module->name][$name]==2) $smartFields[] = $name; // did not include before
							if ($up['smartfields'][$module->name][$name]>=10) $mustReset = true;
						} else
							$up['smartfields'][$module->name][$name] = 1;
					}
				}
				if ($core->layout != 0) continue; // no inteface for seach on pop/ajax
				preg_match("@ENUM \(([^)]*)\).*@",$module->fields[$name][CONS_XML_SQL],$regs);
				$xtp = "<option value=\"{enum}\" {checked}>{enum_translated}</option>";
				$tp = new CKTemplate($core->template);
				$tp->tbreak($xtp);
				$emptyOutput[] = "_string";
				$emptyOutput[] = "_numeric";
				$emptyOutput[] = "_month";
				$emptyOutput[] = "_between";
				$temp = "<option value=''></option>";
				$enums = explode(",",$regs[1]);
				foreach ($enums as $x) {
					$x = str_replace("'","",$x);
					$db = array('enum' => $x,
								'enum_translated' => $core->langOut($x),
								'checked' => isset($_REQUEST[$name]) && $_REQUEST[$name] == $x?' selected="selected"':'');
					$temp .= $tp->techo($db);
				}
				$content =  "<select id=\"$name\" name=\"$name\" >".$temp."</select>";
			break;
			case CONS_TIPO_DATETIME:  ################################################################ DATETIME
			case CONS_TIPO_DATE:
				$fillDT['isbetween'] = 0;
				if ($compare != "m" && $compare != "b" && isset($_REQUEST[$name]) && $_REQUEST[$name] != "0000-00-00" && $_REQUEST[$name] != "0000-00-00 00:00:00" && $_REQUEST[$name] != "") {
					if (isData($_REQUEST[$name],$data)) {
						$sql['WHERE'][] = $module->name.".".$name."$compare\"".$data."\"";
						$filtering++;
						$isFilteringThis = true;
					}
				} else if ($compare == "m") {
					if (is_numeric($_REQUEST[$name])) {
						if ($_REQUEST[$name]<10) $_REQUEST[$name] = "0".$_REQUEST[$name];
						$sql['WHERE'][] = $module->name.".".$name." LIKE \"____-".$_REQUEST[$name]."-%\"";
						$filtering++;
						$isFilteringThis = true;
					} else if (preg_match("@([0-9]{1,2})[^0-9]([0-9]{1,2})[^0-9]([0-9]{1,4})@",$_REQUEST[$name],$regs)>0) {
						$regs = (int)$regs[2];
						if ($regs<10) $regs = "0".$regs;
						$sql['WHERE'][] = $module->name.".".$name." LIKE \"____-".$regs."-%\"";
						$filtering++;
						$isFilteringThis = true;
					}
				} else if ($compare == "b") {
					$sql['WHERE'][] = $module->name.".".$name." >= \"".$_REQUEST[$name]."\"";
					$sql['WHERE'][] = $module->name.".".$name." <= \"".$_REQUEST["between_".$name]."\"";
					$filtering++;
					$fillDT['isbetween'] = 1;
					$fillDT['between_value'] = $_REQUEST['between_'.$name];
				}
				if ($isFilteringThis && $useSF) {
					if (isset($up['smartfields'][$module->name][$name])) {
						$up['smartfields'][$module->name][$name]++;
						if ($up['smartfields'][$module->name][$name]==2) $smartFields[] = $name; // did not include before
						if ($up['smartfields'][$module->name][$name]>=10) $mustReset = true;
					} else
						$up['smartfields'][$module->name][$name] = 1;
				}
				if ($core->layout != 0) continue; // no inteface for seach on pop/ajax
				$hasCalendar = true;
				$field_txt = $core->template->get("_datetime_field");
				$using = clone($field_txt);
				$fillDT['value'] = isset($_REQUEST[$name])?$_REQUEST[$name]:'';
				$fillDT['width'] = "120px";
				$fillDT['calendar'] = "<img id='divcalendar_".$name."' onclick=\"calendarHandler.showCalendar('".$name."','divcalendar_".$name."',-80,-8);\" src=\"".CONS_INSTALL_ROOT.CONS_PATH_PAGES."_js/calendar/gifs/dyncalendar.gif\" style=\"width:16px;height:16px;position:relative;top:3px;left:2px\" alt=\"".$core->langOut('calendar')."\"/>";
				$fillDT['calendar2'] = "<img id='divcalendar_between_".$name."' onclick=\"calendarHandler.showCalendar('between_".$name."','divcalendar_between_".$name."',-80,-8);\" src=\"".CONS_INSTALL_ROOT.CONS_PATH_PAGES."_js/calendar/gifs/dyncalendar.gif\" style=\"width:16px;height:16px;position:relative;top:3px;left:2px\" alt=\"".$core->langOut('calendar')."\"/>";
				$content = $using->techo($fillDT);

			break;
			case CONS_TIPO_TEXT:  ################################################################ NORMAL INPUT FIELDS (text, numbers)
			case CONS_TIPO_VC:
				if (isset($_REQUEST[$name]) && !is_array($_REQUEST[$name]) && $_REQUEST[$name] != "") 
					$_REQUEST[$name] = cleanString($_REQUEST[$name],isset($field[CONS_XML_HTML]));
			case CONS_TIPO_INT:
			case CONS_TIPO_FLOAT:
				if (isset($_REQUEST[$name]) && !is_array($_REQUEST[$name]) && $_REQUEST[$name] != "") {
					$sql['WHERE'][] = $module->name.".".$name."$compare\"".($compare==" LIKE "?"%":"").$_REQUEST[$name].($compare==" LIKE "?"%":"")."\"";
					$filtering++;
					$isFilteringThis = true;
					if ($useSF) {
						if (isset($up['smartfields'][$module->name][$name])) {
							$up['smartfields'][$module->name][$name]++;
							if ($up['smartfields'][$module->name][$name]==2) $smartFields[] = $name; // did not include before
							if ($up['smartfields'][$module->name][$name]>=10) $mustReset = true;
						} else {
							$up['smartfields'][$module->name][$name] = 1;
						}
					}
				}
				if ($core->layout != 0) continue;
				$field_txt = $core->template->get("_normal_field");

				$using = clone($field_txt);
				if ($field[CONS_XML_TIPO] == CONS_TIPO_TEXT || $field[CONS_XML_TIPO] == CONS_TIPO_VC)
					$emptyOutput[] = "_numeric";
				$emptyOutput[] = "_month";
				$emptyOutput[] = "_between";
				$fillDT['value'] = isset($_REQUEST[$name])?$_REQUEST[$name]:'';
				$fillDT['value'] = str_replace("&lt;","&amp;lt;",$fillDT['value']); # textareas cannot have <, and this it will auto-convert &lt; to < .. preventing such behaviour
				$fillDT['value'] = str_replace("&gt;","&amp;gt;",$fillDT['value']); # this is not really needed but keep the code clean

				$content = $using->techo($fillDT);
			break;
			case CONS_TIPO_UPLOAD:  ################################################################ UPLOAD (not displayed but counted for mup)
				$upFields++;
			break;
		} // switch
		if ($isFilteringThis) {
			$skeys .= "<input type=\"hidden\" name=\"match_".$name."\" value=\"".$_REQUEST['match_'.$name]."\"/>";
			$skeys .= "<input type=\"hidden\" name=\"$name\" value=\"".$_REQUEST[$name]."\"/>";
		}
		if ($core->layout == 0 && $content != "") { // if this is a searchable field ... show

			$counter++;
			$outdata['CLASS'] = $counter%2==0?'even':'odd';
			$using = clone $objfield;
			$outdata['field'] = $content;

			if (count($smartFields)==0 || in_array($name,$smartFields))
				$tempOutput .= $using->techo($outdata,$emptyOutput);
			else
				$tempOutputHidden .= $using->techo($outdata,$emptyOutput);
		}
	} // foreach (search)
	// END search LOOP ###########################

	// smartField update
	if ($core->layout == 0 && $filtering>0 && $useSF) {
		$uMod = $core->loaded(CONS_AUTH_USERMODULE);
		if ($mustReset) { // some setting reached 10, reduce all above 5 to 5, reduce 1 on the rest
			foreach ($up['smartfields'][$module->name] as $fname => $fweight) {
				if ($fweight>5) $fweight = 5;
				else $fweight-=1;
				if ($fweight <0) $fweight=0;
				$up['smartfields'][$module->name][$fname] = $fweight;
			}
		}
		$usql = "UPDATE ".$uMod->dbname." SET userprefs=\"".addslashes(serialize($up))."\" WHERE id=".$_SESSION[CONS_SESSION_ACCESS_USER]['id'];
		if ($core->dbo->simpleQuery($usql)) {
			$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'] = $up;
		}
		unset($uMod);
		unset($usql);
	}

	############################ OUTPUT SEARCH AND JS FOR SEARCH #####################

	if ($core->layout == 0) {
		$core->template->assign("_FORM_field",$tempOutput);
		$core->template->assign("smartfields",$tempOutputHidden);
		if ($tempOutputHidden=='') $core->template->assign("_smartfields");
		// The following were just templates we don't need anymore (used to create the fields), remove from page
		$core->template->assign("_select_field");
		$core->template->assign("_normal_field");
		$core->template->assign("_datetime_field");
		unset($tempOutput);
		// if we have ajax handler, build the proper javascript for them at the end of the endScript
		$endScript .= "var ajaxHandlers = new Array();\n"; // the array should exist to prevent JS errors
		if (count($ajaxContextHandler)>0) {
			// we have ajaxHandlers
			foreach ($ajaxContextHandler as $field => $childfields) {
				$endScript .= "var tmp = new Array();\n";
				if ($haveListADD && !isset($module->fields[$field]))
					$endScript .= "tmp.push('".$module->fields[substr($field,3)][CONS_XML_MODULE]."');\n";
				else
					$endScript .= "tmp.push('".$module->fields[$field][CONS_XML_MODULE]."');\n";
				$endScript .= "tmp.push('$field');\n";
				foreach ($childfields as $cf) {
					$endScript .= "tmp.push('$cf');\n";
				}
				$endScript .= "ajaxHandlers.push(tmp);\n";
			}
		}
		if ($hasCalendar) {
			$core->addLink("calendar/dyncalendar.css");
			$core->addLink("calendar/dyncalendar.js");
			$endScript .= "var calendarHandler = new dynCalendar('".CONS_INSTALL_ROOT.CONS_PATH_PAGES."_js/calendar/gifs/');\n";
		}

	}

	############################ LINKER MODULES ON SEARCH #####################

	// now add the linker modules
	foreach ($linkerModules as $lmod) {
		$lmodule = $core->loaded($lmod);
		$myLink = $lmodule->get_key_from($module->name);
		$sql['LEFT'][] = $lmodule->dbname." as $lmod ON ($lmod.$myLink = ".$module->name.".".$module->keys[0].")"; // TODO: what if you have more than one key?
		foreach ($lmodule->fields as $linkerfname => $linkerfield) { # for each link in the linker
			if ($linkerfield[CONS_XML_TIPO] == CONS_TIPO_LINK && $linkerfield[CONS_XML_MODULE] != $module->name) { # that is not to myself
				$remoteModule = $core->loaded($linkerfield[CONS_XML_MODULE]); # from linker
				foreach ($remoteModule->fields as $lfname => $lfield) { # for each field on the linked module
					if (in_array($lfname,$remoteModule->keys) ) { # that is a key to that module
						#add to the search list
						if ($lfname == 'id') $lfname = $lmodule->get_key_from($remoteModule->name,'id_'.$remoteModule->name);
						if (isset($_REQUEST['match_'.$lmod."_".$lfname])) {
							switch ($_REQUEST['match_'.$lmod."_".$lfname]) {
								case "i":
									$compare = "=";
								break;
								case "d":
									$compare = "<>";
								break;
								case "g":
									$compare = ">";
								break;
								case "l":
									$compare = "<";
								break;
								case "c":
									$compare = " LIKE ";
								break;
							}
						} else
							$compare = "=";
						if (isset($_REQUEST[$lmod."_".$lfname]) && !is_array($_REQUEST[$lmod."_".$lfname]) && $_REQUEST[$lmod."_".$lfname] != 0 && $_REQUEST[$lmod."_".$lfname] != "") {
							# filter this out
							$sql['LEFT'][] = $remoteModule->dbname." as ".$remoteModule->name." ON (".$remoteModule->name.".".$remoteModule->keys[0]." = $lmod.$linkerfname)";
							$sql['WHERE'][] = $remoteModule->name.".".$remoteModule->keys[0]." = '".$_REQUEST[$lmod."_".$lfname]."'";
							$filtering++;
							$skeys .= "<input type=\"hidden\" name=\"match_".$lmod."_".$lfname."\" value=\"".$_REQUEST['match_'.$lmod."_".$lfname]."\"/>";
							$skeys .= "<input type=\"hidden\" name=\"$name\" value=\"".$_REQUEST[$lmod."_".$lfname]."\"/>";
						}
					} // if (keys)
				} // foreach (field in remote module)
			} // if (module in linker field)
		} // foreach (field in linker module)
	} // end linker options

	# ok, output
	if ($filtering>0) {
		$core->template->assign("filtering",$filtering);
		$core->template->assign("skeys",$skeys);
	} else
		$core->template->assign("_filterOn");
	$core->template->assign("module",$module->name);
	# -- end filter options
	#########################
















	##### YES, I WANT TO MAKE IT CLEAR: BEFORE THIS IS THE SEARCH PART, AFTER THE LIST PART
















	############################ START UP LIST #####################
	# Build listing templates (listing is loaded from XML at the bi_adm:buildAdminMenu
	if (!$notitle) {
		$toprowObj = $core->template->get("_top_row");
		$toprowObj->assign("qs",$qs);
	}
	$lineObj = $core->template->get("_row");
	$lineAddObj = $core->template->get("_rowla");
	$buttonObj = $core->template->get("_listbutton");
	$buttonHeader= $core->template->get("_listbuttonheader");
	$colwidth = array();
	$output = ""; // table header line (_top_row)
	$outputLine = ""; // template to the list (_row)
	$outputAdd = ""; // temaplte to list add feature (_rowla)

	// if we have listbuttons, add them to the start of each line
	if (count($module->options[CONS_MODULE_LISTBUTTONS])>0) {
		foreach ($module->options[CONS_MODULE_LISTBUTTONS] as $button) {
			$button = explode(",",$button);
			if (count($button)==3) {
				$buttonData = array(
					'CLASS' => "{CLASS}",
					'url' => $button[0],
					'alt' => $button[1],
					'buttonimg' => $button[2]
					);
				$output .= $buttonHeader->techo();
				$outputLine .= $buttonObj->techo($buttonData);
				$outputAdd .= $buttonHeader->techo();
			} // #TODO: else raise error
		}
	}
	// add new templates for buttons (if any)
	$core->template->assign("_listbuttonheader",$output);
	if ($outputLine != '') {
		$temp = new CKTemplate($core->template);
		$temp->tbreak($outputLine);
		$core->template->assign("_listbutton",$temp);
		unset($temp);
	} else
		$core->template->assign("_listbutton","");
	$outputLine = "";
	$output = "";

	################# FORMAT TEMPLATE ###################

	// control column widths
    $maxwidth = floor(100/$usedColumns); // total is 100%, each will have this, we use 99 because cells have borders which might break the template

    if ($maxwidth%2!=0)$maxwidth--; // guaranteed we can divide by 2
	$availablewidth = 100-($maxwidth*$usedColumns); // how many % are available as extra, max 99 same reason


	$imageDetected = false;

	$columnWidth = array();
	foreach ($toShow as $field) { // evaluates column size AND thumbnail configuration
		$linkField = &$module->fields[$field];
		switch ($linkField[CONS_XML_TIPO]){
			case CONS_TIPO_FLOAT:
			case CONS_TIPO_INT:
			case CONS_TIPO_DATE:
			case CONS_TIPO_ENUM:
				$columnWidth[$field] = ceil($maxwidth/2);
				$availablewidth += ceil($maxwidth/2);
			break;
			case CONS_TIPO_UPLOAD:
				$columnWidth[$field] = $maxwidth;
				if (isset($linkField[CONS_XML_THUMBNAILS])) {
					if ($imageDetected) {
						$core->errorControl->raise(527,$field,$module->name);
						continue;
					}
					$imageDetected = true;
					$numThumbs = count($linkField[CONS_XML_THUMBNAILS]);
					$lastThumb = explode(",",$linkField[CONS_XML_THUMBNAILS][$numThumbs-1]);
					if ($lastThumb[0]>$maxcellwidth) {
						$factor = $lastThumb[0]/$maxcellwidth;
						$lastThumb[0] = ceil($lastThumb[0]/$factor);
						$lastThumb[1] = ceil($lastThumb[1]/$factor);
					}
					if ($lastThumb[1]>60) {
						$factor = $lastThumb[1]/60;
						$lastThumb[0] = ceil($lastThumb[0]/$factor);
						$lastThumb[1] = ceil($lastThumb[1]/$factor);
					}
				} else {
					$core->errorControl->raise(528,$field,$module->name);
				}
			break;
			default:
				$columnWidth[$field] = $maxwidth;
			break;
		} // switch
	}
	if (isset($columnWidth[$module->title]))
		$columnWidth[$module->title] += $availablewidth; // this is in percent
	else
		$columnWidth[$toShow[0]] += $availablewidth; // this is in percent

	// for each field to show, build the template for a result line ...
	$foundCounter = false;
	$hadTitle = false;
	$laFields = array();
	foreach ($toShow as $field) { // creates each column in the list
		$field_name = $field; # can change if this is a remote field
		$linkField = &$module->fields[$field];
		$field_extra = "";
		$remoteModule = null;
		$tdw = $columnWidth[$field];
		$la = "";
		$hadTitle = $hadTitle || $field == $module->title;
		if ($field != "") {
			if ($field[0] == "#") {
				$la = "";
				# counter, allow only ONE
				$field = substr($field,1);
				if (!$foundCounter) {
					$remoteModule = $core->loaded($field);
					if ($remoteModule) {
						$alreadyLinked = false;
						foreach ($sql['LEFT'] as $left) {
							if (strpos($left,$remoteModule->dbname." as ")!== false) {
								$alreadyLinked = true;
							}
						}
						$remoteLinker = $remoteModule->get_key_from($module->name,"id_".$module->name);
						if ($remoteLinker != "") {
							# TODO: what if remote to this requires more than one key to link?
							if (!$alreadyLinked) $sql['LEFT'][] = $remoteModule->dbname." as ".$remoteModule->name." on (".$remoteModule->name.".".$remoteLinker." = ".$module->name.".".$module->keys[0].")";
							$sql['GROUP'][0] = $module->name.".".$module->keys[0];
							$sql['SELECT'][] = "COUNT(".$remoteModule->name.".".$remoteModule->keys[0].") as $field";
							$foundCounter = true;
							$field_extra = " (#)";
							$field_name = $field;
						} else
							$core->log[] = "Remote module $field has no link to this module";
					} else
						$core->log[] = "No remote module $field trying to create remote counter";

				} else
					$core->log[] = "Only one counter allowed per SQL";
			} else if ($field[0] == "!") { // gets data from another table which THIS table does not link
				$la = "";
				// remote link (this module do not have a link to the remote link, but the other way applies and I want it)
				$field = substr($field,1);
				if (preg_match("/^([a-zA-Z0-9\_]*)(\([^\)]*\))?\:(.*)\$/i",$field,$regs)) {
					$remoteModule = $core->loaded($regs[1]);
					if ($remoteModule) {
						$optionalWhere = str_replace("(","",str_replace(")","",$regs[2]));
						$field_name = $regs[3];
						$remoteLinker = $remoteModule->get_key_from($module->name,"id_".$module->name);
						if ($remoteLinker != "") {
							$sql['LEFT'][] = $remoteModule->dbname." as ".$remoteModule->name." on (".$remoteModule->name.".".$remoteLinker." = ".$module->name.".".$module->keys[0].($optionalWhere!=""?" AND ".$optionalWhere:"").")";
							$sql['GROUP'][0] = $module->name.".".$module->keys[0]; # prevents repeated occurences
							$sql['SELECT'][] = $remoteModule->name.".".$field_name;
							$field_extra = " (".$core->langOut($remoteModule->name).")";
							$linkField = $remoteModule->fields[$field_name];
							$field = $field_name;
						} else
							$core->log[] = "Remote module ".$regs[1]." has no link to this module at remote link list $field";
					} else
						$core->log[] = "Remote module not found at remote link list $field (".$regs[1].")";
				} else {
					$core->log[] = "Remote list link format failed for $field";
				}
			###############
			// normal items follow
			###############
			} else {
				if (isset($module->fields[$field]))
					$linkField = $module->fields[$field];
				else { # remote?
					$rName = explode("_",$field);
					$lName = array();
					while(count($rName)>0) {
						array_unshift($lName,array_pop($rName));
						$possibleField = "id_".implode("_",$rName);
						if (!isset($module->fields[$possibleField]) && $possibleField[strlen($possibleField)-1]=="s")
							$possibleField = substr($possibleField,0,strlen($possibleField)-1); # If the field would result in a keyword, it adds an 's' .. check this possibility
						if (isset($module->fields[$possibleField]) && $module->fields[$possibleField][CONS_XML_TIPO] == CONS_TIPO_LINK){
							$remoteModule = $core->loaded($module->fields[$possibleField][CONS_XML_MODULE]);
							if ($remoteModule !== false) {
								# Ok I found the remote module, $lName should have the remote name
								$lName = implode("_",$lName);
								if (isset($remoteModule->fields[$lName])) { # sucess!
									$linkField = $remoteModule->fields[$lName];
									$field_name = $lName;
									$field_extra = " (".$core->langOut($possibleField).")";
									$rName= array(); # just in case
									if ($haveListADD) {
										$xtp = "{_items}<option value=\"{".$remoteModule->keys[0]."}\" {selected|selected}>{".$remoteModule->title."}</option>{/items}";
										$tp = new CKTemplate($core->template);
										$tp->tbreak($xtp);
										$innersql = "";
										if (isset($_REQUEST['affreferer']) && isset($_REQUEST['affrefererkeys']) && $_REQUEST['affreferer'] == $module->fields[$possibleField][CONS_XML_MODULE] && count($remoteModule->keys)==1) { // only one key, TODO: multikey
											$innersql = $remoteModule->get_base_sql();
											$innersql['SELECT'][] = "if (".$remoteModule->name.".".$remoteModule->keys[0]."='".$_REQUEST['affrefererkeys']."',1,0) as selected";
										}
										$core->runContent($remoteModule,$tp,$innersql,'_items',false);
										$la = '<span id="la_'.$possibleField.'_ara" style="width:90%"><select onchange="selectChange(\'la_'.$possibleField.'\');" style="width:100%;margin:0px;border:1px;padding:1px;font-size:9px" name="la_'.$possibleField.'" id="la_'.$possibleField.'">'.$tp->techo().'</select></span>';
										$laFields[] = $possibleField;
										unset($tp);
									}
								} else
									$core->log[] = 'unable to process item '.$field; 
								break; # from while
							}
						}
					}
				}
			}

			$field_typed = "{".$field."}";
			$field_title = "{".$field."_title|html}";

			// if we have a formating for this item (from THIS module, or the REMOVE module ... linkField was set accordinly above), format it!
			if (!is_null($linkField)) {
				switch ($linkField[CONS_XML_TIPO]){
					case CONS_TIPO_FLOAT:
						$field_typed = "{".$field."|number|4|.}";
						$field_title = "{".$field."_title|number|4|.}";
					case CONS_TIPO_INT:
						if ($la == '') {
							$la = '<input type="text" name="la_'.$field.'" id="la_'.$field.'" value="'.$core->langOut($field).'" onfocus="if (this.value==\''.$core->langOut($field).'\') this.value = \'\';" onblur="if (this.value==\'\') this.value=\''.$core->langOut($field).'\';" style="width:90%;margin:0px;border:1px;padding:1px;font-size:9px"/>';
							$laFields[] = $field;
						}
					break;
					case CONS_TIPO_VC:
					case CONS_TIPO_TEXT:
						if (isset($linkField[CONS_XML_HTML])) {
							$field_typed = "{".$field."|truncate|".ceil($maxcellwidth/2)."|...|true}";
							$field_title = "{".$field."_title|truncate|500|...|true}";
						} else {
							$field_typed = "{".$field."|truncate|".ceil($maxcellwidth/2)."|...|false}";
							$field_title = "{".$field."_title|truncate|400|...|true}";
						}
						if ($la == '') {
							$la = '<input type="text" name="la_'.$field.'" id="la_'.$field.'" value="'.$core->langOut($field).'" onfocus="if (this.value==\''.$core->langOut($field).'\') this.value = \'\';" onblur="if (this.value==\'\') this.value=\''.$core->langOut($field).'\';" style="width:90%;margin:0px;border:1px;padding:1px;font-size:9px"/>';
							$laFields[] = $field;
						}
					break;
					case CONS_TIPO_DATE:
						$field_typed = "{".$field."|date}";
						$field_title = "{".$field."_title|date}";
						if ($la == '') {
							$la = '<input type="text" name="la_'.$field.'" id="la_'.$field.'" value="'.$core->langOut($field).'" onfocus="if (this.value==\''.$core->langOut($field).'\') this.value = \'\';" onblur="if (this.value==\'\') this.value=\''.$core->langOut($field).'\';" style="width:90%;margin:0px;border:1px;padding:1px;font-size:9px"/>';
							$laFields[] = $field;
						}
					break;
					case CONS_TIPO_DATETIME:
						$field_typed = "{".$field."|datetime}";
						$field_title = "{".$field."_title|datetime}";
						if ($la == '') {
							$la = '<input type="text" name="la_'.$field.'" id="la_'.$field.'" value="'.$core->langOut($field).'" onfocus="if (this.value==\''.$core->langOut($field).'\') this.value = \'\';" onblur="if (this.value==\'\') this.value=\''.$core->langOut($field).'\';" style="width:90%;margin:0px;border:1px;padding:1px;font-size:9px"/>';
							$laFields[] = $field;
						}
					break;
					case CONS_TIPO_ENUM:
						if (CONS_LIST_AJAXEDITOR && !isset($linkField[CONS_XML_READONLY])) { # use ajax editor for enum fields on the list! Keep in mind that the ajax that treats this must be on the page
							$temp = isset($module->fields[$name][CONS_XML_MANDATORY])?'':"<option value=''></option>";
							preg_match("@ENUM \(([^)]*)\).*@",$module->fields[$field][CONS_XML_SQL],$regs);
							$xtp = "<option value=\"{enum}\" {\}".$field."|selected|{enum}}>{enum_translated}</option>";
							$tp = new CKTemplate($core->template);
							$tp->tbreak($xtp);
							$enums = explode(",",$regs[1]);
							foreach ($enums as $x) {
								$x = str_replace("'","",$x);
								$db = array('enum' => $x,
												'enum_translated' => $core->langOut($x),
												);
								$temp .= $tp->techo($db);
							}
							unset($tp);
							$field_typed =  "<select style='width:99%;margin:0px;border:1px;padding:1px;font-size:9px' onchange=\"listselectchange('".$module->name."','{checkkeys}',this.value,'".$field."');\">".$temp."</select>";
							if ($la == '') {
								$la = '<select style="width:90%;margin:0px;border:1px;padding:1px;font-size:9px" name="la_'.$field.'" id="la_'.$field.'">'.$temp.'</select>';
								$laFields[] = $field;
							}
						} else
							$field_typed = "{_t}{".$field."}{/t}";
					break;
					case CONS_TIPO_UPLOAD:
						$field_typed = "<a href=\"/{".$field."_1}\" rel=\"shadowbox[images]\"><img src=\"/{".$field."_".($numThumbs)."}\" width=\"".$lastThumb[0]."\" height=\"".$lastThumb[1]."\"/></a>";
						$field_title = "";
						$haveListADD = false;
						break;
					case CONS_TIPO_LINK:
						$field_title = "";
						//if ($field == "id_parent")
							//$field_typed = "tree";
							# TODO: parenting lists
						if ($haveListADD && $la == '') { // select in this field (note: remote NAMES, which is the majority of the occurences, are handled before this switch)
							# TODO: does not work with multiple keys
							$rmd = $core->loaded($module->fields[$field][CONS_XML_MODULE]);
							$xtp = "{_items}<option value=\"{".$rmd->keys[0]."}\"  {selected|selected}>{".$rmd->title."}</option>{/items}";
							$tp = new CKTemplate($core->template);
							$tp->tbreak($xtp);
							$innersql = "";
							if (isset($_REQUEST['affreferer']) && isset($_REQUEST['affrefererkeys']) && $_REQUEST['affreferer'] == $module->fields[$field][CONS_XML_MODULE] && count($rmd->keys)==1) { // only one key, TODO: multikey
								$innersql = $rmd->get_base_sql();
								$innersql['SELECT'][] = "if (".$rmd->name.".".$rmd->keys[0]."='".$_REQUEST['affrefererkeys']."',1,0) as selected";
							}
							$core->runContent($rmd,$tp,$innersql,'_items',false);
							$la = '<span id="la_'.$possibleField.'_ara" style="width:90%"><select onchange="selectChange(\'la_'.$possibleField.'\');" style="width:100%;margin:0px;border:1px;padding:1px;font-size:9px" name="la_'.$field.'" id="la_'.$field.'">'.$tp->techo().'</select><span>';
							$laFields[] = $field;
							unset($tp);
						}
					break;
				} // switch

			}

			$rowData = array(
							 'field' => $field,
							 'content' => $field_typed, // shown
							 'fullcontent' => $field_title, // title
							 'name' => $field_name,
							 'extra' => $field_extra,
							 'tdwidth' => $tdw, // %
							 'trheight' => 16, // ?? not used ??
							 'CLASS' => "{CLASS}",
							 'oA' => $field == $markOrder[0] && $markOrder[1] == "+"?"_on":"",
						  	 'oD' => $field == $markOrder[0] && $markOrder[1] == "-"?"_on":"",
						  	 'checkkeys' => '{checkkeys}',
							 'contentla' => $la
							 );
			if ($field == $module->title && in_array("edit",$module->options[CONS_MODULE_NOADMINPANES])===false) {
				$rowData['content'] = "<a style=\"font-weight: bold\" href=\"edit.php?module=".$module->name."&amp;{skeys}\">".$rowData['content']."</a>";
			}
			if (!$notitle) {
				$output.= $toprowObj->techo($rowData,$core->layout!=0||$linkField[CONS_XML_TIPO]==CONS_TIPO_UPLOAD?array("_noOrder"):array());
				if ($haveListADD) $outputAdd .= $lineAddObj->techo($rowData);
			}
			$outputLine .= $lineObj->techo($rowData);


		}
	} // foreach field to display
	// end creation of listing templates

	############################ FINALIZE TEMPLATE #####################

	// prepare real output
	if (!$notitle) $core->template->assign("_top_row",$output);
	$lineObj = new CKTemplate($core->template);
	$lineObj->tbreak($outputLine);
	$core->template->assign("_row",$lineObj);
	if ($haveListADD && !$notitle) {
		$core->template->assign("_rowla",$outputAdd);
		$core->template->assign("colspan",$usedColumns+($core->layout==0?3:2));
		$core->template->assign("lafields",implode("','",$laFields));
	} else
		$core->template->assign("_la");


	############################ FILL SEARCH FORMS  #####################

	// prepare template parameters for runContent
	$core->templateParams['callbackModule'] = &$module;
	$core->templateParams['forcepost'] = "frmbase|haveinfo=1"; # not really needed to change the haveinfo but TC requires a field to change
	$core->templateParams['noOutputParse'] = !$imageDetected; # faster (won't treat images nor toggles)

	// some left joins might cause issues, so GROUP
	if (count($sql['GROUP']) == 0)	$sql['GROUP'] = $module->keys;

	// prepare default list size
	if (isset($_REQUEST['p_size']) && is_numeric($_REQUEST['p_size']) && $_REQUEST['p_size']>=0)
		$this->parent->templateParams['p_size'] = $_REQUEST['p_size'];
	else {
		$this->parent->templateParams['p_size'] = CONS_DEFAULT_IPP;
		if (is_array($up) && isset($up['pfim']) && is_numeric($up['pfim']) && $up['pfim'] > 4) {
			$this->parent->templateParams['p_size'] = $up['pfim'];
		}
	}

	$pinit = isset($_REQUEST['p_init'])?$_REQUEST['p_init']:0;
	if (isset($_REQUEST['vaction']) && $_REQUEST['vaction'] == 'repage') {
		$pinit = 0;
		unset($_REQUEST['p_init']);
	} 
	$psize = $core->templateParams['p_size'];
	$core->template->assign("p_size",$psize);
	$core->template->assign("p_init",$pinit);


	############################ EXECUTE mark all results, if requested  #####################

	if (isset($_REQUEST['vaction']) && $_REQUEST['vaction'] == "mark" && isset($_REQUEST['markmode']) && $_REQUEST['markmode'] == "true") { // yes, mark all
		// fetch ALL resulting keys
		$_REQUEST['multiSelectedIds'] = array();
		$core->dbo->query($sql,$r,$n);
		for ($c=0;$c<$n;$c++) {
			$msidata = $core->dbo->fetch_assoc($r);
			$checkkeys = "";
			foreach($module->keys as $key) {
				$checkkeys .= $msidata[$key]."_";
			}
			$checkkeys = substr($checkkeys,0,strlen($checkkeys)-1); // remove last _
			$_REQUEST['multiSelectedIds'][] = $checkkeys;
		}
		$_REQUEST['multiSelectedIds'] = isset($_REQUEST['multiSelectedIds'])?str_replace(",,",",",",".implode(",",$_REQUEST['multiSelectedIds']).","):"";
		echo $_REQUEST['multiSelectedIds'];
		$this->parent->close(true); // that's right, vaction=mark is an ajax call which returns only the list of selected items
	}

	// and here we go! this will fill the list and get us the total of items (real total, not displayed)

	$callback = array('lcallback');
	if ($module->options[CONS_MODULE_CALLBACK] != '') {
		$ctemp = explode(":",$module->options[CONS_MODULE_CALLBACK]);
		if (count($ctemp) == 2) {
			$obj = &$core->loadedPlugins[$ctemp[0]];
			$callback[] = array($obj,$ctemp[1]);
		}
	}

	##############################################################################
	$total = $module->runContent($core->template,$sql,"_lineTemplate",true,false,$callback);
	##############################################################################


	if (!is_numeric($total)) $total = 0; // can come FALSE if nothing found

	// get the listing template up
	if ($core->layout == 0) {
		if ($total > 0) {
			$core->template->createPaging("_list_PAGING",$total, $pinit,$psize);
		} else {
			$core->template->assign("_list_PAGING");
		}
	}


	// some memory cleaning because we are neat
	unset($core->templareParams['callbackModule']);



	############################ RELATED LINKS #####################

	// detect relate links (supports only ONE at this point (first found))
	if ($core->layout == 0) { //relatewithlinker
		foreach ($core->modules as $mname => $modobj) {
			$isLinker = false;
			$prevKey = "";
			if ($modobj->linker) {
				foreach ($modobj->keys as $key) {
					if ($modobj->fields[$key][CONS_XML_TIPO] == CONS_TIPO_LINK && $modobj->fields[$key][CONS_XML_MODULE] == $module->name) {
						// this module is a linker with me
						$isLinker = true;
						if ($prevKey != "") break; # already found the key to the OTHER module
					}
					$prevKey = $key;
				}
				if ($isLinker) {
					// $prevKey has the key to the other module
					$core->template->assign("linkermodule_relate",$modobj->fields[$prevKey][CONS_XML_MODULE]);
					$core->template->assign("linkermodule",$mname);
					$mod = $core->modules[$modobj->fields[$prevKey][CONS_XML_MODULE]];
					$n = $core->dbo->fetch("SELECT count(*) FROM ".$mod->dbname);
					if ($n>CONS_MAX_RELATESIZE) {
						$core->template->assign("_relatewithlinker","");
						$core->log[] = $core->langOut('relatewithlinkertoobig')." :".$mod->name;
					} else if ($mod->options[CONS_MODULE_PARENT] != '') {
						$using = $core->template->get("_linkermodule_optionstree");
						$sql = $mod->get_base_sql();
						$tree = $mod->getContents("",$mod->title,"");
						$using->getTreeTemplate("_sdirs","_ssubdirs",$tree);
						$core->template->assign("_linkermodule_options");
					} else {
						$core->template->assign("_linkermodule_optionstree");
						$sql = "SELECT id,".$mod->title." as title FROM ".$mod->dbname;
						$core->runContent($mod,$core->template,$sql,"_linkermodule_options");
					}
					break;
				}
			}
		}
		if (!$isLinker) {
			$core->template->assign("_relatewithlinker","");
		}
	}

	############################ LABELS #####################
	// label system
	if (isset($core->loadedPlugins['bi_labels']) && $core->layout == 0) {
		$currentLabels = isset($core->dimconfig['_labels'])?$core->dimconfig['_labels']:array();
		$temp = "";
		$tp = $core->template->get("_label_template");
		$l = 0;
		foreach ($currentLabels as $id => $et) {
			if ($et['module'] == $module->name) {
				$et['id'] = $id;
				$l++;
				$temp .= $tp->techo($et);
			}
		}
		if ($l>0)
			$core->template->assign("_label_template",$temp);
		else
			$core->template->assign("_has_label");
	} else
		$core->template->assign("_has_label");


	############################ FINALIZE AND ECHO #####################

	if ($imageDetected) {
		$core->addLink("shadowbox/shadowbox.css");
		$core->addLink("shadowbox/shadowbox.js");
		$core->template->constants['HEADUSERTAGS'] .= "\n<script type=\"text/javascript\"><!--\nShadowbox.init({skipSetup:true});\n//--></script>";
	}

	// get msi data up
	if (isset($_REQUEST['multiSelectedIds']) && count($_REQUEST['multiSelectedIds'])>0) {
		$core->template->assign("selectedItens",",".$core->langOut('selected_itens').": ".(count($_REQUEST['multiSelectedIds'])));
	}
	$_REQUEST['multiSelectedIds'] = isset($_REQUEST['multiSelectedIds'])?str_replace(",,",",",",".implode(",",$_REQUEST['multiSelectedIds']).","):"";
	$core->template->assign("multiSelectedIds",$_REQUEST['multiSelectedIds']);

	// finish it up
	$core->template->assign("total",$total);
	$core->template->assign("multipage",$total>$psize?"true":"false");

	$core->template->assign("endscripts",$endScript);
