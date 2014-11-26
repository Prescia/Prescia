<?	# -------------------------------- BB Plugin

if (CONS_DB_HOST=='') $this->errorControl->raise(4,'bi_bb','Bulleting Board/Blogger module requires database');
if (!isset($this->loadedPlugins['bi_adm'])) $this->errorControl->raise(4,'bi_bb','Bulleting Board/Blogger module requires the ADMIN module');

class mod_bi_bb extends CscriptedModule  {

	########################
	var $bbfolder = "/bb/"; //  If the forum works at the root, just leave "". If you want more than one, use SEO plugin to redirect them here
	var $registrationGroup = 4; # when a new user register, he is put into this group
	var $ignoreTagsSmallerThen = 3; # tags smaller than this number of characters are ignored
	// -- how to display a thread/blog/article
	var $bbpage = "thread"; # TEMPLATE to use as a bb forum (list of threads)
	var $blogpage = "blog"; # TEMPLATE to use as a blog thread (list of blogs)
	var $articlepage = "article"; # TEMPLATE to use as article thread (list of articles)
	// -- following is for the index
	var $blockforumlist = false; # if true, the index will be disabled into just a content manager, w/o the forum list
	var $showlastthreads = 0; # if 0, show none. Otherwise show how much you cho0se here
	var $mainthreadsAsBB = true; # set false to show as articles, not threads
	// -- following for frame
	var $areaname = "forum"; # title of the area, when not provided. Be sure to have i18n tags for it
	var $homename = "home"; # title of the "home" link at the frame
	var $noregistration = false; # set true to disable user registration features
	######################

	var $customPermissions = array('can_flag' => 'can_flag',
								   'can_blog' => 'can_blog', // the standard settings is for every mode. This is just for blog/article (if you disable main permission, this is useless)
								   'can_prop' => 'can_prop'
									);
	public $isBBPage = false; // cache test result
	// --
	private $filter = ""; # filter used on the SQL above

	function loadSettings() {
		$this->name = "bi_bb";
		//$this->parent->onMeta[] = $this->name;
		$this->moduleRelation = "forumthread"; // this is the name of the metadata module with the data on the blog. Change if necessary
		$this->parent->onActionCheck[] = $this->name;
		$this->parent->onRender[] = $this->name;
		$this->parent->on404[] = $this->name;
		//$this->parent->onShow[] = $this->name;
		//$this->parent->onEcho[] = $this->name;
		//$this->parent->onCron[] = $this->name;
	}

