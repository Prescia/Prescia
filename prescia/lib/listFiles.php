<?/*--------------------------------\
  | listFiles : Returns an array with all the files on the specified path given a PREG match. Remaining fields are self-explanatory
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses:
-*/

	function listFiles($path,$eregfilter='',$orddata = false, $ordname = false, $recurse = false) {
	  if ($path == '') $path = './';
	  $array = array();
	  $cont = 0;
	  if (!is_dir($path))
	    return $array;
	  if ($handle = opendir($path)) {
		while (false !== ($file = readdir($handle)))
			if ($file != "." && $file != ".." && ($eregfilter == "" || preg_match($eregfilter,$file)==1)) {
				$array[$cont]= $file;
				$cont++;
			}
		closedir($handle);
	  }
	  if ($path == './') $path = '';
	  // Order ----------
	  $total = count($array);
	  $temp = "";
	  if ($path != '' && (substr($path,strlen($path)-1,1)) != "/" ) $path .= "/";
	  if ($orddata) { // by date
	    for ($cont=0;$cont<$total;$cont++) {
	      for ($cont2=0;$cont2<($total-1);$cont2++) {
	        if (filemtime($path.$array[$cont2]) > filemtime($path.$array[$cont2+1])) { // filemtime is cached, thus fast
	          $temp = $array[$cont2+1];
	          $array[$cont2+1] = $array[$cont2];
	          $array[$cont2] = $temp;
	        }
	      }
	    }
	  }
	  if ($ordname) { // by name, preserves by date if the same
	    for ($cont=0;$cont<$total;$cont++) {
	      for ($cont2=0;$cont2<($total-1);$cont2++) {
	        if (strcasecmp($array[$cont2],$array[$cont2+1]) > 0) {
	          $temp =$array[$cont2+1];
	          $array[$cont2+1] = $array[$cont2];
	          $array[$cont2] = $temp;
	        }
	      }
	    }
	  }
	  if ($recurse) {
	    $dirs = listFiles($path,'/^([^.]*)$/');
	    foreach ($dirs as $x => $dir) {
	      $narray = listFiles($path.$dir,$eregfilter,$orddata,$ordname,true);
	      foreach ($narray as $y => $arquivo)
	        array_push($array,$dir."/".$arquivo);
	    }
	  }
	  return $array;
	}
