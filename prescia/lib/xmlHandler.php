<?/*--------------------------------\
  | xmlHandler : Implements a XML object
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses: ttree
-*/

define ("C_XHTML_AUTOTAB","DIV,FORM,TABLE,TD,TR,SELECT,BODY,HEAD,OBJECT"); # these tags will cause tabulation (formating)
define ("C_XHTML_AUTOCLOSE","IMG,BR,INPUT,META,LINK,HR,PARAM"); # This tags can be auto-closed (<.../>)
define ("C_XHTML_CODE","SCRIPT,STYLE,PRE"); # These are codes, ignore whatever is inside
# NOTE: will automatically break line (\n) after br/

# These are the indexes for the parsedContent array if fetchData is enabled
define ("C_XHTML_IDS",0); # id=""
define ("C_XHTML_CLASSES",1); # class=""
define ("C_XHTML_DUPLICATES",2); # lists duplicated ids
define ("C_XHTML_LINKS",3); # href=""
define ("C_XHTML_SRCS",4); # src=""
define ("C_XHTML_NAMES",5); # name = "" (NOTE: it will ignore name tags inside PARAMS)

# Parameter constants
define ("C_XML_RAW",0); # set it as raw XML and not XHTML
define ("C_XML_AUTOPARSE",1); # will parse the tags and return an array instead of raw string
define ("C_XML_LAX", 2); # ignore errors
define ("C_XML_REMOVECOMMENTS", 3); // if true, will not store <!-- --> or <?  comments
define ("C_XML_KEEPCASE", 4); // disable auto-uppercase for tags

function xmlParamsParser($data) {
	if (is_array($data)) return $data;
	$data = trim($data);
	$t = strlen($data);
	if ($t == 0) return "";
	$buffer = "";
	$tag = "";
	$closure = "";
	$int = false;
	$saida = array();
	for ($c=0;$c<$t;$c++) {
		$char = $data[$c];
		if (!$int && $char == "=") {
			$int = true;
			$tag = trim($buffer);
			$buffer = "";
		} else if ($int) {
			if ($closure== "" && ($char == "\"" || $char == "'")) {
				// opened quotation
				$closure = $char;
			} else if ($closure != "" && $char == $closure) {
				// closed quotation
				$saida[$tag] = $buffer;
				$buffer = "";
				$closure = "";
				$int = false;
			} else if ($closure == "" && $char == " ") {
				// ended w/o quotation
				$saida[$tag] = $buffer;
				$buffer = "";
				$int = false;
			} else
			$buffer .= $char;
		} else
		$buffer .= $char;
	}
	return $saida;

}

class xmlHandler {

  private $xhtmltag = array(); # processed C_XHTML_AUTOTAB for faster search
  private $xhtmlacl = array(); # processed C_XHTML_AUTOCLOSE for faster search
  private $xhtmlcode = array(); # processed C_XHTML_CODE for faster searcg
  private $parsedContent = array(); # processed data from latest parseXML if fetchData true

  public function __construct() {
  	# pre-process C_XHTML_AUTOTAB for faster parser:
	$xhtmlat = explode(",",C_XHTML_AUTOTAB);
	$this->xhtmltag = array();
	foreach ($xhtmlat as $tab)
		$this->xhtmltag[strtoupper($tab)] = true;
	unset($xhtmlat);
  	# pre-process C_XHTML_AUTOCLOSE
  	$xhtmlac = explode(",",C_XHTML_AUTOCLOSE);
  	$this->xhtmlacl = array();
	foreach ($xhtmlac as $tab)
		$this->xhtmlacl[strtoupper($tab)] = true;
	unset($xhtmlac);
	# pre-process C_XHTML_CODE for faster parser:
	$xhtmlat = explode(",",C_XHTML_CODE);
	$this->xhtmlcode = array();
	foreach ($xhtmlat as $tab)
		$this->xhtmlcode[strtoupper($tab)] = true;
	unset($xhtmlat);
  }

  public function XMLParsedContent() {
  	return $this->parsedContent;
  }

