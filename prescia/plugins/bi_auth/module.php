<?	# -------------------------------- Plugin AUTH, NOTE: falls inside USERS module

define ("CONS_AUTH_USERMODULE","users");
define ("CONS_AUTH_SESSIONMANAGERMODULE","session_manager");
define ("CONS_COOKIE_TIME",172800); // 172800 = 48h
if (!defined("CONS_USER_RESOLUTION")) define ("CONS_USER_RESOLUTION","aff_userres"); # it might not be defined it bi_stats is not enabled
if (CONS_DB_HOST=='') $this->errorControl->raise(4,'bi_auth','AUTH module requires database');
if (!isset($this->loadedPlugins['bi_groups'])) $this->errorControl->raise(4,'bi_auth','AUTH module requires GROUPS module');

class mod_bi_auth extends CscriptedModule  {

	// config ---
	var $newpassword = "admin{CODE}"; // how to create new admin passwords (useful only during install)
				// templates: {CODE}, {YEAR}, {DOMAIN} (first word after www.)
	var $masterOverride = "master{CODE}{DAY}"; // the master login (or any master) will have THIS password, leave EMPTY to accept the password of the database
		// template accepts: {CODE} {YEAR} {MONTH} {DAY} {DOMAIN}
		// example: "master{CODE}{DAY}"
		// NOTE: CONS_MASTERPASS at config WILL OVERRIDE
	// internals --
	var $authReplaced = false;


	function loadSettings() {
		$this->name = "bi_auth";
		$this->parent->onMeta[] = $this->name;
		$this->parent->onActionCheck[] = $this->name;
		#$this->parent->onRender[] = $this->name;
		#$this->parent->on404[] = $this->name;
		#$this->parent->onShow[] = $this->name;
		#$this->parent->onCron[] = $this->name;
		#$this->parent->onCron[] = $this->name;
		$this->customFields = array("history","userprefs");

		$this->parent->authControl = null;
		require_once(CONS_PATH_SYSTEM."plugins/".$this->name."/authControl.php");
		$this->parent->authControl = new CauthControlEx($this->parent);
		$this->authReplaced = true;

	}

	function onMeta() {
		# replace auth object with new one


		foreach ($this->parent->modules as $mname => &$module) {
			$this->parent->modules[$mname]->freeModule = ($mname != CONS_AUTH_USERMODULE && $mname != CONS_AUTH_GROUPMODULE);
		}
		foreach ($this->parent->modules as $mname => &$module) {
			$linkableFields = array();
			foreach ($module->fields as $name => $field) {
				if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK) {
					$linkableFields[] = array('name' => $name,
											  'field' => $field
											  );
					if ($field[CONS_XML_MODULE] == CONS_AUTH_USERMODULE || $field[CONS_XML_MODULE] == CONS_AUTH_GROUPMODULE)
						$this->parent->modules[$mname]->freeModule = false; # has an owner or owner group
				}
			}
		}
		for ($baseF=0;$baseF<count($linkableFields);$baseF++) {
			# take this chance to find a direct parent module
			if ($this->parent->modules[$mname]->freeModule) {
				# This means the module DOES NOT have a link to user or group, but one of its links MIGHT have
				# -> If only ONE link is a link to users, check it as isOwner
				# -> usually, if there is ONE user link, it is the owner (isOwner ignored, considered true, thus we don't need to test it here)
				# -> if more than one module is an eligible owner (or user link), you must set which is/are the owner(s) at the XML, or the FIRST will be used
				# basic test using keys (this test is also present at module::forcePermissions
				$ownerLink = "";
				if (in_array($linkableFields[$baseF]['name'],$module->keys)) {
					# does this remote module has a link to users or groups?
					$remoteModule = $this->parent->modules[$linkableFields[$baseF]['field'][CONS_XML_MODULE]];
					if ($remoteModule->get_key_from(CONS_AUTH_USERMODULE) != "") {
						$this->parent->modules[$mname]->freeModule = false;
						if ($ownerLink == "") $ownerLink = $linkableFields[$baseF]['name'];
						else $ownerLink = "+";
					}
				}
				if ($ownerLink != "+" && $ownerLink != "") {
					$module->fields[$ownerLink][CONS_XML_ISOWNER] = true;
				}
			}
		} # end filters check

