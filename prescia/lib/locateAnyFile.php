<?/*--------------------------------\
  | locateAnyFile : Finds a file given ONLY the filename (w/o the extension)
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses: locateFile
-*/

	# Try not to use this! very cpu intensive function. Use locateFile with pre-defined extensions instead (also suported here)
	function locateAnyFile(&$arquivo,&$ext,$extensionsToSearch="") {
		# locate ANY file with the mentioned name. Slow and resource intence.
	    if ($extensionsToSearch != "") {
	    	$v = locateFile($arquivo,$ext,$extensionsToSearch); # fallback to locateFile
	    	return $v;
	    }
	    $dir = explode("/",$arquivo);
	    $filename = array_pop($dir); // tira arquivo
	    $dir = implode("/",$dir);
	    $filename = str_replace("-","\-",$filename);
	    $arquivos = listFiles($dir,'@^'.$filename.'(\.)(.+)$@i', false, true);
	    if (count($arquivos)>0) {
	      $ext = explode(".",$arquivos[0]);
	      $ext = array_pop($ext);
	      $arquivo .= ".".$ext;
	      return true;
	    }
	    return false;
	}

