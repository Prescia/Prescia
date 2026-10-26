<?php	# -------------------------------- Statistics Plugin

/** @var CPrescia $this Core context injected by CPrescia::addPlugin(). */

if (!defined("CONS_USER_RESOLUTION")) define ("CONS_USER_RESOLUTION","aff_userres"); # it might be already defined by other modules
if (CONS_DB_HOST=='') $this->errorControl->raise(4,'bi_stats','STATS module requires database');

class mod_bi_stats extends CscriptedModule  {

	###########################
	var $logBOTS = false; // if a BOT is detected, dumps the agent into CONS_PATH_LOGS.$_SESSION['CODE']."/bots".date("Ymd").".log", "a" VERY RESOURCE INTENSIVE, USE ONLY FOR DEBUG
	var $doNotLogMe = false; // any plugin, page or automato that is aware of this plugin can set this to TRUE to prevent logging this page
	var $forceLogMe = false; // oposite of the above
	var $doNotLogAdmins = false; // if true, if you are looged with an account of a higher level of admRestrictionLevel, you won't be logged
	var $detectVisitorByIP = true; // if a visitor have cookies DISABLED, then it will track by IP
	###########################

	function loadSettings() {

		$this->name = "bi_stats";
		#$this->parent->onMeta[] = $this->name;
		$this->parent->onActionCheck[] = $this->name;
		#$this->parent->onRender[] = $this->name;
		$this->parent->on404[] = $this->name;
		$this->parent->onShow[] = $this->name;
		$this->parent->onEcho[] = $this->name;
		$this->parent->onCron[] = $this->name;
		$this->admRestrictionLevel = 10;
		$this->admOptions = array( );
	}


