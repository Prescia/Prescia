<?

	if (isset($_REQUEST['haveinfo'])) {
		$module = $core->loaded($_REQUEST['module']);
		$field = $_REQUEST['field'];
		$red = $_REQUEST['reduction']; # main image was reduced to this %

		# test each thumbnail
		$mainFile = CONS_FMANAGER.$module->name."/".$field."_";
		$thumbStart = CONS_FMANAGER.$module->name."/t/".$field."_";
		foreach ($module->keys as $key) {
			$mainFile .= $_REQUEST[$key]."_";
			$thumbStart .= $_REQUEST[$key]."_";
		}
		$mainFile .= "1";
		if (!locateFile($mainFile,$ext)) {
			$core->log[] = "Image not found: ".$mainFile;
			return;
		}
		$total = isset($module->fields[$field][CONS_XML_THUMBNAILS]) ? count($module->fields[$field][CONS_XML_THUMBNAILS]) : 1;

		$WM_TODO = array();
		if (isset($module->fields[$field][CONS_XML_TWEAKIMAGES])) {
			foreach ($module->fields[$field][CONS_XML_TWEAKIMAGES] as $c => $WM) {
				// stamp:over(filename@x,y)[r] # [r] not implemented yet
				// stamp:under(filename@x,y)[r]
				// croptofit
				// might have multiple with + separator
				$TODO = array();
				$WM = explode("+",$WM);
				foreach ($WM as $thisWM) {
					$concept = explode(":",$thisWM);
					if ($concept[0] == "stamp") {
						$thisTODO = array();
						$stamptype = explode("(",$concept[1]); // ...(...@x,y)R
						$parameters = explode(")",$stamptype[1]); // ...@x,y)R
						$stamptype = $stamptype[0];
						$thisTODO['isBack'] = $stamptype == "under";
						$resample = (isset($parameters[1]) && $parameters[1] == "r");
						$parameters = $parameters[0];
						$parameters = explode("@",$parameters); // ...@x,y
						$parameters[1] = explode(",",$parameters[1]); // x,y
						$thisTODO['position'] = $parameters[1];
						$thisTODO['filename'] = CONS_INSTALL_ROOT.CONS_PATH_PAGES.$_SESSION['CODE']."/files/".$parameters[0];
						if ($resample && isset($module->fields[$field][CONS_XML_THUMBNAILS]))
							$thisTODO['resample'] = explode(",",$module->fields[$field][CONS_XML_THUMBNAILS][$c]);
						$TODO[] = $thisTODO;
					} else if ($concept[0] == "croptofit") {
						$TODO[] = "C";
					}
				}
				$WM_TODO[$c] = $TODO;
			}
		}

		if ($total > 0) {
			if (!function_exists("cropImage")) include_once CONS_PATH_INCLUDE."imgHandler.php";
			for ($c=0;$c<$total;$c++) {
				if ($_REQUEST['thumbchanges'.$c] != "") {
					#came a new position for this thumb!
					#crops the image according prediction
					$data = explode(";",$_REQUEST['thumbchanges'.$c]);
					$position = explode(",",$data[0]);
					if ($position[0]<0) $position[0] = 0;
					if ($position[1]<0) $position[1] = 0;
					$size = explode(",",$data[1]);
					/*
					echo $c."=>".$_REQUEST['thumbchanges'.$c]."<br/>";
					echo ".... new size = ".ceil($size[0]/$red)."x".ceil($size[1]/$red)."<br/>";
					echo ".... crop start = ".ceil($position[0]/$red)."x".ceil($position[1]/$red)."<br/>";
					//*/
					$destFile = $c == 0 ? $mainFile : $thumbStart.($c+1);
					$size[0] = ceil($size[0]/$red);
					$size[1] = ceil($size[1]/$red);
					$position[0] = ceil($position[0]/$red);
					$position[1] = ceil($position[1]/$red);

					$ok = cropImage($mainFile,$destFile,$position,$size,explode(",",$module->fields[$field][CONS_XML_THUMBNAILS][$c]),0,isset($WM_TODO[$c])?$WM_TODO[$c]:array());

				}
			}
		}
		$core->action = "edit_thumbnails"; // if internalFoward disabled, go to edit pane
		$qs = array();
		foreach ($module->keys as $key) {
			$qs[] = $key . "=" . $_REQUEST[$key];
		}
		$qs = "module=".$module->name."&field=".$_REQUEST['field']."&".implode("&",$qs);
		$core->headerControl->internalFoward("edit_thumbnails.html?".$qs);
	}

