<?

	if (!isset($_REQUEST['module']) || $core->loaded($_REQUEST['module']) === false) {
		$core->errorControl->raise(512,"list",(isset($_REQUEST['module'])?$_REQUEST['module']:''));
		$core->action = "404";
		$_REQUEST = array();
		$_GET = array();
		$_POST = array();
		return;
	} else if (isset($_REQUEST['vaction']) && $_REQUEST['vaction'] != '') {
		switch ($_REQUEST['vaction']) {
			case "mark": // mark all/none (mode true is all)
				$mode = isset($_REQUEST['markmode']) && $_REQUEST['markmode'] == 'true'; // mark all
				if (!$mode) {
					echo ","; // signal that none is selected
					$this->parent->close();
					$_REQUEST['notitle'] = 1; // lightweight the call
				}
			break;
			case "labelprint": // bi_labels
				$core->action = "labels_print"; // bi_labels will handle this
				$core->layout = 1;
				break;
			case "multiple": // multiple edit
				$core->action = "edit";
				$core->storage['actionflag'] = "multiedit";
				break;
			case "delete": // delete multiple
				$module = $core->loaded($_REQUEST['module']);
				@set_time_limit(CONS_TIMELIMIT*2);
				$keys = array();
				$keysdata = array();
				$ereg_pattern = "^";
				$pos = 0;
				$keyscount = count($module->keys);
				$theKeys = explode(",",$_REQUEST['multiSelectedIds']);
				foreach($module->keys as $name) {
					$keys[$pos] = $name;
					switch($module->fields[$name][CONS_XML_TIPO]) {
						case CONS_TIPO_INT:
						case CONS_TIPO_LINK:
							$ereg_pattern .= "([0-9]+)_";
							break;
						case CONS_TIPO_FLOAT:
							$ereg_pattern .= "([0-9]+)(\.([0-9]+))?_";
							break;
						case CONS_TIPO_VC:
						case CONS_TIPO_ENUM:
						case CONS_TIPO_TEXT:
							$ereg_pattern .= "([^_]+)_"; # <-- what if enum/text has "_" ?
							break;
						case CONS_TIPO_DATE:
							$ereg_pattern .= "([0-9]{4}\-[0-9]{2}\-[0-9]{2})_";
							break;
						case CONS_TIPO_DATETIME:
							$ereg_pattern .= "([0-9]{4}\-[0-9]{2}\-[0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})_";
							break;
					}
					$pos++;
				}
				$ereg_pattern = substr($ereg_pattern,0,strlen($ereg_pattern)-1)."\$";
				foreach($theKeys as $ids) {
					if ($ids != "" && preg_match('/'.$ereg_pattern.'/',$ids,$regs)) {
						for($pos=0;$pos<$keyscount;$pos++)
							$keysdata[$keys[$pos]] = $regs[$pos+1];
						$core->runAction($module,CONS_ACTION_DELETE,$keysdata);
						if (!$core->errorState) {
							$core->log[] = str_replace("{#}",$core->langOut($module->name),$core->langOut('delete_sucesso'))." ($ids)";
							$core->setLog(CONS_LOGGING_SUCCESS);
						} else {
							$core->setLog(CONS_LOGGING_ERROR);
							$core->errorState = false; # else will abort other dels
						}
					}
				}
				$core->action = "list";
				$_REQUEST = array("module" =>$module->name);
				$core->headerControl->internalFoward("list.php?module=".$module->name);
				break;
			case "reorder": // reorder selected
				$core->action = "reorder";
				break;
			case "linker": // show history for selected items (this is ajax only)
				//query = "list.php?haveinfo=1&layout=2&module={linkermodule}&vaction=linker&started={module}&ids=" + items + "&toid=" + $('linker_div_pop').value;
				// module = the linker module
				// started = which module is requesting the link
				// ids = comma delimited ids from started to relate with
				// toid = id to relate with
				$lmod = $core->loaded($_REQUEST['module']);
				$sql = "INSERT INTO ".$lmod->dbname." SET ";
				$usedid = false;
				foreach ($lmod->keys as $key) {
					$sql .= $key."=\"".($lmod->fields[$key][CONS_XML_MODULE] == $_REQUEST['started'] && !$usedid?"{id}":$_REQUEST['toid'])."\",";
					if ($lmod->fields[$key][CONS_XML_MODULE] == $_REQUEST['started']) $usedid = true; // already used the id. This guarantees toid is used when the relation is with the SAME module (A<->A)
				}
				$ok = false;
				$sql = substr($sql,0,strlen($sql)-1); // removes last ,
				if ($lmod !== false) {
					$ids = explode(",",str_replace(",,",",",$_REQUEST['ids']));
					foreach ($ids as $id) {
						if ($id != '') {
							$ok = true;
							$m= str_replace("{id}",$id,$sql);
							$core->dbo->simpleQuery($m);
						}
					}
				}
				echo $ok?"o":"e";
				$core->close();
				break;
		}
	}

?>