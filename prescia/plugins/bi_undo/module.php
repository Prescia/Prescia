<?	# -------------------------------- bi_undo plugin

if (CONS_DB_HOST=='') $this->errorControl->raise(4,'bi_undo','UNDO module requires database');

class mod_bi_undo extends CscriptedModule  {


	var $name = "bi_undo";
	var $internalMemory = array(); // will prefetch data to store on the first notify pass, and actually store on second (which means it was sucessful)

	function __construct(&$parent,$moduleRelation="") {
		$this->parent = &$parent; // framework object
		$this->moduleRelation = $moduleRelation;
		$this->loadSettings();
	}

	function loadSettings() {
		#$this->name = "";
		$this->parent->onMeta[] = $this->name;
		#$this->parent->onActionCheck[] = $this->name;
		#$this->parent->onRender[] = $this->name;
		#$this->parent->on404[] = $this->name;
		#$this->parent->onShow[] = $this->name;
		#$this->parent->onEcho[] = $this->name;
		$this->parent->onCron[] = $this->name;
		#$this->customFields = array();

	}

	function onMeta() {
		# Run this function during meta-load (debugmode >>ONLY<<)
		###### -> Construct should add this module to the onMeta array
		if (!is_dir(CONS_FMANAGER."_undodata/"))
			safe_mkdir(CONS_FMANAGER."_undodata/");
	}


	function onCron($isDay=false) { # cron Triggered, isDay or isHour
		###### -> Construct should add this module to the onCron array
		if ($isDay) {
			// delete week old entries (select data, detect files, delete files, delete data)
			$core = &$this->parent;
			$undoModule = $this->parent->loaded($this->moduleRelation);
			$sql = "SELECT id, files FROM ".$undoModule->dbname." WHERE files<>'' AND data<NOW() - INTERVAL 1 WEEK";
			$core->dbo->query($sql,$r,$n);
			if ($n>0) {
				for ($c=0;$c<$n;$c++) {
					list($id,$files) = $core->dbo->fetch_row($r);
					$files = @unserialize($files);
					if ($files) {
						foreach ($files as $file) {
							if (is_file(CONS_FMANAGER."_undodata/".$file))
							@unlink(CONS_FMANAGER."_undodata/".$file);
						}
					}
				}
			}
			$core->dbo->simpleQuery("DELETE FROM ".$undoModule->dbname." WHERE data<NOW() - INTERVAL 1 WEEK");
			# Find orphan files and delete them
			$lastWeek = date("Y-m-d")." 00:00:00";
			$lastWeek = datecalc($lastWeek,0,0,-7);
			$lastWeek = tomktime($lastWeek);
			$files = listFiles(CONS_FMANAGER."_undodata/",'@^(.*)$@',true);
			foreach ($files as $file) {
				$ft = filemtime(CONS_FMANAGER."_undodata/".$file);
				if ($ft<$lastWeek) @unlink(CONS_FMANAGER."_undodata/".$file);
				else break; // the list is ordered by date, so the first not to match means none will

			}

		}

	}

