<?  # -------------------------------- Prescia Simple Header Control

define("CONS_HC_HEADER",0);
define("CONS_HC_PRAGMA",1);
define("CONS_HC_CACHE",2);
define("CONS_HC_CONTENTTYPE",3);

class CHeaderControl {

	var $parent = null;
	var $headers = array();
	var $baseHeader = 200;

	function __construct(&$parent) {
		$this->parent = &$parent;
	}

	function getHeader($code, $pop = true) {
		# Searches a header with given code (from defines CONS_HC...) and returns it
		# pop true will REMOVE it from stack (usefull for replacing the header)
		if (!$pop) {
			foreach ($this->headers as $header)
				if ($header[0] == $code)
					return $header[1];
		} else {
			$nh = array();
			$ret = "";
			foreach ($this->headers as $header) {
				if ($header[0] == $code)
					$ret = $header[1];
				else
					array_push($nh,$header);
			}
			if ($ret != "") {
				$this->headers = $nh;
				return $ret;
			}
		}
		return false;
	} # getHeader

	function baseTranslation($httpcode) {
		# gets text translation of an error
		switch($httpcode) {
			case "200":	return "ok";
			case "403":	return "forbidden";
			case "404":	return "Not Found";
			case "301": return "Moved Permanently";
			case "302": return "Found"; // temporary redirect
			case "307": return "Temporary redirect";
			case "500":	return "Internal Server Error";
			case "503": return "Service Unavailable or Temporarily Unavailable";
		}
		return $httpcode;
	} #  baseTranslation

	function addHeader($header, $headerContent) {
		# adds a new header, replacing old one if present
		$this->getHeader($header,true); // removes if present
		if ($header != CONS_HC_HEADER)
			array_push($this->headers,array($header,$headerContent)); // adds
		else
			array_unshift($this->headers,array($header,$headerContent)); // adds first
	} # addHeader

	function showHeaders($limit = false) {
		# shows the header. shows CONS_HC_HEADER first always
		# limit prevents other headers from showing

		$currentBaseHeader = $this->getHeader(CONS_HC_HEADER,true);
		if ($currentBaseHeader !== false)
			header($_SERVER["SERVER_PROTOCOL"]." ".$currentBaseHeader." ".$this->baseTranslation($currentBaseHeader));
		else
			header($_SERVER["SERVER_PROTOCOL"]." ".$this->baseHeader." ".$this->baseTranslation($this->baseHeader));
		if (!$limit)
			foreach ($this->headers as $header)
				header($header[1]);

	} # showHeaders

	function internalFoward($location,$redirmode = "200") {
		if (CONS_FOWARDER) {
			$_SESSION[CONS_SESSION_LOG] = $this->parent->log;
			$this->parent->dbo->close();
			ob_end_clean();
			if ($redirmode != "200")
				header("HTTP/1.1 $redirmode ".$this->baseTranslation($redirmode));
			header("Location: ".$location);
			die("<html><head><title>$redirmode ".$this->baseTranslation($redirmode)."</title></head><body>Content moved to $location ".$this->baseTranslation($redirmode)." - your browser should automatically move for such location, if not, click on the link below<br/><br/><a href=\"$location\">$location</a></body></html>");
		} else
			$this->parent->errorControl->raise(115);
	}

}

