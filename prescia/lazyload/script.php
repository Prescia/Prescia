<?/* -------------------------------- Prescia - Scripts
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
  | Automate the inclusion of multiple scripts here
  | Supported:
  |   Bootstrap
  |   Shadowbox
  |	  jquery
  |	  prettify
-*/

	// function addScript($scriptname,$parameters) {
	switch (strtolower($scriptname)) {
		case "bootstrap": // parameter: setViewport (number or device-width)

			if (strpos($this->template->constants['METATAGS'],"bootstrap/js/bootstrap.min.js")!== false) return; # no double ads (if added manually)
			if (isset($this->storage['_scripts_bootstrap_added'])) return; # added here

			if (!isset($parameters['setViewport'])) {
				$parameters['setViewport'] = 'device-width';
			}

			$this->addMeta("\t<meta name=\"viewport\" content=\"width=".$parameters['setViewport'].",height=device-height, initial-scale=1, target-densityDpi=device-dpi; \" />");

			$this->addLink("bootstrap/css/bootstrap.min.css");
			if (CONS_BROWSER == "IE" && CONS_BROWSER_VERSION < 9) { // DO NOT WORK ON COMPATIBILITY MODE, IE ALWAYS REPORTS ITSELF AS THE LATEST VERSION ON IE 11
				$this->addLink("html5shiv.min.js");
				$this->addLink("respond.min.js");
			}
			if (!isset($this->storage['_scripts_jquery_added'])) $this->addLink("jquery-1.11.1.min.js"); // bootstrap flavours j-query 1.x
			$this->addLink("bootstrap/js/bootstrap.min.js");

			$this->storage['_scripts_bootstrap_added'] = true;
			$this->storage['_scripts_jquery_added'] = true;

		break;
		case "shadowbox":

			if (strpos($this->template->constants['HEADUSERTAGS'],"<script type=\"text/javascript\"><!--\nShadowbox.init(")!== false) return; # no double ads
			if (isset($this->storage['_scripts_shadowbox_added'])) return; # added here

			$this->addLink("shadowbox/shadowbox.css");
			$this->addLink("shadowbox/shadowbox.js");
			$params = array();
			foreach ($parameters as $parkey => $paritem) {
				# shadowbox
				$params[] = $parkey.":".$paritem;
			}
			$params = implode(",\n",$params)."\n";
			$this->template->constants['HEADUSERTAGS'] .= "\n<script type=\"text/javascript\"><!--\nShadowbox.init({\n".$params."});\n//--></script>";
			
			$this->storage['_scripts_shadowbox_added'] = true;

		break;
		case "jquery":
			if (strpos($this->template->constants['METATAGS'],"jquery")!== false) return; # no double ads (if added manually)
			if (isset($this->storage['_scripts_jquery_added'])) return; # added here
			
			$this->addLink('jquery.js'); // latest version
			
			$this->storage['_scripts_jquery_added'] = true;
		break;
		case "prettify":
			# Will add CSS, JS and call to Google Prettify
			# USAGE: <PRETTIFY>true</PRETTIFY>
			if (strpos($this->template->constants['METATAGS'],"prettify")!== false) return; # no double ads (if added manually)
			if (isset($this->storage['_scripts_prettify_added'])) return; # added here
			
			$this->addLink("prettify/prettify.js");
			$this->addLink("prettify/prettify.css");
			$this->template->constants['HEADUSERTAGS'] .= "\n<script type=\"text/javascript\">\naddEventListener('load', function (event) { prettyPrint() }, false);\n</script>";
			
			$this->storage['_scripts_prettify_added'] = true;
		break;
	}

