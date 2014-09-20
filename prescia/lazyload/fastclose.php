<? // ------------------------ Prescia Fast Close

	# fastClose($action,$context = "")
	$abrupt = $this->ignore404; // prevent loop
	$this->ignore404 = true;

	$msg = '404';
	if ($action == '404' || $action == '403' || $action == '500' || $action == '503') {
		$msg = $action;
	}

	$this->action = $msg;
	$this->context_str = "/";
	$this->context = array();
	$this->nextContainer = "";
	$this->firstContainer = "";

	if (!$abrupt && is_object($this->template)) {

		$this->template->clear();
		$this->template->constants['ACTION'] = $msg;
		$this->template->constants['CONTEXT'] = '/';
		$this->template->constants['HEADUSERTAGS'] = "";
		$this->template->constants['HEADJSTAGS'] = "";
		$this->template->constants['HEADCSSTAGS'] = "";

		$this->renderPage(2);

		$errors = ob_get_contents();
		ob_end_clean();

		$this->headerControl->addHeader(CONS_HC_HEADER,$msg);
		$this->headerControl->showHeaders();

		echo $this->template->techo();
	} else {
		$errors = ob_get_contents();
		ob_end_clean();

		$this->headerControl->addHeader(CONS_HC_HEADER,$msg);
		$this->headerControl->showHeaders();

		echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\"><html><head><title>$msg</title></head><body><h1>$msg</h1><p>".$this->headerControl->baseTranslation($msg)."</p><br/><br/>Prescia</body></html>";
	}
	if (!CONS_ONSERVER) $this->errorControl->raise(166,$this->context_str.$this->action,$this->original_action);
	if ($errors != "") $this->errorControl->dumpUnexpectedOutput($errors);
	$this->close(true);