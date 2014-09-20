<?/*--------------------------------\
  | quota: returns space used insite a path (recursive optional) in KB
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses:
  | Optimized to use glob, so it can handle huge folders
  | NOTE: If the space gets too high, there is a change quota will return negative values. It's probably due to numeric constrains
  |		  ALSO note that it calculates the quota in KB using floor, so the result is usually SMALLER than the actual number
-*/
	function quota($path, $recurse = false) {
		if ($path == '' || $path[0]=='/') return 0; // we use glob, which grants access to the system. We should try to avoid that mistake
	    $size = 0;
	    if (!is_dir($path)) {
	      return false;
	    }
	    if ($path[strlen($path)-1] != "/") $path .= "/";
	    if ($recurse) { // we divide in two for performance reasons (less testing)
	    	foreach(glob($path.'*') as $file) {
	    		if(is_dir($file))
	    			$size += quota($file,true);
	    		else
	    			$size += floor(filesize($file) / 1024);
	    	}
	    } else {
		    foreach(glob($path.'*') as $file) {
	    		if(is_file($file)) $size += floor(filesize($file) / 1024);
		    }
	    }
	    return ($size);
	}
