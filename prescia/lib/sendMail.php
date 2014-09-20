<?/*--------------------------------\
  | sendMail : Improved version of PHP's mail function
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
  | -r parameter for postfix
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses: tc, cleanHTML, getMime
  | DOES NOT VALIDADES DATA, ONLY ORGANIZE WHAT IS AVAILABLE AND CAN BE USED
  |
  | IMPORTANT: if the SOURCE (from) mail have an invalid domain, some servers might NOT send the mail, but return TRUE on the submission!
-*/

	function sendMail($mailto,$subject,&$mail,$mailfrom = "",$header = "",$isHTML=true, $attach = "") {
	  # mailto = destination mail, accepts extended version (name <mail>) and comma delimited list
	  # subject = subject line
	  # mail = template with the fill mail >>>OBJECT<<<
	  # mailfrom = "from" mail
	  # header (optional) = headers, you might or might not fill a Content-Type
	  # isHTML = if true, adds proper Content-Type
	  # attach = filename for attachment

	  $subject = str_replace("\n","",$subject); // bye exploit
	  $subject = str_replace("\r","",$subject); // bye exploit
	  if (preg_match('!\S!u', $subject)!==0)
	  	$subject = '=?UTF-8?B?'.base64_encode($subject).'?=';
	  if ($mailfrom == "" && strpos($mailto,",")===false) $mailfrom = $mailto; // no mailfrom, use mailti
	  if ($header != "" && $header[strlen($header)-1] != "\n") $header .= "\n"; // add \n at the end of the last line of pre-defined header
	  $mailfrom = str_replace("\n","",$mailfrom); // bye exploit
	  if (strpos(strtoupper($header),"RETURN-PATH:") === false && isMail($mailfrom,true)) // no R-P, add if possible
	  	$header .= "Return-path: $mailfrom\n";
	  if (strpos(strtoupper($header),"REPLY-TO:") === false && isMail($mailfrom,true)) // no R-T, add if possible
	  	$header .= "Reply-To: $mailfrom\n";
	  if (strpos(strtoupper($header),"FROM:") === false && isMail($mailfrom,true)) // no FROM, add if possible
	  	$header .= "From: $mailfrom\n";
	  if ($isHTML || $attach != "") { // HTML mode with attachment
	  		$isHTML = true;
	  		$bound = "--=XYZ_" . md5(date("dmYis")) . "_ZYX";
	  		$bnext = "--=NextPart_XYZ_".md5(date("dm")).".E0_PART";
	  		$header .= "Content-Type:multipart/".($attach != "" ? "mixed" : "alternative")."; boundary=\"$bound\"\n";
	  } else { // not HTML nor with attachment
	  		$header .= "Content-Type:text/plain; charset=utf-8\n";
	  }
	  $header .= "MIME-Version: 1.0\n";
	  $header .= "x-mailer: NekoiMailer\n";
	  $mail->assign("IP",CONS_IP);
	  $mail->assign("HOUR",date("H:i"));
	  $mail->assign("DATA",date("d/m/Y"));
	  $mail->assign("DATE",date("m/d/Y"));
	  $corpo = $mail->techo();
	  if ($attach != "" && is_file($attach)) { // deal with attachment
		  //Open file and convert to base64
		  $fOpen = fopen ($attach, "rb");
		  $fAtach = fread ($fOpen,filesize($attach));
		  $ext = explode(".",$attach);
		  $ext = array_pop($ext);
		  $fAtach = base64_encode ($fAtach);
		  fclose ($fOpen);
		  $fAtach = chunk_split($fAtach);

		  $corpoplain = preg_replace("/( ){2,}/"," ",cleanHTML($corpo));

		  // Add multipart message
		  $sBody = "This is a multipart MIME message.\n\n";
		  $sBody .= "--$bound\n";
		  $sBody .= "Content-Type: multipart/alternative; boundary=\"$bnext\"\n\n\n";
		  $sBody .= "--$bnext\n".
		  		   "Content-Type: text/plain; charset=utf-8\n\n".
		  		   $corpoplain."\n\n".
		  		   "--$bnext\n";
		  $sBody .= "Content-Type:text/html; charset=utf-8\n\n";
		  $sBody .= "$corpo \n\n";
		  $sBody .= "--$bnext--\n\n";
		  $sBody .= "--$bound\n";
		  $fname = explode("/",str_replace("\\","/",$attach));
		  $sBody .= "Content-Disposition: attachment; filename=".array_pop($fname)."\n";

		  if (!function_exists("getMime")) include_once CONS_PATH_INCLUDE."getMime.php";

		  $sBody .= "Content-Type: ".getMime($ext)."\n";
		  $sBody .= "Content-Transfer-Encoding: base64\n\n$fAtach\n";
		  $sBody .= "--$bound--\n\n";
		} else {
		  if ($isHTML) {
		  	$corpoplain = preg_replace("/( ){2,}/"," ",stripHTML($corpo));
		  	$sBody = "This is a multipart MIME message.\n\n";
		  	$sBody .= "--$bound\n".
		  			 "Content-Type: text/plain; charset=utf-8\n\n".
		  			 $corpoplain."\n\n".
		  			 "--$bound\n".
		  			 "Content-Type: text/html; charset=utf-8\n\n".
		  			 $corpo."\n\n".
		  			 "--$bound--\n";
		  } else {
		  	$sBody = $corpo;
		  }
	  }

	if (substr($subject,0,3) == "NS:") $sBody .= chr(0); // Newsletter character flag
	if (preg_match('@^([^<]*)<([^>]*)>(.?)$@i',$mailfrom,$matches)==1) $mailfrom = $matches[2]; // removes expanded mail mode
	$ok = false; // will return false ONLY if ALL submissions fail
	$mailto = explode(",",$mailto);
	foreach ($mailto as $mt) {
		$mt = trim($mt);
		// Subject: =?UTF-8?B?".base64_encode($subject)."?=
		if (!@mail($mt, $subject, $sBody, $header,'-f'.$mailfrom))
			$ok = @mail($mt, $subject, $sBody, $header,'-r'.$mailfrom) || $ok; // postfix
		else
			$ok = true;
	}
	return $ok;
	}

