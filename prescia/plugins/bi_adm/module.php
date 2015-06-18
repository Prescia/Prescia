<?	# -------------------------------- Advanced Admin (Codde's Nekoi), requires prototype/scriptaculous

# NOTE: admFolder in this can be a list of comma delimited folders
# ADMIN MENU: will create normal new/list for modules that are not flagged as systemModule="true"
#             for these (or any normal module) add items to this (or any) administrative pane using the plugin's $admOptions array
#			  The actual administrative menu is read from admin.xml in the _config. You can generate one running at debugmode=true, it will be called admin_suggestion.xml

define ("CONS_ADM_BASESKIN","base"); // which skin is the default skin if none set?
define ("CONS_ADM_ACTIVESKINS","base"); // which skins are active? If the user is using another, will reset to baseskin

define("CONS_FIELD_ORDER","ordem"); // which field can be used to re-order a list (can't be order because of order mysql keyword)
// for the module options
define ("CONS_MODULE_MERGE","cmadmmerge"); # (adm) merge these modules as PART and not RELATED to this module
define ("CONS_MODULE_PUBLIC", "cmadmpublic"); # (adm) If the module have a public site to go, this is the page
define ("CONS_MODULE_PARTOF","cmadmpo"); # (adm) used mostly for interface stacking
define ("CONS_MODULE_CANFILTER","cmadmcf"); # (adm) detects which modules the current module can filter
define ("CONS_MODULE_CANBEFILTERED","cmadmcbf"); #(adm) exact oposite of the above (cache purposes)
define ("CONS_MODULE_LISTING","cmadmlist"); # (adm) default listing fields from admin.xml
define ("CONS_MODULE_TABS","cmadmtabs"); # (adm) allows different list modes (tabs) with pre-defined filters
define ("CONS_MODULE_LISTADD","cmadmla"); # (adm) allows simple list adds on the list pane (even ajax), set this to true. Note that will only allow edit of listing fields
define ("CONS_MODULE_NOADMINPANES","cmadmnap"); # (adm) blocks access to edit or list pane
define ("CONS_MODULE_LISTBUTTONS","cmadmnlb"); # (adm) buttons to show on the admin list pante
define ("CONS_MODULE_CALLBACK","cmadmncb"); # (adm) buttons to show on the admin list pante
define ("CONS_MODULE_DISALLOWMULTIPLE",'cmdm'); # (adm) Prevents the "edit multiple" action

						 	 // variable, tag XML, strtolower?, if is an array, what separates it
$this->moduleOptions[] = array('cmadmmerge','merge',true,","); // strtolower,array separated by ,
$this->moduleOptions[] = array('cmadmpublic','publicpage',false,'');
$this->moduleOptions[] = array('cmadmpo','',false,'');
$this->moduleOptions[] = array('cmadmcf','',false,','); // not in xml but want array
$this->moduleOptions[] = array('cmadmcbf','',false,','); // not in xml but want array
$this->moduleOptions[] = array('cmadmlist','listing',true,',');
$this->moduleOptions[] = array('cmadmtabs',"tabs",false,"|");
$this->moduleOptions[] = array('cmadmla',"listadd",true,"");
$this->moduleOptions[] = array('cmadmnap',"noadminpanes",true,",");
$this->moduleOptions[] = array('cmadmnlb',"listbuttons",false,"|");
$this->moduleOptions[] = array('cmadmncb',"callback",false,"");

// check requirements for this module
if (CONS_DB_HOST=='') $this->errorControl->raise(4,'bi_adm','ADVADM module requires database');
if (!isset($this->loadedPlugins['bi_auth'])) $this->errorControl->raise(4,'bi_adm','ADVADM module requires AUTH module');
if (!CONS_USE_I18N) $this->errorControl->raise(4,'bi_adm','ADVADM module requires i18n system available (even with it has only one language)');

class mod_bi_adm extends CscriptedModule  {

	// config ----
	var $admFolder = 'adm';
	var $testDomainHash = "test"; # these will be considered test domains (instr)
	// internals -----
	var $skin = ''; // gets from dimconfig or userprefs, do not change here
	var $customPermissions = array('can_monitor' => 'can_monitor',
								   'can_undo' => 'can_undo',
								   'can_importexport' => 'can_importexport',
								   'can_fm' => 'can_fm',
								   'can_options' => 'can_options',
								   'can_logs' => 'can_logs'
									);
	private $contextfriendlyfolderlist = array();
	private $menudata = array();
	public $isAdminPage = false; // cache test result
	private $hasUndo = false; // cache for testing if have Undo module
	private $hasStats = false; // cache for testing if have stats module