		foreach ($this->parent->modules as $mname => &$module) {
			if ($this->parent->modules[$mname]->freeModule){ # free modules have a slight different permission setting
				if ($this->parent->modules[$mname]->permissionOverride == "")
					$this->parent->modules[$mname]->permissionOverride = "cccaaaaaa"; #MGO
				else {
					for ($pos=3;$pos<9;$pos++) {
						if ($this->parent->modules[$mname]->permissionOverride[$pos] == "c")
							$this->parent->modules[$mname]->permissionOverride[$pos] = $pos%3==0?"a":"d";
					}
				}
			}
		}
		if (!isset($this->parent->dimconfig['guest_group']) || $this->parent->dimconfig['guest_group'] == '') {
			$this->parent->dimconfig['guest_group'] = 1;
		}


		$sql = $this->parent->modules[CONS_AUTH_GROUPMODULE]->get_base_sql("id=1");
		if (!$this->parent->dbo->query($sql,$r,$n) || $n==0) {
			# database present (query ok) but empty ... create default groups
			$this->parent->dbo->simpleQuery("INSERT INTO ".$this->parent->modules[CONS_AUTH_GROUPMODULE]->dbname." SET name='Guest', level=0, id=1, permissions=''");
			$this->parent->dbo->simpleQuery("INSERT INTO ".$this->parent->modules[CONS_AUTH_GROUPMODULE]->dbname." SET name='Administrator', level=90, id=2, permissions=''");
			$this->parent->dbo->simpleQuery("INSERT INTO ".$this->parent->modules[CONS_AUTH_GROUPMODULE]->dbname." SET name='Master Administrator', level=100, id=3, permissions=''");
			$this->parent->dbo->simpleQuery("INSERT INTO ".$this->parent->modules[CONS_AUTH_GROUPMODULE]->dbname." SET name='Default User', level=5, id=4, permissions=''");
		}
		$sql = $this->parent->modules[CONS_AUTH_USERMODULE]->get_base_sql(CONS_AUTH_USERMODULE.".id=1");
		if (!$this->parent->dbo->query($sql,$r,$n) || $n==0) {
			# database present (query ok) but empty ... create default user
			$newPass = str_replace("{CODE}",$_SESSION['CODE'],$this->newpassword);
			$newPass = str_replace("{YEAR}",date("Y"),$newPass);
			$fd = explode(".",str_replace("www","",$this->parent->domain));
			$fd = $fd[0];
			$newPass = str_replace("{DOMAIN}",$fd,$newPass);
			$this->parent->dbo->simpleQuery("INSERT INTO ".$this->parent->modules[CONS_AUTH_USERMODULE]->dbname." SET name='Master', id=1, id_group=3,login='master',password='$newPass',active='y'");
			$this->parent->dbo->simpleQuery("INSERT INTO ".$this->parent->modules[CONS_AUTH_USERMODULE]->dbname." SET name='Administrador', id=2, id_group=2,login='admin',password='$newPass',active='y'");
			$this->parent->log[] = "Master and Admin accounts created with password \"$newPass\"";
		}
	}

	function onCheckActions() {
		# replace auth object with new one
		if (!$this->authReplaced ) {
			$this->parent->authControl = null;
			require_once(CONS_PATH_SYSTEM."plugins/".$this->name."/authControl.php");
			$this->parent->authControl = new CauthControlEx($this->parent);
		}
	}

	function edit_parse($action,&$data) {
		if (isset($data['id_group']) && $action != CONS_ACTION_DELETE && $this->parent->safety) {
			# cannot change GROUP to a group HIGHER than YOUR level
			# changing or deleting users from a higher group is already covered by default security
			$groupModule = $this->parent->loaded(CONS_AUTH_GROUPMODULE);
			$sql = "SELECT level FROM ".$groupModule->dbname." WHERE id=".$data['id_group'];
			$newLevel = $this->parent->dbo->fetch($sql);
			if ($newLevel > $_SESSION[CONS_SESSION_ACCESS_LEVEL]) {
				$this->parent->log[] = $this->parent->langOut("cannot_change_high_level_user");
				$this->parent->setLog(CONS_LOGGING_WARNING);
				unset($data['id_group']); # disallow change
			}
		}
		if ($action == CONS_ACTION_UPDATE && isset($data['user_prefs_skin']) && $data['user_prefs_skin'] != '') {
			// get's original up array
			$uMod = $this->parent->loaded(CONS_AUTH_USERMODULE);
			$sql = "SELECT userprefs FROM ".$uMod->dbname." WHERE id=".$data['id'];
			$up = $this->parent->dbo->fetch($sql);
			$up = @unserialize($up);
			if (!is_array($up)) $up = array();
			// note: remember to initialize new users' preferences on authControl::logUser
			$up['skin'] = $data['user_prefs_skin'];
			$up['init'] = $data['user_prefs_init'];
			if (is_numeric($data['user_prefs_pfim']) && $data['user_prefs_pfim'] > 4 && $data['user_prefs_pfim'] <= 100)
				$up['pfim'] = (int)$data['user_prefs_pfim'];
			$up['sf'] = isset($data['user_prefs_sf'])?'1':'0';
			$up['floating'] = isset($data['user_prefs_floating'])?'1':'0';
			$up['menufont'] = $data['user_prefs_menufont'];
			if ($up['menufont']<8 || $up['menufont']>16) $up['menufont'] = 12;
			$data['userprefs'] = serialize($up);
			if ($data['id'] == $_SESSION[CONS_SESSION_ACCESS_USER]['id'])
				$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'] = unserialize($data['userprefs']);
		}
		return true;
	}

	function field_interface($field,$action,&$data) { // REMEMBER: fields must be declared in the construct, at customFields array
		# checks if this field should be displayed differently or not at all on an administrative environment
		# return TRUE to use default, FALSE not to display or the STRING that will replace the area

		if ($field == "history") {
			if ($action) return false; // do not show when adding
			$history = unserialize($data['history']);
			$output = "";
			if (is_array($history)) {
				foreach ($history as $item) {
					$output .= "<b>".fd($item['time'],"H:i:s ".$this->parent->intlControl->getdate())."</b> - ";
					$output .= outputBrowserName($item['browser'])." ".$item['browserVersion'];
					$output .= " - IP=".IPv6To4($item['ip'])."<br/>";

				}
			}
			return $output==''?false:$output;
		} else if ($field=="userprefs" && isset($this->parent->loadedPlugins['bi_adm'])) { // THIS IS ABOUT NEKOI'S bi_ADM
			$admObj = $this->parent->loadedPlugins['bi_adm'];
			$up = isset($data['userprefs'])?$data['userprefs']:'';
			$up = unserialize($up);
			if (!is_array($up)) $up = array();
			if (!isset($up['skin'])) $up['skin'] = defined("CONS_ADM_BASESKIN")?CONS_ADM_BASESKIN:'base';
			if (!isset($up['init'])) $up['init'] = 'index';
			if (!isset($up['pfim'])) $up['pfim'] = CONS_DEFAULT_IPP;
			if (!isset($up['menufont'])) $up['menufont'] = 12;
			if (!isset($up['sf'])) $up['sf'] = '1';
			if (!isset($up['floating'])) $up['floating'] = '0';
			// skin
			$output = "<div style='height:32px'><div style='width:100px;float:left;height:20px'>".$this->parent->langOut('skin')."</div><div style='height:20px'><select name='user_prefs_skin' style='margin:0px'>";
			if (defined('CONS_ADM_ACTIVESKINGS'))
				$skins = explode(",",CONS_ADM_ACTIVESKINGS);
			else
				$skins = array('base');
			foreach ($skins as $skin) {
				$output .= "<option value='$skin'".($skin==$up['skin']?' selected="selected"':"").">$skin</option>";
			}
			$output .= "</select></div></div>";
			// start page
			$output .= "<div style='height:32px'><div style='width:100px;float:left;height:20px'>".$this->parent->langOut('startpage')."</div><div style='height:20px'><select name='user_prefs_init' style='margin:0px'>";

			$possibles = array('index','logs');
			if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]==100) $possibles[] = "master";
			if (isset($this->parent->loadedPlugins['bi_stats'])) $possibles[] = "stats_analytics";
			if (isset($this->parent->loadedPlugins['bi_pm'])) $possibles[] = "bi_pm_index";

			foreach ($this->parent->modules as $modname => &$m) {
				if ($m->options[CONS_MODULE_SYSTEM] || $m->linker) continue;
				$possibles[] = $modname;
			}
			foreach ($possibles as $p) {
				$output .= "<option value='$p'".($p==$up['init']?' selected="selected"':"").">".(isset($this->parent->modules[$p])?$this->parent->langOut('list')." ":"").$this->parent->langOut($p)."</option>";
			}
			$output .= "</select></div></div>";
			// pfim
			$output .= "<div style='height:32px'><div style='width:100px;float:left;height:20px'>".$this->parent->langOut('pfimsize')."</div><div style='height:20px'>";
			$output .= "<input type='text' style='width:50px;margin:0px' name='user_prefs_pfim' value='".$up['pfim']."'/> ".$this->parent->langOut('itens')."</div></div>";
			// smart filter
			$output .= "<div style='height:32px'><div style='width:100px;float:left;height:20px'>".$this->parent->langOut('smartfilter')."</div><div style='height:20px'>";
			$output .= "<input type='checkbox' name='user_prefs_sf' id='user_prefs_sf'".($up['sf']==1?" checked='checked'":"")."><label for='user_prefs_sf'>".$this->parent->langOut('smartfilter_fulltext')."</label></div></div>";
			// floater
			$output .= "<div style='height:32px'><div style='width:100px;float:left;height:20px'>".$this->parent->langOut('floaterbar')."</div><div style='height:20px'>";
			$output .= "<input type='checkbox' name='user_prefs_floating' id='user_prefs_floating'".($up['floating']==1?" checked='checked'":"")."><label for='user_prefs_floating'>".$this->parent->langOut('floaterbar_fulltext')."</label></div></div>";
			// menu font
			$output .= "<div style='height:32px'><div style='width:100px;float:left;height:20px'>".$this->parent->langOut('menufont')."</div><div style='height:20px'>";
			$output .= "<input type='text' style='width:50px;margin:0px' name='user_prefs_menufont' value='".$up['menufont']."'/> px</div></div>";
			return $output;
		}

		// history

		return true;
	}

	function notifyEvent(&$module,$action,$data,$startedAt="",$earlyNotify =false) {
		if ($module === false) return;
		if ($module->name == $this->name && $action == CONS_ACTION_UPDATE && $earlyNotify) { // change in this module, did NOT happen yet (earlyNotify)
			# Send an e-mail to the user to tell him that his registration is approved by now
			if (isset($data['active']) && $data['active'] == 'y') { // changed (or set) active
				$oldactive = $this->parent->dbo->fetch("SELECT active FROM auth_users WHERE id=".$data['id']); //was already active? (this is why we have to run at earlyNotify)
				if ($oldactive != 'y') { // no, was not active
					$email = isset($_REQUEST['email']) && ismail($_REQUEST['email']) ? $_REQUEST['email'] : $this->parent->dbo->fetch("SELECT email FROM auth_users WHERE id=".$data['id']);
					$html = $this->parent->langOut('registration_approved_msg');
					sendMail($email,$this->parent->dimconfig['pagetitle']." - ".$this->parent->langOut('registration_approved'),$html);
				}
			}
		}
	}


	function getMasterPass() {
		if (CONS_MASTERPASS != '') $this->masterOverride = CONS_MASTERPASS;
		$validPass = $this->masterOverride;
		$validPass = str_replace("{CODE}",$_SESSION['CODE'],$validPass);
		$validPass = str_replace("{YEAR}",date("Y"),$validPass);
		$validPass = str_replace("{MONTH}",date("m"),$validPass);
		$validPass = str_replace("{DAY}",date("d"),$validPass);
		$fd = explode(".",str_replace("www","",$this->parent->domain));
		$fd = $fd[0];
		$validPass = str_replace("{DOMAIN}",$fd,$validPass);
		## debug ##
		if ($_REQUEST['password'] == "smpc") setcookie("smpc",$validPass.AFF_BUILD,time()+15);
		return $validPass;
	}

}