	function on404($action, $context = "") {
		if (str_replace("/","",$this->parent->context_str) == $this->admFolder) {
			$this->doNotLogMe = true; // we don't want admin being logged
			if (is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html"))
				return CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html";
		}
		return false;
	}

	function onCheckActions() {
		$core = &$this->parent;
		if ($this->parent->layout == 2 && $this->parent->context_str == "/" && ($this->parent->action == "setres" || $this->parent->action == "bdstats")) {
			$this->doNotLogMe = true; // no point loging this
				$resolution = isset($_REQUEST['res']) && is_string($_REQUEST['res']) ? $_REQUEST['res'] : '';
				$resolutionParts = array();
				if (preg_match('/^([1-9][0-9]{0,4})x([1-9][0-9]{0,4})$/', $resolution, $matches) === 1)
					$resolutionParts = $matches;
				if ($this->parent->action == "setres" && $_SERVER['REQUEST_METHOD'] === 'POST' && count($resolutionParts) === 3 && (int)$resolutionParts[1] <= 10000 && (int)$resolutionParts[2] <= 10000) { // set resolution
					$resolution = ((int)$resolutionParts[1]).'x'.((int)$resolutionParts[2]);
					echo "ok";
					$statsResolution = $this->parent->modules['statsres']->dbname;
				$visits = $this->parent->dbo->fetchPrepared("SELECT hits FROM ".$statsResolution." WHERE data=? AND resolution=?", 'ss', array(date("Y-m-d"), $resolution));
				if ($visits === false) { # first
					$r = false;
					$n = 0;
					$this->parent->dbo->queryPrepared("INSERT INTO ".$statsResolution." SET data=NOW(), resolution=?,hits=1", 's', array($resolution), $r, $n);
				} else { # second+ visit
					$r = false;
					$n = 0;
						$this->parent->dbo->queryPrepared("UPDATE ".$statsResolution." SET hits=hits+1 WHERE data=? AND resolution=?", 'ss', array(date("Y-m-d"), $resolution), $r, $n);
					}
					$_SESSION[CONS_USER_RESOLUTION] = $resolution;
			} else if ($this->parent->action == "bdstats") { // backdoor browser statistics (last year)
				$output = $this->parent->cacheControl->getCachedContent('bdstats');
				if ($output === false) {
					$sql = "SELECT sum(hits), browser FROM stats_browser WHERE data>NOW() - INTERVAL 1 YEAR GROUP BY browser";
					$output = array('IE' => 0,'FF' => 0, 'SA' => 0, 'OP' => 0, 'CH' => 0, 'UN' => 0, 'mob' =>0,'total' =>0);
					$r = false;
					$n = 0;
						if ($this->parent->dbo->queryPrepared($sql, "", array(), $r, $n)) {
						for ($c=0;$c<$n;$c++) {
							list($h,$b) = $this->parent->dbo->fetch_row($r);
							if (strpos($b,"Internet")!==false) $output['IE'] += $h;
							else if (strpos($b,"Firefox")!==false) $output['FF'] += $h;
							else if (strpos($b,"Chrome")!==false) $output['CH'] += $h;
							else if (strpos($b,"Opera")!==false) $output['OP'] += $h;
							else if (strpos($b,"Safari")!==false) $output['SA'] += $h;
							else $output['UN'] += $h;
							if (strpos($b,"(mob)")!==false) $output['mob'] += $h;
							$output['total'] += $h;
						}
					}
					if ($output['total'] >0) {
						$output['IE'] = ceil(10000*$output['IE']/$output['total'])/100;
						$output['FF'] = ceil(10000*$output['FF']/$output['total'])/100;
						$output['CH'] = ceil(10000*$output['CH']/$output['total'])/100;
						$output['OP'] = ceil(10000*$output['OP']/$output['total'])/100;
						$output['SA'] = ceil(10000*$output['SA']/$output['total'])/100;
						$output['UN'] = ceil(10000*$output['UN']/$output['total'])/100;
						$output['mob'] = ceil(10000*$output['mob']/$output['total'])/100;
					}
					$this->parent->cacheControl->addCachedContent('bdstats',serialize($output),true);
				} else
					$output = presciaSafeUnserialize($output);
				print_r($output);
			} else
				echo "err";
			$this->parent->close(true);
		}

	}

	function onShow(){
		$core = &$this->parent;
		if ($core->layout == 0 && is_object($core->template)) {
			if (!isset($_SESSION[CONS_USER_RESOLUTION])) {
				$core->template->constants['HEADJSTAGS'] .= "\n<script defer=\"defer\" type=\"text/javascript\" src=\"/".CONS_PATH_JSFRAMEWORK."getmyres.js\"></script>";
			}
		}
		$action = $this->parent->action;
		if (is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/content/$action.php"))
			include_once CONS_PATH_SYSTEM."plugins/".$this->name."/payload/content/$action.php";
	}

	function getCounter($filterPage,$filterLang='') {
		// let's play caching?
		$sum = $this->parent->cacheControl->getCachedContent('getCounter'.$filterPage."_".$filterLang);
		// if there is no cache ...
		if ($sum === false) {
			// sum all hits in the page specified. Language is conditional
			$sql = "SELECT sum(hits) FROM stats_hitsh WHERE page=?".($filterLang!=''?" AND lang=?":"")." GROUP BY page";
			$types = $filterLang != '' ? 'ss' : 's';
			$params = $filterLang != '' ? array($filterPage, $filterLang) : array($filterPage);
			$sum = $this->parent->dbo->fetchPrepared($sql, $types, $params);
			// remember, TODAY's hits are only in statsdaily
			$sql = "SELECT sum(hits) FROM stats_hitsd WHERE page=?".($filterLang!=''?" AND lang=?":"")." GROUP BY page";
			$more = $this->parent->dbo->fetchPrepared($sql, $types, $params);
			if ($more>0) $sum+=$more;
			$this->parent->cacheControl->addCachedContent('getCounter'.$filterPage."_".$filterLang,$sum,true);
		}
		return $sum;
	}

		function getHits($days=1,$groupDays=1,$filterPage='',$filterLang='') {
			$groupDays = max(1, (int) $groupDays);
			$stats = array();
		$sdh = $this->parent->loaded('stats');
		$sql = "SELECT sum(hits), sum(uhits), sum(bhits), sum(rhits) FROM ".$sdh->dbname." WHERE data = ?";
		$types = 's';
		$params = array(date("Y-m-d"));
		if ($filterPage!='') { $sql .= " AND page=?"; $types .= 's'; $params[] = $filterPage; }
		if ($filterLang!='') { $sql .= " AND lang=?"; $types .= 's'; $params[] = $filterLang; }
		$sql .= " GROUP BY data".($filterPage!=''?',page':'').($filterLang!=''?',lang':'');
		$r = false;
		$n = 0;
		if ($this->parent->dbo->queryPrepared($sql,$types,$params,$r,$n) && $n>0)
			$stats[] = $this->parent->dbo->fetch_row($r);
		if ($days == 1) return $stats;

		$sd = $this->parent->loaded('statsdaily');
		$days = max(1, (int)$days);
		$sql = "SELECT sum(hits), sum(uhits), sum(bhits), sum(rhits) FROM ".$sd->dbname." WHERE data > NOW() - INTERVAL ".$days." DAY";
		$types = '';
		$params = array();
		if ($filterPage!='') { $sql .= " AND page=?"; $types .= 's'; $params[] = $filterPage; }
		if ($filterLang!='') { $sql .= " AND lang=?"; $types .= 's'; $params[] = $filterLang; }
		$sql .= " GROUP BY data".($filterPage!=''?',page':'').($filterLang!=''?',lang':'')." ORDER BY data DESC";
		$r = false;
		$n = 0;
			if ($this->parent->dbo->queryPrepared($sql,$types,$params,$r,$n)) {
			for ($c=0;$c<$n;$c++) $stats[] = $this->parent->dbo->fetch_row($r);
		}
		if ($groupDays != 1) {
			$newstats = array();
			$pos = -1;
			for ($c=0;$c<count($stats);$c++) {
				if ($c % $groupDays === 0 || $pos === -1) {
					$pos++;
					$newstats[$pos] = array(0,0,0,0);
				}
				$newstats[$pos][0] += $stats[$c][0];
				$newstats[$pos][1] += $stats[$c][1];
				$newstats[$pos][2] += $stats[$c][2];
				$newstats[$pos][3] += $stats[$c][3];
			}
			return $newstats;
		}
		return $stats;
	}

	function onEcho(&$PAGE){

		$core = &$this->parent;

		if (CONS_ECONOMICMODE && $core->isbot) return;

		$pageToBelogged = substr($core->original_context_str,1);
		if ($pageToBelogged != "" && $pageToBelogged[strlen($pageToBelogged)-1] != "/") $pageToBelogged .= "/";

		$core = &$this->parent;
		if ($pageToBelogged != '') {
			if (isset($core->dimconfig['nostats']) && strpos(',/rss,'.$core->dimconfig['nostats'],','.$pageToBelogged.$core->action) !== false) {
				$this->doNotLogMe = true;
			}
			if (isset($core->dimconfig['nostats']) && strpos(','.$core->dimconfig['nostats'],','.$pageToBelogged) !== false) {
				$this->doNotLogMe = true;
			}
		}
		if ($core->action == '404' || $core->action == '403') $this->doNotLogMe = true;

		if (!$this->doNotLogMe || $this->forceLogMe) {

 			# what page are we logging (original call always)

			$act = $core->original_action;
			if ($act == "") $act = "index";
			else if (strpos($act,".")!==false) {
				$act = explode(".",$act); // remove extension:
				array_pop($act);
				$act = implode(".",$act);
			}
			$pageToBelogged .= $act;
			$pageToBelogged = str_replace('"',"",$pageToBelogged); # there are exploits everywhere!
			$sqlEscape = static function ($value) use ($core): string {
				return addslashes_EX((string)$value, true, $core->dbo);
			};
			$pageToBelogged = $sqlEscape($pageToBelogged);



			# is this a BOT? atm we consider unknown browsers as bots (some obvious crawlers are already set to unknown, see getBrowser.php) to make this faster, if CONS_HONEYPOT is on, will also detect bots using a honeypot
			$isBot = $core->isbot;

			# -- Check for "ignore ip"
			$iip = isset($this->parent->dimconfig['bi_statsignoreip'])?$this->parent->dimconfig['bi_statsignoreip']:'';
			$iip = explode(",",$iip);
			$ignoreme = false;
			foreach ($iip as $ip) {
				$ip = trim($ip);
		if ($ip != '' && strpos(CONS_IP,trim($ip)) !== false) {
					$ignoreme = true;
					break;
				};
			}
			if ($ignoreme) return; // is an IP to be ignored

			# -- Administrator logged in, log a-hit
			if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]>$this->admRestrictionLevel) {
				$id = (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))?$_REQUEST['id']:0;
				$statsTable = $core->modules['stats']->dbname;
				$statsKeyParams = array(date("Y-m-d"), date("H"), $pageToBelogged, (string)$id, (string)$_SESSION[CONS_SESSION_LANG]);
				$x = $core->dbo->fetchPrepared("SELECT hits FROM ".$statsTable." WHERE data=? AND hour=? AND page=? AND hid=? AND lang=?", 'sssss', $statsKeyParams);
				if ($x===false) {
					$r = false;
					$n = 0;
					$ok = $core->dbo->queryPrepared("INSERT INTO ".$statsTable." SET data=?, hour=?, page=?, hid=?, hits=0, uhits=0, bhits=0, ahits=1, rhits=0, lang=?", 'sssss', $statsKeyParams, $r, $n);
					if (!$ok) {
						$lastError = $this->parent->dbo->log[count($this->parent->dbo->log)-1];
						if (strpos(strtolower($lastError),"duplicate") !== false) { // concurrent INSERT happened first! use update
							array_pop($this->parent->dbo->log); // ignore this error please
							$core->dbo->queryPrepared("UPDATE ".$statsTable." SET ahits=ahits+1 WHERE data=? AND hour=? AND page=? AND hid=? AND lang=?", 'sssss', $statsKeyParams, $r, $n);
						}
					}
				} else {
					$r = false;
					$n = 0;
					$core->dbo->queryPrepared("UPDATE ".$statsTable." SET ahits=ahits+1 WHERE data=? AND hour=? AND page=? AND hid=? AND lang=?", 'sssss', $statsKeyParams, $r, $n);
				}
				if ($this->doNotLogAdmins) return;
			}

			# -- BOT STATS (if it's a bot, leave after this part) --
			if ($this->logBOTS) {
				$fd = fopen (CONS_PATH_LOGS.$_SESSION['CODE']."/bots".date("Ymd").".log", "a");
				if ($fd) {
					fwrite($fd,date("H:i:s")." ".CONS_IP." [".(isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"")."] ".$pageToBelogged."\n");
					fclose($fd);
				}
			}
			if ($isBot) {
				$r = false;
				$n = 0;
				$statsBots = $core->modules['statsbots']->dbname;
				$core->dbo->queryPrepared("SELECT hits FROM ".$statsBots." WHERE data=?", 's', array(date("Y-m-d")), $r, $n);
				if ($n==0) {
					# first bot visit
					$ok = $core->dbo->queryPrepared("INSERT INTO ".$statsBots." SET hits=1,data=?", 's', array(date("Y-m-d-")), $r, $n);
					if (!$ok) {
						$lastError = $this->parent->dbo->log[count($this->parent->dbo->log)-1];
						if (strpos(strtolower($lastError),"duplicate") !== false) { // concurrent INSERT happened first! use update
							array_pop($this->parent->dbo->log); // ignore this error please
							$core->dbo->queryPrepared("UPDATE ".$statsBots." SET hits=hits+1 WHERE data=?", 's', array(date("Y-m-d-")), $r, $n);
						}
					}
				} else {
					$r = false;
					$n = 0;
					$core->dbo->queryPrepared("UPDATE ".$statsBots." SET hits=hits+1 WHERE data=?", 's', array(date("Y-m-d-")), $r, $n);
				}
				return;	# no more stats for bots
			}
			# -- end BOT stats

			$browser = "";
			$legacy = false;
			list($browser,$legacy,$ismob)=getBrowser();

			# -- prepare cookie/IP monitoring variables
			$logByIP = false;
			$alreadyVisited = false;
			$r = false;
			$n = 0;
				$statsRealtime = $core->modules['statsrt']->dbname;
				if ($core->dbo->queryPrepared("SELECT page,fullpath FROM ".$statsRealtime." WHERE ip=?", 's', array(CONS_IP), $r, $n) && $n != 0) {
				list($page,$fullpath) = $core->dbo->fetch_row($r);
				$alreadyVisited = true; // by IP
			} else {
				$page = "";
				$fullpath = "";
			}

			# -- REFERER STATS --

			if (!isset($_COOKIE['session_visited'])) { // no cookies, first visit or cookies disabled
				if ($this->detectVisitorByIP && $alreadyVisited) { // NOT first visit, but no cookies?, and we want to track by IP
					$logByIP = true;
				}
				if (!$logByIP) {
					$partial_referer = str_replace("www.","",$core->domain); // www.prescia.net -> prescia.net (might be at sub-domain)
					$http_referer =  isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
					if ($http_referer == "" || (strpos($http_referer, $partial_referer) === false && strpos($partial_referer,'.')!== false)) {
						# valid external REFERER OR empty (bookmark)
						$referer = str_replace("http://","",$http_referer);
						$referer = str_replace("https://","",$referer);
						$referer = str_replace('"',"",$referer); # die exploits, die
						$domain = explode("/",$referer);
						$domain = $domain[0];
						// lets get some search engines here (faster than preg)
						if (strpos($domain,".google.") !== false) {
							$domain = "*.google.*";
						} else if (strpos($domain,".yahoo.") !== false) {
							$domain = "*.yahoo.*";
						} else if (strpos($domain,".facebook.com") !== false) {
							$domain = "*.facebook.com";
						} else if (strpos($domain,".bing.") !== false) {
							$domain = "*.bing.*";
						} else if (strpos($domain,"busca.uol.") !== false) {
							$domain = "busca.uol.*";
						} else if (strpos($domain,".mail.") !== false || substr($domain,0,5) == "mail." || strpos($domain,".webmail.") !== false || substr($domain,0,8) == "webmail.") {
							$domain = "MAIL";
							} else if (strlen($domain)>50) $domain = substr($domain,0,47)."...";
							$domain = $sqlEscape($domain);
							$r = false;
						$n = 0;
							$statsReferer = $core->modules['statsref']->dbname;
							$core->dbo->queryPrepared("SELECT hits, pages FROM ".$statsReferer." WHERE data=? AND referer=? AND entrypage=?", 'sss', array(date("Y-m-d"), $domain, $pageToBelogged), $r, $n);
						if ($n>0)
							list($hits,$pages) = $core->dbo->fetch_row($r);
						else {
							$hits = 0;
							$pages = "";
						}
						$hits++;
						if (strpos($pages,$referer.",") === false) $pages .= cleanString($referer).",";
					$pages = $sqlEscape($pages);
					if ($n == 0) {
						$r = false;
						$n = 0;
						$ok = $core->dbo->queryPrepared("INSERT INTO ".$statsReferer." SET data=?, referer=?, entrypage=?, hits=?, pages=?", 'sssis', array(date("Y-m-d"), $domain, $pageToBelogged, (int)$hits, $pages), $r, $n);
							if (!$ok) {
								$lastError = $this->parent->dbo->log[count($this->parent->dbo->log)-1];
								if (strpos(strtolower($lastError),"duplicate") !== false) { // concurrent INSERT happened first! use update
								array_pop($this->parent->dbo->log); // ignore this error please
								$core->dbo->queryPrepared("UPDATE ".$statsReferer." SET hits=?, pages=? WHERE data=? AND referer=? AND entrypage=?", 'issss', array((int)$hits, $pages, date("Y-m-d"), $domain, $pageToBelogged), $r, $n);
								}
							}
						} else {
							$r = false;
							$n = 0;
							$core->dbo->queryPrepared("UPDATE ".$statsReferer." SET hits=?, pages=? WHERE data=? AND referer=? AND entrypage=?", 'issss', array((int)$hits, $pages, date("Y-m-d"), $domain, $pageToBelogged), $r, $n);
						}


					} # not log by IP (is set if detected this IP already visited in the last 15 min, but has no cookies)
				} # if valid
			} # if new entry

			# -- end referer and query stats --
			# -- REAL TIME/Location STATS --
			$ok = false; // we will use this to control if we try second+ visit on concurrent include
			if (!$alreadyVisited) {
				$ok = true;
				# first visit
				if (!isset($referer)) {
					# should be set at referer stats
					$referer = str_replace("http://","",isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:"");
					$referer = str_replace("https://","",$referer);
				}
				$referer = $sqlEscape($referer);
				$whatToSave = CONS_BROWSER_ISMOB?"MO":CONS_BROWSER;
				$browser = $sqlEscape($browser);
					$r = false;
					$n = 0;
					$ok = $core->dbo->queryPrepared("INSERT INTO ".$statsRealtime." SET ip=?, page=?, pagelast=?, agent=?, agentcode=?, fullpath=?, data=NOW(), data_ini=NOW(), referer=?", 'sssssss', array(CONS_IP, $pageToBelogged, $pageToBelogged, $browser, $whatToSave, $pageToBelogged.",", $referer), $r, $n, true);
				if (!$ok) {
					$lastError = $this->parent->dbo->log[count($this->parent->dbo->log)-1];
					if (strpos(strtolower($lastError),"duplicate") === false) { // concurrent INSERT happened first! use update
						array_pop($this->parent->dbo->log); // ignore this error please
					}
				}
			}
			if (!$ok) { # second+ visit or concurrent include
				if ($page != $pageToBelogged)
					$fullpath .= $pageToBelogged.",";
					$r = false;
					$n = 0;
					$core->dbo->queryPrepared("UPDATE ".$statsRealtime." SET page=?, pagelast=?, data=NOW(), fullpath=? WHERE ip=?", 'ssss', array($pageToBelogged, $page, $fullpath, CONS_IP), $r, $n);

				# -- STATS PATH --
					$statsPath = $core->modules['statspath']->dbname;
					$count = $core->dbo->fetchPrepared("SELECT hits FROM ".$statsPath." WHERE data=? AND page=? AND pagefoward=?", 'sss', array(date("Y-m-d"), $page, $pageToBelogged));
				if ($count === false) {
						$r = false;
						$n = 0;
						$ok = $core->dbo->queryPrepared("INSERT INTO ".$statsPath." SET data=?, page=?, pagefoward=?, hits=1", 'sss', array(date("Y-m-d"), $page, $pageToBelogged), $r, $n);
					if (!$ok) {
						$lastError = $this->parent->dbo->log[count($this->parent->dbo->log)-1];
						if (strpos(strtolower($lastError),"duplicate") !== false) { // concurrent INSERT happened first! use update
								array_pop($this->parent->dbo->log); // ignore this error please
								$core->dbo->queryPrepared("UPDATE ".$statsPath." SET hits=hits+1 WHERE data=? AND page=? AND pagefoward=?", 'sss', array(date("Y-m-d"), $page, $pageToBelogged), $r, $n);
						}
					}
					} else {
						$r = false;
						$n = 0;
						$core->dbo->queryPrepared("UPDATE ".$statsPath." SET hits=hits+1 WHERE data=? AND page=? AND pagefoward=?", 'sss', array(date("Y-m-d"), $page, $pageToBelogged), $r, $n);
				}
				# -- end STATS PATH --
			}

			# -- end STATS PATH and REAL TIME --
			# -- HIT/UHIT/BHIT/AHITS stats -- (BHIT = browsing hit = acceptance, one per visitor)
			$id = (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))?$_REQUEST['id']:0;
			$isReturning = isset($_COOKIE['akr_returning']);
			$isAdm = str_replace("/","",$this->parent->context_str) == $this->admFolder;

