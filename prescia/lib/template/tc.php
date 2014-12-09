<? /* ------------------------
   | Caio's Template Engine
   | Version: 6.0 (simpla version)
   | (cc) Caio Vianna de Lima Netto
--*/
# REQUIRES main.php

define ("CKTemplate_version","141204"); // Build version - yes this is a date

define ("EREG_TAG","/(\{)([^\n\r]+)(\})/"); // used inside parsers, but not on tbreak
define ("START_REPLACE", "\\"); // this string inside the limiters will generate the start limiter ({\} will output {)
define ("CLASSSEP_TAG","|"); // class dinamics separator
define ("PARAMSEP_TAG","|"); // class parameter separator
define ("CKAUTORESET",false); // will automatically call the reset function before a techo(); Use only if you are getting weird issues with internal tags and don't want to call reset yourself

class CKTemplate {

  var $contents; // 0 = tag name, 1 = parameters, 2 = contents, 3 = unformated content
  var $debugmode = false;
  var $cache = null;
  var $path = "";
  var $cachepath = ""; // <-- leave blank not to use caching
  var $cacheSeed = ""; // <-- append this to cached filenames (example: language ID)
  // set the following according to your region (note that once a template is loaded, each tag will be a template object with it's own setting. To apply the settings of an object into all it's nested templates, use reset();
  var $decimais = 2;
  var $std_date = "d/m/Y";
  var $std_datetime = "H:i d/m/Y";
  var $str_monthlabels = array("Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro");
  var $str_daylabels = array("Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado");
  var $str_intervals = array("Segundos","Minutos","Horas","Dias","Meses");
  var $std_decimal = ".";
  var $std_tseparator = ",";
  var $lang_replacer = array();
  var $lang_selectors = array(); // i18n selectors			\_> same as removei18n tags from Prescia, but automatic
  var $current_language = ""; // which lang_selector to use /
  // internals
  var $errorsDetected = false;
  var $internal_cache;
  var $constants = array();
  var $cached = false;
  var $firstReturnedSet = false; // ona list, will preserve the first recordset (fullpage)
  var $lastReturnedSet = false; // on a list, will preserve the last recordset (fullpage)
  var $externalClasses = false; // classes to run on templating
  var $stackedTags = array(); // used externally to control nested tags to prevent loop
  // change this to register new classes
  var $varToClass = array(); // if this come as a variable, consider it as a class ({abc|...} => {@|abc|...})

  private $iec = 0; // anti-loop device

  function __construct($parent = null, $mypath = "", $debugmode = false) { // parent must be a CKTemplate too
	$this->clear();
	$this->cache = null;
	if ($parent == null) {
	  $this->debugmode = $debugmode;
	  $this->decimais = 2;
	  $this->path = $mypath;
	} else {
	  $this->debugmode = $debugmode || $parent->debugmode;
	  $this->str_monthlabels = $parent->str_monthlabels;
	  $this->str_intervals = $parent->str_intervals;
	  $this->str_daylabels = $parent->str_daylabels;
	  $this->decimais = (int)$parent->decimais;
	  $this->std_date = $parent->std_date;
	  $this->std_datetime = $parent->std_datetime;
	  $this->std_decimal = $parent->std_decimal;
	  $this->std_tseparator = $parent->std_tseparator;
	  $this->path = $mypath == "" ? $parent->path : $mypath;
	  $this->lang_replacer = &$parent->lang_replacer;
	  $this->constants = &$parent->constants;
	  $this->cacheSeed = $parent->cacheSeed;
	  $this->externalClasses = &$parent->externalClasses;
	  $this->varToClass = $parent->varToClass;
	  $this->lang_selectors = $parent->lang_selectors;
	  $this->current_language = $parent->current_language;
	}
  }
  public function __clone() {
  	$this->flushcache();
	foreach ($this->contents as $idx => &$ct) {
		if (is_object($ct[2])) {
			$this->contents[$idx][2] = clone $this->contents[$idx][2];
		}
	}
  }
  public function removeLanguageTags() {
		foreach ($this->lang_selectors as $lang) {
			if ($lang != $this->current_language)
				$this->assign("_i18n_".$lang);
		}
  }
  public function populate() { // applies tags to $str_monthlabels and $str_intervals
  	for ($c=1;$c<=12;$c++)
		$this->str_monthlabels[$c] = $this->lang_replacer['month'.str_pad($c,2,'0',STR_PAD_LEFT)];
	for ($c=0;$c<7;$c++)
		$this->str_daylabels[$c] = $this->lang_replacer['day'.$c];
	$this->str_intervals = array( $this->lang_replacer['seconds'],  $this->lang_replacer['minutes'],  $this->lang_replacer['hours'],  $this->lang_replacer['days']);
  }

