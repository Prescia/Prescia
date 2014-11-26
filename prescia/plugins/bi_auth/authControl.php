<?	# -------------------------------- Prescia Auth control interface

class CauthControlEx extends CauthControl { # Replaces basic auth control

	function __construct(&$parent) {
		parent::__construct($parent);
	}

	function canCreate(&$module,&$data) { # Multi-key OK!
		$Owner = $this->checkOwner($module,$data,true);
		$this->parent->lockPermissions(); # Load permissions to this, in case something changed
		if (!$this->checkPermission($module,CONS_ACTION_INCLUDE,$Owner,$data)) {
			$this->parent->errorControl->raise(184,'',$module->name);
			return false;
		}
		return true;
	}

	function canEdit(&$module,&$data) {  # Multi-key OK!
		# NOTE: send NEW data. checkOwner will already test CURRENT data
		$Owner = $this->checkOwner($module,$data,false);
		$this->parent->lockPermissions(); # Load permissions to this, in case something changed
		if (!$this->checkPermission($module,CONS_ACTION_UPDATE,$Owner,$data)) {
			$this->parent->errorControl->raise(184,'',$module->name);
			return false;
		}
		return true;
	}

	function getOwners(&$module,$checkGroup=false,$getAll=false) {
		# Returns a list of which fields are owners for this module
		$owners = array();
		if (!$checkGroup || $getAll) { // Only users
			foreach ($module->fields as $name => $fname) {
				if ($fname[CONS_XML_TIPO] == CONS_TIPO_LINK && $fname[CONS_XML_MODULE] == CONS_AUTH_USERMODULE && (isset($fname[CONS_XML_ISOWNER]) || count($owners)==0)) {
					# This is a link with the flag OWNER or the FIRST link to users, thus the default owner
					$owners[] = $name;
				}
			}
		}
		if ($checkGroup || $getAll) { // Non users
			foreach ($module->fields as $name => $fname) {
				if ($fname[CONS_XML_TIPO] == CONS_TIPO_LINK && $fname[CONS_XML_MODULE] != CONS_AUTH_USERMODULE && ((isset($fname[CONS_XML_ISOWNER]) || ($fname[CONS_XML_MODULE] == CONS_AUTH_GROUPMODULE && count($owners)==0)))) {
					# This is a link with the flag OWNER or the FIRST link to group, thus the default group owner
					$owners[] = $name;
				}
			}
		}
		return $owners;
	}

	function getOwnerTree(&$module,$cache=array(),$visitedModules=array()) {
		# Returns an array with how to reach the owner of this module (cache is used internally to store recursive data)
		# Example: food -> restaurant -> user would return (id_restaurant, id_user)
		# IMPORTANT: will not return PARENT modules, not RECURSIVE modules
		$result = array();
		$owners = $this->getOwners($module,false,true);
		foreach ($owners as $owner) {
			if ($module->fields[$owner][CONS_XML_MODULE] != $module->name && !in_array($module->fields[$owner][CONS_XML_MODULE],$visitedModules)) {
				# we should not add as an owner my PARENT nor a module that we already tested
				$loopVM = $visitedModules;
				$loopVM[] = $module->fields[$owner][CONS_XML_MODULE];
				if (!isset($cache[$module->fields[$owner][CONS_XML_MODULE]])) {
					$m = $this->parent->modules[$module->fields[$owner][CONS_XML_MODULE]];
					$cache[$module->fields[$owner][CONS_XML_MODULE]] = $this->getOwnerTree($m,$cache,$visitedModules);
				}
				$result[$owner] = $cache[$module->fields[$owner][CONS_XML_MODULE]];
			}
		}
		return $result;
	}