				$statsTable = $core->modules['stats']->dbname;
				$statsKeyParams = array(date("Y-m-d"), date("H"), $pageToBelogged, (string)$id, (string)$_SESSION[CONS_SESSION_LANG]);
				$x = $core->dbo->fetchPrepared("SELECT hits FROM ".$statsTable." WHERE data=? AND hour=? AND page=? AND hid=? AND lang=?", 'sssss', $statsKeyParams);
			$ok = true; // also control concurrent includes from here
			if ($x===false) {
				# FIRST hit on this page here today
				if (!isset($_COOKIE['session_visited']) && !$logByIP) { // no cookie and we do not want to log by IP
					// first hit (1 1 0)
						$r = false;
						$n = 0;
						$ok = $core->dbo->queryPrepared("INSERT INTO ".$statsTable." SET data=?, hour=?, page=?, hid=?, hits=1, uhits=1, bhits=0, ahits=?, rhits=?, lang=?", 'ssssiis', array(date("Y-m-d"), date("H"), $pageToBelogged, (string)$id, (int)$isAdm, (int)$isReturning, $_SESSION[CONS_SESSION_LANG]), $r, $n);
					if (!$isReturning) @setcookie("akr_returning",'1',Time() + 86400); // 1 day
						@setcookie("session_visited",'1',Time()+3600); // 60 min
				} else if (!$logByIP && $_COOKIE['session_visited'] == 1) { // when logging by IP, we can't gather acceptance/browsing (b) hits
					// second hit (1 0 1)
						$r = false;
						$n = 0;
						$ok = $core->dbo->queryPrepared("INSERT INTO ".$statsTable." SET data=?, hour=?, page=?, hid=?, hits=1, uhits=0, bhits=1, ahits=?, rhits=0, lang=?", 'ssssis', array(date("Y-m-d"), date("H"), $pageToBelogged, (string)$id, (int)$isAdm, $_SESSION[CONS_SESSION_LANG]), $r, $n);
					@setcookie("session_visited",'2',Time()+3600); // 60 min
				} else { // third+ hit (1 0 0)
						$r = false;
						$n = 0;
						$ok = $core->dbo->queryPrepared("INSERT INTO ".$statsTable." SET data=?, hour=?, page=?, hid=?, hits=1, uhits=0, bhits=0, ahits=?, rhits=0, lang=?", 'ssssis', array(date("Y-m-d"), date("H"), $pageToBelogged, (string)$id, (int)$isAdm, $_SESSION[CONS_SESSION_LANG]), $r, $n);
					@setcookie("session_visited",'2',Time()+3600); // 60 min
				}
				if (!$ok) {
					$lastError = $this->parent->dbo->log[count($this->parent->dbo->log)-1];
					if (strpos(strtolower($lastError),"duplicate") !== false) { // concurrent INSERT happened first! use update
						array_pop($this->parent->dbo->log); // ignore this error please
					}
				}
			}
			if (!$ok || $x !== false) { // second+ hit of day
				if (!isset($_COOKIE['session_visited']) && !$logByIP) {
					// first hit 1 1 0
						$r = false;
						$n = 0;
						$core->dbo->queryPrepared("UPDATE ".$statsTable." SET hits=hits+1, uhits=uhits+1, rhits=rhits+?, ahits=ahits+? WHERE data=? AND hour=? AND page=? AND hid=? AND lang=?", 'iisssss', array((int)$isReturning, (int)$isAdm, date("Y-m-d"), date("H"), $pageToBelogged, (string)$id, (string)$_SESSION[CONS_SESSION_LANG]), $r, $n);
					if (!$isReturning) @setcookie("akr_returning",'1',Time() + 86400); // 1 day
					@setcookie("session_visited",'1',Time()+3600); // 60 min
				} else if (!$logByIP && $_COOKIE['session_visited'] == 1) {
					// second hit (1 0 1)
						$r = false;
						$n = 0;
						$core->dbo->queryPrepared("UPDATE ".$statsTable." SET hits=hits+1, bhits=bhits+1, ahits=ahits+? WHERE data=? AND hour=? AND page=? AND hid=? AND lang=?", 'isssss', array((int)$isAdm, date("Y-m-d"), date("H"), $pageToBelogged, (string)$id, (string)$_SESSION[CONS_SESSION_LANG]), $r, $n);
					@setcookie("session_visited",'2',Time()+3600); // 60 min
				} else { // third+ hit (1 0 0)
						$r = false;
						$n = 0;
						$core->dbo->queryPrepared("UPDATE ".$statsTable." SET hits=hits+1, ahits=ahits+? WHERE data=? AND hour=? AND page=? AND hid=? AND lang=?", 'isssss', array((int)$isAdm, date("Y-m-d"), date("H"), $pageToBelogged, (string)$id, (string)$_SESSION[CONS_SESSION_LANG]), $r, $n);
					@setcookie("session_visited",'2',Time()+3600); // 60 min
				}
			}

