<?/*--------------------------------\
  | arrayToString : Converts an array to query-like string. If no array specified, will get from GET and POST.
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses:
-*/

	# items on excludethese will NOT be outputed.
	# if noArray is specified, no arrays will be outputed.
	function arrayToString( $array = false, $excludethese = array(), $noArrays=false ) {
	  $p_qs = "";
	  if (!$array) {
	  	$array = array_merge($_GET, $_POST);
	  }
	  foreach ($array as $name => $conteudo) {
	    if ( !in_array($name,$excludethese)) {
	      if (!is_array($conteudo)) {
	        if ($conteudo != "" && strlen($conteudo)<250 && strpos($conteudo,"\"")===false) $p_qs .= $name."=".$conteudo."&amp;";
	      } else if (!$noArrays){
	        foreach($conteudo as $anome => $aconteudo) {
	          if ($aconteudo != "" && strlen($aconteudo)<250 && strpos($aconteudo,"\"")===false) $p_qs .= $name."[]=".$aconteudo."&amp;";
	        }
	      }
	    }
	  }
	  return $p_qs;
  }