	function undo($id,$record=false) {

		$core = &$this->parent;
		$undo = $core->loaded('bi_undo');
		if (!$record) {
			$sql = $undo->get_base_sql($undo->name.".id=".$id);
			$core->dbo->query($sql,$record,$n);
			if ($n == 0) {
				$core->fastClose(404);
				return false;
			}
		}
		// get data from undo record
		$undodados = $core->dbo->fetch_assoc($record);
		$_REQUEST['module'] = $undodados['modulo'];
		$module = $core->loaded($undodados['modulo']);

		$realdados = array();
		$canUndo = true;
		$history = unserialize($undodados['history']);
		foreach ($module->fields as $fname => $fields) {
			// fill up $_REQUEST to simulate and edit/add
			if (isset($history[$fname])) {
				$realdados[$fname] = $history[$fname];
				# if this is a link, check if remote module exists. If this is mandatory, abort
				if ($fields[CONS_XML_TIPO] == CONS_TIPO_LINK && $history[$fname]!=0) {
					$remoteOk = true;
					$remoteModule = $core->loaded($fields[CONS_XML_MODULE]);
					$sql = $remoteModule->get_base_sql();
					$sql['SELECT'] = array("count(*)"); # just want to check if it exists
					foreach ($remoteModule->keys as $key) {
						if ($key == $remoteModule->keys[0])
							$sql['WHERE'][] = $key."=\"".$history[$fname]."\"";
						else if (isset($undodados[$key]))
							$sql['WHERE'][] = $key."=\"".$history[$key]."\"";
						else {
							$remoteOk =false;
							break;
						}
					}
					$n = $core->dbo->fetch($sql);
					if ($n == 0) $remoteOk = false;
					if (!$remoteOk) {
						// field does not exist, what now?
						$realdados[$fname] = 0;
						$core->log[] = $core->langOut('undoerror_remotefail').": ".$remoteModule->name; // warn it was set to 0
						$this->parent->setLog(CONS_LOGGING_WARNING);
						if (isset($fields[CONS_XML_MANDATORY])) {
							// actually, cannot be 0 ... abort
							$canUndo = false;
							$core->log[] = $core->langOut('undoerror_remotemandatoryfail');
							$this->parent->setLog(CONS_LOGGING_ERROR);
							break;
						}
					}
				}
			} else if (isset($fields[CONS_XML_MANDATORY])) {
				// mandatory field is missing
				$core->log[] = $core->langOut('undoerror_mandatoryfail').": ".$fname;
				$this->parent->setLog(CONS_LOGGING_ERROR);
				$canUndo = false;
			}
		}
		if ($canUndo) {
			// ok, we can undo
			// any files to undo this?
			if ($undodados['files'] != "") {
				$files = unserialize($undodados['files']);
				if ($files !== false) {
					foreach ($files as $file => $exists) {
						if ($exists && is_file(CONS_FMANAGER."_undodata/".$file)) {
							//$module->name.$fname."_".$keys.".".$ext;
							if (preg_match("/^".$module->name."(.*)_".$undodados['ids']."\."."([^.]+)\$/i",$file,$regs)) {
								$campo = $regs[1];
								if (isset($module->fields[$campo])) {
									rename(CONS_FMANAGER."_undodata/".$file,CONS_FMANAGER."_undodata/".$file.".wrk"); # action will delete
									// use a virtual upload on edit/add simulation
									$_FILES[$campo] = array('virtual' => true,
																'error' => 0,
																'tmp_name' => CONS_FMANAGER."_undodata/".$file.".wrk",
																'name' => $file
									);
								}
							}
						}
					}
				}
			}
			$ok = $core->runAction($module,$undodados['event']=='update'?CONS_ACTION_UPDATE:CONS_ACTION_INCLUDE,$realdados,'bi_undo');
			if ($ok) {
				$core->log[] = $core->langOut("undo_sucessfull");
				$this->parent->setLog(CONS_LOGGING_SUCCESS);
				$sql = "DELETE FROM ".$undo->dbname." WHERE id=".$_REQUEST['id']; # undoed ... so we don't need this log
				$core->dbo->simpleQuery($sql);
				if ($undodados['event'] == 'delete') {
					$newKey = $core->lastReturnCode;
					if ($newKey != $realdados[$module->keys[0]]) {
						# try recover main key to original state
						$sql = "UPDATE ".$module->dbname." SET ".$module->keys[0]."=".$realdados[$module->keys[0]]." WHERE ".$module->keys[0]."=$newKey";
						$ok = $core->dbo->simpleQuery($sql);
						if (!$ok) {
							$core->log[] = $core->langOut('undo_unable_to_keep_key');
							$this->parent->setLog(CONS_LOGGING_WARNING);
							$realdados[$module->keys[0]] = $newKey;
						//} else {
							#TODO: uploaded files are stored with the wrong key ... yeah ... must rename them all, GOOD LUCK -_-
						}
					}
				}
				# foward to edit pane
				$_REQUEST = array('module' => $module->name);
				foreach ($module->keys as $keyName) {
					$_REQUEST[$keyName] = $realdados[$keyName];
				}
				return true;
			} else {
				$core->log[] = $core->langOut("undo_failed");
				$this->parent->setLog(CONS_LOGGING_ERROR);
				return false;
			}
		} else
			return false;
	}

