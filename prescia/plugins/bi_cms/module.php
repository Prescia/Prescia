<?	# -------------------------------- CMS plugin
	# Contentman tags start as {CONTENTMAN} for code 1, then {CONTENTMAN#} where # > 1

if (CONS_DB_HOST=='') $this->errorControl->raise(4,'bi_cms','CMS module requires database');

class mod_bi_cms extends CscriptedModule  {

	private $cmstree = false; // internal cache
	private $cmscache = false; // internal cache
	private $serveThisPage = ""; // what page to serve

	function loadSettings() {
		$this->name = "bi_cms";
		$this->parent->onMeta[] = $this->name;
		$this->parent->onActionCheck[] = $this->name;
		//$this->parent->onRender[] = $this->name;
		$this->parent->on404[] = $this->name;
		$this->parent->onShow[] = $this->name;
		//$this->parent->onCron[] = $this->name;
		$this->customFields = array('lang');
	}

	function onMeta() {
		if (!isset($this->parent->dimconfig['_contentManager']))
			$this->parent->dimconfig['_contentManager'] = array();  # CACHE list of pages that will trigger auto area change (maintained by Area module)
	}

	function onCheckActions() {
		if ($this->parent->debugmode) { // save all CMS data into contentManager config
			$this->rebuildCM();
			$cm = $this->parent->loaded($this->moduleRelation);
			$sql = "SELECT DISTINCT(page) FROM ".$cm->dbname;
			$this->parent->dbo->query($sql,$r,$n);
			$this->parent->loadDimconfig(true);
			$newC = array();
			for($c=0;$c<$n;$c++) {
				list($newC[]) = $this->parent->dbo->fetch_row($r);
			}
			$this->parent->dimconfig['_contentManager'] = implode(",",$newC);
			$this->parent->saveConfig();
		}
		if (!is_array($this->parent->dimconfig['_contentManager']))
			$this->parent->dimconfig['_contentManager'] = explode(",",$this->parent->dimconfig['_contentManager']);
		if (in_array($this->parent->context_str.$this->parent->action,$this->parent->dimconfig['_contentManager'])) {
			if ($this->getentry()!==false) {
				$this->serveThisPage = $this->parent->context_str.$this->parent->action;
				if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template".$this->parent->context_str.$this->parent->action.".html")) {
					$this->parent->action = "_cms";
				}
				$this->parent->ignore404 = true; // if you don't disable this, will 404 because of virtual folder/unknown file as soon as it leaves here
			}
		}
	}

	function onShow(){
		if ($this->serveThisPage != '') {
			$n = count($this->cmscache);
			for ($c=0;$c<$n;$c++) {
				list($id,$content,$header,$code,$title,$meta,$mk,$page) = $this->cmscache[$c];
				if ($code==1) {
					$this->autoBreadcrubs($id);
					$this->parent->template->assign("CONTENTMAN",$content);
					$this->parent->template->assign("CONTENTMAN_TITLE",$title);
					if ($header != "" && !isset($this->parent->storage['LOCKTITLE'])) {
						$this->parent->template->constants['PAGE_TITLE'] = $header;
						$this->parent->storage['LOCKTITLE'] = true;
					}
					if ($meta != "" && !isset($this->parent->storage['LOCKDESC'])) {
						$this->parent->template->constants['METADESC'] = $meta;
						$this->parent->storage['LOCKDESC'] = true;
					}
					if ($mk != "" && !isset($this->parent->storage['LOCKKEYS'])) {
						$this->parent->template->constants['METAKEYS'] = $mk;
						$this->parent->storage['LOCKKEYS'] = true;
					}
				} else
					$this->parent->template->assign("CONTENTMAN".$code,$content);
			}
		}
	}

	function edit_parse($action,&$data) {
		if ($action != CONS_ACTION_DELETE) { // tests code and page
			if (isset($data['code']) && $data['code'] == "") $data['code'] = 1;
			if (isset($data['page'])) {
				$folders = explode("/",$data['page']);
				$data['page'] = array();
				foreach ($folders as $f)
					$data['page'][] = removeSimbols($f,true,false);
				$data['page'] = implode("/",$data['page']);
				if ($data['page'][0] != "/") $data['page'] = "/".$data['page'];
			}
			if (!isset($data['lang'])) $data['lang'] = CONS_DEFAULT_LANG;
			// checks if there is some non-unique item with keys code,page,lang (old version used these as keys, but changed to simplify parenting)
			if (isset($data['code']) && isset($data['lang']) && isset($data['page'])) {
				$cm = $this->parent->loaded($this->moduleRelation);
				$sql = "SELECT count(*) FROM ".$cm->dbname." WHERE code='".$data['code']."' AND page='".$data['page']."' AND lang='".$data['lang']."'".($action==CONS_ACTION_INCLUDE?"":" AND id<>'".$data['id']."'");
				if ($this->parent->dbo->fetch($sql)>0) {
					$this->parent->errorControl->raise(520,$data['page'],$this->moduleRelation,$this->parent->langOut('cms_repeated_keys')." ".$data['code'].",".$data['lang']);
					return false;
				}
			}

		} else if ($_SESSION[CONS_SESSION_ACCESS_LEVEL] < 100) { // cannot DELETE an item marked as LOCKED
			$cm = $this->parent->loaded($this->moduleRelation);
			$sql = "SELECT locked FROM ".$cm->dbname." WHERE id=".$data['id'];
			$l = $this->parent->dbo->fetch($sql);
			if ($l=='y') {
				$this->parent->errorControl->raise(521,$data['id'],$this->moduleRelation);
				return false;
			}

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

	function notifyEvent(&$module,$action,$data,$startedAt="",$earlyNofity =false) {
		if ($module === false) return;
		if ($module->name == $this->moduleRelation && !$earlyNofity) {
			$cm = $this->parent->loaded($this->moduleRelation);
			$sql = "SELECT DISTINCT(page) FROM ".$cm->dbname." WHERE publish='y'";
			$this->parent->dbo->query($sql,$r,$n);
			$this->parent->loadDimconfig(true);
			$newC = array();
			for($c=0;$c<$n;$c++) {
				list($newC[]) = $this->parent->dbo->fetch_row($r);
			}
			$this->parent->dimconfig['_contentManager'] = implode(",",$newC);
			$this->parent->saveConfig();
		}
	}

	function on404($action, $context = "") {
		if ($action == "_cms") {
			// if there is no _cms on the folder, serve the one at the root
			if ($context[strlen($context)-1] == "/") $context = substr($context,0,strlen($context)-1);
			$context = explode("/",$context);
			array_pop($context);
			$context = implode("/",$context);
			if (is_file(CONS_PATH_PAGES.$_SESSION["CODE"]."/template/".$context."/_cms.html")) {
				return CONS_PATH_PAGES.$_SESSION["CODE"]."/template/".$context."/_cms.html";
			}
		}
		return false;
	}

	function rebuildCM() {
		$files = listFiles(CONS_PATH_PAGES.$_SESSION['CODE']."/template/",'/^(.*)\.htm(l)?$/i',false,false,true);
		$cm = $this->parent->loaded($this->moduleRelation);
		$possibleLangs = CONS_USE_I18N?explode(",",CONS_POSSIBLE_LANGS):array(CONS_DEFAULT_LANG);
		foreach ($files as $file) {
			if ($file != "_cms.html") {
				$content = cReadFile(CONS_PATH_PAGES.$_SESSION['CODE']."/template/$file");
				$filewoext = explode(".",$file);
				array_pop($filewoext);
				$filewoext = implode(".",$filewoext);
				if (strpos($content,"{CONTENTMAN}") !== false) {
					$sql = "SELECT page FROM ".$cm->dbname." WHERE code=1 AND page=\"/$filewoext\"";
					$id = $this->parent->dbo->fetch($sql);
					if ($id === false) {
						foreach ($possibleLangs as $lang)
							if ($lang != '')
								$this->parent->dbo->simpleQuery("INSERT INTO ".$cm->dbname." SET code=1,page=\"/$filewoext\",title=\"$filewoext\",content=\"Content Manager\", lang='".$lang."'");
					}
				}
				$c=2;
				while ($c<10) {
					if (strpos($content,"{CONTENTMAN".$c."}") !== false) {
						$sql = "SELECT page FROM ".$cm->dbname." WHERE code=$c AND page=\"/$filewoext\"";
						$id = $this->parent->dbo->fetch($sql);
						if ($id === false) {
							foreach ($possibleLangs as $lang)
								if ($lang != '')
									$this->parent->dbo->simpleQuery("INSERT INTO ".$cm->dbname." SET code=$c,page=\"/$filewoext\",title=\"$filewoext $c\",content=\"Content Manager\", lang='".$lang."'");
						}
					}
					$c++;
				}
			}
		}
	}

	// public functions:

	// builds a menu structure inside $dt/$sdt from $id_parent (CACHED)
	function buildMenu($dt,$sdt,$id_parent=0,$parenttag="",$selected=0) {
		// check html cache
		$cached = $this->parent->cacheControl->getCachedContent($this->parent->action."cms".$dt.$sdt.$id_parent.$parenttag.$selected);
		if ($cached !== false) { // have cache, echo and leave
			if ($parenttag != '') {
				$subtemplate = &$this->parent->template->get($parenttag);
				$subtemplate->assign($dt,$cached);
				$subtemplate->assign($sdt);
			} else {
				$this->parent->template->assign($dt,$cached);
				$this->parent->template->assign($sdt);
			}
			return;
		}
		// not in html cache, check if we have the cms tree cahed
		if ($this->cmstree === false) {
			// no, make it
			$cm = $this->parent->loaded($this->moduleRelation);
			$this->cmstree = $cm->getContents();
			function removeNull($tree) {
				if (isset($tree->data['page']) && ($tree->data['page'] == '/'))
					$tree->data['page'] = "#";
				$t = $tree->total();
				for ($c=0;$c<$t;$c++)
					removeNull($tree->branchs[$c]);
			}
			removeNull($this->cmstree);
		}
		$cmt = clone $this->cmstree;
		if ($selected !=0) $cmt->selectWholeBranch($selected);
		// build/echo output
		if ($parenttag != '') {
			$subtemplate = &$this->parent->template->get($parenttag);
			$subtemplate->getTreeTemplate($dt,$sdt,$cmt,$id_parent);
			$cached = $subtemplate->gettxt($dt);
		} else {
			$this->parent->template->getTreeTemplate($dt,$sdt,$cmt,$id_parent);
			$cached = $this->parent->template->gettxt($dt);
		}
		$this->parent->cacheControl->addCachedContent($this->parent->action."cms".$dt.$sdt.$id_parent.$parenttag.$selected,$cached,true);
	}

	// builds Breadcrubs where each item is $tag
	function autoBreadcrubs($id) { // called by onShow
		$tag = $this->parent->template->get("_breadcrubs");
		if ($tag === false) return;
		$cached = $this->parent->cacheControl->getCachedContent($this->parent->action."breadcrubs".$id);
		if ($cached !== false) {
			// have cache, echo and leave
			$this->parent->template->assign("_breadcrubs",$cached);
			return;
		}
		// not in html cache, check if we have the cms tree cached
		if ($this->cmstree === false) {
			// no, make it
			$cm = $this->parent->loaded($this->moduleRelation);
			$this->cmstree = $cm->getContents();
			function removeNull($tree) {
				if (isset($tree->data['page']) && ($tree->data['page'] == '/'))
				$tree->data['page'] = "#";
				$t = $tree->total();
				for ($c=0;$c<$t;$c++)
				removeNull($tree->branchs[$c]);
			}
			removeNull($this->cmstree);
		}
		$cmt = clone $this->cmstree;
		// build/echo output
		$temp = "";
		$current = $cmt->getbranchById($id);
		if ($current !== false) {
			$temp = $tag->techo($current->data,array("_hasnext")); // adds me (final, does not have next)
			while (is_object($current->parent) && $current->parent->data['id'] != 0) { // while we have a parent
				$current = &$current->parent; // get the parent
				$temp = $tag->techo($current->data).$temp; // display
			}
		}
		$this->parent->template->assign("_breadcrubs",$temp);
		$this->parent->cacheControl->addCachedContent($this->parent->action."breadcrubs".$id,$temp,true);
	}

	function getentry() { // gets which CMS to show, if any, or FALSE. Fill the rest of cmscache if other CMS's from the same page
		if (!is_array($this->parent->dimconfig['_contentManager']))
			$this->parent->dimconfig['_contentManager'] = explode(",",$this->parent->dimconfig['_contentManager']);
		if ($this->serveThisPage != '' || in_array($this->parent->context_str.$this->parent->action,$this->parent->dimconfig['_contentManager'])) {
			if ($this->cmscache !== false) return $this->cmscache[0];
			$cm = $this->parent->loaded($this->moduleRelation);
			$this->serveThisPage = $this->serveThisPage != '' ? $this->serveThisPage : $this->parent->context_str.$this->parent->action;
			$sql = "SELECT id,content,header,code,title,meta,metakeys,page FROM ".$cm->dbname." WHERE page='".$this->serveThisPage."' AND lang='".$_SESSION[CONS_SESSION_LANG]."' ORDER BY code ASC";
			if ($this->parent->dbo->query($sql,$r,$n) && $n>0) {
				$this->cmscache = array();
				for ($c=0;$c<$n;$c++)
					$this->cmscache[] = $this->parent->dbo->fetch_row($r);
				return $this->cmscache[0];
			} else
				return false;
		} else
			return false;
	}

	function getparent($id) { // return the parent data (other than 0) from this entry
		$cm = $this->parent->loaded($this->moduleRelation);
		$sql = "SELECT id,id_parent,content,header,code,title,meta,metakeys,page FROM ".$cm->dbname." WHERE id=$id";
		while ($this->parent->dbo->query($sql,$r,$n) && $n>0) {
			$data = $this->parent->dbo->fetch_assoc($r);
			if ($data['id_parent'] == '0') return $data;
			$sql = "SELECT id,id_parent,content,header,code,title,meta,metakeys,page FROM ".$cm->dbname." WHERE id=".$data['id_parent']." AND code=1";
		}
		return false;
	}

}

