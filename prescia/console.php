<?/* -------------------------------- Prescia Console
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
-*/

	# delete [key]				Will delete a dimconfig key
	# dev [on|off]				Will change config.php to either enable or disable bi_dev. Note it will add the plugin at the end of the file when enabling
	# test						Will run an bi_dev fulltest and return true or false for errors
	# dump [dimconfig|session|constants]
	#							Will dump the selected contents to screen, constants are the template constants, not core constants
	# ip						Shows ip data
	# dbfill
	# set [variable] [value]
	# cache						Returns current cacheThrottle log (168 entries!) (SYSTEM)
	# purge [log|cache|bans|all]
	#							Completelly clears the logs, caches, IP log/bans or all (SYSTEM)
	# phpinfo					Quite obvious

	# CUSTOM: if there is a _console file in the _config/ folder, it will be called if no command is found

	function console($core,$command) {

		if (defined('CONS_AUTH_USERMODULE') && $_SESSION[CONS_SESSION_ACCESS_LEVEL]<100) {
			echo 'access denied';
			$core->close();
		}

		$words = explode(" ",trim($command));
		if ($words[0] == "help" || $words[0] == "?") {
			echo "clear - clears the console screen<br/>"; // implemented on the HTML/js
			echo "delete [key] - deletes a key off dimconfig<br/>";
			echo "dev [on|off] - enable/disable developer assistent plugin (affbi_dev)<br/>";
			echo "test - returns a bi_dev fulltest<br/>";
			echo "dump [dimconfig|session|constants|config] - displays the contents of the dimconfig, session or constant variables<br/>";
			//echo "compileaff - compiles aff distribution into new/ folder<br/>";
			echo "dbfill - adds up to 10 random items on EVERY database of the site<br/>";
			echo "set [variable] [value] - sets a dimconfig variable<br/>";
			echo "cache - displays the full cacheThrottle log, as well current values<br/>";
			echo "purge [log|cache|bans|all] - purches all server-side log, cache, ip bans or all these options<br/>";
			echo "ip - Shows local/server IP's";
			$core->close();
		}

		if ($words[0] == "set" && isset($words[1]) && isset($words[2])) {
			$core->dimconfig[$words[1]] = $words[2];
			echo $words[1]." set to '".$words[2]."'";
			$core->saveConfig(true);
			$core->close();
		}

		if ($words[0] == "ip") {
			echo "SERVER IP: ".GetIP(false)."<br/>";
			echo "ON SERVER: ".(CONS_ONSERVER?"true":"false")."<br/>";
			echo "REMOTE IP: ".CONS_IP;
			$core->close();
		}

		if ($words[0] == "delete") {
			if (isset($core->dimconfig[$words[1]])) {
				unset($core->dimconfig[$words[1]]);
				$core->saveConfig(true);
				echo "dimconfig keyword deleted";
			} else
				echo "dimconfig keyword not found";
			$core->close();
		}

		if ($words[0] == "dev") {
			if ($words[1] == "on" || $words[1] == '1') {
				if (isset($core->loadedPlugins['bi_dev'])) {
					echo "dev already on";
					$core->close();
				} else {
					$filenm = CONS_PATH_PAGES.$_SESSION['CODE']."/_config/config.php";
					$file = cReadFile($filenm);
					cWriteFile($filenm.".bak",$file);
					$file .= "\n\$dev = \$this->addPlugin('bi_dev');\n\$dev->administrativePage = \"/adm/\";";
					cWriteFile($filenm,$file);
					echo "dev added to config.php";
					$core->close();
				}
			} else if (!isset($core->loadedPlugins['bi_dev'])) {
				echo "dev already off";
				$core->close();
			} else {
				$filenm = CONS_PATH_PAGES.$_SESSION['CODE']."/_config/config.php";
				$file = cReadFile($filenm);
				cWriteFile($filenm.".bak",$file);
				$file = str_replace("\$dev = \$this->addPlugin('bi_dev');","",$file);
				$file = str_replace("\$dev->administrativePage = \"/adm/\";","",$file);
				cWriteFile($filenm,$file);
				echo "dev removed from config.php";
				$core->close();
			}
		}

		if ($words[0] == "test") {
			if (isset($core->loadedPlugins['bi_dev'])) {
				$ok = $core->loadedPlugins['bi_dev']->fulltest(true);
				echo "DEV-Fulltest: ".($ok?"ERRORS!":"OK!");
			} else
				echo "dev is off";
			$core->close();
		}

		if ($words[0] == "dump") {
			$out = "";

			if ($words[1] == "dimconfig") {
				foreach ($core->dimconfig as $name => $content) {
					$out .= $name . " : ".vardump($content)."<br/>";
				}
				echo $out;
				$core->close();
			} else if ($words[1] == "session") {
				foreach ($_SESSION as $name => $content) {
					$out .= $name . " : ".(is_array($content)?implode(", ",$content):$content)."<br/>";
				}
				echo $out;
				$core->close();
			} else if ($words[1] == "constants") {
				foreach ($core->template->constants as $name => $content) {
					$out .= $name . " : ".(is_array($content)?implode(", ",$content):$content)."<br/>";
				}
				echo $out;
				$core->close();
			} else if ($words[1] == "config" ) {
				echo "CONS_AFF_DATABASECONNECTOR: ".CONS_AFF_DATABASECONNECTOR."<br/>";
				echo "CONS_AFF_ERRORHANDLER: ".(CONS_AFF_ERRORHANDLER?"true":"false")."<br/>";
				echo "CONS_AFF_ERRORHANDLER_NOWARNING: ".(CONS_AFF_ERRORHANDLER_NOWARNING?"true":"false")."<br/>";
				echo "CONS_AJAXRUNSSCRIPTS: ".(CONS_AJAXRUNSSCRIPTS?"true":"false")."<br/>";
				echo "CONS_SINGLEDOMAIN: ".CONS_SINGLEDOMAIN."<br/>";
				echo "CONS_DEFAULT_IPP: ".CONS_DEFAULT_IPP."<br/>";
				echo "CONS_FLATTENURL: ".CONS_FLATTENURL."<br/>";
				echo "CONS_AUTOREMOVEWWW: ".CONS_AUTOREMOVEWWW."<br/>";
				echo "CONS_DEFAULT_MIN_OBJECTCACHETIME: ".CONS_DEFAULT_MIN_OBJECTCACHETIME."<br/>";
				echo "CONS_DEFAULT_MAX_OBJECTCACHETIME: ".CONS_DEFAULT_MAX_OBJECTCACHETIME."<br/>";
				echo "CONS_DEFAULT_MIN_BROWSERCACHETIME: ".CONS_DEFAULT_MIN_BROWSERCACHETIME."<br/>";
				echo "CONS_DEFAULT_MAX_BROWSERCACHETIME: ".CONS_DEFAULT_MAX_BROWSERCACHETIME."<br/>";
				echo "CONS_PM_MINTIME: ".CONS_PM_MINTIME."<br/>";
				echo "CONS_PM_TIME: ".CONS_PM_TIME."<br/>";
				echo "CONS_FREECPU: ".(CONS_FREECPU?"true":"false")."<br/>";
				echo "CONS_MONITORMAILSOURCE: ".CONS_MONITORMAILSOURCE."<br/>";
				echo "CONS_MONITORMAIL: ".CONS_MONITORMAIL."<br/>";
				echo "CONS_HTTPD_ERRDIR: ".CONS_HTTPD_ERRDIR."<br/>";
				echo "CONS_HTTPD_ERRFILE: ".CONS_HTTPD_ERRFILE."<br/>";
				echo "CONS_MASTERMAIL: ".CONS_MASTERMAIL."<br/>";
				echo "CONS_ACCEPT_DIRECTLINK: ".(CONS_ACCEPT_DIRECTLINK?"true":"false")."<br/>";
				echo "CONS_SITESELECTOR: ".(CONS_SITESELECTOR?"true":"false")."<br/>";
				echo "CONS_NOROBOTDOMAINS: ".CONS_NOROBOTDOMAINS."<br/>";
				echo "CONS_FILESEARCH_EXTENSIONS: ".CONS_FILESEARCH_EXTENSIONS."<br/>";
				echo "CONS_TOOLS_DEFAULTPERM: ".CONS_TOOLS_DEFAULTPERM."<br/>";
				echo "CONS_GZIP_MINSIZE: ".CONS_GZIP_MINSIZE."<br/>";
				echo "CONS_CRAWLER_WHITELIST_ENABLE: ".(CONS_CRAWLER_WHITELIST_ENABLE?"true":"false")."<br/>";
				echo "CONS_CRAWLER_WHITELIST: ".CONS_GZIP_MINSIZE."<br/>";
				echo "CONS_HONEYPOT: ".(CONS_HONEYPOT?"true":"false")."<br/>";
				echo "CONS_HONEYPOTURL: ".CONS_GZIP_MINSIZE."<br/>";
				echo "------ site config (".$_SESSION['CODE'].") ------<br/>";
				echo "CONS_USE_I18N: ".(CONS_USE_I18N?"true":"false")."<br/>";
				echo "CONS_DEFAULT_LANG: ".CONS_DEFAULT_LANG."<br/>";
				echo "CONS_DEFAULT_FAVICON: ".(CONS_DEFAULT_FAVICON?"true":"false")."<br/>";
				echo "CONS_INSTALL_ROOT: ".CONS_INSTALL_ROOT."<br/>";
				echo "CONS_DB_HOST: ".CONS_DB_HOST."<br/>";
				echo "CONS_DB_BASE: ".CONS_DB_BASE."<br/>";
				echo "CONS_SITE_ENTRYPOINT: ".CONS_SITE_ENTRYPOINT."<br/>";
				echo "languagetl: ".vardump($core->languageTL)."<br/>";
				echo "forceLang: ".$core->forceLang."<br/>";
				echo "------ modules loaded ----------<br/>";
				foreach ($core->modules as $mname => &$m) {
					echo "$mname<br/>";
				}
				$core->close();
			}
			echo "add 'dimconfig', 'session', 'constants', 'config'<br/>";
		}

		if ($words[0] == "dbfill") {
			if (isset($core->loadedPlugins['bi_dev'])) {
				$ok = $core->loadedPlugins['bi_dev']->fill();
				echo "DEV-Fill: ".($ok==false?"ERROR!":"Ok, $ok items included");
			} else
				echo "dev is off, turn dev on to use dbfill";
			$core->close();
		}

		if ($words[0] == 'cache') {
			if (is_file(CONS_PATH_LOGS."cachecontrol.dat")) {
				$cc = unserialize(cReadFile(CONS_PATH_LOGS."cachecontrol.dat"));
				if ($cc !== false) {
					echo "Date, Page average loadtime, Cache throttle %\n<br/>";
					foreach ($cc as $ccitem) {
						echo $ccitem[0].", ".number_format($ccitem[1])."ms, ".floor(100*$ccitem[2])."%\n<br/>";
					}
					$cc = unserialize(cReadFile(CONS_PATH_CACHE."cachecontrol.dat"));
					if ($cc !== false)
						echo "CURRENT: ".number_format($cc[0])."ms, ".floor(100*$cc[1])."%";
					else
						echo "CURRENT: unable to load cachecontrol.dat in cache";
				} else
					echo "cachecontrol.dat corrupt";
			} else
				echo "cachecontrol.dat not found in logs";
			$core->close();
		}

		if ($words[0] == "purge") {
			$purgeThis = array(!isset($words[1]) || $words[1] == "log" || $words[1] == "all" ,
								!isset($words[1]) || $words[1] == "cache" || $words[1] == "all",
								!isset($words[1]) || $words[1] == "bans" || $words[1] == "all");

			if ($purgeThis[1])
				$core->cacheControl->dumpTemplateCaches($purgeThis[0],true);
			else if ($purgeThis[0]) {
				$listFiles = listFiles(CONS_PATH_LOGS,"/^([^a]).*(\.log)$/i",false,false,true);
				foreach ($listFiles as $file)
					@unlink(CONS_PATH_LOGS.$file);
			}
			if ($purgeThis[2]) {
				foreach(glob(CONS_PATH_TEMP."*.dat") as $file) {
					if(!is_dir($file))
						@unlink($file);
				}
			}
			echo "Ok! (flags=".($purgeThis[0]?"L":"l").($purgeThis[1]?"C":"c").($purgeThis[2]?"B":"b").")";
			$core->close();
		}

		if ($words[0] == "phpinfo") {
			phpinfo();
			$core->close();
		}

		if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/_console.php"))
			include(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/_console.php");

		echo "command not understood";
		$core->close();
	}
