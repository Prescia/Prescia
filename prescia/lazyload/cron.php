<?php
/* -------------------------------- Prescia Cron
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
  | NOTE: forcecron=day|hour|true only works if you are logged as master
-*/

/** @var CPrescia $this Runtime cron context injected by the framework. */
/** @var bool|string $forceCron Cron mode supplied by CPrescia::cronCheck(). */

require_once CONS_PATH_INCLUDE."autoClean.php";

if ($forceCron=='day' || $forceCron=='all' || (date("d") != $this->dimconfig['_cronD'] && $forceCron != 'hour')) { // Daily cron

	if ($this->debugmode) $this->errorControl->raise(1002,$forceCron,"CRON");
	$this->loadAllmodules();

	$isMasterDomain = CONS_MASTERDOMAINS == "" || strpos(CONS_MASTERDOMAINS,$_SESSION['DOMAIN'])!==false || !CONS_ONSERVER;

	// delete throttle datfile (it grows insanelly, but deleting hourly would defeat it's core purpose)
	if ($isMasterDomain) {
		// if honeypot, reset user agent bans
		if (CONS_HONEYPOT) {
			@unlink(CONS_PATH_TEMP."honeypot.dat");
			$_SESSION[CONS_SESSION_HONEYPOTLIST] = array();
		}
		// if botprotect, reset bans
		if (CONS_BOTPROTECT) {
			foreach(glob(CONS_PATH_TEMP."*.dat") as $file) {
				if(!is_dir($file))
					@unlink($file);
			}
		}
	}

	// reset 404 caches
	$this->dimconfig['_404cache'] = array();

	// backup main files
	$this->loadDimconfig(true);
	if ($this->dimconfig !== false) {
		$oFile = CONS_PATH_DINCONFIG.$_SESSION['CODE']."/din.bck";
		cWriteFile($oFile,serialize($this->dimconfig),false,true);
	}

	# delete performance log (it should only keep latest files anyway)
	if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/pm.log")) @unlink(CONS_PATH_LOGS.$_SESSION['CODE']."/pm.log");

	# Check absurd amount of errors?
	if (CONS_HTTPD_ERRFILE != '') {
		$httpderrlog = str_replace("{Y}",date("Y"),CONS_HTTPD_ERRFILE);
		$httpderrlog = str_replace("{m}",date("m"),$httpderrlog);
		$httpderrlog = str_replace("{d}",date("d"),$httpderrlog);
		if (is_file(CONS_HTTPD_ERRDIR.$httpderrlog) && filesize(CONS_HTTPD_ERRDIR.$httpderrlog)>1048576) {
			# php log has more than 1Mb, come on!
			$this->errorControl->raise(604,"size=".filesize(CONS_HTTPD_ERRDIR.$httpderrlog),"PHP error log too big");
		}
	} else {
		$httpderrlog = "";
	}
	if ($this->dimconfig['_errcontrol'] > 100) {
		# system reports more than 100 errors!
		$this->errorControl->raise(605,"errors=".($httpderrlog != "" && is_file(CONS_HTTPD_ERRDIR.$httpderrlog)?filesize(CONS_HTTPD_ERRDIR.$httpderrlog):$this->dimconfig['_errcontrol']),"Too many system errors");
	}

	// quota ok?
	$quota = isset($this->dimconfig['quota'])?$this->dimconfig['quota']:CONS_MAX_QUOTA;
	$this->dimconfig['_usedquota'] = quota(CONS_FMANAGER,true)*1024;
	if ($this->dimconfig['_usedquota'] > $quota) {
		$this->errorControl->raise(110);
		if (isset($this->dimconfig['adminmail']) && isMail($this->dimconfig['adminmail']))
			@mail($this->dimconfig['adminmail'],"QUOTA EXCEEDED @ ".$_SESSION['CODE'],"Quota exceeded: ".$this->dimconfig['_usedquota']." from $quota");
	}
	// auto clean
	foreach ($this->modules as $name => &$module) {
		if (isset($module->options[CONS_MODULE_AUTOCLEAN]) && $module->options[CONS_MODULE_AUTOCLEAN] != "" && (strpos($module->options[CONS_MODULE_AUTOCLEAN],"DAY") !== false || strpos($module->options[CONS_MODULE_AUTOCLEAN],"WEEK") !== false || strpos($module->options[CONS_MODULE_AUTOCLEAN],"MONTH") !== false || strpos($module->options[CONS_MODULE_AUTOCLEAN],"YEAR") !== false)) {

				# daily only runs autocleans with DAY, WEEK, MONTH or YEAR
				try {
					$autoclean = CPresciaAutoClean::compile($module->options[CONS_MODULE_AUTOCLEAN],$module,$this->dbo);
					$table = $this->dbo->quoteIdentifier($module->dbname);
				} catch (InvalidArgumentException $exception) {
					$this->log[] = "Rejected invalid autoclean for module ".$name;
					continue;
				}
				$r = false;
				$n = 0;
				if ($module->options[CONS_MODULE_VOLATILE]) {
					$sql = "DELETE FROM ".$table." WHERE ".$autoclean['sql'];
					$this->dbo->queryPrepared($sql,$autoclean['types'],$autoclean['params'],$r,$n);
				} else {
					$sql = "SELECT * FROM ".$table." WHERE ".$autoclean['sql'];
					if ($this->dbo->queryPrepared($sql,$autoclean['types'],$autoclean['params'],$r,$n)) {
					$this->safety = false;
					$c = 0;
					while (is_array($data = $this->dbo->fetch_assoc($r))) {
						$this->runAction($module,CONS_ACTION_DELETE,$data,true);
						if ($c%10 == 0 && $this->nearTimeLimit()) {
							$this->errorControl->raise(111,'cleanup-stage');
							$this->safety = true; # aborts cron as of now
							return;
						}
						$c++;
					}
					$this->safety = true;
				}
			}
		}
	}

	if (!$this->nearTimeLimit()) {
		# cleans old (90days) logs
		$arquivos = listFiles(CONS_PATH_LOGS.$_SESSION['CODE']."/","/^([^\.]+)\.log$/");
		$hoje = date("Y-m-d");
		$mes = (int)str_replace("-","",datecalc($hoje,0,-3)); # 3 monthes
		foreach ($arquivos as $x => $arquivo) {
			if (substr($arquivo,0,3) == "err" || substr($arquivo,0,3) == "out" || substr($arquivo,0,3) == "act" || substr($arquivo,0,3) == "sec") {
			$dt = (int)substr($arquivo,3,8); // err, out, act, sec
			if ($dt<$mes) @unlink(CONS_PATH_LOGS.$_SESSION['CODE']."/".$arquivo);
		} else {
		  	$dt = (int)substr($arquivo,4,8); // warn
		    	if ($dt<$mes) @unlink(CONS_PATH_LOGS.$_SESSION['CODE']."/".$arquivo);
		  	}
		}

	}

	if (!$this->nearTimeLimit()) {
		# clean up object cache
		$allok = recursive_del(CONS_PATH_CACHE.$_SESSION['CODE']."/",false,'cache',500);
		if (!$allok) $this->dimconfig['_recurseDelOverflow'] = 1;
		else $this->dimconfig['_recurseDelOverflow'] = 0;
	}

	$this->dimconfig['_cronD'] = date("d");
	$this->dimconfig['_errcontrol'] = 0;
	
	// cron notifies
	foreach ($this->onCron as $scriptName) {
		$this->loadedPlugins[$scriptName]->onCron(true);
		if ($this->nearTimeLimit()) {
			$this->errorControl->raise(111,'onCron-stage');
			$this->safety = true; # aborts cron as of now
			return;
		}
	}
	if ($forceCron != 'all') return; // Hourly cron will run on another hit
}

