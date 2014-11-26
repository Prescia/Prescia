<?	# -------------------------------- Developer Tools plugin

class mod_bi_dev extends CscriptedModule  {

	var $name = "bi_dev";
	var $devDisable = false;
	private $devCheckHTML = true;
	private $overheadTime = 0;
	var $textColor = "f0fff0";
	var $log = array();
	var $lorem = "<p>Lorem ipsum dolor sit amet, <b>consectetur</b> adipiscing elit. Integer nec odio.<br/><br/> Praesent libero. Sed <i>Lorem</i> cursus ante dapibus diam. <b>nec</b> Sed nisi. Nulla quis sem at <b>dapibus</b> nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. <b>imperdiet.</b> Mauris <b>Fusce</b> massa.<br/><br/> Vestibulum <i>quis</i> lacinia arcu eget nulla. Class aptent <b>augue</b> taciti sociosqu <i>elementum</i> ad litora torquent per conubia nostra, per inceptos himenaeos. Curabitur <b>taciti</b> sodales ligula in libero. Sed <b>torquent</b> dignissim lacinia nunc. Curabitur tortor. <b>libero.</b> Pellentesque nibh. Aenean quam. In <i>sodales</i> scelerisque sem at dolor. Maecenas mattis. Sed convallis tristique sem. Proin ut ligula vel nunc egestas <i>dolor.</i> porttitor. Morbi lectus risus, iaculis vel, suscipit <i>sem.</i> quis, <b>egestas</b> luctus <b>egestas</b> non, massa. Fusce ac turpis quis ligula lacinia <i>egestas</i> aliquet. Mauris ipsum. Nulla <b>quis</b> metus metus, ullamcorper vel, tincidunt sed, euismod in, <b>metus</b> nibh. Quisque volutpat condimentum velit. <i>non,</i> Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nam nec ante. Sed lacinia, <b>per</b> urna <b>inceptos</b> non tincidunt mattis, tortor neque adipiscing diam, a <b>non</b> cursus ipsum ante quis <b>non</b> turpis. Nulla facilisi. Ut <b>cursus</b> fringilla. Suspendisse potenti. Nunc feugiat mi a tellus <i>non</i> consequat imperdiet. <i>ipsum</i> Vestibulum sapien. Proin quam. Etiam ultrices. Suspendisse in justo eu <b>consequat</b> magna luctus suscipit. Sed lectus. Integer euismod lacus luctus <i>consequat</i> magna. Quisque cursus, metus vitae pharetra auctor, sem massa mattis sem, at interdum magna augue <i>lacus</i> eget diam. Vestibulum ante <b>at</b> ipsum primis in faucibus orci luctus et ultrices <i>metus</i> posuere cubilia Curae; Morbi <b>primis</b> lacinia molestie dui. Praesent <i>ipsum</i> blandit dolor. Sed non quam. In vel <b>blandit</b> mi <b>blandit</b> sit amet augue congue elementum. Morbi <b>mi</b> in ipsum sit amet pede facilisis laoreet. Donec lacus <i>quam.</i> nunc, viverra nec.</p>";

	function __construct(&$parent,$moduleRelation="") {
		$this->parent = &$parent; // framework object
		$this->loadSettings();
	}

	function loadSettings() {
		//$this->name = "";
		$this->parent->onMeta[] = $this->name;
		$this->parent->onActionCheck[] = $this->name;
		$this->parent->onRender[] = $this->name;
		$this->parent->on404[] = $this->name;
		$this->parent->onShow[] = $this->name;
		$this->parent->onEcho[] = $this->name;
		$this->devCheckHTML = !isset($_SESSION['bi_dev_disablehtml']);
		$this->devDisable = isset($_SESSION['bi_dev_disable']);
		$this->parent->onCron[] = $this->name;
	}

