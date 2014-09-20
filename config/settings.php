<?	# -------------------------------- Prescia master settings

	# -- MASTER SETTINGS --
	date_default_timezone_set("America/Sao_Paulo");
	define("CONS_AFF_DATABASECONNECTOR"		,"mysqli"); // Options: mysql or mysqli
	define("CONS_AFF_ERRORHANDLER"			,true); // false will echo php errors/exceptions normally, otherwise will log
	define("CONS_AFF_ERRORHANDLER_NOWARNING",false); // either to ignore or not simple notice/warnings
	define ("CONS_FMANAGER_SAFE"			,"download"); // folder used by bi_fm, with permissions. If no entry (or module), nobody have permission to read the file outside of the admin pane
													 // IMPORTANT: if you change the safe folder, be sure to change it on the .htaccess
	# -- CACHE and PERFORMANCE --
	define("CONS_DEFAULT_MIN_OBJECTCACHETIME",15); // (s) time for default object time caches (they are always common-to-all-users caches)
	define("CONS_DEFAULT_MAX_OBJECTCACHETIME",300); // used to calculate best cache time from cachecontrol.dat
	define("CONS_DEFAULT_MIN_BROWSERCACHETIME",1); // (s) too large a time will break statistics. This is for the WHOLE PAGE (checkActions runs everytime)
	define("CONS_DEFAULT_MAX_BROWSERCACHETIME",25); // used to calculate best cache time from cachecontrol.dat
	define("CONS_PM_MINTIME"				,80); // (ms) this is the minimum expected average for performance (for 1 million hits a day, max serve time is 83ms)
	define("CONS_PM_TIME"					,1000); // (ms) performance manager timer, this should be the BIGGEST time you expect a page load, if exceeded, will trigger warning on the admin
	define("CONS_FREECPU"					,false); // if the server is too busy (average too high, cache at max), will add a 50ms idle on each hit
	# -- MONITORING and ERROR --
	//define("CONS_HTTPD_ERRDIR"				,"../logs/"); // which folder (relative to the index.php) the httpd error file is located (usually error_log or err_log). USED ONLY ONLINE (end with / if not root)
	//define("CONS_HTTPD_ERRFILE"				,"error_log{Y}{m}{d}"); // which filename is used inside such dir. Supports {Y}{m}{d}
	define("CONS_HTTPD_ERRDIR"				,""); // which folder (relative to the index.php) the httpd error file is located (usually error_log or err_log). USED ONLY ONLINE
	define("CONS_HTTPD_ERRFILE"				,"error_log"); // which filename is used inside such dir. Supports {Y}{m}{d}
	# -- Master overrides --
	define("CONS_SINGLEDOMAIN"				,""); // set the name of the folder inside /pages/ that will ALWAYS result this install, thus disabling domain handling (faster)
	define("CONS_SITESELECTOR"				,true); // on first access, will ask which site to serve. Works only on production
# CHANGE THIS OR MASTER USERS CAN'T LOG:
	define("CONS_MASTERPASS"				,""); // if not "", this will override $masterOverride in ALL sites, accepts {CODE} {YEAR} {MONTH} {DAY} {DOMAIN}
