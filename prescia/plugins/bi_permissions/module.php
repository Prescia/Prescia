<?	# -------------------------------- Plugin permissions (Automatically added to groups)

class mod_bi_permissions extends CscriptedModule  {

	function loadSettings() {
		$this->name = "bi_permissions";
		#$this->parent->onMeta[] = $this->name;
		#$this->parent->onActionCheck[] = $this->name;
		#$this->parent->onRender[] = $this->name;
		#$this->parent->on404[] = $this->name;
		#$this->parent->onShow[] = $this->name;
		#$this->parent->onEcho[] = $this->name;
		#$this->parent->onCron[] = $this->name;
		$this->customFields = array("permissions");
	}

	function edit_parse($action,&$data) {

		$baseModule = $this->parent->loaded($this->moduleRelation);
		if ($action != CONS_ACTION_DELETE) {
			$allperm = $this->parent->permissionTemplate;
			if (!isset($data['permissions'])) { // can expect in checkboxes in format c_[module_name]_xx
				$this->parent->loadPermissions(); # makes sure it's loaded
				foreach ($this->parent->modules as $name => $pmodule) {
					$pointers = array('c_'.$name."_mr", // member
									  'c_'.$name."_mw",
									  'c_'.$name."_me",
									  'c_'.$name."_gr", // group
									  'c_'.$name."_gw",
									  'c_'.$name."_ge",
									  'c_'.$name."_or", // others
									  'c_'.$name."_ow",
									  'c_'.$name."_oe");
					$locker = "ccccccccc";
					if ($pmodule->permissionOverride != "")
						$locker = $pmodule->permissionOverride;
					if (!isset($allperm[$name]) || is_array($allperm[$name]) || strlen($allperm[$name]) < 9)
						$allperm[$name] = "000000000";
					for ($pos = 0; $pos < 9 ; $pos++) {
						if ($locker[$pos] == "c") { # only the ones I can select (others won't come at all)
							if (isset($data[$pointers[$pos]]))
								$allperm[$name][$pos] = '1';
							else
								$allperm[$name][$pos] = '0';
						} else if ($locker[$pos] == "a")
							$allperm[$name][$pos] = '1';
						else
							$allperm[$name][$pos] = '0';
					}
					// now checks custom permissions

					$pos = 9;
					foreach ($pmodule->plugins as $pluginname) {
						foreach ($this->parent->loadedPlugins[$pluginname]->customPermissions as $ptag => $pi18n) {
							if (isset($data['c_'.$name.'_'.$ptag]))
								$allperm[$name][$pos] = '1';
							else
								$allperm[$name][$pos] = '0';
							$pos++;
						}

					}
				}
				foreach ($this->parent->loadedPlugins as $pname => $plugin) {
					if (count($plugin->customPermissions) != 0) {
						$pos = 9;
						foreach ($plugin->customPermissions as $ptag => $pi18n) {
							if (isset($data['c_'.$pname.'_'.$ptag]))
								$allperm["plugin_".$pname][$pos] = '1';
							else
								$allperm["plugin_".$pname][$pos] = '0';
							$pos++;
						}
					}
				}
				$data['permissions'] = serialize($allperm);
			}

			if ($action == CONS_ACTION_UPDATE && $this->moduleRelation == CONS_AUTH_GROUPMODULE && $data['id'] == $_SESSION[CONS_SESSION_ACCESS_USER]['id_group']) {
				$_SESSION[CONS_SESSION_ACCESS_USER]['group_permissions'] = $data['permissions'];
				$_SESSION[CONS_SESSION_ACCESS_PERMISSIONS] = $allperm;
			}
		}
		return true;
	}