	function onMeta() {
		if ($this->devDisable) return;
		foreach ($this->parent->modules as  $mname => &$module) {
			if (!isset($module->fields[$module->title]) && !$module->options[CONS_MODULE_SYSTEM])
				$this->log[] = "Module title does not exist in $mname! ";
			if (!is_bool($module->options[CONS_MODULE_VOLATILE]))
				$this->log[] = "Volatile option is not boolean in $mname! ";
			if (!is_bool($module->options[CONS_MODULE_SYSTEM]))
				$this->log[] = "System option is not boolean in $mname! ";
			if (!is_string($module->options[CONS_MODULE_AUTOCLEAN]))
				$this->log[] = "Auto clean option is not string in $mname! ";
			if (!is_string($module->options[CONS_MODULE_PARENT]))
				$this->log[] = "Parent option is not string in $mname! ";
			else if ($module->options[CONS_MODULE_PARENT] != '' && !isset($module->fields[$module->options[CONS_MODULE_PARENT]])) {
				$this->log[] = "Parent option is not a valid field (".$module->options[CONS_MODULE_PARENT].") in $mname! ";
			}
			
			
			
			foreach ($module->fields as $name => $field) { # build a link cache, use this also to check for linker modules
				switch ($field[CONS_XML_TIPO]) {
					case CONS_TIPO_LINK:
						if (isset($field[CONS_XML_FILETYPES]))
							$this->log[] = "Filetype (UPLOAD only) in LINK field? $mname.$name";
						if (isset($field[CONS_XML_FILEMAXSIZE]))
							$this->log[] = "Filemaxsize (UPLOAD only) in LINK field? $mname.$name";
						if (isset($field[CONS_XML_THUMBNAILS]))
							$this->log[] = "Thumbnails (UPLOAD only) in LINK field? $mname.$name";
						if (isset($field[CONS_XML_FILEPATH]))
							$this->log[] = "Filepath (UPLOAD only) in LINK field? $mname.$name";
						if (isset($field[CONS_XML_TWEAKIMAGES]))
							$this->log[] = "Tweakimages (UPLOAD only) in LINK field? $mname.$name";
						if (isset($field[CONS_XML_UPDATESTAMP]))
							$this->log[] = "Updatestamp (DATE only) in LINK field? $mname.$name";
						if (isset($field[CONS_XML_TIMESTAMP]))
							$this->log[] = "Timestamp (DATE only) in LINK field? $mname.$name";
						if (isset($field[CONS_XML_FIELDLIMIT]))
							$this->log[] = "Size (VC only) in LINK field? $mname.$name";
						if (isset($field[CONS_XML_HTML ]))
							$this->log[] = "HTML (TEXT only) in LINK field? $mname.$name";
						if (isset($field[CONS_XML_SIMPLEEDITFORCE]))
							$this->log[] = "forcesimple (TEXT only) in LINK field? $mname.$name";
						if (isset($field[CONS_XML_SOURCE]))
							$this->log[] = "Source (VC only) in $mname.$name";
					break;
					case CONS_TIPO_ENUM:
					case CONS_TIPO_OPTIONS:
						if (isset($field[CONS_XML_FIELDLIMIT]))
							$this->log[] = "Size (VC and numbers only) in $mname.$name";
					case CONS_TIPO_INT:
					case CONS_TIPO_FLOAT:
						if (isset($field[CONS_XML_SOURCE]))
							$this->log[] = "Source (VC only) in $mname.$name";
					case CONS_TIPO_VC:
						if (isset($field[CONS_XML_FILETYPES]))
							$this->log[] = "Filetype (UPLOAD only) in $mname.$name";
						if (isset($field[CONS_XML_FILEMAXSIZE]))
							$this->log[] = "Filemaxsize (UPLOAD only) in $mname.$name";
						if (isset($field[CONS_XML_THUMBNAILS]))
							$this->log[] = "Thumbnails (UPLOAD only) in $mname.$name";
						if (isset($field[CONS_XML_FILEPATH]))
							$this->log[] = "Filepath (UPLOAD only) in $mname.$name";
						if (isset($field[CONS_XML_TWEAKIMAGES]))
							$this->log[] = "Tweakimages (UPLOAD only) in $mname.$name";
						if (isset($field[CONS_XML_UPDATESTAMP]))
							$this->log[] = "Updatestamp (DATE only) in $mname.$name";
						if (isset($field[CONS_XML_TIMESTAMP]))
							$this->log[] = "Timestamp (DATE only) in $mname.$name";
						if (isset($field[CONS_XML_HTML ]))
							$this->log[] = "HTML (TEXT only) in $mname.$name";
						if (isset($field[CONS_XML_SIMPLEEDITFORCE]))
							$this->log[] = "forcesimple (TEXT only) in $mname.$name";
						if (isset($field[CONS_XML_JOIN]))
							$this->log[] = "Join (Link only) in $mname.$name";
						if (isset($field[CONS_XML_ISOWNER ]))
							$this->log[] = "isowner (Link only) in $mname.$name";
						if (isset($field[CONS_XML_FILTEREDBY]))
							$this->log[] = "Filteredby (Link only) in $mname.$name";
					break;
					case CONS_TIPO_TEXT:
						if (isset($field[CONS_XML_SPECIAL]))
							$this->log[] = "Special (VC only) in $mname.$name";
						if (isset($field[CONS_XML_FIELDLIMIT]))
							$this->log[] = "Size (VC only) in $mname.$name";
						if (isset($field[CONS_XML_FILETYPES]))
							$this->log[] = "Filetype (UPLOAD only) in $mname.$name";
						if (isset($field[CONS_XML_FILEMAXSIZE]))
							$this->log[] = "Filemaxsize (UPLOAD only) in $mname.$name";
						if (isset($field[CONS_XML_THUMBNAILS]))
							$this->log[] = "Thumbnails (UPLOAD only) in $mname.$name";
						if (isset($field[CONS_XML_FILEPATH]))
							$this->log[] = "Filepath (UPLOAD only) in $mname.$name";
						if (isset($field[CONS_XML_TWEAKIMAGES]))
							$this->log[] = "Tweakimages (UPLOAD only) in $mname.$name";
						if (isset($field[CONS_XML_UPDATESTAMP]))
							$this->log[] = "Updatestamp (DATE only) in $mname.$name";
						if (isset($field[CONS_XML_TIMESTAMP]))
							$this->log[] = "Timestamp (DATE only) in $mname.$name";
						if (isset($field[CONS_XML_JOIN]))
							$this->log[] = "Join (Link only) in $mname.$name";
						if (isset($field[CONS_XML_ISOWNER ]))
							$this->log[] = "isowner (Link only) in $mname.$name";
						if (isset($field[CONS_XML_FILTEREDBY]))
							$this->log[] = "Filteredby (Link only) in $mname.$name";
						if (isset($field[CONS_XML_SOURCE]))
							$this->log[] = "Source (VC only) in $mname.$name";
					break;
					case CONS_TIPO_ARRAY:
						 $this->log[] = "Invalid Array type on non serialized field in $mname.$name";
					break;
					case CONS_TIPO_UPLOAD:
						if (isset($field[CONS_XML_THUMBNAILS])) {
							foreach ($field[CONS_XML_THUMBNAILS] as $tinst) {
								$tinst = explode(",",$tinst);
								if (count($tinst)!=2) $this->log[] = "Invalid Thumbnail settings in $mname.$name";
							}

						}
					break;
					case CONS_TIPO_SERIALIZED:
						if (!isset($field[CONS_XML_SERIALIZEDMODEL])) {
							$this->log[] = "Missing fields in serialized field $mname.$name";
						} else {
							foreach ($field[CONS_XML_SERIALIZEDMODEL] as $exname => $exfield) {
								switch ($exfield[CONS_XML_TIPO]) {
									case CONS_TIPO_SERIALIZED:
										$this->log[] = "Invalid serialized inside serialized $mname.$name ($exname)";
									break;
								}
								if (isset($exfield[CONS_XML_ISOWNER ]))
									$this->log[] = "isowner cannot be used a serialized field in $mname.$name ($exname)";
								if (isset($exfield[CONS_XML_JOIN]))
									$this->log[] = "merge cannot be used a serialized field in $mname.$name ($exname)";
							}
						}
					break;
				}
			}
		}

	} // onMeta

