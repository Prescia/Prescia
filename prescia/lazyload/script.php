<?/* -------------------------------- Prescia - Scripts
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
  | Automate the inclusion of multiple scripts here
  | Supported:
  |   Bootstrap
  |   Shadowbox
-*/

	// function addScript($scriptname,$parameters) {
	switch (strtolower($scriptname)) {
		case "bootstrap":
			if (isset($parameters['setViewport'])) $this->addMeta("\t<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />");
			$this->addLink("bootstrap/css/bootstrap.min.css");
			if (CONS_BROWSER == "IE" && CONS_BROWSER_VERSION < 9) {
				$this->addMeta("\t<script src=\"https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js\"></script>");
				$this->addMeta("\t<script src=\"https://oss.maxcdn.com/respond/1.4.2/respond.min.js\"></script>");
			}
			$this->addLink("jquery-2.1.1.min.js");
			$this->addLink("bootstrap/js/bootstrap.min.js");
		break;
		case "shadowbox":
			$this->addLink("shadowbox/shadowbox.css");
			$this->addLink("shadowbox/shadowbox.js");
			$params = array();
			foreach ($parameters as $parkey => $paritem) {
				# shadowbox
				$params[] = $parkey.":".$paritem;
			}
			$params = implode(",\n",$params)."\n";
			$this->template->constants['HEADUSERTAGS'] .= "\n<script type=\"text/javascript\"><!--\nShadowbox.init({\n".$params."});\n//--></script>";
		break;
	}