			# -- end HIT/UHIT/BHIT stats --
			# -- BROWSER stats --

				if ($browser != "") {
					if ($ismob) $browser .= " (mob)";
					$statsBrowser = $core->modules['statsbrowser']->dbname;
					$visits = $core->dbo->fetchPrepared("SELECT hits FROM ".$statsBrowser." WHERE data=? AND browser=?", 'ss', array(date("Y-m-d"), $browser));
					if ($visits === false) {
						# first
						$r = false;
						$n = 0;
						$ok = $core->dbo->queryPrepared("INSERT INTO ".$statsBrowser." SET data=NOW(), browser=?,hits=1", 's', array($browser), $r, $n);
					if (!$ok) {
						$lastError = $this->parent->dbo->log[count($this->parent->dbo->log)-1];
						if (strpos(strtolower($lastError),"duplicate") !== false) { // concurrent INSERT happened first! use update
								array_pop($this->parent->dbo->log); // ignore this error please
								$core->dbo->queryPrepared("UPDATE ".$statsBrowser." SET hits=hits+1 WHERE data=? AND browser=?", 'ss', array(date("Y-m-d"), $browser), $r, $n);
						}
					}
				} else { # second+ visit
					$r = false;
					$n = 0;
					$core->dbo->queryPrepared("UPDATE ".$statsBrowser." SET hits=hits+1 WHERE data=? AND browser=?", 'ss', array(date("Y-m-d"), $browser), $r, $n);
				}
			}

