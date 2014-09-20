<?

	$core->layout = 2;
	if (!isset($_REQUEST['module']) || !isset($_REQUEST['filter'])) echo "ERRO";
	else {
		$module = $core->loaded($_REQUEST['module']);
		if ($module === false) echo "ERRO";
		else {
			include_once(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/importer.php");
			$importerObj = new Cimporter($core);
			$iFields = $importerObj->fields($module);
			if (isset($_REQUEST['sep']))
				$sep = $_REQUEST['sep'];
			else
				$sep = ";";
			if (isset($_REQUEST['quote']))
				$quote = $_REQUEST['quote']=="NULL"?"":$_REQUEST['quote'];
			else
				$quote = "\"";
			$filter = explode(",",$_REQUEST['filter']);
			if (isset($_REQUEST['size'])) {
				$size = explode(",",$_REQUEST['size']);
			} else
				$size = false;
			$sample = "";
			$c = 0;
			foreach ($filter as $id) {
				if (!is_numeric($id) || $id == 0) {
					$idx = -1;
					if ($id == "$") $sample .= "(id)";
				} else
					$idx = $importerObj->getField($iFields,$id);
				if ($idx != -1) {
					if ($iFields[$idx]['type'] == CONS_TIPO_VC || $iFields[$idx]['type'] == CONS_TIPO_TEXT) // text, put quotation marks
						$sample .= $quote.$core->langOut($iFields[$idx]['name']).$quote;
					else if ($iFields[$idx]['type'] != 0 && $iFields[$idx]['type'] != CONS_TIPO_LINK) // not text nor a link
						$sample .= $core->langOut($iFields[$idx]['name']);
					else { # remote or linker module, we want the remote so same code
						# REMOTE module:
						#   [ THIS MODULE ] ----> [ REMOTE MODULE ]
						# LINKER module:
						#	[ THIS MODULE ]  <--- [ LINKER MODULE ] ---> [ REMOTE MODULE ]
						$rModule = $core->loaded($iFields[$idx]['remoteModule']);
						$sample .= $core->langOut($iFields[$idx]['name'])." (".$core->langOut($rModule->title)."/id)";
					}
				}
				if ($sep != "")
					$sample .= "<b>".$sep."</b>";
				if ($size !== false)
					$sample .= "(".$size[$c]." caracteres) ";
				$c++;
			}
			echo $sample;
		}
	}
	$core->close();



