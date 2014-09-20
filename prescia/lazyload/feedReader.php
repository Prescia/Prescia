<?/* -------------------------------- Prescia extra core functions
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
  | These functions are not used so often, so to reduce parse time from the PHP compiler, they where removed from the core.php
  | This reads an external RSS feed into an ARRAY, as if queried from the database with mysql_assoc
  | $url = URL to the feed
  | $cancache= true|false if this can be cached
-*/

	if ($cancache) {
		$cached = $this->cacheControl->getCachedContent("feedReader_".$url);
		if ($cached !== false) return $cached;
	}

	if (!function_exists("fget")) include_once CONS_PATH_INCLUDE."loadURL.php";
	if (!function_exists("xmlParamsParser")) include_once CONS_PATH_INCLUDE."xmlHandler.php";
	$feed = loadURL($url); // reads feed
	$output = array();
	if ($feed !== false) { // valid content
		$feedXML = new xmlHandler(); // parses into XML
		$feed = $feedXML->parseXML($feed[1],array(C_XML_RAW => true,C_XML_AUTOPARSE => true,C_XML_LAX => true, C_XML_REMOVECOMMENTS => true));
		// locates CHANNEL or channel (could skip this and get only items)
		$channel = $feed->getNode("CHANNEL");
		if ($channel === false) $channel = $feed->getNode("channel");
		if ($channel !== false) { // found channel
			$items = $channel[0]->getNode("ITEM"); // <-- note this supports only the first channel
			if ($items == false) $items = $channel->getNode("item"); // locate item's
			if ($items !== false) { // found them!
				foreach ($items as $item) { // for each item
					$thisItem = array();
					foreach ($item->branchs as $branch) { // for each tag inside ...
						$bname = $branch->data[0];
						if ($branch->data[2] != '') {
							$thisItem[strtolower($bname)] = $branch->data[2]; // text
						} else {
							$thisItem[strtolower($bname)] = $branch->echoHTML(true); // html (probably <![cdata[ )
						}
					}
					$output[] = $thisItem;
				}
			}
		}
		if (count($output)>0) {
			if ($cancache) $this->cacheControl->addCachedContent("feedreader_".$url,$output,true);
			return $output; // done deal
		}
	}
	return false; // ops