  public function reset() { // applies this object's settings to all it's childs
  	foreach ($this->contents as $idx => &$ct) {
  		if (is_object($ct[2])) {
  			$this->contents[$idx][2]->errorsDetected = false;
  			$this->contents[$idx][2]->debugmode = &$this->debugmode;
  			$this->contents[$idx][2]->path = &$this->path;
  			$this->contents[$idx][2]->cachepath = &$this->cachepath;
  			$this->contents[$idx][2]->cacheSeed = &$this->cacheSeed;
  			$this->contents[$idx][2]->decimais = &$this->decimais;
  			$this->contents[$idx][2]->std_date = &$this->std_date;
  			$this->contents[$idx][2]->std_datetime = &$this->std_datetime;
  			$this->contents[$idx][2]->str_monthlabels = &$this->str_monthlabels;
  			$this->contents[$idx][2]->std_decimal = &$this->std_decimal;
  			$this->contents[$idx][2]->std_tseparator = &$this->std_tseparator;
  			$this->contents[$idx][2]->lang_replacer = &$this->lang_replacer;
  			$this->contents[$idx][2]->constants = &$this->constants;
  			$this->contents[$idx][2]->externalClasses = &$this->externalClasses;
  			$this->contents[$idx][2]->varToClass = &$this->varToClass;
  			$this->contents[$idx][2]->reset();
  		}
  	}
	$this->errorsDetected = false;
  }

  public function __sleep() {
  	$this->flushcache();
	$this->errorsDetected = false;
  	return array('contents','varToClass');
  }

  public function __toString() {
	return $this->techo();
  }

  // public
  public function clear($preserve_constants = true) {
	$this->contents = array();
	$this->cache = "";
	$this->internal_cache = array();
	$this->errorsDetected = false;
	if (!$preserve_constants) {
		$this->lang_replacer = array();
		$this->constants = array();
	}
  }

  // returns an array with all tags the current template uses set. If it is a template replacer (_t) will return an array of all keywords.
  // ALL templates will be brought to the root of the array.
  public function getAllTags($forcelower = false) {
  	$output = array();
  	$output["_t"] = array();
  	foreach ($this->contents as $ct) {
		if ($ct[0] != "") {
			if (!is_object($ct[2])) {
				if ($ct[0] == "_t" || $ct[0] == "_T") {
					$output["_t"][] = $ct[2];
				} else
					$output[$forcelower?strtolower($ct[0]):$ct[0]] = true;

			} else {
				if ($ct[0] != "_t" && $ct[0] != "_T")
					$output[$forcelower?strtolower($ct[0]):$ct[0]] = true;
				$temp = $ct[2]->getAllTags($forcelower);
				foreach ($temp as $tag => $content) {
					if ($tag == "_t" || $tag == "_T") {
						foreach ($content as $t_tag) {
							if (!in_array($t_tag,$output["_t"]))
								$output["_t"][] = $t_tag;
						}
					} else
						$output[$forcelower?strtolower($tag):$tag] = true;
				}
			}
		}
  	}
  	return $output;
  }

  // loads an external file into this tag
  // if forcePlainParse will consider it is not a template and will just load the contents into the tag
  public function assignFile($tag,$file,$checkfirst=false,$forcePlainParse=false) {
  	if ($checkfirst) {
  		# checks if tag exists
  		$tagContent = $this->gettxt($tag,true);
  		if ($tagContent === false) return;
  	}
	if ($forcePlainParse) {
		$this->assign($tag,cReadFile($file));
	} else {
		$newtemplate = new CKTemplate($this,$this->path, $this->debugmode);
		$newtemplate->cachepath = $this->cachepath;
		if ($newtemplate->fetch($file))
			$this->assign($tag,$newtemplate);
		else {
			$this->assign($tag,"ERROR ON TEMPLATE FILE \"$file\"<br/>");
			$this->errorsDetected = true;
		}
		$newtemplate = null;
	}
  }

  // reads a file. The encoded file will be saved at the cache
  public function fetch($arquivo) {
	$this->contents = array(); // reset
	$seed = $this->cacheSeed != "" ? $this->cacheSeed."/" : "";
	if (is_file($arquivo)) {

	  if ($this->cachepath != "" && !isset($_REQUEST['nocache'])) {
	  	  $exarquivo = explode("/",$arquivo);
		  $cfile = array_pop($exarquivo);
		  $cpath = substr($arquivo,0,strlen($arquivo)-strlen($cfile));
		  makeDirs($cpath.$seed,$this->cachepath);
		  $cfile = $this->cachepath.$cpath.$seed.$cfile;
		  if (is_file($cfile)) { // cached?
			$thistime = filemtime($arquivo);
			$thattime = filemtime($cfile);
			if ($thistime < $thattime) {
				$temp = cReadFile($cfile);
				$temp = @unserialize($temp);
				if ($temp !== false) {
					$this->copyfrom($temp);
					unset($temp);
					$this->cached = true;
					return true;
				} else {
					echo "Error on cache file $cfile <br/>";
					$this->errorsDetected = true;
					@unlink($cfile); // invalid
				}
		  	}
		  }
	  }
	  $fd = fopen ($arquivo, "rb");
	  $size = filesize($arquivo);
	  if ($size == 0) $this->cache = '';
	  else $this->cache = fread($fd,$size);
	  if ($size>0) removeBOM($this->cache);
	  else $this->cache = "";
	  fclose($fd);
	  $ok = $this->tbreak($this->cache);
	  if ($ok && $this->cachepath != "") {
	  	$temp = $this->cache;
	  	$this->cache = "";
	  	if (count($this->lang_replacer)>0) {
	  		$this->applyCachedLang();
	  	}
		cWriteFile($cfile,serialize($this));
		$this->cache = $temp;
	  }
	  return $ok;
	} else {
	  if ($this->debugmode) echo "TC:fetch File not found: ".$arquivo."<br/>";
	  $this->errorsDetected = true;
	  return false;
	}
  }

