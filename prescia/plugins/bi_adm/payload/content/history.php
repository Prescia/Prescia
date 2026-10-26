<?php

/** @var CPrescia $core Runtime payload context injected by the framework. */

	if (!$core->authControl->checkPermission('bi_adm','can_undo'))
		$core->fastClose(403);

	if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) $core->fastClose(404);
	
	// load data from undo table and echo on the template
	$undodata = $core->runContent('bi_undo',$core->template,$_REQUEST['id']);
	if ($undodata === false)
		$core->fastClose(404);
	
	// now get just the history array
	$data = presciaSafeUnserialize($undodata['history']);
	if ($data === false) // history array corrupt!
		$core->errorControl->raise(519,$undodata['modulo'],$undodata['modulo']);

	// file array?
	$files = presciaSafeUnserialize($undodata['files']);
	
	$module = $core->loaded($undodata['modulo']);
	if ($module === false) // invalid module 
		$core->errorControl->raise(518,$undodata['modulo'],$undodata['modulo']);

	$missingField = false;
	$temp = "";
	$mytp = $core->template->get("_field");
	foreach ($module->fields as $name => &$field) {
		$fieldDT = array('name' => $name,
						'content' => '');
		switch ($field[CONS_XML_TIPO]) {
			case CONS_TIPO_DATE:
				$fieldDT['conteudo'] = fd($data[$name],$core->intlControl->getDate());
			break;
			case CONS_TIPO_DATETIME:
				$fieldDT['conteudo'] = fd($data[$name],"H:i:s ".$core->intlControl->getDate());
			break;
			case CONS_TIPO_ENUM:
				$fieldDT['conteudo'] = $core->langOut($data[$name]);
			break; 
			case CONS_TIPO_UPLOAD:
				$keys = "";
				foreach ($module->keys as $key)
					$keys = $data[$key]."_";
				$keys = substr($keys,0,strlen($keys)-1); // remove last _
				$file = CONS_FMANAGER."_undodata/".$module->name.$name."_".$keys;
				$ext = "";
				foreach ($files as $undofile => $true) {
					if (strpos($undofile,$module->name.$name."_".$keys) !== false) {
						$ext = explode(".",$undofile);
						$ext = array_pop($ext);
						break;
					}
				}
				
				if ($ext != "") {
					$fieldDT['conteudo'] = $core->langOut('file').": ".$ext;
				} else
					$fieldDT['conteudo'] = $core->langOut('nofile');
					
			break;
				case CONS_TIPO_LINK:
					$rmodule = $core->loaded($field[CONS_XML_MODULE]);
					$where = '';
					$whereTypes = '';
					$whereParams = array();
					if (!$module->getRemotePreparedKeys($rmodule,$where,$whereTypes,$whereParams,$data))
						continue 2;
				
					$sql = "SELECT ".$rmodule->title." FROM ".$rmodule->dbname." as ".$rmodule->name." WHERE ".$where;
								
					$r = false;
					$n = 0;
					if ($core->dbo->queryPrepared($sql,$whereTypes,$whereParams,$r,$n) && $n == 1) { 
					$fieldDT['conteudo'] = $core->dbo->fetch_row($r);
					$fieldDT['conteudo'] = $fieldDT['conteudo'][0];
				} else { // none or more than two ... can't decide thus ambiguous
						$fieldDT['conteudo'] = "<span style=\"color:#ff0000\">".$where." ?</span>";
					$missingField = true;
				}				
			break;			
			default:
				$fieldDT['conteudo'] = $data[$name];
			break;
		}
		$temp.=$mytp->techo($fieldDT);
	}
	$core->template->assign("_field",$temp);
	if (!$missingField) $core->template->assign("_fault");
	else $core->template->assign("_readytorestore");
