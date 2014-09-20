<?/*--------------------------------\
  | getBrowser : Returns an array
  |		(Browser name,
  |		 true|false for legacy support,
  |		 true|false if it's a mobile/pad,
  |		 Operating System,
  |		 Browser Code)
  |	Will consider this is NOT a bot (if it IS a bot, will most likelly end up being UN)
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses: -
  | Revision: 2014.09.15
  | Latest versions at revision: CH=37, FF=32, SA=7, OP=24, IE=11
-*/

	# List of supported browsers:
	# MSIE 5~		(9.x- legacy)	IE
	# Firefox 1~	(12.x- legacy)	FF
	# Opera 9~		(9.x- legacy)	OP
	# Android 		(all legacy)	AN
	# Chrome 1~		(17.x- legacy)	CH  (most android phones now use this, otherwise AN)
	# Safari 3~		(4.x- legacy)	SA	(note: some android phones use Safari 4)
	# Konqueror		(legacy)		KO
	# BlackBerry	(legacy)		MO	(note: Newer versions of these phones run Android, thus Android browser = safari/chrome)
	# SonyEricsson	(legacy)		MO
	# Other Mobiles	(legacy)		MO
	# others		(legacy)		UN

	# List of supported OS:
	# Windows
	# Android (takes precedence over Linux)
	# Linux
	# Mac OS/iOS

	# Legacy means the browser is know to have issues with css, w3c or web 2.0+ features

	function getBrowser($allowCached=true) {
		# returns array(browser name, isLegacy (boolean), isMobile (boolean), OS, browser code, browser version)
		if ($allowCached && defined(CONS_BROWSER) && defined(CONS_BROWSER_VERSION) && defined(CONS_BROWSER_SO)) {
			$translation = array("OP" => "Opera",
								 "IE" => "Internet Explorer",
								 "FF" => "Firefox",
								 "CH" => "Chrome",
								 "AN" => "Android Browser",
								 "SA" => "Safari",
								 "KO" => "Konqueror",
								 "MO" => "MOBILE/?", // <-- unknown mobile
								 "UN" => "UNKNOWN/?"); // <-- unknown non-mobile

			$browser = $translation[CONS_BROWSER]." ".CONS_BROWSER_VERSION;
			$legacy = (CONS_BROWSER == 'OP' && CONS_BROWSER_VERSION < 9.5) ||	/* OP-VERSION */
					  (CONS_BROWSER == 'IE' && CONS_BROWSER_VERSION < 9) ||		/* IE-VERSION */
					  (CONS_BROWSER == 'FF' && CONS_BROWSER_VERSION < 12) ||	/* FF-VERSION */
					  (CONS_BROWSER == 'CH' && CONS_BROWSER_VERSION < 17) ||	/* CH-VERSION */
					  (CONS_BROWSER == 'SA' && CONS_BROWSER_VERSION < 4) ||		/* SA-VERSION */
					  CONS_BROWSER == 'KO' ||
					  CONS_BROWSER == 'MO' ||
					  CONS_BROWSER == 'AN' ||
					  CONS_BROWSER == 'UN';
			return array($browser,$legacy,defined(CONS_BROWSER_ISMOB)?CONS_BROWSER_ISMOB:CONS_BROWSER=='MO',CONS_BROWSER_SO,CONS_BROWSER,CONS_BROWSER_VERSION);
		}
		$browser = isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"";
		$ismob = preg_match("@mobile|android|iphone|ipad@i",$browser,$regs);
		$so = preg_match("@windows@i",$browser)?"Windows":
						(preg_match("@android@i",$browser)?"Android":
						 (preg_match("@linux@i",$browser)?"Linux":
						  (preg_match("@mac@i",$browser)||preg_match("@ios@i",$browser)?"Mac OS":"??")
						 )
						);
		$legacy = false; // non w3c // ccs3+ sufficiently compliant
		if ($browser == "") return array("UNKNOWN/",true,false,$so,"UN",0);
		if (strpos($browser,"MSIE") !== false || strpos($browser,"Opera")!== false) { # Old OPERAs reported as MSIE
			if (strpos($browser,"Opera")!== false) {
				if (preg_match("@Opera/([0-9\.]*)@",$browser,$regs)==1) {
					$browser = "Opera ".$regs[1];
					$vi = explode(".",$regs[1]);
					$v = $vi[0]; // w/o dot
					if (isset($vi[1])) $v .= ".".$vi[1];
					if ($v < 9.5) $legacy = true; 								/* OP-VERSION */
				} else {
					$browser = "Opera";
					$legacy = true;
					$v = 0;
				}
				return array($browser,$legacy,$ismob,$so,"OP",$v);
			} else {
				if (preg_match("@MSIE ([0-9\.]*)@",$browser,$regs)==1) {
					$browser = "Internet Explorer ".$regs[1];
					$vi = explode(".",$regs[1]);
					$v = $vi[0]; // w/o dot
					if (isset($vi[1])) $v .= ".".$vi[1];
					if ($v < 9) $legacy = true; 								/* IE-VERSION */
				} else {
					$browser = "Internet Explorer";
					$legacy = true;
					$v = 0;
				}
				return array($browser,$legacy,$ismob,$so,"IE",$v);
			}
		} else if (strpos($browser,"Firefox")!== false || strpos($browser,"Gecko/20") !== false) {
			if (preg_match("@Firefox/([0-9\.]*)@",$browser,$regs)==1) {
				$browser = "Firefox ".$regs[1];
				$vi = explode(".",$regs[1]);
				$v = $vi[0]; // w/o dot
				if (isset($vi[1])) $v .= ".".$vi[1];
				if ($v < 12) $legacy = true; 									/* FF-VERSION */
			} else {
				$browser = "Firefox";
				$legacy = true;
				$v = 0;
			}
			return array($browser,$legacy,$ismob,$so,"FF",$v);
		} else if (preg_match("@Chrome/([0-9\.]*)@",$browser,$regs)==1) { // Chrome also reports as Safari, so must test first
			$browser = "Chrome ".$regs[1];
			$vi = explode(".",$regs[1]);
			$v = $vi[0]; // w/o dot
			if (isset($vi[1])) $v .= ".".$vi[1];
			if ($v < 17) $legacy = true; 										/* CH-VERSION */
			return array($browser,$legacy,$ismob,$so,"CH",$v);
		} else if (strpos($browser,"Safari") !== false || (strpos($browser,"AppleWebKit") !== false && strpos($browser,"Android") === false)) {
			if (preg_match("@Version/([0-9\.]*)@",$browser,$regs)==1) {
				$browser = "Safari ".$regs[1];
				$vi = explode(".",$regs[1]);
				$v = $vi[0]; // w/o dot
				if (isset($vi[1])) $v .= ".".$vi[1];
				if ($v < 4) $legacy = true; 									/* SA-VERSION */
			} else {
				$browser = "Safari";
				$legacy = true;
				$v = 0;
			}
			return array($browser,$legacy,$ismob,$so,"SA",$v);
		} else if (strpos($browser,"Android /") !== false) {
			return array("Android Browser",true,$ismob,$so,"AN",0);
		} else if (strpos($browser,"Konqueror/") !== false) {
			return array("Konqueror",true,$ismob,$so,"KO",0);
		} else if ($ismob || strpos($browser,"BlackBerry") !== false || strpos($browser,"SonyEricsson") !== false) {
			return array("MOBILE/".$browser,true,true,$so,"MO",0);
		} else if (strlen($browser)>50) {
			$browser = substr($browser,0,50)."...";
			return array("UNKNOWN/".$browser,true,false,$so,"UN",0);
		}
		return array("UNKNOWN/",true,$ismob,$so,"UN",0);
	}

	function outputBrowserName($browserID) {
  		$translation = array("OP" => "Opera",
							 "IE" => "Internet Explorer",
							 "FF" => "Firefox",
							 "CH" => "Chrome",
							 "AN" => "Android Browser",
							 "SA" => "Safari",
							 "KO" => "Konqueror",
							 "MO" => "MOBILE/?", // <-- unknown mobile
							 "UN" => "UNKNOWN/?"); // <-- unknown non-mobile
	if (isset($translation[$browserID]))
		return $translation[$browserID];
	else
		return "UNKNOWN/?";
	}

	# set browser constants
	if (isset($_SESSION['CONS_BROWSER'])) { // if I have the browser, I have the rests
		define ("CONS_BROWSER", $_SESSION['CONS_BROWSER']);
		define ("CONS_BROWSER_VERSION", $_SESSION['CONS_BROWSER_VERSION']);
		define ("CONS_BROWSER_ISMOB", $_SESSION['CONS_BROWSER_ISMOB']);
		define ("CONS_BROWSER_SO", $_SESSION['CONS_BROWSER_SO']);
	} else {
		$browser = getBrowser(false);
		define ("CONS_BROWSER", $browser[4]);
		define ("CONS_BROWSER_VERSION", $browser[5]);
		define ("CONS_BROWSER_ISMOB", $browser[2]);
		define ("CONS_BROWSER_SO", $browser[3]);
		$_SESSION['CONS_BROWSER'] = CONS_BROWSER;
		$_SESSION['CONS_BROWSER_VERSION'] = CONS_BROWSER_VERSION;
		$_SESSION['CONS_BROWSER_ISMOB'] = CONS_BROWSER_ISMOB;
		$_SESSION['CONS_BROWSER_SO'] = CONS_BROWSER_SO;
	}
