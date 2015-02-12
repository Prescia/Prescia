<?	# -------------------------------- Prescia cache control
	# NOTE: all caches automatically add LANGUAGE and USER ID (if not common)

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
		$keyOne = $this->parent->original_context_str.$this->parent->original_action.$this->cacheseed.$this->cacheuid();
		$keyTwo = arrayToString($_GET,array("__utma","__utmb","__utmc","__utmz","__atuvc","PHPSESSID"),true); # Remove Google big-brother shit
		$keyOne = md5($keyOne); // shorten and standardize a little
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
	function cacheuid($shared=false) { // returns a string with a unique cache key for the most common differences among users
		return $_SESSION[CONS_SESSION_LANG].(!$shared && $this->parent->logged()?$_SESSION[CONS_SESSION_ACCESS_USER]['id']:'x').md5($this->parent->domain);
	}
#-
	function getCachedContent($tag,$expiration=-1) { // $expiration=0 to ignore expiration alltogether
		if (isset($_REQUEST['nocache']) || !CONS_CACHE) return false;
		if ($expiration==-1) $expiration = $this->parent->cachetimeObj/1000;
		$tag = removeSimbols($this->cacheuid(true).$tag,true,false);
		$tag_ns = removeSimbols($this->cacheuid(false).$tag,true,false);
		if (isset($_SESSION[CONS_SESSION_CACHE]) && isset($_SESSION[CONS_SESSION_CACHE][$tag_ns])) { // in the session is not shared
			$passedSeconds = time_diff(date("Y-m-d H:i:s"),$_SESSION[CONS_SESSION_CACHE][$tag_ns]['time']);
			if (($expiration==0 || $expiration>$passedSeconds)) {
				return $_SESSION[CONS_SESSION_CACHE][$tag_ns]['payload'];
			}
		} else if (is_file(CONS_PATH_CACHE.$_SESSION["CODE"]."/caches/$tag.cache")) { // in file is shared cache
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
	function addCachedContent($tag,$content,$shared=false) {
		if ($content == '' || $content === false) return; // we don't take empty caches, sorry
		$tag = removeSimbols($this->cacheuid($shared).$tag,true,false);
		// feel free to use memcached on the $shared mode instead of files ;)
		if ($shared) {
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
		$tag = removeSimbols($this->cacheuid(true).$tag,true,false);
		$tag_ns = removeSimbols($this->cacheuid(false).$tag,true,false);
		if (isset($_SESSION[CONS_SESSION_CACHE]) && isset($_SESSION[CONS_SESSION_CACHE][$tag_ns])) { // not shared
			$passedSeconds = time_diff(date("Y-m-d H:i:s"),$_SESSION[CONS_SESSION_CACHE][$tag_ns]['time']);
			return $passedSeconds*1000;
		} else if (is_file(CONS_PATH_CACHE.$_SESSION["CODE"]."/caches/$tag.cache")) { // shared
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
		if (!CONS_ONSERVER) { // local, we want to see up-to-date, so force 1s caches
			$this->parent->cachetime = 1000;
			$this->parent->cachetimeObj = 1000;
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
	function killCache($tag) { // kills all caches for this tag (supports a * at the end)
		$lazymatch = false;
		if ($tag[strlen($tag)-1] == "*") {
			$lazymatch = true;
			$tag =substr($tag,0,strlen($tag)-1);
		}
		// shared caches
		$tags = removeSimbols($this->cacheuid(true).$tag,true,false);
		if ($lazymatch) {
			$files = listFiles(CONS_PATH_CACHE.$_SESSION["CODE"]."/caches/","/^".$tags."/");
			foreach ($files as $file) {
				@unlink($file);
			}
		} else {
			$file = CONS_PATH_CACHE.$_SESSION["CODE"]."/caches/$tag.cache";
			@unlink($file);
		}
		
		$tagns = removeSimbols($this->cacheuid(false).$tag,true,false);
		if ($lazymatch) {
			$size = strlen($tagns);
			foreach ($_SESSION[CONS_SESSION_CACHE] as $key => $cache) {
				if (substr($key,$size) == $tag) unset($_SESSION[CONS_SESSION_CACHE][$key]);
			}
		} else
			unset($_SESSION[CONS_SESSION_CACHE][$tagns]);
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