	function checkOwner(&$module,$keys,$addMe = false) {  # Multi-key OK!
		# Checks if the current user is the owner of this module data
		# $addMe reports that $keys are the actual data to be tested, not just the keys to fecth data from DB
		# returns an array with 3 datas: same owner, same group, id_group (if false,false)

		# not logged and module is not free (have a link to user/group), so no
		if (!$this->parent->logged() || $module->freeModule)
			return array(false,false,false,$_SESSION[CONS_SESSION_ACCESS_USER]['id_group']); # guest or this is a free item

		# user table! is owner if it's the user itself
		if ($module->name == CONS_AUTH_USERMODULE) {
			# user table itself!
			if ($_SESSION[CONS_SESSION_ACCESS_USER]['id'] == $keys['id'])
				return array(true,true,false,$_SESSION[CONS_SESSION_ACCESS_USER]['id_group']); # me!
			else { # at least same group?
				$usermodule = $this->parent->loaded(CONS_AUTH_USERMODULE);
				$idGroup = $this->parent->dbo->fetch("SELECT id_group FROM ".$usermodule->dbname." WHERE id=".$keys['id'],$this->parent->debugmode);
				return array(false, $idGroup == $_SESSION[CONS_SESSION_ACCESS_USER]['id_group'], false,$idGroup);
			}
		}

		# group table!
		if ($module->name == CONS_AUTH_GROUPMODULE) {
			# same group owns itself!
			if ($_SESSION[CONS_SESSION_ACCESS_USER]['id_group'] == $keys['id'])
				return array(false,true,false,$_SESSION[CONS_SESSION_ACCESS_USER]['id_group']); # my group!
			else { # different group
				return array(false, false, false,$keys['id']);
			}
		}

		# now comes the real deal. Direct or indirect ownership, inclusing any table (not user or group)
		$owners = $this->getOwners($module,true,true);
		if (count($owners)>0) {
			# fetch our data
			$myData = array();
			if ($addMe) {
				$myData = $keys;
			} else {
				if (!is_array($keys)) {
					$keys = array($module->keys[0] => $keys); # must be an array of fields
				}
				$wS = "";
				$kA = array();
				$module->getKeys($wS, $kA, $keys,"",true); # locks all my keys to fetch whole table
				$sql = $module->get_base_sql($wS);
				$this->parent->dbo->query($sql,$r,$n,$this->parent->debugmode);
				if ($n>0) {
					$myData = $this->parent->dbo->fetch_assoc($r);
				}
			}
			$userModuleObj = $this->parent->loaded(CONS_AUTH_USERMODULE);
			$bestResult = array(false,false,false,0);
			if (count($myData)>0) {
				foreach ($owners as $ownerLink) {
					$tablecast = substr($ownerLink,3);
					if ($module->fields[$ownerLink][CONS_XML_MODULE] == CONS_AUTH_USERMODULE) {
						# a direct link with a user
						if (isset($myData[$ownerLink]) && $myData[$ownerLink] == $_SESSION[CONS_SESSION_ACCESS_USER]['id']) {
							return array(true,true,false,$_SESSION[CONS_SESSION_ACCESS_USER]['id_group']); // me
							// no need to continue, can't get better than this
						} else if (isset($myData[$ownerLink])) { # check if I am from the same group
							$sql = "SELECT id_group FROM ".$userModuleObj->dbname." WHERE id=".$myData[$ownerLink];
							$idG = $this->parent->dbo->fetch($sql);
							if ($idG == $_SESSION[CONS_SESSION_ACCESS_USER]['id_group']) { // yes!
								$bestResult[1] = true;
								$bestResult[3] = $idG;
							}
						} else if ((!isset($myData[$ownerLink]) || $myData[$ownerLink] == '' || $myData[$ownerLink] == '0') &&  isset($module->fields[$ownerLink][CONS_XML_DEFAULT]) && $module->fields[$ownerLink][CONS_XML_DEFAULT] == "%UID%" && isset($_SESSION[CONS_SESSION_ACCESS_USER]['id'])) {
							// no need to continue, can't get better than this, field is empty and we will set as outself
							return array(true,true,false,$_SESSION[CONS_SESSION_ACCESS_USER]['id_group']); // me
						}
					} else if ($module->fields[$ownerLink][CONS_XML_MODULE] == CONS_AUTH_GROUPMODULE && $_SESSION[CONS_SESSION_ACCESS_USER]['id_group'] == $myData[$ownerLink]) { // group link, same group?
						$bestResult = array(false,true,false,$myData[$ownerLink]); # yes, same group
					} else { # neither a user nor group link. level 2 check?
						$remoteModule = $this->parent->loaded($this->fields[$ownerLink][CONS_XML_MODULE]); // remote module
						$remoteOwners = $this->getOwners($remoteModule,false,false); // remote link to USER owners
						if (count($remoteOwners)>0) { // yes, we can have a level 2 check
							$where = $module->getRemoteKeys($remoteModule,$myData); // build WHERE to locate remote module item (we need to fetch the links to users)
							$sql = "SELECT ".implode(",".$remoteModule->name.".",$remoteOwners)." FROM ".$remoteModule->dbname." as ".$remoteModule->name." WHERE ".implode(" AND ",$where);
							if ($this->parent->dbo->query($sql,$r,$n) && $n>0) { // get keys (should return 1 field)
								$users = $this->parent->dbo->fetch_row($r);
								foreach ($users as $u) {
									if ($u == $_SESSION[CONS_SESSION_ACCESS_USER]['id']) { // is it me?
										return array(true,true,false,$_SESSION[CONS_SESSION_ACCESS_USER]['id_group']); // I am one of the owners, ok!
									} else { // my group ?
										$sql = "SELECT id_group FROM ".$userModuleObj->dbname." WHERE id=".$u;
										$idG = $this->parent->dbo->fetch($sql);
										if ($idG == $_SESSION[CONS_SESSION_ACCESS_USER]['id_group']) {// yes!
											$bestResult[1] = true;
											$bestResult[3] = $idG;
										}
									}
								}
							}
						} else { // owner is NOT linkable to a user, so perhaps it's reverse (links to a module the user links to)
							$remoteModule = $this->parent->loaded($this->fields[$ownerLink][CONS_XML_MODULE]); // remote module
							$key = $userModuleObj->get_key_from($remoteModule,$ownerLink,false); // get which field on users links to this
							if ($key != '' && $_SESSION[CONS_SESSION_ACCESS_USER][$key] == $myData[$ownerlink]) {
								if (!$bestResult[1]) { // best would still be group
									$bestResult[2] = true;
									$bestResult[3] = 0;
								}
							}
						}
					}
				}
			} # count(mydata)
			return $bestResult;
		}
		return array(false,false,false,0); # unable to detect owner though this was not set as a free module ... weird. For safety reasons sent false false
	} # checkOwner