	function notifyEvent(&$module,$action,$data,$startedAt="",$earlyNotify=false) {
		# notify followup for this field (happens before standard notify)

		if ($module === false || $module->options[CONS_MODULE_SYSTEM] || isset($module->options[CONS_MODULE_NOUNDO])) return;
		$ws = "";$ka = array();
		if ($action != CONS_ACTION_INCLUDE) {
			if ($earlyNotify) {
				// saves INTENTION of performing an action. If it FAILS, we don't need to store UNDO data.
				if (isset($this->internalMemory[$module->name])) $this->internalMemory[$module->name] = array();
				$module->getKeys($ws,$ka,$data);
				$sql = "SELECT * FROM ".$module->dbname." WHERE $ws";
				$ok =$this->parent->dbo->query($sql,$r,$n);
				if ($ok && $n>0) {
					$data = $this->parent->dbo->fetch_assoc($r);
					$files = array();
					// saves files ... this will be saved even if a DELETE fails, but we can't wait as the data above since later it will be deleted
					// move files (only mains, no thumbs)
					foreach ($ka as $value)
						$keys = $value."_"; // keys (searchable)
					$keys = substr($keys,0,strlen($keys)-1); // remove last _
					foreach ($module->fields as $fname => $field) {
						if ($field[CONS_XML_TIPO] == CONS_TIPO_UPLOAD) {
							$arquivo = CONS_FMANAGER.$module->name."/".$fname."_".$keys."_1";
							if (locateanyfile($arquivo,$ext)) {
								$dest = CONS_FMANAGER."_undodata/".$module->name.$fname."_".$keys.".".$ext;
								if (is_file($dest)) @unlink($dest);
								$ok = copy($arquivo,$dest);
								if ($ok)
								$files[$module->name.$fname."_".$keys.".".$ext] = true;
							}
						}
					}
					$data['___FILES___'] = $files;
					$this->internalMemory[$module->name][] = array($action,$data);
				}
			} else {
				// checks for the stored data from BEFORE the action (not the case in INCLUDE), since it has been confirmed changed
				// note that FILES have already been backed up
				if (isset($this->internalMemory[$module->name])) { // so for each stored action on this module
					foreach ($this->internalMemory[$module->name] as $iMi) {
						if ($iMi[0] == $action) { // that is the same action
							// check if it's the same keys
							foreach ($module->keys as $key) {
								if ($data[$key] != $iMi[1][$key]) continue 2; // not this item, next item please ...
							}
							// if we got here, the keys were compared sucessfuly. Save
							$undoModule = $this->parent->loaded($this->moduleRelation);
							$module->getKeys($ws,$ka,$data);
							foreach ($ka as $value)
								$keys = $value."_"; // keys (searchable)
							$keys = substr($keys,0,strlen($keys)-1); // remove last _
							$files = $iMi[1]['___FILES___'];
							$sql = "INSERT INTO ".$undoModule->dbname." SET
									modulo='".$module->name."',
									event='".($action==CONS_ACTION_DELETE?'delete':'update')."',
									ids='$keys',
									history=\"".addslashes_EX(serialize($iMi[1]))."\",
									files=\"".addslashes_EX(serialize($files))."\",
									data=NOW(),
									id_author = '".($this->parent->logged()?$_SESSION[CONS_SESSION_ACCESS_USER]['id']:0)."'";

							$ok = $this->parent->dbo->simpleQuery($sql);
							break;
						}
					}
				}
			}
		}
	}

}