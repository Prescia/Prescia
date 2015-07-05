<?/* -------------------------------- Prescia URL Dispatch Manager (UDM)
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
  | This will translate virtual folders to queries on the root folder
  | The first parameters is a list (array) of parameters for each folder, in decreasing order (closest to filename first)
  | Each parameter is: module = where to look for the URL
  |						key = which key to use while looking for URL (accepts only one)
  |						convertquery = if found, fill this $_REQUEST with the MAIN key
  |						  if there are more than one main key, will fill as an array
  |						fillqueries = comma delimited list of fields to also fill into $_REQUEST with data form the database match
  |						filter = SQL filter to be added
  |						treemode = true|false, if true, before parsing the next step, will check if it is a parent from the current step (might consume all)
  |								in this case, the returned valid id's are form the first hit (alas, closest to filename)
  |								example: "alpha/beta/gama/filename.html" with all hits on the same module on treemode, will fill the data of "gama" AS LONG as the tree is correct ("beta" is parent for "gama", "alpha" is parent for "beta")
  |						treeoffset = a number of how many virtual folders are NOT part of the tree
  |								example: "alpha/beta/gama/filename.html" with a treeoffset 0, alpha/beta/gama must validate
  |									same example, treeoffset 1, only beta/gama must validate
  |								set this to how many virtual folders you will treat PAST the tree structure so it can create a propper sql
  | Second parameter: if we do not consume all virtual folders, ignore it and consider it processed
-*/

	$matched = false;
	if ($this->virtualFolder) {
		#$this->warning[] = "UDM start";
		$vFn = 0;
		$tempContext = $this->context;
		$strContext = implode("/",$tempContext);
		
		// while the current folder does not exist (alas, is a virtual folder)
		while (count($tempContext)>1 && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$strContext) && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/template".$strContext)){
			$vF = str_replace("%20"," ",$this->checkHackAttempt(array_pop($tempContext))); // vF is the current virtual Folder
			$strContext = implode("/",$tempContext); // next context w/o this (notice $tempContext updated above already)
			$isTree = isset($param[$vFn]['treemode']); // this is a tree mode, so the NEXT folder could be treated like this IF it is a parent for this
			$treeOffset = isset($param[$vFn]['treeoffset'])?$param[$vFn]['treeoffset']:0;
			
			// set up configuration for this vF parameters
			$param[$vFn]['module'] = strtolower($param[$vFn]['module']);
			$module = $this->loaded($param[$vFn]['module']);
			if (!$module) $this->errorControl->raise(186,$param[$vFn]['module'],"UDM","Module not found on entry ".$vFn);
			if (!isset($param[$vFn]['key'])) $this->errorControl->raise(186,"","UDM","Key not defined on entry ".$vFn);
			else $param[$vFn]['key'] = strtolower($param[$vFn]['key']);
			if (!isset($param[$vFn]['convertquery'])) $this->errorControl->raise(186,"","UDM","Convertquery not defined on entry ".$vFn);
			else $param[$vFn]['convertquery'] = strtolower($param[$vFn]['convertquery']);
			if (!isset($module->fields[$param[$vFn]['key']])) $this->errorControl->raise(186,$param[$vFn]['key'],"UDM","Field not found (".$param[$vFn]['key'].") on entry ".$vFn);

			if ($isTree) { // we will consume 1 or more folders according to how many are available
				
				if ($treeOffset>0 && count($tempContext)<=$treeOffset) {
					// we have LESS folders than necessary to treat this, because the offset tells us to ignore some!
					return false;
				}
				$vF = array($vF);
				while (count($tempContext)>$treeOffset+1 && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$strContext) && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/template".$strContext)){
					$vF[] = str_replace("%20"," ",$this->checkHackAttempt(array_pop($tempContext))); // vF is the current virtual Folder
					$strContext = implode("/",$tempContext); // next context w/o this (notice $tempContext updated above already)
				}
				// $vF now have all the folders that must be validated by this tree (alas, we can add the necessary SQL checks
				// get BASE sql (first check is the top-level folder
				$sql = $module->get_base_sql($param[$vFn]['module'].".".$param[$vFn]['key']." =\"".$vF[0]."\"".(isset($param[$vFn]['filter'])?" AND (".$param[$vFn]['filter'].")":""),"",1,true); // true so it does not add the parent, we will force it ... better would to join everything EXCEPT parent though
				for ($processed=1;$processed<count($vF);$processed++) {
					// add extra tests for parent
					$sql['FROM'][] = $module->dbname." as ".$module->name."_p".$processed; // parent 1..2..3
					$sql['WHERE'][] = $module->name."_p".$processed.".id = ".($processed==1?$module->name.".id_parent":$module->name."_p".($processed-1).".id_parent")."
									AND ".$module->name."_p".$processed.".".$param[$vFn]['key']." = \"".$vF[1]."\""; 
				}
				#$this->warning[] = "UDM keys = ".vardump($vF);
			} else {
				// get the proper (non-tree)SQL
				$sql = $module->get_base_sql($param[$vFn]['module'].".".$param[$vFn]['key']." =\"".$vF."\"".(isset($param[$vFn]['filter'])?" AND (".$param[$vFn]['filter'].")":""),"",1);
				#$this->warning[] = "UDM key = $vF";
			}
			
			$n=-1;
			if ($this->dbo->query($sql,$r,$n) && $n>0) { // found!
				$matched = true;
				if ($n>1) {
					// can't determine which, considers NOT found
					$this->warning[] = "Too many UDM results";
					return false;
				} else { // 1 result
					#$this->warning[] = "UDM ok";
					$result = $this->dbo->fetch_assoc($r);
					$keys = array();
					foreach ($module->keys as $index) {
						$keys[] = $result[$index];
					}
					$_REQUEST[$param[$vFn]['convertquery']] = count($keys)>=1?array_shift($keys):$keys;
					if (isset($param[$vFn]['fillqueries'])) {
						$fq = explode(',',$param[$vFn]['fillqueries']);
						foreach ($fq as $fqv) {
							$_REQUEST[$fqv] = $result[$fqv];
						}
					}
					// rebuild context w/o this, disable virtual mode
					$this->context = $tempContext;
					$this->context_str = $strContext == ''?'/':'';
					$this->virtualFolder = false;
				}
			} else {
				#$this->warning[] = "No UDM (".($n==-1?"error":"zero")."): ".vardump($sql);
				return false;
			}
			$results = array(); // resets treemode
			$vFn++;
			if (count($param)==$vFn) break; // we parsed all data
		}

		if (count($tempContext)>1 && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$strContext) && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/template".$strContext)) {
			// we still are in virtual folders, despite running sucessfully all data
			$this->virtualFolder = !$ignorePreVF;
			return $ignorePreVF;
		}
		return $matched;
	}
	return $matched;