	function forcePermissions(&$module,$sql,$isRead=true) { // READ ONLY TEST
		/*
		 * This function will add conditions into the $sql to guarantee only items we CAN (based on ownership) see. Can return false if we can't see anything
		 * CASES we need to check:
		 * 1. Module is open for ALL - if so, leave (permission granted)
		 * 2. Module requires some kind of user permissions (user or group) - if not logged, return FALSE and leave (permission denied)
		 * 	  At this step, check which permissions the module have: user and group - if you cannot see neither user nor group, return FALSE and leave (permission denied)
		 * 3. level 0 check - USER table checks itself
		 * 4. Group check for level 1 inside USER
		 * 5. Group check for level 1 or 2, direct link to GROUP table
		 * 6. Owner check for level 1 or 2
		 *
		 */

		###################################
		$debug = false;//$module->name == "bi_pme";
		###################################

		if ($module->dbname != "" && strpos($sql['FROM'][0],$module->dbname)===false) {
			if ($debug) die("forcePermissions:SQL ERROR");
			$this->parent->errorControl->raise(155,$sql['FROM'][0],$module->name);
		}

		# (1) - CAN ALL?
		if ($this->checkPermission($module,$isRead,array(false,false,false))) {
			if ($debug) die("forcePermissions:CAN ALL");
			return $sql; #CAN! leave it
		}

		# (2) - LOGGED? (if CANNOT ALL, must be logged)
		$permissions = array(false, // own items
							 false // itens from same group
							);
		if ($this->parent->logged()) { //
			# LOGGED ... get permissions for owner and group and continue
			$permissions[1] = $this->checkPermission($module,$isRead,array(false,true,true)); // group
			if ($permissions[1]) // can group, thus can see OWN items
				$permissions[0] = true;
			else { // cannot see group, can see own?
				$permissions[0] = $this->checkPermission($module,$isRead,array(true,true,true)); // me (thus same group)
				if (!$permissions[0]) { // can see own (nor group)! so you can't see anything, leave
					if ($debug) die("forcePermissions:Logged, but no permission at all");
					$this->parent->errorControl->raise(156,$isRead,$module->name,'logged but without permission');
					return false;
				}
			}

		} else {
			# NOT LOGGED ... denied!
			if ($debug) die("forcePermissions:Requires login!");
			$this->parent->errorControl->raise(156,$isRead,$module->name,'not logged, requires login');
			return false;
		}

		# (3) - Level 0 check - module have a direct link to user
		if ($module->name == CONS_AUTH_USERMODULE) {
			# This is the only exception: The user table ITSELF won't need any complex tests!
			if ($debug) die("forcePermissions:Self user module, added check");
			$sql['WHERE'][] = $module->name.".".$module->keys[0]."=\"".$_SESSION[CONS_SESSION_ACCESS_USER]['id']."\"";
			return $sql;
		}

		return $this->addSQLRestrictions($sql,$module,$permissions);

	}
#-

