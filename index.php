<?php
  /* -------------------------------- Prescia ENTRYPOINT
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ for Prescia (BSD/open source)
  |
  | Remember: UTF-8 server/php and php 5.+ ARE MANDATORY, all files are UTF-8, php 5.4+ recommended
  | Also, always use some rewrite engine to divert EVERYTHING not multimidia (or at least .php) to this entrypoint.
  | -----------------------------------------------
  | NOTEs:
  | + Prescia code has been optimized for APACHE server, several changes needed for proper IIS operation
  | + Initial code dates back of 2004 and might be obsolete, but still operational
  | + Requires short tags
  | + The ab test requires: (1) set a single domain on CONS_SINGLEDOMAIN, (2) disable CONS_CACHE, CONS_BOTPROTECT and CONS_FREECPU
  |	  Displayed ab test data have 2 numbers: on developer mode, and not on developer mode. Some (where displayed) were run with cache (CONS_CACHE) on
  |   AB test is made to detect choke points and see how the caching is improving performance
  | -----------------------------------------------
  | Last ab test: 15.1.20 (beta 0.94) on an i7-3770 Windows 7 Apache 2.2 php 5.4. Daisuki.com site model
-*/

# ab -n50 total mean: 1ms
ob_start();

#- Paths (relative to root (THIS FILE))  Must end with /
define("CONS_PATH_FILES","pages/"); // if changed, also check .htaccess				 (3)
define("CONS_PATH_SYSTEM","prescia/"); // Core path. 								 (1)
define("CONS_PATH_TEMP","_temp/"); // Temporary/cache/log files					     (2)
define("CONS_PATH_LOGS",CONS_PATH_TEMP."_logs/"); // log files					 	 (2)
define("CONS_PATH_CACHE",CONS_PATH_TEMP."_cache/"); // cache files					 (2)
define("CONS_PATH_SETTINGS","config/"); // config and settings						 (1)
define("CONS_PATH_DINCONFIG",CONS_PATH_SETTINGS."sites/"); // dinamic config		 (1)
define("CONS_PATH_PAGES","pages/"); // each domain/client							 (3)
define("CONS_PATH_INCLUDE",CONS_PATH_SYSTEM."lib/"); // library include 			 (2)
define("CONS_PATH_JSFRAMEWORK",CONS_PATH_PAGES."_js/"); // javascript framework		 (3)
define("CONS_PATH_BACKUP",CONS_PATH_TEMP."_backups/"); // sql backup generated once a month	(2)
// (1) - should never be accessible from outside (2) - could be accessible from outside (3) - must be accessible from outside

require CONS_PATH_INCLUDE."main.php";
if (CONS_ONSERVER && is_file("heavymaint.html")) {
	include "heavymaint.html";
	die();
}
# Server settings and libraries
require CONS_PATH_SETTINGS."settings.php";
# Database
require CONS_PATH_INCLUDE."dbo/cdbo.php";
require CONS_PATH_INCLUDE."dbo/".CONS_AFF_DATABASECONNECTOR.".php";
# Core
require CONS_PATH_SYSTEM."coreVar.php";
require CONS_PATH_SYSTEM."core.php";
# Create core version according to debug/developer mode (note: does NOT connect to database yet)
# ab -n50 total mean: 17ms

if (CONS_DEVELOPER || isset($_GET['debugmode'])) {
	require CONS_PATH_SYSTEM."coreFull.php";
	$cdbo = "CDBO_".CONS_AFF_DATABASECONNECTOR;
	$core = new CPresciaFull(new $cdbo('','','','',isset($_GET['debugmode'])),true);
} else {
	$cdbo = "CDBO_".CONS_AFF_DATABASECONNECTOR;
	$core = new CPrescia(new $cdbo('','','','',false),false);
}

if (CONS_AFF_ERRORHANDLER) { // override PHP error messaging? (if true, will not display errors, but rather log them)
	function PresciaErrorHandler($errno, $errstr, $errfile, $errline) {
		global $core;
		if (error_reporting() === 0) return; // if the function had a "@" before it, then ignore it totally
		switch ($errno) {
			case E_USER_WARNING:
			case E_USER_NOTICE:
			case E_WARNING:
			case E_NOTICE:
				if (!CONS_AFF_ERRORHANDLER_NOWARNING || $errno != 2) {
					if ($core && $core->errorControl) {
						$core->errorControl->raise(600,$errno,"","$errstr at $errfile ($errline)");
					} else
						echo "PHP warning: [$errno] $errstr  at $errfile ($errline)<br/>";
				}
				break;
			default:
				if ($core && $core->errorControl) {
					$core->errorControl->raise(601,$errno,"","$errstr at $errfile ($errline)");
				} else
					echo "PHP error: [$errno] $errstr  at $errfile ($errline)<br/>";
			break;
		}
	}
	function PresciaExceptionHandler($exception) {
		global $core;
		if (error_reporting() === 0) return; // if the function had a "@" before it, then ignore it totally
		if ($core && $core->debugmode)
			$core->errorControl->raise(602,$exception->getMessage());
		else
			die($exception->getMessage());
	}

	$crap = set_exception_handler('PresciaExceptionHandler');
	unset($crap);
	$crap = set_error_handler('PresciaErrorHandler');
	unset($crap);
}
# ab -n50 total mean: 17ms 16ms