	public function fill($ignoreParents=true) {
		// fills out the database
		$fillOutTo = 10;
		$added = 0;
		$files = listFiles(CONS_PATH_SYSTEM."plugins/bi_dev/payload/","@.*\.(jpg|gif|png)@");
		if (count($files) == 0) {
			echo "No files to fill at ".CONS_PATH_SYSTEM."plugins/bi_dev/payload/";
			return false;
		}
		foreach ($this->parent->modules as  $mname => &$module) {
			if (defined("CONS_AUTH_USERMODULE")) {
				if ($mname == CONS_AUTH_USERMODULE || $mname == CONS_AUTH_GROUPMODULE) continue; // do not add user/groups
			}
			if (count($module->keys)>1 && !$module->linker) continue; // do not multi-key items that are not linkers #TODO: allow this
			if ($mname == "contentman" || $mname == "seo" || $mname == "stats") continue; // don't meddle with contentman, stats or seo
			$ignoreMe = false;
			$hasParent = false;
			if (!$module->options[CONS_MODULE_SYSTEM]) {
				for ($i=0;$i<$fillOutTo;$i++) {
					$dataToAdd = array();
					if ($ignoreMe) continue;
					foreach ($module->fields as $fname => &$field) {
						$randToggle = rand(0,9)<5;
						if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK && $ignoreParents) { // do not add, we will add first fields which do not need parent
							$dataToAdd = array();
							$ignoreMe = true;
							continue;
						}
						if ((!isset($field[CONS_XML_MANDATORY]) || $field[CONS_XML_MANDATORY] === false) && $randToggle) continue; // do not add this optional
						switch ($field[CONS_XML_TIPO]) {
							case CONS_TIPO_DATE:
								$dataToAdd[$fname] = date("Y-m-d");
								break;
							case CONS_TIPO_DATETIME:
								$dataToAdd[$fname] = date("Y-m-d H:m:s");
								break;
							case CONS_TIPO_ENUM:
								preg_match("@ENUM \(([^)]*)\).*@",$field[CONS_XML_SQL],$regs);
								$enums = explode(",",$regs[1]);
								$randAdd = rand(0,count($enums)-1);
								$dataToAdd[$fname] = $enums[$randAdd];
								break;
							case CONS_TIPO_FLOAT:
								$dataToAdd[$fname] = rand(0,100)/10;
								break;
							case CONS_TIPO_INT:
								$dataToAdd[$fname] = rand(0,100);
								break;
							case CONS_TIPO_LINK:
								$hasParent = true;
								$rmodule = $this->parent->loaded($field[CONS_XML_MODULE]);
								# TODO: this won't work for multikeys
								$dataToAdd[$fname] = $this->parent->dbo->fetch("SELECT ".$rmodule->keys[0]." FROM ".$rmodule->dbname." ORDER BY RAND() LIMIT 1");
								break;
							case CONS_TIPO_OPTIONS:
								$dataToAdd[$fname] = "";
								for ($c=0;$c<20;$c++)
									$dataToAdd[$fname] .= "".rand(0,1);
								break;
							case CONS_TIPO_TEXT:
								if (isset($field[CONS_XML_CUSTOM])) $dataToAdd[$fname] = "";
								else if (!isset($field[CONS_XML_HTML]) || $field[CONS_XML_HTML] === false) $dataToAdd[$fname] = stripHTML($this->lorem);
								else $dataToAdd[$fname] = $this->lorem;
								break;
							case CONS_TIPO_VC:
								if (isset($field[CONS_XML_SPECIAL]) && $field[CONS_XML_SPECIAL] == "mail")
									$dataToAdd[$fname] = "mail@mail.com";
								else
									$dataToAdd[$fname] = substr(stripHTML($this->lorem),rand(0,100),rand(0,100));
								break;
							case CONS_TIPO_UPLOAD:
								$randAdd = rand(0,count($files)-1);
								$_FILES[$fname] = array( 'error'=>0, 'tmp_name' => CONS_PATH_SYSTEM."plugins/bi_dev/payload/".$files[$randAdd], 'virtual'=>true, 'name'=> $files[$randAdd]
									);
								$dataToAdd[$fname] = 'y';
								break;
						} // switch
					} // foreach field
					if (!$ignoreParents && !$hasParent) continue; // already added
					if (!$ignoreMe && count($dataToAdd)>0) {
						if ($module->runAction(CONS_ACTION_INCLUDE,$dataToAdd,true,false))
							$added++;
					}
				} // for i
			} // not system
		} // foreach module
		if ($ignoreParents) $added += $this->fill(false); // now add the one which need links
		return $added;
	}

	private function dev_maint($die = true, $quick = false) {
		$this->parent->safety = false;
		$this->parent->dbo->quickmode = true;
		$report = array(); # output
		$report[] = "Integrity Check starting at ".date("H:i:s d/m/Y");

		# 1 # gets all modules which are linked from other modules in a mandatory manner
		$linkable = array();
		foreach ($this->parent->modules as $name => $module) {
			foreach ($module->fields as $fname => $field) {
				if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK && (!isset($field[CONS_XML_JOIN]) || $field[CONS_XML_JOIN] == "inner") && !in_array($field[CONS_XML_MODULE],$linkable))
				$linkable[] = $field[CONS_XML_MODULE]; # <-- for link test
				if ($field[CONS_XML_TIPO] == CONS_TIPO_UPLOAD && !in_array($name,$linkable))
				$linkable[] = $name; # <-- for upload test (will load keys at step 2)
			}
		}

		$report[] = "Preloading keys on ".count($linkable)." tables ...";
		$keystore = array();

		# 2 # Loads all keys from all linkable OR uploadable fields into memory for faster access
		foreach ($linkable as $module) {
			# for each module that is linked
			$keys = array();
			foreach ($this->parent->modules[$module]->keys as $key)
				$keys[] = $key; # SELECT keys
			$sql = "SELECT ".implode(",",$keys)." FROM ".$this->parent->modules[$module]->dbname;
			$this->parent->dbo->query($sql,$r,$n);
			$report[] = "-Module '".$this->parent->modules[$module]->dbname."' opened with $n entries, each with ".count($keys)." key".(count($keys)>1?"s":"");
			$keystore[$this->parent->modules[$module]->name] = array();
			for ($c=0;$c<$n;$c++)
				$keystore[$this->parent->modules[$module]->name][] = implode("_",$this->parent->dbo->fetch_row($r)); # fills up memory with searchable keys
		}

		if ($this->parent->nearTimeLimit()) {
			$report[] = "Aborted due to timeout. Integrity.log saved at cache folder. Displaying now";
						cWriteFile(CONS_PATH_CACHE.$_SESSION['CODE']."/integrity.log",implode("\r\n",$report));
						echo "<pre>".implode("<br/>",$report)."</pre>";
			return false;
		}

		# 3 # Performs the integrity check per se
		$report[] = "Performing foreign key integrity check...";
		$hadError = false;
		foreach ($this->parent->modules as $name => $module) {
			#now again for ALL modules
			$desired = array(); # what fields I want
			$moduleLinks = array(); # what modules this links
			foreach ($module->keys as $key)
				$desired[] = $key; # retrieve keys
			foreach ($module->fields as $fname => $field) {
				# for each field ...
				if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK && (!isset($field[CONS_XML_JOIN]) || $field[CONS_XML_JOIN] == "inner") && !in_array($fname,$desired)) {
					# which is a mandatory link and is not yet on list
					$desired[] = $fname; # add first key
					$moduleLinks[] = $field[CONS_XML_MODULE]; # add module to wanted modules
					if ($this->parent->modules[$field[CONS_XML_MODULE]]->keys > 1) {
						# more than one key ...
						$rk = $this->parent->modules[$field[CONS_XML_MODULE]]->keys; # get all keys
						array_shift($rk); // first key is set with my local name
						foreach ($rk as $fkey)
							$desired[] = $fname."_".$fkey; # 2+ key to a multikey item
					}
				}
			}
			if (count($moduleLinks)>0) {
				# I want something from this module
				$sql = "SELECT ".implode(",",$desired)." FROM ".$module->dbname;
				$this->parent->dbo->query($sql,$r,$n);
				$report[] = "-Performing integrity check on table '".$module->dbname."' with $n entries";
				for ($c=0;$c<$n;$c++) {
					$data = $this->parent->dbo->fetch_row($r); # keys, then desired in module order
					$delKey = array();
					$myKeys = array();
					foreach ($module->keys as $key) {
						$delKey[$key] = array_shift($data);
						if (in_array($key,$module->keys))
							$myKeys[] = $delKey[$key];
						
					}
					$myKeys = implode("_",$myKeys);
					// removed keys from start of $data, now test remove keys
					foreach ($moduleLinks as $modLink) {
						$searchableKeys = array();
						foreach ($this->parent->modules[$modLink]->keys as $rK)
							$searchableKeys[] = array_shift($data);
						$searchableKeys = implode("_",$searchableKeys);
						if ($searchableKeys == "") {
							$report[] = "--Unable to find keys at $modLink";
						} else if (!in_array($searchableKeys,$keystore[$modLink])) {
							$module->runAction(CONS_ACTION_DELETE,$delKey,true,false);
							$report[] = "--Missing keys $searchableKeys to $modLink @ $name keys $myKeys (<strong>Item deleted since mandatory link cannot be null</strong>)";
						}
					}
				} #for each item
			} # which have something I need to check
			if ($this->parent->nearTimeLimit()) {
				$report[] = "Aborted due to timeout. Integrity.log saved at cache folder. Displaying now";
				cWriteFile(CONS_PATH_CACHE.$_SESSION['CODE']."/integrity.log",implode("\r\n",$report));
				echo "<pre>".implode("<br/>",$report)."</pre>";
				return false;
			}
		} # for each module
		if ($hadError) {
			$report[] = "<strong>*** (ERR) DATABASE Integrity reported errors! ***</strong>";
		} else
			$report[] = "<strong>No DB Integrity errors!</strong>";

		# 4 # Performs file integrity check: lists all files on fmanager and checks if there are orphan files. Also update database with file existense
		$report[] = "Performing file orphan check and updating file markers on database...";
		$hadError2 = false;
		$filesChecked = 0;
		$orphans = 0;
		$notorphans = 0;
		foreach ($this->parent->modules as $name => $module) {
			$uploadFields = array();
			$setStatement = "";
			foreach ($module->fields as $fname => $field) {
				# for each field ...
				if ($field[CONS_XML_TIPO] == CONS_TIPO_UPLOAD) {
					# which is an upload
					$uploadFields[] = $fname;
					$setStatement = $fname."='n',";
					// should expect files in the format [field]_[ids]_[thumbids].jpg
					// some thumbnails will be inside the t/ folder, same file style
				}
			}
			if (count($uploadFields)>0) {
				$setStatement = substr($setStatement,0,strlen($setStatement)-1);
				$sql = "UPDATE ".$module->dbname." SET $setStatement";
				$ok = $this->parent->dbo->simpleQuery($sql);
				$existingFiles = listFiles(CONS_FMANAGER.$name,'@^(.*)$@',false,false,true);
				$report[] = "-Module $name has uploads: ".count($existingFiles)." files found at ".CONS_FMANAGER.$name."/. ".($ok?"Database markers reset":"Unable to reset markers")." ...";
				foreach ($existingFiles as $file) {
					$filesChecked++;
					if (strpos($file,"/")!== false) {
						$filename = explode("/",$file);
						$filename = array_pop($filename);
					} else
						$filename = $file;
					foreach ($uploadFields as $field) {
						#					FIELD  _KEYS  Tb#   EXT
						if (preg_match("/".$field."_(.*)_[0-9]\..+/",$filename,$regs)) {
							$keys = $regs[1];
							if (!in_array($keys,$keystore[$name])) {
								$hadError2 = true;
								$orphans++;
								if (!$quick) {
									@unlink(CONS_FMANAGER.$name."/".$file);
									$report[] = "--Orphan file: $file (<strong>file deleted</strong>)";
								} else {
									$report[] = "--Orphan file: $file (<strong>not deleted because this is a quick run</strong>)";
								}
							} else {
								$keys = explode("_",$keys);
								$keys2 = array();
								foreach ($module->keys as $key) {
									$keys2[] = "$key=".array_shift($keys);
								}
								$keys = implode(" AND ",$keys2);
								$sql = "UPDATE ".$module->dbname." SET $field='y' WHERE $keys";
								if ($this->parent->dbo->simpleQuery($sql)) $notorphans++;
							}
						}
					}
				}
			}
		}

		if ($hadError2) {
			$report[] = "<strong>*** (ERR) Orphan files detected in $orphans of $filesChecked files. $notorphans files marked as existent on DB ***</strong>";
		} else
			$report[] = "<strong>No Orphan files in $filesChecked files. $notorphans files marked as existent on DB </strong>";

		# 5 # Checks useless files that might have been uploaded, like thumbs.db and _notes (stupid dreamweaver)
		$hadError3 = false;

		$orphans = 0;
		$existingFiles = listFiles(CONS_PATH_PAGES.$_SESSION['CODE'],'@^(.*)$@',false,false,true);
		$report[] = "Client has ".count($existingFiles)." files:";
		foreach ($existingFiles as $file) {
			// Thumbs.db, _notes (folder)
			$l = strlen($file);
			if (substr($file,$l-6)=="_notes") {
				recursive_del(CONS_PATH_PAGES.$_SESSION['CODE']."/$file");
				$report[] = " _notes folder: $file (<strong>folder deleted</strong>)";
			} else if (substr($file,$l-8)=="humbs.db") {
				unlink(CONS_PATH_PAGES.$_SESSION['CODE']."/$file");
				$report[] = " Thumbs.db: $file (<strong>file deleted</strong>)";
			}
		}
		if ($orphans>0) {
			$hadError3 = true;
			$report[] = "<strong>-Useless files found: $orphans files</strong>";
		} else
			$report[] = "<strong>-No useless files</strong>";

		# - # End
		if (!$quick) {
			$report[] = "<strong>Integrity Check complete at ".date("H:i:s d/m/Y")." with ".$this->parent->dbo->dbc." DB queries run taking ".$this->parent->dbo->dbt."ms</strong>";
			echo "<pre>".implode("<br/>",$report)."</pre>";
		}
		if ($die) {
			$this->parent->close(true);
			die();
		}
		return $hadError3 || $hadError2 || $hadError;
	}

	private function dev_locale($die = true,$quick = false) {# Search metadata ...
		$terms = array();
		$sourceIncluded = false;
		# module (model)
		foreach ($this->parent->modules as $modulename => &$moduleObj) {
			if (!in_array($modulename,$terms)) {
				if (!$sourceIncluded) {
					$terms[] = "-- Modules";
					$sourceIncluded = true;
				}
				$terms[] = $modulename;
			}
			if (!$moduleObj->options[CONS_MODULE_SYSTEM]) {
				foreach ($moduleObj->fields as $fieldname => &$fieldObj) {
					if (!in_array($fieldname,$terms)) {
						if (!$sourceIncluded) {
							$terms[] = "-- Modules";
							$sourceIncluded = true;
						}
						$terms[] = $fieldname;
					}
					if ($fieldObj[CONS_XML_TIPO] == CONS_TIPO_ENUM) {
						preg_match("@ENUM \(([^)]*)\).*@",$fieldObj[CONS_XML_SQL],$regs);
						$enums = explode(",",$regs[1]);
						foreach ($enums as $enum) {
							$enum = str_replace("'","",$enum);
							if ($enum != '' && !in_array($enum,$terms)) {
								if (!$sourceIncluded) {
									$terms[] = "-- Modules";
									$sourceIncluded = true;
								}
								$terms[] = $enum;
							}
						}
					}
				}
			}
		}
		# Search i18n translators at the template
		$tpl = listFiles(CONS_PATH_PAGES.$_SESSION['CODE']."/template/","@.*\.html?@i",false,false,true);
		foreach ($tpl as $file) {
			$sourceIncluded = false;
			$template = new CKTemplate();
			$template->fetch(CONS_PATH_PAGES.$_SESSION['CODE']."/template/$file");
			if ($template->errorsDetected && !$quick) {
				echo "Error reported on ".CONS_PATH_PAGES.$_SESSION['CODE']."/template/$file<br/>";
			}

			$tags = $template->getAllTags(true);
			if (isset($tags['_t']))
				foreach ($tags['_t'] as $tag) {
					if ($tag != '' && !in_array($tag,$terms)) {
						if (!$sourceIncluded) {
							$terms[] = "-- /template/$file";
							$sourceIncluded = true;
						}
						$terms[] = $tag;
					}
				}
		}

		# Search i18n translators at plugin templates
		foreach ($this->parent->loadedPlugins as $pname => &$pobj) {
			$sourceIncluded = false;
			$tpl = listFiles(CONS_PATH_SYSTEM."plugins/$pname/payload/template/","@.*\.html?@i",false,false,true);
			foreach ($tpl as $file) {
				$template = new CKTemplate();
				$template->fetch(CONS_PATH_SYSTEM."plugins/$pname/payload/template/$file");
				if ($template->errorsDetected && !$quick) {
					echo "Error reported on ".CONS_PATH_SYSTEM."plugins/$pname/payload/template/$file<br/>";
				}
				$tags = $template->getAllTags(true);
				if (isset($tags['_t']))
					foreach ($tags['_t'] as $tag) {
						if ($tag != '' && !in_array($tag,$terms)) {
							if (!$sourceIncluded) {
								$terms[] = "-- Plugin $pname /template/$file";
								$sourceIncluded = true;
							}
							$terms[] = $tag;
						}
					}
			}
		}

		# ok
		$totalTerms = count($terms);

		# now which terms are NOT translated?
		$notTranslated = array();
		$context = "";
		$lastcontext = "";
		$contexts = 0;
		foreach ($terms as $term) {
			$sourceIncluded = false;
			if ($term[0] != "-") {
				if (!isset($this->parent->template->lang_replacer[$term])) {
					if (!$sourceIncluded) {
						if ($lastcontext != $context) $notTranslated[] = $context;
						$lastcontext = $context;
						$contexts++;
						$sourceIncluded = true;
					}
					$notTranslated[] = $term;
				}
			} else {
				$context = $term;
			}
		}
		$totalNotTranslated = count($notTranslated) - $contexts;
		if (!$quick) {
			echo "i18n translators for this language: <strong>".$_SESSION[CONS_SESSION_LANG]."</strong><br/>";
			echo "Total terms: ".$totalTerms."<br/>";
			echo "<strong>not translated terms: ".$totalNotTranslated."</strong><br/>List of not translated:<br/>";
			echo "<blockquote>";
			foreach ($notTranslated as $nT) {
				echo $nT."<br/>";
			}
			echo "</blockquote>";
		}
		if ($die) {
			$this->parent->close(true);
			die();
		}
		return $totalNotTranslated>0?$notTranslated:false;
	}

	function log($die = true) {
		function appendErrors(&$core,&$output,&$template,$data) {
			foreach ($data as $line) {
				$line = explode("|",$line);
				# date|id_client|uri|errCode|module|parameters|extended parameters|log[|...]
				$thisData = array();
				$thisData['date'] = array_shift($line);
				$thisData['id_client'] = array_shift($line);
				$thisData['uri'] = array_shift($line);
				$thisData['errCode'] = array_shift($line);
				$thisData['module'] = array_shift($line);
				$thisData['parameters'] = array_shift($line);
				$thisData['extended'] = array_shift($line);
				$thisData['log'] = implode("|",$line);
				if (is_numeric($thisData['errCode']) && isset($core->errorControl->ERRORS[$thisData['errCode']])) {
					$errorLevel = $core->errorControl->ERRORS[$thisData['errCode']];
					$thisData['level'] = $errorLevel < 10?0:($errorLevel<20?1:2);
					$output .= $template->techo($thisData);

				}
			}
		}
		$outputTemplate = "<div style='line-height:25px;border-bottom:1px solid #999999;margin-bottom:3px;'>{date} {id_client}: <span style='color:red'>{errCode} ({_t}e{errCode}{/t})</span> [{module}] {parameters} (<strong>{extended}</strong>) @ {uri}</div>";
		$template = new CKTemplate($this->parent->template);
		$template->tbreak($outputTemplate);
		$this->parent->close(false);

		$temp = "";
		// 1 day ago
		$previousDay = datecalc(date("Y-m-d"),0,0,-1);
		$previousDay = str_replace("-","",$previousDay);
		if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/err".$previousDay.".log"))
			appendErrors($this->parent,$temp,$template,explode("\n",cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/err".$previousDay.".log")));

		# Today
		$previousDay = date("Ymd");
		if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/err".$previousDay.".log"))
			appendErrors($this->parent,$temp,$template,explode("\n",cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/err".$previousDay.".log")));
		if (is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/out".$previousDay.".log"))
			$temp .= nl2br(cReadFile(CONS_PATH_LOGS.$_SESSION['CODE']."/out".$previousDay.".log"));
		echo "Log files are located at ".CONS_PATH_LOGS.$_SESSION['CODE']."/<br/><br/>";
		echo $temp;
		echo "<br/><hr>";
		# httpd log?
		if (CONS_HTTPD_ERRFILE != '') {
			$httpderrlog = str_replace("{Y}",date("Y"),CONS_HTTPD_ERRFILE);
			$httpderrlog = str_replace("{m}",date("m"),$httpderrlog);
			$httpderrlog = str_replace("{d}",date("d"),$httpderrlog);
			if (is_file(CONS_HTTPD_ERRDIR.$httpderrlog)) {
				echo "<div style='font-color:red;margin-top:5px'>HTTPD errors detected on $httpderrlog log file:</div><br/><pre>";
				echo cReadFile(CONS_HTTPD_ERRDIR.$httpderrlog);
				echo "</pre>";
			}
		}
		if ($die) {
			$this->parent->close(true);
			die();
		}
	}

	private function dev_link($die=true,$quick=false) {
		$realpages = listFiles(CONS_PATH_PAGES.$_SESSION['CODE']."/template/","@^[^_](.*)\.htm(l?)$@i");
		$tmp = listFiles(CONS_PATH_PAGES.$_SESSION['CODE']."/actions/");
		$pages = array();

		foreach ($realpages as $x=>$page) { // removes extensions
			$page = explode(".",$page);
			array_pop($page);
			$pages[] = implode(".",$page);
		}
		foreach ($tmp as $x=>$page) { // adds actions that are not covered in content/template
			$page = explode(".",$page);
			array_pop($page);
			$page = implode(".",$page);
			if (!in_array($page,$pages)) $pages[] = $page;
		}

		if (isset($this->parent->dimconfig['_contentManager'])) { // add CMS pages
			if (!is_array($this->parent->dimconfig['_contentManager']))
				$this->parent->dimconfig['_contentManager'] = explode(",",$this->parent->dimconfig['_contentManager']);
			foreach ($this->parent->dimconfig['_contentManager'] as $page) {
				$page = substr($page,1); // removes initial /
				if ($page != '' && !in_array($page,$pages)) $pages[] = $page;
			}
		}

		if (isset($this->parent->dimconfig['_seoManager'])) { // add SEO alias
			if (!is_array($this->parent->dimconfig['_seoManager']))
				$this->parent->dimconfig['_seoManager'] = explode(",",$this->parent->dimconfig['_seoManager']);
			foreach ($this->parent->dimconfig['_seoManager'] as $page) {
				if ($page != '') {
					if ($page[0] == '/') $page = substr($page,1); // removes initial /
					if ($page != '' && !in_array($page,$pages)) $pages[] = $page;
				}
			}
		}

		$missingContent = array();

		if (!function_exists('xmlParamsParser')) include CONS_PATH_SYSTEM."lib/xmlHandler.php";

		foreach ($realpages as $page) {
			$xhtml = new xmlHandler();
			$xhtml->cReadXML(CONS_PATH_PAGES.$_SESSION['CODE']."/template/".$page,array(C_XML_AUTOPARSE=>true,C_XML_LAX=>true),true);
			$objects = $xhtml->XMLParsedContent();

			foreach ($objects[C_XHTML_LINKS] as $link) {
				$oL = $link;
				if ($link == '')
					$missingContent[] = $page.": Empty link";
				else if ($link[0] != "?" && $link[0] != "#" && strpos($link,"javascript:") === false && strpos($link,"mailto:") === false && strpos($link,"http") === false) {
					if (strpos($link,"{")!== false) {
						$link = explode("{",$link);
						$newLink = "";
						foreach ($link as $part){
							$part = explode("}",$part);
							$part[0] = explode("|",$part[0]);
							if ($part[0][0] == "seo") {
								$part = $part[0][1];
								if (strpos($part,".")!== false) {
									$part = explode(".",$part);
									array_pop($part);
									$part = implode(".",$part);
								}
								if (strpos($part,"?")!==false) {
									$part = explode("?",$part);
									array_pop($part);
									$part = implode("?",$part);
								}
								if ($part != '' && $part[0] == '/') $part = substr($part,1); // removes initial /
								if (!in_array($part,$pages))
									$missingContent[] = $page.": ".$part." (SEO from $oL) ";
							}
						}
					} else {
						if (strpos($link,".")!== false) {
							$link = explode(".",$link);
							array_pop($link);
							$link = implode(".",$link);
						}
						if (strpos($link,"?")!==false) {
							$link = explode("?",$link);
							array_pop($link);
							$link = implode("?",$link);
						}
						if ($link != '' && $link[0] == '/') $link = substr($link,1); // removes initial /
						if (!in_array($link,$pages))
							$missingContent[] = $page.": ".$link." ($oL)";
					}
				}
			}
		}
		if (!$quick) {
			if (count($missingContent)==0)
				echo "No link issues on ".count($realpages)." pages";
			else {
				echo "<b>".count($missingContent)." link issues on ".count($realpages)." pages:</b><br/>";
				echo implode($missingContent,"<br/>\n");
			}
		}
		if ($die) {
			$this->parent->close(true);
			die();
		}
		return count($missingContent)>0?$missingContent:false;
	}

	function dev_todo($die=true, $quick=false) {
		$report = array();
		$realpages = listFiles(CONS_PATH_PAGES.$_SESSION['CODE']."/template/");
		$hadError = false;
		foreach ($realpages as $page) {
			$content = cReadFile(CONS_PATH_PAGES.$_SESSION['CODE']."/template/".$page);
			if (strpos($content,"TODO:")!==false) {
				$report[] = "TODO tag located at $page";
				$hadError =true;
			}
		}
		unset($content);

		if ($this->parent->dimconfig['adminmail'] == '') {
			$report[] = "Adminmail (config) not filled";
			$hadError = true;
		}
		if ($this->parent->dimconfig['contactmail'] == '') {
			$report[] = "Contactmail (config) not filled";
			$hadError = true;
		}
		if ($this->parent->dimconfig['pagetitle'] == '') {
			$report[] = "Pagetitle (config) not filled";
			$hadError = true;
		}
		if ($this->parent->dimconfig['metakeys'] == '') {
			$report[] = "metakeys (config) not filled";
			$hadError = true;
		}
		if ($this->parent->dimconfig['metadesc'] == '') {
			$report[] = "metadesc (config) not filled";
			$hadError = true;
		}
		if (isset($this->parent->loadedPlugins['bi_newsletter']) && $this->parent->dimconfig['newslettersourcemail'] == '') {
			$report[] = "newsletterSourceMail (config) not filled";
			$hadError = true;
		}
		if (!$quick) {
			if (!$hadError) {
				echo "No pending TODO tags<br/>";
			} else
				echo implode("<br/>",$report);
		}

		if ($die) {
			$this->parent->close(true);
			die();
		}
		return $hadError?$report:false;


	}

	function fulltest($quick=false) {
		$this->parent->loadAllModules();
		if (!$quick) echo "<h1>LOCALE</h1>";
		$localeError = $this->dev_locale(false,$quick);
		if (!$quick) echo "<h1>INTEGRITY TEST</h1>";
		$integrityError = $this->dev_maint(false,$quick);
		if (!$quick) echo "<h1>LINK CHECK</h1>";
		$linkError = $this->dev_link(false,$quick);
		if (!$quick) echo "<h1>TODO CHECK</h1>";
		$todoError = $this->dev_todo(false,$quick);
		if (!$quick) echo "<h1>FAVICON</h1>";
		$favfile = CONS_PATH_PAGES.$_SESSION['CODE']."/files/favicon";
		$lastError = "";
		if ($linkError !== false) $lastError .= "<b>Links</b>:\n".implode("\n",$linkError)."\n";
		if ($todoError !== false) $lastError .= "<b>TODO</b>:\n".implode("\n",$todoError)."\n";
		if ($localeError !== false) $lastError .= "<b>Locale</b>:\n".implode(", ",$localeError)."\n";
		if (!locateFile($favfile,$ext) && !CONS_DEFAULT_FAVICON) {
			$lastError .= "<b>Favicon missing</b>\n";
			if (!$quick) echo "Warning: site has no favicon";
		} else {
			if (!$quick) echo "Favicon settings ok<br/>";
		}
		if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/mail/template.html")) {
			$lastError .= "<b>Mail template missing</b>\n";
			if (!$quick) echo "Warning: no mail template";
		} else {
			if (!$quick) echo "Mail template settings ok<br/>";
		}
		if (isset($this->parent->loadedPlugins['bi_newsletter']) && $this->parent->dimconfig['newslettersourcemail'] == '') {
			$lastError .= "<b>Newsletter outgout mail not set</b>\n";
		}
		if ($lastError == "" && is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/fulltest.log"))
			@unlink(CONS_PATH_LOGS.$_SESSION['CODE']."/fulltest.log");
		else
			cWriteFile(CONS_PATH_LOGS.$_SESSION['CODE']."/fulltest.log",$lastError);
		if (!$quick) {
			echo "<h1>RESULTS</h1>";
			echo "LOCALE TAGS are ".($localeError!==false?"<b>not ok</b>":"OK")."<br/>";
			echo "DATABASE was ".($integrityError!==false?"<b>not ok</b>":"OK")."<br/>";
			echo "LINKS are ".($linkError!==false?"<b>not ok</b>":"OK")."<br/>";
			echo "TODO LIST is ".($todoError!==false?"<b>not ok</b>":"OK")."<br/>";
			echo "<h1>CONFIGURATION</h1>";
			echo "Administrator E-mail:".$this->parent->dimconfig['adminmail'].'<br/>';
			echo "Default page name:".$this->parent->dimconfig['pagetitle'].'<br/>';
			if (isset($this->parent->loadedPlugins['bi_newsletter'])) {
				echo "Default Newsletter outgoing mail: ".$this->parent->dimconfig['newslettersourcemail']."<br/>";
			}
			$this->parent->close(true);
			die();
		}
		return $localeError || $integrityError || $linkError || $todoError;
	}

	function onCheckActions() {

		if (CONS_BROWSER_ISMOB && !isset($_SESSION['NOMOBVER'])) $this->devDisable = true; // auto-disable on mob

		if ($this->devDisable || CONS_BROWSER == "UN") return;

		$this->overheadTime = scriptTime() * 1000;
		if ($this->parent->layout == 2) return; # don't mess with ajax
		if (isset($_REQUEST['dev_locale'])) {
			$this->dev_locale();
		}
		if (isset($_REQUEST['dev_full'])) {
			$this->fulltest();
		}

		if (isset($_REQUEST['dev_help'])) {
			$tp = new CKTemplate();
			$tp->fetch(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/options.html");
			echo $tp->techo();
			$this->parent->close(true);
			die();
		}

		if (isset($_REQUEST['dev_test'])) {
			if (!isset($_SESSION['affbidevut'])) { # start up
				$pages = array();
				$_SESSION['affbidevut'] = array(0,$pages,array(),"end"); // current test, pages to test, error messages
				sleep(1);
				$this->parent->headerControl->internalFoward(CONS_INSTALL_ROOT.$_SESSION['affbidevut'][1][0]."?dev_test=1");
			}
			if ($_SESSION['affbidevut'][3] == "start") { // last script DIED on me! user used "back" button or typed /?dev_test=1 ... redirect to the proper NEXT page to test
				$_SESSION['affbidevut'][3] = "end";
				sleep(1);
				$this->parent->headerControl->internalFoward(CONS_INSTALL_ROOT.$_SESSION['affbidevut'][1][$_SESSION['affbidevut'][0]]."?dev_test=1");
			}
			$_SESSION['affbidevut'][3] = "start";
			$_SESSION['affbidevut'][0]++;
		}
		if (isset($_REQUEST['dev_log'])) {
			$this->log();
		}
		if (isset($_REQUEST['dev_maint'])) {
			$this->dev_maint(true);
		}
		if (isset($_REQUEST['dev_html'])) {
			$this->devCheckHTML = $_REQUEST['dev_html'] == '1' || $_REQUEST['dev_html'] == 'on';
			if (!$this->devCheckHTML) $_SESSION['bi_dev_disablehtml'] = true;
			else unset($_SESSION['bi_dev_disablehtml']);
		} else
			$this->devCheckHTML = !isset($_SESSION['bi_dev_disablehtml']);

		if (isset($_REQUEST['dev_disable'])) {
			$this->devDisable = $_REQUEST['dev_disable'] == '1' || $_REQUEST['dev_disable'] == 'on';
			$this->log[] = "Developer strip disabled. Reset session to activate it again";
			if ($this->devDisable) {
				$_SESSION['bi_dev_disable'] = true;
				unset($_SESSION['bi_dev_chtml']);
			}
		}

		if (isset($_REQUEST['dev_fill'])) {
			$this->fill();
		}
	}


	function on404($action, $context = "") {
		if (isset($_REQUEST['dev_test']) && isset($_SESSION['affbidevut'])) $this->unitTest();
		return false;
	}


	function onShow(){
		if ($this->parent->layout == 2 || $this->devDisable) return; # don't mess with ajax, FM or disabled mode
		if ($this->parent->template->get("CORE_DEBUG") === false)
			$this->log[] = "This page has no {CORE_DEBUG} output area!";
	}

	function onEcho(&$PAGE){

		if ($this->parent->layout == 2 || $this->parent->servingFile) return; # don't mess with ajax
		# Happens just after the template has been parsed (note it received the page as a STRING now), after this, is ECHO and DIE
		###### -> Construct should add this module to the onEcho array
		if (!$this->devDisable && CONS_BROWSER != "UN") {
			$thereAreErrors = false;
			if ($this->devCheckHTML || isset($_REQUEST['dev_test'])) {
				if (!function_exists('checkHTML')) include CONS_PATH_INCLUDE."checkHTML.php";
				$log = checkHTML($PAGE,false);
				if (count($log)>0) {
					$thereAreErrors = true;
					$this->log[] = implode("<br/>",$log); // for dev_test
					//$PAGE .= "<font color='red'>".implode("<br/>",$log)."</font>";
				}
				unset($log);
			}
			if (isset($_REQUEST['dev_test'])) {
				if (count($this->parent->log)>0 || count($this->parent->warning)>0 ) {
					# failed basic test , log it
					$_SESSION['affbidevut'][2][] = $this->parent->context_str.$this->parent->action." Reports errors:";
					foreach ($this->parent->log as $log)
						$_SESSION['affbidevut'][2][] = $log;
					foreach ($this->parent->warning as $log)
						$_SESSION['affbidevut'][2][] = $log;
				}
				$this->unitTest();
			} else  {

				$qs= $this->parent->action.".html?".arrayToString(false,array("login","gfc","haveinfo","password","debugmode","nosession","nocache","dev_html"));
				$totalTime = scriptTime() * 1000;

				// ###############################---
				// ## This is the info strip that stays on top of the site:

				array_unshift($this->log,number_format($totalTime,2)."ms (".CONS_AFF_DATABASECONNECTOR.": ".number_format($this->parent->dbo->dbt,2)."ms, framework: ".number_format($this->overheadTime,2)." ms), SQL(s): ".$this->parent->dbo->dbc.
							  ", caches: ".number_format($this->parent->cachetime/1000)."ms main, ".number_format($this->parent->cachetimeObj/1000)."ms obj".
							  (isset($this->parent->storage['CORE_CACHECONTROL'])?" avg: ".number_format($this->parent->storage['CORE_CACHECONTROL'][0]/1000)."s factor ".number_format($this->parent->storage['CORE_CACHECONTROL'][1],2):"").
							  " (".$_SESSION[CONS_SESSION_LANG].") (".($this->devCheckHTML?"<a style='color:#".$this->textColor."' href='$qs&dev_html=0'><strong>checkHTML</strong> is on</a>":"<a style='color:#".$this->textColor."' href='$qs&dev_html=1'><strong>checkHTML</strong> is off</a>").
							  ") (".($thereAreErrors?"<strong><a style='color:#".$this->textColor."' href='?dev_log=1'>Errors!</a></strong>":"no errors").") (<a style='color:#".$this->textColor."' href='?debugmode=true&nosession=true&nocache=true'>RESET</a>)".
							  " (<a style='color:#".$this->textColor."' href='?dev_help=1'>DEVELOPER OPTIONS</a>) (<a style='color:#".$this->textColor."' href=\"".$qs."&dev_disable=1\">disable</a>)".($this->parent->cacheControl->contentFromCache?" CACHED CONTENT":""));

				// ###############################---

				$pl = strlen($PAGE);
				$tp = new CKTemplate();
				$tp->fetch(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/overlay.html");
				$tp->assign("AFFBIDEV_CONTENT",implode("<br/>",$this->log).(count($this->parent->warning) != 0 ? "<br/>Warnings:".implode("<br/>",$this->parent->warning):""));

				$arrowColor = $this->parent->cacheControl->contentFromCache ? "#000099" : ($thereAreErrors ? "#BB0000" : "#000000");

				$tp->assign("ARROWCOLOR",$arrowColor);
				$tp->assign("ARROWSIZE",$thereAreErrors?20:12);
				$PAGE = str_replace("</body>",$tp->techo()."</body>",$PAGE);
				if (strlen($PAGE) == $pl) {
					$this->log[] = "WARNING: no /body on page";
					$PAGE .= $tp->techo();
				}
				$PAGE .= "<!-- bi_dev output logs. To stop this output, disable bi_dev";
				$PAGE .= "\nDbLOG:\n".implode("\n",$this->parent->dbo->log);
				$PAGE .= "\n".print_r($_SESSION,1)."\n";
				$PAGE .= "-->";
			}
		}
	}

	function unitTest() {
		if ($_SESSION['affbidevut'][0] >= count($_SESSION['affbidevut'][1])) {
			// unit test complete
			$hadErrors = count($_SESSION['affbidevut'][2])>0;
			$_SESSION['affbidevut'][2][] = "Test complete".($hadErrors?" - errors found:".count($_SESSION['affbidevut'][2]):"<b> - NO ERRORS!</b>");
			echo "<pre>".(implode("\n",$_SESSION['affbidevut'][2]))."</pre>";
			echo "<a href='/'>Ok - back to index</a>";
			unset($_SESSION['affbidevut']);
			$this->parent->close();
		} else {
			$_SESSION['affbidevut'][3] = "end";
			echo "Errors so far: ".count($_SESSION['affbidevut'][2])." - (page ".$_SESSION['affbidevut'][0]." of ".(count($_SESSION['affbidevut'][1])).") - if the next page is blank, just hit the back button";
			echo "<script type='text/javascript'><!--\nfunction gotest(){\ndocument.location='".CONS_INSTALL_ROOT.$_SESSION['affbidevut'][1][$_SESSION['affbidevut'][0]]."?dev_test=1';\n}\n";
			echo "setTimeout(gotest,1000);\n//--></script>";
			$this->parent->close();
		}
	}

	function importer() {
		$htmlIMG = $_REQUEST['imgpath'];
		$cssIMG = $_REQUEST['cssimgpath'];

		// improves/fix css, in and out
		$cssFiles = listFiles(CONS_PATH_PAGES.$_SESSION["CODE"]."/files/",'/^.*\.css$/i');
		foreach ($cssFiles as $cF) {
			$css = cReadFile(CONS_PATH_PAGES.$_SESSION["CODE"]."/files/".$cF);
			$css = str_replace($cssIMG,"",$css);
			$css = str_replace("    ","\t",$css);
			cWriteFile(CONS_PATH_PAGES.$_SESSION["CODE"]."/files/".$cF,$css);
		}

		// improves/fix html, in
		$htmlFiles = listFiles(CONS_PATH_PAGES.$_SESSION["CODE"]."/template/",'/^([^_]).*\.html$/i');
		$htmlSTR = array();
		$cut = array();
		foreach ($htmlFiles as $hF) {
			$htmlSTR[$hF] = cReadFile(CONS_PATH_PAGES.$_SESSION["CODE"]."/template/".$hF);
			$htmlSTR[$hF] = str_replace($htmlIMG,"{IMG_PATH}",$htmlSTR[$hF]);
			$htmlSTR[$hF] = str_replace("    ","\t",$htmlSTR[$hF]);
			$bodyPos = strpos($htmlSTR[$hF],"<body>");
			if ($bodyPos !== false) {
				$htmlSTR[$hF] = substr($htmlSTR[$hF],$bodyPos+6);
				$htmlSTR[$hF] = str_replace("</body>","",$htmlSTR[$hF]);
			} else {
				$bodyPos = strpos($htmlSTR[$hF],"<body");
				if ($bodyPos !== false && $bodyPos != 0)
					$htmlSTR[$hF] = substr($htmlSTR[$hF],$bodyPos-1);
			}
			$htmlSTR[$hF] = str_replace("</html>","",$htmlSTR[$hF]);
			cWriteFile(CONS_PATH_PAGES.$_SESSION["CODE"]."/template/".$hF.".out",$htmlSTR[$hF]);
		}

		// locate patterns within the files, using index.html

		//{CORE_DEBUG} {FRAME_CONTENT}
		echo "css replaced, html outputed as .out, frame breaking not implemented"; #TODO:

		die();
	}

	function onCron($isDay=false) {
		# cron Triggered, isDay or isHour
		###### -> Construct should add this module to the onCron array
		if (!$isDay) {
			if ($this->fulltest(true)) {
				$pms = strtolower(trim(ini_get('post_max_size')));
				$pmsv = $pms[strlen($pms)-1];
				$pms = substr($pms,0,strlen($pms)-1);
				$umfs = strtolower(trim(ini_get('upload_max_filesize')));
				$umfsv = $umfs[strlen($umfs)-1];
				$umfs = substr($umfs,0,strlen($umfs)-1);
				switch ($pmsv) {
					case 'g':
						$pms *= 1024;
					case 'm':
						$pms *= 1024;
					case 'k':
						$pms *= 1024;
				}
				switch ($umfsv) {
					case 'g':
						$umfs *= 1024;
					case 'm':
						$umfs *= 1024;
					case 'k':
						$umfs *= 1024;
				}
				if ($pms < 12582912) $this->parent->errorControl->raise(523,'post_max_size','bi_dev');
				if ($umfs < 10485760) $this->parent->errorControl->raise(523,'upload_max_filesize','bi_dev');
				$this->parent->errorControl->raise(522,"bi_dev","affibi_dev");
			}
		}
	}


}