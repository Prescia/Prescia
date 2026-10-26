<?php

/** @var CPrescia $this Core context that includes this lazy-load from checkActions(). */

/* this file is captured by Prescia to fill a Select Query, as per the functions on common.js:

	HTML triggers the request to fill a SELECT -> startAjaxSelectFill -> THIS FILE
	THIS FILE -> executeajaxSelectFill -> select is filled as requested
 
	For example, this should be the select with the CITIES of a STATE, triggered by the choice of a STATE
 
	REQUEST queries received (built in startAjaxSelectFill):
	  MANDATORY:
		container = which field this is being filled (in the example, where the CITIES select will be placed)
   		[keys] = key names of the current module that will filter out the SELECT, note that it can also come in listadd (preceeded by la_) for admin. (in the example, the WHERE keys with the STATE to look inside the CITY list)
		module = module this is all about (in the example, the CITIES module)
		sourcemodule = from which module the keys (filters) came from, so we can translate. Can be '' to no translation, but must be present (in the example, the STATE module so we can add "choose a state" if nothing found)
		aoc = true|false to put onChange="selectChange()" inside the SELECT (in the example, the CITY SELECT would have the onChange so it can also trigger other selects, imagine a 3-way selects with country->state->city)
   	  OPTIONAL:
   	    preSelected = id of pre-selected item (in the example, the id of the CITY that is already selected)
   	    className = class name of the SELECT (in the example, a class to be appended to the HTML of the SELECT)
   		widthValue = style width of the SELECT (in the example, a numeric width (px or %) that will be added on style="width:xxx")
   		allowEmpty = true (if present (always true), allows an empty option as first item) (in the example, means the first option is nothing (the SELECT allows you not to choose one))

	The results will be filled in a span named [field]_ara (ajax return area) <- the container

	This script runs on layout=2, startAjaxSelectFill should send layout=2 but be sure to enforce
	This script is called (captured) by core::checkActions

	#######################
	WARNING: works only with modules with one key
	#######################
---*/

	$this->layout = 2; // enforce ajax mode
	$this->ignore404 = true; // enforce 404 should not be auto-generated (though this script will auto close)

	// load request items and check for mandatory fields
	$container = isset($_GET['container']) && is_scalar($_GET['container'])?(string)$_GET['container']:'';
	$module = isset($_GET['module'])?$_GET['module']:''; // the module where all the fields and filters are at (but NOT the one which will be listed)
	$module = $this->loaded($module);
	$sm = isset($_GET['sourcemodule'])?$_GET['sourcemodule']:'';
	if ($sm != '') $sm = $this->loaded($sm);
	$aoc = isset($_GET['aoc'])?$_GET['aoc']=='true':false;
		if ($container == '' || $module == '' || $module === false || $sm === false) {
			$this->log[] = "ajaxQuery with incomplete fields came, query was: ".arrayToString($_GET);
			$safeContainer = htmlspecialchars($container,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');
			echo "<select id=\"$safeContainer\" name=\"$safeContainer\"><option>".$this->langOut("select_other_field")."</option></select> (error)";
			$this->close(true);
		}
		if (!$this->authControl->checkPermission($module,CONS_ACTION_SELECT)) {
			$this->close(true);
		}
	$preSelected = isset($_GET['preSelected']) && is_scalar($_GET['preSelected'])?(string)$_GET['preSelected']:'';
	$className = isset($_GET['className']) && is_scalar($_GET['className'])?(string)$_GET['className']:'';
	$widthValue = isset($_GET['widthValue']) && is_scalar($_GET['widthValue'])?(string)$_GET['widthValue']:'';
	$allowEmpty = isset($_GET['allowEmpty']);
	$preparedTypes = '';
	$preparedParams = array();

	// prepare SQL to run this select
	$sql = $module->get_base_sql();
	$sql['SELECT'][] = $module->name.".".$module->title." as title";
	if (!isset($module->fields['id']))
		$sql['SELECT'][] = $module->name.".".$module->keys[0]." as id";
		if ($preSelected != '')
			$sql['SELECT'][] = "if (".$module->name.".".$module->keys[0]."=?,1,0) as selected";
		if ($preSelected != '') {
			$preparedTypes .= 's';
			$preparedParams[] = $preSelected;
		}

	// locate filters and put into sql
	if ($sm == '' || $sm === false) {
		// no translation
		foreach ($module->fields as $fname => $mfield)
				if (isset($_GET[$fname]) && is_scalar($_GET[$fname]) && $_GET[$fname] != '')
					{
						$sql['WHERE'][] = $module->name.".".$fname."=?";
						$preparedTypes .= isset($mfield[CONS_XML_TIPO]) && $mfield[CONS_XML_TIPO] == CONS_TIPO_INT ? 'i' : 's';
						$preparedParams[] = isset($mfield[CONS_XML_TIPO]) && $mfield[CONS_XML_TIPO] == CONS_TIPO_INT ? (int)$_GET[$fname] : (string)$_GET[$fname];
					}
	} else {
		$remoteWhere = '';
		$remoteTypes = '';
		$remoteParams = array();
		$remoteData = $_GET;
		$whereReady = $sm->getRemotePreparedKeys($module,$remoteWhere,$remoteTypes,$remoteParams,$remoteData);
		if (!$whereReady) {
			// no filter? weird. Do we have listadd keys?
			$newlist = array();
			foreach ($_GET as $gname => $gitem) {
				if (substr($gname,0,3) == "la_")
					$newlist[substr($gname,3)] = $gitem;
				else
					$newlist[$gname] = $gitem;
			}
			$whereReady = $sm->getRemotePreparedKeys($module,$remoteWhere,$remoteTypes,$remoteParams,$newlist);
		}
		if ($whereReady && $remoteWhere != '') {
			$sql['WHERE'][] = $remoteWhere;
			$preparedTypes .= $remoteTypes;
			$preparedParams = array_merge($preparedParams,$remoteParams);
		}
	}
	$sql['_preparedTypes'] = $preparedTypes;
	$sql['_preparedParams'] = $preparedParams;

	// prepare template
	$tp = new CKTemplate($this->template);
	$safeContainer = htmlspecialchars($container,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');
	$tp->tbreak("<select id=\"$safeContainer\" name=\"$safeContainer\" {extras}>".($allowEmpty?"<option value=''></option>":"")."{_options}<option {selected|selected} value=\"{id}\">{title}</option>{/options}</select>");
	$extras = "";
	if ($aoc) $extras .= "onChange=\"selectChange();\" ";
	if ($className != "" && preg_match('/^[A-Za-z0-9 _-]+$/',$className)) $extras .= "class=\"".htmlspecialchars($className,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8')."\" ";
	if ($widthValue != "" && preg_match('/^[0-9]+(?:\.[0-9]+)?(?:px|%)$/i',$widthValue)) $extras .= "style=\"width:".htmlspecialchars($widthValue,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8')."\" ";
	$tp->assign("extras",$extras);
	// fill select
		$total = $module->runContent($tp,$sql,"_options",false,false,false);

	if($total == 0)
		echo "<select id=\"$safeContainer\" name=\"$safeContainer\" $extras><option>".$this->langOut("select_other_field")."</option></select>";
	else
		echo $tp->techo();

	$this->close(true);