  public function parseXML($data,$params=array(),$fetchData=false) {
  	# this supports <?  > and <! ... > ... tags as auto-closure comment tags

	if (!isset($params[C_XML_RAW]))
  		$params[C_XML_RAW] = false; # Forces XHTML mode if not specified
  	if (!isset($params[C_XML_AUTOPARSE]))
  		$params[C_XML_AUTOPARSE] = $fetchData;
  	else if ($fetchData)
  		$params[C_XML_AUTOPARSE] = true; # we must parse the parameters to get ids, classes and links to the $parsedContent
  	if (!isset($params[C_XML_LAX]))
  		$params[C_XML_LAX] = false; # LAX mode will ignore auto-close tags that are not closed, and will automatically fix incorrectly opened/closed tags
  	if ($params[C_XML_RAW]) {
  		$fetchData = false; # pointless with XML
  	}
  	if ($fetchData) {
  		$this->parsedContent = array(	C_XHTML_IDS => array (),
  									C_XHTML_CLASSES => array (),
  									C_XHTML_DUPLICATES => array (),
  									C_XHTML_LINKS => array (),
  									C_XHTML_SRCS => array (),
  									C_XHTML_NAMES => array ()
  							  		);
  	}
  	$autoparse = $params[C_XML_AUTOPARSE]; # will parse the tag parameters to an array
  	$laxClosure = $params[C_XML_LAX]; # Ignore errors in HTML, such as incorrect open/close tags
  	$keepcase = isset($params[C_XML_KEEPCASE]);
  	$isXHTML = false; # The last tag to be closed had XHTML content (false means only raw text)
  	$emptyParams = $autoparse?array():"";
	$pos = 0; # position in file
	$buffer = ""; # what is being processed
	$data = str_replace("\r","\n",$data); # remove \r, we don't need them (Windows \n\r)
	$data = str_replace("\n\n","\n",$data); # now remove double \n caused by \r removal (or redundant \n anyway)
	$total = strlen($data); # html size
	$intag = false; # we are inside a < >
	$intags = array(); # queue of nested tags
	$incode = false; # we are inside a code (C_XHTML_CODE)
	$saida = new ttree(); # what we will generate as output
	 # nest result in this node (remember the XHTML will be nested on this)
	$saida->addbranch(array(0=>$params[C_XML_RAW]?"xml":"xhtml", # tag name
						  1=>$emptyParams, # tag parameters
						  2=>"") # Tag contents
				   );
	$branch=&$saida->lastsibling();
	$closure = ""; # if inside a literal, which closure was used, " or '
	$line = 1; # current line for debug purposes
	while ($pos<$total) {
	  $car = $data[$pos];
	  if ($car == "\n") $line++;
	  if ($intag) {
	  	if ($car == ">" && $closure == "") {
		  $intag = false;
		  // CLOSURE -----------------------------------------------------------
		  if ($buffer[0] == "/") {
			// closes, checking consistency
			$tagClose = substr($buffer,1);
			$nextClose = count($intags)>0?$intags[count($intags)-1]:'';
			$thisClose = strtoupper($tagClose);
			if ($thisClose == $nextClose) {
			  // fine, back to previous node
			  array_pop($intags);
			  if (!$isXHTML && count($branch->branchs)==1 && $branch->branchs[0]->data[2] != "" && $branch->branchs[0]->data[0] == "") { # we had no XHTML inside this tag, so we can compact it!
			  	$branch->data[2] = $branch->branchs[0]->data[2];
			  	$branch->branchs = array();
			  	$branch = &$branch->parent;
			  } else
			  	$branch = &$branch->parent;
			  $isXHTML = true;
			} else if (!$laxClosure) {
			  // incorrect close tag, and we are running on strict mode
			  echo "XML:Tag mismatch at $buffer expecting $nextClose @ line ".$line." (parent was ".$branch->parent->data[0].")";
			  echo $data;
			  return false;
			} else {
			  // incorrect close tag, but we are allowed to auto-close it. If this closure is an EXTRA closure, this will cause an error
			  // we search if this tag exists to be closed, if so, close all of them, if not, ignore this closure
			  $located = false;
			  for ($tp=count($intags)-2;$tp>-1;$tp--) {
			  	if ($thisClose == $intags[$tp]) {
			  		$located = true;
			  		break;
			  	}
			  }
			  if ($located) {
			  	$isXHTML = true;
			  	$autoClose = count($intags)-$tp; # we will close all this tags
			  	for ($c=0;$c<$autoClose;$c++) {
			  		array_pop($intags);
			  		$branch = &$branch->parent;
			  	}
			  }
			}
			if (isset($this->xhtmlcode[$thisClose])) {
				$incode=false;
			}
		  // Auto-closing tag ----------------------------------------------------------
		  } else if ($buffer[strlen($buffer)-1] == "/" || ($buffer[strlen($buffer)-1] == "?" && $buffer[0] == "?")) {
			$buffer = substr($buffer,0,strlen($buffer)-1); # remove auto close
			$tag = explode(" ",$buffer);
			$tag = $tag[0];
			$utag = strtoupper($tag);
			$buffer = trim(substr($buffer,strlen($tag)+1));
			$data_do_branch = array(0=>$keepcase?$tag:$utag, 1=>$buffer, 2=> '');
			if ($autoparse) {
			  $data_do_branch[1] = $this->parseparams($buffer);
			  if ($fetchData && $utag != "PARAM" && !$incode) $this->refreshData($data_do_branch[1]);
			}
			$branch->addbranch($data_do_branch);
		  // Opening TAG -------------------------------------------------------------
		  } else {
			if ($buffer[0] == "!" || $buffer[0] == "?") { // stand-alone comment tag, consider it auto-close but don't parse
				$tag = explode(" ",$buffer);
				$tag = $tag[0];
				$utag = strtoupper($tag);
				$buffer = substr($buffer,strlen($tag)+1);
				$data_do_branch = array(0=>$tag, 1=>$buffer, 2=> '');
				$branch->addbranch($data_do_branch);
			} else { // separates parameters
				$tag = explode(" ",$buffer);
				$tag = $tag[0];
				$utag = strtoupper($tag);
				if (!$params[C_XML_RAW] && isset($this->xhtmlacl[$utag])) { # this should be a auto-close tag!
					if ($laxClosure) {
						$buffer = trim(substr($buffer,strlen($tag)+1));
						$data_do_branch = array(0=>$keepcase?$tag:$utag, 1=>$buffer, 2=> '');
						if ($autoparse) {
			  				$data_do_branch[1] = $this->parseparams($buffer);
			  				if ($fetchData && $utag != "PARAM" && !$incode) $this->refreshData($data_do_branch[1]);
						} else if ($buffer == "")
							$buffer = $emptyParams;
						$branch->addbranch($data_do_branch);
					} else {
						echo "XML: Auto-close tag not closed at line $line: $tag";
						return false;
					}
				} else {
					$buffer = trim(substr($buffer,strlen($tag)+1));
					array_push($intags,$utag);
					$data_do_branch = array(0=>$keepcase?$tag:$utag, 1=>$buffer, 2=> '');
					if ($autoparse) {
					  $data_do_branch[1] = $this->parseparams($buffer);
					  if ($fetchData && !$incode) $this->refreshData($data_do_branch[1]);
					} else if ($buffer == "")
						$buffer = $emptyParams;
					$branch->addbranch($data_do_branch);
					$branch=&$branch->lastsibling();
				}
			}
			if (isset($this->xhtmlcode[$utag])) {
			   	$incode=true;
			}
			$isXHTML = false;
		  }
		  // ----------------------------------------------------------------------
		  $buffer = "";
		} else if ($car != "\n") {
		  if ($closure == "" && $car == "\t") $car = " "; // converts \t to " "
		  else if ($closure == "" && ($car == "\"" || $car == "'" ))
			$closure = $car;
		  else if ($closure == $car) $closure = "";
		  $buffer .= $car;
		} else {
		  $buffer .= " "; // converts \r or \n to " " (\r was hard-converted to \n before)
		}
	  } else {
		if ($car == "<") {
			if ($pos == $total-1 || $data[$pos+1] == ">" || preg_match("/^([\n\r\t ='\"<]+)\$/",$data[$pos+1])) { // do not count as a tag on these cases
				$buffer .= "<";
			} else {
			  	if ($buffer != "" && !preg_match("/^([\n\r\t ]+)\$/",$buffer)) { # ignore empty strings comprised only or these characters (do not add to xml)
			  		$data_do_branch = array(0=>"", 1=>$emptyParams, 2=> preg_replace("/([\t ])+/"," ",$buffer));
					$branch->addbranch($data_do_branch);
			  	}
			  	$buffer = "";
			  	if (substr($data,$pos,4) == "<!--" || substr($data,$pos,2) == "<?") {
					$end = substr($data,$pos,4) == "<!--" ? strpos($data,"-->",$pos)+3 : strpos($data,"?>",$pos)+2;
					$buffer .= substr($data,$pos,$end-$pos);
	  				$pos = $end-1;
	  				if (isset($data[$pos+1]) && $data[$pos+1] == "\n") {
	  					$buffer .= "\n";
	  					$pos++;
	  				}
	  				if (!isset($params[C_XML_REMOVECOMMENTS]) || $incode) { # only add comments if set NOT to remove or we are in code
	  					$data_do_branch = array(0=>"", 1=>$emptyParams, 2=> $buffer);
						$branch->addbranch($data_do_branch);
	  				}
					$buffer = "";
			  	} else
			  		$intag = true;
			  	$closure = "";
			}
		} else
		  $buffer .= $car;
	  }
	  $pos++;
	}
	if ($buffer != "" && !preg_match("/^([\n\r\t ]+)\$/",$buffer)) { # ignore empty strings comprised only or these characters
  		$data_do_branch = array(0=>"", 1=>$emptyParams, 2=> preg_replace("/([\t ])+/"," ",$buffer));
		$branch->addbranch($data_do_branch);
  	}
	if (!$laxClosure && count($intags)>0) echo "XML: Tag not closed at ".array_pop($intags); # this should never happen on LAX mode
	return $saida;
  }

