<?/*--------------------------------\
  | Input Bundle: Implements functions for cleaning or preparing input/text fields
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses:
-*/

	# Clears a string for SQL input (prevents injection), removes total or partially HTML codes, and such
	# Returns the treated string
	function cleanString($data,$ishtml = false, $allowadv = false) {
	    if (!$ishtml) $data = str_replace("<","&lt;",str_replace(">","&gt;",$data));
	    else $data = cleanHTML($data,$allowadv);
	    $data = addslashes_EX($data,$ishtml);
	    return $data;
  	}

	# Removes HTML that should not exist in a text content (body, html) and optionally advanced (potentially harmfull) tags
	function cleanHTML($htmlinput,$allowadv=false) {
	    $output = preg_replace("@<\/?body([^>]*)>@i","",$htmlinput);
	    $output = preg_replace("@<\/?html([^>]*)>@i","",$output);
	    $output = preg_replace("@<\/?head([^>]*)>@i","",$output);
	    $output = preg_replace("@<\/?meta([^>]*)>@i","",$output);
	    if (!$allowadv) {
	      $output = preg_replace("@(<\/?script)(( |\t|\n|\n\r)+(([a-z]+)=(('([^'>]*)')|(\"([^\">]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
	      $output = preg_replace("@(<\/?layer)(( |\t|\n|\n\r)+(([a-z]+)=(('([^'>]*)')|(\"([^\">]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
	      $output = preg_replace("@(<\/?iframe)(( |\t|\n|\n\r)+(([a-z]+)=(('([^'>]*)')|(\"([^\">]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
	      $output = preg_replace("@(<\/?link)(( |\t|\n|\n\r)+(([a-z]+)=(('([^'>]*)')|(\"([^\">]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
	    }
	    return $output;
	}

	# Extension of addslashes, adds more slashes than addslashes, also conditionals
	function addslashes_EX($entrada, $ishtml = true) {
  		if ($ishtml) {
  			$entrada = addslashes($entrada); // escapes ', " and \
  			return $entrada;
  		} else {
  			$entrada = str_replace("\\\"","&quot;",$entrada); # in case something was escaped
  			return str_replace("\"","&quot;",$entrada); # and now the official
  		}
	}

  	# Removes HTML - note this will also remove script and style.
  	function stripHTML($str,$preserveEndOfLine=false) {
		if (strpos($str,"<")===false) return $str; // no HTML, just pop out
		$l = strlen($str);
		$inTag = false;
		$theTag = "";
		$inQuotes = ""; // actual quotes
		$inScript = false; // in style or script
		$output = "";
		for ($p=0;$p<$l;$p++) {
			if ($inTag) { // we are in a tag, do not output until we get to a non-quoted >
				$theTag .= $str[$p];
				if ($inQuotes == "" && ($str[$p] == "\"" || $str[$p] == "'")) $inQuotes =$str[$p]; // quote started
				else if ($str[$p] == $inQuotes) $inQuotes = ""; // quote ended
				else if ($inQuotes == "" && $str[$p] == ">") {
					$inTag =false; // non-quoted > reached, leave tag
					if (!$inScript && (substr($theTag,0,6) == 'script' || substr($theTag,0,5) == "style")) $inScript = true;
					else if ($inScript && (substr($theTag,0,7) == '/script' || substr($theTag,0,6) == "/style")) $inScript = false;
					if ($preserveEndOfLine) {
						if (substr($theTag,0,3) == "br " ||
							substr($theTag,0,3) == "br>" ||
							substr($theTag,0,3) == "br/" ||
							substr($theTag,0,2) == "/p") {
							$output .= "<br/>";
						}
					}
				}
			} else if ($str[$p] == "<") {
				$inTag = true;
				$theTag = "";
			} else if (!$inScript)
				$output .= $str[$p]; // note this will also remove tags that where not finished (cropped)
		}
		return preg_replace("@(<[^b][^r])@","&lt;",$output);
  	}

