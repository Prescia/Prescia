<?/* -------------------------------- Prescia Core (Debug mode)
  | Copyleft (ɔ) 2012+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
-*/

define("CONS_SQL_QUOTE","`");

class CPresciaFull extends CPrescia {

	var $dbchanged = false;

	function __construct(&$dbo, $debugmode=true) {
		parent::__construct($dbo,$debugmode);
		$this->dbo->quickmode = false; // will log EVERY SINGLE SQL
	}

	# loads a module and sets is as loaded (on operation mode, modules are only referenced without loading)
	function loadModule($name, $dbname="") {
		if ($this->loaded($name,true)) {
			# redefining module (.xml is redefining a module already present in other .xml)
			return;
		}
		$this->modules[$name] = new CModule($this, $name, $dbname);
		$this->modules[$name]->loaded = true;
	} # loadModule (override)

	# checks all file structures. At least base folders, config.php and meta.xml must exist
	function checkinstall() {
		$size = count($this->log);
		if (!is_dir(CONS_PATH_PAGES)) $this->log[] = "BASE folder missing: ".CONS_PATH_PAGES;
		if (!is_dir(CONS_PATH_PAGES.$_SESSION['CODE'])) $this->log[] = "Site folder missing: ".CONS_PATH_PAGES.$_SESSION['CODE']."/";
		if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/config.php")) $this->log[] = "Site php config missing: ".CONS_PATH_PAGES.$_SESSION['CODE']."/_config/config.php";
		# perform chmod checks

		if (is_file(CONS_PATH_TEMP."test.tmp")) @unlink(CONS_PATH_TEMP."test.tmp");
		if (!cWriteFile(CONS_PATH_TEMP."test.tmp","writeok"))
			$this->log[] = "Permission denied to write on ".CONS_PATH_TEMP;
		@unlink(CONS_PATH_TEMP."test.tmp");

		if (is_file(CONS_PATH_LOGS."test.tmp")) @unlink(CONS_PATH_LOGS."test.tmp");
		if (!cWriteFile(CONS_PATH_LOGS."test.tmp","writeok"))
			$this->log[] = "Permission denied to write on ".CONS_PATH_LOGS;
		@unlink(CONS_PATH_LOGS."test.tmp");

		if (is_file(CONS_PATH_CACHE."test.tmp")) @unlink(CONS_PATH_CACHE."test.tmp");
		if (!cWriteFile(CONS_PATH_CACHE."test.tmp","writeok"))
			$this->log[] = "Permission denied to write on ".CONS_PATH_CACHE;
		@unlink(CONS_PATH_CACHE."test.tmp");

		if (is_file(CONS_PATH_DINCONFIG."test.tmp")) @unlink(CONS_PATH_DINCONFIG."test.tmp");
		if (!cWriteFile(CONS_PATH_DINCONFIG."test.tmp","writeok"))
			$this->log[] = "Permission denied to write on ".CONS_PATH_DINCONFIG;
		@unlink(CONS_PATH_DINCONFIG."test.tmp");

		return ($size == count($this->log));
	} # checkinstall;

	function checkConfig() {
		# checks if these fields exist
		$dimconfig = array( 'adminmail' => '', # on server, mails errors to this account
							'contactmail' => '', # default contact mail for forms. TO mails should default to this
							//'originmail' => '', # defailt mail FROM the system. FROM should default to this
							'pagetitle' => "", # page HEADER TITLE
							'metakeys' => "", # meta keywords
							'metadesc' => "", # meta description
							'metafigure' => "", # meta figure/image that should be a file inside the files/ folder of the site
							'quota' => '1048576000', # default 1Gb
							'_scheduledCronDay' => rand(0,28), # fill this with a valid 1~28 to special cron activity (backup, optimize), set to 0 to do EVERY DAY
							'_scheduledCronDayHour' => rand(1,23), # prefered hour to run the scheduledCron
							'_usedquota' => 0, #estimative, updated with real value on CRON
							'_errcontrol' => 0, # (system) number of errors today (uses cronD as "Today")
							'_cronD' => date("d"), # (system) last time cronD run
							'_cronH' => date("H"), # (system) last time cronH run
							'minlvltooptions' => 90, # on admins, minimal level to change main options
							);
		foreach ($dimconfig as $idx => $val)
			if (!isset($this->dimconfig[$idx]))
				$this->dimconfig[$idx] = $val;
		$this->saveConfig(true);
	}

