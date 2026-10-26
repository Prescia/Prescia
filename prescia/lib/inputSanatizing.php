<?php
/*--------------------------------\
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
			// $allowadv is retained for API compatibility; the central allowlist
			// deliberately never permits executable or embedded content.
			return \Prescia\Services\Sanitizer::sanitizeHtml((string)$htmlinput);
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
			return \Prescia\Services\Sanitizer::stripTags((string)$str,$preserveEndOfLine);
  	}
