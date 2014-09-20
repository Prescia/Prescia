<?	# -------------------------------- Plugin GROUPS (automatically added by plugin AUTH)

define ("CONS_AUTH_GROUPMODULE","groups");
if (CONS_DB_HOST=='') $this->errorControl->raise(4,'bi_groups','GROUPS module requires database');

class mod_bi_groups extends CscriptedModule  {

	function loadSettings() {
		$this->name = "bi_groups";
		#$this->parent->onMeta[] = $this->name;
		#$this->parent->onActionCheck[] = $this->name;
		#$this->parent->onRender[] = $this->name;
		#$this->parent->on404[] = $this->name;
		#$this->parent->onShow[] = $this->name;
		#$this->parent->onEcho[] = $this->name;
		#$this->parent->onCron[] = $this->name;
		#$this->customFields = array();
	}

	function edit_parse($action,&$data) {
		$baseModule = $this->parent->loaded($this->moduleRelation);
		if ($action != CONS_ACTION_DELETE) {
			# you cannot ADD or CHANGE a group with higher level than you
			if ($this->parent->safety && $_SESSION[CONS_SESSION_ACCESS_LEVEL] < 100) {
				if (isset($data['level']) && $data['level'] >= $_SESSION[CONS_SESSION_ACCESS_LEVEL]) {
					$this->parent->log[] = $this->parent->langOut("group_cannot_change_higher");
					$this->parent->setLog(CONS_LOGGING_WARNING);
					return false;
				} else if (isset($data['level']) && $data['level'] >= 100) { # you cannot add a level 100+ group (only manually)
					$this->parent->log[] = $this->parent->langOut("group_top_level_is_99");
					$this->parent->setLog(CONS_LOGGING_WARNING);
					if ($action == CONS_ACTION_INCLUDE) $data['level'] = 99;
					else unset($data['level']);
				}
			} else if ($action == CONS_ACTION_UPDATE) {
				$lvl = $this->parent->dbo->fetch("SELECT level FROM ".$baseModule->dbname." WHERE id=".$data['id']);
				if ($lvl == 100 && $_SESSION[CONS_SESSION_ACCESS_LEVEL] < 100)  {
					$this->parent->log[] = $this->parent->langOut("cannot_change_master_group");
					$this->parent->setLog(CONS_LOGGING_WARNING);
					return false;
				}
				if ($data['id'] == $this->parent->dimconfig['guest_group'] && isset($data['level']) && $data['level'] > 0) {
					$this->parent->log[] = $this->parent->langOut("cannot_change_guest_level");
					$this->parent->setLog(CONS_LOGGING_WARNING);
					$data['level'] = 0;
				}
			}
			if (isset($data['level']) && $data['level'] >= 100 && $_SESSION[CONS_SESSION_ACCESS_LEVEL] != 100) { # only masters can set 100
				$this->parent->log[] = $this->parent->langOut("group_top_level_is_99");
				$this->parent->setLog(CONS_LOGGING_WARNING);
				$data['level'] = 99;
			}
		} else if ($action == CONS_ACTION_DELETE && $this->parent->safety) {
			# gets the group level, if it's higher, disallow delete
			$lvl = $this->parent->dbo->fetch("SELECT level FROM ".$baseModule->dbname." WHERE id=".$data['id']);
			if ($lvl >= $_SESSION[CONS_SESSION_ACCESS_LEVEL]) {
				$this->parent->log[] = $this->parent->langOut("group_cannot_change_higher");
				$this->parent->setLog(CONS_LOGGING_WARNING);
				return false;
			}
			if ($data['id'] == $this->parent->dimconfig['guest_group']) {
				$this->parent->log[] = $this->parent->langOut("group_cannot_delete_guest");
				$this->parent->setLog(CONS_LOGGING_WARNING);
				return false;
			}
		}
		// delete all caches
		$files = listFiles(CONS_PATH_CACHE.$_SESSION['CODE']."/",$eregfilter='@admin([0-9]*)\.cache@');
		foreach ($files as $file) {
			@unlink(CONS_PATH_CACHE.$_SESSION['CODE']."/".$file);
		}

		return true;
	}

	function field_interface($field,$action,&$data) {
		# checks if this field should be displayed differently or not at all on an administrative enviroment
		# return TRUE to use default, FALSE not to display or the STRING that will replace the area

		// permissions

		return true;
	}


}