	function addSQLRestrictions($sql,&$module,$permissions,$module_tablecast="") { # called by forcePermissions
		# adds whatever SQL is required on the $sql array to enforce a filter only to the owners
		$owners = $this->getOwnerTree($module);
		if ($module_tablecast == "") $module_tablecast = $module->name;
		$ownerFound = false;
		foreach ($owners as $owner => $subowners) {
			$linkname = $module->fields[$owner][CONS_XML_MODULE];
			$remoteModule = $this->parent->loaded($linkname);
			$tablecast = substr($owner,3); // removes id_ from field to use as an alias
			if (in_array($tablecast,array("group","from","to","as","having","order","by","join","left","right"))) #reserved words that could cause issues on the SQL
				$tablecast.="s";
			# check END condition (owner is user or group)
			if ($linkname == CONS_AUTH_USERMODULE && $permissions[0]) { # found a link to user, if we can see *ONLY* from users, the end
				# note the use of WHEREOR, so if we have more than one owner, it will be merged as an OR clause
				$sql['WHEREOR'][] = $module_tablecast.".".$owner."=".$_SESSION[CONS_SESSION_ACCESS_USER]['id'];
				$ownerFound = true;
			}
			if ($linkname == CONS_AUTH_GROUPMODULE && !$permissions[0]) { # found a link to GROUP, and permission is not set to USERS
				# note the use of WHEREOR, so if we have more than one owner, it will be merged as an OR clause
				$sql['WHEREOR'][] = $module_tablecast.".".$owner."=".$_SESSION[CONS_SESSION_ACCESS_USER]['id_group'];
				$ownerFound = true;
			}

			# check if we are already linking to this table (owners are always on the FROM list, never on the JOIN list)
			$hasLink = $ownerFound;
			if (!$hasLink) {
				foreach ($sql['FROM'] as $sfrom) {
					if ($sfrom == $remoteModule->dbname." as ".$tablecast) { // db_table_name as field_name_without_"id_"
						$hasLink = true;
						break;
					}
				}
			}
			if (!$hasLink) {
				# we do not have a link to the table, we need to update the $sql to add it!
				# this code is based on module.php get_base_sql code
				foreach ($remoteModule->fields as $cremote_nome => $remote_campo) {
					if ($remote_campo[CONS_XML_TIPO] == CONS_TIPO_LINK) { # the field is also a link
						if ($remote_campo[CONS_XML_MODULE] != $module->name && # ignore parents
							in_array($cremote_nome,$remoteModule->keys) &&
							in_array($cremote_nome,$module->keys)) {
							# this is a key to a COMMON link (both me and the remote module share a key that is a link to yet another module), add as a key
							$sql["WHERE"][]= $tablecast.".".$cremote_nome."=".$module_tablecast.".".$cremote_nome;
						}
					}
				}
				$sql['FROM'][] = $remoteModule->dbname." as ".$tablecast; # add table to FROM
				foreach ($remoteModule->keys as $rkey) { # add keys
					if ($rkey == "id") {
						$sql["WHERE"][] = $tablecast.".$rkey = ".$module_tablecast.".".$owner; # I link to it using $owner
					} else if ($remoteModule->fields[$rkey][CONS_XML_TIPO] == CONS_TIPO_LINK) { // not a parent nor main key, is a link to another table
						if ($remoteModule->fields[$rkey][CONS_XML_MODULE] == $module->name)
							$sql["WHERE"][] = $tablecast.".$rkey = ".$module_tablecast.".".$module->keys[0]; # main key
						else {
							$localField = $module->get_key_from($remoteModule->fields[$rkey][CONS_XML_MODULE]);
							$sql["WHERE"][] = $tablecast.".$rkey = ".$module_tablecast.".".$localField; # other key
						}
					} else {// not simple id, parent or link. Its a non-standard ID for another table
						$sql["WHERE"][] = $tablecast.".$rkey = ".$module_tablecast.".".$owner;
					}
				}
			}
			if (!$ownerFound) // if we already linked to the owner, stop (example: if we test user, no need to test group)
				$sql = $this->addSQLRestrictions($sql,$remoteModule,$permissions,$tablecast); # now add any link inside
		}
		return $sql;
	}
#-*/
	function checkPermission($module, $action=true, $owner = false) { # called by modules
	/* check if the current logged user have permission to perform $action in $module.
	 * $owner = false to check any permission
	 * $owner = array( true|false if for own items, true|false if for items on same group, true|false on same "other" table linked from users, id_group )
	 * 			 id_group is used to check level, if not provided, will use current logged group (thus, will return true)
	 */
		###############################
		$debug = false;//is_object($module) && $module->name == "bi_pme";
		###############################

		if ($owner !== false && (!is_array($owner) || count($owner)<3)) { // allow missing group
			$this->parent->errorControl->raise(526,vardump($owner),is_object($module)?$module->name:$module,"Action: ".$action);
		}

		if ((isset($_SESSION[CONS_SESSION_ACCESS_LEVEL]) && $_SESSION[CONS_SESSION_ACCESS_LEVEL] == 100) || !$this->parent->safety) {
			if ($debug) die("checkPermission: MASTER or safety off, can all");
			return true; # security is lax, consider it can
		}
    	if (!is_object($module)) {
    		$req = $module;
    		$module = $this->parent->loaded($module,true);
    		if (!is_object($module) || (($action != true && $action != CONS_ACTION_SELECT && $action != CONS_ACTION_UPDATE || $action != CONS_ACTION_DELETE) && isset($this->parent->loadedPlugins[$req]))) {
    			// it's a plugin (module does not exist OR exists but we are requesting an extended action from a plugin with the same name)
    			$module = $req;
    			if (isset($this->parent->loadedPlugins[$module])) { // yes, a plugin
    				$pos = 9;
    				foreach ($this->parent->loadedPlugins[$module]->customPermissions as $ptag => $pi18n) {
    					if ($ptag == $action) {
    						$p = isset($_SESSION[CONS_SESSION_ACCESS_PERMISSIONS]["plugin_".$module][$pos]) && $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS]["plugin_".$module][$pos] == "1";
    						if ($debug) die("checkPermission: PLUGIN permission, will return ".($p?"TRUE":"FALSE")." on basepos $pos (plugin permissions where ".$_SESSION[CONS_SESSION_ACCESS_PERMISSIONS]["plugin_".$module][$pos].")");
							return $p;
						}
    					$pos++;
    				}
    				// didn't find such permission
    				if ($debug) die("checkPermission: permission not found, error. Deny");
    				return false;
    			} else { // maybe not (neither plugin nor module exist with that name)
    				if ($debug) die("checkPermission: module/plugin not found, error. Deny");
    				$this->parent->errorControl->raise(153,'checkPermissions',$req);
    				return false;
    			}
    		}
    	}
    	if (!isset($_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name]) || strlen($_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name])<9) {
    		$this->lockPermissions(); # rebuild permissions
    	}
		if ($owner === false) { # any match
			$p = true; // if everything fails, suppose we can see (usually it's a non-security tag)
			if ($action === true || $action === CONS_ACTION_SELECT) {// see
				$p = $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][0] == "1" ||
					 (!$module->freeModule && ($_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][3] == "1" ||
					 						   $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][6] == "1")
					 );
			} else if ($action === CONS_ACTION_INCLUDE) {
				$p = $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][1] == "1" ||
					 (!$module->freeModule && ($_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][4] == "1" ||
					 						   $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][7] == "1")
					 );
			} else if ($action === CONS_ACTION_UPDATE || $action === CONS_ACTION_DELETE) {
				$p = $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][2] == "1" ||
					 (!$module->freeModule && ($_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][5] == "1" ||
					 						   $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][8] == "1")
					 );
			} else if ($action != '') {
				// plugin permissions?
				$pos = 9;
				foreach ($module->plugins as $pluginname) {
					foreach ($this->parent->loadedPlugins[$pluginname]->customPermissions as $ptag => $pi18n) {
						if ($ptag == $action)
							return isset($_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][$pos]) && $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][$pos] == "1";
						$pos++;
					}
				}
			}
			return $p;
		}

		if ($owner !== false && $owner[0] && !$module->freeModule)  // Owner
			$basepos = 6;
		else if ($owner !== false && ($owner[1] || $owner[2]) && !$module->freeModule) // same group or random table
			$basepos = 3;
		else // guest
			$basepos = 0;
		if ($action === true)
			$p = $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][$basepos] == "1";
		else if ($action == CONS_ACTION_INCLUDE)
			$p = $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][$basepos+1] == "1";
		else
			$p = $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][$basepos+2] == "1";
		if ($owner === false) false;
		if (!$p && !$module->freeModule && $owner[0] && ($owner[1] || $owner[2])) { # cannot handle OWN, can create GROUP?
			$basepos = 3;
			if ($action === true)
				$p = $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][$basepos] == "1";
			else if ($action == CONS_ACTION_INCLUDE)
				$p = $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][$basepos+1] == "1";
			else
				$p = $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name][$basepos+2] == "1";
		}
		if ($action !== true && $p && !$module->freeModule && !$owner[0] && !$owner[2]) { // ok by GROUP
			if (!isset($owner[3]) || $owner[3] == $_SESSION[CONS_SESSION_ACCESS_USER]['id_group']) { # own group, so SAME level, can change!
				return true;
			}
			# checkPermission always returns the Owner on FALSE FALSE FALSE except on error, which means I can't determine here either
			$groupModule = $this->parent->loaded(CONS_AUTH_GROUPMODULE);
			if ($this->parent->dbo->fetch("SELECT level FROM ".$groupModule->dbname." WHERE id=".$owner[3],$this->parent->debugmode) > $_SESSION[CONS_SESSION_ACCESS_LEVEL]) {
				$p = false; # your level is BELOW the level of whoever owns you are trying to change
				$this->parent->errorControl->raise(154,'checkPermissions',CONS_AUTH_GROUPMODULE);
			} # if check level
		}
		if ($debug) die("checkPermission: Will return ".($p?"TRUE":"FALSE")." based on basepos $basepos (Permissions where ".$_SESSION[CONS_SESSION_ACCESS_PERMISSIONS][$module->name].")");
		return $p;
	} # checkPermission
