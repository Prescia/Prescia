<?	# -------------------------------- Prescia cache control

class CCacheControl {

	var $parent = null;
	var $cachepath = '';
	var $cacheseed = '';
	var $contentFromCache = false;
	var $noCache = false; // some script requested that this page not to be cached

	function __construct(&$parent) {
		$this->parent = &$parent;
	}

	function cacheFile() {
		# Returns which cache file should be used for this page
		$keyOne = $this->parent->original_context_str.$this->parent->original_action.$this->cacheseed;
		$keyTwo = arrayToString($_GET,array("__utma","__utmb","__utmc","__utmz","__atuvc","PHPSESSID"),true); # Remove Google big-brother shit
		$keyOne = md5($keyOne);
		$keyTwo = md5($keyTwo);
		return $this->cachepath.$keyOne.$keyTwo.".cache";
	} # cacheFile
#-
	function renderCache() {
		$file = $this->cacheFile();
		if (is_file($file))	{
			$this->contentFromCache = true;
			return cReadFile($file);
		}
		$this->parent->fastClose('503'); # unavailable
	} # renderCache
#-
	function setCache($PAGE) {
		if (!$this->noCache)
			cWriteFile($this->cacheFile(),$PAGE);
	} # setCache
#-
	function canUseCache($emergency=false) {
		if ($this->parent->layout == 2 || (isset($_POST) && count($_POST)>0) || $this->noCache) return false; # these are exceptions where cache can never be applied
		$file = $this->cacheFile();
		if (is_file($file)) {
			if ($emergency) return true; // we have the file and we MUST serve the cache
			$time = filemtime($file);
			return ($time > time() - ($this->parent->cachetime/1000)); // cache still valid?
		}
		return false;
	} # canUseCache
# -
	function dumpTemplateCaches($alsoDumpLogs=false,$allSites=false) {
		# Some core controler asked all caches to be dumped (also happens on nocache)
		if (CONS_PATH_CACHE != "CONS_PATH_CACHE" && isset($_SESSION['CODE'])) { // this bug could be disastrous
			if ($allSites) {
				$d = cReadFile(CONS_PATH_CACHE."domains.dat");
				recursive_del(CONS_PATH_CACHE,true,"cache");
				cWriteFile(CONS_PATH_CACHE."domains.dat",$d);
			} else
				recursive_del(CONS_PATH_CACHE.$_SESSION['CODE']."/caches/",true,"cache");
			makeDirs(CONS_PATH_CACHE.$_SESSION['CODE']."/caches/");
			if ($alsoDumpLogs) { // just .log to keep .dat, always allsites
				$listFiles = listFiles(CONS_PATH_LOGS,"/^([^a]).*(\.log)$/i",false,false,true);
				foreach ($listFiles as $file)
					@unlink(CONS_PATH_LOGS.$file);
			}
		}

	} # dumpTemplatecaches
#-
	function getCachedContent($tag,$expiration=-1) { // $expiration=0 to ignore expiration alltogether
		if (isset($_REQUEST['nocache']) || !CONS_CACHE) return false;
		if ($expiration==-1) $expiration = $this->parent->cachetimeObj/1000;
		$tag = removeSimbols($_SESSION[CONS_SESSION_LANG].$tag,true,false);
		if (isset($_SESSION[CONS_SESSION_CACHE]) && isset($_SESSION[CONS_SESSION_CACHE][$tag])) {
			$passedSeconds = time_diff(date("Y-m-d H:i:s"),$_SESSION[CONS_SESSION_CACHE][$tag]['time']);
			if (($expiration==0 || $expiration>$passedSeconds)) {
				return $_SESSION[CONS_SESSION_CACHE][$tag]['payload'];
			}
		} else if (is_file(CONS_PATH_CACHE.$_SESSION["CODE"]."/caches/$tag.cache")) {
			$fileContent = unserialize(cReadFile(CONS_PATH_CACHE.$_SESSION["CODE"]."/caches/$tag.cache"));
			if ($expiration != 0) {
				$passedSeconds = time_diff(date("Y-m-d H:i:s"),$fileContent['time']);
				if ($expiration>$passedSeconds) {
					return $fileContent['payload'];
				}
			} else {
				return $fileContent['payload'];
			}
		}
		return false;
	}
#-
	function addCachedContent($tag,$content,$common=false) {
		if ($content == '' || $content === false) return; // we don't take empty caches, sorry
		$tag = removeSimbols($_SESSION[CONS_SESSION_LANG].$tag,true,false);
		// feel free to use memcached on the $common mode instead of files ;)
		if ($common) {
			$file = CONS_PATH_CACHE.$_SESSION["CODE"]."/caches/$tag.cache";
			$data = array('time' => date("Y-m-d H:i:s"), 'payload' => $content);
			if (!is_dir(CONS_PATH_CACHE.$_SESSION['CODE']."/caches/"))
				makeDirs(CONS_PATH_CACHE.$_SESSION['CODE']."/caches/");
			cWriteFile($file,serialize($data));
		} else {
			if (!isset($_SESSION[CONS_SESSION_CACHE])) $_SESSION[CONS_SESSION_CACHE] = array();
			$_SESSION[CONS_SESSION_CACHE][$tag] = array('time' => date("Y-m-d H:i:s"), 'payload' => $content);
		}
	}
#-
	function cacheAge($tag) { // returns data in ms
		$tag = removeSimbols($_SESSION[CONS_SESSION_LANG].$tag,true,false);
		if (isset($_SESSION[CONS_SESSION_CACHE]) && isset($_SESSION[CONS_SESSION_CACHE][$tag])) {
			$passedSeconds = time_diff(date("Y-m-d H:i:s"),$_SESSION[CONS_SESSION_CACHE][$tag]['time']);
			return $passedSeconds*1000;
		} else if (is_file(CONS_PATH_CACHE.$_SESSION["CODE"]."/caches/$tag.cache")) {
			$fileContent = unserialize(cReadFile(CONS_PATH_CACHE.$_SESSION["CODE"]."/caches/$tag.cache"));
			$passedSeconds = time_diff(date("Y-m-d H:i:s"),$fileContent['time']);
			return $passedSeconds*1000;
		} else
			return false;
	}
#-
	function startCaches() {
		// default on error
		$this->parent->storage['CORE_CACHECONTROL'] = array();
		$this->parent->storage['CORE_CACHECONTROL'][] = CONS_PM_MINTIME; // actual cache
		$this->parent->storage['CORE_CACHECONTROL'][] = 0.5; // factor
		if (!CONS_ONSERVER) {
			$this->parent->cachetime = 100000;
			$this->parent->cachetimeObj = 100000;
			return;
		}
		// load default in case we fail to load cachecontrol.dat
		$this->parent->cachetime = 1000*floor(CONS_DEFAULT_MIN_BROWSERCACHETIME+(CONS_DEFAULT_MAX_BROWSERCACHETIME - CONS_DEFAULT_MIN_BROWSERCACHETIME)/2);
		$this->parent->cachetimeObj = 1000*floor(CONS_DEFAULT_MIN_OBJECTCACHETIME+(CONS_DEFAULT_MAX_OBJECTCACHETIME - CONS_DEFAULT_MIN_OBJECTCACHETIME)/2);
		// loads cachecontrol
		if (is_file(CONS_PATH_CACHE."cachecontrol.dat")) {
			$cc = unserialize(cReadFile(CONS_PATH_CACHE."cachecontrol.dat")); // lists the last 10 page times, first item is the average, second is the cache modifier
			if (is_array($cc)) {
				$this->parent->storage['CORE_CACHECONTROL'] = $cc;
				$average = $cc[0];
				$cmod = ($average-CONS_PM_MINTIME)/(CONS_PM_TIME-CONS_PM_MINTIME);
				if ($cmod<0)$cmod=0;
				if ($cmod>1)$cmod=1;
				$this->parent->cachetime = floor(1000*(CONS_DEFAULT_MIN_BROWSERCACHETIME + (CONS_DEFAULT_MAX_BROWSERCACHETIME - CONS_DEFAULT_MIN_BROWSERCACHETIME) * $cmod));
				$this->parent->cachetimeObj = floor(1000*(CONS_DEFAULT_MIN_OBJECTCACHETIME + (CONS_DEFAULT_MAX_OBJECTCACHETIME - CONS_DEFAULT_MIN_OBJECTCACHETIME) * $cmod));
				$this->parent->storage['CORE_CACHECONTROL'][1] = $cmod; // update cache

			} // else will use default
		} // else will use default
	}
	function updateCacheControl($thisTime) {
		if (!isset($this->parent->storage['CORE_CACHECONTROL'])) $this->startCaches();
		// the cachecontrol is an array with 52 items: AVERAGE load time, CACHE MODIFIER, 50 last hit times
		$average = $this->parent->storage['CORE_CACHECONTROL'][0];
		$cmod =  $this->parent->storage['CORE_CACHECONTROL'][1];
		$newCC = array();
		for ($c=2;$c<count($this->parent->storage['CORE_CACHECONTROL']);$c++)
			$newCC[] = $this->parent->storage['CORE_CACHECONTROL'][$c];
		if (count($newCC)==50) array_shift($newCC); // remove oldest
		$newCC[] = floor($thisTime);
		// calcs new average
		$sum = 0;
		for ($c=0;$c<count($newCC);$c++) {
			$sum += $newCC[$c];
		}
		$average = $sum/count($newCC);
		array_unshift($newCC,$cmod); // cmod already changed on startCaches
		array_unshift($newCC,$average);
		cWriteFile(CONS_PATH_CACHE."cachecontrol.dat",serialize($newCC));
		if (CONS_FREECPU && $cmod >= 0.9) usleep(50);
	}
	function logCacheThrottle() {
		if (!isset($this->parent->storage['CORE_CACHECONTROL'])) return;
		if (!isset($this->parent->storage['CORE_CACHECONTROL'])) $this->startCaches();
		$average = $this->parent->storage['CORE_CACHECONTROL'][0];
		$cmod =  $this->parent->storage['CORE_CACHECONTROL'][1];
		$cc = array();
		if (is_file(CONS_PATH_LOGS."cachecontrol.dat")) {
			$cc = unserialize(cReadFile(CONS_PATH_LOGS."cachecontrol.dat"));
			if (!is_array($cc)) $cc = array();
		}
		$thisEntry = array(date("Y-m-d H:i:s"),$average,$cmod);
		$cc[] = $thisEntry;
		// cleanup to show only the whole last week (24*7=168)
		while(count($cc)>168) array_shift($cc);
		cWriteFile(CONS_PATH_LOGS."cachecontrol.dat",serialize($cc));
	}
#-

}