	# reads metadata and loads it, also call database checks and cache creation.
	function loadMetadata() {
		if (!$this->debugmode) {
			return parent::loadMetadata();
		}
		$this->errorControl->raise(1000);
		$this->log = array(); // we don't want the above "log" to cause an abort (yes, this function uses the log size to confirm an error - lame but extremelly effective)
		$this->allModulesLoaded = true;

		# initial clean up and check
		if (!is_dir(CONS_PATH_TEMP)) safe_mkdir(CONS_PATH_TEMP);
		if (!is_dir(CONS_PATH_CACHE)) safe_mkdir(CONS_PATH_CACHE);
		if (!is_dir(CONS_PATH_DINCONFIG)) safe_mkdir(CONS_PATH_DINCONFIG);
		if (!is_dir(CONS_PATH_CACHE."locale/")) safe_mkdir(CONS_PATH_CACHE."locale/");
		if (!is_dir(CONS_PATH_LOGS)) safe_mkdir(CONS_PATH_LOGS);
		if (!is_dir(CONS_PATH_LOGS.$_SESSION['CODE']."/")) safe_mkdir(CONS_PATH_LOGS.$_SESSION['CODE']."/");
		if (!is_dir(CONS_PATH_DINCONFIG.$_SESSION['CODE']."/")) safe_mkdir(CONS_PATH_DINCONFIG.$_SESSION['CODE']."/");
		if (!is_dir(CONS_PATH_CACHE.$_SESSION['CODE']."/")) safe_mkdir(CONS_PATH_CACHE.$_SESSION['CODE']."/");
		if (!is_dir(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/")) safe_mkdir(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/");
		if (!is_dir(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/locale")) safe_mkdir(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/locale/");
		if (!is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/")) safe_mkdir(CONS_PATH_PAGES.$_SESSION['CODE']."/");
		if (!is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/actions/")) safe_mkdir(CONS_PATH_PAGES.$_SESSION['CODE']."/actions");
		if (!is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/content/")) safe_mkdir(CONS_PATH_PAGES.$_SESSION['CODE']."/content");
		if (!is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/locale/")) safe_mkdir(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/locale");
		if (!is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/files/")) safe_mkdir(CONS_PATH_PAGES.$_SESSION['CODE']."/files");
		if (!is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/template/")) {
			 safe_mkdir(CONS_PATH_PAGES.$_SESSION['CODE']."/template");
			 copy(CONS_PATH_SETTINGS."defaults/basefile.html",CONS_PATH_PAGES.$_SESSION['CODE']."/template/basefile.html");
			 copy(CONS_PATH_SETTINGS."defaults/index.html",CONS_PATH_PAGES.$_SESSION['CODE']."/template/index.html");
		}
		if (!is_dir(CONS_PATH_PAGES.$_SESSION['CODE']."/mail/")) safe_mkdir(CONS_PATH_PAGES.$_SESSION['CODE']."/mail");

		# Dimconfig
		if (is_file(CONS_PATH_DINCONFIG.$_SESSION['CODE']."/din.dat"))
			$this->dimconfig = unserialize(cReadFile(CONS_PATH_DINCONFIG.$_SESSION['CODE']."/din.dat"));
		if ($this->dimconfig === false) $this->dimconfig = array(); # Error on load
		$this->checkConfig();
		# clear the meta cache
		if (!$this->offlineMode) {
			$files = listFiles(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/");
			foreach ($files as $file)
				if (is_file(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/".$file)) {
					@unlink (CONS_PATH_CACHE.$_SESSION['CODE']."/meta/".$file);
				}
			if (!$this->checkinstall()) {
				$this->errorControl->raise(118,array_unshift($this->log));
			}

			if (isset($_REQUEST['nocache'])) {
				recursive_del(CONS_PATH_CACHE.$_SESSION['CODE']."/pages/",true);
				recursive_del(CONS_PATH_CACHE.$_SESSION['CODE']."/",false,'cache');
			}

		}

		# If no database, we are done
		if ($this->dbless) return count($this->log) == 0;

		# Search all necessary model files
		$parseXMLparams = array(C_XML_RAW => true, C_XML_AUTOPARSE => true, C_XML_REMOVECOMMENTS => true);
		$xml = new xmlHandler();
		$model = is_file(CONS_PATH_SETTINGS."default.xml")?cReadFile(CONS_PATH_SETTINGS."default.xml")."\n":'';

		foreach ($this->loadedPlugins as $scriptName => $scriptObj) {
			if (is_file(CONS_PATH_SYSTEM."plugins/".$scriptName."/meta.xml"))
				$model .= cReadFile(CONS_PATH_SYSTEM."plugins/".$scriptName."/meta.xml")."\n";
		}
		unset($scriptName); unset($scriptObj);
		if (is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/meta.xml"))
			$model .= cReadFile(CONS_PATH_PAGES.$_SESSION['CODE']."/_config/meta.xml")."\n";

		$model = $xml->parseXML($model,$parseXMLparams,true);
		unset($xml);
		if ($model === false) $this->errorControl->raise(119);

	  	# browses the XML and loads modules
	  	$model = &$model->getbranch(0);
	  	$total = $model->total();
	  	$relation = array(); # foreign keys are only created later

		$lastLoad = "";
	  	for ($c=0;$c<$total;$c++) { # for each module ...
			$thisbranch = &$model->getbranch($c);

			$total_campos = $thisbranch->total();
			# creates the module as from XML settings
			$module = strtolower($thisbranch->data[0]);
			$param = &$thisbranch->data[1];
			$dbname = strtolower(isset($param['dbname'])?$param['dbname']:'');

			foreach ($this->modules as $name => $otherModule) {
				if ($otherModule->dbname == $dbname && $dbname != "" && $module != $otherModule->name)
					$this->errorControl->raise(120,$otherModule->name,$name,$dbname);
			}
			if ($module == '') $this->errorControl->raise(107,$dbname,"XML error","Module after $lastLoad is corrupt");
			$this->loadModule($module,$dbname); #MODULE CREATE
			$lastLoad = $module;
			# loads standard data from this object ---------------------------------------------------------------------

			# read parameters for the MODULE
			foreach ($this->moduleOptions as $mo) {
				$this->modules[$module]->options[$mo[0]] = $mo[3] != ''?array():'';
			}
			if (is_array($param)) {
				foreach ($param as $pkey => $pcontent) {
					$pkey = strtolower($pkey);
					switch ($pkey) {
						case "key":
						case "keys":
							# will use default auto_increment "id" if none specified. If you specify more than one, none will be auto_increment and the system will use auto-numbering
								$this->modules[$module]->keys = explode(",",$pcontent);
							break;
							case "title":
								$this->modules[$module]->title = strtolower($pcontent);
							break;
						case "volatile":
							# this module can be deleted as a stand-alone volatile item
							$this->modules[$module]->options[CONS_MODULE_VOLATILE] = strtolower($pcontent)=="true";
						break;
			  			case "parent":
			  				$this->modules[$module]->options[CONS_MODULE_PARENT] = strtolower($pcontent); // field which denotes parenthood
			  			break;
			  			case "plugins":
						case "plugin":
			  					$this->modules[$module]->plugins = explode(",",strtolower($pcontent));
			  				break;
			  			case "order":
			  				$this->modules[$module]->order = trim(strtolower($pcontent));
			  			break;
			  			case "permissionoverride":
			  				if (strlen($pcontent)>=9)
			  					$this->modules[$module]->permissionOverride = substr(strtolower($pcontent),0,9);
			  			break;
			  				case "linker":
			  					$this->modules[$module]->linker = true;
			  				break;
			  				case "systemmodule":
							$this->modules[$module]->options[CONS_MODULE_SYSTEM] = true;
						break;
						case "autoclean":
							$this->modules[$module]->options[CONS_MODULE_AUTOCLEAN] = $pcontent;
						break;
						case "meta":
							$this->modules[$module]->options[CONS_MODULE_META] = $pcontent;
						break;
						case "disallowmultiple":
							if (strtolower($pcontent) == "true")
								$this->modules[$module]->options[CONS_MODULE_DISALLOWMULTIPLE] = true;
							else
								unset($this->modules[$module]->options[CONS_MODULE_DISALLOWMULTIPLE]);
						break;
						case "noundo":
							if (strtolower($pcontent) == "true")
								$this->modules[$module]->options[CONS_MODULE_NOUNDO] = true;
							else
								unset($this->modules[$module]->options[CONS_MODULE_NOUNDO]);
			  				default:
			  					if ($pkey != "name" && $pkey != "dbname") {
			  						$isMO = false;
			  						foreach ($this->moduleOptions as $mo) {
			  							if ($mo[1] == $pkey) {
			  								$isMO = true;
			  								if ($mo[2]) $pcontent = strtolower($pcontent);
			  								if ($mo[3] != '') $pcontent = explode($mo[3],$pcontent);
			  								$this->modules[$module]->options[$mo[0]] = $pcontent;
			  								break;
			  							}
			  						}
			  						if (!$isMO) {
			  							$this->modules[$module]->options[$pkey] = $pcontent;
			  						}
			  					}
			  				break;
					}
				} #foreach
				unset($pkey); unset($pcontent);
			}
			if ($this->modules[$module]->options[CONS_MODULE_PARENT] != '' && strpos($this->modules[$module]->order,$this->modules[$module]->options[CONS_MODULE_PARENT])===false) {
				# in tree mode, the field that defines parenthood must be in the order clause, the first if possible
				$this->modules[$module]->order = $this->modules[$module]->options[CONS_MODULE_PARENT]."+".( $this->modules[$module]->order != '' ? ",".$this->modules[$module]->order : '');
			}
			# -- ok on reading parameters
			$campos = array();
			$mandatory = 0;

			# browse FIELDS ---------------------------------------------------------------------------------

			for ($campo=0;$campo<$total_campos;$campo++) {

		  		$thiscampo= &$thisbranch->getbranch($campo);

				## processParameters #########################################
		  		$campos = $this->processParameters($thiscampo,$campos,$module);
				##############################################################

				$nomecampo = strtolower($thiscampo->data[0]);
		  		if ($campos[$nomecampo][CONS_XML_TIPO] == CONS_TIPO_LINK) {
		  			array_push($relation,array($module,$nomecampo,$campos[$nomecampo][CONS_XML_MODULE]));
					// if this is a non-mandatory link to myself, called "id_parent", and I don't have parent ... well .. obviously this is it
					if ($campos[$nomecampo][CONS_XML_MODULE] == $module && !isset($campos[$nomecampo][CONS_XML_MANDATORY]) && $nomecampo == "id_parent" && $this->modules[$module]->options[CONS_MODULE_PARENT] == '') {
						$this->modules[$module]->options[CONS_MODULE_PARENT] = $nomecampo;
					}
				} else if ($campos[$nomecampo][CONS_XML_TIPO] == CONS_TIPO_SERIALIZED) {
					// browse fields looking for links
					foreach ($campos[$nomecampo][CONS_XML_SERIALIZEDMODEL] as $exname => &$exfield) {
						if ($exfield[CONS_XML_TIPO] == CONS_TIPO_LINK) {
							array_push($relation,array($module,$nomecampo.":".$exname,$exfield[CONS_XML_MODULE]));
						}
					}
				}

		  		# checks if this field can be NULL or NOT depending on options and mandatory setting
				if (isset($campos[$nomecampo][CONS_XML_SQL]) && $campos[$nomecampo][CONS_XML_SQL] != "") { # relation will not be set
					if (isset($campos[$nomecampo][CONS_XML_MANDATORY]) || $campos[$nomecampo][CONS_XML_TIPO] == CONS_TIPO_OPTIONS || isset($campos[$nomecampo][CONS_XML_DEFAULT])) {
						$campos[$nomecampo][CONS_XML_SQL] .= " NOT NULL";
						$mandatory++;
					} else
						$campos[$nomecampo][CONS_XML_SQL] .= " NULL";
					if (isset($campos[$nomecampo][CONS_XML_DEFAULT]))
						$campos[$nomecampo][CONS_XML_SQL] .= " DEFAULT '".$campos[$nomecampo][CONS_XML_DEFAULT]."'";
				}
				
			}

			# this module has a database (it's possible to have modules without a database)
			if ($this->modules[$module]->dbname != "") {

				# checks standard key "id" if no key specified
				if (in_array("id",$this->modules[$module]->keys) && !isset($this->modules[$module]->fields['id']) && !isset($campos['id'])) {
					if ($this->modules[$module]->linker) {
						$this->modules[$module]->keys = array();
						$keys = 0;
						foreach ($campos as $fieldname => $fieldobj) {
							if (isset($fieldobj[CONS_XML_MODULE])) {
								$keys++;
								$this->modules[$module]->keys[] = $fieldname;
								if ($keys==2) break;
							}
						}
						unset($fieldname); unset($fieldobj);
					} else {
						$campos['id'][CONS_XML_SQL] = "INT (11) UNSIGNED NOT NULL".(count($this->modules[$module]->keys)<=1?" AUTO_INCREMENT":"");
						$campos['id'][CONS_XML_TIPO] = CONS_TIPO_INT;
						if (count($this->modules[$module]->keys)>1) {
							$campos['id'][CONS_XML_RESTRICT] = 99;
						}
					}
	  			}
				# -- keys (this is done to prevent repeated keys)
	  			$chave = $this->modules[$module]->keys;
	  			$this->modules[$module]->keys = array();
	  			foreach($chave as $x => $di)
					if (!in_array($di,$this->modules[$module]->keys) && $di!="") array_push($this->modules[$module]->keys,$di);
	  			unset($x); unset($di);
				# if this is a re-definition, will TOTALLY overright the fields (you can redefine fields from the default.xml on the meta.xml)
	  			$this->modules[$module]->fields = array_merge($this->modules[$module]->fields,$campos);
	 			# -- makes sure all keys are mandatory and present
	  			foreach($this->modules[$module]->keys as $x => $chave) {
	  				if (!isset($this->modules[$module]->fields[$chave])) {
						array_push($this->log,"Key not defined, considering INT 11, please fix the XML: $module.$chave");
						$this->modules[$module]->fields[$chave] = array("CONS_XML_SQL" => "INT (11) UNSIGNED NOT NULL",
																		"CONS_XML_TIPO" => CONS_TIPO_INT);
	  				}
					$this->modules[$module]->fields[$chave][CONS_XML_MANDATORY] = true;
					// vc keys without case specified, force ucase
					if ($this->modules[$module]->fields[$chave][CONS_XML_TIPO] == CONS_TIPO_VC && !isset($this->modules[$module]->fields[$chave][CONS_XML_SPECIAL]))
						$this->modules[$module]->fields[$chave][CONS_XML_SPECIAL] = "ucase";
	  			}
	  			unset($x); unset($chave);
			}
  		} # -- foreach module

  		$total_relacoes = count($relation);
  		# check our relationship counts and build proper fields or support tables -------------
	  	for ($c=0;$c<$total_relacoes;$c++) {
			$rel = $relation[$c]; # relation: MODULE => FIELD => MODULE or MODULE => SFIELD:FIELD => MODULE for serialized fields
			if (!isset($this->modules[$rel[0]]) || !isset($this->modules[$rel[2]])) {
				array_push($this->log,"Error (pass 1) trying to build foreign keys from '".$rel[0]."' to '".$rel[2]."' at ".$rel[1].": one of the modules do not exist, ignoring relation");
			} else {
				$sfield = "";
				if (strpos($rel[1],":") !== false) {
					#serialized field
					$field = explode(":",$field);
					$sfield = $field[0];
					$field = $field[1];
				} else
					$field = $rel[1];
				if (substr($field,0,3) != "id_")
					array_push($this->log,"All relations to another modules MUST start with id_ on ".$rel[0]."' to '".$rel[2]."' at ".$rel[1].": should be id_".$field." ?");
				if ($sfield == '')
					$this->modules[$rel[2]]->volatile = false; # keeps volatile if linked from serialized (a.k.a. serialized links are not safe, because they are meant to be dinamic)
				foreach($this->modules[$rel[2]]->keys as $x => $chave) {
					# will create required keys for foreign table, except any one in common with this table
					if ($chave == "id" || !isset($this->modules[$rel[0]]->fields[$chave])) {
						# only standard id exists (always link it), or it's not a standard key ... still have to test if it's not a key to this table
						# basically, this will create the second+ keys on multikey relations
					  	if (!($this->modules[$rel[2]]->fields[$chave][CONS_XML_TIPO] == CONS_TIPO_LINK && $this->modules[$rel[2]]->fields[$chave][CONS_XML_MODULE] == $rel[0])) {
							# ok not a key to this table (the FOREING key is not this table, pay attention! this will still be true for id_parent)
							if ($sfield == "") { # normal
								if ($chave == "id") { # uses the name that came in the XML model
							  		if (!isset($this->modules[$rel[0]]->fields[$field])) $this->modules[$rel[0]]->fields[$field] = array();
									$this->modules[$rel[0]]->fields[$field][CONS_XML_SQL] = str_replace("AUTO_INCREMENT","",$this->modules[$rel[2]]->fields[$chave][CONS_XML_SQL]);
									$this->modules[$rel[0]]->fields[$field][CONS_XML_TIPO] = CONS_TIPO_LINK;
									$this->modules[$rel[0]]->fields[$field][CONS_XML_LINKTYPE] = $this->modules[$rel[2]]->fields[$chave][CONS_XML_TIPO] != CONS_TIPO_LINK ? $this->modules[$rel[2]]->fields[$chave][CONS_XML_TIPO] : CONS_TIPO_INT;
									$this->modules[$rel[0]]->fields[$field][CONS_XML_MODULE] = $rel[2];
					  				# the creation system might have added this already, that's why testing before resetting the array
						  			if ((isset($this->modules[$rel[0]]->fields[$field][CONS_XML_JOIN]) && $this->modules[$rel[0]]->fields[$field][CONS_XML_JOIN] == "inner") || isset($this->modules[$rel[0]]->fields[$field][CONS_XML_MANDATORY])) {
						  				// is set join to INNER or is explicitly mandatory, make sure both are set
										$this->modules[$rel[0]]->fields[$field][CONS_XML_MANDATORY] = true;
										if ($x==0)  $this->modules[$rel[0]]->fields[$field][CONS_XML_JOIN] = "inner";
									} else {
										// no join mode set (defaults to left), set to left, and no explicit mandatory tag
										if ($x==0)  $this->modules[$rel[0]]->fields[$field][CONS_XML_JOIN] = "left";
										$this->modules[$rel[0]]->fields[$field][CONS_XML_SQL] = str_replace("NOT NULL","NULL",$this->modules[$rel[0]]->fields[$field][CONS_XML_SQL]);
						  			}
								} else {
									if ($x == 0) {
										$nome = $field; # first key keeps the original name
										$this->modules[$rel[0]]->fields[$field][CONS_XML_LINKTYPE] = $this->modules[$rel[2]]->fields[$chave][CONS_XML_TIPO] != CONS_TIPO_LINK ? $this->modules[$rel[2]]->fields[$chave][CONS_XML_TIPO] : CONS_TIPO_INT;
									} else
						  				$nome = $field."_".str_replace("id_","",$chave); # creates a composition with the model name and the foreign name
						  			$this->modules[$rel[0]]->fields[$nome][CONS_XML_SQL] = str_replace("AUTO_INCREMENT","",$this->modules[$rel[2]]->fields[$chave][CONS_XML_SQL]);
						  			$this->modules[$rel[0]]->fields[$nome][CONS_XML_TIPO] = $x==0?CONS_TIPO_LINK:$this->modules[$rel[2]]->fields[$chave][CONS_XML_TIPO];
						  			$this->modules[$rel[0]]->fields[$nome][CONS_XML_MODULE] = isset($this->modules[$rel[2]]->fields[$chave][CONS_XML_MODULE])?$this->modules[$rel[2]]->fields[$chave][CONS_XML_MODULE]:$rel[2];
								  	if ((isset($this->modules[$rel[0]]->fields[$field][CONS_XML_JOIN]) && $this->modules[$rel[0]]->fields[$field][CONS_XML_JOIN] == "inner") || isset($this->modules[$rel[0]]->fields[$nome][CONS_XML_MANDATORY])) {
										$this->modules[$rel[0]]->fields[$nome][CONS_XML_MANDATORY] = true;
										if ($x==0) $this->modules[$rel[0]]->fields[$nome][CONS_XML_JOIN] = "inner";
								  	} else {
										if ($x==0)  $this->modules[$rel[0]]->fields[$nome][CONS_XML_JOIN] = "left";
										unset($this->modules[$rel[0]]->fields[$nome][CONS_XML_MANDATORY]);
										$this->modules[$rel[0]]->fields[$nome][CONS_XML_SQL] = str_replace("NOT NULL","NULL",$this->modules[$rel[0]]->fields[$nome][CONS_XML_SQL]);
								  	}
								}
							} else { # serialized
								if ($chave == "id") { # uses the name that came in the XML model
							  		if (!isset($this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$field])) $this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$field] = array();
									$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$field][CONS_XML_SQL] = str_replace("AUTO_INCREMENT","",$this->modules[$rel[2]]->fields[$chave][CONS_XML_SQL]);
									$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$field][CONS_XML_TIPO] = CONS_TIPO_LINK;
									$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$field][CONS_XML_LINKTYPE] = $this->modules[$rel[2]]->fields[$chave][CONS_XML_TIPO] != CONS_TIPO_LINK ? $this->modules[$rel[2]]->fields[$chave][CONS_XML_TIPO] : CONS_TIPO_INT;
									$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$field][CONS_XML_MODULE] = $rel[2];
					  				# serialized links cannot be "inner"
					  				$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$field][CONS_XML_JOIN] = "left";
						  			if (isset($this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$field][CONS_XML_MANDATORY])) {
										$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$field][CONS_XML_MANDATORY] = true;
									} else {
										$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$field][CONS_XML_SQL] = str_replace("NOT NULL","NULL",$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$field][CONS_XML_SQL]);
						  			}
								} else {
									if ($x == 0) {
										$nome = $field; # first key keeps the original name
										$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$field][CONS_XML_LINKTYPE] = $this->modules[$rel[2]]->fields[$chave][CONS_XML_TIPO] != CONS_TIPO_LINK ? $this->modules[$rel[2]]->fields[$chave][CONS_XML_TIPO] : CONS_TIPO_INT;
									} else
						  				$nome = $field."_".str_replace("id_","",$chave); # creates a composition with the model name and the foreign name
						  			$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$nome][CONS_XML_SQL] = str_replace("AUTO_INCREMENT","",$this->modules[$rel[2]]->fields[$chave][CONS_XML_SQL]);
						  			$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$nome][CONS_XML_TIPO] = $x==0?CONS_TIPO_LINK:$this->modules[$rel[2]]->fields[$chave][CONS_XML_TIPO];
						  			$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$nome][CONS_XML_MODULE] = isset($this->modules[$rel[2]]->fields[$chave][CONS_XML_MODULE])?$this->modules[$rel[2]]->fields[$chave][CONS_XML_MODULE]:$rel[2];
								  	# serialized links cannot be "inner"
					  				$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$nome][CONS_XML_JOIN] = "left";
						  			if (isset($this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$nome][CONS_XML_MANDATORY])) {
										$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$nome][CONS_XML_MANDATORY] = true;
									} else {
										$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$nome][CONS_XML_SQL] = str_replace("NOT NULL","NULL",$this->modules[$rel[0]]->fields[$sfield][CONS_XML_SERIALIZEDMODEL][$nome][CONS_XML_SQL]);
						  			}
								}
							} # sfield?
				  		}
					} # secondary (multikey)?
			  	} # foreach
			  	unset($x); unset($chave);
			  	if (!isset($this->modules[$rel[0]]->fields[$field][CONS_XML_SQL])) {
			  		array_push($this->log,"Error (pass 2) trying to build foreing keys from ".$rel[0]." to ".$rel[2]." at ".$field.": ignoring relation");
			  	}
		  	}

		} # foreach for relations

		// now some automatic settings since all modules are loaded, and consistency check on build, partOf, etc ---------------------
		$cacheLinkNum = array(); // module => modules which link to this
		foreach ($this->modules as $mname => &$module) {

			$links = 0;
			$fieldsRequiredToLinks = 0;
			foreach ($module->fields as $name => $field) { // check for linker modules
				if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK && $field[CONS_XML_MODULE] != $mname) { // links to OTHER link not myself
					$links++; # do not count PARENTS as links
					$fieldsRequiredToLinks += count($this->modules[$field[CONS_XML_MODULE]]->keys); # a module can have more than one key, thus to know if this module is a linker module, we need to check if ALL THIS HAVE are the keys for 2 modules
					// vc links that have no case specified, force to upper
					if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK && $field[CONS_XML_LINKTYPE] == CONS_TIPO_VC && !isset($field[CONS_XML_SPECIAL]))
						$this->modules[$mname]->fields[$name][CONS_XML_SPECIAL] = "ucase";
				}
				if (isset($field[CONS_XML_FILTEREDBY])) {
					foreach ($field[CONS_XML_FILTEREDBY] as $fbname) {
						if (!isset($module->fields[$fbname]))
							$this->log[] = "Error on filteredby for $mname.$name: $fbname does not exist";
						else if (!isset($this->modules[$module->fields[$fbname][CONS_XML_MODULE]]))
							$this->log[] = "Error on filteredby for $mname.$name: module defined in $fbname does not exist";

					}
				}
			}

			if (($links == 2 && count($module->fields) == $fieldsRequiredToLinks) || $this->modules[$mname]->linker) { # this is a linker module!
				$this->modules[$mname]->linker = true;
			}

			if ($this->modules[$mname]->title == "" && !$this->modules[$mname]->options[CONS_MODULE_SYSTEM] && !$this->modules[$mname]->linker) {
				$this->modules[$mname]->title = $this->modules[$mname]->keys[0]; // first key
			}
		} # here we finished the automatic settings


		# load plugins that are defined by METADATA
		foreach ($this->modules as $name => &$module) {
			foreach ($module->plugins as $sname) {
				if (!isset($this->loadedPlugins[$sname])) {
					$this->addPlugin($sname,$name);
				} else
					$this->loadedPlugins[$sname]->moduleRelation = $name;
			}


		}

		foreach ($this->loadedPlugins as $sname => $obj) {
			if ($obj->name == '' || $obj->name != $sname) {
				$this->errorControl->raise(9,$obj->name,$sname);
			}
		}

		# DIE FREAKING THUMBS.DB, DIE!
		function dieFreakingThumbs($folder) {
			if ($folder[strlen($folder)-1] != '/') $folder .= "/";
			foreach(glob($folder."*") as $file) {
				if(is_dir($file))
					dieFreakingThumbs($file);
				else {
					$arf = explode(".",$file);
					if (array_pop($arf) == 'db')
						@unlink($file);
				}
			}
		}
		dieFreakingThumbs(CONS_PATH_PAGES.$_SESSION['CODE']."/");

		$customxml = is_file(CONS_PATH_PAGES.$_SESSION["CODE"]."/_config/custom.xml")?cReadFile(CONS_PATH_PAGES.$_SESSION["CODE"]."/_config/custom.xml"):'';
		# All plugins are loaded, check their manifest and customs
		foreach ($this->loadedPlugins as $sname => $plugin) {
			if (is_file(CONS_PATH_SYSTEM."plugins/$sname/payloadmanifest.php")) {
				$copyFiles = include(CONS_PATH_SYSTEM."plugins/$sname/payloadmanifest.php");
				foreach ($copyFiles as $from=>$to) {
					if ($from[strlen($from)-1] == "/" && is_dir($from) && (!is_dir($to) || (!CONS_ONSERVER && isset($_REQUEST['nocache'])))) { // FOLDER
						if (!function_exists('recursive_copy'))
							include_once(CONS_PATH_INCLUDE."recursive_copy.php");
						recursive_copy($from,$to);
					} else if (is_file($from) && (!is_file($to) || (!CONS_ONSERVER && isset($_REQUEST['nocache'])))) { // FILE
						$path = explode("/",$to);
						array_pop($path); // bye file
						$path = implode("/",$path);
						makeDirs($path);
						copy($from,$to);
					}
				}
			}
			if (is_file(CONS_PATH_SYSTEM."plugins/$sname/custom.xml"))
				$customxml .= cReadFile(CONS_PATH_SYSTEM."plugins/$sname/custom.xml");
		}

		# Read custom metadata for dimconfig
		if ($customxml != '') {
			$parseXMLparams = array(C_XML_RAW => true, C_XML_AUTOPARSE => true, C_XML_REMOVECOMMENTS => true);
			$xml = new xmlHandler();

			$customxml = $xml->parseXML($customxml,$parseXMLparams,true);
			if ($customxml === false) $this->errorControl->raise(180);
			unset($xml);
			$customxml = &$customxml->getbranch(0);
			$total = $customxml->total();

			$dimconfigMD = array(); // MetaData -------------------------------------
			for ($c=0;$c<$total;$c++) {
				# for each module ...
				$thisbranch = &$customxml->getbranch($c);
				$configname = strtolower($thisbranch->data[0]);
				if (!isset($this->dimconfig[$configname])) $this->dimconfig[$configname] = '';
				$dimconfigMD = $this->processParameters($thisbranch,$dimconfigMD,'');
			}
			foreach ($dimconfigMD as $name => $field) {
				if ($field[CONS_XML_TIPO] == CONS_TIPO_UPLOAD && (!isset($field['location']) || $field['location'][0] == '/')) {
					$this->errorControl->raise(181,$name,'dimconfig');
				}
				if ($field[CONS_XML_TIPO] != CONS_TIPO_ENUM) unset($dimconfigMD[$name][CONS_XML_SQL]);
			}
			cWriteFile(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/_dimconfig.dat",serialize($dimconfigMD)); // this defines the type of each item on dimconfig
		}

		# Apply and raise metadata
		$this->applyMetaData();

		# no log = no error
	  	return $sucess = count($this->log) ==0;
	} # loadMetadata