require CONS_PATH_INCLUDE."getBrowser.php"; # this will also detect if we are on mobile, required at domainLoad
$core->isbot =  CONS_BROWSER == 'UN'; // bots are not logged and have twice as much cache time
if (!$core->isbot && CONS_HONEYPOT) include(CONS_PATH_SYSTEM."lazyload/honeypot.php"); // start up honeypot detection if enabled
$core->domainLoad(); // locks domain, load config, start i18n, parses requested URL

# ab -50 total mean: 28ms 26ms

define("CONS_FMANAGER",CONS_PATH_PAGES.$_SESSION['CODE']."/files/");
$core->servingFile = $core->checkDirectLink(); // if serving a file, will run end here (if file is not set to statistics collection)
if (CONS_CACHE && !$core->servingFile) $core->cacheControl->startCaches(); // detects which cache to use from auto-throttle system

# -- database and metadata load
if (!$core->dbconnect()) $core->offlineMode = true;
# ab -n50 total mean: 32ms 32ms

if (!$core->loadMetadata()) $core->errorControl->raise(1,"metamodel fault"); // loadMetadata loads dimconfig
if ($core->debugmode) $core->applyMetaData(); // only in debug. Executes onMeta's and save metadata/sql changes
# ab -n50 total mean: 360ms 32ms

# -- start parsing the request
if (!$core->servingFile) {
	// if serving file, we just want to enable the database and run onEcho plugins
	$core->parseRequest();
	# ab -n50 total mean: 360ms 32ms (27ms with cache enabled)
	
	# -- which page I want and context are ready on parseRequest, load template, so get the template core (in case we need to dump an error, we can do it with the template)
	require CONS_PATH_INCLUDE."template/tc.php";
	$core->template = new CKTemplate(null,CONS_PATH_INCLUDE."template/",$core->debugmode,true);
	
	$core->template->constants = array( // these are constants ALWAYS available (echoed) in the template
		'PAGE_TITLE' => (isset($core->dimconfig['pagetitle']) && $core->dimconfig['pagetitle'] != '')?$core->dimconfig['pagetitle']:UcWords($_SESSION['CODE']),
		'IMG_PATH' => CONS_INSTALL_ROOT.CONS_PATH_PAGES.$_SESSION['CODE']."/files/",
		'FMANAGER_PATH' => CONS_INSTALL_ROOT.CONS_FMANAGER, // CONS_FMANAGER came from custom config.php
		'BASE_PATH' => CONS_INSTALL_ROOT,
		'JS_PATH' => CONS_INSTALL_ROOT.CONS_PATH_JSFRAMEWORK,
		'SESSION_LANG' => $_SESSION[CONS_SESSION_LANG],
		'CHARSET' => $core->charset,
		'DOMAIN_NAME' => $core->domain,
		'METAKEYS' => '', // meta keys contents (not the tag)
		'METADESC' => '', // meta description contents ( not the tag)
		'CANONICAL' => '', // canonical contents (the URL, not the tag)
		'HEADCSSTAGS' => '', // CSS tags (should be echoed before js)
		'HEADJSTAGS' => '', // JS tags
		'HEADUSERTAGS' => '', // other tags that will come last in the HEADER
		'METATAGS' => '' // actual meta tags (build with the contents above, at core::showTemplate)
	);
	$core->template->lang_selectors = explode(",",CONS_POSSIBLE_LANGS);
	$core->template->current_language = $_SESSION[CONS_SESSION_LANG];
	require CONS_PATH_SYSTEM."tcexternal.php"; // template classes not built-in into the core (plugins)
	$core->template->externalClasses = new CKTCexternal($core);
	
	foreach ($core->tClass as $class=>$script)
		$core->template->varToClass[] = $class;
	$core->loadIntlControl(); # load i18n variables into template system, translate parseRewrite folder
	if ($_SESSION[CONS_SESSION_NOROBOTS]) {
		$core->headerControl->noIndex();
	}
		
	# -- at this point, the framework overhead is done. From now on, it's mostly the site code.
	# ab -n50 total mean: 375ms 44ms (36ms with cache enabled)

	# -- actions and cron run regardless of cache restrictions
	$core->checkActions();
	$core->cronCheck();
	# ab -n50 total mean: 402ms 46ms (41ms with cache enabled)

	# -- cache test
	$is404 = isset($core->dimconfig['_404cache']) && is_array($core->dimconfig['_404cache']) && isset($core->dimconfig['_404cache'][$core->context_str.$core->original_action]);
	if (CONS_CACHE && !isset($_REQUEST['nocache'])) {
		$core->cacheControl->cachepath = CONS_PATH_CACHE.$_SESSION['CODE']."/caches/";
		$core->cacheControl->cacheseed = ''; // language and user id are automatic		
		if ($core->cacheControl->canUseCache($core->offlineMode)) { # we can use the cache, load it up
			if ($is404) {
				$core->warning[] = "404 because cached as 404";
				$core->action = "404";
				$core->template->constants['ACTION'] = "404";
			}
			$usedCache =true;
			$core->showHeaders();
			$PAGE = $core->cacheControl->renderCache();
		} else { # can't use cache, build page normally (same as on the next ELSE)
			$core->renderPage();
			$core->template->constants['ACTION'] = $core->action; // yes, render could change it
			$core->template->constants['CONTEXT'] = $core->context_str;
			$core->showHeaders();
			$PAGE = $core->showTemplate();
			unset($core->template); // free memory
			if ($core->layout < 2 && count($core->log)==0) $core->cacheControl->setCache($PAGE);
		}
	} else { # cache disabled or can't use cache, build page normaly (same as on the ELSE above)
		// note: core::loadMetadata already run a dumpTemplateCaches if that was required
		$core->renderPage();
		$core->template->constants['ACTION'] = $core->action; // yes, render could change it
		$core->template->constants['CONTEXT'] = $core->context_str;
		$core->showHeaders();
		$PAGE = $core->showTemplate();
		unset($core->template);
	}
	if (!$is404 && $core->action == '404') {
		if (!is_array($core->dimconfig['_404cache']))
			$core->dimconfig['_404cache'] = array();
		if (!isset($core->dimconfig['_404cache'][$core->context_str.$core->original_action])) {		
			$core->dimconfig['_404cache'][$core->context_str.$core->original_action] = true;
			$core->saveConfig(true);
		}
	}
	# ab -n50 total mean: 411ms 77ms (42m with cache enabled)
	
	# -- build headers
	$core->headerControl->showHeaders();
	# -- any script want to check the raw text/HTML output?
} else { // when serving a file directly, these were not set because checkAction was never loaded
	$_SESSION[CONS_SESSION_ACCESS_LEVEL] = CONS_SESSION_ACCESS_LEVEL_GUEST;
	$core->currentAuth = CONS_AUTH_SESSION_GUEST;
}