  public function cReadXML($arquivo, $params=array(),$fetchData=false) {
	if (!is_file($arquivo))
	  return false;
	$data = cReadFile($arquivo);
	return $this->parseXML($data,$params,$fetchData);
  }

  private function refreshData($data) { # updates the parsedContent with the data received (parameters of a tag)
	if (!is_array($data)) return;
  	foreach ($data as $index => $content) {
  		switch(strtolower($index)) {
  			case "id":
  				if (!in_array($content,$this->parsedContent[C_XHTML_IDS])) {
  					$this->parsedContent[C_XHTML_IDS][] = $content;
  				} else if (!in_array($content,$this->parsedContent[C_XHTML_DUPLICATES])) {
  					$this->parsedContent[C_XHTML_DUPLICATES][] = $content;
  				}
  			break;
  			case "name": # param tags SHOULD NOT call this function
  				if (!in_array($content,$this->parsedContent[C_XHTML_NAMES])) {
  					$this->parsedContent[C_XHTML_NAMES][] = $content;
  				}
  			break;
  			case "class":
  				if (!in_array($content,$this->parsedContent[C_XHTML_CLASSES])) {
  					$this->parsedContent[C_XHTML_CLASSES][] = $content;
  				}
  			break;
  			case "href":
  				if (!in_array($content,$this->parsedContent[C_XHTML_LINKS])) {
  					$this->parsedContent[C_XHTML_LINKS][] = $content;
  				}
  			break;
  			case "src":
  				if (!in_array($content,$this->parsedContent[C_XHTML_SRCS])) {
  					$this->parsedContent[C_XHTML_SRCS][] = $content;
  				}
  			break;
  		}
  	}
  }

