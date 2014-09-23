<?	# -------------------------------- BB Plugin

if (CONS_DB_HOST=='') $this->errorControl->raise(4,'bi_bb','Bulleting Board module requires database');
if (!isset($this->loadedPlugins['bi_adm'])) $this->errorControl->raise(4,'bi_bb','Bulleting Board module requires the ADMIN module');

class mod_bi_bb extends CscriptedModule  {

	var $bbfolder = "/bb/"; # add / on both sides. If the forum works at the root, just leave "". Supports multiple folders, for that separate them by a comma (ex "/prescia/,/blog/")
	var $folderfilters = ""; # maps one of the above folders to a forum id (ex.: "1,5" maps the first folder to forum 1, and second to forum 5
	// --
	var $customPermissions = array('can_flag' => 'can_flag'
									);
	public $isBBPage = false; // cache test result
	// --
	private $contextfriendlyfolderlist = array();
	private $bbinuse = 0; # which folderfilter is being used, 0 to none
	private $filter = ""; # filter used on the SQL above

	function loadSettings() {
		$this->name = "bi_bb";
		//$this->parent->onMeta[] = $this->name;
		$this->moduleRelation = "forums"; // this is the name of the metadata module with the data on the blog. Change if necessary
		$this->parent->onActionCheck[] = $this->name;
		$this->parent->onRender[] = $this->name;
		$this->parent->on404[] = $this->name;
		//$this->parent->onShow[] = $this->name;
		//$this->parent->onEcho[] = $this->name;
		//$this->parent->onCron[] = $this->name;
	}

	function onCheckActions() {
		// explode the lists into arrays, checks for / at the end and beginning of folders
		$this->bbfolder = explode(",",$this->bbfolder);
		for ($c=0;$c<count($this->bbfolder);$c++) {
			$this->bbfolder[$c] = trim($this->bbfolder[$c]," /");
			$this->contextfriendlyfolderlist[] = ($this->bbfolder[$c]!=''?"/":"").$this->bbfolder[$c]."/";
		}
		$this->isBBPage = in_array($this->parent->context_str,$this->contextfriendlyfolderlist);
		if ($this->isBBPage) {
			$this->parent->virtualFolder = false; // or we will 404 or serve root data
			$core = &$this->parent;
			//$this->parent->debugFile = CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/_debugarea.html"; // this is our debug area
			$this->folderfilters = explode(",",$this->folderfilters);
			$this->filter = "";
			if (count($this->contextfriendlyfolderlist)>1 && count($this->contextfriendlyfolderlist)==count($this->folderfilters)) {
				for ($c=0;$c<count($this->contextfriendlyfolderlist);$c++) {
					if ($this->parent->context_str == $this->contextfriendlyfolderlist[$c]) {
						$this->bbinuse = $c;
						$this->filter = "forum.id =".$this->folderfilters[$c];
						break;
					}
				}
			}
			$this->filter .= ($this->filter != "" ? " AND " : "")."forum.lang=\"".$_SESSION[CONS_SESSION_LANG]."\"";

			//$this->parent->template->constants['SKIN_PATH'] = CONS_INSTALL_ROOT.CONS_PATH_PAGES."_common/files/bb/skin/".$this->skin."/";
			$this->parent->template->constants['IMG_BBPATH'] = CONS_INSTALL_ROOT.CONS_PATH_PAGES."_common/files/bb/";
			$this->parent->template->constants['BBROOT_PATH'] = substr($this->contextfriendlyfolderlist[0],1);

			if (is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/default.php")) { // default?
				include_once CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/default.php"; // will load template
			}
			if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/actions".$this->parent->context_str.$this->parent->action.".php") && is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/".$this->parent->action.".php")) { // file?
				include_once CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/".$this->parent->action.".php";
			}
		}
	}

	function on404($action, $context = "") { // if we do not copy the blog index.html, use the default
		if ($this->isBBPage) {
			if (is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html"))
				return CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html";
		}
		return false;
	}

	function onRender(){
		if ($this->isBBPage) {
			// we are at a forum page
			$core = &$this->parent;
			$sname = $this->name;

			if ($this->parent->layout != 2) { // cannot use core::frame because we want the full path to avoid loading default files
				if (is_file(CONS_PATH_SYSTEM."plugins/$sname/payload/template/basefile.html")) {
					$frame = CONS_PATH_SYSTEM."plugins/$sname/payload/template/basefile.html";
				} else if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template/basefile.html")) {
					$frame = CONS_PATH_PAGES.$_SESSION['CODE']."/template/basefile.html";
				} else {
					$frame = CONS_PATH_SETTINGS."defaults/basefile.html";
				}

				if (!is_object($this->parent->template)) $this->parent->template = new CKTemplate();
				$this->parent->template->fetch($frame);
				$this->parent->nextContainer = "BASEFILE_CONTENT";
			}

			$this->parent->addScript('bootstrap');

			if (($this->parent->layout == 0 || $this->parent->layout == 3) && $this->parent->nextContainer != '') {

				if (is_file(CONS_PATH_SYSTEM."plugins/$sname/payload/template/frame.html")) {
					$frame = CONS_PATH_SYSTEM."plugins/$sname/payload/template/frame.html";
				} else if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template/frame.html")) {
					$frame = CONS_PATH_PAGES.$_SESSION['CODE']."/template/frame.html";
				}

				$this->parent->template->assignFile($this->parent->nextContainer,$frame);
				$this->parent->nextContainer = "BBCONTENT";
			}

			if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$this->parent->context_str."default.php") && is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/content/default.php")) {
				// default?
				include CONS_PATH_SYSTEM."plugins/".$this->name."/payload/content/default.php";
			}
			if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$this->parent->context_str.$this->parent->action.".php") && is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/content/".$this->parent->action.".php")) {
				// file?
				include CONS_PATH_SYSTEM."plugins/".$this->name."/payload/content/".$this->parent->action.".php";
			}
		}
	}

	function notifyEvent(&$module,$action,$data,$startedAt="",$earlyNofity =false) {
		if (!$earlyNofity &&
			$module->name == "forumpost" &&
			$startedAt != "forumpost" &&
			isset($data['id_forumthread']) &&
			$action != CONS_ACTION_UPDATE) {

			// a post changed in a certain thread
			$this->parent->safety = false;
			$updateData = array("lastupdate" => "NOW()",
								"id" => $data['id_forumthread']);
			$this->parent->runAction('FORUMTHREAD',CONS_ACTION_UPDATE,$updateData,true,'forumpost');
			$this->parent->safety = true;
		}

	}
}

