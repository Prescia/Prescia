<?/*--------------------------------\
  | recursive_del Recursivelly (as per parameter) deletes all files and folders at path
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | maxDel is the maximum deletions on this recurse, carryDel is used internally to carry this on recursive call (do not use)
-*/

	function recursive_del($dir,$recursive = true,$extLimit="",$maxDel=0,$carryDel=0) { // USE AT YOUR OWN RISK
		while($dir != '' && $dir[0] == "/") $dir = substr($dir,1); // remove initial "/"
		if ($dir != '' && $dir[strlen($dir)-1] != "/") $dir .= "/";
		if ($dir == "/" || $dir == "") { // SOME degree of safety
			return false;
		}
		$pattern = $dir . "*".($extLimit != ''?'.'.$extLimit:'');
		$count = $carryDel;
		if ($recursive) { // we divide in two for performance reasons (less testing)	
			foreach(glob($pattern) as $file) {
				if ($maxDel>0) {
					$count++;
					if ($count>$maxDel) return false;
				}
				if(is_dir($file))
					@recursive_del($file,true,$extLimit,$maxDel,$count);
				else
					@unlink($file);
				@rmdir($dir);
			}
		} else {
			foreach(glob($pattern) as $file) {
				if ($maxDel>0) {
					$count++;
					if ($count>$maxDel) return false;
				}
				if(is_file($file))
					@unlink($file);
				@rmdir($dir);
			}
		}
		return true;
  	}
