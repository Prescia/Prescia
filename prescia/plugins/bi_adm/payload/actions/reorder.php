<?
	if (!isset($_REQUEST['module']) || !($module = $core->loaded($_REQUEST['module'])) || !$module) {
		# master check if this is a valid module
		$core->errorControl->raise(512,"reorder",(isset($_REQUEST['module'])?$_REQUEST['module']:''));
		$core->action = "404";
		$_REQUEST = array();
		$_GET = array();
		$_POST = array();
		return;
	}

	
	if ($_REQUEST['haveinfo'] == 1 && isset($_REQUEST['new_order'])) {
		$module = $core->loaded($_REQUEST['module']);
		$changed = array(); 
		$ok = true;
		if (!isset($_REQUEST['min_id'])) $_REQUEST['min_id'] = 1;
        if ($core->authControl->checkPermission($module,CONS_ACTION_UPDATE)) {
            $no = explode("=",$_REQUEST['new_order']); // (order_ul[]=#&)
            array_shift($no); // first is useless and only define it as an array (order_ul[])
            for ($c=0;$c<count($no);$c++) {
              // #&order_ul[] ,except the last which has only the number
              if ($c != (count($no)-1)) {
                $no[$c] = explode("&",$no[$c]);
                $id = $no[$c][0];
              } else {
                $id = $no[$c];
              }
              $sql = "UPDATE ".$module->dbname." SET ordem=".($c + $_REQUEST['min_id'])." WHERE ".$module->keys[0]."=$id";
              if (!in_array($id,$changed)) { // sometimes scriptaculos can send a repeated item
				$ok = $ok && $core->dbo->simpleQuery($sql);
                array_push($changed,$id);
              }
            }
            $core->log[] = $core->langOut($ok?"reorder_ok":"reorder_failed");
			$core->setLog($ok?CONS_LOGGING_SUCCESS:CONS_LOGGING_ERROR);
        } else  {
        	$core->setLog(CONS_LOGGING_WARNING);
        	$core->log[] = $core->langOut("permission_denied");
        }
        $core->action = "list";
        $core->headerControl->internalFoward("list.php?module=".$module->name);
	}

