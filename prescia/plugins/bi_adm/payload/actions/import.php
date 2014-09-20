<?

	if (!$core->authControl->checkPermission('bi_adm','can_importexport'))
		$core->fastClose(403);

	// replace /**/# with /**/ to debug



	if (isset($_POST['haveinfo'])) {
		$core->storage['failed'] = array();
		$core->loadAllmodules();
		if (!CONS_ONSERVER)
			set_time_limit(300);
		else
			set_time_limit(90);
		$module = $core->loaded($_POST['module']);
		if ($module !== false) {
			if (isset($_REQUEST['ignoreErrors'])) {
				$core->dbo->quickmode =true;
				$core->safety = false;
			}
			$isSimulation = isset($_REQUEST['isSimulation']);
			$preserveKeys = isset($_REQUEST['preserveKeys']);
			# field translation service:
			$c = 0;
			$fT = array(0=>-1);
			include_once(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/importer.php");
			$importerObj = new Cimporter($core);
			$iFields = $importerObj->fields($module); // database fields
			foreach ($iFields as $idx => $field) {
				$fT[$field['#']] = $idx; // converts a field number to the place in iFields
			}
			$linkerCache = array(); # a cache of foreing translations
			$oldKeys = array(); # this table might have parenting, and be pointing to OLD keys, this will translate the keys if that's the case
			# --

			# parse import content
			$importContent = stripslashes($_POST['importContent']);
			unset($_POST['importContent']); // MEMFREE
			$importContent = str_replace("\r","\n",$importContent);
			$importContent = explode("\n",$importContent);
			# --
			$okc = 0;
			$ok = true;
			$errorc = 0;
			$core->lastReturnCode = 0;
			switch($_POST['imode']) {
				case "cvs":

					$replacer = explode(",",str_replace(" ","",stripslashes($_POST['itemOrder']))); // item order (replace with values), is the ID of iFields, ? or $
					$fields = count($replacer);
					$sepData = trim(stripslashes($_POST['sepDados']));
					$sepQuote = trim(stripslashes($_POST['sepQuote']));

					foreach ($importContent as $Content) {
						$originalContent = $Content;
						if ($Content == '') continue; // null line
						$regs = array();
						$Content = stripslashes($Content); // it will strip the quotes, not needed
						if ($Content != "") {

							/**/#echo "-----<br/>LINE: $Content<br/>";
							if (isset($_REQUEST['utf8enforce'])) $Content = utf8_decode($Content);
							$total = strlen($Content);
							$buffer = "";
							$pos = 0;
							$inQuote = ($Content[$pos] == $sepQuote);
							$escape = false;
							if ($inQuote) $pos++;
							while ($pos < $total) {
								$car = $Content[$pos];
								if ($car == $sepData && !$escape && !$inQuote) {
									$regs[] = $buffer;
									$buffer = "";
								} else {
									if ($buffer == "" && $car == $sepQuote && !$escape) {
										$inQuote = true;
									} else if ($car == "\\" && !$escape) {
										$escape = true;
										$buffer .= $car;
									} else if (!$escape && $car == $sepQuote) {
										$inQuote = false;
									} else {
										$buffer .= $car;
										$escape = false;
									}
								}
								$pos++;
							}

							if ($buffer != "") $regs[] = $buffer;
							$dataArray = array();
							$linkerStack = array(); # on the event of linker modules, stack extra inserts to be made AFTER this one is made
							if (!isset($regs[$fields-1])) $regs[$fields-1] = ""; # just in case, last field is blank
							$ok = true;
							for ($c=0;$c<$fields;$c++) { # checks if we have all possible fields
								if (!isset($regs[$c])) {
									$core->log[] = $core->langOut("invalid_line")." ($originalContent)";
									$core->storage['failed'][] = $originalContent;
									$ok = false;
									break;
								}
							}
							if ($ok) { // line seems to be able to be imported
								/**/#echo "Line ok<br/>";
								$oldKey = 0;
								for ($c=0;$c<$fields;$c++) {
									// which DB field is this about?
									$idx = isset($replacer[$c]) && is_numeric($replacer[$c]) && isset($fT[$replacer[$c]])?$fT[$replacer[$c]]:-1;
									if ($idx != -1) { // is a field to import (not an empty or oldkey)
										/**/#echo "Item $c is valid<br/>";
										if ($iFields[$idx]['type'] == 0 || $iFields[$idx]['type'] == CONS_TIPO_LINK) { # linked to another table using a linker table
											#	[ THIS MODULE ]  <--- [ LINKER MODULE ] ---> [ REMOTE MODULE ]
											#	[ THIS MODULE ] ---> [ REMOTE MODULE ]
											if (!is_object($iFields[$idx]['remoteModule'])) { # do I have the remoteModule loaded?
												$m = $iFields[$idx]['remoteModule'];
												$iFields[$idx]['remoteModule'] = $core->loaded($iFields[$idx]['remoteModule']);
												if (!$iFields[$idx]['remoteModule']) die("ERR: $m ?");
											}
											/**/#echo "Item is a link to another table (".$iFields[$idx]['remoteModule']->name."): ".$regs[$c]."<br/>";
											if ($preserveKeys && $iFields[$idx]['remoteModule']->name == $module->name && $module->options[CONS_MODULE_PARENT] != '' && $regs[$c] != 0) {
												// is pointing to me, as parent. Check if I have a possible key
												/**/#echo "*** Linking to parent: ".$regs[c]."<br/>";
												if (isset($oldKeys[$regs[$c]])) {
													/**/#echo "Found old parent at: ".$oldKeys[$regs[$c]]."<br/>";
													$dataArray[$iFields[$idx]['name']] = $oldKeys[$regs[$c]];
													continue;
												} /**/# else
												/**/#echo "old parent not found<br/>";
											}

											if (!isset($linkerCache[$iFields[$idx]['name']])) # do I have a cache for this table?
												$linkerCache[$iFields[$idx]['name']] = array(); # no
											if (!isset($linkerCache[$iFields[$idx]['name']][$regs[$c]])) { # do not have this item cached, look for it
												if (isset($_REQUEST['exactlinkers']))
													$sql = $iFields[$idx]['remoteModule']->get_base_sql("(".$iFields[$idx]['remoteModule']->title." LIKE \"".cleanString($regs[$c])."\" OR ".$iFields[$idx]['remoteModule']->keys[0]."=\"".cleanString($regs[$c])."\")");
												else
													$sql = $iFields[$idx]['remoteModule']->get_base_sql("(".$iFields[$idx]['remoteModule']->title." LIKE \"%".cleanString($regs[$c])."%\" OR ".$iFields[$idx]['remoteModule']->keys[0]."=\"".cleanString($regs[$c])."\")");
												$sql['SELECT'] = array($iFields[$idx]['remoteModule']->keys[0]);
												$core->dbo->query($sql,$r,$n);
												if ($n == 1) {
													list($coreID) = $core->dbo->fetch_row($r);
													$linkerCache[$iFields[$idx]['name']][$regs[$c]] = $coreID;
													if ($iFields[$idx]['islinker']) # linker module. STACK
														$linkerStack[] = array($iFields[$idx]['name'],$linkerCache[$iFields[$idx]['name']][$regs[$c]]);
													else # direct link
														$dataArray[$iFields[$idx]['name']] = $linkerCache[$iFields[$idx]['name']][$regs[$c]];
												} else {
													if ($n>1) {
														$core->log[] = $core->langOut('ambiguity_found').": ".cleanString($regs[$c])." @ ".$iFields[$idx]['remoteModule']->name.".".$iFields[$idx]['remoteModule']->title." ($originalContent)";
														$core->setLog(CONS_LOGGING_WARNING);
													}
													if (isset($_REQUEST['failinvalidlinkers'])) $ok = false;
												}

											} else {
												$dataArray[$iFields[$idx]['name']] = $linkerCache[$iFields[$idx]['name']][$regs[$c]];
											}
										} else if ($iFields[$idx]['type'] == CONS_TIPO_DATE) {
											/**/#echo "Item is a date<br/>";
											if (ereg("([0-9]{2})[^0-9]?([0-9]{2})[^0-9]?([0-9]{2})[^0-9]?([0-9]{2})",$regs[$c],$tuplas)) {
												switch ($_REQUEST['cvsData']) {
													case "ymd":
														$dataArray[$iFields[$idx]['name']] = $tuplas[1].$tuplas[2]."-".$tuplas[3]."-".$tuplas[4];
													break;
													case "ydm":
														$dataArray[$iFields[$idx]['name']] = $tuplas[1].$tuplas[2]."-".$tuplas[4]."-".$tuplas[3];
													break;
													case "dmy":
														$dataArray[$iFields[$idx]['name']] = $tuplas[3].$tuplas[4]."-".$tuplas[2]."-".$tuplas[1];
													break;
													case "mdy":
														$dataArray[$iFields[$idx]['name']] = $tuplas[3].$tuplas[4]."-".$tuplas[1]."-".$tuplas[2];
													break;
												}
											} else if ($iFields[$idx]['type'] == CONS_TIPO_ENUM) {
												/**/#echo "Item is an enum<br/>";
												if ($iFields[$idx]['enum'] == "'y','n'") { // translate 1/0 boolean to y/n
													if ($regs[c] == 1) $regs[c] = 'y';
													else $regs[c] == 'n';
												}
											} else {
												$dataArray[$iFields[$idx]['name']] = $regs[$c];
											}
										} else if (isset($regs[$c])) # normal link
											$dataArray[$iFields[$idx]['name']] = $regs[$c];
									} else if ($replacer[$c] == "$") { // this is the old key
										/**/#echo "item $c is an oldKey: ".$regs[$c]."<br/>";
										$oldKey = $regs[$c];
										$dataArray[$module->keys[0]] = $oldKey;
									} /**/#else
										/**/#echo "Item $c is not valid (skip)<br/>";
								}
							}
							/**/#echo "Array: ".print_r($dataArray,true)."<br/>";

							if ($ok) {
								$core->setLog(CONS_LOGGING_SUCCESS);
								$dataArray = fillDefaults($module,$dataArray);

								if ($isSimulation) {
									$missing = $module->check_mandatory($dataArray,CONS_ACTION_INCLUDE);
									if (count($missing)>0) {
										$core->log[] = $core->langOut('e127')." ($originalContent)";
										$core->setLog(CONS_LOGGING_WARNING);
										$core->storage['failed'][] = $originalContent;
										$ok = false;
									}
									$core->lastReturnCode++;
								} else {
									if (count($dataArray)>0) {
										$tempOk = $core->runAction($module,CONS_ACTION_INCLUDE,$dataArray);
										if (!$tempOk){
											 $core->log[] = array_pop($core->log)." ($originalContent)";
											 $core->storage['failed'][] = $originalContent;
											 $core->setLog(CONS_LOGGING_ERROR);
										}
									}
									$ok = $ok && $tempOk;
								}

								$newID = $core->lastReturnCode;
								/**/#echo "Include ok, lRC=$newID<br/>";
								$oldKeys[$oldKey] = $newID;
								/**/#echo "old $oldKey = $newID<br/>";
								$okc++;
								if (!$isSimulation && count($linkerStack)>0) { # we have linkers to be made!
									#	[ THIS MODULE ]  <--- [ LINKER MODULE ] ---> [ REMOTE MODULE ]
									foreach ($linkerStack as $linkerdata) { # linkerdata is an array with the linker module and the remote module ID
										$linkerModule = $core->loaded($linkerdata[0]);
										$dataArray = array();
										foreach ($linkerModule->fields as $fname => $field) {
											if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK && $field[CONS_XML_MODULE] == $module->name)
												$dataArray[$fname] = $newID;
											else
												$dataArray[$fname] = $linkerdata[1];
										}
										$core->runAction($linkerModule,CONS_ACTION_INCLUDE,$dataArray);
										$core->errorState = false;
									}
								}
							} else {
								$errorc++;
								$core->errorState = false;
								$core->storage['failed'][] = $originalContent;
								if (!$isSimulation && !isset($_REQUEST['ignoreErrors'])) break;
							} # ok?
						}
					}
					if ($isSimulation) {
						$core->log[] = $core->langOut("import_simulation_complete");
						$core->log[] = $core->langOut("import_complete")." ".$okc." OK!, ".$errorc." ERRORS!";
						$core->setLog(CONS_LOGGING_SUCCESS);
					} else if (!$ok && !isset($_REQUEST['ignoreErrors'])) {
						$core->log[] = $core->langOut("import_aborted_at")." ".$okc;
						$core->setLog(CONS_LOGGING_ERROR);
					} else {
						$core->log[] = $core->langOut("import_complete")." ".$okc." OK!, ".$errorc." ERRORS!";
						$core->setLog(CONS_LOGGING_SUCCESS);
					}

					/**/#die();

				break;
				case "raw": // ereg

					// TODO: preserve key and enum translate (1=y, 0=n)

					$pattern = "/".stripslashes($_POST['ereg'])."/";
					$replacer = explode(",",$_POST['itemOrder']);
					for ($c=0;$c<count($replacer);$c++)
						$replacer[$c] = explode("=",$replacer[$c]);
					foreach ($importContent as $Content) {
						if (isset($_REQUEST['utf8enforce'])) $Content = utf8_decode($Content);
						if ($Content != "" && preg_match($pattern,$Content,$regs)!=0) {
							$dataArray = array();
							foreach ($replacer as $rep) {
								if (isset($fT[$rep[0]])) {
									$dataArray[$fT[$rep[0]]] = $regs[$rep[1]];
								}
							}
							$dataArray = fillDefaults($module,$dataArray);
							if (!$isSimulation)
								$ok = $core->runAction($module,CONS_ACTION_INCLUDE,$dataArray);
								if (!$ok) {
									$core->log[] = array_pop($core->log)." ($originalContent)";
									$core->storage['failed'][] = $originalContent;
								}
							else {
								$missing = $module->check_mandatory($dataArray,CONS_ACTION_INCLUDE);
								$ok = count($missing)==0;
								if (!$ok) {
									$core->storage['failed'][] = $originalContent;
									$core->log[] = $core->langOut('e127')." ($originalContent)";
									$core->setLog(CONS_LOGGING_WARNING);
								}
							}
							if ($ok)
								$okc++;
							else {
								$errorc++;
								$core->errorState = false;
								if (!isset($_REQUEST['ignoreErrors'])) break;
							} # ok?
						} # valid?
					} # each line
					if ($isSimulation) {
						$core->setLog(CONS_LOGGING_SUCCESS);
						$core->log[] = $core->langOut("import_simulation_complete");
						if (!$ok && !isset($_REQUEST['ignoreErrors'])) {
							$core->log[] = $core->langOut("import_aborted_at")." ".$okc;
							$core->setLog(CONS_LOGGING_WARNING);
						}
						$core->log[] = $core->langOut("import_complete")." ".$okc." OK!, ".$errorc." ERRORS!";
					} else if (!$ok && !isset($_REQUEST['ignoreErrors'])) {
						$core->setLog(CONS_LOGGING_WARNING);
						$core->log[] = $core->langOut("import_aborted_at")." ".$okc;
					} else {
						$core->setLog(CONS_LOGGING_SUCCESS);
						$core->log[] = $core->langOut("import_complete")." ".$okc." OK!, ".$errorc." ERRORS!";
					}
				break;
				case "php":
					if (isset($_REQUEST['phpscript'])) {
						include_once CONS_PATH_SYSTEM."import/".$_REQUEST['phpscript'].".php";
					}
				break;
			}

		} else
			$core->log[] = "Module not found";
	}

	function fillDefaults($module,$dA) {
		foreach ($module->fields as $fname => $field) {
			if (!isset($dA[$fname]) && isset($_REQUEST[$fname]) && $_REQUEST[$fname] != '')
				$dA[$fname] = $_REQUEST[$fname];
		}
		return $dA;
	}