  private function parseparams($data) { // arrives as xxx="yy yy yy" zzz="11 22 33", leaves as array
  	# Supports W3C (mandatory quotes) or single-worded unquoted parameters
  	return xmlParamsParser($data);
  }

  public function outputHTML($htmltree,$autotab = true, $compact = false) {
  	$output = "";
  	if (count($htmltree->data) ==0) {
  		$branch = $htmltree->branchs[0]; # this should be the 0,0,xml tag generated by the parseXML
  		$branch->data[0] == ""; # removes the xml auto-tag
	} else {
  		$branch = $htmltree;
	}
  	$justcrlf = false;
  	$output = $this->outputHTML_ex($branch,$autotab,$compact,0,$justcrlf);
  	return $output;
  }

  private function outputHTML_ex($branch,$autotab,$compact,$tlevel,&$justcrlf) {
  	$output = "";
  	$prefix = "";
  	$breaker = $compact?"":"\n";
  	if ($autotab && !$compact) {
  		for ($c=0;$c<$tlevel;$c++) $prefix .= "\t";
  	}
  	foreach ($branch->branchs as $childbranch) {
  		if (count($childbranch->branchs) == 0) { # no childs, thus a simple tag
			if ($childbranch->data[2] != "") { # we have raw TEXT to output
				if ($childbranch->data[0] != "") { # such raw TEXT is inside a TAG
					$output .= ($justcrlf?$prefix:"")."<".strtolower($childbranch->data[0]).($childbranch->data[1] != ""?" ".$this->tostr($childbranch->data[1]):"").">";
					$output .= $childbranch->data[2];
					$output .= "</".strtolower($childbranch->data[0]).">";
					$justcrlf = false;
				} else { # this raw TEXT is ... raw
					$output .= ($justcrlf?$prefix:"").$childbranch->data[2];
					$justcrlf = false;
				}
			} else if ($childbranch->data[0] != "") { # No raw tag, but this is a TAG, thus this is a auto-close tag or a open/close tag
				if ($childbranch->data[0][0] == "?" || $childbranch->data[0][0] == "!") {# xml or php
					$output .= "<".strtolower($childbranch->data[0]).($childbranch->data[1] != ""?" ".$this->tostr($childbranch->data[1]):"").($childbranch->data[0][0] == "?"?" ?":"").">$breaker";
					$justcrlf = true;
				} else if (isset($this->xhtmlacl[$childbranch->data[0]])) { # auto-close
					if (strtolower($childbranch->data[0]) == "img") {
						if (is_array($childbranch->data[1]) && !isset($childbranch->data[1]['alt']))
							$childbranch->data[1]['alt'] = '';
						else if (!is_array($childbranch->data[1]) && strpos($childbranch->data[1],'alt=')===false)
							$childbranch->data[1] .= " alt=''";
					}
					$output .= ($justcrlf?$prefix:"")."<".strtolower($childbranch->data[0]).($childbranch->data[1] != ""?" ".$this->tostr($childbranch->data[1]):"")." />"; ## TODO: check alts
					if (strtolower($childbranch->data[0]) == "br") {
						$output .= $breaker;
						$justcrlf = true;
					} else
						$justcrlf = strtolower($childbranch->data[0]) == "img"; // on img, suppose justcrlf because some bugs with space after image
				} else { # non-auto close supported
					$output .= ($justcrlf?$prefix:"")."<".strtolower($childbranch->data[0]).($childbranch->data[1] != ""?" ".$this->tostr($childbranch->data[1]):"").">";
					$output .= $childbranch->data[2];
					$output .= "</".strtolower($childbranch->data[0]).">";
					$justcrlf = false;
				}
			}
  		} else {
  			// this is a container! (TAG)
  			if ($autotab && isset($this->xhtmltag[strtoupper($childbranch->data[0])])) {
  				if (!$justcrlf) $output .= $breaker;
  				$output .= $prefix."<".strtolower($childbranch->data[0]).($childbranch->data[1] != ""?" ".$this->tostr($childbranch->data[1]):"").">".$breaker;
  				$tlevel++;
  				$justcrlf = true;
  				$output .= $this->outputHTML_ex($childbranch,$autotab,$compact,$tlevel,$justcrlf);
  				$tlevel--;
  				if (!$justcrlf) $output .= $breaker;
  				$justcrlf = true;
  			} else {
  				$output .= ($justcrlf?$prefix:"")."<".strtolower($childbranch->data[0]).($childbranch->data[1] != ""?" ".$this->tostr($childbranch->data[1]):"").">";
  				$justcrlf = false;
  				$output .= $this->outputHTML_ex($childbranch,$autotab,$compact,$tlevel,$justcrlf);
  			}
  			$output .= ($justcrlf?$prefix:"")."</".strtolower($childbranch->data[0]).">".$breaker.$childbranch->data[2];
  			$justcrlf = true;
  		}
  	}
  	return $output;
  }

  private function tostr($params) {
  	if (!is_array($params)) return $params;
  	$saida = "";
  	foreach ($params as $name => $content) {
  		if (strpos($content,"\"")!==false)
  			$saida.= $name."='".$content."' ";
  		else
  			$saida.= $name."=\"".$content."\" ";
  	}
  	if ($saida != "") {
  		$saida = substr($saida,0,strlen($saida)-1);
  	}
  	return $saida;
  }

}

