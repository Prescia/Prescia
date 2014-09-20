<?	# -------------------------------- Statistics Plugin

if (!defined("CONS_USER_RESOLUTION")) define ("CONS_USER_RESOLUTION","aff_userres"); # it might be already defined by other modules
if (CONS_DB_HOST=='') $this->errorControl->raise(4,'bi_stats','STATS module requires database');

class mod_bi_stats extends CscriptedModule  {

	var $logBOTS = false; // if a BOT is detected, dumps the agent into CONS_PATH_LOGS.$_SESSION['CODE']."/bots".date("Ymd").".log", "a" VERY RESOURCE INTENSIVE, USE ONLY FOR DEBUG
	var $doNotLogMe = false; // any plugin, page or automato that is aware of this plugin can set this to TRUE to prevent logging this page
	var $forceLogMe = false; // oposite of the above

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
		$this->admOptions = array( ); // menu added manually (nekoi 2.0)
	}


	function on404($action, $context = "") {
		if ($this->parent->context_str == $this->admFolder) {
			$this->doNotLogMe = true; // we don't want admin being logged
			if (is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html"))
				return CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html";
		}
		return false;
	}

	function onCheckActions() {
		if ($this->parent->layout == 2 && $this->parent->context_str == "/" && $this->parent->action == "setres") {
			$this->doNotLogMe = true;
			if (isset($_REQUEST['res']) && strlen($_REQUEST['res'])>6) {
				echo "ok";
				$_SESSION[CONS_USER_RESOLUTION] = $_REQUEST['res'];
				$visits = $this->parent->dbo->fetch("SELECT hits FROM ".$this->parent->modules['statsres']->dbname." WHERE data='".date("Y-m-d")."' AND resolution=\"".$_SESSION[CONS_USER_RESOLUTION]."\"");
				if ($visits === false) { # first
					$this->parent->dbo->simpleQuery("INSERT INTO ".$this->parent->modules['statsres']->dbname." SET data=NOW(), resolution=\"".$_SESSION[CONS_USER_RESOLUTION]."\",hits=1");
				} else { # second+ visit
					$this->parent->dbo->simpleQuery("UPDATE ".$this->parent->modules['statsres']->dbname." SET hits=hits+1 WHERE data='".date("Y-m-d")."' AND resolution=\"".$_SESSION[CONS_USER_RESOLUTION]."\"");
				}
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

	function getHits($days=1,$groupDays=1,$filterPage='',$filterLang='') {
		$stats = array();
		// today's stats are not in the statsdaily, but in stats ... get only TODAY's:
		$sdh = $this->parent->loaded('stats');
		$sql = "SELECT sum(hits), sum(uhits), sum(bhits), sum(rhits) FROM ".$sdh->dbname." WHERE ";
		$where = array();
		$where[] = "data = '".date("Y-m-d")."'"; // only today, see?
		if ($filterPage!='') {
			$where[] = "page=\"$filterPage\"";
		}
		if ($filterLang!='') {
			$where[] = "lang=\"$filterLang\"";
		}
		$sql .= implode(" AND ",$where);
		$sql .= " GROUP BY data".($filterPage!=''?',page':'').($filterLang!=''?',lang':'');
		if ($this->parent->dbo->query($sql,$r,$n) && $n>0) {
			$stats[] = $this->parent->dbo->fetch_row($r); // if more come, WHAT!?
		}
		if ($days == 1) return $stats; // done
		$sd = $this->parent->loaded('statsdaily');
		$sql = "SELECT sum(hits), sum(uhits), sum(bhits), sum(rhits) FROM ".$sd->dbname." WHERE ";
		$where = array();
		$where[] = "data > NOW() - INTERVAL $days DAY";
		if ($filterPage!='') {
			$where[] = "page=\"$filterPage\"";
		}
		if ($filterLang!='') {
			$where[] = "lang=\"$filterLang\"";
		}
		$sql .= implode(" AND ",$where);
		$sql .= " GROUP BY data".($filterPage!=''?',page':'').($filterLang!=''?',lang':'');
		$sql .= " ORDER BY data DESC";
		if ($this->parent->dbo->query($sql,$r,$n) && $n>0) {
			for ($c=0;$c<$n;$c++)
				$stats[] = $this->parent->dbo->fetch_row($r);
		}
		if ($groupDays != 1) {
			$newstats = array();
			$pos = -1;
			for ($c=0;$c<count($stats);$c++) {
				if ($c % $groupDays ==0 || $pos == -1) {
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
		if (isset($core->dimconfig['nostats']) && strpos(',rss,'.$core->dimconfig['nostats'],','.$core->action) !== false) {
			$this->doNotLogMe = true;
		}
		if (isset($core->dimconfig['nostats']) && strpos(','.$core->dimconfig['nostats'],','.$core->context_str) !== false) {
			$this->doNotLogMe = true;
		}
		if ($core->action == '404' || $core->action == '403') $this->doNotLogMe = true;

		if (!$this->doNotLogMe || $this->forceLogMe) {

 			# what page are we logging (original call always)
			$pageToBelogged = $core->original_context_str;
			if ($pageToBelogged[0] == "/") $pageToBelogged = substr($pageToBelogged,1);
			if ($pageToBelogged != "" && $pageToBelogged[strlen($pageToBelogged)-1] != "/") $pageToBelogged .= "/";
			$act = $core->original_action;
			if ($act == "") $act = "index";
			else if (strpos($act,".")!==false) {
				$act = explode(".",$act); // remove extension:
				array_pop($act);
				$act = implode(".",$act);
			}
			$pageToBelogged .= $act;

			# is this a BOT? atm we consider unknown browsers as bots to make this faster
			$isBot = CONS_BROWSER == "UN";

			# -- Check for "ignore ip"
			$iip = isset($this->parent->dimconfig['bi_statsignoreip'])?$this->parent->dimconfig['bi_statsignoreip']:'';
			$iip = explode(",",$iip);
			$ignoreme = false;
			foreach ($iip as $ip) {
				$ip = trim($ip);
				if ($ip != '' && strpos(CONS_IP,trim($ip))!==-1) {
					$ignoreme = true;
					break;
				};
			}
			if ($ignoreme) return; // is an IP to be ignored

			if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]>$this->admRestrictionLevel) {
				$id = (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))?$_REQUEST['id']:0;
				$x = $core->dbo->fetch("SELECT hits FROM ".$core->modules['stats']->dbname." WHERE data = '".date("Y-m-d")."' AND hour = '".date("H")."' AND page=\"".$pageToBelogged."\" AND hid=\"".$id."\" AND lang=\"".$_SESSION[CONS_SESSION_LANG]."\"");
				if ($x===false) {
					$core->dbo->simpleQuery("INSERT INTO ".$core->modules['stats']->dbname." SET data = '".date("Y-m-d")."' , hour = '".date("H")."' , page=\"".$pageToBelogged."\" , hid=\"".$id."\", hits=0, uhits=0, bhits=0, ahits=1, rhits=0, lang=\"".$_SESSION[CONS_SESSION_LANG]."\"");
				} else {
					$core->dbo->simpleQuery("UPDATE ".$core->modules['stats']->dbname." SET ahits=ahits+1 WHERE data = '".date("Y-m-d")."' AND hour = '".date("H")."' AND page=\"".$pageToBelogged."\" AND hid=\"".$id."\" AND lang=\"".$_SESSION[CONS_SESSION_LANG]."\"");
				}
				return;
			}

			# -- BOT STATS (if it's a bot, leave after this part) --
			if ($isBot) {
				if ($this->logBOTS) {
					$fd = fopen (CONS_PATH_LOGS.$_SESSION['CODE']."/bots".date("Ymd").".log", "a");
					if ($fd) {
						fwrite($fd,CONS_IP." ".(isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"")." ? ".$this->parent->context_str.$this->parent->action."\n");
						fclose($fd);
					}
				}
				$core->dbo->query("SELECT hits FROM ".$core->modules['statsbots']->dbname." WHERE data='".date("Y-m-d")."'",$r,$n);
				if ($n==0) {
					# first bot visit
					$core->dbo->simpleQuery("INSERT INTO ".$core->modules['statsbots']->dbname." SET hits=1,data='".date("Y-m-d-")."'");
				} else {
				$core->dbo->simpleQuery("UPDATE ".$core->modules['statsbots']->dbname." SET hits=hits+1 WHERE data='".date("Y-m-d-")."'");
				}
				return;	# no more stats for bots
			}
			# -- end BOT stats

			$browser = "";
			$legacy = false;
			list($browser,$legacy,$ismob)=getBrowser();

			# -- REFERER STATS --
			if (!isset($_COOKIE['session_visited'])) {
				$partial_referer = str_replace("www.","",$core->domain); // www.prescia.net -> prescia.net (might be at sub-domain)
				$http_referer =  isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
				if ($http_referer == "" || (strpos($http_referer, $partial_referer) === false && strpos($partial_referer,'.')!== false)) {
					# valid external REFERER OR empty (bookmark)
					$referer = str_replace("http://","",$http_referer);
					$referer = str_replace("https://","",$referer);
					$domain = explode("/",$referer);
					$domain = $domain[0];
					$isSearchEngine = false;
					// lets get some search engines here (faster than preg)
					if (strpos($domain,"www.google.") !== false) {
						$domain = "www.google.*";
						$isSearchEngine = true;
					} else if (strpos($domain,".yahoo.co") !== false) {
						$domain = "*.yahoo.*";
						$isSearchEngine = true;
					} else if (strpos($domain,".bing.") !== false) {
						$domain = "*.bing.*";
						$isSearchEngine = true;
					} else if (strpos($domain,"busca.uol.") !== false) {
						$domain = "busca.uol.*";
						$isSearchEngine = true;
					} else if (strpos($domain,".mail.") !== false || substr($domain,0,5) == "mail.") {
						$domain = "MAIL";
					} else if (strlen($domain)>50) $domain = substr($domain,0,47)."...";
					$core->dbo->query("SELECT hits, pages FROM ".$core->modules['statsref']->dbname." WHERE data='".date("Y-m-d")."' AND referer=\"$domain\" AND entrypage=\"".$pageToBelogged."\"",$r,$n);
					if ($n>0)
						list($hits,$pages) = $core->dbo->fetch_row($r);
					else {
						$hits = 0;
						$pages = "";
					}
					$hits++;
					if (strpos($pages,$referer.",") === false) $pages .= cleanString($referer).",";
					if ($n == 0)
						$core->dbo->simpleQuery("INSERT INTO ".$core->modules['statsref']->dbname." SET data='".date("Y-m-d")."', referer=\"$domain\", entrypage=\"".$pageToBelogged."\", hits=$hits, pages=\"".$pages."\"");
					else
						$core->dbo->simpleQuery("UPDATE ".$core->modules['statsref']->dbname." SET hits=$hits, pages=\"".$pages."\" WHERE data='".date("Y-m-d")."' AND referer=\"$domain\" AND entrypage=\"".$pageToBelogged."\"");

					# -- QUERY STATS --

					if ($isSearchEngine) {
						# came from search tool
						if (preg_match("/(\?|\&)(p|q|qs)=([^\&]*)/i",$referer,$req)) {
							# detected query!
							$query = preg_replace("/(\+|&|%..)/i"," ",$req[3]);
							$query = preg_replace("/( ){2,}/i"," ",$query);
							$hits = $core->dbo->fetch("SELECT count FROM ".$core->modules['statsquery']->dbname." WHERE data='".date("Y-m-d")."' AND engine=\"$domain\" AND query=\"$query\" AND entrypage=\"".$pageToBelogged."\"");
							if ($hits === false)
								$core->dbo->simpleQuery("INSERT INTO ".$core->modules['statsquery']->dbname." SET data='".date("Y-m-d")."' , engine=\"$domain\" , query=\"$query\" , entrypage=\"".$pageToBelogged."\", count=1");
							else
								$core->dbo->simpleQuery("UPDATE ".$core->modules['statsquery']->dbname." SET count=count+1 WHERE data='".date("Y-m-d")."' AND engine=\"$domain\" AND query=\"$query\" AND entrypage=\"".$pageToBelogged."\"");
						} # detected query
					} # is search

					# -- end query stats --
				} # if valid
			} # if new entry

			# -- end referer and query stats --
			# -- REAL TIME/Location STATS --

			$core->dbo->query("SELECT page,fullpath FROM ".$core->modules['statsrt']->dbname." WHERE ip='".CONS_IP."'",$r,$n);
			if ($n==0) {
				# first visit
				if (!isset($referer)) {
					# should be set at referer stats
					$referer = str_replace("http://","",isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:"");
					$referer = str_replace("https://","",$referer);
				}
				$whatToSave = CONS_BROWSER_ISMOB?"MO":CONS_BROWSER;
				$core->dbo->simpleQuery("INSERT INTO ".$core->modules['statsrt']->dbname." SET ip='".CONS_IP."', page=\"".$pageToBelogged."\", agent=\"".$browser."\", agentcode=\"".$whatToSave."\", fullpath=\"".$pageToBelogged.",\", data=NOW(), data_ini=NOW(), referer=\"$referer\"");
			} else { # second+ visit
				list($page,$fullpath) = $core->dbo->fetch_row($r);
				if ($page != $pageToBelogged)
					$fullpath .= $pageToBelogged.",";
				$core->dbo->simpleQuery("UPDATE ".$core->modules['statsrt']->dbname." SET page=\"".$pageToBelogged."\", pagelast=\"$page\", data=NOW(), fullpath=\"$fullpath\" WHERE ip='".CONS_IP."'");

				# -- STATS PATH --
				$count = $core->dbo->fetch("SELECT count FROM ".$core->modules['statspath']->dbname." WHERE data='".date("Y-m-d")."' AND page=\"$page\" AND pagefoward=\"".$pageToBelogged."\"");
				if ($count === false) {
					$core->dbo->simpleQuery("INSERT INTO ".$core->modules['statspath']->dbname." SET data='".date("Y-m-d")."', page=\"$page\", pagefoward=\"".$pageToBelogged."\", count=1");
				} else {
					$core->dbo->simpleQuery("UPDATE ".$core->modules['statspath']->dbname." SET count=count+1 WHERE data='".date("Y-m-d")."' AND page=\"$page\" AND pagefoward=\"".$pageToBelogged."\"");
				}
				# -- end STATS PATH --
			}

			# -- end STATS PATH and REAL TIME --
			# -- HIT/UHIT/BHIT/AHITS stats --
			$id = (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))?$_REQUEST['id']:0;
			$isReturning = isset($_COOKIE['akr_returning']);
			$isAdm = $this->parent->context_str == $this->admFolder;
			$x = $core->dbo->fetch("SELECT hits FROM ".$core->modules['stats']->dbname." WHERE data = '".date("Y-m-d")."' AND hour = '".date("H")."' AND page=\"".$pageToBelogged."\" AND hid=\"".$id."\" AND lang=\"".$_SESSION[CONS_SESSION_LANG]."\"");
			if ($x===false) {
				# FIRST hit here today
				if (!isset($_COOKIE['session_visited'])) {
					// first hit (1 1 0)
					$core->dbo->simpleQuery("INSERT INTO ".$core->modules['stats']->dbname." SET data = '".date("Y-m-d")."' , hour = '".date("H")."' , page=\"".$pageToBelogged."\" , hid=\"".$id."\", hits=1, uhits=1, bhits=0, ahits=".($isAdm?1:0).", rhits=".($isReturning?"1":"0").", lang=\"".$_SESSION[CONS_SESSION_LANG]."\"");
					if (!$isReturning) @setcookie("akr_returning",'1',Time() + 86400); // 1 day
						@setcookie("session_visited",'1',Time()+3600); // 60 min
				} else if ($_COOKIE['session_visited'] == 1) {
					// second hit (1 0 1)
					$core->dbo->simpleQuery("INSERT INTO ".$core->modules['stats']->dbname." SET data = '".date("Y-m-d")."' , hour = '".date("H")."' , page=\"".$pageToBelogged."\" , hid=\"".$id."\", hits=1, uhits=0, bhits=1, ahits=".($isAdm?1:0).", rhits=0, lang=\"".$_SESSION[CONS_SESSION_LANG]."\"");
					@setcookie("session_visited",'2',Time()+3600); // 60 min
				} else { // third+ hit (1 0 0)
					$core->dbo->simpleQuery("INSERT INTO ".$core->modules['stats']->dbname." SET data = '".date("Y-m-d")."' , hour = '".date("H")."' , page=\"".$pageToBelogged."\" , hid=\"".$id."\", hits=1, uhits=0, bhits=0, ahits=".($isAdm?1:0).", rhits=0, lang=\"".$_SESSION[CONS_SESSION_LANG]."\"");
					@setcookie("session_visited",'2',Time()+3600); // 60 min
				}
			} else { // segundo+ hit do dia aqui
				if (!isset($_COOKIE['session_visited'])) {
					// first hit 1 1 0
					$core->dbo->simpleQuery("UPDATE ".$core->modules['stats']->dbname." SET hits=hits+1, uhits=uhits+1 ".($isReturning?", rhits=rhits+1":"").($isAdm?", ahits=ahits+1":"")." WHERE data = '".date("Y-m-d")."' AND hour = '".date("H")."' AND page=\"".$pageToBelogged."\" AND hid=\"".$id."\" AND lang=\"".$_SESSION[CONS_SESSION_LANG]."\"");
					if (!$isReturning) @setcookie("akr_returning",'1',Time() + 86400); // 1 day
					@setcookie("session_visited",'1',Time()+3600); // 60 min
				} else if ($_COOKIE['session_visited'] == 1) {
					// second hit (1 0 1)
					$core->dbo->simpleQuery("UPDATE ".$core->modules['stats']->dbname." SET hits=hits+1, bhits=bhits+1".($isAdm?", ahits=ahits+1":"")." WHERE data = '".date("Y-m-d")."' AND hour = '".date("H")."' AND page=\"".$pageToBelogged."\" AND hid=\"".$id."\" AND lang=\"".$_SESSION[CONS_SESSION_LANG]."\"");
					@setcookie("session_visited",'2',Time()+3600); // 60 min
				} else { // third+ hit (1 0 0)
					$core->dbo->simpleQuery("UPDATE ".$core->modules['stats']->dbname." SET hits=hits+1".($isAdm?", ahits=ahits+1":"")." WHERE data = '".date("Y-m-d")."' AND hour = '".date("H")."' AND page=\"".$pageToBelogged."\" AND hid=\"".$id."\" AND lang=\"".$_SESSION[CONS_SESSION_LANG]."\"");
					@setcookie("session_visited",'2',Time()+3600); // 60 min
				}
			}

			# -- end HIT/UHIT/BHIT stats --
			# -- BROWSER stats --

			if ($browser != "") {
				if ($ismob) $browser .= " (mob)";
				$visits = $core->dbo->fetch("SELECT hits FROM ".$core->modules['statsbrowser']->dbname." WHERE data='".date("Y-m-d")."' AND browser=\"$browser\"");
				if ($visits === false) {
					# first
					$core->dbo->simpleQuery("INSERT INTO ".$core->modules['statsbrowser']->dbname." SET data=NOW(), browser=\"$browser\",hits=1");
				} else { # second+ visit
					$core->dbo->simpleQuery("UPDATE ".$core->modules['statsbrowser']->dbname." SET hits=hits+1 WHERE data='".date("Y-m-d")."' AND browser=\"$browser\"");
				}
			}

			# -- end Browser stats --
			# -- RESOLUTION stats --

			if (isset($_SESSION[CONS_USER_RESOLUTION])) {
				$visits = $core->dbo->fetch("SELECT hits FROM ".$core->modules['statsres']->dbname." WHERE data='".date("Y-m-d")."' AND resolution=\"".$_SESSION[CONS_USER_RESOLUTION]."\"");
				if ($visits === false) {
					# first
					$core->dbo->simpleQuery("INSERT INTO ".$core->modules['statsres']->dbname." SET data=NOW(), resolution=\"".$_SESSION[CONS_USER_RESOLUTION]."\",hits=1");
				} else { # second+ visit
					$core->dbo->simpleQuery("UPDATE ".$core->modules['statsres']->dbname." SET hits=hits+1 WHERE data='".date("Y-m-d")."' AND resolution=\"".$_SESSION[CONS_USER_RESOLUTION]."\"");
				}
			}

		}


		## BENCHMARK ##
		if (isset($core->dimconfig['nobenchstats']) && strpos(','.$core->dimconfig['nobenchstats'],','.$core->action) !== false) {
			return; # ignore benchmark on this page
		}
		$totalTime = scriptTime() * 1000;
		$file = CONS_PATH_LOGS.$_SESSION['CODE']."/scripttime.dat";
		$data = array(date('H'),0,0,0,0,0,array()); // hour, max time w/o cache, max time w/ cache, bot hits today, normal hits today, last week average, browser array
		if (is_file($file)) $data = unserialize(cReadFile($file));
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
		$data[3] = $this->parent->dbo->fetch("SELECT hits FROM ".$sb->dbname." WHERE data='".date("Y-m-d")."'");
		if ($data[3] === false) $data[3] = 0; // bots
		$data[4] = $this->getHits(1);
		if (count($data[4]) == 0) $data[4] = 0; // normal
		else $data[4] = $data[4][0][0];
		$data[5] = $this->getHits(7);
		if (count($data[5]) == 0) $data[5] = array(0,0,0,0); // sums
		// 6 is browser stats on last week
		$data[6] = array(); // browser
		$sb = $this->parent->loaded('statsbrowser');
		$this->parent->dbo->query("SELECT sum(hits), browser FROM ".$sb->dbname." WHERE data>NOW() - INTERVAL 1 MONTH GROUP BY browser",$r,$n);
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
			$x = $core->dbo->fetch("SELECT hits FROM ".$core->modules['statsdaily']->dbname." WHERE data='".$previousDay."'");
			if ($x === false) { # nothing yet registered on history
				if ($core->dbo->query("SELECT SUM(hits), SUM(uhits), SUM(bhits), SUM(rhits), hid, page,lang FROM ".$core->modules['stats']->dbname." WHERE data='".$previousDay."' GROUP BY hid, page, lang",$r,$n)) {
					for ($c=0;$c<$n;$c++) {
						list($hits,$uhits,$bhits,$rhits,$hid,$page,$lang) = $core->dbo->fetch_row($r);
						$core->dbo->simpleQuery("INSERT INTO ".$core->modules['statsdaily']->dbname." SET lang='$lang', hid='$hid', data='$previousDay', page=\"$page\", hits=$hits, uhits=$uhits, bhits=$bhits, rhits=$rhits");
					}
				}

			}
			# daily referers:
			$x = $core->dbo->fetch("SELECT hits FROM ".$core->modules['statsrefdaily']->dbname." WHERE data='".$previousDay."'");
			if ($x===false ) { # nothing yet registered on history
				if ($core->dbo->query("SELECT referer,entrypage,hits FROM ".$core->modules['statsref']->dbname." WHERE data='".$previousDay."'",$r,$n)){
					for ($c=0;$c<$n;$c++) {
						list($ref,$ep,$hits) = $core->dbo->fetch_row($r);
						$core->dbo->simpleQuery("INSERT INTO ".$core->modules['statsrefdaily']->dbname." SET data='$previousDay', referer=\"$ref\", hits=$hits, entrypage=\"$ep\"");
					}
				}
			}
		}
		# done
	}

}