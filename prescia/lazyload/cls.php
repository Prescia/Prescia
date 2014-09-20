<?/* -------------------------------- Prescia CLS - Change Local site
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
-*/

	$error = ob_get_contents();
	ob_end_clean();

	echo "<html><body>Choose site:<br/><br/><select onchange=\"document.location='/index.php?changelocalsite='+this.value+'&amp;nosession=true&amp;nocache=true&amp;debugmode=true';\"><option></option>";

	if (!is_file(CONS_PATH_CACHE."domains.dat")) $domains = $this->builddomains();
	else $domains = unserialize(cReadFile(CONS_PATH_CACHE."domains.dat"));
	if ($domains == false || count($domains) == 0) $domains = $this->builddomains();

	$codes = array();
	foreach ($domains as $url => $code) {
		if (!isset($codes[$code])) {
			$codes[$code] = array($url);
		} else
			$codes[$code][] = $url;
	}

	foreach ($codes as $code => $urls) {
		echo "<option value=\"$code\">$code</option>";
	}

	echo "</select></body></html>";


	$this->close(true);