	function loadSettings() {
		$this->name = "bi_adm";
		$this->parent->onMeta[] = $this->name;
		$this->parent->onActionCheck[] = $this->name;
		$this->parent->onRender[] = $this->name;
		$this->parent->on404[] = $this->name;
		#$this->parent->onShow[] = $this->name;
		#$this->parent->onEcho[] = $this->name;
		#$this->parent->onCron[] = $this->name;
		$this->admRestrictionLevel = 10;

	}

	function onMeta() {

		// check/create admin options on user profile
		if (!isset($this->parent->dimconfig['bi_adm_skin']) || $this->parent->dimconfig['bi_adm_skin'] == '') $this->parent->dimconfig['bi_adm_skin'] = CONS_ADM_BASESKIN;
		if (!isset($this->parent->dimconfig['minlvltooptions']) || $this->parent->dimconfig['minlvltooptions'] == '') $this->parent->dimconfig['minlvltooptions'] = "90";

		// check if each module have some administrative options
		foreach ($this->parent->modules as $mname => &$module) {
			$links = 0;
			$fieldsRequiredToLinks = 0;
			foreach ($module->fields as $name => $field) { // check for linker modules
				if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK && $field[CONS_XML_MODULE] != $mname) { // links to OTHER link not myself
					$links++; # do not count PARENTS as links
					# ADDS THAT THIS LINK CAN BE USED TO FILTER, AND VICE-VERSA
					if (!$module->options[CONS_MODULE_SYSTEM]) { // MANDATORY reomoved because canfilter requires non-mandatory. If you need it for partOf, implement on the other foreach
						$this->parent->modules[$field[CONS_XML_MODULE]]->options[CONS_MODULE_CANFILTER][] = $mname;
						$this->parent->modules[$mname]->options[CONS_MODULE_CANBEFILTERED][] = $field[CONS_XML_MODULE];
					}
					$fieldsRequiredToLinks += count($this->parent->modules[$field[CONS_XML_MODULE]]->keys); # a module can have more than one key, thus to know if this module is a linker module, we need to check if ALL THIS HAVE are the keys for 2 modules
				}
			}
			if (($links == 2 && count($module->fields) == $fieldsRequiredToLinks) || $this->parent->modules[$mname]->linker) { # this is a linker module!
				# FORCES LISTING OF LINKER MODULES TO BE JUST THE LINKED MODULES
				$this->parent->modules[$mname]->options[CONS_MODULE_LISTING] = array();
				foreach ($this->parent->modules[$mname]->keys as $key) {
					$this->parent->modules[$mname]->options[CONS_MODULE_LISTING][] = substr($key,3)."_".$this->parent->modules[$this->parent->modules[$mname]->fields[$key][CONS_XML_MODULE]]->title;
				}
			}
		}

		# CREATE PARTOF
		foreach ($this->parent->modules as $mname => &$module) {
			if (!$module->options[CONS_MODULE_SYSTEM] && !$module->linker && count($module->options[CONS_MODULE_CANFILTER])==1 && !$this->parent->modules[$module->options[CONS_MODULE_CANFILTER][0]]->linker) {
				$this->parent->modules[$mname]->options[CONS_MODULE_PARTOF] = $module->options[CONS_MODULE_CANFILTER][0];
			}
		}