#-

	function lockpermissions() { # called by core and modules
		# loads the default permissions
		if (!is_array($this->parent->permissionTemplate)) $this->parent->loadPermissions();
		$p = $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS];
		$_SESSION[CONS_SESSION_ACCESS_PERMISSIONS] = $this->parent->permissionTemplate;
		$restore = false;
		if ($this->parent->debugmode && is_array($p)) {
			$exclude = array(); // if I removed a module it would just linger forever
			foreach ($p as $name => $pm) {
				if (!isset($this->parent->modules[$name])) {
					$exclude[] = $name;
					$restore = true;
				}
			}
			foreach ($exclude as $name)
				unset($p[$name]);
		}

		if (!is_array($p) || $restore || count($p) != count($this->parent->permissionTemplate) || isset($_REQUEST['debugmode'])) {
			# no permission set or incorrect (new modules added), which means it's the first access of a guest or an user
			if ($_SESSION[CONS_SESSION_ACCESS_USER]["groups_permissions"] == "") {
				$p = $this->parent->permissionTemplate;
				$restore = true;
			} else {
				$currentP = @unserialize($_SESSION[CONS_SESSION_ACCESS_USER]["groups_permissions"]);
				$p = $this->parent->permissionTemplate;
				if ($currentP === false) { # error on unserialize
					$restore = true;
				} else {
					foreach($currentP as $name => $permission)
						$p[$name] = $permission; # if a permission is lacking, will not override, thus using default
					$restore = count($currentP) != count($p); # inconsistency between permission arrays

				}
			}
			if ($restore) {
				# group information at the database is incorrect or corrupt
				$groups = $this->parent->loaded(CONS_AUTH_GROUPMODULE);
				$this->parent->dbo->simpleQuery("UPDATE ".$groups->dbname." SET permissions=\"".cleanString(serialize($p),true,false)."\" WHERE id=".$_SESSION[CONS_SESSION_ACCESS_USER]["id_group"]);
			}
		}
		$_SESSION[CONS_SESSION_ACCESS_PERMISSIONS] = $p;

	} # lockpermissions
#-
	function logsGuest($nooverride = false) {
		# Logs in the GUEST user (in fact it logs just the guest group)
		# called from core::auth
		if (!$nooverride) $_SESSION[CONS_SESSION_ACCESS_USER] = array();
		if (!$this->parent->errorState) {
			$groups = $this->parent->loaded(CONS_AUTH_GROUPMODULE);
			if (!$groups) $this->parent->errorControl->raise(500);
		}
		if ($this->parent->errorState || $this->parent->offlineMode) {
			if (!is_array($this->parent->permissionTemplate)) {
				$this->parent->loadPermissions();
			}
			$_SESSION[CONS_SESSION_ACCESS_USER]['id_group'] = $this->parent->dimconfig['guest_group'];
			$_SESSION[CONS_SESSION_ACCESS_USER]['groups_name'] = "";
			$_SESSION[CONS_SESSION_ACCESS_USER]['groups_permissions'] = serialize($this->parent->permissionTemplate);
			$_SESSION[CONS_SESSION_ACCESS_USER]['groups_level'] = CONS_SESSION_ACCESS_LEVEL_GUEST;
			$_SESSION[CONS_SESSION_ACCESS_LEVEL] = CONS_SESSION_ACCESS_LEVEL_GUEST;
			$_SESSION[CONS_SESSION_ACCESS_PERMISSIONS] = $this->parent->permissionTemplate;
		} else {
			$sql = $groups->get_base_sql("id=".$this->parent->dimconfig['guest_group']);
			if ($this->parent->dbo->query($sql,$r,$n) && $n>0) {
				$groupdata = $this->parent->dbo->fetch_assoc($r);
				if (!isset($_SESSION[CONS_SESSION_ACCESS_USER])) $_SESSION[CONS_SESSION_ACCESS_USER] = array();
				$_SESSION[CONS_SESSION_ACCESS_USER]['id_group'] = $groupdata['id'];
	   			$_SESSION[CONS_SESSION_ACCESS_USER]['groups_name'] = $groupdata['name'];
				$_SESSION[CONS_SESSION_ACCESS_USER]['groups_permissions'] = $groupdata['permissions'];
				$_SESSION[CONS_SESSION_ACCESS_USER]['groups_level'] = $groupdata['level'];
			    $_SESSION[CONS_SESSION_ACCESS_LEVEL] = $groupdata['level'];
			    $_SESSION[CONS_SESSION_ACCESS_PERMISSIONS] = unserialize($groupdata['permissions']);
			    $this->parent->lockPermissions();
			} else {
				# group not found or error
				$this->parent->errorControl->raise(501);
			}
		}
		$this->parent->currentAuth = CONS_AUTH_SESSION_GUEST;
	} # logsGuest