if ($forceCron=='hour' || $forceCron=='all' || $this->dimconfig['_cronH'] != date("H")) {
	// Hourly cron

	# autoclean and notifies
	$this->loadAllmodules();
	foreach ($this->modules as $name => &$module) {
		if (isset($module->options[CONS_MODULE_AUTOCLEAN]) && $module->options[CONS_MODULE_AUTOCLEAN] != "" && (strpos($module->options[CONS_MODULE_AUTOCLEAN],"HOUR") !== false || strpos($module->options[CONS_MODULE_AUTOCLEAN],"MINUTE") !== false)) {

			# hourly only runs autocleans with HOUR or MINUTE
			try {
				$autoclean = CPresciaAutoClean::compile($module->options[CONS_MODULE_AUTOCLEAN],$module,$this->dbo);
				$table = $this->dbo->quoteIdentifier($module->dbname);
				} catch (InvalidArgumentException $exception) {
					$this->log[] = "Rejected invalid autoclean for module ".$name;
					continue;
				}
				$r = false;
				$n = 0;
				if ($module->options[CONS_MODULE_VOLATILE]) {
					$sql = "DELETE FROM ".$table." WHERE ".$autoclean['sql'];
					$this->dbo->queryPrepared($sql,$autoclean['types'],$autoclean['params'],$r,$n);
				} else {
					$sql = "SELECT * FROM ".$table." WHERE ".$autoclean['sql'];
					if ($this->dbo->queryPrepared($sql,$autoclean['types'],$autoclean['params'],$r,$n)) {
					$this->safety = false;
					$c = 0;
					while (is_array($data = $this->dbo->fetch_assoc($r))) {
						$this->runAction($module,CONS_ACTION_DELETE,$data,true);
						if ($c%10 == 0 && $this->nearTimeLimit()) {
							$this->errorControl->raise(112,'cleanup-stage');
							$this->safety = true; # aborts cron as of now
							return;
						}
						$c++;
					}
					$this->safety = true;
				}
			}
		}
	}
	// cron notifies
	foreach ($this->onCron as $scriptName) {
		$this->loadedPlugins[$scriptName]->onCron(false);
		if ($this->nearTimeLimit()) {
			$this->errorControl->raise(112,'onCron-stage');
			$this->safety = true; # aborts cron as of now
			return;
		}
	}

	if (strpos(CONS_MASTERDOMAINS,$_SESSION['DOMAIN'])!==false) {
		$this->cacheControl->logCacheThrottle();
	}
	
	if (!$this->nearTimeLimit() && isset($this->dimconfig['_recurseDelOverflow']) && $this->dimconfig['_recurseDelOverflow'] == 1) {
		# clean up object cache, unable to complete on daily clean up
		$allok = recursive_del(CONS_PATH_CACHE.$_SESSION['CODE']."/",false,'cache',250);
		if (!$allok) $this->dimconfig['_recurseDelOverflow'] = 1;
		else $this->dimconfig['_recurseDelOverflow'] = 0;
	}

	if (CONS_CRONDBBACKUP && !$this->nearTimeLimit() && ($this->dimconfig['_scheduledCronDay'] == 0 || date("d") == $this->dimconfig['_scheduledCronDay']) && $this->dimconfig['_scheduledCronDayHour'] == date("H")) {


		// special optimization and backup
		$this->errorControl->raise(113);
		$mods = array();
		foreach ($this->modules as $name => $module) {
			if ($module->dbname != "" && !in_array($module->dbname,$mods))
				$mods[] = $module->dbname;
		}
		$quotedMods = array();
		foreach ($mods as $mod) $quotedMods[] = $this->dbo->quoteIdentifier($mod);
		// optimize
		$sql = "REPAIR TABLE ".implode(",",$quotedMods);
		$maintenanceResult = false;
		$maintenanceRows = 0;
		$this->dbo->queryPrepared($sql,"",array(),$maintenanceResult,$maintenanceRows,false);
		$sql = "OPTIMIZE TABLE ".implode(",",$quotedMods);
		$maintenanceResult = false;
		$maintenanceRows = 0;
		$this->dbo->queryPrepared($sql,"",array(),$maintenanceResult,$maintenanceRows,false);
		// backup
		if (!$this->nearTimeLimit()) {
			$mods = array();
			foreach ($this->modules as $name => $module) {
				if (!isset($module->options['backup']) || $module->options['backup'] != 'no') {
					$module->generateBackup();
					if ($this->nearTimeLimit()) break;
				}
			}
		}

		if (CONS_CRONDBBACKUP_MAIL != '' && !$this->nearTimeLimit()) {
			$bfile = CONS_PATH_BACKUP.$_SESSION['CODE']."/backup.zip";
			if (is_file($bfile)) @unlink($bfile);
			$files = listFiles(CONS_PATH_BACKUP.$_SESSION['CODE']."/",'/.*\.sql/');
			if (count($files) == 0) return;
			$zip = new ZipArchive();
			$zip->open($bfile, ZipArchive::CREATE);
			foreach ($files as $file) {
				$zip->addFile(CONS_PATH_BACKUP.$_SESSION['CODE']."/".$file,$file);
			}
			$zip->close();
			unset($zip);
			$mail = "BACKUP PERFORMED AT ".date("Y-m-d H:i:s");
			$tmail = new CKTemplate();
			$tmail->tbreak($mail);
			sendmail(CONS_CRONDBBACKUP_MAIL,"backup ".$_SESSION['CODE'],$tmail,CONS_MASTERMAIL,'',true,$bfile);
		}
	}

	$this->dimconfig['_cronH'] = date("H");
}
