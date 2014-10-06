<?/* -------------------------------- Prescia URL Dispatch Manager (UDM)
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
  | This will translate virtual folders to queries on the root folder
  | The first parameters is a list (array) of parameters for each folder, in decreasing order (closest to filename first)
  | Each parameter is: module = where to look for the URL
  |					   key = which key to use while looking for URL (accepts only one)
  |					   convertquery = if found, fill this $_REQUEST with the MAIN key
  |						  if there are more than one main key, will fill as an array
  |					   fillqueries = comma delimited list of fields to also fill into $_REQUEST with data form the database match
  |					   filter = SQL filter to be added
  | Second parameter: if we do not consume all virtual folders, ignore it and consider it processed
-*/

	if ($this->virtualFolder) {
		$vFn = 0;
		$tempContext = $this->context;
		$strContext = implode("/",$tempContext);

		while (count($tempContext)>1 && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$strContext) && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/template".$strContext)){
			$vF = str_replace("%20"," ",$this->checkHackAttempt(array_pop($tempContext)));
			$strContext = implode("/",$tempContext);

			$param[$vFn]['module'] = strtolower($param[$vFn]['module']);
			$module = $this->loaded($param[$vFn]['module']);
			if (!$module) $this->errorControl->raise(186,$param[$vFn]['module'],"UDM","Module not found on entry ".$vFn);
			if (!isset($param[$vFn]['key'])) $this->errorControl->raise(186,"","UDM","Key not defined on entry ".$vFn);
			else $param[$vFn]['key'] = strtolower($param[$vFn]['key']);
			if (!isset($param[$vFn]['convertquery'])) $this->errorControl->raise(186,"","UDM","Convertquery not defined on entry ".$vFn);
			else $param[$vFn]['convertquery'] = strtolower($param[$vFn]['convertquery']);
			if (!isset($module->fields[$param[$vFn]['key']])) $this->errorControl->raise(186,$param[$vFn]['key'],"UDM","Field not found (".$param[$vFn]['key'].") on entry ".$vFn);

			$sql = $module->get_base_sql($param[$vFn]['module'].".".$param[$vFn]['key']." =\"".$vF."\"".(isset($param[$vFn]['filter'])?" AND (".$param[$vFn]['filter'].")":""),"",1);

			if ($this->dbo->query($sql,$r,$n) && $n>0) { // found!

				$result = $this->dbo->fetch_assoc($r);
				$keys = array();
				foreach ($module->keys as $index) {
					$keys[] = $result[$index];
				}
				$_REQUEST[$param[$vFn]['convertquery']] = count($keys)==1?array_pop($keys):$keys;
				if (isset($param[$vFn]['fillqueries'])) {
					$fq = explode(',',$param[$vFn]['fillqueries']);
					foreach ($fq as $fqv) {
						$_REQUEST[$fqv] = $result[$fqv];
					}
				}
			} else {
				return false;
			}
			$vFn++;
			if (count($param)==$vFn) break; // we parsed all data
		}

		if (count($tempContext)>1 && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$strContext) && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/template".$strContext)) {
			// we still are in virtual folders, despite running sucessfully all data
			return $ignorePreVF;
		}
		return true;
	}