  protected function applyCachedLang() {
  	// applies language tags for this version ONLY their content is simple text
	foreach ($this->contents as $idx => $ct) {
  		if (is_object($ct[2])) {
	  		$ct[2]->lang_replacer = &$this->lang_replacer;
	  		$ct[2]->applyCachedLang();
  		} else if (!is_object($ct[2]) && $ct[0] == "_t") {
  			if (isset($this->lang_replacer[$ct[2]])) {
	  			$this->contents[$idx][2] = $this->lang_replacer[$ct[2]];
	  			$this->contents[$idx][0] = "";
			}
  		}
  	}
  }

  public function append($content) {
  	if (is_object($content)) {
	  	$content->flushcache();
	  	foreach ($content->contents as $ct) {
	  		$this->contents[] = $ct;
	  	}
  	} else
  		$this->addcontent('','',$content);
  }

  public function addcontent($nome, $tipo, $conteudo) {
	if (!is_object($conteudo) && $this->havetag($conteudo)) {
	  $temp = $conteudo;
	  $conteudo = new CKTemplate($this);
	  $conteudo->tbreak($temp);
	}
	if ($tipo != "") {
		$tipo = explode(PARAMSEP_TAG,$tipo);
		if (in_array(strtolower($nome),$this->varToClass)) {
			array_unshift($tipo,strtolower($nome)); # variable turned to @, so the original name is a class
			$nome = "@";
		}
	}
	if (is_object($conteudo))
	  $novo = array ( $nome, $tipo, &$conteudo , null);
	else {
	  $novo = array ( $nome, $tipo, $conteudo , $conteudo);
	}
	array_push($this->contents, $novo);
	return $this->contents[count($this->contents)-1];
  }

  public function &get($nome, $noerror=true) {
	$this->flushcache();
	$this->cache = null;
	foreach ($this->contents as $key => $conteudo) {
	  if ($conteudo[0] == $nome) {
		if (!is_object($conteudo[2])) { // literal
		  /*$temp = new CKTemplate($this);
		  // COMENTADO PORQUE ... PORQUE EU QUERO QUEBRAR? VAI RETORNAR ALGO DIFERENTE DO QUE REALMENTE É! Por exemplo, um texto com { vai virar template! (bug: forum posts with { being CACHED as template
		  $temp->tbreak($conteudo[2]);
		  $this->cache = $conteudo[2];
		  return $temp;
		  */
		  return $conteudo[2];
		} else {
		  $this->cache = $this->contents[$key][2]; // not $conteudo[2] because I might require the link if it's an object
		  return $this->cache;
		}
	  } else if (is_object($conteudo[2])) { // search child nodes
	  	$possible_return = $conteudo[2]->get($nome,true);
		if ($possible_return !== false) {
		  return $possible_return;
		}
	  }
	}
	if (!$noerror && $this->debugmode) echo "CKTemplate.get:TAG not found: ".$nome. "<br/>";
	$nao_me_retorne_notices_podres = false; // pointer issues
	return $nao_me_retorne_notices_podres;
  }

  public function gettxt($nome, $noerror=false) {
	$this->flushcache();
	$this->cache = null;
	foreach ($this->contents as $key => $conteudo) {
	  if ($conteudo[0] == $nome) {
		if (is_object($conteudo[2])) { // obj
			return $conteudo[2]->techo();
		} else
			return $conteudo[2];
	  } else if (is_object($conteudo[2])) { // procura nos filhos
		$possible_return = $conteudo[2]->gettxt($nome,true);
		if ($possible_return !== false) {
		  return $possible_return;
		}
	  }
	}
	if (!$noerror && $this->debugmode) echo "CKTemplate.gettxt:TAG not found: ".$nome."<br/>";
	return false;
  }

  public function copyfrom($outro) { // more explicit than clone
  	if (!is_object($outro)) return;
	$outro->flushcache();
	foreach ($outro->contents as $key => $conteudo) {
	  if (!is_object($conteudo[2])) {
	  	array_push($this->contents,$conteudo);
	  } else {
		$x = new CKTemplate($this);
		$x->copyfrom($conteudo[2]);
		array_push($this->contents,array($conteudo[0],$conteudo[1],$x));
	  }
	}
  }

  protected function runclasses($arrayin=false) {
	$this->flushcache();
	foreach ($this->contents as $idx => $ct) {
		// NOT recursive: runclasses run at the techo, and techo will call techo RECURSIVELLY
		// if this is recursive, you have recursive on top of recursive, which will create garbage at class output
		if (is_array($ct[1])) {
  			$this->contents[$idx][2] = $this->runclass($ct[1],$ct[3],$arrayin);
  		}
  	}
	return true;
  }