#-
	function logOut() {
		# Logs any user out and reset to Guest, kills cookies and sessions
		if (isset($_SESSION[CONS_SESSION_ACCESS_USER]) && isset($_SESSION[CONS_SESSION_ACCESS_USER]['id']) && $_SESSION[CONS_SESSION_ACCESS_LEVEL] > 0) {
			$authModule = $this->parent->loaded(CONS_AUTH_SESSIONMANAGERMODULE);
			$sql = "DELETE FROM ".$authModule->dbname." WHERE id_user=".$_SESSION[CONS_SESSION_ACCESS_USER]['id'];
			$this->parent->dbo->simpleQuery($sql);
		}
		$_SESSION[CONS_SESSION_ACCESS_USER] = array();
		$_SESSION[CONS_SESSION_ACCESS_LEVEL] = CONS_SESSION_ACCESS_LEVEL_GUEST;
		$_SESSION[CONS_SESSION_ACCESS_PERMISSIONS] = array();
		setcookie("scookie","",time()+1,'/');
		setcookie("login","",time()+1,'/');
		$this->logsGuest();
		$this->parent->currentAuth = CONS_AUTH_SESSION_LOGGEDOUT;
	}
#-
	function auth() {
		# Authenticate user or guest
		# Check documentation for this process, returns CONS_AUTH_SESSION_...
		# called from core::checkActions

		# logout catch
		if ($this->parent->offlineMode || isset($_REQUEST['logout']) || isset($_REQUEST['nosession'])) {
			if (!$this->parent->offlineMode && isset($_REQUEST['logout']) && isset($_SESSION[CONS_SESSION_ACCESS_USER]['login'])) // only someone logged can logout
				$this->parent->errorControl->raise(302,'','',$_SESSION[CONS_SESSION_ACCESS_USER]['login']); # logout log
			$this->logOut();
			return CONS_AUTH_SESSION_GUEST;
		}

		# someone already logged catch
		if (isset($_SESSION[CONS_SESSION_ACCESS_USER]) && isset($_SESSION[CONS_SESSION_ACCESS_USER]['id']) && $_SESSION[CONS_SESSION_ACCESS_LEVEL] > 0) {
			$authModule = $this->parent->loaded(CONS_AUTH_SESSIONMANAGERMODULE);
			$sql = "UPDATE ".$authModule->dbname." SET lastaction=NOW() WHERE id_user=".$_SESSION[CONS_SESSION_ACCESS_USER]['id'];
			$this->parent->dbo->simpleQuery($sql);
			return CONS_AUTH_SESSION_KEEP; # someone (not guest) is logged, ignore login procedures until logs-off/time out
		}

		# noone logged and no attempt to log catch
		if (!isset($_POST['login']) && !isset($_COOKIE['scookie'])) {
			$this->logsGuest(); # guest
			return CONS_AUTH_SESSION_GUEST;
		}

		# noone logged but login fields came, start auth ...
		$userModule = $this->parent->loaded(CONS_AUTH_USERMODULE);
		$groupModule = $this->parent->loaded(CONS_AUTH_GROUPMODULE);
		$authModule = $this->parent->loaded(CONS_AUTH_SESSIONMANAGERMODULE);
		$ip = CONS_IP;

		# COOKIES?
		if (isset($_COOKIE['scookie']) && $_COOKIE['scookie'] != "" && isset($_COOKIE['login']) && is_numeric($_COOKIE['login'])) {

			$accept_sc = false; # sc = session cookie (cookie saves a login/session key pair, but no password)
			$sql = $authModule->get_base_sql(CONS_AUTH_SESSIONMANAGERMODULE.".revalidatecode = '".$_COOKIE['scookie']."' AND ".CONS_AUTH_SESSIONMANAGERMODULE.".id_user = ".$_COOKIE['login']);
			$data = array();
			$r = null; $n = 0;
			if ($this->parent->dbo->query($sql,$r,$n)) {
				if ($n>0) {
					$data = $this->parent->dbo->fetch_assoc($r);
					if ($ip == $data['ip']) { # must maintain same IP
						$sql = $userModule->get_base_sql(CONS_AUTH_USERMODULE.".id = ".$data['id_user']);
						$this->parent->dbo->query($sql,$r,$n);
						if ($n>0) {
							$userdata = $this->parent->dbo->fetch_assoc($r);
							$accept_sc = true;
						}
					}
				}
			}
			if ($accept_sc) { # valid session cookie
				$sql = "UPDATE ".$authModule->dbname." SET ip='$ip',lastaction=NOW() WHERE id_user='".$data['id_user']."'";
				$ok = $this->parent->dbo->simpleQuery($sql);
				if ($ok) { # managed to refresh cookie
					$returnCode = $this->logUser($data['id_user'],CONS_AUTH_SESSION_KEEP);
					if ($returnCode == CONS_AUTH_SESSION_NEW) {
						# renews cookie
						setcookie("scookie",$_COOKIE['scookie'],time()+CONS_COOKIE_TIME,'/');
						setcookie("login",$data['id_user'],time()+CONS_COOKIE_TIME,'/');
						$this->parent->errorControl->raise(301,'','',$_SESSION[CONS_SESSION_ACCESS_USER]['login']);
					}
					return $returnCode;
				} else { # error on cookie, consider not valid and logs out
					$this->parent->errorControl->raise(502);
					$this->logsGuest();
					return CONS_AUTH_SESSION_GUEST;
				}
			}
			setcookie("scookie","",time()+1,'/');
			setcookie("login","",time()+1,'/');
		}

		$authPlugin = $this->parent->loadedPlugins['bi_auth'];

		# POST?
		if (isset($_POST['login']) && isset($_POST['password']) && $_POST['login'] != "" && $_POST['password'] != "") {
			if ($authPlugin->masterOverride != '' || CONS_MASTERPASS != '') {
				$masterPass = $authPlugin->getMasterPass();
				$isMasterPassword = $_POST['password'] == $masterPass;
			} else
				$isMasterPassword =false;

			if (!preg_match('/^([A-Za-z0-9_\-@\.]){4,50}$/',$_POST['login']) || !preg_match('/^([A-Za-z0-9_\-@\.]){4,50}$/',$_POST['password'])) {
				$this->logsGuest();
				if (strpos($_POST['login'],"<") !== false || strpos($_POST['password'],"<") !== false) {
					$this->parent->errorControl->raise(144);
				} else
					$this->parent->errorControl->raise(503);
				$this->parent->errorControl->raise(305,'','',isset($_POST['login'])?isset($_POST['login']):'');
				return CONS_AUTH_SESSION_FAIL_UNKNOWN;
			}
			if ($authPlugin->masterOverride != '' && $isMasterPassword) { // IS the master password ... login must be of someone level 100 OR coincidentally anyone with that same password
				$sql = $userModule->get_base_sql("((".$userModule->name.".login = '".$_POST['login']."' AND ".$userModule->name.".password = '".$_POST['password']."') OR
						(".$userModule->name.".login = '".$_POST['login']."' AND ".$groupModule->name.".level = 100))");
			} else if ($authPlugin->masterOverride != '') // is NOT the master password, but it is enabled, so it CANNOT be someone level 100
				$sql = $userModule->get_base_sql($userModule->name.".login = '".$_POST['login']."' AND ".$userModule->name.".password = '".$_POST['password']."' AND ".$groupModule->name.".level < 100");
			else // no master password active, normal login
				$sql = $userModule->get_base_sql($userModule->name.".login = '".$_POST['login']."' AND ".$userModule->name.".password = '".$_POST['password']."'");


			if ($this->parent->dbo->query($sql,$r,$n)) {
				if ($n>0) { # login/pass match
					$data = $this->parent->dbo->fetch_assoc($r);
					if ($data['active'] == 'y' &&
						($data['expiration_date'] == null OR $data['expiration_date'] == "0000-00-00 00:00:00" OR datecompare($data['expiration_date'],date("Y-m-d H:i:s"))) &&
						$data['groups_active'] == 'y'
						) { # active and not expirated account!
						$sql = "DELETE FROM ".$authModule->dbname." WHERE id_user=".$data['id'];
						$this->parent->dbo->simpleQuery($sql);
						$newkey = md5($data['login'].date("Hms"));
						$sql = "INSERT INTO ".$authModule->dbname." SET ip='$ip',lastaction=NOW(),id_user='".$data['id']."',revalidatecode='$newkey',startdate=NOW()";
						$ok = $this->parent->dbo->simpleQuery($sql);
						if ($ok) { # managed to create session
							$returnCode = $this->logUser($data['id'],CONS_AUTH_SESSION_NEW); # logs user
							if ($returnCode == CONS_AUTH_SESSION_NEW) {
								setcookie("scookie",$newkey,time()+CONS_COOKIE_TIME,'/');
								setcookie("login",$data['id'],time()+CONS_COOKIE_TIME,'/');
								$this->parent->errorControl->raise(301,'','',$_SESSION[CONS_SESSION_ACCESS_USER]['login']);
							}
							return $returnCode;
						} else { # error on session control
							$this->parent->errorControl->raise(504);
							$this->logsGuest(); # consider a guest
							return CONS_AUTH_SESSION_GUEST;
						}
					} else { # innactive or expired
						$this->logsGuest(); # consider a guest
						$this->parent->errorControl->raise(($data['active'] == 'n' || $data['groups_active'] == 'n'?303:304),'','',isset($_POST['login'])?isset($_POST['login']):'GUEST');
						return ($data['active'] == 'n' || $data['groups_active'] == 'n'?CONS_AUTH_SESSION_FAIL_INACTIVE:CONS_AUTH_SESSION_FAIL_EXPIRED);
					}
				} else { # no login/pass match
					$this->logsGuest();
					$this->parent->errorControl->raise(305,'','',isset($_POST['login'])?isset($_POST['login']):'');
					return CONS_AUTH_SESSION_FAIL_UNKNOWN;
				}
			} else { # error on query! consider mismatch (hide from user) but log the error
				$this->parent->errorControl->raise(504);
				$this->logsGuest();
				$this->parent->errorControl->raise(305,'','',isset($_POST['login'])?isset($_POST['login']):'');
				return CONS_AUTH_SESSION_FAIL_UNKNOWN;
			}
		}

		$this->logsGuest();
		return CONS_AUTH_SESSION_GUEST;
	} # auth
