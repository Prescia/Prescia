<?

	define("CONS_MAX_MUP",100); // maximum files to process WARNING (will still process)

	if (!isset($_REQUEST['module']) || !($module = $core->loaded($_REQUEST['module'])) || !$module) {
		# master check if this is a valid module
		$core->errorControl->raise(512,"edit",(isset($_REQUEST['module'])?$_REQUEST['module']:''));
		$core->action = "404";
		$_REQUEST = array();
		$_GET = array();
		$_POST = array();
		return;
	}

	if (isset($_POST['haveinfo']) && isset($_REQUEST['vaction'])) {
		$module = $core->loaded($_REQUEST['module']);
		$kS = "";
		$ok = false;
		switch ($_POST['vaction']) {
			case "add": // add new item
				$ok = $core->runAction($module,CONS_ACTION_INCLUDE,$_POST);
				if ($ok) {
					foreach ($module->keys as $key) {
						if (strpos($module->fields[$key][CONS_XML_SQL],"AUTO_INCREMENT")!==false || !isset($_POST[$key])) {
							# main id is auto_increment (thus came at lastReturnCode) or not set (also set at lastReturnCode, probably a key controled by "parent" flag)
							$_POST[$key] = $core->lastReturnCode;
						}
						$kS .= $key."=".$_POST[$key]."&";
					}
					$core->log[] = str_replace("{#}",$core->langOut($module->name),$core->langOut('new_sucesso')).($module->title != "" && isset($_REQUEST[$module->title])?": ".truncate($_REQUEST[$module->title],500):"");
					$core->setLog(CONS_LOGGING_SUCCESS);
				} else {
					$core->setLog(CONS_LOGGING_ERROR);
					$_SESSION[CONS_SESSION_LOG_REQ] = $_POST;
					unset($_SESSION[CONS_SESSION_LOG_REQ]['haveinfo']);
					unset($_SESSION[CONS_SESSION_LOG_REQ]['vaction']);
				}
			break;
			case "edit": // edit an item

				$ok = $core->runAction($module,CONS_ACTION_UPDATE,$_POST);

				if ($ok) {
					foreach ($module->keys as $key)
						$kS .= $key."=".$_POST[$key]."&";
					$core->log[] = str_replace("{#}",$core->langOut($module->name),$core->langOut('edit_sucesso')).($module->title != "" && isset($_REQUEST[$module->title])?": ".truncate($_REQUEST[$module->title],500):"");
					$core->setLog(CONS_LOGGING_SUCCESS);
				} else {
					$core->setLog(CONS_LOGGING_ERROR);
					$_SESSION[CONS_SESSION_LOG_REQ] = $_POST;
					unset($_SESSION[CONS_SESSION_LOG_REQ]['haveinfo']);
					unset($_SESSION[CONS_SESSION_LOG_REQ]['vaction']);
				}
			break;
			case "multiple": // edit multiple items
				$msi_n = explode(",",$_REQUEST['multiSelectedIds']);
				$msi_nfiltered = array();
				foreach ($msi_n as $msi_nx) {
					if (trim($msi_nx) != '') {
						$msi_nfiltered[] = $msi_nx;
					}
				}
				$okKeys = array(); // how many (and which) where successfull
				$errorKeys = array(); // and which didn't work
				foreach ($msi_nfiltered as $msi) {
					// prepare array that will edit the fields
					$editArray = array();
					$myKeys = array();
					// add keys
					$keys = explode("_",$msi);
					foreach	($module->keys as $key) {
						$nextKey = array_shift($keys);
						$editArray[$key] = $nextKey;
						$myKeys[] = $nextKey;
					}
					// add items to edit
					foreach ($module->fields as $name => $field) {
						if (!in_array($name,$module->keys) && isset($_REQUEST['me_edit_'.$name])) {
							// not a key and the checkbox to edit was set
							$editArray[$name] = $_REQUEST[$name];
						}
					}

					if ($module->runAction(CONS_ACTION_UPDATE,$editArray,true,true))
						$okKeys[] = $myKeys;
					else
						$errorKeys[] = $myKeys;
				}
				if (count($errorKeys)==0) { // 100% sucess
					$core->log[] = $core->langOut("me_sucess_total")." (".count($okKeys).")";
					$core->setLog(CONS_LOGGING_SUCCESS);
				} else if (count($okKeys) ==0) { // 100% error
					$core->setLog(CONS_LOGGING_ERROR);
					$core->log[] = $core->langOut("me_error_total")." (".count($errorKeys).")";
				} else { // some sucessful, some errors
					$core->setLog(CONS_LOGGING_WARNING);
					$core->log[] = $core->langOut("me_partial_sucess");

					# TODO: not working with multiple keys!
					// items that were sucessful:
					$sql = "SELECT ".$module->title." as title, ".$module->keys[0]." as id FROM ".$module->dbname." WHERE ".$module->keys[0]." IN (".implode(",",$okKeys).")";
					$core->dbo->query($sql,$r,$n);
					// show item names/ids being edited
					$itemlist = array();
					for ($c=0;$c<$n;$c++) {
						$item = $core->dbo->fetch_assoc($r);
						$itemlist[] = $item['title']." (".$item['id'].")";
					}
					$core->log[] = $core->langOut("me_sucess_items").": ".implode(", ",$itemList);

					// items that were NOT sucessful:
					$sql = "SELECT ".$module->title." as title, ".$module->keys[0]." as id FROM ".$module->dbname." WHERE ".$module->keys[0]." IN (".implode(",",$errorKeys).")";
					$core->dbo->query($sql,$r,$n);
					// show item names/ids being edited
					$itemlist = array();
					for ($c=0;$c<$n;$c++) {
						$item = $core->dbo->fetch_assoc($r);
						$itemlist[] = $item['title']." (".$item['id'].")";
					}
					$core->log[] = $core->langOut("me_error_items").": ".implode(", ",$itemList);
					$core->action = "list";
				}
				$ok = true; // so it behaves as if it were ok
				$_REQUEST['postaction'] = 0; // and sends us to the list

			break;


			case "mup": // multiple uploads
				# what is the upload field?
				$upField = "";
				foreach($module->fields as $name => $field) {
					if ($field[CONS_XML_TIPO] == CONS_TIPO_UPLOAD) {
						$upField = $name;
						break;
					}
				}
				$getNames = isset($_REQUEST["mup_autofill"]);
				$addCounter = isset($_REQUEST["mup_autofill2"]);
				$zipFile = "";
				if ($upField != "") {
					$destination = CONS_FMANAGER."upload/";
					if (!is_dir($destination)) makeDirs($destination);
					$destination .= "multiple_upload_".$module->name.".zip";
					$ok = storefile($_FILES['mup_file'],$destination,'udef:zip'); // process upload to this file
					if ($ok==0) { // upload ok
						$zipFile = $destination;
						# prepare and clean up destination folder
						$destination = CONS_FMANAGER."upload/mpu/";
						recursive_del($destination,true); # this will delete mpu!
						if (!is_dir($destination)) { # so recreate
							if (!makeDirs($destination)) {
								$ok = 9;
								$core->errorControl->raise(804,$core->langOut("mpu_error_mkdir"));
							}
						}
					}
					if ($ok == 0) { // no error in preparing /mpu

						$zHn = zip_open($zipFile);
						if (is_resource($zHn)) {

							$core->errorControl->raise('300',"Unzipping file...");

							$hadFolders = false;
							while ($zHn_file = zip_read($zHn)) {
								$zip_name = zip_entry_name($zHn_file);
								if(strpos($zip_name, '.')!==false) {
									cWriteFile($destination.$zip_name,zip_entry_read($zHn_file,zip_entry_filesize($zHn_file)),false,true);
								} else
									$hadFolders = true;
							}
							// unzip complete
							@unlink($zipFile);

							// process files
							$listFiles = listFiles($destination,'/^.*$/');

							$core->errorControl->raise('300',"Files to unzip: ".count($listFiles));
							if ($hadFolders) {
								$core->log[] = $core->langOut('mup_hadfolders');
							}

							if (count($listFiles) > CONS_MAX_MUP) {
								$core->log[] = str_replace("#",CONS_MAX_MUP,$core->langOut("mup_toomany"));
							}


							$sucess = 0;
							$errors = 0;
							$filesToGo = count($listFiles);
							$originalTitle = isset($_POST[$module->title])?$_POST[$module->title]:"";
							foreach ($listFiles as $file) {
								if ($file != "." && $file != ".." && !is_dir($destination.$file)) {
									$filesToGo--;
									$simulatedUpload = array('name' => $file,
															 'type' => '',
															 'size' => filesize($destination.$file),
															 'tmp_name' => $destination.$file,
															 'error' => 0,
															 'virtual' => true
															);
									$_FILES[$upField] = $simulatedUpload;
									$filen = explode(".",$file);
									array_pop($filen);
									$filen = implode(".",$filen);
									if ($getNames) $_POST[$module->title] = UCWords(str_replace("_"," ",$filen));
									if ($addCounter) $_POST[$module->title] .= " ".($sucess+1);
									$ok = $core->runAction($module,CONS_ACTION_INCLUDE,$_POST,false);
									$_POST[$module->title] = $originalTitle;
									if ($ok)
										$sucess++;
									else {
										$errors++;
										$core->errorState = false;
									}
									if ($core->nearTimeLimit()) {
										$core->errorControl->raise('524',"Nearing time limit after processing $sucess files ($errors errors). Aborting.");
										$core->log[] = $core->langOut("mpu_partial").": $sucess ($errors errors)";
										$core->setLog(CONS_LOGGING_WARNING);
										break;
									}
								}
							}
							if ($sucess > 0) $core->setLog(CONS_LOGGING_SUCCESS);
							if ($filesToGo == 0) {
								$core->log[] = $core->langOut("mpu_complete").": $sucess/".count($listFiles)." ($errors errors)";
								$core->setLog(CONS_LOGGING_WARNING);
							}
							if ($sucess > 0 && $errors > 0) {
								$core->log[] = $core->langOut("mpu_some_errors");
								$core->setLog(CONS_LOGGING_WARNING);
								$core->errorControl->raise('300',"MPU partial sucess: $sucess/".count($listFiles)." ok, $errors/".count($listFiles)." fail");
							}
							if ($filesToGo > 0) {
								$core->log[] = $core->langOut("mpu_toomany_files");

							}
							$ok = true;
						} else {
							function ZipStatusString( $status )	{
								switch( (int) $status )	{
									case ZipArchive::ER_OK           : return 'No error';
									case ZipArchive::ER_MULTIDISK    : return 'Multi-disk zip archives not supported';
									case ZipArchive::ER_RENAME       : return 'Renaming temporary file failed';
									case ZipArchive::ER_CLOSE        : return 'Closing zip archive failed';
									case ZipArchive::ER_SEEK         : return 'Seek error';
									case ZipArchive::ER_READ         : return 'Read error';
									case ZipArchive::ER_WRITE        : return 'Write error';
									case ZipArchive::ER_CRC          : return 'CRC error';
									case ZipArchive::ER_ZIPCLOSED    : return 'Containing zip archive was closed';
									case ZipArchive::ER_NOENT        : return 'No such file';
									case ZipArchive::ER_EXISTS       : return 'File already exists';
									case ZipArchive::ER_OPEN         : return 'Can\'t open file';
									case ZipArchive::ER_TMPOPEN      : return 'Failure to create temporary file';
									case ZipArchive::ER_ZLIB         : return 'Zlib error';
									case ZipArchive::ER_MEMORY       : return 'Malloc failure';
									case ZipArchive::ER_CHANGED      : return 'Entry has been changed';
									case ZipArchive::ER_COMPNOTSUPP  : return 'Compression method not supported';
									case ZipArchive::ER_EOF          : return 'Premature EOF';
									case ZipArchive::ER_INVAL        : return 'Invalid argument';
									case ZipArchive::ER_NOZIP        : return 'Not a zip archive';
									case ZipArchive::ER_INTERNAL     : return 'Internal error';
									case ZipArchive::ER_INCONS       : return 'Zip archive inconsistent';
									case ZipArchive::ER_REMOVE       : return 'Can\'t remove file';
									case ZipArchive::ER_DELETED      : return 'Entry has been deleted';

									default: return sprintf('Unknown status %s', $status );
								}
							}
							$core->log[] = $core->langOut("mup_error").": ".ZipStatusString($zHn);
							$core->setLog(CONS_LOGGING_ERROR);
							$ok = false;
						}
						# kill files and zip file
						recursive_del(CONS_FMANAGER."upload/mpu",true);
					} else {
						// error 9 = unable to make destination path
						$core->log[] = $core->langOut("mup_error").": code #".$ok." (".$core->langOut("e20".$ok).")";
						if (is_file($zipFile)) @unlink($zipFile);
						if (is_file($destination)) @unlink($destination);
						$ok = false;
						if ($ok == 9) $core->log[] = CONS_ERROR_TAG." Error while creating MPU folder";
						$core->setLog(CONS_LOGGING_ERROR);
					}
				} else
					$ok = false; # er ... no multiple upload at this module!
			break;

		} # switch
		if ($ok) { # sucess
			if (!isset($_REQUEST['postaction'])) $_REQUEST['postaction'] = 0;
			$pa_dealt = false;
			switch ($_REQUEST['postaction']) {
				case 1: // edit
					$core->headerControl->internalFoward("edit.php?module=".$module->name."&".$kS);
					$core->action = "edit"; // if internalFoward is disabled
					$pa_dealt = true;
					break;
				case 2: // copy & create
					unset ($_POST[$module->keys[0]]); # remove main key to force "create" pane
					unset ($_REQUEST[$module->keys[0]]); # remove main key to force "create" pane
					unset ($_GET[$module->keys[0]]); # remove main key to force "create" pane
					unset ($_POST['vaction']);
					unset ($_REQUEST['vaction']);
					unset ($_GET['vaction']);
					foreach ($module->fields as $name => $field) { // remove slashes
						if (isset($_REQUEST[$name]) && ($field[CONS_XML_TIPO] == CONS_TIPO_VC || $field[CONS_XML_TIPO] == CONS_TIPO_TEXT)) {
							$_REQUEST[$name] = stripslashes($_REQUEST[$name]);
							if ($field[CONS_XML_TIPO] == CONS_TIPO_TEXT && !isset($field[CONS_XML_HTML]))
							$_REQUEST[$name] = str_replace("&quot;","\"",$_REQUEST[$name]);
						}
					}
					$pa_dealt = true;
					break;
				case 3: // referer
					if (isset($_REQUEST['affreferer']) && $_REQUEST['affreferer'] != "") {
						# breaks keys
						$str = "edit.php?module=".$_REQUEST['affreferer'];
						$refererModule = $core->loaded($_REQUEST['affreferer']);
						$_REQUEST = array('module' => $_REQUEST['affreferer']);
						if (isset($_POST['affrefererkeys']) && $_POST['affrefererkeys'] != '') { // loads the referer keys
							$keys = explode("_",$_POST['affrefererkeys']);
							foreach	($refererModule->keys as $key) {
								$_REQUEST[$key] = array_shift($keys); // if fowarder off
								$str .= "&".$key."=".$_REQUEST[$key];
							}
						}

						// prepares my keys in regard of the refered keys
						/*
						foreach ($module->keys as $aKey) {
							if ($aKey == "id")
								$rKey = $refererModule->get_key_from($module->name,"id_".$module->name);
							else if ($module->fields[$aKey][CONS_XML_TIPO] == CONS_TIPO_LINK) {
								$rKey = $refererModule->get_key_from($module->fields[$aKey][CONS_XML_MODULE]);
							} else {
								$rKey = $aKey;
							}
							if ($rKey != "" && isset($refererModule->fields[$rKey])) {
								$str .= "&".$rKey."=".$_POST[$key];
							}
						}
						*/
						$core->headerControl->internalFoward($str);
						$core->action = "edit"; // if internalFoward is disabled
						$pa_dealt = true;
					}
				case 4: // public
					$url = new CKTemplate($core->template);
					$url->tbreak($module->options[CONS_MODULE_PUBLIC]);
					$url = $url->techo($_POST);
					$url = CONS_INSTALL_ROOT.$url;
					$core->headerControl->internalFoward($url);
					list($core->context,$core->action,$core->original_action,$ext) = extractUri("",$url);
					$core->context_str = implode("/",$core->context);
					$pa_dealt = true;
					break;
			}
			if (!$pa_dealt) {
				$core->action = "list";
				$_REQUEST = array('module' => $_REQUEST['module']); // prevents filtering the list in the event of non-foward mode
				$core->headerControl->internalFoward("list.html?module=".$_REQUEST['module']);
			}

		} else { # error, stays with no redirect, but remove keys if it were insertion
			if ($_POST['vaction'] == "add") {
				unset ($_POST[$module->keys[0]]); # remove main key to force "create" pane
				unset ($_REQUEST[$module->keys[0]]); # remove main key to force "create" pane
				unset ($_GET[$module->keys[0]]); # remove main key to force "create" pane
			}
			// text fields should be stripped from slashed and treated for &quote; ... &lt; is not a problem
			foreach ($module->fields as $name => $field) {
				if (isset($_REQUEST[$name]) && ($field[CONS_XML_TIPO] == CONS_TIPO_VC || $field[CONS_XML_TIPO] == CONS_TIPO_TEXT)) {
					$_REQUEST[$name] = stripslashes($_REQUEST[$name]);
 					if ($field[CONS_XML_TIPO] == CONS_TIPO_TEXT && !isset($field[CONS_XML_HTML]))
 						$_REQUEST[$name] = str_replace("&quote;","\"",$_REQUEST[$name]);
				}
			}
			$core->errorState = false; // or it will result in master error anyway
		}


	}