foreach ($core->onEcho as $scriptName) {
	$core->loadedPlugins[$scriptName]->onEcho($PAGE);
}

if ($core->servingFile) $core->close(true); // end here

# -- collect and serve
$error = ob_get_contents();
ob_end_clean();
# unexpected error? dump after the page
if ($error != "") {
	if ($core->layout<2)
		$PAGE .= $core->errorControl->dumpUnexpectedOutput($error);
	else
		$PAGE .= $error;
}
# -- performance monitor
$totalTime = scriptTime() * 1000;
if (CONS_CACHE) $core->cacheControl->updateCacheControl($totalTime);
if ($totalTime > CONS_PM_TIME) {
	$fd = fopen (CONS_PATH_LOGS.$_SESSION['CODE']."/pm.log", "a");
	if ($fd) {
		fwrite($fd,date("Y-m-d H:i:s")." took ".number_format($totalTime,2)."ms :".$core->context_str.$core->original_action." (caller IP: ".CONS_IP.")\n");
		fclose($fd);
	}
}

# -- if honeypot is on, append trap
if (CONS_HONEYPOT && !$core->isbot && $core->context_str == "/") { // note we don't add the honeypot if we already know this is a bot and on non root folders, no need to catch it again or go overboard
	$hp = "\n<a href=\"/".CONS_HONEYPOTURL."/\" nofollow='true' style='width:0px;overflow:hidden;position:absolute;bottom:0px'>*</a>";
	$PAGE = str_replace("</body>",$hp."</body>",$PAGE);
}		

# -- we are done here, close up whatever is no longer necessary and prepare to echo
$core->close(false);

# -- outputs gzip if on normal layout and browser supports gzip
if (CONS_GZIP_OK && $core->layout < 2 && strlen($PAGE)>CONS_GZIP_MINSIZE) {
	header("Content-Encoding: gzip");
    echo gzencode($PAGE);
} else
	echo $PAGE;

# -- clean up, we are so neat
unset($PAGE);
unset($core);

# ab -n50 total mean: 417ms 77ms (47 with cache enabled)