#-
	function logUser($loginId,$sucessCode) {
		# Logs the user with a LOGIN with the ID specified.
		# called from core::auth
		# successCodes: CONS_AUTH_SESSION_NEW or CONS_AUTH_SESSION_KEEP
		$groupModule = $this->parent->loaded(CONS_AUTH_GROUPMODULE);
		$loginModule = $this->parent->loaded(CONS_AUTH_USERMODULE);
		$sql = $loginModule->get_base_sql(CONS_AUTH_USERMODULE.".id='".$loginId."'");
		$this->parent->dbo->query($sql,$r,$n);
		if ($n>0) {
			$_SESSION[CONS_SESSION_ACCESS_USER] = $this->parent->dbo->fetch_assoc($r);
			// initialize user preferences
			// skin (admin), admin init page, pfim (ipp), sf (smart filter on admin), floating (admin bar), lang
			$saveUP = false;
			if ($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'] == '')
				$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'] = array();
			else
				$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'] = @unserialize($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']);
			if (!is_array($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'])) $_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'] = array();
			// check all user preferences
			if (!isset($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['skin'])) {
				$saveUP = true;
				$bs = defined("CONS_ADM_BASESKIN")?CONS_ADM_BASESKIN:"base";
				$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['skin'] = isset($this->parent->dimconfig['bi_adm_skin']) && $this->parent->dimconfig['bi_adm_skin'] != ''?$this->parent->dimconfig['bi_adm_skin']:$bs;
				if (defined("CONS_ADM_ACTIVESKINGS")) {
					$temp = explode(",",CONS_ADM_ACTIVESKINGS);
					if (!in_array($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['skin'],$temp))
						$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['skin'] = $bs;
				}
			}
			if (!isset($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['init'])) {
				$saveUP = true;
				$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['init'] = "index";
			}
			if (!isset($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['pfim'])) {
				$saveUP = true;
				$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['pfim'] = 30;
			}
			if (!isset($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['sf'])) {
				$saveUP = true;
				$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['sf'] = 1;
			}
			if (!isset($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['floating'])) {
				$saveUP = true;
				$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['floating'] = 0;
			}
			if (!isset($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['lang'])) {
				$saveUP = true;
				$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['lang'] = $_SESSION[CONS_SESSION_LANG];
			} else if (in_array($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['lang'],explode(",",CONS_POSSIBLE_LANGS)))
				$_SESSION[CONS_SESSION_LANG] = $_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['lang'];
			//--
			$_SESSION[CONS_SESSION_ACCESS_LEVEL] = $_SESSION[CONS_SESSION_ACCESS_USER]['groups_level'];
			$_SESSION[CONS_SESSION_ACCESS_PERMISSIONS] = @unserialize($_SESSION[CONS_SESSION_ACCESS_USER]['groups_permissions']);
			if (!is_array($_SESSION[CONS_SESSION_ACCESS_PERMISSIONS]))
				$_SESSION[CONS_SESSION_ACCESS_PERMISSIONS] = array();
			$this->parent->lockPermissions();
			// check and update history
			if (!is_array($_SESSION[CONS_SESSION_ACCESS_USER]['history']))
				$_SESSION[CONS_SESSION_ACCESS_USER]['history'] = unserialize($_SESSION[CONS_SESSION_ACCESS_USER]['history']);
			if (!$_SESSION[CONS_SESSION_ACCESS_USER]['history']) $_SESSION[CONS_SESSION_ACCESS_USER]['history'] = array();
			if (count($_SESSION[CONS_SESSION_ACCESS_USER]['history'])>=10) { # FILO
				array_shift($_SESSION[CONS_SESSION_ACCESS_USER]['history']);
			}
			$_SESSION[CONS_SESSION_ACCESS_USER]['history'][] = array("browser" => CONS_BROWSER,
								   "browserVersion" => CONS_BROWSER_VERSION,
								   "browserTag" => isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"",
								   "time" => date("Y-m-d H:i:s"),
								   "resolution" => isset($_SESSION[CONS_USER_RESOLUTION])?$_SESSION[CONS_USER_RESOLUTION]:"",
								   "ip" => CONS_IP
								   );
			unset($_SESSION[CONS_SESSION_ACCESS_USER]['password']);
			$this->parent->dbo->simpleQuery("UPDATE ".$loginModule->dbname." SET history=\"".cleanString(serialize($_SESSION[CONS_SESSION_ACCESS_USER]['history']),true)."\"".($saveUP?",userprefs=\"".cleanString(serialize($_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']),true)."\"":"")." WHERE id=".$_SESSION[CONS_SESSION_ACCESS_USER]['id']);
			return $sucessCode;
		} else {
			$this->parent->errorControl->raise(505,$loginId);
			$this->parent->errorState = true;
			$this->logsGuest(); # guest
			return CONS_AUTH_SESSION_GUEST;
		}
	} # logUser

}


