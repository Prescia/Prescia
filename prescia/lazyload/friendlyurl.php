<?/* -------------------------------- Prescia extra core functions
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
  | Translates a URL to another URL using friendly url rules
  | If found, fills $this->storage['friendlyurldata'] and $this->storage['friendlyurlmodule'] with the data from the hit
  | Parameters: module = which module to look for the friendly url
  |				keys = comma separated fields on the module to try and compare the URL
  |				page = which page to foward if we successfully get a match
  |				condition = a condition (to be tested on the module, not sql) for the redirect. For instance, same category, active page, etc
  |				queryfilter = comma separated list of fields that we will perform a comparission from the $_REQUEST
  |				filter = SQL filter to be passed
  |				title = a CKTemplare enabled title to be used as the meta title of the page
  |				metadesc = a CKTemplare enabled meta description to be used as the meta description of the page
  |				metakeys = a CKTemplare enabled meta keywords to be used as the meta keywords of the page
  |
  | Note: you can use data from the returned module by accessing the variables in $this->storage['friendlyurldata'] at your script
  | Returns true|false if a hit for friendlyurl was found
-*/

if (!isset($param['module']))
	$this->errorControl->raise(185,'friendlyurl',"","Module not defined");
if (!isset($param['keys']))
	$this->errorControl->raise(185,'friendlyurl',$param['module'],"Keys not defined");
if (!isset($param['page']))
	$this->errorControl->raise(185,'friendlyurl',$param['module'],"Redirect page not defined");
if (isset($param['condition'])) {
	$condition = explode("=",$param['condition']);
	$testIsEqual = true;
	if ($condition[0][strlen($condition[0])-1] == "!") {
		$testIsEqual = false;
		$condition[0] = str_replace("!","",$condition[0]);
	}
	if ($testIsEqual && ((isset($_REQUEST[$condition[0]]) && $_REQUEST[$condition[0]] != $condition[1]) || (!isset($_REQUEST[$condition[0]]) && $condition[1] != ''))) {
		return false; // failed (wanted the request to be the same, but it does not exist or is different)
	}
	if (!$testIsEqual && isset($_REQUEST[$condition[0]]) && $_REQUEST[$condition[0]] == $condition[1]) {
		return false; // failed (wanted the request to be different, but it is the same)
	}
}

$m = explode(",",$param['module']);
foreach ($m as $moduletxt) {
	$module = $this->loaded($moduletxt);
	if ($module === false)
		$this->errorControl->raise(185,'friendlyur',$moduletxt,"Module not found (multiple)");
	if (isset($param['queryfilter'])) {
		$param['queryfilter'] = explode(",",$param['queryfilter']);
		foreach ($param['queryfilter'] as $field) {
			/*$filterName = str_replace("_",".",$field);
			$queryName = str_replace(".","_",$field);*/
			if (isset($_REQUEST[$field])) {
				if (!isset($param['filter'])) $param['filter'] = $field."=\"".$this->checkHackAttempt($_REQUEST[$field])."\"";
				else $param['filter'] .= " AND ".$field."=\"".$this->checkHackAttempt($_REQUEST[$field])."\"";
			}
		}
	} # queryfilter
	$keys = array();
	foreach ($module->keys as $key)
		$keys[] = $key;
	$keys = implode(",",$keys);

	$fields = explode(",",$param['keys']);
	$sql = $module->get_base_sql("","",1);
	foreach ($fields as $field) {
		if ($module->fields[$field][CONS_XML_TIPO] == CONS_TIPO_INT && is_numeric($this->action))
			// if the mysql field is INT, and one test id = "123_this_is_a_url", it will actually test id=123 duh
			$sql['WHERE'][] = $module->name.".".$field."=\"".$this->action."\"";
		else if ($module->fields[$field][CONS_XML_TIPO] != CONS_TIPO_INT)
			$sql['WHERE'][] = $module->name.".".$field."=\"".$this->action."\"";
	}
	if (isset($param['filter']))
		$sql['WHERE'][] = $param['filter'];


	if ($this->dbo->query($sql,$r,$n) && $n>0) { // found!
		$this->action = $param['page'];
		$result = $this->dbo->fetch_assoc($r);
		foreach ($module->keys as $index)
			$_REQUEST[$index] = $result[$index];

		$this->storage['friendlyurldata'] = $result; // cache the result
		$this->storage['friendlyurlmodule'] = $moduletxt;
		$this->template->constants['CANONICAL'] = "http://".$_SESSION['CANONICAL'].$this->context_str.(isset($result['urla'])?$result['urla'].".html":$this->original_action);

		// fill up title, metas etc
		if (isset($param['title'])) {
			$mytemplate = new CKTemplate($this->template);
			$mytemplate->tbreak($param['title']);
			$this->template->constants['PAGE_TITLE'] = $mytemplate->techo($result);
			unset($mytemplate);
		}
		if (isset($param['metadesc'])) {
			$mytemplate = new CKTemplate($this->template);
			$mytemplate->tbreak($param['metadesc']);
			$this->template->constants['METADESC'] = $mytemplate->techo($result);
			unset($mytemplate);
		}
		if (isset($param['metakeys'])) {
			$mytemplate = new CKTemplate($this->template);
			$mytemplate->tbreak($param['metakeys']);
			$this->template->constants['METAKEYS'] = $mytemplate->techo($result);
			unset($mytemplate);
		}
		return true;
	} else {
		unset($this->storage['friendlyurldata']); // just in case
		unset($this->storage['friendlyurlmodule']); // jic
	}
}
return false;
