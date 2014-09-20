<?/*--------------------------------\
  | checkHTML : Performs a simple HTML check for common mistakes and W3C issues
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses: base
-*/

	# Returns a list of issues in an array
	function checkHTML($source) {
  		$mandatoryTagsPresent = array(false,false); // HTML, BODY
  		$EID = array();
  		$l = strlen($source);
    	$buffer = "";
    	$intag = false;
    	$intags = array();
    	$closure = "";
    	$log = array();
 		for ($pos = 0; $pos < $l; $pos++) {
  			$car = $source[$pos];
  			if ($intag) {
  				if ($car == ">" && $closure == "") { # end of a tag
  					$intag =false;
  					$buffer = str_replace("\r","",$buffer);
  					$buffer = str_replace("\n","",$buffer);
  					if ($buffer[0] == "/") { # it's a end tag
  						# check integrity
  						$closureTag = strtolower(substr($buffer,1));
  						$nextClose = array_pop($intags);
  						if ($closureTag != $nextClose) {
  							if ($nextClose == "")
  								$log[]= str_replace("<","&lt;","/$closureTag came when end of file was expected");
  							else
  								$log[]= str_replace("<","&lt;","/$closureTag came when /$nextClose was expected");
  							break; // won't be able to help
  						}
  					} else if ($buffer[strlen($buffer)-1] == "/") { # self-closing tag
  						$buffer = substr($buffer,0,strlen($buffer)-1);
  						$tag = explode(" ",$buffer);
  						$tag = strtolower($tag[0]);
  						if ($tag == "img" && strpos($buffer," alt=") === false) {
							$log[]= "Missing ALT in img tag: ".str_replace("<","&lt;",$buffer);
  						}
  						if ($tag == "input") {
  							if (preg_match("/type=(\"|')([^\"']+)(\"|')/i",$buffer,$regs)) {
								if (!in_array(strtolower($regs[2]),array("password","checkbox","radio","hidden","text","submit","file","button","image"))) {
									$log[]= "Invalid INPUT type: ".$regs[2];
								}
							}
  						}
  						if (preg_match("/id=(\"|')([^\"']+)(\"|')/i",$buffer,$regs)) {
							if (in_array(strtolower($regs[2]),$EID)) {
								$log[]= "Duplicated Element ID: ".$regs[2];
							}
							$EID[] = strtolower($regs[2]);
						}
  						$buffer .= "/";
  					} else { # opening tag
  						$tag = explode(" ",$buffer);
  						$tag = strtolower($tag[0]);
  						if ($tag == "html") $mandatoryTagsPresent[0] = true;
  						if ($tag == "body") $mandatoryTagsPresent[1] = true;
  						if ($tag[0] != "?" && $tag[0] != "!") # ignore xml and doctypes
  							$intags[] = $tag;
  						if ($tag == "script" && strpos($buffer," type=") === false) {
							$log[]= "Missing type in SCRIPT tag";
  						} else if ($tag == "script" && strpos($buffer,"text/javascript") === false) {
  							$log[]= "type in SCRIPT tag should be text/javascript";
  						}
  						if ($tag == "style" && strpos($buffer," type=") === false) {
							$log[]= "Missing type in STYLE tag (hint: styles should be external in .css files, or type text/css)";
  						}
  						if ($tag == "br" || $tag == "input" || $tag == "hr" || $tag == "img" || $tag == "link") {
  							$log[]= "$tag without auto-close: ".str_replace("<","&lt;",$buffer);
  							array_pop($intags);
  						}
  						if ($tag == "form") {
	  						if (preg_match("/(name|id)=(\"|')([^\"']+)(\"|')/i",$buffer,$regs)) {
								if (in_array(strtolower($regs[3]),$EID)) {
									$log[]= "Duplicated Form named: ".$regs[3];
								}
								$EID[] = strtolower($regs[2]);
							}
  						}
  					}
  					$buffer = "";
  				} else {
  					if ($closure == "" && ($car == "\"" || $car == "'"))
  						$closure = $car;
  					else if ($closure == $car)
  						$closure = "";
  					else if ($closure != "" && $car == "<") {
  						$log[]= "Invalid &lt; inside quotation at the tag: ".str_replace("<","&lt;",$buffer);
						break;
  					}
  					$buffer .= $car;
  				}
  			} else {
  				if ($car == "<") {
  					if (substr($source,$pos+1,3) == "!--") {
  						#comment, look ahead (scripts should never have --> as per W3C, so it's safe to look ahead)
  						$end = strpos($source,"-->",$pos);
						if ($end === false) {
							$log[] = "Unclosed comment";
						} else
  							$pos = $end;
  					} else if (strpos("!?%qazwsxedcrfvtgbyhnujmikolp1234567890_/",strtolower($source[$pos+1])) !== false) {
  						$buffer = "";
  						$intag = true;
  						$closure = "";
  					}
  				}
  			}
  		}
  		if (count($intags)>0) {
  			$log[]= "End of file reached while waiting ".count($intags)." end tags:".print_r($intags,true);
  		}
  		if (!$mandatoryTagsPresent[0])
  			$log[] = "Mandatory HTML tag not present";
  		if (!$mandatoryTagsPresent[1])
  			$log[] = "Mandatory BODY tag not present";

  		return $log;

  	}

