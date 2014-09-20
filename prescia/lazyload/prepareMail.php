<? // ------------------------ Prescia readfile

	# prepareMail($name="",$fillArray=array()) {

	$file = "";
	if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/mail/template.html"))
		$file = CONS_PATH_PAGES.$_SESSION['CODE']."/mail/template.html";
	$mail = new CKTemplate($this->template);
	if ($file != "")
		$mail->fetch($file);
	if ($name != "") {
		$innerFile = CONS_PATH_PAGES.$_SESSION['CODE']."/mail/".$name.".html";
		if (!is_file($innerFile)) {
			$innerFile = CONS_PATH_PAGES.$_SESSION['CODE']."/mail/".$name;
			if (!is_file($innerFile)) {
				$this->errorControl->raise(183,$name);
				$innerFile = "";
			}
		}
		if ($innerFile != "") {
			if ($file != "")
				$mail->assignFile("CONTENT",$innerFile); # we have a template AND a file
			else
				$mail->fetch($innerFile); # no template, but we have a file
		}
	} else if ($file == "") {
		$mail->tbreak("{CONTENT} "); # no template nor file
		if (is_file(CONS_PATH_SETTINGS."defaults/automail.html")) {
			$smh = new CKTemplate($this->template);
			$smh->fetch(CONS_PATH_SETTINGS."defaults/automail.html");
			$objField = $smh->get("_field");
			$temp = "";
			foreach ($_POST as $name => $data) {
				if ($name != "haveinfo") {
					$temp .= $objField->techo(array('name' => $name,'content' => nl2br($data)));
				}
			}
			$smh->assign("_field",$temp);
			$template = $smh->techo();
			unset($smh);
			unset($objField);
		} else {
			foreach ($_POST as $name => $data) {
				if ($name != "haveinfo") {
					if (strpos($data,"\n")!==false)
						$template .= "<strong>$name</strong>: <br/><blockquote>".nl2br($data)."</blockquote>\n";
					else
						$template .= "<strong>$name</strong>: ".$data."<br/>\n";
				}
			}
		}
		$template .= "<br/>\n<br/>".$this->langOut('sendmail_sent_from')." : ".$this->domain.$this->context_str.$this->action."<br/>\n";
		$mail->assign("CONTENT",$template);
		unset($template);
	}
	
	# if we have only the template, the template should have the CONTENT
	$mail->fill($fillArray);
	$mail->constants['ABSOLUTE_URL'] = "http://".$this->domain.CONS_INSTALL_ROOT;
	return $mail;