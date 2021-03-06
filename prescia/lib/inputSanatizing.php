<?/*--------------------------------\
  | Input Bundle: Implements functions for cleaning or preparing input/text fields
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses:
-*/

	# Clears a string for SQL input (prevents injection), removes total or partially HTML codes, and such
	# Returns the treated string
	function cleanString($data,$ishtml = false, $allowadv = false, $dbo = false) {
	    if (!$ishtml) $data = str_replace("<","&lt;",str_replace(">","&gt;",$data));
	    else $data = cleanHTML($data,$allowadv);
	    $data = addslashes_EX($data,$ishtml,$dbo);
	    return $data;
  	}

	# Removes HTML that should not exist in a text content (body, html) and optionally advanced (potentially harmfull) tags
	function cleanHTML($htmlinput,$allowadv=false) {
	    $output = preg_replace("@<\/?body([^>]*)>@i","",$htmlinput);
	    $output = preg_replace("@<\/?html([^>]*)>@i","",$output);
	    $output = preg_replace("@<\/?head([^>]*)>@i","",$output);
	    $output = preg_replace("@<\/?meta([^>]*)>@i","",$output);
		$output = preg_replace("@<\/?title([^>]*)>@i","",$output);
		$output = preg_replace("@<\/?!doctype([^>]*)>@i","",$output);
		if (!$allowadv) {
			$output = preg_replace("@(<\/?script)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
			$output = preg_replace("@(<\/?form)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
			$output = preg_replace("@(<\/?input)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
			$output = preg_replace("@(<\/?button)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
			$output = preg_replace("@(<\/?object)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
			$output = preg_replace("@(<\/?embed)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
			$output = preg_replace("@(<\/?textarea)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
			$output = preg_replace("@(<\/?select)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
			$output = preg_replace("@(<\/?option)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
			$output = preg_replace("@(<\/?applet)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
			$output = preg_replace("@(<\/?canvas)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
			$output = preg_replace("@(<\/?style)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);					
			$output = preg_replace("@(<\/?layer)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
			$output = preg_replace("@(<\/?iframe)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
			$output = preg_replace("@(<\/?link)(( |\t|\n|\n\r)+(([a-z]+)=(('([^']*)')|(\"([^\"]*)\")|([^> ]+)))?)*(\/?>)@i","",$output);
		}
		return $output;
	}

	# Extension of addslashes, adds more slashes than addslashes, also conditionals. Send the database object to use database-especific escape
		function addslashes_EX($entrada, $ishtml = true, $dbo = false) {
  		if ($ishtml) {
  			if ($dbo !== false)
				return $dbo->escape($entrada);
			else
				return addslashes($entrada); // escapes ', " and \
  		} else {
			$entrada = str_replace("'","&#39;",$entrada); # normal escape
			$entrada = str_replace("\"","&quot;",$entrada); # normal escape
  			if ($dbo !== false)
				return $dbo->escape($entrada); // escapes other characters
			else
				return addslashes($entrada); // escapes other characters
  		}
	}

  	# Removes HTML - note this will also remove script and style. For a better HTML stripping that keeps basic tags, check the parseHTML function on xmlHandler
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
						if (substr($theTag,0,2) == "br" ||
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
		return $output;
  	}

