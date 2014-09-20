<?	# -------------------------------- Label system

# NOTE: admFolder in this can be a list of comma delimited folders

class mod_bi_labels extends CscriptedModule  {

	// config ----
	var $admFolder = 'adm';
	// internals -----
	var $customPermissions = array('can_editlabels' => 'can_editlabels',
									);
	var $isAdminPage;
	private $contextfriendlyfolderlist = array();
	
	function loadSettings() {
		$this->name = "bi_labels"; 
		//$this->parent->onMeta[] = $this->name;
		$this->parent->onActionCheck[] = $this->name;
		$this->parent->onRender[] = $this->name;
		$this->parent->on404[] = $this->name;
		$this->parent->onShow[] = $this->name;
		//$this->parent->onEcho[] = $this->name;
		//$this->parent->onCron[] = $this->name;
		$this->admRestrictionLevel = 10;
		$this->admOptions = array("config_labels"=>"config_labels.php");
	}

	function onMeta() {
		
	}
	
	function onCheckActions() {
		// prepare folders
		$this->admFolder = explode(",",$this->admFolder);
		for ($c=0;$c<count($this->admFolder);$c++) {
			$this->admFolder[$c] = trim($this->admFolder[$c]," /");
			$this->contextfriendlyfolderlist[] = ($this->admFolder[$c]==""?"/":("/".$this->admFolder[$c]."/"));
		}
		
		// check for default/action on this page
		if (in_array($this->parent->context_str,$this->contextfriendlyfolderlist)) {
			$this->isAdminPage = true;
			$this->parent->cacheControl->noCache = true; // do not cache admin pages!
					
			$core = &$this->parent; 
			if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/actions".$this->parent->context_str."default.php") && is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/default.php")) { // default?
				include CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/default.php";
			}		
			if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/actions".$this->parent->context_str.$this->parent->action.".php") && is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/".$this->parent->action.".php")) { // file?
				include CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/".$this->parent->action.".php";
			}
		}
	}
	
	function on404($action, $context = "") { // if we do not copy the selected file, use the default
		if ($this->isAdminPage) {
			if (is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html"))
				return CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html";
		}
		return false;
	}
	
	function onRender(){ // if there is no index.php to run the admin, use default
		if ($this->isAdminPage) { // on admin
			//$this->parent->addLink(CONS_PATH_PAGES.'_common/files/adm/skin/'.$this->skin."/advadm.css");
			//$this->parent->addLink('prototype.js');
			//$this->parent->addLink('scriptaculous/scriptaculous.js');
			//$this->parent->addLink('scriptaculous/effectsExtended.js');
		}
	}
	
	function onShow() {
		if ($this->isAdminPage) {
			$core = &$this->parent; // php 5.4 namespaces could come in handy now -_-
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
	
}