//--
	function applyMetaData() {
		# no error? check for sql changes
		if (count($this->log) ==0 && !$this->offlineMode) {
			$this->check_sql();
		}

		# start meta check from plugins
		foreach ($this->onMeta as $scriptName) {
			if (!isset($this->loadedPlugins[$scriptName]))
				$this->errorControl->raise(170,$scriptName,$scriptName);
			else {
				$this->loadedPlugins[$scriptName]->onMeta();
			}
		}

		# save config (all plugins also loaded their variables)
		$this->saveConfig();

		# if no error, then we can save main model structure
		if (count($this->log) ==0 && !$this->offlineMode) {
			if (!$this->save_model()) # turns the metadata in objects
				array_push($this->log,"Error trying to create metadata caches");
		}

		# no log = no error
		return $sucess = count($this->log) ==0;
	} # applyMetaData
//--
	function dbconnect() {
		if (!$this->debugmode) {
			return parent::dbconnect();
		}
		if (CONS_DB_HOST != '') {
			if (!$this->dbo->connect(1,CONS_OVERRIDE_DB==''?CONS_DB_HOST:CONS_OVERRIDE_DB,CONS_OVERRIDE_DBUSER==''?CONS_DB_USER:CONS_OVERRIDE_DBUSER,CONS_OVERRIDE_DBPASS==''?CONS_DB_PASS:CONS_OVERRIDE_DBPASS,CONS_DB_BASE)) {
				if (strpos($this->dbo->log[count($this->dbo->log)-1],"Unknown database") !== false) {
					# we can try to create the database ...
					$this->log[] = "Server not reachable or database not present";
					$tmpConnection = $this->dbo->connect(1,CONS_OVERRIDE_DB==''?CONS_DB_HOST:CONS_OVERRIDE_DB,CONS_OVERRIDE_DBUSER==''?CONS_DB_USER:CONS_OVERRIDE_DBUSER,CONS_OVERRIDE_DBPASS==''?CONS_DB_PASS:CONS_OVERRIDE_DBPASS,'');
					if ($tmpConnection) {
						$this->log[] = "Connected to server and trying to create database";
						$this->dbchanged = true;
						$ok = $this->dbo->simpleQuery("CREATE DATABASE ".CONS_SQL_QUOTE.CONS_DB_BASE.CONS_SQL_QUOTE." DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;");
						if (!$ok) {
							$this->log[] = "Unable to create database using CREATE DATABASE command";
							$this->errorControl->raise(122);
						} else {
							$this->dbo->close();
							$this->log[] = "Database created. Trying to load";
							if (!$this->dbo->select_db(CONS_DB_BASE)) {
								$this->errorControl->raise(123);
							}
						}
					}
				} else {
					return false;
				}
			}
		} else
			$this->dbless = true;
		return true;
	} # dbconnect (override)

	# checks if the database scheme is up-to-date
	function check_sql() {
		foreach($this->modules as $nome => $module) {
		  if ($module->dbname != "") {
			$chave = $module->keys[0];
			$sql = "SHOW TABLES LIKE '".$module->dbname."'";
			if (!$this->dbo->fetch($sql)) { # table does not exists (else would return it's name)
				$sql = "";
				foreach ($module->fields as $cnome => $campo) {
					if ($campo[CONS_XML_SQL] != "") {
						if ($cnome == 'id')
							$sql = CONS_SQL_QUOTE.$cnome.CONS_SQL_QUOTE." ".$campo[CONS_XML_SQL].",".$sql;
						else
							$sql .= CONS_SQL_QUOTE.$cnome.CONS_SQL_QUOTE." ".$campo[CONS_XML_SQL].",";
					}
				}
				$sql = "CREATE TABLE ".CONS_SQL_QUOTE.$module->dbname.CONS_SQL_QUOTE." ( $sql PRIMARY KEY(".implode(",",$module->keys).")) ENGINE=MYISAM";
				if ($this->dbo->simpleQuery($sql)) {
					$this->dbchanged = true;
					array_push($this->log,"Base ".$module->dbname." for ".$module->name." not detected and created!");
				} else {
					$this->errorState = true;
					array_push($this->log,"Base ".$module->dbname." for ".$module->name." not detected and an error occured while creating it! (triggered errorState)");
				}
			} else {
				# checks if all fields are ok
				$sql = "SHOW FIELDS FROM ".CONS_SQL_QUOTE.$module->dbname.CONS_SQL_QUOTE;
				$this->dbo->query($sql,$r,$n);
				$camposdb = array();
				$data = $this->dbo->fetch_row($r);
				while (is_array($data)){
					array_push($camposdb,$data[0]);
					$data = $this->dbo->fetch_row($r);
				}
				foreach ($module->fields as $nome => $campo) {
					if ($campo[CONS_XML_SQL] != "" && !in_array($nome,$camposdb)) {
						array_push($this->log,"Missing field at ".$module->dbname.":$nome");
						$sql = "ALTER TABLE ".CONS_SQL_QUOTE.$module->dbname.CONS_SQL_QUOTE." ADD ".CONS_SQL_QUOTE.$nome.CONS_SQL_QUOTE." ".$campo[CONS_XML_SQL];
						if (!$this->dbo->simpleQuery($sql)) {
							$this->errorState = true;
							array_push($this->log,"Unable to create it! (triggered errorState)");
						} else {
							$this->dbchanged = true;
							array_push($this->log,"Created!");
						}
					}
				}
				# checks keys and uniques
				$sql = "SHOW KEYS FROM ".CONS_SQL_QUOTE.$module->dbname.CONS_SQL_QUOTE;
				$this->dbo->query($sql,$r,$n);
				$camposdb = array();
				$uniquedb = array();
				$normalkeys = array();
				$data = $this->dbo->fetch_assoc($r);
				while (is_array($data)){
					if ($data['Key_name'] == 'PRIMARY')
						array_push($camposdb,$data['Column_name']);
					else if ($data['Non_unique'] == '0')
						array_push($uniquedb,$data['Column_name']);
					else
						$normalkeys[] = $data['Column_name'];
					$data = $this->dbo->fetch_assoc($r);
				}
				# main keys
				foreach ($module->keys as $x => $nome) {
					if (!in_array($nome,$camposdb)) {
						array_push($this->log,"Missing key at ".$module->dbname.":$nome");
						$sql = "ALTER TABLE ".CONS_SQL_QUOTE.$module->dbname.CONS_SQL_QUOTE." DROP PRIMARY KEY, ADD PRIMARY KEY (".implode(",",$module->keys).")";
						if (!$this->dbo->simpleQuery($sql)) {
							array_push($this->log,"Error updating keys! Trying without drop ...");
							$sql = "ALTER TABLE ".CONS_SQL_QUOTE.$module->dbname.CONS_SQL_QUOTE." ADD PRIMARY KEY (".implode(",",$module->keys).")";
							if (!$this->dbo->simpleQuery($sql)) {
								$this->errorState = true;
								array_push($this->log,"Error updating keys! (triggered errorState)");
							} else
								$this->dbchanged = true;
						} else
							$this->dbchanged = true;
						array_push($this->log,"Keys reset sucessfully!");
						break;
					}
				}
				# uniques
				foreach ($module->unique as $name) {
					if (!in_array($name,$uniquedb)) {
						$this->log[] = "Missing unique key at ".$module->dbname.":".$name;
						$sql = "ALTER TABLE  ".CONS_SQL_QUOTE.$module->dbname.CONS_SQL_QUOTE." ADD UNIQUE ($name)";
						if (!$this->dbo->simpleQuery($sql)) {
							$this->errorState = true;
							array_push($this->log,"Error updating unique! (triggered errorState)");
						} else
							$this->dbchanged = true;
						array_push($this->log,"Unique reset sucessfully!");
						break;
					}
				}
				# non-unique (hash)
				foreach ($module->hash as $name) {
					if (!in_array($name,$normalkeys)) {
						$this->log[] = "Missing hash key at ".$module->dbname.":".$name;
						$sql = "ALTER TABLE  ".CONS_SQL_QUOTE.$module->dbname.CONS_SQL_QUOTE." ADD INDEX ($name)";
						if (!$this->dbo->simpleQuery($sql)) {
							$this->errorState = true;
							array_push($this->log,"Error updating index! (triggered errorState)");
						} else
						$this->dbchanged = true;
						array_push($this->log,"Index (hash) reset sucessfully!");
						break;
					}
				}
			}
		  }
		}
		if ($this->dbchanged) {
			$_REQUEST['nosession'] = true; # reset our session
			$_REQUEST['nocache'] = true; # do not use caches
		}
	} # check_sql
