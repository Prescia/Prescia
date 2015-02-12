<?/*--------------------------------\
  | loadURL : Loads a remote URL using fsockopen, returns an array with header and content
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses: safe_mkdir
-*/

	# This is a ubber-simple URL catcher that can be faster (easier to use or an alternate if you don't have) than CURL for simple URL's
	# Supports basc authentication on the URL since it uses parse_url (check PHP's documentation on that)
	# if openssl is installed, also supports https 
	# $response[0] is the header data (array)
	# $response[1] is the actual content
	function loadURL($url,$agent="PHP",$method = "get") {
		ini_set("allow_url_fopen", 1);
	    $url_parts = parse_url($url);
	    $response = '';
	    if(isset($url_parts['query'])) {
	        if($method != 'get')
	            $page = $url_parts['path'];
	        else
	            $page = $url_parts['path'] . '?' . $url_parts['query'];
	    } elseif (isset($url_parts['path'])) {
	        $page = $url_parts['path'];
	    } else
	    	$page = "/";

	    if(!isset($url_parts['port'])) $url_parts['port'] = $url_parts['scheme'] == 'https'?443:80;

	    $fp = fsockopen(($url_parts['scheme'] == 'https'?'ssl://':'').$url_parts['host'], $url_parts['port'], $errno, $errstr, 10);
	    if ($fp) {
	        $out = '';
	        if($method == 'post' && isset($url_parts['query'])) {
	            $out .= "POST $page HTTP/1.1\r\n";
	        } else {
	            $out .= "GET $page HTTP/1.0\r\n"; //HTTP/1.0 is much easier to handle than HTTP/1.1
	        }
	        $out .= "Host: $url_parts[host]\r\n";
	        $out .= "Accept: text/*\r\n";
	        $out .= "User-Agent: $agent\r\n";
	        $out .= "Connection: Close\r\n";

	        //HTTP Basic Authorization support
	        if(isset($url_parts['user']) && isset($url_parts['pass'])) {
	            $out .= "Authorization: Basic ".base64_encode($url_parts['user'].':'.$url_parts['pass']) . "\r\n";
	        }

	        //If the request is post ...
	        if($method == 'post' && $url_parts['query']) {
	            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
	            $out .= 'Content-Length: ' . strlen($url_parts['query']) . "\r\n";
	            $out .= "\r\n" . $url_parts['query'];
	        }
	        $out .= "\r\n";

	        fwrite($fp, $out);
	        while (!feof($fp)) {
	            $response .= fgets($fp, 128);
	        }
	        fclose($fp);
	        $response = explode("\r\n\r\n",$response);
	        $response = array(array_shift($response),implode("\r\n\r\n",$response));
	        $response[0] = explode("\r\n",$response[0]);
	        return $response;
	    } else {
	        return false;
	    }
	}

	# Simple FTP Get with multiple tries. Return the file content or FALSE on fail
	# If it fails to download, make sure the folder have 777 chmod
	function fget($url,$login,$pass,$file,$tries=1,$tmpfile="",$mode=FTP_ASCII) {

		if ($tmpfile == "") $tmpfile = "tmpdlw.tmp";
		if (is_file($tmpfile)) @unlink($tmpfile);

		while ($tries>0) {
			$fp = ftp_connect($url);
			if ($fp) {
				$login = ftp_login($fp, $login, $pass);
				if ($login) {
					ftp_pasv($fp,true);
					$handle = fopen($tmpfile, 'w');
					$sucess = ftp_fget($fp,$handle,$file,$mode);
					if ($sucess) {
						ftp_close($fp);
						fclose($handle);
						return cReadFile($tmpfile);
					} else {
						fclose($handle);
						if (is_file($tmpfile)) @unlink($tmpfile); // incomplete?
					}
				}
				ftp_close($fp);
				unset($fp);
				$tries--;
				if ($tries>0) sleep(1);
			}
		}
		return false;

	}

	# Get number of views on a YOUTUBE video!
	# Send video code, not url
	function getYoutubeViews($code) {
		# 2015.1.28: <div class="watch-view-count">16,520,660</div>
		#			 <strong class="watch-time-text">Published on Nov 30, 2011
		#			 <button class="yt-uix-button yt-uix-button-size-default yt-uix-button-opacity yt-uix-button-has-icon no-icon-markup yt-uix-button-toggled yt-uix-tooltip" type="button" onclick=";return false;" title="Unlike" id="watch-like" aria-label="like this video along with 238,062 other people" data-like-tooltip="I like this" data-orientation="vertical" data-unlike-tooltip="Unlike" data-position="bottomright" data-force-position="true" data-button-toggle="true"><span class="yt-uix-button-content">238,062 </span></button></span><span ><button class="yt-uix-button yt-uix-button-size-default yt-uix-button-opacity yt-uix-button-has-icon no-icon-markup  yt-uix-tooltip" type="button" onclick=";return false;" title="I dislike this" id="watch-dislike" aria-label="dislike this video along with 11,897 other people" data-orientation="vertical" data-position="bottomright" data-force-position="true" data-button-toggle="true"><span class="yt-uix-button-content">11,897 
		$html = loadURL('https://www.youtube.com/watch?v='.$code);
		if ($html !== false) {
			$html = $html[1];
			$haswvc = strpos($html,'watch-view-count',5000);
			if ($haswvc > 0) {
				$initpos = strpos($html,">",$haswvc);
				$endpos = strpos($html,"<",$initpos);
				return str_replace(".","",str_replace(",","",substr($html,$initpos+1,$endpos-$initpos-1)));
			} else
				return "getYoutubeviews:counter not found";
		} else
			return "getYoutubeviews:loadURL fail";
	}