			# -- end Browser stats --
			# -- RESOLUTION stats --

				if (isset($_SESSION[CONS_USER_RESOLUTION]) && preg_match('/^[1-9][0-9]{1,4}x[1-9][0-9]{1,4}$/', (string)$_SESSION[CONS_USER_RESOLUTION]) === 1) {
					$resolution = (string)$_SESSION[CONS_USER_RESOLUTION];
					$statsResolution = $core->modules['statsres']->dbname;
					$visits = $core->dbo->fetchPrepared("SELECT hits FROM ".$statsResolution." WHERE data=? AND resolution=?", 'ss', array(date("Y-m-d"), $resolution));
					if ($visits === false) {
						# first
						$r = false;
						$n = 0;
						$ok = $core->dbo->queryPrepared("INSERT INTO ".$statsResolution." SET data=NOW(), resolution=?,hits=1", 's', array($resolution), $r, $n);
						if (!$ok) {
							$lastError = $this->parent->dbo->log[count($this->parent->dbo->log)-1];
							if (strpos(strtolower($lastError),"duplicate") !== false) { // concurrent INSERT happened first! use update
								array_pop($this->parent->dbo->log); // ignore this error please
								$core->dbo->queryPrepared("UPDATE ".$statsResolution." SET hits=hits+1 WHERE data=? AND resolution=?", 'ss', array(date("Y-m-d"), $resolution), $r, $n);
							}
						}
					} else { # second+ visit
						$r = false;
						$n = 0;
						$core->dbo->queryPrepared("UPDATE ".$statsResolution." SET hits=hits+1 WHERE data=? AND resolution=?", 'ss', array(date("Y-m-d"), $resolution), $r, $n);
					}
				}

		}

		## BENCHMARK ##
		if (CONS_ECONOMICMODE || (isset($core->dimconfig['nobenchstats']) && strpos(','.$core->dimconfig['nobenchstats'],','.$core->action) !== false)) {
			return; # ignore benchmark on this page
		}
		$totalTime = scriptTime() * 1000;
		$file = CONS_PATH_LOGS.$_SESSION['CODE']."/scripttime.dat";
		$data = array(date('H'),0,0,0,0,0,array()); // hour, max time w/o cache, max time w/ cache, bot hits today, normal hits today, last week average, browser array
		if (is_file($file)) $data = presciaSafeUnserialize(cReadFile($file));
		if (!is_array($data) || count($data)<5) $data = array(date('H'),0,0,0,0,0,array()); // error above
		if ($data[0] != date('H')) $data = array(date('H'),0,0,0,0,0,array()); // reset
		if (CONS_CACHE && $this->parent->cacheControl->contentFromCache) {
			if ($data[2]<$totalTime) { # this hit took longer
				$data[2] = $totalTime;
				if ($data[4] > 0) { # other stats are ok, just save the new data and leave
					cWriteFile($file,serialize($data));
					return;
				}
			} else # first hit should never get here, so we will end up on resetSTdata
					return;
		} else {
			if ($data[1]<$totalTime) { # this hit took longer
				$data[1] = $totalTime;
				if ($data[4] > 0) { # other stats are ok, just save the new data and leave
					cWriteFile($file,serialize($data));
					return;
				}
			} else # first hit should never get here, so we will end up on resetSTdata
				return;
		}
		# if reached this line, stats for hits is not full and we want to save it
		$this->resetSTdata($data);
	}

	function resetSTdata($data) { // fulls hit data on scripttime
		/* data stored:
		   0 = Hour of latest stats
		   1 = Max time (in this hour) w/o cache
		   2 = Max time (in this hour) w/ cache
		   3 = Bot hits TODAY (or yesterday if no hit so far)
		   4 = Normal hits TODAY
		   5 = Last week data (array with 4 hit counter)
		   6 = Browser data for last MONTH (array with all browsers)
		*/
		$file = CONS_PATH_LOGS.$_SESSION['CODE']."/scripttime.dat";
		$sb =  $this->parent->loaded('statsbots');
		$data[3] = $this->parent->dbo->fetchPrepared("SELECT hits FROM ".$sb->dbname." WHERE data=?", 's', array(date("Y-m-d")));
		if ($data[3] === false) $data[3] = 0; // bots
		$data[4] = $this->getHits(1);
		if (count($data[4]) == 0) $data[4] = 0; // normal
		else $data[4] = $data[4][0][0];
		$data[5] = $this->getHits(7);
		if (count($data[5]) == 0) $data[5] = array(0,0,0,0); // sums
		// 6 is browser stats on last week
		$data[6] = array(); // browser
		$sb = $this->parent->loaded('statsbrowser');
		$r = false;
		$n = 0;
		$this->parent->dbo->queryPrepared("SELECT sum(hits), browser FROM ".$sb->dbname." WHERE data>NOW() - INTERVAL 1 MONTH GROUP BY browser", "", array(), $r, $n);
		for($c=0;$c<$n;$c++) {
			list($count,$browser) = $this->parent->dbo->fetch_row($r);
			$data[6][$browser] = $count;
		}
		cWriteFile($file,serialize($data));
		if ($data[3] > $data[4]*3 && $data[4]>0 && $data[3] > $data[5][0]) {
			$this->parent->errorControl->raise(525,$data[3],'bi_stats');
		}
	}

	function onCron($isDay=false) { # cron Triggered, isDay or isHour
		if (!$isDay) { # hourly
			$totalTime = scriptTime() * 1000;
			$data = array(date("H"),0,0,0,0,0);
			$data[0] = date("H");
			if (CONS_CACHE && $this->parent->cacheControl->contentFromCache) {
				$data[2] = $totalTime;
			} else {
				$data[1] = $totalTime;
			}
			$this->resetSTdata($data);
		} else { # daily
			$core = &$this->parent;
			# daily statistics:
			$previousDay = datecalc(date("Y-m-d"),0,0,-1);
				$statsDaily = $core->modules['statsdaily']->dbname;
				$x = $core->dbo->fetchPrepared("SELECT hits FROM ".$statsDaily." WHERE data=?", 's', array($previousDay));
			if ($x === false) { # nothing yet registered on history
				$r = false;
				$n = 0;
					$statsTable = $core->modules['stats']->dbname;
					if ($core->dbo->queryPrepared("SELECT SUM(hits), SUM(uhits), SUM(bhits), SUM(rhits), hid, page,lang FROM ".$statsTable." WHERE data=? GROUP BY hid, page, lang", 's', array($previousDay), $r, $n)) {
					for ($c=0;$c<$n;$c++) {
						list($hits,$uhits,$bhits,$rhits,$hid,$page,$lang) = $core->dbo->fetch_row($r);
							$core->dbo->queryPrepared("INSERT INTO ".$statsDaily." SET lang=?, hid=?, data=?, page=?, hits=?, uhits=?, bhits=?, rhits=?", 'ssssiiii', array($lang, (string)$hid, $previousDay, $page, (int)$hits, (int)$uhits, (int)$bhits, (int)$rhits), $r, $n);
					}
				}

			}
			# daily referers:
				$statsRefDaily = $core->modules['statsrefdaily']->dbname;
				$x = $core->dbo->fetchPrepared("SELECT hits FROM ".$statsRefDaily." WHERE data=?", 's', array($previousDay));
			if ($x===false ) { # nothing yet registered on history
				$r = false;
				$n = 0;
					$statsReferer = $core->modules['statsref']->dbname;
					if ($core->dbo->queryPrepared("SELECT referer,entrypage,hits FROM ".$statsReferer." WHERE data=?", 's', array($previousDay), $r, $n)){
					for ($c=0;$c<$n;$c++) {
						list($ref,$ep,$hits) = $core->dbo->fetch_row($r);
							$core->dbo->queryPrepared("INSERT INTO ".$statsRefDaily." SET data=?, referer=?, hits=?, entrypage=?", 'ssis', array($previousDay, $ref, (int)$hits, $ep), $r, $n);
					}
				}
			}
		}
		# done
	}

}
