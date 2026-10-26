<?php

/** @var CPrescia $core Runtime payload context injected by the framework. */

	$module = $core->loaded($_REQUEST['module']);

	$core->template->assign("module",$module->name);

	# TODO: not working for multikeys
	
		// process the keys and choose only those filled
		$temp = isset($_REQUEST['multiSelectedIds'])?explode(",",str_replace(",,",",",$_REQUEST['multiSelectedIds'])):array();
		$_REQUEST['multiSelectedIds'] = array();
		$preparedParams = array();
		$preparedTypes = '';
		$preparedPlaceholders = array();
		foreach ($temp as $msi)
			if ($msi !== '' && filter_var($msi,FILTER_VALIDATE_INT) !== false) {
				$_REQUEST['multiSelectedIds'][] = (int)$msi;
				$preparedPlaceholders[] = '?';
				$preparedTypes .= 'i';
				$preparedParams[] = (int)$msi;
			}
		if (count($preparedPlaceholders) == 0) $preparedPlaceholders[] = 'NULL';

		// reads the items to select min and max id's to try and keep them in the same order region, and while at that, fill the template
	  	$sql = $module->get_base_sql($module->name.".".$module->keys[0]." IN (".implode(",",$preparedPlaceholders).")",$module->name.".".CONS_FIELD_ORDER." ASC");
	$sql['SELECT'] = array($module->name.".".$module->keys[0],$module->name.".".CONS_FIELD_ORDER,$module->name.".".$module->title." as title_select");
	
  	$min_id = 0;
	$item = $core->template->get("_item");
	$temp = "";
	$r = false;
	$n = 0;
		$core->dbo->queryPrepared($core->dbo->sqlarray_echo($sql),$preparedTypes,$preparedParams,$r,$n);
	for ($c=0;$c<$n;$c++) {
	$dados = $core->dbo->fetch_assoc($r);
		$temp .= $item->techo($dados);
		if ($min_id == 0) $min_id = $dados[CONS_FIELD_ORDER];
	}
	
	$core->template->assign("min_id",$min_id);
	$core->template->assign("_item",$temp);
	
