<?/* -------------------------------- Prescia Template Extra Classes
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
-*/

class CKTCexternal {

	var $parent = null;

	function __construct(&$parent) {
		$this->parent = &$parent;
		if (!in_array('vdir',$this->parent->template->varToClass))
			$this->parent->template->varToClass[] = 'vdir';
		if (!in_array('query_strings',$this->parent->template->varToClass))
			$this->parent->template->varToClass[] = 'query_strings';
	}

	function runclass($function, $params, $content,$arrayin=false) {
		switch ($function) {
			case "vdir":
				# {vdir|path} - will remove paths if using domainTranslation or any different context.
				# path should be the FULL path FROM ROOT (ignoring CONS_INSTALL_ROOT)
				# WILL ADD CONS_INSTALL_ROOT if necessary
				$vdir = "";
				if ($this->parent->forceVDIRTL && count($this->parent->languageTL)>1) {
					foreach ($this->parent->languageTL as $f => $l) {
						if ($l == $_SESSION[CONS_SESSION_LANG]) {
							$vdir = $f."/";
							break;
						}
					}
				}
				
				if (!isset($params[0]) || $params[0]=='' || $params[0]=='/') return CONS_INSTALL_ROOT.$vdir;
				if ($params[0] != '' && substr($params[0],0,strlen(CONS_INSTALL_ROOT)) != CONS_INSTALL_ROOT) $params[0] = CONS_INSTALL_ROOT.$vdir.$params[0];
				else if ($vdir != '') $params[0] = CONS_INSTALL_ROOT.$vdir.substr($params[0],strlen(CONS_INSTALL_ROOT));
				$params[0] = preg_replace("@/{1,}@","/",$params[0]);
				return $params[0];
			break;
			case "query_strings": # {query_strings} or {query_strings|comma separated list of query items to exclude}
				if (!isset($params[0]) || $params[0]=='') $itemsToExclude = array();
				else $itemsToExclude = explode(",",$params[0]);
				if (!in_array("haveinfo",$itemsToExclude)) $itemsToExclude[] = "haveinfo";
				if (!in_array("debugmode",$itemsToExclude)) $itemsToExclude[] = "debugmode";
				if (!in_array("nocache",$itemsToExclude)) $itemsToExclude[] = "nocache";
				if (!in_array("nosession",$itemsToExclude)) $itemsToExclude[] = "nosession";
				$qs = arrayToString(false,$itemsToExclude);
				return "?".$qs;
			break;
			default:
				if (isset($this->parent->tClass[$function]))
					return $this->parent->loadedPlugins[$this->parent->tClass[$function]]->tclass($function,$params,$content,$arrayin);
			break;
		}
		return $content;
	}

}

