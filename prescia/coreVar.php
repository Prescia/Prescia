<?/* -------------------------------- Prescia Core variables and constants
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
-*/

set_time_limit (CONS_TIMELIMIT);
define ("AFF_BUILD","14.9.16 beta"); // (Y.m.d) ~ last stable: n/a
define ("AFF_VERSION",0.6);  // 1.0 conditioned to intrade release
// Original numbering before ɔ: 1 = Akari, 2 = Sora, 3 = Aff(ɔ)/Nekoi, 4 = Prescia(ɔ)

# -- XML parameter
define ("CONS_XML_MANDATORY",0); # field is mandatory
define ("CONS_XML_JOIN",1); # join type (inner / left)
define ("CONS_XML_HTML",2); # text field accepts HTML content
define ("CONS_XML_TIMESTAMP",3); # field starts with timestamp
define ("CONS_XML_UPDATESTAMP",4); # field is to be updated with timestamp every update
define ("CONS_XML_FILETYPES",5); # file types an upload field accepts
define ("CONS_XML_FILEMAXSIZE",6); # file size an upload field accepts
define ("CONS_XML_THUMBNAILS",7); # thumbnail sizes (including original dimensions) an image upload accepts
define ("CONS_XML_CONDTHUMBNAILS",20); # conditional thumbnails
define ("CONS_XML_FILEPATH",8); # path to save files (other than default)
define ("CONS_XML_RESTRICT",9); # field is restrict only for high level users (this is the level from which can edit)
define ("CONS_XML_DEFAULT",10); # default value the field received if none defined in include
define ("CONS_XML_FIELDLIMIT",11); # size limit for field
define ("CONS_XML_IGNORENEDIT",12); # ignores when a mandatory field comes empty on EDIT (do not raise mandatory error, and do not change field to empty)
define ("CONS_XML_OPTIONS",13); # option list (array) for an OPTION field
define ("CONS_XML_AUTOFILL",14); # will fill this field with another if empty
define ("CONS_XML_META",15); # will store the meta description of this field (used ones: masked, password)
define ("CONS_XML_SERIALIZED",23); # how this field deals with serialization: 0/none (ignores), 1/read (will parse on output/runcontent), 2/write (will parse on input/runaction), 3/all (always parses)
define ("CONS_XML_CUSTOM",21); # this field will will NOT be subject to ANY system check. Be careful
define ("CONS_XML_NOIMG",22); # default image to be returned if no image is set
define ("CONS_XML_AUTOPRUNE",16); # used in enums, this is used to limit how many items of each class can exist
define ("CONS_XML_FILTEREDBY",17); # manually sets how this field should be filtered
define ("CONS_XML_READONLY", 18); # not editable on admin panes
define ("CONS_XML_TWEAKIMAGES",19); # see documentation. Uses watermark/tweaker for images
define ("CONS_XML_SPECIAL",25); # login, mail, ucase, lcase, path, google, youtube, time, cpf, cnpj, id
define ("CONS_XML_SOURCE",27); # for URLA. Default is title, but multilanguage fields might require others
define ("CONS_XML_SIMPLEEDITFORCE",26); # forces via PHP the simpleEdit effect. 0 to none (default), 1 to always, 2 to only non-highlevel
define ("CONS_XML_ISOWNER",28); # this field is considered an owner of this item (auto-checked on coreFull, if a SINGLE owner is found, even not a user, it will be set)
# -- MODULE OPTIONS. This options are not in raw format thus are formated here, other options are directly on the options array as they come (such as autoclean)
define ("CONS_MODULE_VOLATILE","cmv"); # (cron) This module has no link with other modules or no upload fields, thus it can be deleted with no check
define ("CONS_MODULE_MULTIKEYS","cmmk"); # which fields are considered multiple keys for this module. Having multiple key fields will void the autoincrement system
define ("CONS_MODULE_SYSTEM","cmadmsys"); # (adm) this is a system module and should not be listed for the end user
define ("CONS_MODULE_AUTOCLEAN","cmac"); # (cron) WHERE statement with date handling to when this database should be cleaned (cron)
define ("CONS_MODULE_PARENT","cmtp"); # If the module defines a tree structure, which field defines the tree parenthood (usually id_parent)
define ("CONS_MODULE_META","cmadmmeta"); # (adm) If the module defines a meta description, this is it
# -- logging levels
define ("CONS_LOGGING_NOTICE",0); // normal "info"
define ("CONS_LOGGING_WARNING",1); // low-level "warning"
define ("CONS_LOGGING_SUCCESS",2); // normal "sucess" / action return
define ("CONS_LOGGING_ERROR",3); // normal "warning" / error
# -- internal parameters
define ("CONS_XML_SQL",100); # field SQL
define ("CONS_XML_TIPO",101); # field Type
define ("CONS_XML_LINKTYPE",104); # if this is a link, what is the type of the variable?
define ("CONS_XML_MODULE",102); # foreing key module (module name)
define ("CONS_XML_SERIALIZEDMODEL",103); # list of fields inside a CONS_TIPO_SERIALIZED
# -- basic types
define ("CONS_TIPO_INT",200);
define ("CONS_TIPO_FLOAT",201);
define ("CONS_TIPO_VC",202);
define ("CONS_TIPO_ENUM", 203);
define ("CONS_TIPO_TEXT", 204);
define ("CONS_TIPO_DATE", 205);
define ("CONS_TIPO_DATETIME", 206);
define ("CONS_TIPO_UPLOAD", 207);
define ("CONS_TIPO_LINK", 208);
define ("CONS_TIPO_OPTIONS", 209); # 0/1 string of options
define ("CONS_TIPO_SERIALIZED", 210); # same as text, but have CONS_XML_SERIALIZEDMODEL
define ("CONS_TIPO_ARRAY", 211); # serialized-only type, this is a serialized array
# -- session control (PHP does not accept numeric keys at the session array)
define ("CONS_SESSION_CONFIG","prescia_s_config"); # dimconfig temporary copy
define ("CONS_SESSION_ACCESS_LEVEL","prescia_sa_level");
 define ("CONS_SESSION_ACCESS_LEVEL_GUEST",0);
