<?php

/** @var CPrescia $core Runtime payload context injected by the framework. */
		$requestedModule = isset($_POST['module']) && is_string($_POST['module']) ? $_POST['module'] : '';
		if ($requestedModule === '' || !($module = $core->loaded($requestedModule)) || !$module) {
		# master check if this is a valid module
			$core->errorControl->raise(512,"reorder",$requestedModule);
		$core->action = "404";
		$_REQUEST = array();
		$_GET = array();
		$_POST = array();
		return;
	}

	
		if (isset($_POST['haveinfo']) && $_POST['haveinfo'] == 1 && isset($_POST['new_order']) && is_string($_POST['new_order'])) {
			$changed = array();
			$ok = true;
			$minId = isset($_POST['min_id']) && is_numeric($_POST['min_id']) ? (int)$_POST['min_id'] : 1;
	        if ($core->authControl->checkPermission($module,CONS_ACTION_UPDATE)) {
	            $no = explode("=",$_POST['new_order']); // (order_ul[]=#&)
            array_shift($no); // first is useless and only define it as an array (order_ul[])
            for ($c=0;$c<count($no);$c++) {
              // #&order_ul[] ,except the last which has only the number
              if ($c != (count($no)-1)) {
                $no[$c] = explode("&",$no[$c]);
                $id = $no[$c][0];
              } else {
                $id = $no[$c];
              }
	              $id = is_numeric($id) ? (int)$id : 0;
	              $sql = "UPDATE ".$module->dbname." SET ordem=? WHERE ".$module->keys[0]."=?";
	              if (!in_array($id,$changed)) { // sometimes scriptaculos can send a repeated item
					$r = false;
					$n = 0;
					$ok = $ok && $core->dbo->queryPrepared($sql, 'ii', array($c + $minId, $id), $r, $n);
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
