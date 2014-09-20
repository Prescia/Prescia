<?/* -------------------------------- Domain cache
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
  | reads from main domains file, create the cache and in the process fill in my $_SESSION['CODE']
-*/

$domains = cReadFile(CONS_PATH_SETTINGS."domains");
if (!$domains) $this->errorControl->raise(100);
$domains = explode("\n",str_replace("\r","",preg_replace("/(\t| ){1,}/"," ",$domains)));
$domainList = array();
$gotdomain = false;
foreach ($domains as $dline) {
	if (strlen($dline)>0 && $dline[0] != "#") {
		$dline = explode(" ",$dline);
		if (count($dline)==2) {
			$thisdomains = explode(",",$dline[1]);
			foreach ($thisdomains as $td) {
				$td = trim($td);
				if ($td != "") {
					$domainList[$td] = $dline[0];
					if (!$gotdomain && ($td == $this->domain || $td == "*"))  {
						$_SESSION["CODE"] = $dline[0];
						$gotdomain = true;
					}
				}
			}
		}
	}
}
if (!is_dir(CONS_PATH_CACHE)) makeDirs(CONS_PATH_CACHE);
cWriteFile(CONS_PATH_CACHE."domains.dat",serialize($domainList));
return $domainList;