	function onCheckActions() {
		
		if (isset($this->parent->loadedPlugins['bi_adm']) && $this->parent->loadedPlugins['bi_adm']->isAdminPage) return; // bi_adm captured first

		$this->bbfolder = trim($this->bbfolder," /");
		$this->bbfolder = ($this->bbfolder!=''?"/":"").$this->bbfolder."/";
		$this->isBBPage = $this->bbfolder == substr($this->parent->context_str,0,strlen($this->bbfolder));

		if ($this->isBBPage) {
						
			$core = &$this->parent;
			
			$oa = explode(".",$this->parent->original_action);
			$oa = array_shift($oa);
			if (($oa == "index" || $oa == '') && $this->parent->action == "_cms") $this->parent->action = "index"; // CMS captured the page, give it back!
			if ($this->noregistration && ($this->parent->action == "login" || $this->parent->action == "profile" || $this->parent->action == "preview")) $this->parent->action = "index";

			$this->filter = "forum.lang=\"".$_SESSION[CONS_SESSION_LANG]."\"";

			$this->parent->template->constants['IMG_BBPATH'] = CONS_INSTALL_ROOT.CONS_PATH_PAGES."_common/files/bb/";
			$this->parent->template->constants['BBROOT_PATH'] = substr($this->bbfolder,1);

			include_once CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/default.php";

			if ($this->bbfolder != '/') $this->parent->virtualFolder = false; // or we will 404 or serve root data (after default because we handle UDM there)

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

			$oa = explode(".",$this->parent->original_action);
			$oa = array_shift($oa);
			if (($oa == "index" || $oa == '') && $this->parent->action == "_cms") $this->parent->action = "index"; // CMS captured the page, give it back!

			if ($this->parent->layout != 2) { // cannot use core::frame because we want the full path to avoid loading default files
				if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template/".$this->bbfolder."basefile.html")) {
					$frame = CONS_PATH_PAGES.$_SESSION['CODE']."/template/".$this->bbfolder."basefile.html";
				} else if (is_file(CONS_PATH_SYSTEM."plugins/$sname/payload/template/basefile.html")) {
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


			if (($this->parent->layout == 0 || $this->parent->layout == 3) && $this->parent->nextContainer != '') {

				if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template/".$this->bbfolder."frame.html")) {
					$frame = CONS_PATH_PAGES.$_SESSION['CODE']."/template/".$this->bbfolder."frame.html";
				} else if (is_file(CONS_PATH_SYSTEM."plugins/$sname/payload/template/frame.html")) {
					$frame = CONS_PATH_SYSTEM."plugins/$sname/payload/template/frame.html";
				} else if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template/frame.html")) {
					$frame = CONS_PATH_PAGES.$_SESSION['CODE']."/template/frame.html";
				}

				$this->parent->template->assignFile($this->parent->nextContainer,$frame);
				$this->parent->nextContainer = "BBCONTENT";
			}

			include CONS_PATH_SYSTEM."plugins/".$this->name."/payload/content/default.php";
						
			if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$this->parent->context_str.$this->parent->action.".php") && is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/content/".$this->parent->action.".php")) {
				// file?
				include CONS_PATH_SYSTEM."plugins/".$this->name."/payload/content/".$this->parent->action.".php";
			}
		}
	}

	function notifyEvent(&$module,$action,$data,$startedAt="",$earlyNotify =false) {
		if (!$earlyNotify &&
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
		if (!$earlyNotify && $module->name == "forum" && isset($data['id_parent']) && $data['id_parent'] != 0 && $action != CONS_ACTION_DELETE) {
			// we changed a forum that have a parent. We will FORCE same language and operation mode as parent
			$objForum = $this->parent->loaded('forum');
			if ($this->parent->dbo->query("SELECT lang,operationmode FROM ".$objForum->dbname." WHERE id=".$data['id_parent'],$r,$n) && $n>0) {
				list($lang,$om) = $this->parent->dbo->fetch_row($r);
				if ((isset($data['lang']) && $data['lang'] != $lang) ||
				    (isset($data['operationmode']) && $data['operationmode'] != $om))
					$this->parent->log[] = $this->parent->langOut("force_lang_and_om_to_parent");
				$ok = $this->parent->dbo->simpleQuery("UPDATE ".$objForum->dbname." SET lang='$lang', operationmode='$om' WHERE id=".$data['id']);

			}
		}

	}

	function getTags($filter="") { # Filter is an SQL where statement
		# tag sizes 0 ~ 4
		$TAGS = array();
		$maxTAG = 1;
		$mod = $this->parent->loaded($this->moduleRelation);
		$sql = "SELECT ".$mod->name.".tags FROM ".$mod->dbname." as ".$mod->name." WHERE ".$mod->name.".tags<>''".($filter != ""?" AND ".$filter:"");
		$this->parent->dbo->query($sql,$r,$n);
		for ($c=0;$c<$n;$c++) {
			list($ttags) = $this->parent->dbo->fetch_row($r);
			$ttags = multiexplode(array(" ",',',';'),strtolower($ttags));
			foreach ($ttags as $tag) {
				if (strlen($tag)>=$this->ignoreTagsSmallerThen) {
					if (isset($TAGS[$tag]))
						$TAGS[$tag]++;
					else
						$TAGS[$tag] = 1;
					if ($TAGS[$tag]>$maxTAG) $maxTAG =$TAGS[$tag];
				}
			}
		}
		foreach ($TAGS as $tag => $count) {
			$TAGS[$tag] = ($count<$maxTAG/5)?0:(($count<2*$maxTAG/5)?1:(($count<3*$maxTAG/5)?2:(($count<4*$maxTAG/5)?3:4)));
		}

		return $TAGS;
	}

	function getArchieveDates($filter="") { # Filter is an SQL where statement
		$mod = $this->parent->loaded($this->moduleRelation);
		$sql = "SELECT ".$mod->name.".date FROM ".$mod->dbname." as ".$mod->name." ".($filter!=""?"WHERE $filter":"")." ORDER BY ".$mod->name.".date DESC";
		$result = array();
		$this->parent->dbo->query($sql,$r,$n);
		for($c=0;$c<$n;$c++) {
			list($date) = $this->parent->dbo->fetch_row($r);
			$YM = substr($date,0,4).substr($date,5,2);
			if (!isset($result[$YM]))
				$result[$YM] = array('month' => substr($date,5,2), 'year' => substr($date,0,4), 'monthname' => 'month'.substr($date,5,2));
		}
		return $result;
	}

	function countMessages($filterOnlyNew=false) { # get number of messages on inbox
		if (!$this->parent->logged()) return false;
		$mod = $this->parent->loaded('bbmail');
		$sql = "SELECT count(*) FROM ".$mod->dbname." WHERE outbox='n' AND id_recipient=".$_SESSION[CONS_SESSION_ACCESS_USER]['id'];
		if ($filterOnlyNew) $sql .= " AND dateseen<>'0000-00-00 00:00:00'";
		return $this->parent->dbo->fetch($sql);
	}
}

