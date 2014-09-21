<?	# -------------------------------- Prescia International (i18n/l10n) control
	# Requires datetime.php

class CintlControl {

	var $parent = null;
	var $i18n = array(  #		dec  th  date	 date preg (opt 4 digit year)							   preg Y M D positions (0 all)
						#						  12           3        45           6        7							 currency
				'pt-br' => array(",",".","d/m/Y","(([0-9]{1,2})([^0-9]))(([0-9]{1,2})([^0-9]))([0-9]{2,4})",array(7,5,2),'BRL','R$'),
				'en'    => array(".",",","m/d/Y","(([0-9]{1,2})([^0-9]))(([0-9]{1,2})([^0-9]))([0-9]{2,4})",array(7,2,5),'USD','U$'),
				'es'    => array(",",".","m/d/Y","(([0-9]{1,2})([^0-9]))(([0-9]{1,2})([^0-9]))([0-9]{2,4})",array(7,2,5),'EUR','€'),
				'jp'    => array(".",",","Y/m/d","(([0-9]{2,4})([^0-9]))(([0-9]{1,2})([^0-9]))([0-9]{1,2})",array(2,5,7),'JPY','¥'),
				);
	var $selectedCode = "";

	function __construct(&$parent) {
		$this->parent = &$parent;
	}
#-
	function getDec($code="") {
		if ($code=="") $code = $this->selectedCode;
		return isset($this->i18n[$code])?$this->i18n[$code][0]:'.';
	}
#-
	function getTSep($code="") {
		if ($code=="") $code = $this->selectedCode;
		return isset($this->i18n[$code])?$this->i18n[$code][1]:',';
	}
#-
	function getDate($code="") {
		if ($code=="") $code = $this->selectedCode;
		return isset($this->i18n[$code])?$this->i18n[$code][2]:'d/m/Y';
	}
#-
	function getDatePreg($code="") {
		if ($code=="") $code = $this->selectedCode;
		return isset($this->i18n[$code])?$this->i18n[$code][3]:'(([0-9]{1,2})([^0-9]))(([0-9]{1,2})([^0-9]))([0-9]{2,4})';
	}
#-
	function getCurrency($getCode) {
		return isset($this->i18n[$code])?($getCode?$this->i18n[$code][6]:$this->i18n[$code][5]):'$';
	}
#-
	function mergeDate($data,$prefix) { // data cames in array as dataday, datamonth, datayear etc, returns in MySQL format
		$theDate = "";
		$dateFormat = $this->getDate();
		$theDate = str_replace("d",$data[$prefix."day"],$dateFormat);
		$theDate = str_replace("m",$data[$prefix."month"],$theDate);
		$theDate = str_replace("Y",$data[$prefix."year"],$theDate);
		if (isset($data[$prefix."hour"]) && isset($data[$prefix."min"])) {
			# also with hour/minutes
			$data[$prefix."hour"] = (int)$data[$prefix."hour"];
			$data[$prefix."min"] = (int)$data[$prefix."min"];
			$date[$prefix."sec"] = isset($data[$prefix."sec"])?(int)$data[$prefix."sec"]:0;
			$theDate .= " ".($data[$prefix."hour"]<10?"0":"").$data[$prefix."hour"].":".
						($data[$prefix."min"]<10?"0":"").$data[$prefix."min"].":".
						(isset($data[$prefix."sec"])?($data[$prefix."sec"]<10?"0":"").$data[$prefix."sec"]:"00");
		} else
			$theDate .= " 00:00:00";
		return $this->dateToSql($theDate);
	}
#-
	function dateToSql($theDate,$isDateTime=false) { // theDate is the date in a string (complete with full time), typed by humans (but already parsed for errors)
		//				  12		   3        4          56       7           8
		if (preg_match("/^(([0-9]{1,2})([^0-9])([0-9]{1,2})(([^0-9])([0-9]{1,2})([^0-9]+))?)?".$this->getDatePreg().'$/',$theDate,$regs)) { // date
			$pregPositions = isset($this->i18n[$this->selectedCode])?$this->i18n[$this->selectedCode][4]:array(7,5,2);
			if ($regs[2] == '') $regs[2] = '00';
			else if (strlen($regs[2])==1) $regs[2] = '0'.$regs[2];
			if ($regs[4] == '') $regs[4] = '00';
			else if (strlen($regs[4])==1) $regs[4] = '0'.$regs[4];
			if ($regs[7] == '') $regs[7] = '00';
			else if (strlen($regs[7])==1) $regs[7] = '0'.$regs[7];
			if ($regs[8+$pregPositions[0]] == '') $regs[8+$pregPositions[0]] = '0000';
			else if ($regs[8+$pregPositions[0]]<ADODB_TWODIGITYEAR_OFFSET) {
				$regs[8+$pregPositions[0]] += 2000;
			} else { # $pregPositions 0 is the year, 4 digits
				while (strlen($regs[8+$pregPositions[0]])<4)
					$regs[8+$pregPositions[0]] = '0'.$regs[8+$pregPositions[0]];
			}
			if ($regs[8+$pregPositions[1]] == '') $regs[8+$pregPositions[1]] = '00';
			else if (strlen($regs[8+$pregPositions[1]])==1) $regs[8+$pregPositions[1]] = '0'.$regs[8+$pregPositions[1]];
			if ($regs[8+$pregPositions[2]] == '') $regs[8+$pregPositions[2]] = '00';
			else if (strlen($regs[8+$pregPositions[2]])==1) $regs[8+$pregPositions[2]] = '0'.$regs[8+$pregPositions[2]];
			return $regs[8+$pregPositions[0]]."-".$regs[8+$pregPositions[1]]."-".$regs[8+$pregPositions[2]]." ".$regs[2].":".$regs[4].":".$regs[7];
		} else if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})( ([0-9]{2}):([0-9]{2}):([0-9]{2}))?$/',$theDate)) # raw SQL
			return $theDate;
		else
			return false;
	}
