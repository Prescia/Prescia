<?
	return; // em manutenção

	// erro que deu: na linha 38 não achou $fT[$idx], o $iFields pode estar sendo gerado com defeito?

	if (isset($_POST['haveinfo'])) {
		$module = $core->loaded($_POST['module']);
		if ($module !== false) {
			$pattern = "";
			$core->dbo->quickmode =true;
			include_once(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/importer.php");
			$importerObj = new Cimporter($core);
			$iFields = $importerObj->fields($module,true);
			$fT = array(0=>-1);
			foreach ($iFields as $idx => $field) {
				$fT[$field['#']] = $idx;
			}
			if ($_REQUEST['imode'] == 'cvs') { // csv ><!
				//sepDados
				//sepQuote
				//cvsOrder
				$replacer = explode(",",str_replace(" ","",stripslashes($_POST['cvsOrder'])));
				$fields = count($replacer);
				$sepData = trim(stripslashes($_POST['sepDados']));
				$sepQuote = trim(stripslashes($_POST['sepQuote']));
				$sql = "SELECT * FROM ".$module->dbname;
				$output = "";
				if ($core->dbo->query($sql,$r,$n)) {
					$core->layout = 2;
					header("Content-Description: File Transfer");
					header("Pragma: public");
        			header("Cache-Control: public,max-age=0,s-maxage=0");
        			header("Content-type: text/plain; charset=utf-8");
        			header("Content-Disposition: inline; filename=\"".$module->name.".csv\"");
					for($c=0;$c<$n;$c++) {
						$data = $core->dbo->fetch_assoc($r);
						foreach ($replacer as $idx) {
							if (!isset($fT[$idx])) {
								$outData = "?"; // <-------------------------------------------- too many "?" in export? check here. Wrong $iFields on importer
							} else {
								$idx = $fT[$idx];
								if ($idx == -1)
									$outData = "";
								else if ($iFields[$idx]['type'] == CONS_TIPO_VC ||$iFields[$idx]['type'] == CONS_TIPO_TEXT) {
									$data[$iFields[$idx]['name']] = str_replace("\n","\\n",$data[$iFields[$idx]['name']]);
									$outData = $sepQuote.str_replace($sepQuote,"\\".$sepQuote,$data[$iFields[$idx]['name']]).$sepQuote;
								} else
									$outData = $data[$iFields[$idx]['name']];
							}
							echo $outData.$sepData;
						}
						echo "\r\n";
					}
					$core->close(true);
				} else {
					$core->log[] = "Error running SQL command";
					$core->setLog(CONS_LOGGING_ERROR);
				}
			} else if ($_REQUEST['imode'] == 'fix') {
				//fixblock
				//fixOrder
				$replacer = explode(",",str_replace(" ","",stripslashes($_POST['fixOrder'])));
				$fields = count($replacer);
				$sizes = explode(",",str_replace(" ","",stripslashes($_POST['fixblock'])));
				$sizes[] = 0;
				$sql = "SELECT * FROM ".$module->dbname;
				$output = "";
				if ($core->dbo->query($sql,$r,$n)) {
					$core->layout = 2;
					header("Content-Description: File Transfer");
					header("Pragma: public");
        			header("Cache-Control: public,max-age=0,s-maxage=0");
        			header("Content-type: text/plain; charset=utf-8");
        			header("Content-Disposition: inline; filename=\"".$module->name.".csv\"");
					for($c=0;$c<$n;$c++) {
						$data = $core->dbo->fetch_assoc($r);
						foreach ($replacer as $idx) {
							if (!isset($fT[$idx])) {
								$outData = "?"; // <-------------------------------------------- too many "?" in export? check here. Wrong $iFields on importer
							} else {
								$Ridx = $fT[$idx];
								if ($Ridx == -1)
									$outData = "";
								else
									$outData = $data[$iFields[$Ridx]['name']];
							}
							echo strlen($outData)<$sizes[$idx]?str_pad($outData,$sizes[$idx]," ",STR_PAD_LEFT):substr($outData,0,$sizes[$idx]);
						}
						echo "\r\n";
					}
					$core->close(true);
				} else {
					$core->log[] = "Error running SQL command";
					$core->setLog(CONS_LOGGING_ERROR);
				}
			} else { // sql
				$core->layout = 2;
				header("Content-Description: File Transfer");
				header("Pragma: public");
				header("Cache-Control: public,max-age=0,s-maxage=0");
				header("Content-type: text/plain; charset=utf-8");
				header("Content-Disposition: inline; filename=\"".$module->name.".sql\"");
				$module->generateBackup(true);
				$core->close(true);
			}
		}

	}