# FILL THIS:
	define("CONS_MASTERMAIL"				,""); // for fatal errors
	define("CONS_ACCEPT_DIRECTLINK"			,true); // will accept direct link to the files/ folder with domain/files/..., redirecting to /pages/[code]/files/ by PHP (dangerous if serving large files)
	define("CONS_NOROBOTDOMAINS"			,""); // these will enforce crawlers NOT to index the domains in this list
	define("CONS_DEFAULT_IPP"				,50); // default IPP (Itens per page)

	# -- BEHAVIOUR/SAFETY SETTINGS --
	if (CONS_ONSERVER) { # When on main server/deployed
		define("CONS_OVERRIDE_DB"		,""); // ALL sites will use this database (blank to allow different db per site)
		define("CONS_OVERRIDE_DBUSER"	,""); // ALL sites will use this database user (blank to allow different users per site)
		define("CONS_OVERRIDE_DBPASS"	,""); // ALL sites will use this database password (blank to allow different passwords per site)

		### DO NOT LEAVE CONS_DEVELOPER ON AT SERVER ###
		define ("CONS_DEVELOPER"		,false); // Running full debug version (coreFull)
		### ---------------------------------------- ###
		define ("CONS_FOWARDER"			,true); // after an action on the admin, foward to the same page to prevent the refresh "repost" issue
		define ("CONS_CACHE"			,true); // cache main toggle
		define ("CONS_DEFAULT_CACHETIME",30); // default cache in seconds, when not handled by the main cache handler
		define ("CONS_HIDE_MYSQLDOWN"	,true); // If DB is gone, try to use caches and serve them (or abort with a 503 error instead an ugly SQL error)
		define ("CONS_MASTERDOMAINS"	,"www.prescia.net"); # these can access the master control (if available)

		define ("CONS_TIMELIMIT"		,40); # PHP time limit.
		define ("CONS_TIMEWARNING"		,35); # time considered too long to proceed (about 5~10 below CONS_TIMELIMIT), will try a soft abort
		define ("CONS_SLOWQUERY_TH"		, 2); # queries that take longer than this will trigger an internal (log) warning
		define ("CONS_MAXRUNCONTENTSIZE",50000); // lists larger then this will result a fatal error due to overflow of the TC system (paging should prevent this altogether, so this catches infinite loops)

		define("CONS_BOTPROTECT"		,false); // monitors calls from same IP and prevent too many in a short time (see CONS_BOTPROTECT_MAXHITS)
		define("CONS_BOTPROTECT_MAXHITS",35); // how many hits one IP can perform per MINUTE. More than that will trigger a temporary IP ban
											  // KEEP IN MIND this also count ajax and other hits. So be conservative
		define("CONS_BOTPROTECT_BANTIME",5); // how long, in minutes, someone caught by BOTPROTECT should be banned from the system

		define("CONS_SANATIZEREQUEST",	false); // if TRUE, will totally ignore 'nosession' (note this will still be accepted on library level),'debugmode' and 'nocache' queries. Safe for stable finished projects
		define("CONS_CRONDBBACKUP",		true); // if true, will obbey _scheduledCronDay and _scheduledCronDayHour to make a backup of the database

	} else { # When on local PRODUCTION system (same as above)

		define("CONS_OVERRIDE_DB"		,"localhost");
		define("CONS_OVERRIDE_DBUSER"	,"root");
		define("CONS_OVERRIDE_DBPASS"	,"root");

		define ("CONS_DEVELOPER"		,true);
		define ("CONS_FOWARDER"			,true);
		define ("CONS_CACHE"			,true);
		define ("CONS_DEFAULT_CACHETIME",5);
		define ("CONS_HIDE_MYSQLDOWN"	,false);
		define ("CONS_MASTERDOMAINS"	,"localhost,127.0.0.1");

		define ("CONS_TIMELIMIT"		,90);
		define ("CONS_TIMEWARNING"		,85);
		define ("CONS_SLOWQUERY_TH"		,6);
		define ("CONS_MAXRUNCONTENTSIZE",30000);

		define("CONS_BOTPROTECT"		,false);
		define("CONS_BOTPROTECT_MAXHITS",60);

		define("CONS_BOTPROTECT_BANTIME",1);

		define("CONS_SANATIZEREQUEST",	false);
		define("CONS_CRONDBBACKUP",		false);

	}

	# -- OPERATION SETTINGS --
	// when searching a file, search this extensions first for faster seek
	define ("CONS_FILESEARCH_EXTENSIONS","jpg,gif,png,swf,mp3,doc,pdf,txt,odt,docx,xls,xlsx,exe,zip,rar,psd,mp4,avi,mpg,mpeg,flv,7z,mkv,wmv,gz");
	define ("CONS_TOOLS_DEFAULTPERM","10010011100000000000"); // default permissions if not set
	define ("CONS_GZIP_MINSIZE",20000); // pages SMALLER then this (bytes) won't get gzipped (waste of resources)

	if (CONS_SANATIZEREQUEST) {
		unset($_REQUEST['nosession']);
		unset($_REQUEST['nocache']);
		unset($_REQUEST['debugmode']);
		unset($_REQUEST['forcecron']);
	}

	# -- Function/libraries used
	/* FUNCTION USAGE (other than basic bundle) ON Prescia 14.8.21 :

	TC: 'makeDirs','utf8_truncate','htmlentities_ex','fd','stripHTML'
	CORE: 'makeDirs','recursive_del', 'listFiles', 'removeSimbols', 'locateFile', 'listFiles','datecalc'
	COREFULL: 'listFiles','xmlHandler', 'safe_mkdir', 'recursive_del', 'makeDirs'
	TCE: 'arrayToString'
	CACHEC: 'arrayToString', 'recursive_del', 'makeDirs', 'removeSimbols', 'time_diff'
	ERRORC: 'safe_mkdir'
	INTLC: 'safe_mkdir'
	MODULES: 'humanSize', 'ttree', 'locateAnyFile', 'makeDirs', 'safe_mkdir', 'storefile', 'resizeImage', 'resizeImageCond', 'cleanString', 'removeSimbols', 'isData'

	*/

	include_once CONS_PATH_INCLUDE."basic.php";				# bundle (scriptTime, isMail, cReadfile, cWriteFile, removeBOM, fv)
	include_once CONS_PATH_INCLUDE."ttree.php";				# class
	include_once CONS_PATH_INCLUDE."dirBundle.php";			# bundle (safe_mkdir, safe_chmod, makeDirs)
	include_once CONS_PATH_INCLUDE."datetime.php";			# bundle (all date/time related)
	include_once CONS_PATH_INCLUDE."inputSanatizing.php";	# bundle (cleanString, cleanHTML, addslashes_EX, stripHTML)
	include_once CONS_PATH_INCLUDE."imgHandler.php"; 		# bundle (resizeImageCond, resizeImage, watermark)
	if (CONS_DEVELOPER || isset($_REQUEST['debugmode'])) include_once CONS_PATH_INCLUDE."xmlHandler.php";		# class  (used only by coreFull on vanilla aff, but some plugins might use it too, load if necessary THERE)
	#include CONS_PATH_INCLUDE."zipfile.php";				# class (still used?)

	# --
	include CONS_PATH_INCLUDE."arrayToString.php";
	#include CONS_PATH_INCLUDE."checkHTML.php"; # used by bi_dev, included as required
	#include CONS_PATH_INCLUDE."chmod_all.php";
	#include CONS_PATH_INCLUDE."filetypeIcon.php"; # used by bi_adm, included as required
	#include CONS_PATH_INCLUDE."getBrowser.php"; # called later
	#include CONS_PATH_INCLUDE."getMime.php"; # core will test and include as required
	include CONS_PATH_INCLUDE."htmlentities_ex.php";
	include CONS_PATH_INCLUDE."listFiles.php";
	#include CONS_PATH_INCLUDE."loadURL.php";
	include CONS_PATH_INCLUDE."locateFile.php"; # use CONS_FILESEARCH_EXTENSIONS
	include CONS_PATH_INCLUDE."locateAnyFile.php"; # use CONS_FILESEARCH_EXTENSIONS
	include CONS_PATH_INCLUDE."quota.php";
	#include CONS_PATH_INCLUDE."recursive_copy.php";
	include CONS_PATH_INCLUDE."recursive_del.php";
	include CONS_PATH_INCLUDE."removeSimbols.php";
	include CONS_PATH_INCLUDE."sendMail.php";
	include CONS_PATH_INCLUDE."storeFile.php";
	include CONS_PATH_INCLUDE."utf8_truncate.php";
