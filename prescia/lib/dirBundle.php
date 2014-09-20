<?/*--------------------------------\
  | Directory bundle
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses:
-*/

	# Creates a folder and sets the proper chmod . Mode must have 4 numbers so it's an octal (ex 0777, 0775)
	function safe_mkdir($path,$mode=0777) {
	  	if ($path != "" && substr($path,strlen($path)-1) == "/") $path = substr($path,0,strlen($path)-1);
	    $old_umask = umask(0);
	    if (!mkdir($path,$mode)) {
	    	umask($old_umask);
	    	return false;
	    } else {
	    	umask($old_umask);
	    	return true;
	    }
	}

	# Sets chmod forcibly, by overriding umask
	function safe_chmod($file, $mode) {
	    $temp = umask(0);
	    chmod($file,intval($mode,8));
	    umask ($temp);
	}

	# Creates a folder tree
	# Returns true/false for sucess
	function makeDirs($path,$base = "") {
	  	if ($base != "" && substr($base,strlen($base)-1) != "/") $base .= '/';
	  	$paths = explode("/",$path);
	  	if ($base != "" && !is_dir($base))
	  		if (!safe_mkdir($base))
	  			return false;
	  	while (count($paths)>0) {
	  		$starter = array_shift($paths);
	  		if ($starter != "") {
	  			$base .= $starter."/";
	  			if (!is_dir($base))
	  				if (!safe_mkdir($base))
	  					return false;
	  		}
	  	}
	  	return true;
	}
	
	function humanSize($size) {
	  	# send in BYTES
	  	$trail = array("B","KB","MB","GB","TB","PB");
	  	$pos = 0;
	  	while ($size > 1024) {
	  		$size /= 1024;
	  		$pos ++;
	  		if ($pos == 5) break;
	  	}
	  	return number_format($size,1).$trail[$pos];
	}
	


