<?/* -------------------------------- Prescia Core (non-debug)
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ for Prescia
  | This code is optimized, and as such, please check core.php.txt for comments and documentation (line by line)
-*/

class CPrescia extends CPresciaVar {

  	function __construct(&$dbo, $debugmode=false) {
  		$this->dbo = &$dbo;
  		$this->debugmode = $debugmode;
  		$this->errorControl = new CErrorControl($this);
  		$this->headerControl = new CHeaderControl($this);
  		$this->authControl = new CauthControl($this); # CauthControl is a simple/empty interface, to be replaced if you use an auth system
  		$this->cacheControl = new CCacheControl($this); # CCacheControl is loaded even if cache is offline, some sites might EXPLICITLY use object caching
  		$this->intlControl = new CintlControl($this); # CintlControl is also loaded to provide single-language handling
  		$this->dbo->quickmode = !$debugmode;
  		$this->maintenanceMode = CONS_ONSERVER && is_file("maint.txt"); # Creating a maint.txt file on root will display it's contents on the debug area, telling your site is on maintenance for instance
  	} # __construct
#-
	function domainLoad() { # DOMAINLOAD → parseRequest -> loadIntlControl -> checkActions -> renderPage -> showTemplate
		# Checks from the HTTP_HOST (or SERVER_NAME) which site is being accessed
		# Uses a session cache to speed this up, though the domain file is also cached for speed
		# NOTE this will load the site config.php after selecting the site code.

		$uri = explode(":",isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME']);
		$this->domain = array_shift($uri); # removes protocol

		if (CONS_SINGLEDOMAIN != '') { # single domain? use it
			$_SESSION["DOMAIN"] = $this->domain;
			$_SESSION['CODE'] = CONS_SINGLEDOMAIN;
		} else if (!CONS_ONSERVER && CONS_SITESELECTOR) { # multiple domain, if on production and we can select domain manually ...
			if (isset($_REQUEST['nocache'])) $domainList = $this->builddomains();
			if (isset($_REQUEST['changelocalsite']) && isset($_REQUEST['nosession'])) { # new domain arrived! switch everything to this domain
				$_COOKIE['prescia_cls'] = $_REQUEST['changelocalsite'];
				setcookie('prescia_cls',$_REQUEST['changelocalsite'],time()+86400);
			} else if (!isset($_COOKIE['prescia_cls']) || (isset($_REQUEST['prescia_cls']) && isset($_REQUEST['debugmode']) && isset($_REQUEST['nosession']))) { # no domain selected or requested domain change, chose domain selector
				include_once CONS_PATH_SYSTEM."lazyload/cls.php";
				die();
			}
			if (isset($_COOKIE['prescia_cls'])) { # we have a domain set, use it
				$_SESSION['CODE'] = $_COOKIE['prescia_cls'];
				$_SESSION['DOMAIN'] = $this->domain;
			}
		}
		if (!isset($_SESSION['CODE']) || !isset($_SESSION['DOMAIN']) || $_SESSION['DOMAIN'] != $this->domain || (isset($_REQUEST['nocache']) && !isset($_COOKIE['prescia_cls']))) { # no data, different domain or forced reload
			$errCode = 102;
			$hasFile = is_file(CONS_PATH_CACHE."domains.dat");
			if ($hasFile) $domainList = unserialize(cReadFile(CONS_PATH_CACHE."domains.dat"));
			if (isset($_REQUEST['nocache']) || !$hasFile || $domainList===false) { # This is a cached script. Performance is mandatory
				$errCode = 101;
				$domainList = $this->builddomains();
			}
			if (isset($domainList[$this->domain])) $_SESSION['CODE'] = $domainList[$this->domain];
			if (!isset($_SESSION['CODE']) || !is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/config.php")) {
				$this->log[]= "Registered domains: ".count($domainList);
				$this->errorControl->raise($errCode,$this->domain,"",!isset($_SESSION['CODE'])?'Domain not registered':'config.php not found (CODE: '.$_SESSION['CODE'].")");
			}
			$_SESSION["DOMAIN"] = $this->domain;
		}

		# Loads the selected domain/site configuration file
		require CONS_PATH_PAGES.$_SESSION['CODE']."/_config/config.php";
		if (CONS_USE_I18N) { # Checks which language we will serve control (IF ENABLED)
			if (isset($_REQUEST['lang']) && !isset($_REQUEST['haveinfo']) && strpos(CONS_POSSIBLE_LANGS.",",$_REQUEST['lang'].",") !== false) {
				$_SESSION[CONS_SESSION_LANG] = $_REQUEST['lang'];
			}
			$_SESSION[CONS_SESSION_LANG] = $this->intlControl->loadLocale(isset($_SESSION[CONS_SESSION_LANG])?$_SESSION[CONS_SESSION_LANG]:CONS_DEFAULT_LANG);
		} else
			$_SESSION[CONS_SESSION_LANG] = CONS_DEFAULT_LANG;
	} # domainlock
#-
	function dbconnect() { # coreFull will override
	 	# Performs the connection with the database (with usually one retry)
	 	# Handle database down situations as per config (ignore and try caches, or total abort)
 		# Also handle situations where the site does not have a database (dbless)
		if (CONS_DB_HOST != '') {
			if (!$this->dbo->connect(1,CONS_OVERRIDE_DB==''?CONS_DB_HOST:CONS_OVERRIDE_DB,CONS_OVERRIDE_DBUSER==''?CONS_DB_USER:CONS_OVERRIDE_DBUSER,CONS_OVERRIDE_DBPASS==''?CONS_DB_PASS:CONS_OVERRIDE_DBPASS,CONS_DB_BASE)) {
				$this->errorControl->raise(CONS_HIDE_MYSQLDOWN?104:105,array_pop($this->dbo->log)); // error 104 should abort
				return false;
			}
		} else
			$this->dbless = true;
		return true;
	} # dbconnect
#-
	function loadMetadata() { # coreFull will override
		# Loads dinamic config and metadata config from caches, also load plugin scripts
		# If the metadata cache is corrupt, can attempt to redirect to debugmode (rebuild it)
		$this->loadDimconfig(isset($_REQUEST['nocache']));
		if (isset($_REQUEST['nocache'])) {
			$this->cacheControl->dumpTemplateCaches();
		}
		if (!$this->dbless) {
			$theModules = unserialize(cReadFile(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/_modules.dat"));
			if ($theModules === false) {
				# try again, this might be a parallel access issue
				sleep(1);
				$theModules = unserialize(cReadFile(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/_modules.dat"));
			}
			if ($theModules === false) {
				$this->errorControl->raise(106);
				if (!isset($_GET['debugmode'])) $this->headerControl->internalFoward('index.php?debugmode=true'); // _modules.dat might be corrupt, force a debugmode (really wishful thinking here though)
				return false;
			}
			foreach ($theModules as $module) {
				# metadata ONLY (name,dbname and plugin)
				$this->loadModule($module[0],$module[1]);
				$this->modules[$module[0]]->plugins = $module[2];
				if ($module[2] != "") $this->modules[$module[0]]->loadPlugins();
			}
		}
		return true;
	} # loadMetadata
#-
	function langOut($tag) {
		# Returns the translation given the current i18n (if enabled) of a hash string
		if (!defined("CONS_USE_I18N") || !CONS_USE_I18N || !isset($this->intlControl)) return $tag;
		return $this->intlControl->langOut($tag);
	} # langOut
#-
	function parseRequest() { # domainLoad → PARSEREQUEST -> loadIntlControl -> checkActions -> renderPage -> showTemplate
		# Treats the URI and detects context and action (page) being accessed.
		# Also handle several exceptions, optimization and security issues related to URI

		list($this->context,$this->action,$this->original_action,$ext) = extractUri(CONS_INSTALL_ROOT);

		if (!isset($_SESSION[CONS_SESSION_NOROBOTS])) { # norobots controller
			$_SESSION[CONS_SESSION_NOROBOTS] = (strpos(",".CONS_NOROBOTDOMAINS.",",$this->domain) !== false ||
											  strpos(",".CONS_NOROBOTDOMAINS.",",str_replace("www.","",$this->domain)) !== false);
		}
		if ($_SESSION[CONS_SESSION_NOROBOTS]) {
			$this->headerControl->addHeader("X-Robots-Tag", "X-Robots-Tag: noindex"); # How to avoid robots, as specified by Google
		}

		# LAYOUT CONTROLER -- (need early for the bot control)
		# 0 = normal, 1 = no frame, 2 = ajax (.ajax or .xml to force), = mobile (.mob to force)
		$this->layout = isset($_REQUEST['layout'])?(int)$_REQUEST['layout']:0;
		if ($ext == "ajax" || $ext == "xml") $this->layout = 2;
		else if ($ext == "mob" || (CONS_BROWSER_ISMOB && !isset($_SESSION['NOMOBVER']))) $this->layout = 3;
		if (!is_numeric($this->layout) || $this->layout<0 || $this->layout>3) $this->layout = 0;
		if ($this->layout==3 && isset($_REQUEST['desktopversion'])) {
			$_SESSION['NOMOBVER'] = true;
			$this->layout = 0;
		}
		if ($this->layout==0 && isset($_REQUEST['mobileversion'])) {
			unset($_SESSION['NOMOBVER']);
			$this->layout = 3;
		}

		# anti-bot (basically a anti-DOS tool)
		if (CONS_BOTPROTECT && ($this->layout != 2 || !$this->noBotProtectOnAjax)) require CONS_PATH_SYSTEM."lazyload/botprotect.php";

		# domainTranslator (allows you to translate a sub-domain to a folder)
		if (count($this->domainTranslator)>0) { # (TODO: test)
			if (isset($this->domainTranslator[$this->domain]) && $this->domainTranslator[$this->domain] != '') {
				array_shift($this->context);
				if (count($this->context)>0) {
					$folder = array_shift($this->context);
					if ($folder == 'm' || (CONS_USE_I18N && isset($this->languageTL[$folder]))) { # mobile or language translator folder tag
						array_unshift($this->context,trim($this->domainTranslator[$this->domain],"/"));
						array_unshift($this->context,$folder);
					} else {
						array_unshift($this->context,$folder);
						array_unshift($this->context,trim($this->domainTranslator[$this->domain],"/"));
					}
				} else
					array_unshift($this->context,trim($this->domainTranslator[$this->domain],"/"));
				array_unshift($this->context,"");
			}
		}

		# builds proper context and action data
		$this->context_str = implode("/",$this->context)."/";
		if ($this->context_str[0] != "/") $this->context_str = "/".$this->context_str;
		if ($this->action == "") $this->action = "index";
		$this->original_context_str = $this->context_str; # storage of original call in case script redirects us, also used by stats

		# special cases (robots,favicon, sitemap)
		if ($this->context_str == "/") {
			if ($this->original_action == "robots.txt" || $this->original_action == "robot.txt") {
				if ($_SESSION[CONS_SESSION_NOROBOTS])
					$this->readfile("robotsno.txt","txt",true,"robots.txt");
				else if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/files/robots.txt")) # allows personalized robots.txt
					$this->readfile(CONS_PATH_PAGES.$_SESSION['CODE']."/files/robots.txt","txt",true,"robots.txt");
				else if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template/sitemap.xml"))
					$this->readfile("robotssm.txt","txt",true,"robots.txt");
				else
					$this->readfile("robots.txt","txt",true,"robots.txt");
			} else if ($this->action == "favicon") {
				$favfile = CONS_PATH_PAGES.$_SESSION['CODE']."/files/favicon";
				# favicon requested, regardless of what extension was requested, serve the one we have
				if (locateFile($favfile,$ext,"png,jpg,gif,ico"))
					$this->readfile($favfile,$ext,true);
				else if (CONS_DEFAULT_FAVICON) {
					$favfile = "favicon";
					if (locateFile($favfile,$ext))
						$this->readfile($favfile,$ext,true);
				} else
					$this->fastClose(404);
			} else if ($this->original_action == "sitemap.xml" || $this->original_action == "sitemap.xml") {
				if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template/sitemap.xml"))
					$this->readfile(CONS_PATH_PAGES.$_SESSION['CODE']."/template/sitemap.xml","txt",true,"sitemap.xml");
				else
					$this->fastClose(404);
			}
		}

		# Redirect root files to file manager, if enabled
		if (CONS_ACCEPT_DIRECTLINK && count($this->context)>1 && $this->context[1] == "files") {
			$this->context_str = "/".CONS_FMANAGER;
			for ($c=2;$c<count($this->context);$c++) {
				$this->context_str .= $this->context[$c]."/";
			}
			$this->context = explode("/",$this->context_str);
		}
		if (substr($this->context_str,0,strlen("/".CONS_FMANAGER)) == "/".CONS_FMANAGER) {
			# Serve a file from the file manager.
			# Avoid using the short URL notation (files/), use full path (pages/[code]/files/)
			# The reason this should be avoided is that during readfile, PHP will allocate the file into memory for flushing, so you can see what would happen if one tries to download a huge file
			if (!CONS_ACCEPT_DIRECTLINK) $this->errorControl->raise(171,$theFile);
			$theFile = $this->context_str.$this->original_action;
			$theFile = subStr($theFile,1);
			$ext = strtolower($ext);
			if (!is_file($theFile)) $this->fastClose(404);
			$this->close(false); # shuts down database
			$this->readfile($theFile,$ext,true,$this->original_action);
			$this->close(true); # should abort script if readfile didn't
			return;
		}

		# you cannot have an action named default, alas it's the same as index!
		if ($this->action == "default") $this->action = "index";

		# in the event we have log messages stored on session from a previous hit/redirection, load them now
		if ($this->layout != 2) {
			if (isset($_SESSION[CONS_SESSION_LOG]) && count($_SESSION[CONS_SESSION_LOG])>0) {
				$this->log = $_SESSION[CONS_SESSION_LOG];
				$_SESSION[CONS_SESSION_LOG] = array();
				if (isset($_SESSION[CONS_SESSION_LOG_REQ]) && count($_SESSION[CONS_SESSION_LOG_REQ])>0) {
					$_REQUEST = $_SESSION[CONS_SESSION_LOG_REQ];
				}
				$_SESSION[CONS_SESSION_LOG_REQ] = array();
			} else if (isset($_SESSION[CONS_SESSION_LOG_REQ]))
				unset($_SESSION[CONS_SESSION_LOG_REQ]);
		}

		# translate FOLDER to FOLDER/index.html
		if (strpos($this->original_action,".") === false && $this->action != "" && !is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template".$this->context_str.$this->action.".html") && is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template".$this->context_str.$this->action."/index.html")) {
			$this->context_str .= $this->action."/";
			$this->context_str = substr($this->context_str,1);
			$this->context[] = $this->action;
			$this->action = "index";
			$this->headerControl->internalFoward(CONS_INSTALL_ROOT.$this->context_str);
		}
	} # parseRequest
#-
	/* loadIntlControl
	* domainLoad → parseRequest -> LOADINTLCONTROL -> checkActions -> renderPage -> showTemplate
	* will load language from url (languageTL) on top of existing language settings, then apply all language settings
	*/
	function loadIntlControl() {
		if (CONS_USE_I18N) {
			# Language translator?
			if (count($this->languageTL)>0 && count($this->context)>1) { # have a language translator AND is in a subfolder
				# this will allow subfolders with the language, for instance: site.com/en and site.com/pt-br will redirect to root while setting the proper language
				if (isset($this->languageTL[$this->context[1]])) { # is in a language context
					$temp = $this->context[1];
					$_SESSION[CONS_SESSION_LANG] = $this->languageTL[$this->context[1]]; // get the language from the folder
					array_shift($this->context); # root
					array_shift($this->context); # language
					array_unshift($this->context,""); # puts back root
					$this->context_str = substr($this->context_str,strlen($temp)+1);
				}
			}

			# loads main locale settings for this language
			$this->intlControl->loadLangFile($_SESSION[CONS_SESSION_LANG],true);
			$this->template->std_date = $this->intlControl->getDate();
		    $this->template->std_datetime = "H:i ".$this->intlControl->getDate();
		    $this->template->std_decimal = $this->intlControl->getDec();
		    $this->template->std_tseparator = $this->intlControl->getTSep();

		    # loads site locale settings for this language
		    if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/locale/".$_SESSION[CONS_SESSION_LANG].".php"))
				$this->intlControl->loadLangFile($_SESSION[CONS_SESSION_LANG],false);

		    # loads plugin locale settings for this language
			foreach ($this->loadedPlugins as $pname => &$pObj) { // plugins loaded directly (loaded in config.php, not related to a module)
				$this->intlControl->loadLangFile($_SESSION[CONS_SESSION_LANG],true,$pname);
			}
		}
	}
#-
	/* cronCheck
	 * The framework simulates a see-thru cron system believing it has at least one hit every hour (usually way more)
	 * ALSO based on the Uncertainty Principle by Werner Heisenberg (using Schrödinger's cat as base too), we should only need to update/clean up when someone hits the site, not every time
	 * ALAS a tree only "exists" if there is someone to see it: the cron only needs to run if someone visits the site
	 * This function checks if it's time to run the next cron check, it handles concurrent requests or failed cron runs
	 */
	function cronCheck() {
		if ($this->maintenanceMode) return;
		if (!isset($this->dimconfig['_cronD'])) $this->dimconfig['_cronD'] = "0";
		if (!isset($this->dimconfig['_cronH'])) $this->dimconfig['_cronH'] = "0";
		$forceCron = isset($_REQUEST['forcecron']) && $_SESSION[CONS_SESSION_ACCESS_LEVEL] == 100; # lvl 100 tested on SessionStart
		if ($forceCron || date("d") != $this->dimconfig['_cronD'] || date("H") != $this->dimconfig['_cronH']) { # will have cron
			# should run cron, however our dimconfig is a CACHED config == reload
			$this->loadDimconfig(true);
			if (!isset($this->dimconfig['_cronD'])) $this->dimconfig['_cronD'] = "0";
			if (!isset($this->dimconfig['_cronH'])) $this->dimconfig['_cronH'] = "0";
			# another instance is already running the cron for this client?
			if (is_file(CONS_PATH_CACHE.$_SESSION['CODE']."/cronlock.php")) {
				include_once CONS_PATH_CACHE.$_SESSION['CODE']."/cronlock.php"; # yes, for how long?
				$now = date("Y-m-d H:i:s");
				if (isset($cronLock)) $runTime = time_diff($now,$cronLock); // cronlock came from cronlock.php
				else {
					$runTime = 60;
					$cronLock = "CORRUPT";
				}
				if ($runTime<60)
					return; # not even 60s passed, not a bugged cron? abort
				$this->errorControl->raise(109,$cronLock);
			}
			// lock cron if not locked
			cWriteFile(CONS_PATH_CACHE.$_SESSION['CODE']."/cronlock.php",'<? $cronLock="'.date("Y-m-d H:i:s").'"; ?>');
		} else return;

		include_once CONS_PATH_SYSTEM."lazyload/cron.php";

		@unlink(CONS_PATH_CACHE.$_SESSION['CODE']."/cronlock.php"); # unlock cron
		$this->saveConfig(true);

	} # cronCheck
#-
	/* close
	 * Frees up most of the memory as if the script is about to end (if $stop set, it actually does)
	 * Usefull for situations where you expect delay (processing a file or whatever)
	 * calling a dbconnect after this should enable you to resume working with the system transparently
	 */
	function close($stop = true) {
		unset($this->template);
		if ($this->dbo->errorRaised) $this->errorControl->raise(606,"","core",vardump($this->dbo->log));
		$this->dbo->close();
		if ($stop) exit();
	} # close
#-
	/* addPlugin
	 * Loads up a plugin into the loadedPlugins array, with possible relation to a database module
	 * To have the same plugin loaded twice, use renamePluginTo parameter (which will turn mandatory the relateToModule, so the same plugin cannot be loaded twice on the same module)
	 * Plugins can be inside /pages/[site]/_config/plugins/
	 */
	function addPlugin($script,$relateToModule="",$renamePluginTo="",$noRaise=false) {

		if ($renamePluginTo != "") {
			if ($relateToModule=="") {
				$this->errorControl->raise(5,'Related Module required',$script,"Renamed to: $renamePluginTo");
			}
			$scriptname = $renamePluginTo;
		} else
			$scriptname = $script;

		if (is_file(CONS_PATH_SYSTEM."plugins/$script/module.php")) {

			include_once(CONS_PATH_SYSTEM."plugins/$script/module.php");
			$scriptclass = "mod_".$script;
			$this->loadedPlugins[$scriptname] = new $scriptclass($this,$relateToModule);
			return $this->loadedPlugins[$scriptname];

		} else if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/plugins/$script/module.php")) {

			include_once(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/plugins/$script/module.php");
			$scriptclass = "mod_".$script;
			$this->loadedPlugins[$scriptname] = new $scriptclass($this,$relateToModule);
			return $this->loadedPlugins[$scriptname];

		} else if (!$noRaise)
			$this->errorControl->raise(2,'Plugin not found',$script,"alias: $scriptname");
		else
			return false;
	} # addPlugin
#--
	function addMeta($str) {
		$this->template->constants['METATAGS'] .= $str."\n";
	}
#--
	/* addLink
	 * Finds a javascript or CSS file in the core _js framework, files folder or full path, and adds the proper link/script tag in the header
	 * Preceed will add the file first on the current list
	 */
	function addLink($file,$preceed=false) {
		$isJS = preg_match("/^(.*)\.js(\?.*)?$/i",$file) == 1;

		if ($isJS) {
			$tfile = explode("?",$file);
			$tfile = $tfile[0];
			if (is_file(CONS_PATH_JSFRAMEWORK.$tfile))
				$file = CONS_PATH_JSFRAMEWORK.$file;
			else if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/files/".$tfile))
				$file = CONS_PATH_PAGES.$_SESSION['CODE']."/files/".$file;
			else if (!is_file($file)) {
				$this->errorControl->raise(8,'Javascript not found',$file,"addLink");
				return false;
			}
			if (strpos($this->template->constants['HEADJSTAGS'],$file)===false) { // WARNING: if a.js?q was inserted, trying to insert a.js will fail! alas it cannot replace insertions
				if ($preceed)
					$this->template->constants['HEADJSTAGS'] = "\t<script type=\"text/javascript\" src=\"".CONS_INSTALL_ROOT.$file."\"></script>\n".$this->template->constants['HEADJSTAGS'];
				else
					$this->template->constants['HEADJSTAGS'] .= "\t<script type=\"text/javascript\" src=\"".CONS_INSTALL_ROOT.$file."\"></script>\n";
			}
			return true;
		} else {
			if (is_file(CONS_PATH_JSFRAMEWORK.$file))
				$file = CONS_PATH_JSFRAMEWORK.$file;
			else if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/files/".$file))
				$file = CONS_PATH_PAGES.$_SESSION['CODE']."/files/".$file;
			else if (!is_file($file)) {
				$this->errorControl->raise(8,'Style not found',$file,"addLink");
				return false;
			}
			if (strpos($this->template->constants['HEADCSSTAGS'],$file)===false) {
				if ($preceed)
					$this->template->constants['HEADCSSTAGS'] = "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"".CONS_INSTALL_ROOT.$file."\" />\n".$this->template->constants['HEADCSSTAGS'];
				else
					$this->template->constants['HEADCSSTAGS'] .= "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"".CONS_INSTALL_ROOT.$file."\" />\n";
			}
			return true;
		}
		$this->errorControl->raise(8,'File not found',$file,"addLink");
		return false;
	}# addLink
#--
	/* checkActions
	 * domainLoad -> parseRequest -> loadIntlControl -> checkActions -> renderPage -> showTemplate
	 * Once everything is in order to render the page, checks if there are pending actions to be performed, such as handling a $_POST
	 *
	 * IMPORTANT: For actions to work properly, send haveinfo=true or whatever (value doesn't matter) in the query.
	 * 			  DO NOT send it always as it will degrade performance, send ONLY when an action is expected (DB include/edit, upload handling, etc)
	 */
	function checkActions() {

		// look for the first valid context if this is invalid (a.k.a. remove virtual folders)
		$tempContext = $this->context;
		$strContext = implode("/",$tempContext);
		while (count($tempContext)>1 && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/template".$strContext) && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$strContext) && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/actions".$strContext)){
			array_pop($tempContext);
			$strContext = implode("/",$tempContext);
			$this->virtualFolder = true; // if this remains true, we will 404
		}

		if ($this->maintenanceMode) $this->log[] = cReadFile("maint.txt");

		if (!isset($_SESSION[CONS_SESSION_ACCESS_LEVEL])) {
			$_SESSION[CONS_SESSION_ACCESS_LEVEL] = CONS_SESSION_ACCESS_LEVEL_GUEST;
			$this->currentAuth = CONS_AUTH_SESSION_GUEST;
		}

		if (isset($_POST['haveinfo'])) $this->loadAllmodules(); # haveinfo flags the framework that an action MIGHT take place, and thus some extra libraries and precautions should be taken

		$this->currentAuth = $this->authControl->auth(); // <-- authControl or whatever plugin that snatches it, should also give the user warnings. Check errorControl for standard raise codes (3xx)

		if ($this->action == "ajaxquery") {
			// captured this action to the default folder. BEFORE any other action because we don't want default behaviour messing this
			// this is used by some plugins (mostly the admin)
			include_once CONS_PATH_SYSTEM."lazyload/ajaxQuery.php";
			// ajaxQuery SHOULD perform a graceful close, but let's ensure
			$this->close(true);
		}

		// allows you to download a file field (f) from a module (m), with the specified module title as filename, checking permission.
		// will also trigger a "download" notifyEvent on the module
		// NOTE: if bi_stats is on, this will NOT BE LOGGED (TODO: fix?)
		if ($this->action == "_download" && isset($_REQUEST['m']) && isset($_REQUEST['f'])) {
			// same as above
			$m = $this->loaded($_REQUEST['m']);
			if ($m!==false && isset($m->fields[$_REQUEST['f']])) {

				if (!$this->authControl->checkPermission($m)) $this->fastClose(403); // read permission

				$ws = ""; $ka = array();
				$m->getKeys($ws,$ka,$_REQUEST);

				$sql = "SELECT ".$m->title." FROM ".$m->dbname." as ".$m->name." WHERE ".$ws;
				$filename = removeSimbols($this->dbo->fetch($sql),true,false);

				$file = CONS_FMANAGER.$m->name."/".$_REQUEST['f']."_";
				foreach ($ka as $kn => $ki) // this is multikey =D
					$file .= $ki."_";
				$file .= "1";
				if (locateAnyFile($file,$ext)) {
					$filename .= ".".$ext;
					$this->notifyEvent($m,"download",$ka,$m,true); // plugin should handle this
					$this->close(false); // gracefull
					$this->readfile($file,$ext,true,$filename,true);
					$this->close(true); // should never get here
				}
			}
			$this->action = '404';
			$this->warning[] = "404 because download request of invalid file";
		}

		foreach ($this->onActionCheck as $scriptName) {
			$this->loadedPlugins[$scriptName]->onCheckActions();
		}

		if ($this->virtualFolder) { // if we are in a virtualFolder, we run the closest default.php

		 	if ($strContext == "" || $strContect[strlen($strContext)-1] == "/") $strContext .= "/";

			// closest default (which SHOULD disable virtualFolder or disable 404)
			if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/actions".$strContext."default.php")) {
				include_once CONS_PATH_PAGES.$_SESSION['CODE']."/actions".$strContext."default.php";
			}

			// bring to real context
			$this->context = $tempContext;
			$this->context_str = $strContext;

			if ($this->virtualFolder && !$this->ignore404) { // still virtual!? 404
				$this->action = "404";
				$this->warning[] = "404 because of untreated virtualFolder at actions";
			}

		} else { // default behaviour

			if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/actions".$this->context_str."default.php"))
				include_once CONS_PATH_PAGES.$_SESSION['CODE']."/actions".$this->context_str."default.php";
		}

		if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/actions".$this->context_str.$this->action.".php"))
			include_once CONS_PATH_PAGES.$_SESSION['CODE']."/actions".$this->context_str.$this->action.".php";

		// at this point, all DEFAULT scripts were called; Rechecks for permissions (if auth module or some plugin set it before)
		$this->lockPermissions();

		// replace in case some action redirected us
		$this->template->constants['ACTION'] = $this->action;
		$this->template->constants['CONTEXT'] = $this->context_str;
		$this->template->constants['ORIGINAL_ACTION'] = $this->original_context_str.$this->original_action;

	} # checkActions
#-
	/* runContent
	 * Multi functional entrypoint used to work with the template system. Is aware of all MVC structure, as well safety
	 * NOTE: the callback parameters are: &$template, &$params, $data, $processed = false
	 *       a callback sample (which is used on all runContents) is located at components/module.php
	 */
	function runContent($module, &$tp, $sql = "", $tag = "", $usePaging=false,$cacheTAG = false,$callback = false) {
		if (!is_object($module)) { # loads module if not already given
			$tmp = $module;
			$module = $this->loaded($module);
			if ($module === false) $this->errorControl->raise(158,"runContent",$tmp);
		}
		if ($callback !== false || (is_array($sql) && count($sql)>3) || (is_string($sql) && $sql != '')) {
			if (is_array($sql) && count($sql) == 3 && !isset($sql['SELECT'])) {
				$sql = $module->get_base_sql($sql[0],$sql[1],$sql[2]);
			}
		} else {
			$innertp = &$tp->get($tag);
			$innertp = $innertp->getAllTags(true);
			unset($innertp['_t']);
			if (is_array($sql) && count($sql) == 3 && !isset($sql['SELECT'])) {
				$sql = $module->get_advanced_sql($innertp,$sql[0],$sql[1],$sql[2],$cacheTAG!=''?$cacheTAG."sql":false);
			} else
				$sql = $module->get_advanced_sql($innertp,'','','',$cacheTAG!=''?$cacheTAG."sql":false);
		}
		return $module->runContent($tp,$sql,$tag,$usePaging,$cacheTAG,$callback);
	} # runContent
#-
	/* runAction
	 * Multi functional entrypoint used to process actions. Is aware of all MVC structure, safety
	 * $action = CONS_ACTION_INCLUDE, CONS_ACTION_UPDATE, CONS_ACTION_DELETE
	 * Returns true|false if action was performed properly. On CONS_ACTION_INCLUDE the last inserted ID is stored at $this->lastReturnCode
	 */
	function runAction($module,$action,$data,$mfo=false,$startedAt="") {
		if ($this->offlineMode) return false;
		if (!is_object($module)) { # loads module if not already given
			$module = $this->loaded($module);
			if ($module === false) {
				$this->errorState = true;
				return false;
			}
		}
		if ($this->errorState || $this->maintenanceMode) { # if we are in ErrorState, abort all actions
			$this->errorControl->raise(157,$module->name.":#".$action,$module->name,$this->maintenanceMode?"MAINTENANCE MODE":"");
			return false;
		}

		$this->loadAllmodules();
		// Notifies are inside the module->runAction
		// If this is an inclusion, auto-increment value is at $this->lastReturnCode
		$ok = $module->runAction($action,$data,false,$mfo,$startedAt);

		# log
		if (!$module->options[CONS_MODULE_SYSTEM]) $this->errorControl->raise(($ok?300:306),$action,$module->name,"key=".($action==CONS_ACTION_INCLUDE?$this->lastReturnCode:$data[$module->keys[0]]).(isset($_SESSION[CONS_SESSION_ACCESS_USER]) && isset($_SESSION[CONS_SESSION_ACCESS_USER]['id'])?" (user ".$_SESSION[CONS_SESSION_ACCESS_USER]['id'].")":' (guest)'));

		return $ok;
	} # runAction
#-
	/* notifyEvent
	 * System-wide notification so every single module is aware of changes on any module
	 */
	function notifyEvent(&$module, $action, $data,$startedAt="",$early=false) {
		if (is_object($module)) { # we might have triggered this by a fake module
			$module->notifyEvent($module,$action,$data,$startedAt,$early); # own module has precedence
			$mname = $module->name;
		} else
			$mname = "";
    	foreach($this->modules as $name => $object) {
    		if ($name != $mname)
      			$this->modules[$name]->notifyEvent($module,$action,$data,$startedAt,$early);
    	}
	} # notifyEvent
# -
	/* deleteAllFrom
	 * Deletes all childs (or simple remove links) for all fields/itens that are linked to the specified module/data as it is deleted
	 * This basically prevents database inconsistency such as a link pointing to a non-existent foreing key
	 */
	function deleteAllFrom(&$module,$data,$zerothem=false,$startedAt="") {

		$return = array();
		$dbname = $module->dbname;

		$keyString = array();
		$updateString = array();
		foreach ($data as $name => $value) {
			$keyString[] = "$name=\"".$value."\"";
			$updateString[] = $name."=0";
		}
		$keyString = implode(" AND ",$keyString);
		$updateString = implode(", ",$updateString);

		if ($zerothem) { // we are supposed to just ZERO these items
			$this->dbo->simpleQuery("UPDATE $dbname SET $updateString WHERE $keyString"); // direct DB query to prevent notifies and overhead
		} else { // we are supposed to self-destruct
			$myKeys = implode(",",$module->keys);
			$this->dbo->query("SELECT $myKeys FROM ($dbname) WHERE $keyString",$r,$n);

			if ($startedAt == '') $startedAt = $module->name;
			for ($c=0;$c<$n;$c++) {
				$thisIds = $this->dbo->fetch_assoc($r);
				if ($this->runAction($module,CONS_ACTION_DELETE,$thisIds,true,$startedAt)) // this might cause cascading deletions
					array_push($return,$thisIds);
			}
		}
		return $return;
	} # deleteAllFrom
#-
	/* logged
	 * true|false for someone logged
	 */
	function logged() {
		# since we can have more than one return code from AUTH, this makes things easier
		return (isset($_SESSION[CONS_SESSION_ACCESS_USER]) && isset($_SESSION[CONS_SESSION_ACCESS_USER]['id']) && isset($_SESSION[CONS_SESSION_ACCESS_LEVEL]) && $_SESSION[CONS_SESSION_ACCESS_LEVEL] > 0 && ($this->currentAuth == CONS_AUTH_SESSION_NEW || $this->currentAuth == CONS_AUTH_SESSION_KEEP));
	} # logged
#-
	/* loadPermissions
	 * load permission template cache (which is built on debugmode at coreFull
	 */
	function loadPermissions() {
		# loads permissionTemplate
		if (is_null($this->permissionTemplate)) {
			$this->permissionTemplate = @unserialize(cReadFile(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/_permissions.dat"));
			if ($this->permissionTemplate === false) {
				$this->errorControl->raise(3);
				$this->close(true);
			}
		}
	} #loadPermissions
#-
	/* lockPermissions
	 * Sets the proper area from the module/data received (or default if none)
	 * Then loads the permissions from that area/role into the current permission set
	 * the authControl object might be rewritten by a auth plugin
	 */
	function lockPermissions() {
		if (!isset($_SESSION[CONS_SESSION_ACCESS_USER]) || $_SESSION[CONS_SESSION_ACCESS_LEVEL] == 100) return; # no one logged or master admin, no change
		return $this->authControl->lockPermissions();
	} # lockPermissions
#-
	/* loadDimconfig
	 * Loads the dinamic config/status config from cache (or even $_SESSION cache)
	 */
	function loadDimconfig($force = false) {
		if (!isset($_SESSION[CONS_SESSION_CONFIG]) || ($force && !isset($this->dimconfig['_forced']))) {
			$this->dimconfig = unserialize(cReadFile(CONS_PATH_DINCONFIG.$_SESSION['CODE']."/din.dat"));
			if (($this->dimconfig === false || !is_array($this->dimconfig)) && is_file(CONS_PATH_DINCONFIG.$_SESSION['CODE']."/din.bck")) {
				$this->dimconfig = unserialize(cReadFile(CONS_PATH_DINCONFIG.$_SESSION['CODE']."/din.bck"));
				if ($this->dimconfig !== false) {
					$this->errorControl->raise(160);
					$this->saveConfig();
				} else
					$this->errorControl->raise(162);
			}
			$_SESSION[CONS_SESSION_CONFIG] = array($this->dimconfig,date("i")); // lasts tops 1 min
			$this->dimconfig['_forced'] = true;
		} else {
			$this->dimconfig = $_SESSION[CONS_SESSION_CONFIG][0];
			if ($_SESSION[CONS_SESSION_CONFIG][1] != date("i"))
				unset($_SESSION[CONS_SESSION_CONFIG]);
		}
	} # loadDimconfig
#-
	/* loadModule
	 * Most basic module loading just to fill up the system meta-model (thus sets the module as actually not loaded)
	 */
	function loadModule($name,$dbname="") {
		$this->modules[$name] = new CModule($this, $name,$dbname);
		$this->modules[$name]->loaded = false;
	} # loadModule
#-
	function loadTemplate($forcePage="") {
		# Loads the current action template into the proper frame tag (or whole file)

		$file = $this->getTemplate($forcePage==''?$this->action:$forcePage,$this->context_str,true);

		if ($file != "") {
			if ($this->nextContainer != "") { # inside a frame
				$this->template->assignFile($this->nextContainer,$file);
			} else {
				$this->template->fetch($file); # layout 2 can run with no template at all
			}
		}

		$this->removeAutoTags();
	}
#-
	/* loadAllmodules
	 * Loads all module metadata (if not done yet)
	 */
	function loadAllmodules() {
		# loads ALL modules (does the same as loaded, except cicle trhu all)
		if ($this->allModulesLoaded) return;
		$this->allModulesLoaded = true;
		foreach($this->modules as $mod) {
			if (!$mod->loaded) { # this does the same as core::loaded, but optimized for all
				$moduleName = $mod->name;
				$loadedData = unserialize(cReadFile(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/$moduleName.dat"));
				if ($loadedData) {
					# IMPORTANT: this is also loaded in loaded()
					list(
						 $this->modules[$moduleName]->keys,
						 $this->modules[$moduleName]->title,
						 $this->modules[$moduleName]->fields,
						 $this->modules[$moduleName]->order,
						 $this->modules[$moduleName]->permissionOverride,
						 $this->modules[$moduleName]->freeModule,
						 $this->modules[$moduleName]->linker,
						 $this->modules[$moduleName]->options,
						 ) = $loadedData;
				} else {
					$this->errorControl->raise(163,'',$moduleName);
					$this->errorState = true;
				}
			}
		}
		foreach ($this->modules as $moduleName => &$m) {
			if (!$m->loaded) {
				$m->loaded = true;
				$m->loadPlugins();
			}
		}
	} # loadAllmodules
#-
	/* loaded
	 * Loads ONE module metadata and returns such module object (or false on failure)
	 * If the module is already loaded, simply returns it's object
	 */
	function loaded($moduleName) {
		# loads the module if it exists, or return false
		if (is_object($moduleName)) return $moduleName;
		$moduleName = strtolower($moduleName);
		if (isset($this->modules[$moduleName])) {
			if (!$this->modules[$moduleName]->loaded) {
				# loads module, it was only referenced
				$loadedData = unserialize(cReadFile(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/$moduleName.dat"));
				if ($loadedData) {
					list(
						 $this->modules[$moduleName]->keys,
						 $this->modules[$moduleName]->title,
						 $this->modules[$moduleName]->fields,
						 $this->modules[$moduleName]->order,
						 $this->modules[$moduleName]->permissionOverride,
						 $this->modules[$moduleName]->freeModule,
						 $this->modules[$moduleName]->linker,
						 $this->modules[$moduleName]->options,
						 ) = $loadedData;
					if (!is_object($this->modules[$moduleName])) {
						$this->errorControl->raise(163,$moduleName);
						$this->errorState = true;
						return false;
					} else {
						$this->modules[$moduleName]->loaded = true;
						$this->modules[$moduleName]->loadPlugins();
					}
				} else {
					$this->errorControl->raise(130,$moduleName);
					$this->errorState = true;
					return false;
				}
			}
			return $this->modules[$moduleName];
		}
		return false;
	} # loaded
#-
	/* setLog
	 * sets Log level depending on the request
	*/
	function setLog($level,$force=false) {
		if ($force) $this->loglevel = $level;
		else {
			switch ($level) {
				case CONS_LOGGING_WARNING: // replaces notice or sucess, but not error
					if ($this->loglevel != CONS_LOGGING_SUCCESS) $this->loglevel = CONS_LOGGING_WARNING;
				break;
				case CONS_LOGGING_SUCCESS: // replaces only notice
					if ($this->loglevel == CONS_LOGGING_NOTICE) $this->loglevel = CONS_LOGGING_SUCCESS;
				break;
				case CONS_LOGGING_ERROR: // replaces all
					$this->loglevel = CONS_LOGGING_ERROR;
				break;
				// CONS_LOGGING_NOTICE: no action, is the default level and should not replace others
			}
		}
	}
#-
	/* saveConfig
	 * saves dinamic config/status config into disk, with or without raising a fatal error on failure
	 */
	function saveConfig($NO_RAISE = false) {
		#saves the dimconfig and/or the statsConfig
		unset($this->dimconfig['_forced']);
		$this->dimconfig['_debugmode'] = CONS_DEVELOPER || $this->debugmode ? 1 : 0;
		if (isset($this->loadedPlugins['bi_dev'])) $this->dimconfig['_debugmode']++;

		if (count($this->dimconfig) == 0 || !isset($this->dimconfig['adminmail'])) {
			if (!$NO_RAISE) $this->errorControl->raise(164,'dimconfig');
			return;
		}
		$oFile = CONS_PATH_DINCONFIG.$_SESSION['CODE']."/din.dat";
		if (!cWriteFile($oFile,serialize($this->dimconfig))) {
			sleep(1);
			if (!cWriteFile($oFile,serialize($this->dimconfig))) {
				if (!$NO_RAISE) $this->errorControl->raise(165,'dimconfig');
			}
		} else {
			$_SESSION[CONS_SESSION_CONFIG] = array($this->dimconfig,date("i")); // lasts tops 1 min
			$this->dimconfig['_forced'] = true;
		}
	} #saveConfig

	function registerTclass($script,$class) {
		$this->tClass[$class] = $script->name;
	}

#-
	/* renderPage
	 * domainLoad -> parseRequest -> loadIntlControl -> checkActions -> renderPage -> showTemplate
	 * Runs the control scripts (which will most likely change the views), deals with 404 errors and some optimizations. Also sets the header for the current charset
	 */
	function renderPage() {


		// plugins can ALSO change the template ...
		foreach ($this->onRender as $scriptName) {
			$this->loadedPlugins[$scriptName]->onRender();
		}

		// if no plugin handled 404, trigger it
		if (!$this->ignore404 && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/template".$this->context_str) && !is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$this->context_str)) {
			$this->action = "404";
			$this->warning[] = "404 because of path not found at renderPage";
		}



		# default content handler
		if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$this->context_str."default.php")) {
			include_once CONS_PATH_PAGES.$_SESSION['CODE']."/content".$this->context_str."default.php";
		}

		# Call the content script
		if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$this->context_str.$this->action.".php"))
			include CONS_PATH_PAGES.$_SESSION['CODE']."/content".$this->context_str.$this->action.".php";

		$this->onShow(); // process onShow for plugins

	} # renderPage
#-
	/* getTemplate
	 * Returns the template name given the current action and context.
	 * It will eventually return the best 404 file given the context if no file matches the current action
	 * If everything fails, will automatically end the script on a hard 404 error
	 */
	function getTemplate($action, $context = "",$PluginCheck = false,$secondPass=false) {

		$action = str_replace(".html","",$action);
		if (($context == "" || $context[0] != "/") && $action[0] != "/") $context = "/".$context;
		if (isset($_SESSION['CODE'])) {
			$cfile = CONS_PATH_PAGES.$_SESSION['CODE']."/template".$context.$action.".html";
			if (is_file($cfile)) {
				return $cfile;
			}
			$cfile = CONS_PATH_PAGES.$_SESSION['CODE']."/template".$context.$action.".xml";
			if (is_file($cfile)) {
				return $cfile;
			}
		}

		if ($PluginCheck) { # plugins can change the 404 handling (such as searching for a CMS or friendlyURL)

			foreach ($this->on404 as $scriptName) {
				$file = $this->loadedPlugins[$scriptName]->on404($action, $context);
				if ($file !== false) {
					$this->ignore404 = true; // further file checks should be done by the module, thus ignore 404
					return $file;
				}
			}
		}

		if ((!$secondPass && $this->ignore404) || $this->layout > 1) {
			// secondPass cannot ignore a 404
			return "";
		}

		$cfile = CONS_PATH_SETTINGS."defaults/".$action.".html";
		if (is_file($cfile)) return $cfile;

		if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/content".$context.$action.".php"))
			$this->errorControl->raise(166,"Nothing for: ".$context."' / '".$action);
		else
			$this->errorControl->raise(166,"No HTML for: ".$context."' / '".$action);

		$this->headerControl->baseHeader = $action == '403'?'403':($action == '503'?'503':'404');
		if ($action != "403" && $action != "404" && $action != "503") {
			$this->warning[] = "404 because template not found: ".$context.$action;

			$cfile = CONS_PATH_PAGES.$_SESSION['CODE']."/template".$context.$this->headerControl->baseHeader.".html";
			if (is_file($cfile)) return $cfile;

			$cfile = CONS_PATH_PAGES.$_SESSION['CODE']."/template/".$this->headerControl->baseHeader.".html";
			if (is_file($cfile)) return $cfile;

			$cfile = CONS_PATH_SETTINGS."defaults/".$this->headerControl->baseHeader.".html";
			if (is_file($cfile)) return $cfile;
		}

		# everything failed!
		$this->close(false);
		$this->headerControl->showHeaders(true);
  		die("<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\"><HTML><HEAD><TITLE>".$this->headerControl->baseHeader."</TITLE></HEAD><BODY><H1>".$this->headerControl->baseHeader." Not Found</H1>The requested URL was not found on this server.<P><P>Additionally, a 404 Not Found error was encountered while trying to use an ErrorDocument to handle the request.</BODY></HTML>");
	} # getTemplate
#-
	function onShow() {
		# onShow (might change metadata, such as CMS plugin)
		foreach ($this->onShow as $scriptName) {
			$this->loadedPlugins[$scriptName]->onShow();
		}
	}
#-
	/* showTemplate
	 * domainlock -> parseRequest -> loadIntlControl -> checkActions -> renderPage -> showTemplate
	 * Basically echos the template (view), filling up constants and other framework optimizations into it
	 */
	function showTemplate() {

		# Echo dimconfig if something should be outputed
		$data = $this->cacheControl->getCachedContent('dimconfig_auto',120);
		if ($data === false && is_object($this->template)) {
			$data = $this->dimconfig;
			$dimconfigMD = unserialize(cReadFile(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/_dimconfig.dat"));
			foreach ($data as $name => $content) {
				if (isset($dimconfigMD[$name])) {
					if ($dimconfigMD[$name][CONS_XML_TIPO] == CONS_TIPO_UPLOAD) {
						$FirstfileName = CONS_FMANAGER.$dimconfigMD[$name]['location'];
						$path = explode("/",$FirstfileName);
						$fileName = array_pop($path);
						$path = implode("/",$path)."/";
						$hasFile = locateAnyFile($FirstfileName,$ext);
						if (isset($dimconfigMD[$name][CONS_XML_THUMBNAILS])) { // images
							$imgs = count($dimconfigMD[$name][CONS_XML_THUMBNAILS]);
							for ($c=1;$c<=$imgs;$c++) {
								$fnamedata = $name."_".$c;
								$data[$fnamedata] = $FirstfileName;
								$data[$fnamedata."w"] = "";
								$data[$fnamedata."h"] = "";
								$data[$fnamedata."t"] = "";
								$data[$fnamedata."s"] = "";
								if ($hasFile) {
									$data[$fnamedata] = $FirstfileName;
									$popped = explode("/",$FirstfileName);
									$data[$fnamedata."filename"] = array_pop($popped);
									if (in_array(strtolower($ext),array("jpg","gif","png","jpeg","swf"))) {
										// image/flash
										$h = getimagesize($FirstfileName);
										$data[$fnamedata."w"] = $h[0];
										$data[$fnamedata."h"] = $h[1];
										$data[$fnamedata."s"] = humanSize(filesize($FirstfileName));
										if (in_array(strtolower($ext),array("jpg","gif","png","jpeg"))) {
											$data[$fnamedata."t"] = "<img src=\"".$FirstfileName."\" width='".$h[0]."' height='".$h[1]."' alt='' />";
										} else if (strtolower($ext) == "swf") {
											$data[$fnamedata."t"] =
											str_replace("{FILE}",$FirstfileName,
											str_replace("{H}",$h[1],
											str_replace("{W}",$h[0],SWF_OBJECT)));
										}
									}
								}
							}
						} else if ($hasFile) {
							$fnamedata = $name."_1";
							$data[$fnamedata] = $FirstfileName;
							$data[$fnamedata."s"] = humanSize(filesize($FirstfileName));
							$popped = explode("/",$FirstfileName);
							$data[$fnamedata."filename"] = array_pop($popped);
						} else {
							$fnamedata = $name."_1";
							$data[$fnamedata] = "";
							$data[$fnamedata."t"] = "";
							$data[$fnamedata."s"] = "";
						}
						$this->template->fill($data);
					} else
						$data[$name] = $content;
				} else
					$data[$name] = $content;
			}
			$this->cacheControl->addCachedContent('dimconfig_auto',$data,true);
		}
		if (is_object($this->template)) $this->template->fill($data);

		# Header / cache
		if ($this->action != "404" && $this->action != "403") $this->headerControl->addHeader(CONS_HC_HEADER,200);
		$this->headerControl->addHeader(CONS_HC_CONTENTTYPE,"Content-Type: text/html; charset=".$this->charset);
		$this->headerControl->addHeader(CONS_HC_PRAGMA,'Pragma: '.($this->layout!=2 && $this->cachetime>2000?'public':'no-cache'));
		if (CONS_CACHE && $this->layout != 2)
			$this->headerControl->addHeader(CONS_HC_CACHE,'Cache-Control: public,max-age='.floor($this->cachetime/1000).',s-maxage='.floor($this->cachetime/1000));

		if (is_object($this->template)) {
			$this->template->constants['CHARSET'] = $this->charset;

			# metadata - fill default values if not set yet (plugins can set)
			$this->addMeta("\t<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" />"); // render in the most recent engine for IE
			if ($this->layout != 2) {
				if ((!isset($this->template->constants['METAKEYS']) || $this->template->constants['METAKEYS'] == '') && $this->dimconfig['metakeys'] != '') {
					$this->template->constants['METAKEYS'] = $this->dimconfig['metakeys'];
				}
				if ((!isset($this->template->constants['METADESC']) || $this->template->constants['METADESC'] == '') && $this->dimconfig['metadesc'] != '') {
					$this->template->constants['METADESC'] = $this->dimconfig['metadesc'];
				}

				// METAS
				if ($this->template->constants['CANONICAL'] == '') {
					$this->template->constants['CANONICAL'] = "http://".$this->domain.$this->context_str.$this->action.".html";
					if (isset($_REQUEST['id']))
						$this->template->constants['CANONICAL'] .= "?id=".$_REQUEST['id'];
				}
				$metadata = $this->template->constants['METATAGS'];
				if (CONS_PATH_PAGES.$_SESSION['CODE']."/template/_meta.xml")
					$metadata .= cReadFile(CONS_PATH_PAGES.$_SESSION['CODE']."/template/_meta.xml");
				$metadata .= "\t<link rel=\"canonical\" href=\"".$this->template->constants['CANONICAL']."\" />\n";
				if ($this->template->constants['METAKEYS'] != '')
					$metadata .= "\t<meta name=\"keywords\" content=\"".str_replace("\"","",$this->template->constants['METAKEYS'])."\"/>\n";
				if ($this->template->constants['METADESC'] != '') {
					$metadata .= "\t<meta name=\"description\" content=\"".str_replace("\"","",$this->template->constants['METADESC'])."\"/>\n";
					$metadata .= "\t<meta property=\"og:description\" content=\"".str_replace("\"","",$this->template->constants['METADESC'])."\"/>\n";
				}
				$metadata .= "\t<meta property=\"og:type\" content=\"website\" />\n";
				$metadata .= "\t<meta property=\"og:title\" content=\"".str_replace("\"","",$this->template->constants['PAGE_TITLE'])."\" />\n";
				$metadata .= "\t<meta property=\"og:url\" content=\"".$this->template->constants['CANONICAL']."\" />\n";
				if (isset($this->template->constants['METAFIGURE']) && $this->template->constants['METAFIGURE'] != "") {
					$metadata .= "\t<meta property=\"og:image\" content=\"/files/".$this->template->constants['METAFIGURE']."\" />\n";
					$metadata .= "\t<link rel=\"image_src\" href=\"/files/".$this->template->constants['METAFIGURE']."\" />\n";
				}

				$favfile = CONS_PATH_PAGES.$_SESSION['CODE']."/files/favicon";
				if (locateFile($favfile,$ext)) {
					$favfile = CONS_INSTALL_ROOT.$favfile;
					$metadata .= "\t<link rel=\"shortcut icon\" href=\"/favicon.".$ext."\" />\n";
				} else if (CONS_DEFAULT_FAVICON) {
					$favfile = "favicon";
					if (locateFile($favfile,$ext)) {
						$favfile = CONS_INSTALL_ROOT.$favfile;
						$metadata .= "\t<link rel=\"shortcut icon\" href=\"/favicon.".$ext."\" />\n";
					}
				}

				$this->template->constants['METATAGS'] = $metadata;
			}

			$this->removeAutoTags();

			if (count($this->log)>0) {
				$output = "";
				$haveWarning = false;
				foreach ($this->log as $saida) {
					$output .= $saida."\n<br/>";
					if ($saida[strlen($saida)-1] == "!") $haveWarning =  true;
				}
				$file = $this->debugFile;
				if ($this->debugFile == '' || !is_file($file)) {
					if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template/_debugarea.html"))
						$file = CONS_PATH_PAGES.$_SESSION['CODE']."/template/_debugarea.html";
					else
						$file = CONS_PATH_SETTINGS."defaults/_debugarea.html";
				}
				$tp = new CKTemplate($this->template);
				$tp->fetch($file);
				$tp->assign("CORE_DEBUG",$output);
				$tp->assign("CORE_DEBUGWARNING",$haveWarning ? "1" : "0");
				$this->template->constants['CORE_DEBUG'] = $tp->techo();
				unset($tp);
			}

			// print version
			if ($this->template->get("printver") == '') {
				$printVersion = arrayToString($_GET,array("layout"));
				$printVersion .= "&layout=1";
				$this->template->assign("printver",$this->action.".html?".$printVersion);
			}
		}

		if ($this->action == '404' || $this->action == '403') $this->errorControl->raise(103,$this->context_str.$this->action,"404x403");

		return $this->template->techo();
	} # showTemplate
#-
	function removeAutoTags() {
		/*
						layout 0 (normal)		layout 1 (popup)		layout 2 (ajax)		CONS_BROWSER_ISMOB / layout 3
		_ajaxonly		REMOVE					REMOVE					KEEP				<- as layout
		_removeonpopup	KEEP					REMOVE					REMOVE				<- as layout
		_removeonajax	KEEP					KEEP					REMOVE				<- as layout
		_removemob		KEEP					KEEP					KEEP				REMOVE
		_mobonly		REMOVE					REMOVE					REMOVE				KEEP
		_onserver		NEST					NEST					NEST				NEST		<- removes when LOCAL

		To force non mobime, use ?desktopversion=1

		*/
		if ($this->layout == 0) {
			$this->template->assign("_ajaxonly");
		} else if ($this->layout == 1) {
			$this->template->assign("_removeonpopup");
			$this->template->assign("_ajaxonly");
		} else {
			$this->template->assign("_removeonpopup");
			$this->template->assign("_removeonajax");
		}

		if (!CONS_ONSERVER)
			$this->template->assign("_onserver");

		if ((CONS_BROWSER_ISMOB && !isset($_SESSION['NOMOBVER'])) || $this->layout == 3) // note: also removed in renderPage
			$this->template->assign("_removemob");
		else
			$this->template->assign("_mobonly");

		if ($this->logged()) $this->template->assign("_GUEST");
		else $this->template->assign("_LOGGED");

		if (CONS_USE_I18N) $this->intlControl->removeLanguageTags();

	}
#-
	function rss($data,$echoHeader=true,$imgtitle="",$imgurl="",$imglink="") {
		// interfaced function (lazy load)
		return include(CONS_PATH_SYSTEM."lazyload/rss.php");
	}
#-
	function fullSearch($parameters=array(),$groupPerModule=false) {
		// interfaced function (lazy load)
		return include(CONS_PATH_SYSTEM."lazyload/fullSearch.php");
	}
#-
	function feedReader($url,$cancache=true) {
		// interfaced function (lazy load)
		return include(CONS_PATH_SYSTEM."lazyload/feedReader.php");
	}
#-
	private function builddomains() {
		// interfaced function (lazy load)
		return include(CONS_PATH_SYSTEM."lazyload/builddomains.php");
	}
#-
	function addScript($scriptname,$parameters=array()) {
		// interfaced function (lazy load)
		return include(CONS_PATH_SYSTEM."lazyload/script.php");
	}
#-
	/* Simple text captcha code
	 * Must run on action if you want to check the field, and content to generate a new key
	*/
	function tCaptcha($key,$checkStage=false) {
		// interfaced function
		return include(CONS_PATH_SYSTEM."lazyload/tcaptcha.php");
	}
#-
	/* fastClose
	 * To put it simple, this is an improved version of die() or exit(), performing some friendly error checks and logs
	 */
	function fastClose($action,$context = "") {
		// interfaced function (lazy load)
		return include(CONS_PATH_SYSTEM."lazyload/fastclose.php");
	} # fastClose
#-
	/* friendlyurl
	 * Translates an URL to another, based on a database
	 * Call this at action/default.php
	 * Returns true if a hit is made, and redirects to the page defined on the parameter. Also stores the resulted database item in storage['friendlyurldata']
	 */
	function friendlyurl($param) {
		// interfaced function (lazy load)
		return include(CONS_PATH_SYSTEM."lazyload/friendlyurl.php");
	}
#-
	/* udm
	 * Translates FOLDERS to query strings on $_REQUEST based on a database
	 * Call this at action/default.php
	 * This should also reset $this->virtualFolder to avoid 404
	 * returns true if it was a hit (and this, reset virtualFolder)
	 */
	function udm($param,$ignorePreVF=true) {
		// interfaced function (lazy load)
		return include(CONS_PATH_SYSTEM."lazyload/udm.php");
	}
#-
	/* readfile
	 * Similar to PHP's readfile, except this will fill up header data and will also free up resources while the file is sent
	 * TEST IF THE FILE EXIST BEFORE CALLING THIS FUNCTION
	 */
	function readfile($file,$ext="",$exit=true,$filename="",$forceAttach=false,$cachetime=6000) {
		// interfaced function (lazy load)
		return include(CONS_PATH_SYSTEM."lazyload/readfile.php");
	} # readfile
#-
#-
	/* prepareMail
	 * Reads a template from the /mail/ folder and build the view using a possible template, then fill it up with data from the provided array
	 * Returns a template object that should be ready to be sent
	 */
	function prepareMail($name="",$fillArray=array()) {
		// interfaced function (lazy load)
		return include(CONS_PATH_SYSTEM."lazyload/prepareMail.php");
	} # prepareMail
#-
	function frame($f1,$f2=false,$f3=false,$f4=false) {
	  	$this->template->clear(true);
		$fs = array();
		$fs[] = explode(":",$f1);
		if ($f2) $fs[] = explode(":",$f2);
		if ($f3) $fs[] = explode(":",$f3);
		if ($f4) $fs[] = explode(":",$f4);

		foreach ($fs as $frame) {
			$file = $this->getTemplate($frame[0],"",true);

			if ($file == "") {
				if (is_file(CONS_PATH_SETTINGS."defaults/".$frame[0]))
					$file = CONS_PATH_SETTINGS."defaults/".$frame[0];
			}
			if ($file == "") {
				$this->errorControl->raise(184,'frame',$frame[0]);
				return false;
			}

			if ($file== $this->context_str.$this->action.".html") {
				$this->action = "404"; // we cannot access a page which is part of the frame!
				$this->warning[] = "404 because ill formed frame request";
			}

			if ($this->nextContainer != "") {
				if (in_array($frame[1],$this->template->stackedTags)) break; // looping!
				$this->template->assignFile($this->nextContainer,$file);
				$this->template->stackedTags[] = $this->nextContainer;
			} else {
				$this->template->fetch($file);
			}
			if ($this->firstContainer == "" && isset($frame[1]))
				$this->firstContainer = $frame[1];
			$this->nextContainer = isset($frame[1])?$frame[1]:'';
		}
		return true;
  	} # frame

	/* checkHackAttempt
	 * Some field for a SELECT came in a strange format where neither SELECT nor FROM where created, check if this is an attempt to load a hack script
	 * Can also be used to test a generic GET QUERY string for some possible hack attempts
	 */
	function checkHackAttempt($command) {
		if (strpos($command,"../")!== false ||
			strpos($command,"tp://")!== false || // ftp:// http://
			strpos($command,"/*")!== false) {
			$this->errorControl->raise(144,$command,''); # should abort
			$this->fastClose(403); # most certainly will abort
			$this->close(true); # bah abort
		} else
			return $command;
	}
#-
	/* queryOk
	 * Checks if all provided fields are filled (can test numeric values)
	 */
	function queryOk($testFields = array()) {
		# This function tests haveinfo approach, antibot system, and the presence of the fields passed in the array testFields (testFields might be a form XML)
		# fields starting with # will be tested for is_numeric (#id => check if $_REQUEST['id'] exists then is_numeric($_REQUEST['id'])
		if (!is_array($testFields)) $testFields = array($testFields);
		foreach ($testFields as $field) {
			if ($field[0] == "#") {
				$field = substr($field,1);
				$aH = !isset($_POST[$field]); // antiHack only on GET
				$data = isset($_POST[$field]) ? $_POST[$field] : (isset($_GET[$field])?$_GET[$field]: (isset($_REQUEST[$field])?$_REQUEST[$field]:false));
				if ($data === false || !is_numeric($data)) { // missing
					$this->errorControl->raise(211,$field,'','Field had to be numeric');
					return false;
				}
				if ($aH) $this->checkHackAttempt($data); ## will abort script immediatly
			} else {
				$aH = !isset($_POST[$field]); // antiHack only on GET
				$data = isset($_POST[$field]) ? $_POST[$field] : (isset($_GET[$field])?$_GET[$field]: (isset($_REQUEST[$field])?$_REQUEST[$field]:false));
				if ($data === false || $data == '') {
					$this->errorControl->raise(211,$field);
					return false;
				}
				if ($aH) $this->checkHackAttempt($data); ## will abort script immediatly
			}
		}
		return true;
	} # queryOk
#-
	/* nearTimeLimit
	 * Some CPU intensive functions might query this function to check if the script is nearing the Time Limit, and thus try to abort gracefully
	 */
	function nearTimeLimit() {
		# returns TRUE if we are too near time's up
		if (scriptTime() > CONS_TIMEWARNING) {
			$this->errorControl->raise(167,$this->context_str."/".$this->action,'',scriptTime());
			return true;
		}
		return false;
	} # nearTimeLimit
} # CORE OBJECT