	function field_interface($field,$action,&$data) {

		if ($field == "permissions") {
			$output = "";
			$this->parent->loadPermissions(); // makes sure it's loaded
			$perm = array();
			$allperm = $this->parent->permissionTemplate; // get default permission array
			if (isset($data['permissions']) && $data['permissions'] != "")
				$perm = unserialize($data['permissions']); // loads
			// merge with standard permissions to make sure we have ALL permissions (example: a new module was added and this group still don't have permissions set?)
			foreach($perm as $name => $permission)
				$allperm[$name] = $permission; // if a permission is lacking, will not override, thus using default

			// load template
			$mytp = new CKTemplate($this->parent->template);
			$mytp->fetch(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/permission_field.html");
			$customPerm = $mytp->get("_custompermission");
			$objPerm = $mytp->get("_permission");

			foreach ($this->parent->modules as $name => $pmodule) {
				if ($pmodule->options[CONS_MODULE_SYSTEM]) continue; // cannot edit system modules
				$locker = "ccccccccc";
				if ($pmodule->permissionOverride != "") {
					$locker = $pmodule->permissionOverride;
					$hasSOME = false;
					for ($pos=0;$pos<9;$pos++) {
						if ($pmodule->permissionOverride[$pos] == "c") {
							$hasSOME = true;
							break;
						}
					}
				}
				if ($hasSOME) {
						$thisPermission = array(
						'title' => $this->parent->langOut($name).(defined("CONS_MODULE_PARTOF") && $pmodule->options[CONS_MODULE_PARTOF]!=''?" (".$this->parent->langOut($pmodule->options[CONS_MODULE_PARTOF]).")":""),
						'module' => $name,
						'mr_checked' => $allperm[$name][0]=="1" || $locker[0]=="a"?true:"",
						'mr_disabled' => $locker[0]!="c"?"disabled":"",
						'mw_checked' => $allperm[$name][1]=="1" || $locker[1]=="a"?true:"",
						'mw_disabled' => $locker[1]!="c"?"disabled":"",
						'me_checked' => $allperm[$name][2]=="1" || $locker[2]=="a"?true:"",
						'me_disabled' => $locker[2]!="c"?"disabled":"",
						'gr_checked' => $allperm[$name][3]=="1" || $locker[3]=="a"?true:"",
						'gr_disabled' => $locker[3]!="c"?"disabled":"",
						'gw_checked' => $allperm[$name][4]=="1" || $locker[4]=="a"?true:"",
						'gw_disabled' => $locker[4]!="c"?"disabled":"",
						'ge_checked' => $allperm[$name][5]=="1" || $locker[5]=="a"?true:"",
						'ge_disabled' => $locker[5]!="c"?"disabled":"",
						'or_checked' => $allperm[$name][6]=="1" || $locker[6]=="a"?true:"",
						'or_disabled' => $locker[6]!="c"?"disabled":"",
						'ow_checked' => $allperm[$name][7]=="1" || $locker[7]=="a"?true:"",
						'ow_disabled' => $locker[7]!="c"?"disabled":"",
						'oe_checked' => $allperm[$name][8]=="1" || $locker[8]=="a"?true:"",
						'oe_disabled' => $locker[8]!="c"?"disabled":""
						);

					$output .= $objPerm->techo($thisPermission);
				}
				$pos = 9;

				foreach ($pmodule->plugins as $pluginname) {
					foreach ($this->parent->loadedPlugins[$pluginname]->customPermissions as $ptag => $pi18n) {
						$thisPermission = array(
							'title' => $hasSOME ? '&nbsp;' : $this->parent->langOut($name).(defined("CONS_MODULE_PARTOF") && $pmodule->options[CONS_MODULE_PARTOF]!=''?" (".$this->parent->langOut($pmodule->options[CONS_MODULE_PARTOF]).")":""),
							'pname' => 'c_'.$name."_".$ptag,
							'ptitle' => $pi18n,
							'checked' => isset($allperm[$name][$pos]) && $allperm[$name][$pos] == '1' ? true : '',
						);
						$output .= $customPerm->techo($thisPermission);
						$pos++;
					}
				}

			}
			foreach ($this->parent->loadedPlugins as $pname => $plugin) { // stand alone plugins (no module)
				if (count($plugin->customPermissions) != 0 ) {
					$pos = 9;
					foreach ($plugin->customPermissions as $ptag => $pi18n) {
						$thisPermission = array(
							'title' => $pos == 9 ? $this->parent->langOut($pname): '',
							'pname' => 'c_'.$pname."_".$ptag,
							'ptitle' => $pi18n,
							'checked' => isset($allperm["plugin_".$pname][$pos]) && $allperm["plugin_".$pname][$pos] == '1' ? true : '',
						);
						$output .= $customPerm->techo($thisPermission);
						$pos++;
					}
				}
			}
			$mytp->assign("_permission",$output);
			$mytp->assign("_custompermission","");
			$output = $mytp->techo();
			if ($output == '') return false;
			return $output;

		}
		return true;
	}

}

