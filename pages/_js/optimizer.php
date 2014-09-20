<? # will gzip files, if possible. Note this will also auto-detect the latest jquery min version if you call jquery.js

	$gzip = isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strstr( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip');

	function cReadFile($ofile) {
	  if (is_file($ofile)) {
	    $fd = fopen ($ofile, "rb");
	    $size = filesize($ofile);
	    if ($size>0) $saida = fread($fd,$size);
	    else $saida = "";
	    fclose($fd);
	    return $saida;
	  }
	}

	function fastDir($pattern) {
		$files = array();
		$patternlen = strlen($pattern);
		if ($handle = opendir("./")) {
			while (false !== ($file = readdir($handle)))
				if ($file != "." && $file != ".." && strlen($file)>= $patternlen && substr($file,0,$patternlen) == $pattern)
					$files[]= $file;
			closedir($handle);
		}
		return $files;
	}

	$file = "";
	if (isset($_REQUEST['js'])) {
		$file = $_REQUEST['js'];
		if ($file == "jquery.js" && !is_file($file)) {
			// we will serve the latest jquery min!
			$possibilities = fastDir("jquery");
			$maxF = "";
			$maxP = 0;
			foreach ($possibilities as $possible) {
				$p = (float)substr($possible,7,3);
				if ($p>$maxP) {
					$maxP = $p;
					$maxF = $possible;
				}
			}
			$file = $maxF;
		}
	} else if (isset($_REQUEST['css'])) {
		$file = $_REQUEST['css'];
	}

	$file = str_replace("..","",$file); // very basic anti-injection

	if (is_file($file)) {
		if ($gzip) {
			header("Content-Encoding: gzip");
			header("Content-Type: text/".(isset($_REQUEST['js'])?"javascript":"plain"));
    		echo gzencode(cReadFile($file));
		} else {
			header("Content-Type: text/".(isset($_REQUEST['js'])?"javascript":"plain"));
			readfile($file);
		}
	} else {
		header("HTTP/1.0 404 Not Found");
  		die("<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\"><HTML><HEAD><TITLE>404 Not Found</TITLE></HEAD><BODY><H1>Not Found</H1>The requested URL was not found on this server.<P><P>Additionally, a 404 Not Found error was encountered while trying to use an ErrorDocument to handle the request.<br/><br/>Aff optimizer, $file</BODY></HTML>");
	}

?>