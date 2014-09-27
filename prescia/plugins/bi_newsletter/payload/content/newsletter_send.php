<?

	if (!$core->authControl->checkPermission('bi_newsletter')) {
		$core->fastClose(403);
		return;
	}

	$nlPlugin = $core->loadedPlugins['bi_newsletter'];
	// lists newsletters
	$core->runContent('bi_newsletter',$core->template,'','_newsletters');

	// CKE
	$core->addLink("ckfinder/ckfinder.js",true);
	$core->addLink("ckeditor/ckeditor.js",true);

	// groups
	$mod = $core->loaded('bi_NEWSGROUP');
	$modRec = $core->loaded('bi_NEWSLINK');
	$sql = $mod->get_base_sql();
	$sql['LEFT'][] = $modRec->dbname." as nl ON nl.id_newsgroup = bi_newsgroup.id";
	$sql['SELECT'][] = "count(nl.id_recipient) as dest";
	$sql['GROUP'][] = "bi_newsgroup.id";
	$tree = $mod->getContents("",$mod->title,"","",$sql);
	$core->template->getTreeTemplate("_sdirs","_ssubdirs",$tree);

	// filters
	$modDest = $core->loaded($nlPlugin->recipientList);
	$tp = $core->template->get("_filters");
	$temp = "";
	$validnames = "";
	foreach ($modDest->fields as $fname => $field) {
		if ($fname != "id" && $fname != "receber_newsletter" && $fname != "data_cadastro") {
			$exclude = array();
			$options = "";
			switch ($field[CONS_XML_TIPO]) {
				case CONS_TIPO_INT:
				case CONS_TIPO_FLOAT:
					$exclude[] = "_string";
					$exclude[] = "_select";
					break;
				case CONS_TIPO_VC:
				case CONS_TIPO_TEXT:
					$exclude[] = "_select";
					break;
				case CONS_TIPO_DATE:
				case CONS_TIPO_DATETIME:
				case CONS_TIPO_UPLOAD:
				case CONS_TIPO_OPTIONS:
					continue 2;
				case CONS_TIPO_ENUM:
					$exclude[] = "_string";
					$exclude[] = "_text";
					preg_match("@ENUM \(([^)]*)\).*@",$field[CONS_XML_SQL],$regs);
					$xtp = new CKTemplate($core->template);
					$xtp->tbreak("<option value=\"{enum}\">{enum_translated}</option>");
					$enums = explode(",",$regs[1]);
					foreach ($enums as $x) {
						$x = str_replace("'","",$x);
						$db = array('enum' => $x,
									'enum_translated' => $core->langOut($x),
						);
						$options .= $xtp->techo($db);
					}
					unset($xtp);

					break;
				case CONS_TIPO_LINK:
					$exclude[] = "_string";
					$exclude[] = "_text";
					$remoteMod = $core->loaded($field[CONS_XML_MODULE]);
					$xtp = new CKTemplate($core->template);
					$xtp->tbreak("{_loop}<option value=\"{".$remoteMod->keys[0]."}\">{".$remoteMod->title."}</option>{/loop}");
					$remoteMod->runContent($xtp,"","_loop",false);
					$options = $xtp->techo();
					unset($xtp);
					break;
			}
			$temp .= $tp->techo(array('name' => $fname,'options' => $options),$exclude);
			$validnames .= "validNames.push('$fname');\n";
		}
	}
	$core->template->assign("_filters",$temp);
	$core->template->assign("validnames",$validnames);