		# DEFAULT WARNING ON IMPROPER MERGE
		if (count($this->parent->modules[$mname]->options[CONS_MODULE_MERGE])>0) {
			foreach ($this->parent->modules[$mname]->options[CONS_MODULE_MERGE] as $m) {
				if (!isset($this->parent->modules[$m])) {
					$this->parent->log[] = "Merged module $m does not exist in $mname";
					$this->parent->setLog(CONS_LOGGING_ERROR);
				}
			}
		}
	}

	function onCheckActions() { // check if we should do something, and also check if we are properly on admin page

		if (!$this->parent->virtualFolder) return; // other module processed the folder. The adm pane is a mandatory virtual folder
	
		// prepare folders (we can have multiple administrative pages)
		$this->admFolder = explode(",",$this->admFolder);
		for ($c=0;$c<count($this->admFolder);$c++) {
			$this->admFolder[$c] = trim($this->admFolder[$c]," /");
			$this->contextfriendlyfolderlist[] = ($this->admFolder[$c]==""?"/":("/".$this->admFolder[$c]."/"));
		}

		// check for default/action on this page
		if (in_array($this->parent->context_str,$this->contextfriendlyfolderlist)) { // are we on the admin?
			$this->isAdminPage = true; // we are on the admin
			$this->parent->virtualFolder = false; // or we will 404 or serve root data
			$this->parent->cachetime = 0;
			$this->parent->cachetimeObj = 0;
			$this->parent->cacheControl->noCache = true;
			########## SAFETY - IT'S HERE ##############
			// if we do not have enough level (not logged, logged with low-level user), force to login page
			if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<$this->admRestrictionLevel) {
				$this->parent->authControl->logsGuest();
				$this->parent->action = "login";
			}
			############################################

			$this->parent->debugFile = CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/_debugarea.html"; // this is our debug area

			// prepare skin
			$this->skin = isset($this->parent->dimconfig['bi_adm_skin']) && $this->parent->dimconfig['bi_adm_skin'] != ''?$this->parent->dimconfig['bi_adm_skin']:CONS_ADM_BASESKIN;
			if (isset($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']) && $_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'] != '') {
				$up = $_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'];
				if (!is_array($up)) $up = @unserialize($up);
				if (is_array($up)) {
					$this->parent->storage['up'] = $up;
					$this->skin = $up['skin'];
				}
			}
			if ($this->parent->action == "login") $this->skin = CONS_ADM_BASESKIN;

			$temp = explode(",",CONS_ADM_ACTIVESKINS);
			if (!in_array($this->skin,$temp)) $this->skin = CONS_ADM_BASESKIN;

			// check if statistics are installed
			if (isset($this->parent->loadedPlugins['bi_stats'])) { # we are aware of bi_stats ;)
				$this->parent->loadedPlugins['bi_stats']->doNotLogMe = true;
				$this->hasStats = true;
			}
			// check if undo module is installed
			if (isset($this->parent->loadedPlugins['bi_undo'])) $this->hasUndo = true;

			$this->parent->cacheControl->noCache = true; // do not cache admin pages!

			$this->parent->template->constants['SKIN_PATH'] = CONS_INSTALL_ROOT.CONS_PATH_PAGES."_common/files/adm/skin/".$this->skin."/";
			$this->parent->template->constants['IMG_ADMPATH'] = CONS_INSTALL_ROOT.CONS_PATH_PAGES."_common/files/adm/";

			// basic ok, check if we have a specific action for the current page
			$core = &$this->parent;
			if (is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/default.php")) { // default?
				include_once CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/default.php"; // will load template
			}
			if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/actions".$this->parent->context_str.$this->parent->action.".php") && is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/".$this->parent->action.".php")) { // file?
				include_once CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/".$this->parent->action.".php";
			}
		}
	}

	function on404($action, $context = "") { // if we do not copy the selected file, use the default
		if ($this->isAdminPage) {
			if (is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/skin/".$this->skin."/$action.html"))
				return CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/skin/".$this->skin."/$action.html";
			else if (is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html"))
				return CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html";
		}
		return false;
	}

	function onRender(){ // if there is no index.php to run the admin, use default
		if ($this->isAdminPage) { // on admin
			// build administrative frame
			$core = &$this->parent;
			if ($this->parent->layout != 2) { // cannot use core::frame because we want the full path to avoid loading default files
				$sname = $this->name;
				if (is_file(CONS_PATH_SYSTEM."plugins/$sname/payload/template/basefile.html")) {
					$frame = CONS_PATH_SYSTEM."plugins/$sname/payload/template/basefile.html";
				} else {
					$frame = CONS_PATH_SETTINGS."defaults/basefile.html";
				}
				if (!is_object($this->parent->template)) $this->parent->template = new CKTemplate();
				$this->parent->template->fetch($frame);
				$this->parent->nextContainer = "BASEFILE_CONTENT";
				$this->parent->addLink(CONS_PATH_PAGES.'_common/files/adm/skin/'.$this->skin."/advadm_body.css");
			}
			// we need basic css/js
			$this->parent->addLink(CONS_PATH_PAGES.'_common/files/adm/skin/'.$this->skin."/advadm.css");
			$this->parent->addLink('prototype.js');
			$this->parent->addLink('scriptaculous/scriptaculous.js');
			$this->parent->addLink('scriptaculous/effectsExtended.js');
			// normal page, display internal frame
			if (($this->parent->layout == 0 || $this->parent->layout == 3) && $this->parent->nextContainer != '') {
				// main frame
				if (is_file(CONS_PATH_SYSTEM."plugins/$sname/payload/template/skin/".$this->skin."/admframe.html")) {
					$frame = CONS_PATH_SYSTEM."plugins/$sname/payload/template/skin/".$this->skin."/admframe.html";
				} else {
					$frame = CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/admframe.html";
				}

				$this->parent->template->assignFile($this->parent->nextContainer,$frame);
				$this->parent->nextContainer = "ADMCONTENT";
				// if not on administrative page, check for menu
				if ($this->parent->action != 'login') {
					if ($this->parent->action == 'index' && $this->parent->debugmode) $this->createAdminSuggestion();
					$this->buildAdminMenu();
				}
			}

			if ($this->parent->action != 'login' && ($this->parent->layout == 0 || $this->parent->layout == 3) && is_object($this->menudata)) {
				if (CONS_CACHE && !$this->parent->debugmode && !isset($_REQUEST['nocache'])) {
					$cached = $this->parent->cacheControl->getCachedContent("bi_adm_admmenu_".$_SESSION[CONS_SESSION_ACCESS_USER]['id_group']);
					if ($cached === false) {
						$this->parent->template->getTreeTemplate("_dirs","_dirs_subdirs",$this->menudata);
						$this->parent->cacheControl->addCachedContent("bi_adm_admmenu_".$_SESSION[CONS_SESSION_ACCESS_USER]['id_group'],$this->parent->template->get("_dirs"),true);
					} else {
						$this->parent->template->assign("_dirs",$cached);
						$this->parent->template->assign("_dirs_subdirs");
					}
				} else{  // no cache system OR nocache requested, just burp it
					$this->parent->template->getTreeTemplate("_dirs","_dirs_subdirs",$this->menudata);
					if (CONS_CACHE) $this->parent->cacheControl->addCachedContent("bi_adm_admmenu_".$_SESSION[CONS_SESSION_ACCESS_USER]['id_group'],$this->parent->template->get("_dirs"),true);
				}
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
	#--

	function onShow() {
	}

	function canEdit($dir) {

		if ($dir == "" || $dir == "/") return true; // can create but not delete root
		if ($dir[0] == "/") $dir = substr($dir,1);

		$dir = explode("/",$dir);

		if (!$this->parent->loaded(array_shift($dir),true))
			return true; // base folder is not a module, ok to edit
		else { // base folder IS a module, ok only if it's a sub dir different from "t"
			if (count($dir) == 0 || array_shift($dir) == "t")
				return false;
			else
				return true;
		}
	}

	function onCron($isDay=false) {

	}

	function createAdminSuggestion() {
		// create a xml suggestion for the admin (not a good suggestion, but good for copy&paste)
		$output = "";
		$this->parent->loadAllModules();
		foreach ($this->parent->modules as $name => $module) {
			if (!$module->linker) {
				$pluginOptions = 0;
				foreach ($module->plugins as $pname) {
					if (count($this->parent->loadedPlugins[$pname]->admOptions) != 0)
					$pluginOptions++;
				}
				if ($pluginOptions==0 && $module->options[CONS_MODULE_SYSTEM]) continue;

				$listing = isset($module->options[CONS_MODULE_LISTING])?implode(",",$module->options[CONS_MODULE_LISTING]):"";
				$listingTotal = 0;
				if ($listing == "") {
					foreach ($module->fields as $fname => $field) {
						if ($field[CONS_XML_TIPO] == CONS_TIPO_TEXT && isset($field[CONS_XML_HTML])) continue; // don't want textearea
						if (!in_array($fname,$module->keys) && $field[CONS_XML_TIPO] != CONS_TIPO_UPLOAD && (!isset($field[CONS_XML_META]) || $field[CONS_XML_META] != "password")) {
							if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK) {
								$rmodule = substr($fname,3); // removes the mandatory id_ in front of links
								if (in_array($rmodule,array("group","from","to","as","having","order","by","join","left","right"))) // reserved words that could cause issues on the SQL
								$rmodule .= "s"; # keyword, add a "s" to prevent it from causing SQL problems
								$listing .= $rmodule."_".$this->parent->modules[$field[CONS_XML_MODULE]]->title.",";
							} else
								$listing .= $fname.",";
							$listingTotal++;
							if ($listingTotal >= 8) break;
						}
					}
				}
				$ao = $this->getAdminOptions($module);
				if ($listing != "" && count($ao)>0) {
					if ($listing[strlen($listing)-1] == ',')
						$listing = substr($listing,0,strlen($listing)-1);
					$output .= "<".strtoupper($name)." icon=\"list\" listing=\"".$listing."\">\n";
					foreach ($ao as $aoname => $action) {
						$output .= "\t<".$aoname.">".$action.(strpos($action,".")===false?".html":"")."</".$aoname.">\n";
					}
					$output .= "</".strtoupper($name).">\n";
				}
			}
		}
		// some scripts might add administrative options without being in a module
		foreach ($this->parent->loadedPlugins as $pname => $pl) {
			if (count($pl->admOptions) != 0 && $pl->moduleRelation == '') {
				$output .= "<".strtoupper($pname)." icon=\"list\">\n";
				foreach ($pl->admOptions as $aoname => $action) {
					$output .= "\t<".$aoname.">".$action.(strpos($action,".")===false?".html":"")."</".$aoname.">\n";
				}
				$output .= "</".strtoupper($pname).">\n";
			}
		}
		if (!CONS_ONSERVER) cWriteFile(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/admin_suggestion.xml",$output);
		if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/admin.xml")) // no actual admin, use this suggestion to avoid errors
			cWriteFile(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/admin.xml",$output);
	}

	function buildAdminMenu() {
		// this function builds the Ttree object for the menu, but does not handle the HTML. The menu stays in the private var $this->menudata

		if (!isset($_SESSION[CONS_SESSION_ACCESS_USER]['id_group'])) return;


		$file = CONS_PATH_CACHE.$_SESSION['CODE']."/admin".$_SESSION[CONS_SESSION_ACCESS_USER]['id_group'].".cache"; // HTML output with normal menu

		if (!is_file($file) || $this->parent->debugmode || isset($_REQUEST['nocache'])) {

			if (is_file($file)) unlink($file);
			if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/admin.xml"))
				$this->parent->errorControl->raise(517,"buildAdminMenu","admin");

			if (!defined('C_XHTML_AUTOTAB')) include CONS_PATH_INCLUDE."xmlHandler.php";
			$xml = new xmlHandler();

			$menuXML = $xml->cReadXML(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/admin.xml",array('C_XML_autoparse' => true, 'C_XML_lax' => true),false);
			$menu = array();

			$this->parent->lockPermissions(); // guarantee permissions are loaded
			$this->addMenuItens($menuXML->getbranch(0),$menu,0,$this->parent);

			if (!function_exists("mysort")) {
				function mysort($a,$b) {
					if ($a['id_parent'] == $b['id_parent']) {
						return ($a['id'] <= $b['id'])?-1:1; // itens on the same level sorted by id
					} else
						return ($a['id_parent'] <= $b['id_parent'])?-1:1; // menus sorted by ID
				}
			}

			usort($menu,'mysort');
			$this->menudata = new TTree();
			$this->menudata->arrayToTree($menu,'\\','id_parent','title');

			// save caches
			cWriteFile($file,serialize($this->menudata)); // <- ttree object


		} else {

			@$this->menudata = unserialize(cReadFile($file));
			if ($this->menudata === false || !is_object($this->menudata)) {
				$this->parent->log[] = "Error loading admin menu";
				$this->parent->setLog(CONS_LOGGING_ERROR);
			}
		}
	}

	function addMenuItens(&$xml,&$menu,$idp,&$core,$inModule="") {
		if ($xml->data[0] != 'xhtml') { // not the BASE node
			$tm = $core->loaded(strtolower($xml->data[0]),true); // check if this is a module
			if (!is_object($tm) && $inModule != '') $tm = $core->loaded($inModule,true); // if not a module, but we are INSIDE a module, inherit it
			if (is_object($tm)) $inModule = $tm->name; // works both ways
			else $inModule = "";
			$permissionTag = strtolower($xml->data[0]) == 'new' ? CONS_ACTION_INCLUDE : (strtolower($xml->data[0]) == 'list' ? true : strtolower($xml->data[0]));
			if ($permissionTag == $inModule || $inModule == '') $permissionTag = true;
			$perm = $inModule == '' || is_object($tm) && $core->authControl->checkPermission($tm,$permissionTag); // permission to see this?
			if ($perm) { // yes, add this item
				// Check the listing options for this module, if any
				if ($xml->data[1] != '') {
					$xml->data[1] = xmlParamsParser($xml->data[1]);
					if (is_object($tm) && isset($xml->data[1]['listing'])) {
						$tm->options[CONS_MODULE_LISTING] = $xml->data[1]['listing'];
					}
				}
				// prepare the item
				$id = 1000 + count($menu) + 1;
				$item = array('id' => $id,
							  'id_parent' => $idp,
							  'title' => $core->langOut($xml->data[0]),
							  'link' => $xml->data[2],
							  'icon' => isset($xml->data[1]['icon'])?$xml->data[1]['icon']:'list'
				);

				if (is_object($tm)) {
					$item['listing'] = isset($tm->options[CONS_MODULE_LISTING])?$tm->options[CONS_MODULE_LISTING]:array();
					$item['module'] = $tm->name;
				}
				// add
				$menu[] = $item;
			}
		} else {
			$id = $idp;
			$perm = true;
		}
		// and recursivelly add all child nodes IF this node was allowed to be seen
		if ($perm) {
			$total = $xml->total();
			for($c=0;$c<$total;$c++) {
				$this->addMenuItens($xml->getbranch($c),$menu,$id,$core,$inModule);
			}
		}
	}

	function getAdminOptions(&$module) {
		$ao = array();
		if (!$module->options[CONS_MODULE_SYSTEM]) {
			if (in_array("edit",$module->options[CONS_MODULE_NOADMINPANES])===false) $ao['new'] = 'edit.php?module='.$module->name;
			if (in_array("list",$module->options[CONS_MODULE_NOADMINPANES])===false) $ao['list'] = 'list.php?module='.$module->name;
		}
		// if this is a gallery, allow MUP
		foreach ($module->fields as $name => $field) {
			if ($field[CONS_XML_TIPO] == CONS_TIPO_UPLOAD && isset($field[CONS_XML_THUMBNAILS]) && isset($field[CONS_XML_MANDATORY])) {
				if (in_array("mup",$module->options[CONS_MODULE_NOADMINPANES])===false) $ao['mup'] = 'edit.php?module='.$module->name."&mup=true";
				break;
			}
		}
		$module->loadPlugins();
		foreach ($module->plugins as $scriptname) {
			foreach ($this->parent->loadedPlugins[$scriptname]->admOptions as $action => $name) {
				$ao[$action] = $name;
			}
		}
		return $ao;
	}

	function getMonitorArray() {
		$monitorXmlCache = CONS_PATH_CACHE.$_SESSION['CODE']."/monitor.cache";
		$monitorXml = array();
		$core = &$this->parent;
		if ($core->debugmode || !is_file($monitorXmlCache)) {
			if (!defined('C_XHTML_AUTOTAB')) include CONS_PATH_INCLUDE."xmlHandler.php";
			$xml = new xmlHandler();
			$xml = $xml->cReadXML(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/monitor.xml",array('C_XML_autoparse' => true, 'C_XML_lax' => true),false);

			if ($xml === false) {
				$core->errorControl->raise(514);
				$monitorXmlCache = array();
			} else {
				# browses the XML and loads modules
				$xml = &$xml->getbranch(0);
				$total = $xml->total();
				for ($c=0;$c<$total;$c++) {
					$thisbranch = &$xml->getbranch($c);
					$total_childs = $thisbranch->total();
					$item = array("xmlname" => strtolower($thisbranch->data[0]));
					for ($cb=0;$cb<$total_childs;$cb++) {
						$temp = $thisbranch->getbranch($cb);
						$item[strtolower($temp->data[0])] = $temp->data[2];
						unset($temp);
					}
					if (isset($item['module']) && isset($item['sql']) && isset($core->modules[strtolower($item['module'])])) {
						$item['module'] = strtolower($item['module']);
						$monitorXml[] = $item;
					} else
					$core->errorControl->raise(515,isset($item['sql'])?$item['sql']:"NO SQL",isset($item['module'])?$item['module']:"NO MODULE");
				}
			}
			unset($xml);
		} else // use cache
			$monitorXml = unserialize(cReadFile($monitorXmlCache));
		return $monitorXml;
	}
}