  protected function runclass($params, $content,$arrayin=false) {
  	// treats input and returns as class definition asks
  	if (is_object($content)) $content = $content->techo();
  	$function = strtolower(array_shift($params));
  	switch ($function) {
  		case "integer":
  			return floor($content);
  		case "float":
  		case "number": // parameters might be: decimal numbers, decimal separator, thousand separator
  			if (!is_numeric($content)) return $content;
			$decimais = $this->decimais;
			$sep = $this->std_decimal;
			$milhar = $this->std_tseparator;
			if (isset($params[0]) && is_numeric($params[0]))
				$decimais = $params[0];
			if (isset($params[1]))
				$sep = $params[1];
			if (isset($params[2]))
				$milhar = $params[2];
			return number_format($content,$decimais,$sep,$milhar);
  		case "each": // maps on each hit (should be a number): {#|each|2=even,10=ten|others}
  			if (!isset($params[0])) return $content;
  			$rawmap = explode(",",$params[0]);
  			$map = array();
  			$default = false;
  			foreach ($rawmap as $item) {
  				$item = explode("=",$item);
				$v = array_shift($item);
				if (is_numeric($v))
  					$map[$v] = implode("=",$item); // dare you to optimize it better
				else
					echo "CKTemplate: Invalid EACH value: $v\n<br/>";
  			}
  			if (isset($params[1])) $default = $params[1];
  			if (isset($map[$content]))
  			if (!is_numeric($content)) return $default==false?"":$default;
  			foreach ($map as $value => $result) {
  				if ($content%$value==0)
  					return $result;
  			}
  			if ($default !== false)
  				return $default;
  			else
  				return "";
  		case "truncate": // truncate: (truncate[:pos[:…[:striptags[:preserveEOL]]]])
  			$default = 50;
  			$final = "…";
  			if (isset($params[0]))
  				$default = (int)$params[0];
			if (isset($params[1]))
				$final = $params[1];
			return truncate($content,$default,$final,isset($params[2]),isset($params[3]));
		case "nl2br":
			return nl2br($content);
		case "onnull":
		case "on_null":
			if ($content == "") return $params[0];
			else return $content;
		case "toplain":
			return str_replace("<","&lt;",str_replace(">","&gt;",$content));
		case "html": // create amps, remove ", and if param is set, remove '
			return str_replace("\"","",isset($params[0])?str_replace("'","",htmlspecialchars($content,ENT_NOQUOTES)):htmlspecialchars($content,ENT_NOQUOTES));
		case "htmlentities":
  			return htmlentities_ex($content);
		case "url":
			if ($content == "")
				return "#";
			else if ($content != "" && $content[0] != '/' && strpos(strtolower(trim($content)),"http://") === false && strpos(strtolower(trim($content)),"https://") === false) {
				if (preg_match("@^([^\.]*\/)*([^\.]+)\.([^\.]+)$@",$content,$regs)==1 && ($regs[3] == 'html' || $regs[3] == 'htm' || $regs[3] == 'php')) // folder/folder/file.html
					return "/".$content;
				else
					return "http://".$content; // url...
			} else
				return $content;
  		case "deutf":
  			return utf8_decode($content);
  		case "uc":
  		case "ucwords":
  			return ucwords(strtolower(strtr($content, "ÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇÑÝ", "áàãâäéèêëíìîïóòõôöúùûüçñý")));
  		case "up":
  			return strtoupper(strtr($content, "áàãâäéèêëíìîïóòõôöúùûüçñý","ÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÔÖÚÙÛÜÇÑÝ"));
  		case "noenvelope": # removes trims and enveloping tag
			$temp = trim(str_replace("\n"," ",str_replace("\r","",$content)));
			if ($temp[0] == "<" && $temp[strlen($temp)-1] == ">") {
				$initTagL = strpos($temp,">")+1;
				$initTag = substr($temp,0,$initTagL);
				if (strpos($initTag," ")!==false) {
					$initTag = explode(" ",$initTag);
					$initTag = $initTag[0]; // removes all parameters, remaining is <tag
					return substr($temp,$initTagL,strlen($temp)-strlen($initTag)-2-$initTagL);
				} else
					return substr($temp,$initTagL,strlen($temp)-strlen($initTag)-1-$initTagL);
			}
			return $content;
  		case "nohtml": # removes HTML, if parameter is set, also remove quotes
			$temp = trim($content);
			$temp = stripHTML($temp);
			if (isset($params[0])) $temp = str_replace("\"","",$temp);
			return $temp;
  		case "map": // maps output (map,map,map|default)
  			if (!isset($params[0])) return $content;
  			$rawmap = explode(",",$params[0]);
  			$map = array();
  			$default = false;
  			foreach ($rawmap as $item) {
  				$item = explode("=",$item);
				$v = array_shift($item);
				$map[$v] = implode("=",$item); // dare you to optimize it better
 			}
 			if (isset($params[1])) $default = $params[1];
 			if (isset($map[$content]))
 				return $map[$content]=="%%1"?$content:$map[$content];
 			else if ($default !== false)
 				return $default=="%%1"?$content:$default;
 			else
 				return "";
  		case "select": // outputs SELECT if the field is defined
  		case "selected":
  			if (isset($params[0])) {
  				return ($params[0] == $content)?"selected='selected'":"";
  			}
  			else
  				return ($content)?"selected='selected'":"";
  		case "check": // outputs CHECK if the field is defined
  		case "checked":
  			if (isset($params[0])) {
  				return ($params[0] == $content)?"checked='checked'":"";
  			} else
  				return ($content)?"checked='checked'":"";
  		case "date": // date format
			if (isset($params[0]) && $params[0] != "") {
				return fd($content,$params[0]);
			} else
				return fd($content,$this->std_date);
  		case "month": // gets a date or datetime, extracts the month and show it in TEXT using $str_monthlabels and i18n
  			$m = 0;
  			if (strlen($content)>7) $m = (int)substr($content,5,2);
  			else return "";

  			if ($m>=1 && $m<=12) return $this->str_monthlabels[$m-1];
  			else return "";
		case "day": // same as above, with day
			$d = date('w',$content);
			if ($d !== false) return $this->$str_daylabels[$d];
  			else return "";
		case "past": // how long has passed, in i18n. Send "true" as a parameter to short the past time in one leter
			$sdif = time_diff(date("Y-m-d H:i:s"),$content);
			$short = isset($params[0]) && $params[0] != "";
			if ($sdif < 60) return $sdif.($short?strtolower($this->str_intervals[0][0]):' '.$this->str_intervals[0]); // seconds
			else if ($sdif < 3600) return floor($sdif/60).($short?strtolower($this->str_intervals[1][0]):' '.$this->str_intervals[1]); // minutes
			else if ($sdif < 86400) return floor($sdif/3600).($short?strtolower($this->str_intervals[2][0]):' '.$this->str_intervals[2]); // hours
			else return floor($sdif/86400).($short?strtolower($this->str_intervals[3][0]):' '.$this->str_intervals[3]); // days
  		case "datetime": // datetime format|remove 00:00:00 (true/false)
  			if (isset($params[0])) {
  				if ($params[0] != "")
					$date = fd($content,$params[0]);
				else
					$date = fd($content,$this->std_datetime);
				if (isset($params[1]))
					$date = str_replace("00:00:00 ","",$date);
				return $date;
  			} else
				return fd($content,$this->std_datetime);
		case "math": // simple math
			if (isset($params[0]) && strlen($params[0])>1) { // first char is the operator, rest is the number
				$op = $params[0][0];
				$value = substr($params[0],1);
				switch ($op) {
					case "+":
						return $content+$value;
					case "-":
						return $content-$value;
					case "*":
						return $content*$value;
					case "/":
						return $content/$value;
					case "%":
						return $content%$value;
					default:
						return $content;
				}
			} else
				return $content;
		default:
			if ($this->externalClasses !== false)
				return $this->externalClasses->runclass($function, $params, $content,$arrayin);
			else
				return $content;
  	}
  }

