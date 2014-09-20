<?/*--------------------------------\
  | htmlentities_ex : basic htmlentities wont convert latin (or others) chars ...
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses: Turn non ASCII accents to amp codes since htmlentities does not do it
  | Still in use?
-*/

	function htmlentities_ex($str) {
	    $str = htmlentities($str);
	    # Add any other specials as you see fit. Keep in mind that the larger this list, more overhead this function will have, so it would be best to keep only your locale options
	    # Others you can add here: http://tlt.its.psu.edu/suggestions/international/web/codehtml.html
	    $specials = array("á" => "&aacute;", "é" => "&eacute;", "í" => "&iacute;", "ó" => "&oacute;", "ú"=>"&uacute;",
	    				  "Á" => "&Aacute;", "É" => "&Eacute;", "Í" => "&Iacute;", "Ó" => "&Oacute;", "Ú"=>"&Uacute;",
	    				  "ã" => "&atilde;", "Ã" => "&Atilde;", "õ" => "&otilde;", "Õ" => "&Otilde;", "ñ"=>"&ntilde;", "Ñ"=>"&Ntilde;",
	    				  "â" => "&acirc;", "ê" => "&ecirc;", "î" => "&icirc;", "ô" => "&ocirc;", "û"=>"&ucirc;",
	    				  "Â" => "&Acirc;", "Ê" => "&Ecirc;", "Î" => "&Icirc;", "Ô" => "&Ocirc;", "Û"=>"&Ucirc;",
	    				  "ç" => "&ccedil;", "Ç" => "&Ccedil;", "¿" => "&iquest;"
	    				 ); # Portuguese/spanish specials
		if (count($specials)>strlen($str)) {
			$total = strlen($str);
			$out = "";
			for($c=0;$c<$total;$c++) {
				if (isset($specials[$str[$c]]))
					$out .= $specials[$str[$c]];
				else
					$out .= $str[$c];
			}
			return $out;
		} else {
			foreach ($specials as $from => $to) {
				$str = str_replace($from,$to,$str);
			}
			return $str;
		}
	}

