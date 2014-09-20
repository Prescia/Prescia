<?/*--------------------------------\
  | Basic Bundle: Functions that often (if not always) are used
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto
  | Free to use, change and redistribute, but please keep the above disclamer.
-*/

	function scriptTime() { return getmicrotime() - CONS_STARTTIME; }
	function isMail($mail, $Allowextended = false) {
		if (!$Allowextended)
			return preg_match("/^[A-Za-z0-9]+(([_\.\-]?[a-zA-Z0-9]+)*)@([A-Za-z0-9]+)(([\.\-]?[a-zA-Z0-9]+)*)\.([A-Za-z]{2,})$/",$mail);
		else // extended: name <mail>
			return preg_match("/^([^<,;>]*<)?[A-Za-z0-9]+(([_\.\-]?[a-zA-Z0-9]+)*)@([A-Za-z0-9]+)(([\.\-]?[a-zA-Z0-9]+)*)\.([A-Za-z]{2,})(>)?$/",$mail);
	}
	function cReadFile($ofile,$removeBOM=false) {
	  if (is_file($ofile)) {
	    $fd = fopen ($ofile, "rb");
	    $size = filesize($ofile);
	    if ($size>0) $output = fread($fd,$size);
	    else $output = "";
	    fclose($fd);
	    return $removeBOM?str_replace("\xef\xbb\xbf", '', $output):$output; # Remove possible BOM HEADER
	  }
	}
	function cWriteFile($ofile,$conteudo,$append=false,$binary=false) {
	  $fd =@fopen ($ofile, ($append?"a":"w").($binary?"b":""));
	  if ($fd) {
	    fwrite($fd,$conteudo);
	    fclose($fd);
	    if (!$append) @chmod($ofile,0777);
	    return is_file($ofile);
	  }
	  return false;
	}
	function removeBOM(&$data) {
		$data = str_replace("\xef\xbb\xbf", '', $data);
	}
	function fv($valor) { # prepare a value to SQL format no matter what l10n it comes
	  $valor = str_replace(",",".",$valor);
	  if (strpos($valor,".")>0) {
	    $valor = explode(".",$valor);
	    $last = array_pop($valor);
	    $valor = implode("",$valor).".".$last;
	  }
	  return $valor;
	}
	function vardump($content) { // better output then var_dump (does not work with objects tough)
		if (!is_array($content)) return $content;
		else {
			$out = "(";
			foreach ($content as $c) {
				$out .= vardump($c).", ";
			}
			$out = substr($out,0,strlen($out)-2).")"; // removes last ', '
			return $out;
		}
	}
	function extractUri($install_root="",$uri="") { // returns an array with: array of folders from URI, filename (no extension), actual filename with extension, extension
		// removes install_root to make some changes to how it is handled (basically, ignores it)
		# context
		if ($uri == "") $uri = isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] != "" ? $_SERVER['REQUEST_URI'] : "";
		if ($uri != "") {
			# removes query from request
			if ($uri[0] != "/") $uri = "/".$uri; # ALWAYS START WITH /
			$uri = explode("?",$uri);
			$uri = str_replace("..",".",array_shift($uri)); # little exploit remover
			if ($install_root != "/" && preg_match("@^(".$install_root.")(.*)\$@",$uri,$regs)) {
				$uri = "/".$regs[2];
			}
			/*
			if ($install_root != "/" && substr($uri,1,strlen($install_root)) == $install_root)
				$uri = substr($uri,strlen($install_root));
			*/
			$uri = explode("/",str_replace("//","/",$uri));
		} else
			$uri = array("/");


		$context = array();
		foreach ($uri as $part)
			array_push($context,preg_replace("/(\.){2,}/","\.",$part)); # prevents ..

		$action = array_pop($context);
		$original_action = $action;
		$ext = "";
		if ($action == "") $$action = 'index';
		else if (strpos($action,".")!==false) {
			$action = explode(".",$action);
			$ext = array_pop($action);
			$action = implode(".",$action);
			$action = removeSimbols($action,true,false); # note extension was removed
		}
		return array($context,$action,$original_action,$ext);
	}