  public function fullpage($tag,&$dbo,$dbr,$n,$params=array(),$callback = array(),$overflowprotect=0) {
	/* Parameters:
		p_init:	starting item (for pagination) - ITEM not page
		p_size: page size in items
		excludes: list of content tags to be removed from the list (can be changed at the callback)
		no_paging: ignore paging requests, will list all regardless of $_REQUEST or $params
		overflowprotect: if 0, disabled. If any other number, is the maximum number of iteractions allowed, will abort and issue a warning
		grouping: [field in dataset:tag in template] to show said tag each time the field in dataset changes
	*/

		$this->lastReturnedSet = false;
		$this->firstReturnedSet = false;
		$hasGrouping = isset($params['grouping']);
		if ($hasGrouping) {
			$params['grouping'] = explode(":",$params['grouping']);
			$groupTemplate = $this->get($params['grouping'][1]);
			if ($groupTemplate == false) {
				echo "CKTemplate: grouping not possible, tag ".$params['grouping'][1]." not found<br/>";
				$hasGrouping =false;
			}
		}
		if (isset($params['no_paging'])) {
  			$params['p_init'] = 0;
  			$params['p_size'] = $n;
	  	} else { // this is redundant in Prescia since module.php does this, but this will allow the fullpage to be used manually/outside Prescia
		  	if (!isset($params['p_init']) || !is_numeric($params['p_init']) || $params['p_init']<0) {
				if (isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init']) && $_REQUEST['p_init']>=0)
					$params['p_init'] = $_REQUEST['p_init'];
	  	  		else
	  	  			$params['p_init'] = 0;
	  	  	}
	  	  	 if (!isset($params['p_size']) || !is_numeric($params['p_size']) || $params['p_size']<0) { # if no paging, this will still be used to limit how many to show
				if (isset($_REQUEST['p_size']) && is_numeric($_REQUEST['p_size']) && $_REQUEST['p_size']>=0)
					$params['p_size'] = $_REQUEST['p_size'];
		  	  	else
		  	  		$params['p_size'] = defined(CONS_DEFAULT_PAGESIZE)?CONS_DEFAULT_PAGESIZE:$n;
			}
	  	}

		## ----------------
		$tagn = $tag!=""?substr($tag,1):"";
		$itens = "";
		$this->flushcache();
		if ($params['p_init'] + $params['p_size'] > $n) $params['p_size'] = $n - $params['p_init']; // prevents reading more than it should
		$parte = false;
		if ($tag != "")
			$parte = $this->get($tag,true); // get as template
		$isarray = is_array($dbr);
		if ($parte !== false || $tag == "") {
			if ($isarray) {
		  		$init_array = $params['p_init'];
		  		$params['p_size'] += $params['p_init'];
			} else {
		  		for ($cont=0; $cont<$params['p_init']; $cont++) {
					$thisitem = $dbo->fetch_row($dbr); // jumps page_start, so we don't process items that won't be shown due to paging
		  		}
		  		$init_array = 0;
			}

			## --------------
			$position = $params['p_init'];
			$originalExcludes = isset($params['excludes'])?$params['excludes']:array();
			$lastGroup = "";
			if ($overflowprotect==0) $overflowprotect = $params['p_size'];

			for ($cont=$init_array; $cont<$params['p_size']; $cont++) { // loop start <<-----------------
				$thisitem = $this->constants;
				$thisitem = $isarray?$dbr[$cont]:$dbo->fetch_assoc($dbr);
				$position++;
				$thisitem['#'] = $position-$params['p_init']; // so it always starts at 0
				$thisitem['islast'] = ($cont<$params['p_size']-1)?0:1;
				$thisitem['isfirst'] = ($cont==$init_array)?1:0;
				$params['excludes'] = $originalExcludes;
				foreach ($callback as $cbf) {
			  		$thisitem = $cbf($this,$params,$thisitem);
			  		if ($thisitem === false) break; # from this foreach, to be continued from the main for
			  	}
				if ($thisitem === false) continue; # skip this item
			  	foreach ($thisitem as $itemkey => $itemcontent)
			  		if (is_null($itemcontent)) $thisitem[$itemkey] = ""; // else won't fill
			  	//$thisitem = array_merge($thisitem,$this->constants);
			  	if ($tag == "") {
					$this->fill($thisitem);
					foreach ( $params['excludes'] as $tremove) {
			  			$this->assign($tremove);
			  		}
			  		$this->lastReturnedSet = $thisitem;
			  		$this->firstReturnedSet = $thisitem;
			  		break;
			  	} else {
			  		if ($this->firstReturnedSet===false) $this->firstReturnedSet=$thisitem;
			  		if ($hasGrouping && $thisitem[$params['grouping'][0]] != $lastGroup) {
			  			$itens .= $groupTemplate->techo($thisitem,$params['excludes']);
			  			$lastGroup = $thisitem[$params['grouping'][0]];
			  		}
			  		if (isset($params['reverse']))
			  			$itens = $parte->techo($thisitem, $params['excludes']).$itens;
			  		else
			  			$itens .= $parte->techo($thisitem, $params['excludes']);
			  	}

			  	$this->lastReturnedSet = $thisitem;
			  	if ($position > $overflowprotect) {
			  		echo "CKTemplate.fullpage: Overflow protect cropped the result to $overflowprotect <br/>";
			  		break;
			  	}
			} // loop end
			if ($hasGrouping) $this->assign($params['grouping'][1]); // removes grouping template
			$this->assign($tag,$itens);
			unset($itens);
		} else echo "CKTemplate.fullpage: TAG not found: ".$tag. "<br/>";
  }

  public function tbreak($entrada) {
	$p = 0; // pointer for where the file was read so far
	$inipos = strpos($entrada,"{"); // pointer for the next tag
	while ($inipos !== false) {
	  if (($inipos-$p)>0) { // auto-tag
	  	$nextContent = substr($entrada,$p,($inipos-$p));
		$possibleBody = strpos($nextContent,"</body>");
		if ($possibleBody !==false) { // adds {endbody} auto-tag
			$temp = substr($nextContent,0,$possibleBody);
			$this->addcontent("","",$temp);
			$this->addContent("endbody","","");
			$nextContent = substr($nextContent,$possibleBody);
		}
		$this->addcontent("","",$nextContent); // pega o que tem entre o lido e o tag
	  }
	  $p = $inipos+1;
	  $fimpos = strpos($entrada,"}",$p);
	  if ($fimpos !== false) { // end of }
		$key = substr($entrada,$p,($fimpos-$inipos-1));
		if (strpos($key,"\n")===false && strpos($key,"\r")===false && strpos($key,"{")===false) { // valid key (\n, \r and another { not allowed)
		  if ($key[0]=="_") { // conditional key, searches for /$key
			$inipos = $fimpos+1;
			$p = $fimpos;
			$fimpos = strpos($entrada,"{/".substr($key,1)."}",$inipos);
			if ($fimpos !== false) { // found {/
			  $content = substr($entrada,$inipos,($fimpos-$inipos));
			  if ($key != "_") {
				$this->addcontent($key,"",$content);
			  }
			  $p = $fimpos + strlen($key)+2;
			  $inipos = strpos($entrada,"{",$fimpos+2);
			} else { // no end? error
			  echo "CKTemplate.tbreak: ERROR, TAG with no end: ".$key. "<br/>";
			  $this->errorsDetected = true;
			  return false;
			}
		  } else { // key
			if (strpos($key,CLASSSEP_TAG)>0) {
			  $rkey = substr($key,0,strpos($key,CLASSSEP_TAG));
			  $tipo = substr($key,strlen($rkey)+1);
			  $this->addcontent($rkey,$tipo,"");
			} else if ($key == START_REPLACE) {
			  if (count($this->contents)-1>=0)
				$this->contents[count($this->contents)-1][2] .= "{";
			  else
				$this->addcontent("","","{");
			} else
			  $this->addcontent($key,"","");
			$inipos = $fimpos+1;
			$p = $fimpos+1;
			$inipos = strpos($entrada,"{",$inipos);
		  }
		} else { // there is a \n in the tag, ignores it
		  $inipos = strpos($entrada,"{",$inipos+1);
		  $p--;
		}
	  } else { // no end to the tag, and no future end tags, so read it all
		$this->addcontent("","",substr($entrada,$inipos,(strlen($entrada)-$inipos)));
		$p = strlen($entrada);
		$inipos = false;
	  }
	}
	// some more to read
	if ($p < strlen($entrada)) {
	  	$nextContent = substr($entrada,$p,(strlen($entrada)-$p));
		$possibleBody = strpos($nextContent,"</body>");
		if ($possibleBody !==false) { // adds {endbody} auto-tag
			$temp = substr($nextContent,0,$possibleBody);
			$this->addcontent("","",$temp);
			$this->addContent("endbody","","");
			$nextContent = substr($nextContent,$possibleBody);
		}
		$this->addcontent("","",$nextContent); // pega o que tem entre o lido e o tag
	}
	return true;
  }

  public function assign($mkey, $valor="") {
  	if ($mkey == '') return;
	if (is_object($valor)) {
	  $x = new CKTemplate($valor,$valor->path, $valor->debugmode);
	  $x->copyfrom($valor);
	  $this->internal_cache[$mkey] = $x; 
	} else
	  $this->internal_cache[$mkey] = $valor; 
	if ($mkey[0] = "_") $this->flushcache(); // all sorts of wrong if we don't apply content-tags immediatly
  }


  protected function flushcache() {
	if (count($this->internal_cache)>0) $this->fill(array());
	$this->cache = false;
  }

  public function techo($arrayin = false,$emptyme = array(),$recursive=false) {
	$saida = "";
	$this->removeLanguageTags();
	$this->fill($this->constants);
	$this->flushcache();
	if ($arrayin !== false && !$recursive) $this->fill($arrayin);
	if (!$recursive && CKAUTORESET) $this->reset();
	$this->runclasses($arrayin,true);
	foreach ($this->contents as $key => $content) {
	  if ( $content[0] == "" || !in_array( $content[0],$emptyme)) {
		if (is_object($content[2])) {
			$retorno = $content[2]->techo($arrayin,$emptyme,true); // we must send the arrayin so it can be read on classes, but we don't need to replace, thus recursive=true
			if ($content[0] == "_t") {
				if (isset($this->lang_replacer[$retorno]))
					$retorno = $this->lang_replacer[$retorno];
				else if (strlen($retorno)>0 && strpos("!?.:",$retorno[strlen($retorno)-1])!==false){
					$trailing = $retorno[strlen($retorno)-1];
					$retorno = substr($retorno,0,strlen($retorno)-1);
					if (isset($this->lang_replacer[$retorno]))
						$retorno = $this->lang_replacer[$retorno].$trailing;
					else
						$retorno .= $trailing;
				}
			} else if ($content[0] == "_FLASHME") {
  				# uses the flash templating {_FLASHME}file|width|height{/FLASHME}
  				$data = explode("|",$retorno);
  				if (count($data)>2) {
  					$retorno = str_replace("{FILE}",$data[0],
					   		   str_replace("{W}",$data[1],
					   		   str_replace("{H}",$data[2],SWF_OBJECT)));
  				}
  			}
			$saida .= $retorno;
		} else { // content
			if ($content[0] == "_t" && isset($this->lang_replacer[$content[2]]))
				$content[2] = $this->lang_replacer[$content[2]];
			else if ($content[0] == "_FLASHME") {
  				//	uses the flash templating {_FLASHME}file|width|height{/FLASHME}
  				$data = explode("|",$content[2]);
  				if (count($data)>2)
  					$content[2] = str_replace("{FILE}",$data[0],
					   		   str_replace("{W}",$data[1],
					   		   str_replace("{H}",$data[2],SWF_OBJECT)));
  			}
			$saida .= $content[2];
		}
	  }
	}
	return $saida;
  }

  public function fill($arrayin, $emptyme = array()) {
  	// same as assign but receives an array. This function also flushes the internal cache
  	// emptyme is an array of content tags to ignore and delete
  	if (!is_array($arrayin)) $arrayin = array();
	if (count($this->internal_cache)>0) { // merges internal cache with arrayin
	  $arrayin = array_merge($arrayin,$this->internal_cache);
	  $this->internal_cache = array();
	}
	$this->cache = null;
	foreach ($this->contents as $key => $content) { // browses this template tags
	  if ( $content[0] != "" && !in_array( $content[0],$emptyme) ) { // not a content tag
		if (isset($arrayin[$content[0]])) { // we have this information on the arrayin, replace
		  if (!is_object($arrayin[$content[0]])) {
			$this->contents[$key][2] = $arrayin[$content[0]];
			$this->contents[$key][3] = $arrayin[$content[0]];
		  } else {
		  	$this->contents[$key][2] = new CKTemplate($this);
			$this->contents[$key][2]->copyfrom($arrayin[$content[0]]);
			$this->contents[$key][2]->fill($arrayin,$emptyme);
			$this->contents[$key][3] = null;
		  }
		} else if (is_object($content[2])) { // recurse on content tags
		  $this->contents[$key][2]->fill($arrayin,$emptyme);
		}
	  }
	}
  }

  private function havetag($conteudo){
	if (strpos($conteudo,"{")!== false)
	  return preg_match(EREG_TAG,$conteudo);
	else
	  return false;
  }

  //
  public function getTreeTemplate($dt,$sdt,&$tree,$startingId = 0) {
  	if ($this->iec>10) die("<br/><br/>Seems you have a serious loop here, 10 levels?. <br/><br/>dt was: $dt<br/>sdt was: $sdt<br/>Tree was:".print_r($tree,true));
  	if (!is_object($tree)) {
  		$this->iec++;
  		echo "CKTemplate:getTreeTemplate tree is not a CTree<br/>";
  		return;
  	}
  	$dto = $this->get($dt);
  	if (!$dto) {
  		echo "CKTemplate:getTreeTemplate dt tag not found<br/>";
  		$this->iec++;
  		return;
  	}
  	$sdto = $this->get($sdt);
  	if (!$sdto) {
  		echo "CKTemplate:getTreeTemplate sdt tag not found<br/>";
  		$this->iec++;
  		return;
  	}
  	if ($startingId != 0) {
  		$branch = $tree->getbranchById($startingId);
  		$this->assign($dt,$this->getTreeTemplate_ex($dto,$sdto,$branch));
  	} else
  		$this->assign($dt,$this->getTreeTemplate_ex($dto,$sdto,$tree));
  	$this->assign($sdt);
  }

  # dt receives the template for the structure, sdt for a sub-dir. See CKTemplate documentation
  private function getTreeTemplate_ex(&$dt,&$sdt,&$tree,$level = 0,$parent=0,$fulldir="/") {
  	if (!is_object($tree)) return "";
  	$tparent = new CKTemplate($this);
  	$n = $tree->total();
  	$tempmaster = "";
  	for($c=0;$c<$n;$c++) {
		$branch = &$tree->getbranch($c);
		$tparent->clear();
		if ($level==0)
	  		$tparent->copyfrom($dt);
		else
	  		$tparent->copyfrom($sdt);
		$tparent->fill($branch->data);
		$tparent->assign("level",$level);
		$tparent->assign("id_parent",$parent == 0 ? $branch->data['id'] : $parent);
		$tparent->assign("id",$branch->data['id']);
		$tparent->assign("fulldir",$fulldir.$branch->data['id']);
		$tparent->assign("children",$branch->total());
		$subs = $this->getTreeTemplate_ex($dt,$sdt,$branch,$level+1,$branch->data['id'],$fulldir.$branch->data['id']."/");
		if ($subs != "") {
	  		$tparent->assign("subdirs",$subs);
		} else {
	  		$tparent->assign("_insubdirs","");
		}
   		$tparent->assign("branchs",$branch->total());

	   $tempmaster .= $tparent->techo();
  	}
	return $tempmaster;
  }

  public function getPagingLinks($total_itens,$p_init,$p_size) { # Paging link helper
  	if (!is_numeric($total_itens) || !is_numeric($p_init) || !is_numeric($p_size)) echo "CKTemplate:getPagingLinks received non-numeric values";
	if ($p_init<0) $p_init = 0;
   	if ($p_size == 0) $p_size = 1; // no division by zero
   	$pgtot = floor(($total_itens-1)/$p_size)+1;
   	$pgat = floor($p_init/$p_size)+1;
   	$lastpagestart = (($pgtot-1)*$p_size);
   	$page_previous = $pgat>1?($pgat-2)*$p_size:0;
   	$page_next = $pgat<$pgtot?($pgat)*$p_size:$lastpagestart;
   	return array($pgtot,$lastpagestart,$pgat,$page_previous,$page_next);
  }

  public function createPaging($tag, $total_itens,$p_init=0,$p_size=30, $numberOfPagesToShow=7) { # Use getPagingLinks and create paging using template standards as specified bellow
  	# _has_page_previous is erased if there is no previous page (currently at first page)
  	# _has_page_next is erased if there is no next page (currently at last page)
  	# _pages is used to loop to create the $numberOfPagesToShow, centered at the current page (if possible)
  	#	p_number is set to the page number, starting at 1
  	#	current_page is set to 1 if the page being looped thru is the current page, 0 if not
  	#	p_init is set to the p_init of the page being displayed
  	# page_previous receives the p_init for the previews page (=0 if already at first)
  	# page_next receives the p_init for the next page (=total-p_size if already at last)
  	# p_total receives the total number of pages
  	if ($total_itens === false) $total_itens = 0;
  	$pL = $this->getPagingLinks($total_itens,$p_init,$p_size);
  	$contentTAG = $this->get($tag);
  	if ($contentTAG === false) return; // tag not found, gracefull exit
  	if ($pL[2]<=1) $contentTAG->assign("_has_page_previous");
  	if ($pL[2]>=$pL[0]) $contentTAG->assign("_has_page_next");
  	$contentTAG->assign("page_previous",$pL[3]);
  	$contentTAG->assign("page_next",$pL[4]);
  	$contentTAG->assign("p_total",$pL[0]);
  	$contentTAG->assign("last_page",$pL[1]);
	$loopTAG = $contentTAG->get("_pages");
	if ($loopTAG === false) return; // loop tag not found, gracefull exit
	$pgCenter = floor($numberOfPagesToShow/2); // on 7, 3 to each side
	$pgStart = $pL[2]-$pgCenter;
	if ($pgStart < 1) $pgStart = 1;
	$pgEnd = $pgStart + ($numberOfPagesToShow-1);
	if ($pgEnd > $pL[0]) $pgEnd = $pL[0];
	if ($pgEnd-$pgStart<$numberOfPagesToShow && $pL[0]>=$numberOfPagesToShow) {
		$pgStart = $pgEnd - ($numberOfPagesToShow-1);
	}

	$qs = "&amp;".arrayToString(false,array("p_init","CKFinder_Path","HTTPDSESSID","akr_returning","session_visited"));
	$contentTAG->assign("qs",$qs);
	$temp = "";
	$temp .= $loopTAG->techo(array("qs" => $qs, "p_number" => $pgStart, "current_page" => $pgStart == $pL[2] ? "1": "0", "p_init" => ($pgStart-1) * ($p_size)));
	$pgStart ++;
	while ($pgStart <= $pgEnd) {
		$temp .= $loopTAG->techo(array("qs" => $qs, "p_number" => $pgStart, "current_page" => $pgStart == $pL[2] ? "1": "0", "p_init" => ($pgStart-1) * ($p_size)));
		$pgStart++;
	}
  	$contentTAG->assign("_pages",$temp);
  }

}
