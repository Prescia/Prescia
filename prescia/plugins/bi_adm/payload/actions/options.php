<?

	$core->loadAllModules();
	$core->loadDimconfig(true);
	if (isset($_REQUEST['haveinfo'])) {
		if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<$core->dimconfig['minlvltooptions']) {
			$core->log[] = $core->langOut('permission_denied');
			$core->setLog(CONS_LOGGING_WARNING);
		} else {

			$dimconfigMD = unserialize(cReadFile(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/_dimconfig.dat"));

			foreach ($core->dimconfig as $name => $v) {
				if (!isset($dimconfigMD[$name])) {
					if (isset($_POST[$name]))
						$core->dimconfig[$name] = trim($_POST[$name]);
				} else {
					if (isset($dimconfigMD[$name][CONS_XML_RESTRICT]) && $dimconfigMD[$name][CONS_XML_RESTRICT]>$_SESSION[CONS_SESSION_ACCESS_LEVEL]) continue;
					if ($name == 'guest_group' && is_numeric($v)) {
						$groupModule = $core->loaded(CONS_AUTH_GROUPMODULE);
						$lvl = $core->dbo->fetch("SELECT level FROM ".$groupModule->dbname." WHERE id=".$_POST[$name]);
						if ($lvl > 0) {
							$core->log[] = $core->langOut("guest_mustbe_level0_group");
							$core->setLog(CONS_LOGGING_WARNING);
							continue; // won't change guest_group
						}
					}
					switch ($dimconfigMD[$name][CONS_XML_TIPO]) {
						case CONS_TIPO_UPLOAD:

							$FirstfileName = CONS_FMANAGER.$dimconfigMD[$name]['location'];
							$path = explode("/",$FirstfileName);
							$filename = array_pop($path);
							$path = implode("/",$path)."/";

							// perform delete test

							if (isset($_REQUEST[$name."_delete"]) || (isset($_FILES[$name]) && $_FILES[$name]['error']==0 )) { // delete ou update

								if (locateFile($FirstfileName,$ext)) {
									@unlink($FirstfileName);
									$thumbVersions = isset($dimconfigMD[$name][CONS_XML_THUMBNAILS])?count($dimconfigMD[$name][CONS_XML_THUMBNAILS]):1;
									for ($tb=1;$tb<$thumbVersions;$tb++) { # for all thumbs ...
										$thisFilenameT = $path."t/".$filename.($tb+1);
										if (locateFile($thisFilenameT,$ext))
											@unlink($thisFilenameT);
									}
								}
							}

							if (isset($_FILES[$name]) && $_FILES[$name]['error'] != 4) {

								$isImg = isset($dimconfigMD[$name][CONS_XML_TWEAKIMAGES]) || isset($dimconfigMD[$name][CONS_XML_THUMBNAILS]);

								# quota test
								if (isset($core->dimconfig['_usedquota']) && isset($core->dimconfig['quota']) && $core->dimconfig['quota'] > 0) {
									if ($core->dimconfig['_usedquota'] > $core->dimconfig['quota']) {
										$core->errorControl->raise(210,$name,'dincomfig');
										continue;
									}
								}

								#specials
								$WM_TODO = array(); # copied from module prepareUpload:
								if (isset($dimconfigMD[$name][CONS_XML_TWEAKIMAGES])) {
									foreach ($dimconfigMD[$name][CONS_XML_TWEAKIMAGES] as $c => $WM) {
										# stamp:over(filename@x,y)[r] # [r] not implemented yet
											# stamp:under(filename@x,y)[r]
										# croptofit:top bottom left right
										# might have multiple with + separator
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
												$thisTODO['filename'] = CONS_PATH_PAGES.$_SESSION['CODE']."/files/" .$parameters[0];
												if ($resample && isset($dimconfigMD[$name][CONS_XML_THUMBNAILS]))
												$thisTODO['resample'] = explode(",",$dimconfigMD[$name][CONS_XML_THUMBNAILS][$c]);
												$TODO[] = $thisTODO;
											} else if ($concept[0] == "croptofit") {
												$TODO[] = "C".(isset($concept[1])?$concept[1]:'');
											}
										}
									$WM_TODO[$c] = $TODO;
									}
								}

								if (isset($dimconfigMD[$name][CONS_XML_FILETYPES])) {
									$ftypes = "udef:".$dimconfigMD[$name][CONS_XML_FILETYPES];
								} else
									$ftypes = "";

								$mfs = isset($dimconfigMD[$name][CONS_XML_FILEMAXSIZE])?$dimconfigMD[$name][CONS_XML_FILEMAXSIZE]:0;
								if (isset($dimconfigMD[$name][CONS_XML_FILEMAXSIZE]) && !$isImg) {
									if (filesize($_FILES[$name]['tmp_name'])>$dimconfigMD[$name][CONS_XML_FILEMAXSIZE]) {
										@unlink($_FILES[$name]['tmp_name']);
										$core->errorControl->raise(202,$name,'dincomfig');
										continue;
									}
								}

								$errCode = storeFile($_FILES[$name],$FirstfileName,$ftypes);

								if ($errCode == 0 && $isImg) {

									# $FirstfileName has the file untreated

									# delete other images (could have uploaded a different image type than before on edit)
									$arquivo = explode(".",$FirstfileName);
									$ext = strtolower(array_pop($arquivo)); # <-- ext for the file
									$arquivo = implode(".",$arquivo);
									$exts = array("jpg","gif","swf","png","jpeg","ico");
									foreach ($exts as $x => $sext) {
										if ($sext != $ext && is_file($arquivo.".".$sext))
											@unlink($arquivo.".".$sext);
									}

									# if this is not an JPG image, and it's larger then mfs, won't work at all. Abort
									if ($mfs > 0 && filesize($FirstfileName)>$mfs && $ext != 'jpg') {
										$core->errorControl->raise(206,$name,'dincomfig');
										break;
									}

									# this is a image and we want thumbnails
									$thumbVersions = isset($dimconfigMD[$name][CONS_XML_THUMBNAILS])?count($dimconfigMD[$name][CONS_XML_THUMBNAILS]):1;

									if (!function_exists("resizeImage")) include_once CONS_PATH_INCLUDE."imgHandler.php";
									if ($thumbVersions > 1) { # has other versions/thumbnails, work these first

										for ($tb=1;$tb<$thumbVersions;$tb++) { # for all thumbs ...
											if (!isset($dimconfigMD[$name][CONS_XML_THUMBNAILS]))
												$dim = array(0,0);
											else
												$dim = explode(",",$dimconfigMD[$name][CONS_XML_THUMBNAILS][0]);
											$thisFilenameT = $path."t/".$filename.($tb+1);
											if (!resizeImage($FirstfileName,$thisFilenameT,$dim[0],isset($dim[1])?$dim[1]:0,0,isset($WM_TODO[$tb])?$WM_TODO[$tb]:array()) == 2) {
												# error!
												$core->errorControl->raise(208,$name,'dincomfig');
												break;
											}
										} #for each thumb
									}

									# Checks original filesize
									$dim = explode(",",$dimconfigMD[$name][CONS_XML_THUMBNAILS][0]);
									if (resizeImageCond($FirstfileName,$dim[0],isset($dim[1])?$dim[1]:0,isset($WM_TODO[0])?$WM_TODO[0]:array()) == 2)
										$core->errorControl->raise(206,$name,'dincomfig');

								}
							}
						break;
						default:
							if (isset($_POST[$name]))
								$core->dimconfig[$name] = trim($_POST[$name]);
					}
				}
			}
			$core->log[] = "ok";
			$core->saveConfig();
			$core->cacheControl->dumpTemplateCaches();
		}
	}


?>