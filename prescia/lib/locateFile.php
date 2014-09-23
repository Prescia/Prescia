<?/*--------------------------------\
  | locateFile : Locates a file given only the filename (w/o extension)
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses:
-*/

	# Will check only the extensions provided for increased performance
	# Use locateAnyFile if you don't know the possible extensions
	function locateFile(&$arquivo,&$ext,$extensionsToSearch="png,jpg,gif,swf,jpeg,ico") {
	    $exts = explode(",",$extensionsToSearch);
	    $total = count($exts);
	    for ($c=0;$c<$total;$c++) {
	      if (is_file($arquivo.".".$exts[$c])) {
	        $arquivo .= ".".$exts[$c];
	        $ext = $exts[$c];
	        return true;
	      }
	    }

	    return false;
	}