define ("CONS_SESSION_ACCESS_USER","prescia_sa_user"); # data for user
define ("CONS_SESSION_ACCESS_PERMISSIONS","prescia_sa_perm");
define ("CONS_SESSION_LANG","prescia_lang");
define ("CONS_SESSION_LOG","prescia_log"); # for fowarded control
define ("CONS_SESSION_LOG_REQ","prescia_logreq"); # will replace the POST in case of error
define ("CONS_SESSION_LOGLEVEL","prescia_loglevel"); # level of log to display
define ("CONS_SESSION_CACHE","prescia_cache"); # object cache for cacheControl
define ("CONS_SESSION_NOROBOTS","prescia_robots"); # either this is a norobots or not domain
# -- actions
define ("CONS_ACTION_SELECT",0); // the "action" of reading a data - used by permission controls
define ("CONS_ACTION_INCLUDE",1);
define ("CONS_ACTION_UPDATE",2);
define ("CONS_ACTION_DELETE",3);
define ("CONS_RUNCONTENT_NOIMGOVERRIDE",5); // add this to template parameters to prevent the noimg tag from working (a.k.a. if there is no image, the placeholder won't be used)
# -- current autentication process (stored in currentAuth)
define ("CONS_AUTH_SESSION_GUEST",0); # no user logged
define ("CONS_AUTH_SESSION_NEW",1); # just logged in (at this instance)
define ("CONS_AUTH_SESSION_KEEP",2); # valid autentication, will keep logged (a.k.a. logged)
define ("CONS_AUTH_SESSION_LOGGEDOUT",5); # was logged, but logged out as per time-out
define ("CONS_AUTH_SESSION_FAIL_INACTIVE",10); # login ok, but inactive
define ("CONS_AUTH_SESSION_FAIL_EXPIRED",11); # login ok, but expired
define ("CONS_AUTH_SESSION_FAIL_UNKNOWN",12); # no login/password pair found

# -- includes
require_once CONS_PATH_SYSTEM."components/errorControl.php"; # manages ERROR control
require_once CONS_PATH_SYSTEM."components/headerControl.php"; # manages HEADER control
require_once CONS_PATH_SYSTEM."components/intlControl.php"; # manages INTERNATIONALIZATION control (even if not enabled, must be created to use default handlers)
require_once CONS_PATH_SYSTEM."components/module.php"; # Standard module
require_once CONS_PATH_SYSTEM."components/scripted.php"; # Basic script class
require_once CONS_PATH_SYSTEM."components/cacheControl.php"; # manages CACHE control
require_once CONS_PATH_SYSTEM."components/authControl.php"; # manages AUTHENTICATION control (default does nothing, a placeholder call class)

class CPresciaVar {

