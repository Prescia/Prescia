<?	# -------------------------------- SEO Plugin

define ("CONS_SEO_LOADED","aff_seo"); # array of alias that are to be published in the place of default pages
if (CONS_DB_HOST=='') $this->errorControl->raise(4,'bi_seo','SEO module requires database');

class mod_bi_seo extends CscriptedModule  {

	function loadSettings() {
		$this->name = "bi_seo";
		$this->parent->onMeta[] = $this->name;
		$this->parent->onActionCheck[] = $this->name;
		//$this->parent->onRender[] = $this->name;
		//$this->parent->on404[] = $this->name;
		//$this->parent->onShow[] = $this->name;
		//$this->parent->onEcho[] = $this->name;
		//$this->parent->onCron[] = $this->name;
		$this->parent->registerTclass($this,'seo');
		$this->customFields = array('lang');
	}

	function onMeta() {
		if (!isset($this->parent->dimconfig['_seoManager']))
			$this->parent->dimconfig['_seoManager'] = array();  # CACHE list of alias from this plugin
	}

	function onCheckActions() {
		$this->loadSEO(); // loads data into template so the seo tag works
		$context_for_seo = substr($this->parent->context_str,1); // no initial /
		if (isset($this->parent->dimconfig['_seoManager']) && !is_array($this->parent->dimconfig['_seoManager']))
			$this->parent->dimconfig['_seoManager'] = explode(",",$this->parent->dimconfig['_seoManager']);
		
		if (isset($this->parent->dimconfig['_seoManager']) && in_array(strtolower($context_for_seo.$this->parent->action),$this->parent->dimconfig['_seoManager'])) {
			
			$seo = $this->parent->loaded($this->moduleRelation);
			$sql = "SELECT * FROM ".$seo->dbname." WHERE alias=\"".$context_for_seo.$this->parent->action."\" AND lang='".$_SESSION[CONS_SESSION_LANG]."'";

			$this->parent->dbo->query($sql,$r,$n);
			if ($n>0) {
				
				$seo = $this->parent->dbo->fetch_assoc($r);
				if (isset($seo['redirectmode']) && $seo['redirectmode'] != 'normal') {
					$this->parent->headerControl->internalFoward($seo['page'].(strpos($seo['page'],".")===false && $seo['page'][strlen($seo['page'])-1]!='/'?".html":""),$seo['redirectmode']=='sr_temporary'?"307":"301");
					$this->parent->close();
				}
				$this->parent->context = explode("/",$seo['page']);
				$this->parent->action = array_pop($this->parent->context);
				$this->parent->original_action = $this->parent->action; // SEO can only happen to real actions, thus we have to change here or friendlyurl/CMS will fail
				// explode action in case of parameters
				if (strpos($this->parent->action,"?")!==false) {
					$this->parent->action = explode("?",$this->parent->action);
					$this->parent->original_action = array_shift($this->parent->action);
					$params = explode("&",implode("?",$this->parent->action));
					foreach ($params as $p) { // translate queries
						$p = str_replace("amp;","",$p); // if it was amp escaped
						$p = explode("=",$p);
						$_REQUEST[$p[0]] = isset($p[1])?$p[1]:'';
						$_GET[$p[0]] = $_REQUEST[$p[0]];
					}
					$this->parent->action = explode(".",$this->parent->original_action);
					$this->parent->action = array_shift($this->parent->action);
				}
				$this->parent->context_str = implode("/",$this->parent->context)."/";

				if ($this->parent->context_str[0] != "/") $this->parent->context_str = "/".$this->parent->context_str;
				if ($seo['title'] != "") {
					$this->parent->template->constants['PAGE_TITLE'] = $seo['title'];
					$this->parent->storage['LOCKTITLE'] = true;
				}
				if ($seo['meta'] != "") {
					$this->parent->template->constants['METADESC'] = $seo['meta'];
					$this->parent->storage['LOCKDESC'] = true;
				}
				if ($seo['metakey'] != "") {
					$this->parent->template->constants['METAKEYS'] = $seo['metakey'];
					$this->parent->storage['LOCKKEYS'] = true;
				}
				
				
				// treat virtual folder to check if we moved in/out of one
				$this->parent->virtualFolder = false;
				$tempContext = $this->parent->context;
				$strContext = implode("/",$tempContext);
				while (count($tempContext)>1 && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/template".$strContext) && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$strContext) && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/actions".$strContext)){
					array_pop($tempContext);
					$strContext = implode("/",$tempContext);
					$this->parent->virtualFolder = true; // if this remains true, we will 404
				}
				
			}

		}
	}

	function edit_parse($action,&$data) {
		if ($action != CONS_ACTION_DELETE) {
			if (isset($data['alias']) && $data['alias'] != "" && $data['alias'][0] == "/")
				$data['alias'] = substr($data['alias'],1); // no "/" on SEO
			if (isset($data['alias']) &&  $data['alias'] == $data['page']) {
				$this->parent->log[] = "REDIRECT LOOP! you cannot create an alias for the own page with same name";
				$this->parent->setLog(CONS_LOGGING_ERROR);
				return false;
			}
			if ($action==CONS_ACTION_INCLUDE && $data['alias'] == "") {
				$this->parent->log[] = "ALIAS is mandatory";
				$this->parent->setLog(CONS_LOGGING_ERROR);
				return false;
			}
			if (!isset($data['lang'])) $data['lang'] = CONS_DEFAULT_LANG;
		}
		return true;
	}

	function field_interface($field,$action,&$data) {
		if ($field == 'lang') {
			$langs = explode(',',CONS_POSSIBLE_LANGS);
			if (count($langs)==1)
				return false;
			$outfield = "<select name='lang' id='lang'>";
			$langToUse = (isset($data['lang']) && $data['lang'] != '')? $data['lang']:CONS_DEFAULT_LANG;
			foreach ($langs as $lang)
				$outfield .= "<option value='$lang'".(($lang==$langToUse)?" selected='selected'":'').">".$this->parent->langOut($lang)."</option>";
			$outfield .= "</select>";
			return $outfield;
		}
		return true;
	}

	function notifyEvent(&$module,$action,$data,$startedAt="",$earlyNotify =false) {
		if ($module === false) return;
		if ($module->name == $this->moduleRelation && !$earlyNotify) {
			$seo = $this->parent->loaded($this->moduleRelation);
			$sql = "SELECT DISTINCT(alias) FROM ".$seo->dbname;
			$this->parent->dbo->query($sql,$r,$n);
			$this->parent->loadDimconfig(true);
			$newC = array();
			for($c=0;$c<$n;$c++) {
				list($shit) = $this->parent->dbo->fetch_row($r);
				$newC[] = strtolower($shit);
			}
			$this->parent->dimconfig['_seoManager'] = $newC;
			$this->parent->saveConfig();
			unset($_SESSION[CONS_SEO_LOADED]);
		}
	}

	function loadSEO() { // called by checkActions (which BTW is the function that will check SEO redirects)
		# loads session SEO cache (for output data)
		if (isset($this->parent->dimconfig['_seoManager'])) {
			if (isset($_SESSION[CONS_SEO_LOADED]) && !isset($_REQUEST['lang']))
				$this->parent->template->constants = array_merge($this->parent->template->constants,$_SESSION[CONS_SEO_LOADED]);
			else {
				$_SESSION[CONS_SEO_LOADED] = array();
				$seo = $this->parent->loaded($this->moduleRelation);
				$sql = "SELECT page,alias FROM ".$seo->dbname." WHERE publicar='y' AND lang='".$_SESSION[CONS_SESSION_LANG]."'";
				$this->parent->dbo->query($sql,$r,$n);
				for ($c=0;$c<$n;$c++) {
					$dados = $this->parent->dbo->fetch_row($r);
					if ($dados[0] != '' && $dados[0][0] == "/") $dados[0] = substr($dados[0],1); // SEO does not meddle with base path
					if ($dados[1] != '' && $dados[1][0] == "/") $dados[1] = substr($dados[1],1); // SEO does not meddle with base path
					$_SESSION[CONS_SEO_LOADED]['seo_'.$dados[0]] = $dados[1];
				}
				$this->parent->template->constants = array_merge($this->parent->template->constants,$_SESSION[CONS_SEO_LOADED]);
			}
		}
	} # loadSEO

	function tclass($function, $params, $content,$arrayin=false) {
		if ($function == "seo") {
			if (isset($_SESSION[CONS_SEO_LOADED]['seo_'.strtolower($params[0])]))
				$rp = $_SESSION[CONS_SEO_LOADED]['seo_'.strtolower($params[0])];
			else
				$rp = $params[0];
			$rp .= ".html";
			return $rp;
		}
	}

}

?>