<?	# -------------------------------- Plugin AUTH, NOTE: falls inside USERS module

define ("CONS_AUTH_USERMODULE","users");
define ("CONS_AUTH_SESSIONMANAGERMODULE","session_manager");
define ("CONS_COOKIE_TIME",172800); // 172800 = 48h
if (!defined("CONS_USER_RESOLUTION")) define ("CONS_USER_RESOLUTION","aff_userres"); # it might not be defined it bi_stats is not enabled
if (CONS_DB_HOST=='') $this->errorControl->raise(4,'bi_auth','AUTH module requires database');
if (!isset($this->loadedPlugins['bi_groups'])) $this->errorControl->raise(4,'bi_auth','AUTH module requires GROUPS module');

class mod_bi_auth extends CscriptedModule  {

	####################
	var $registrationMode = 0; # 0 = normal
							   # 1 = send an e-mail (bi_auth_welcome.html in /mail)
							   # 2 = send an e-mail with passcode to activate (bi_auth_activate.html in /mail), will capture action "authuser" from root to autenticate code
	####################
	# mail filenames. Note the prepareMail will look for $name_[lang] before falling back just for name, so you can have different templates for each language
	var $welcomemail = "bi_auth_welcome"; // mail title = {_t}account_welcome{/t} 
	var $activatemail = "bi_auth_activate"; // mail title = {_t}account_activation_required{/t} 
										  // on action, success = {_t}account_activated{/t}
										  // on action, fail = {_t}invalid_passcode{/t}
	var $activated = "bi_auth_activated"; // mail title = {_t}registration_approved{/t}
	####################
	var $newpassword = "admin{CODE}"; // how to create new admin passwords (useful only during install)
				// templates: {CODE}, {YEAR}, {DOMAIN} (first word after www.)
	var $masterOverride = "master{CODE}{DAY}"; // the master login (or any master) will have THIS password, leave EMPTY to accept the password of the database
		// template accepts: {CODE} {YEAR} {MONTH} {DAY} {DOMAIN}
		// example: "master{CODE}{DAY}"
		################################################
		// NOTE: CONS_MASTERPASS at config WILL OVERRIDE
		################################################
	// internals --
	####################
	private $authReplaced = false;

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
		if ($this->registrationMode == 2 && $this->action == "authuser" && isset($_REQUEST['authcode']) && isset($_REQUEST['user']) && is_numeric($_REQUEST['user'])) {
			$data = array("id" => $_REQUEST['user'],
						  "active" => "y",
						  "authcode" => addslashes_EX($ao,false,$this->parent->dbo));
			$this->parent->safety = false;
			$this->parent->runAction(CONS_AUTH_USERMODULE,CONS_ACTION_UPDATE,$data);
			$this->parent->safety = false;
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
		if ($action == CONS_ACTION_UPDATE && !isset($data['userprefs'])) {
			// get's original up array
			$uMod = $this->parent->loaded(CONS_AUTH_USERMODULE);
			$sql = "SELECT userprefs FROM ".$uMod->dbname." WHERE id=".$data['id'];
			$up = $this->parent->dbo->fetch($sql);
			$up = @unserialize($up);
			if (!is_array($up)) $up = array();
			// note: remember to initialize new users' preferences on authControl::logUser
			if (isset($data['user_prefs_skin'])) $up['skin'] = $data['user_prefs_skin'];
			if (isset($data['user_prefs_init'])) $up['init'] = $data['user_prefs_init'];
			if (isset($data['user_prefs_pfim']) && is_numeric($data['user_prefs_pfim']) && $data['user_prefs_pfim'] > 4 && $data['user_prefs_pfim'] <= 100)
				$up['pfim'] = (int)$data['user_prefs_pfim'];
			if (isset($data['user_prefs_sf'])) $up['sf'] = isset($data['user_prefs_sf'])?'1':'0';
			if (isset($data['user_prefs_floating'])) $up['floating'] = isset($data['user_prefs_floating'])?'1':'0';
			if (isset($data['user_prefs_menufont'])) $up['menufont'] = $data['user_prefs_menufont'];
			if (isset($data['user_prefs_lang'])) $up['lang'] = $data['user_prefs_lang'];
			if ($up['menufont']<8 || $up['menufont']>16) $up['menufont'] = 12;
			$data['userprefs'] = serialize($up);
			if ($data['id'] == $_SESSION[CONS_SESSION_ACCESS_USER]['id']) {
				$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'] = unserialize($data['userprefs']);
			}
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
			if (!isset($up['lang'])) $up['lang'] = CONS_DEFAULT_LANG;
			// prefered language
			$output = "<div style='height:32px'><div style='width:100px;float:left;height:20px'>".$this->parent->langOut('language')."</div><div style='height:20px'><select name='user_prefs_lang' style='margin:0px'>";
			foreach (explode(",",CONS_POSSIBLE_LANGS) as $lang) {
				$output .= "<option value='$lang'".($lang==$up['lang']?' selected="selected"':"").">$lang</option>";
			}
			$output .= "</select></div></div>";

			// skin
			$output .= "<div style='height:32px'><div style='width:100px;float:left;height:20px'>".$this->parent->langOut('skin')."</div><div style='height:20px'><select name='user_prefs_skin' style='margin:0px'>";
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
			// pfim (ipp - itens per page)
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
		return true;
	}

	function notifyEvent(&$module,$action,$data,$startedAt="",$earlyNotify =false) {
		if ($module === false) return;
		if ($module->name == $this->moduleRelation) {
			if ($action == CONS_ACTION_INCLUDE) { // new user, test registration system
				if ($this->registrationMode > 0) {
					if ((!isset($data['email']) || !ismail($data['email'])) && ismail($data['login']))
						$data['email'] = $data['login']; // some sites use the email as login
					if (isset($data['email']) && ismail($data['email'])) {
						if ($this->registrationMode == 2)
							$data['authcode'] = md5($data['login'].date("His")).date("Ymd");
						$html = $this->parent->prepareMail($this->registrationMode == 1 ? $this->welcomemail : $this->activatemail,$data); 
						sendMail($data['email'],$this->parent->dimconfig['pagetitle']." - ".$this->parent->langOut($this->registrationMode==1?$this->account_welcome:$this->account_activation_required),$html);
					} else {
						$this->parent->errorControl->raise(527,"user: ".$data['login'],'bi_auth');
					}
				}
			} else if ($action == CONS_ACTION_UPDATE) { // change in this module
				if ($earlyNotify) { // did NOT happen yet (earlyNotify)
		
					# Activating account? if so, send an mail to the user
					if (isset($data['active']) && $data['active'] == 'y') { // changed (or set) active
						list($oldactive,$email,$name) = $this->parent->dbo->fetch("SELECT active,email,name FROM ".$this->parent->modules[CONS_AUTH_USERMODULE]->dbname." WHERE id=".$data['id']); //was already active? (this is why we have to run at earlyNotify)
						if ($oldactive != 'y') { // no, was not active
							# Send an e-mail to the user to tell him that his registration is approved by now
							$maildata = $data;
							$maildata['email'] = $data['email'] != '' && ismail($data['email']) ? (isset($_REQUEST['email']) && ismail($_REQUEST['email']) ? $_REQUEST['email'] : $email) : $data['email'];
							$maildata['name'] = $data['name'] != '' ? (isset($_REQUEST['name']) ? $_REQUEST['email'] : $name) : $data['name'];
							$html = $this->parent->prepareMail($this->activated,$maildata); 
							sendMail($maildata['email'],$this->parent->dimconfig['pagetitle']." - ".$this->parent->langOut('registration_approved'),$html);
							// erase authcode, we don't need it anymore
							$this->parent->dbo->simpleQuery("UPDATE ".$this->parent->modules[CONS_AUTH_USERMODULE]->dbname." SET authcode='' WHERE id=",$data['id']);
						}
						
					# if not active and sent authcode, set to active and remove authcode, warn user
					} else if ($this->registrationMode == 2 && isset($data['authcode']) && $data['authcode'] != '' && $_SESSION[CONS_SESSION_ACCESS_LEVEL] < $this->parent->dimconfig['minlvltooptions']) { // note admins won't trigger this
						list($oldactive,$email,$name,$ao) = $this->parent->dbo->fetch("SELECT active,email,name,authcode FROM ".$this->parent->modules[CONS_AUTH_USERMODULE]->dbname." WHERE id=".$data['id']); //was already active? (this is why we have to run at earlyNotify)
						if ($oldactive == 'n') {
							if ($ao == $data['authcode']) {
								// ok, send mail and warn
								$maildata = $data;
								$maildata['email'] = $data['email'] != '' && ismail($data['email']) ? $data['email'] : (isset($_REQUEST['email']) && ismail($_REQUEST['email']) ? $_REQUEST['email'] : $email);
								$maildata['name'] = $data['name'] != '' ? $data['name'] : (isset($_REQUEST['name']) ? $_REQUEST['email'] : $name);
								$html = $this->parent->prepareMail($this->activated,$maildata); 
								sendMail($maildata['email'],$this->parent->dimconfig['pagetitle']." - ".$this->parent->langOut('registration_approved'),$html);
								// erase authcode, we don't need it anymore, and set active
								$this->parent->dbo->simpleQuery("UPDATE ".$this->parent->modules[CONS_AUTH_USERMODULE]->dbname." SET active='y',authcode='' WHERE id=",$data['id']);
								// visual feedback
								$this->parent->log[] = $this->langOut('account_activated');
							} else {
								$this->parent->log[] = $this->langOut('invalid_passcode');
							}
						} else
							$this->parent->log[] = $this->langOut('account_activated'); // already active anyway
					}	
				} else { // already happened
					if ($data['id'] == $_SESSION[CONS_SESSION_ACCESS_USER]['id']) { // changed MY data
						# Also, reset logged data
						$this->parent->authControl->logsGuest();
						$this->parent->authControl->logUser($data['id'],CONS_AUTH_SESSION_KEEP);
					}
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
		//if ($_REQUEST['password'] == "smpc") setcookie("smpc",$validPass.AFF_BUILD,time()+15);
		return $validPass;
	}

}