#-
	function formatValue($value,$code="") {
		if ($code=="") $code = $this->selectedCode;
		$valor = str_replace(",",".",$valor);
		if (strpos($valor,".")>0) {
			$valor = explode(".",$valor);
			$last = array_pop($valor);
			$valor = implode("",$valor).".".$last;
		}
  		return number_format($valor,2,$this->getDec($code),$this->getTSep($code));
	}
#-
	function removeLanguageTags() {
		$langs = explode(",",CONS_POSSIBLE_LANGS);
		if (!isset($_SESSION[CONS_SESSION_LANG]))
			$_SESSION[CONS_SESSION_LANG] = CONS_DEFAULT_LANG;
		foreach ($langs as $lang) {
			if ($lang != $_SESSION[CONS_SESSION_LANG])
				$this->parent->template->assign("_i18n_".$lang);
		}
	}
#-
	/* loadLocaleLang
	 * Sets language to be used (forced, by request or by IP)
	 */
	function loadLocale($code='') {
		if ($code == '' || strpos(CONS_POSSIBLE_LANGS.",",$code.",") === false) $code = CONS_DEFAULT_LANG; # invalid language or no language set at this site!
		$this->selectedCode = $code;
		return $code;
	} # loadLocale
#-
	/* loadLangFile
	 * Loads the cache or original (and then create the cache) of a language file, either from the current site or default (standard)
	 */
	function loadLangFile($file,$standard=true,$plugin='') {
		# loads a templating language file to the template, checks if cache is present
		# called by /index.php
		$file .= ".php";
		$strippedFile = str_replace("/","_",$file);
		if ($standard) {
			if ($plugin == "")
				$file = CONS_PATH_SETTINGS."locale/".$file;
			else
				$file = CONS_PATH_SYSTEM."plugins/$plugin/locale/$file";
		} else {
			$file = CONS_PATH_PAGES.$_SESSION['CODE']."/_config/locale/$file";
		}
		if (!is_file($file)) return false;
		if (!isset($_REQUEST['nocache'])) { # if nocache is specified, ignore caches ... not the case
			if ($standard) {
				if ($plugin!='') $plugin .= '/';
				if (!is_dir(CONS_PATH_CACHE."locale/$plugin")) safe_mkdir(CONS_PATH_CACHE."locale/$plugin");
				$cacheFile = CONS_PATH_CACHE."locale/$plugin".$strippedFile.".cache";
				$cacheMTFile = CONS_PATH_CACHE."locale/$plugin".$strippedFile.".cachemd";
			} else {
				if (!is_dir(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/locale/")) safe_mkdir(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/locale/");
				$cacheFile = CONS_PATH_CACHE.$_SESSION['CODE']."/meta/locale/".$strippedFile.".cache";
				$cacheMTFile = CONS_PATH_CACHE.$_SESSION['CODE']."/meta/locale/".$strippedFile.".cachemd";
			}
			if (is_file($cacheFile) && is_file($cacheMTFile)) {
				$ofMD = filemtime($file); # modify date of ORIGINAL file
				$cMD = cReadFile($cacheMTFile); # modify date of ORIGINAL file when CACHE file was created
				if ($cMD == $ofMD) { # valid cache file (it was created from the current original file)
					$newData = @unserialize(cReadFile($cacheFile));
					if (is_array($newData)) {
						$this->parent->template->lang_replacer = array_merge($this->parent->template->lang_replacer,$newData);
						return true;
					} else
						$this->parent->errorControl->raise(6,$_SESSION[CONS_SESSION_LANG],$plugin,$standard?"standard":"non-standard");
				} else if ($this->parent->debugmode && CONS_CACHE) {
					# Warning: if the lang file was replaced, template caches might be invalid
					# So we must delete ALL TEMPLATE CACHES!
					$this->parent->cacheControl->dumpTemplateCaches();
				}
			}
		}
		# no cache available or no cache specified
		$data = include($file);
		if ($data===false || !is_array($data)) {
			$this->parent->errorControl->raise(7,$_SESSION[CONS_SESSION_LANG],$plugin,$standard?"standard":"non-standard");
			return false;
		}
		if (!isset($_REQUEST['nocache'])) {
			$ofMD = filemtime($file);
			cWriteFile($cacheMTFile,$ofMD);
			cWriteFile($cacheFile,serialize($data));
		}
		foreach ($data as $term => $trans) {
			$this->parent->template->lang_replacer[$term] = $trans; // array_merge has issues
		}
		return true;
	} # loadLangFile
#-
	/* langOut
	 * Returns the translation given the current i18n (if enabled) of a hash string
	 */
	function langOut($tag) {
		#searches the template language replacer for this tag. Will ignore trailing ! ? . :
		$ltag = strtolower($tag);
		$trailing = "";
		if (strlen($tag)>1 && strpos("!?.:",$tag[strlen($tag)-1])!==false) {
			$trailing = strlen($tag)>0?$tag[strlen($tag)-1]:'';
			$ltag = substr($tag,0,strlen($tag)-1);
		}
		if (isset($this->parent->template->lang_replacer[$ltag]))
			return $this->parent->template->lang_replacer[$ltag].$trailing;
		else if (strpos($ltag,"_") !== false) {
			$etag = explode("_",$ltag);
			array_shift($etag);
			$etag = implode("_",$etag);
			if (isset($this->parent->template->lang_replacer[$etag]))
				return $this->parent->template->lang_replacer[$etag].$trailing;
		}
		return $tag;
	} # langOut
#-
}