//--
	function save_model() {
		# saves all XML data into cached serialized phps
		$ok = true;
		$theModules = array();
		$this->permissionTemplate = array();
		if (!is_dir(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/"))
			makeDirs(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/");
		foreach ($this->modules as $nome => $module) {
			$theModules[] = array($nome,$module->dbname,$module->plugins);
			$oModule = array($module->keys,
							 $module->title,
							 $module->fields,
							 $module->order,
							 $module->permissionOverride,
							 $module->freeModule,
							 $module->linker,
							 $module->options
							 );
			$ok = $ok && cWriteFile(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/$nome.dat",serialize($oModule));
			$p = CONS_TOOLS_DEFAULTPERM;
			if ($module->permissionOverride != "") {
				for ($c=0;$c<9;$c++) {
					if ($module->permissionOverride[$c] == "a")
						$p[$c] = "1";
					else if($module->permissionOverride[$c] == "d")
						$p[$c] = "0";
				}
			}
			$p .= "00000000000000"; // some random custom permissions
			$this->permissionTemplate[$nome] = $p;
		}
		// now add plugin templates

		foreach ($this->loadedPlugins as $pname => $plugin) {
			if ($plugin->moduleRelation == '') {
				$p = "000000000"; // standard
				$pos = 9;
				foreach ($plugin->customPermissions as $ptag => $pi18n) {
					$p .= "0";
					$pos++;
				}
			}
			$this->permissionTemplate["plugin_".$pname] = $p;
		}
		$ok = $ok && cWriteFile(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/_modules.dat",serialize($theModules));
		$ok = $ok && cWriteFile(CONS_PATH_CACHE.$_SESSION['CODE']."/meta/_permissions.dat",serialize($this->permissionTemplate));
		if (!$ok) $this->errorControl->raise(124);
		return $ok;
	} # save_model
//--
	private function getSerializedModel($fields,$module) {
		$exfields = array();
		foreach ($fields as $field) {
			$this->processParameters($field,$exfields,$module,true);
		}
		return $exfields;
	}
//--
	private function processParameters($thiscampo,&$fields,$module='',$isSerialized=false) {

		$namefield = strtolower($thiscampo->data[0]); // comes from XML
		if (!isset($fields[$namefield])) $fields[$namefield] = array();

		if ($module != '') {
			if (in_array($namefield,array("set","case","module","keys","asc","desc","order","action","by","select","from","to","having","update","insert","delete","as","join","limit","now","lock")))
			$this->log[] = "Field name might cause SQL/SYSTEM issues: $namefield at $module";
			if (in_array($namefield,array("id_set","id_case","id_module","id_keys","id_asc","id_desc","id_order","id_action","id_by","id_select","id_from","id_to","id_having","id_update","id_insert","id_delete","id_as","id_join","id_limit","id_now","id_lock")))
			$this->log[] = "Field name might cause SQL/SYSTEM issues when linking to the parent table: $namefield at $module";
		}


		if (is_array($thiscampo->data[1])) {
			$nparam = &$thiscampo->data[1];
			foreach ($nparam as $name=>$content) {
				# browse parameters for this FIELD ---------------
				switch (strtolower($name)) {
					case "mandatory":
						if ($content == 'true')
							$fields[$namefield][CONS_XML_MANDATORY] = true;
						else
							unset($fields[$namefield][CONS_XML_MANDATORY]);
						break;
					case "merge":
					case "join":
						if (!$isSerialized) {
							$fields[$namefield][CONS_XML_JOIN] = strtolower($content)=="inner"?"inner":"left";
							if ($fields[$namefield][CONS_XML_JOIN] == "left") unset($fields[$namefield][CONS_XML_MANDATORY]);
						}
						break;
					case "unique":
						if (!$isSerialized) {
							if ($content == 'true')
								$this->modules[$module]->unique[] = $namefield;
							else {
								$newunique = array();
								foreach ($this->modules[$module]->unique as $u) {
									if ($u != $namefield)
										$newunique[] = $u;
								}
								$this->modules[$module]->unique = $newunique;
							}
						}
						break;
					case "restricted":
						if ($content == 'false')
							unset($fields[$namefield][CONS_XML_RESTRICT]);
						else {
							if (!is_numeric($content)) $content = 10;
							$fields[$namefield][CONS_XML_RESTRICT] = $content;
						}
						break;
					case "hashkey":
						if (!$isSerialized)
							$this->modules[$module]->hash[] = $namefield;
						break;
					case "html":
						if ($content == 'true')
							$fields[$namefield][CONS_XML_HTML] = true;
						else
							unset($fields[$namefield][CONS_XML_HTML]);
						break;
					case "size":
						$fields[$namefield][CONS_XML_FIELDLIMIT] = (int)$content;
						break;
					case "timestamp":
						if ($content == 'true')
							$fields[$namefield][CONS_XML_TIMESTAMP] = true;
						else
							unset($fields[$namefield][CONS_XML_TIMESTAMP]);
						break;
					case "updatestamp":
						if ($content == 'true')
							$fields[$namefield][CONS_XML_UPDATESTAMP] = true;
						else
							unset($fields[$namefield][CONS_XML_UPDATESTAMP]);
						break;
					case "filetypes":
						$fields[$namefield][CONS_XML_FILETYPES] = strtolower($content);
						break;
					case "filemaxsize":
						$fields[$namefield][CONS_XML_FILEMAXSIZE] = (int)trim($content);
						break;
					case "thumbnails":
						$fields[$namefield][CONS_XML_THUMBNAILS] = explode("|",strtolower($content));
						break;
					case "condthumbnails":
						$fields[$namefield][CONS_XML_CONDTHUMBNAILS] = explode(":",strtolower($content));
						if (!isset($fields[$namefield][CONS_XML_THUMBNAILS])) { // if no normal thumbs specified, do it
							$fields[$namefield][CONS_XML_THUMBNAILS] = explode(";",$fields[$namefield][CONS_XML_CONDTHUMBNAILS][1]);
							$fields[$namefield][CONS_XML_THUMBNAILS] = explode("|",$fields[$namefield][CONS_XML_THUMBNAILS][0]);
						}
						break;
					case "filepath":
						$fields[$namefield][CONS_XML_FILEPATH] = strtolower($content);
						break;
					case "restrict":
						$fields[$namefield][CONS_XML_RESTRICT] = (int)trim($content);
						break;
					case "default":
						$fields[$namefield][CONS_XML_DEFAULT] = $content;
						break;
					case "ignorenedit":
						if ($content == 'true')
							$fields[$namefield][CONS_XML_IGNORENEDIT] = true;
						else
							unset($fields[$namefield][CONS_XML_IGNORENEDIT]);
						break;
					case "simple":
					case "forcesimple":
						if ($content == 'true')
							$fields[$namefield][CONS_XML_SIMPLEEDITFORCE] = true;
						else
							unset($fields[$namefield][CONS_XML_SIMPLEEDITFORCE]);
						break;
					case "tweakimages":
						$fields[$namefield][CONS_XML_TWEAKIMAGES] = explode("|",strtolower(trim($content)));
						break;
					case "meta":
						$fields[$namefield][CONS_XML_META] = trim($content);
						break;
					case "special":
						$fields[$namefield][CONS_XML_SPECIAL] = strtolower(trim($content));
						if ($module != '' && ($fields[$namefield][CONS_XML_SPECIAL] == "urla" || $fields[$namefield][CONS_XML_SPECIAL] == "furl")) {
							$fields[$namefield][CONS_XML_SPECIAL] = "urla"; // not interested in testing both all the time
							if (isset($fields[$namefield][CONS_XML_MANDATORY]))
								unset($fields[$namefield][CONS_XML_MANDATORY]);
							if (!isset($fields[$namefield][CONS_XML_SOURCE]))
								$fields[$namefield][CONS_XML_SOURCE] = "{".$this->modules[$module]->title."}";
						}
						break;
					case "urlaformat":
					case "furlformat": // used for special:furl
						$fields[$namefield][CONS_XML_SPECIAL] = "urla";
						$fields[$namefield][CONS_XML_SOURCE] = strtolower(trim($content));
						break;
					case "autoprune":
						if (!$isSerialized) {
							$fields[$namefield][CONS_XML_AUTOPRUNE] = explode(",",trim($content));
							if (!in_array("*",$fields[$namefield][CONS_XML_AUTOPRUNE])) {
								$this->log[] = "Autoprune list has no recipient (*) value at $namefield at $module"; #TODO: default might come later on parameter list!
								unset($fields[$namefield][CONS_XML_AUTOPRUNE]);
							}
						}
						break;
					case "owner":
					case "isowner":
						if (!$isSerialized) {
							if (isset($fields[$namefield][CONS_XML_JOIN]) && $fields[$namefield][CONS_XML_JOIN] == "left")
								$this->log[] = "IsOwner field in $namefield at $module incorrect, cannot be a LEFT join. Ignored";
							else
								$fields[$namefield][CONS_XML_ISOWNER] = true;
							break;
						}
					case "serialization":
						if (!$isSerialized) {
							if (in_array($content,array(0,1,2,3,'none','read','write','all'))) {
								$fields[$namefield][CONS_XML_SERIALIZED] = $content == 'read'?1:($content=='write'?2:($content=='all'?3:($content=='none'?0:$content)));
								$fields[$namefield][CONS_XML_CUSTOM] = true;
							} else {
								unset($fields[$namefield][CONS_XML_SERIALIZED]);
							}
							break;
						} else
							$this->log[]= "Cannot have a serialized field inside another at $namefield in $module";
					case "custom":
						if ($content == 'true')
							$fields[$namefield][CONS_XML_CUSTOM] = true;
						else
							unset($fields[$namefield][CONS_XML_CUSTOM]);
						break;
					case "conditional": // conditions this field if it is to be displayed based on VARIABLE OPERATOR VALUE
						$fields[$namefield][CONS_XML_CONDITIONAL] = trim($content);
						break;
					case "filteredby": // if the automatic filtering doesn't underestand what you want, you can override here
							$fields[$namefield][CONS_XML_FILTEREDBY] = explode(",",strtolower(trim($content)));
						break;
					case "noimage":
					case "noimg": // default image if image not set
							$fields[$namefield][CONS_XML_NOIMG] = trim($content);
						break;
					case "readonly": // should not edit on edit panes
						if ($content == 'true')
							$fields[$namefield][CONS_XML_READONLY] = true;
						else
							unset($fields[$namefield][CONS_XML_READONLY]);
						break;
					case "language": // overrides the enums with languages
						if (strpos($thiscampo->data[2],"ENUM")!== false) {
							$thiscampo->data[2] = "ENUM:";
							foreach (explode(',',CONS_POSSIBLE_LANGS) as $l)
								$thiscampo->data[2] .= "'$l',";
							$thiscampo->data[2] = substr($thiscampo->data[2],0,strlen($thiscampo->data[2])-1);
							$fields[$namefield][CONS_XML_DEFAULT] = CONS_DEFAULT_LANG;
						} else
							$this->log[] = "Invalid language parameter inside a non-enum field";
					break;
					default:
						$fields[$namefield][strtolower($name)] = $content;
						$this->warning[] = "Unknown parameter at $namefield: $name";
						break;
				} # switch
			} # ended browsing FIELD parameters
		} # has parameters

		if ($thiscampo->total()>0) {
			if (!$fields[$namefield][CONS_XML_SERIALIZED]) {
				$this->log[] = "Field has nested tags, but have no serialization option: $namefield at $module";
			} else { // serialized field,
				$thiscampo->data[2] = "serialized";
			}
		}

		// pre-process enum type
		$xipo = $thiscampo->data[2];
		if (strpos($xipo,":")!== false) {
			# enum and set have their values inside the content: ENUM:'1','2','3'
			$xipo = explode(":",$xipo);
			$tipo = strtolower(array_shift($xipo));
			$xipo = implode(":",$xipo);
		} else {
			$tipo = strtolower($xipo);
			$xipo = "";
		}
		if ($tipo == "")
			$this->errorControl->raise(121,$namefield,$module);

		// pre-process type
		switch($tipo) {
			case "int":
			case "tinyint":
			case "bigint":
			case "smallint":
				if (!isset($fields[$namefield][CONS_XML_FIELDLIMIT]))
					$fields[$namefield][CONS_XML_FIELDLIMIT] = $tipo == "bigint" ? 20 : ( $tipo == "int" ? 10 : ( $tipo == 'smallint' ? 5 : 3));
				$fields[$namefield][CONS_XML_SQL] = strtoupper($tipo)." (".$fields[$namefield][CONS_XML_FIELDLIMIT].")";
				$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_INT;
				break;
			case "float":
				$fields[$namefield][CONS_XML_SQL]= "FLOAT";
				$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_FLOAT;
				break;
			case "varchar":
			case "vc":
				if (!isset($fields[$namefield][CONS_XML_FIELDLIMIT]))
				$fields[$namefield][CONS_XML_FIELDLIMIT] = 255;
				$fields[$namefield][CONS_XML_SQL] = "VARCHAR (".$fields[$namefield][CONS_XML_FIELDLIMIT].") CHARACTER SET utf8 COLLATE utf8_unicode_ci ";
				$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_VC;
				break;
			case "bol": # uses enum
			case "boolean":
				$xipo = "'y','n'";
				if (!isset($fields[$namefield][CONS_XML_DEFAULT])) $fields[$namefield][CONS_XML_DEFAULT] = 'n';
			case "enum":
				$fields[$namefield][CONS_XML_SQL]= "ENUM ($xipo)";
				$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_ENUM;
				break;
			case "serialized":
				$fields[$namefield][CONS_XML_SQL]= "MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci ";
				$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_SERIALIZED;
				$fields[$namefield][CONS_XML_SERIALIZEDMODEL] = $this->getSerializedModel($thiscampo->branchs,$module);
				break;
			case "txt":
			case "text": // 64k
				$fields[$namefield][CONS_XML_SQL]= "TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci ";
				$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_TEXT;
				break;
			case "mediumtext": // 16Mb
				$fields[$namefield][CONS_XML_SQL]= "MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci ";
				$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_TEXT;
				break;
			case "longtext": // 4Gb
				$fields[$namefield][CONS_XML_SQL]= "LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci ";
				$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_TEXT;
				break;
			case "date":
				$fields[$namefield][CONS_XML_SQL]= "DATE";
				$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_DATE;
				break;
			case "datetime":
				$fields[$namefield][CONS_XML_SQL]= "DATETIME";
				$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_DATETIME;
				break;
			case "opt":
			case "options":
				$fields[$namefield][CONS_XML_SQL]= "VARCHAR (100)";
				$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_OPTIONS;
				$fields[$namefield][CONS_XML_OPTIONS] = array();
				if (!isset($fields[$namefield][CONS_XML_DEFAULT]))
					$fields[$namefield][CONS_XML_DEFAULT] = "000"; # pad
				$temp = explode(",",$xipo);
				foreach ($temp as $item)
					if (strlen($item)>2) {
						$item = substr($item,1,strlen($item)-2); // removes quotes
						$fields[$namefield][CONS_XML_OPTIONS][] = $item;
						$fields[$namefield][CONS_XML_DEFAULT] .= "0";
					}
				break;
			case "file":
			case "upload":
				if (is_numeric($namefield[strlen($namefield)-1])) {
					$this->log[] = "Upload/file fields cannot end in a numeric value ($namefield @ $module)";
					$fields[$namefield][CONS_XML_SQL] = ""; // prevents the creation of the field
				} else {
					$fields[$namefield][CONS_XML_DEFAULT] = 'n';
					$fields[$namefield][CONS_XML_SQL]= "ENUM ('y','n')";
				}
				$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_UPLOAD;
				if ($module != '') $this->modules[$module]->options[CONS_MODULE_VOLATILE] = false;
				# all file upload parameters are inside the xml tag
				break;
			case "array":
				# serialize-only type, is a serialized array
				if ($isSerialized) {
					$fields[$namefield][CONS_XML_SQL]= "TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci ";
					$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_ARRAY;
					$fields[$namefield][CONS_XML_OPTIONS] = array();
					$temp = explode(",",$xipo);
					foreach ($temp as $item)
						if (strlen($item)>2) {
							$item = substr($item,1,strlen($item)-2); // removes quotes
							$fields[$namefield][CONS_XML_OPTIONS][] = $item;
						}
				} else {
					$this->log[] = "Invalid array type on non-serialized field ($namefield @ $module)";
				}
				break;
			default:
				if ($module != '') $this->modules[$module]->options[CONS_MODULE_VOLATILE] = false;
				$fields[$namefield][CONS_XML_TIPO] = CONS_TIPO_LINK; # will be a link type
				$fields[$namefield][CONS_XML_LINKTYPE] = CONS_TIPO_INT; # standard
				$fields[$namefield][CONS_XML_MODULE] = $tipo;
				if (isset($fields[$namefield][CONS_XML_ISOWNER]) && $tipo != CONS_AUTH_USERMODULE)
					unset($fields[$namefield][CONS_XML_ISOWNER]);
				if (isset($fields[$namefield][CONS_XML_MANDATORY]) && !$isSerialized)
					$fields[$namefield][CONS_XML_JOIN] = "inner";
				else if (isset($fields[$namefield][CONS_XML_JOIN]) && $fields[$namefield][CONS_XML_JOIN] == "inner")
					$fields[$namefield][CONS_XML_MANDATORY] = true;
			break;
		}
		return $fields;
	} # processParameter
//--

	function addPlugin($script,$relateToModule="",$renamePluginTo="",$noRaise=false) {

		$r = parent::addPlugin($script,$relateToModule,$renamePluginTo,$noRaise);

		if (!isset($this->dimconfig['_pluginStarter'.$script]) || $this->dimconfig['_pluginStarter'.$script] != true) {
			// ad monitors form this script to the list
			$fileP = CONS_PATH_SYSTEM."plugins/$script/monitor.xml";
			$fileS = CONS_PATH_PAGES.$_SESSION['CODE']."/_config/monitor.xml";
			if (is_file($fileP)) { // plugin has a monitor
				if (is_file($fileS)) { // site has a monitor
					$contentP = cReadFile($fileP);
					if (preg_match("@[^<]*(<[^>]*>).*@",$contentP,$e)) { // get first tag ($e[1])
						// check if site's monitor has this tag
						$contentS = cReadFile($fileS);
						if (strpos($contentS,$e[1])===false) { // it doesn't have, add
							$contentS .= "\n".$contentP;
						}
						cWriteFile($fileS,$contentS);
					}
				} else
					copy($fileP,$fileS);
			}
			$this->dimconfig['_pluginStarter'.$script] = true;
		}

		return $r;

	} # addPlugin

}