	# Client variables (change at config.php) ----
    var $debugmode = false; # running on debug mode? (from index, can be set with ?debugmode=true or CONS_DEVELOPER at settings.php)
	var $languageTL = array(); # folder names for different languages, leave blank not to use it
	var $forceLang = ""; # ignore all langage settings and use THIS language always
	var $charset = "utf-8"; # kinda obvious
	var $domainTranslator = array(); # if a site has multiple domains, you can foward each domain to a separate folder with domain=>folder here (no "/" allowed)
	var $debugFile = ''; # Set this to the HTML template to debug areas (full path)

	# Filled at startup
	var $offlineMode = false; # if true, means database is not online, and will try caches (automatic)
	var $dbless = false; # we expect a database
	var $domain = ""; # this domain (automatic)
	var $action = ""; # action (file) requested
	var $original_action = ""; # full file, important for file check, if in the right context, WITHOUT EXTENSION
	var $context = array(); # the context, in the array form (usefull to run back to default.php on the folder structure)
	var $context_str = ""; # implode of context, stored for faster access, must start AND end with /
	var $original_context_str = ""; # full path, with no change whatsoever (UDM, friendlyurl etc could change the path too)
	var $maintenanceMode = false; # if file maint.txt at root, will be true and will put maint.txt as a msg
	var $noBotProtectOnAjax = false; # if true, disable bot protection on ajax. Enabled usually by newsletter system

    # Operation proceedures --
	var $log = array(); # log array (automatic)
	var $loglevel = CONS_LOGGING_NOTICE;
	var $warning = array(); # warning array (automatic - not used by the framework, but can be used by error-handling plugins)
	var $lastReturnCode = 0; # last returned code from an action (for instance, ID from an include), or last item from a list
	var $lastFirstset = false; # in a loop from runContent, this will be the FIRST returned set (the last comes from lastReturnCode)
	var $errorState = false; # If TRUE, some action returned an error, thus the script is on errorState and wont run any more actions
	var $ignore404 = false; # on 404, ignore it as other scripts will handle it (example: actions, urnames)
	var $safety = true; # checks permissions on actions (should be set FALSE when the site runs a safe function and want to bypass safety check)
	var $dimconfig = array(); # dinamic config from meta folder
	var $cachetime = 0; # throttled cachetime, read from cachecontrol.dat, IN ms
	var $cachetimeObj = 0; # throttled cachetime for objects, read from cachecontrol.dat, IN ms

	# Controllers --
	var $dbo = null; # Database Controller object (dbo.php)
	var $errorControl = null; # Error and Exception controller object (errorControl.php)
	var $cacheControl = null; # Cache control object (cacheControl.php)
	var $intlControl = null; #Intl object (intlControl.php)
	var $authControl = null; #Authentication default object (authControl.php)

	# Appearance and templating --
	var $template = null; # main template object (CKTemplate)
	var $layout = 0; # 0 = normal, 1 = popup, 2 = ajax, 3 = mobile
	var $nextContainer = ""; # from the current template, where to put the next content (meaning, we are using a frame), "" means there is no further place for content (no frame)
	var $firstContainer = ""; # first tag on the base container
	var $templateParams = array(); # used for the runContent and others, fill out before calling any runContent, used by template systems and module.php
	var $virtualFolder = false; # is set at renderPage if the requested folder does not exist on both content nor template

	# Modules -- (from meta.XML)
	var $modules = array();
	var $allModulesLoaded = false; # all modules are loaded into memory, thus we don't need to check each requisition (default if ?haveinfo=1)
	var $loadedPlugins = array();
	var $moduleOptions = array();

	# plugin runtimes (plugins will fill them)
	var $onMeta = array(); # on DEBUGMODE, while metadata is built
	var $onActionCheck = array(); # Right before default checkAction
	var $onRender = array(); # Right before renderPage (alas before default.php)
	var $on404 = array(); # handles 404 rendering (CMS/FriendlyURL/SEO)
	var $onShow = array(); # Right before the template is echoed
	var $onEcho = array(); # Right after the template is parsed to text/HTML, before echo
	var $onCron = array(); # When CRON is raised
	var $tClass = array(); # This module have a tclass handler
	var $templateClasses = array();

	# Session and auth -- (most are stored inside the session, some however are script dependent)
	var $currentAuth = CONS_AUTH_SESSION_GUEST; # Current auth level
	var $permissionTemplate = null; # Default tool permissions built automatically from XML
	var $storage = array(); # so you can store data accessible anywhere, for instance, from ACTION context to CONTENT context or to _output.php

	function __destruct() {
		$this->dbo = null;
		$this->errorControl = null;
		$this->cacheControl = null;
		$this->intlControl = null;
		$this->authControl = null;
		$this->modules = null;
		$this->template = null;
	}

}
