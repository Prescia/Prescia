<?	# -------------------------------- Prescia Module, all modules loaded from XML inherit this file

function prepareDataToOutput(&$template, &$params, $data, $processed = false) { // you can prevent auto-running this with ?noOutputParse=true
	# A callback might be called multiple times. The processed should come as TRUE on all second or more times it's called to prevent performance impact
	# IMPORTANT: DO NOT REMOVE A CONTENT BY SETTING IT AS $data['_somecontent'] = ""; OR THIS WILL REMOVE SAID CONTENT FROM ALL FURTHER OCCURENCES OF THE RUNCONTENT LOOP (this the object is not instantiated on each iteraction for performance).
	#			 INSTEAD, ADD THE CONTENT TO BE REMOVED ON THE $params['excludes'] array, as: $params['excludes'][] = "_somecontent";
	#			 THIS WILL SIMPLY NOT ECHO THE CONTENT ON THE ITERACTION, WHILE PRESERVING IT INSIDE THE TEMPLATE OBJECT
	# Multikey OK

	$myTitle = isset($data[$params['module']->title])?$data[$params['module']->title]:'';
	if ($myTitle != '') $myTitle = str_replace("\"","",htmlspecialchars($myTitle,ENT_NOQUOTES));

	$keystring = "";
	$havekeys = true;
	foreach ($params['module']->keys as $pkey) {
		if (!isset($data[$pkey])) {
			$havekeys = false;
			break;
		} else
			$keystring .= "_".$data[$pkey];
	}

	foreach ($params['module']->fields as $fname => $field) {
		if (isset($data[$fname]) && !$processed) {
			if ($field[CONS_XML_TIPO] == CONS_TIPO_UPLOAD) {

				$thumbsettings= isset($field[CONS_XML_THUMBNAILS])?$field[CONS_XML_THUMBNAILS]:'';

				if ($thumbsettings != '' && $havekeys) { // image/flash
					$imgs = count($thumbsettings);
					for ($c=1;$c<=$imgs;$c++) {
						$fnamedata = $fname."_".$c;
						$data[$fnamedata."w"] = "";
						$data[$fnamedata."h"] = "";
						$data[$fnamedata."t"] = "";
						$data[$fnamedata."s"] = "";
						$file = CONS_FMANAGER.$params['module']->name."/".($c==1?"":"t/").$fname.$keystring."_$c";
						if ($data[$fname] == 'y' && locateAnyFile($file,$ext,CONS_FILESEARCH_EXTENSIONS)) {
							$data[$fnamedata] = $file;
							$popped = explode("/",$file);
							$data[$fnamedata."filename"] = array_pop($popped);
							if (in_array(strtolower($ext),array("jpg","gif","png","jpeg","swf"))) { // image/flash
							  $h = getimagesize($file);
							  $data[$fnamedata."w"] = $h[0];
							  $data[$fnamedata."h"] = $h[1];
							  $data[$fnamedata."s"] = humanSize(filesize($file));
							  if (in_array(strtolower($ext),array("jpg","gif","png","jpeg"))) { // image
							  	$randomseed = "?r=".rand(1000,9999).date("YmdHis");
								$data[$fnamedata."t"] = "<img src=\"".CONS_INSTALL_ROOT.$file.$randomseed."\" width='".$h[0]."' title=\"".$myTitle."\" height='".$h[1]."' alt='' />";
							  } else if (strtolower($ext) == "swf") {
								$data[$fnamedata."t"] =
								   str_replace("{FILE}",$file,
								   str_replace("{H}",$h[1],
								   str_replace("{W}",$h[0],SWF_OBJECT)));
							  }
							}
						} else if (isset($field[CONS_XML_NOIMG])) {
							$data[$fnamedata] = CONS_PATH_PAGES.$_SESSION['CODE']."/files/".$field[CONS_XML_NOIMG];
							$popped = explode("/",$data[$fnamedata]);
							$data[$fnamedata."filename"] = array_pop($popped);
							$h = getimagesize($data[$fnamedata]);
							$data[$fnamedata."w"] = $h[0];
							$data[$fnamedata."h"] = $h[1];
							$data[$fnamedata."s"] = humanSize(filesize($data[$fnamedata]));
							$data[$fnamedata."t"] = "<img src=\"".CONS_INSTALL_ROOT.$data[$fnamedata]."\" width='".$h[0]."' title=\"".$myTitle."\" height='".$h[1]."' alt='' />";
						}
					 }
				} else if ($data[$fname]== 'y') { // file w/o image, present
					$file = CONS_FMANAGER.$params['module']->name."/".$fname.$keystring."_1";
					$fnamedata = $fname."_1";
					if (locateAnyFile($file,$ext)) {
						$data[$fnamedata] = CONS_INSTALL_ROOT.$file;
						$data[$fnamedata."s"] = humanSize(filesize($file));
						$popped = explode("/",$file);
						$data[$fnamedata."filename"] = array_pop($popped);
					} else
						$data[$fname] = 'n';
				} else { // not uploaded
					$fnamedata = $fname."_1";
					$data[$fnamedata] = "";
					$data[$fnamedata."s"] = "";
					$data[$fnamedata."filename"] = "";
				}
			} elseif ($field[CONS_XML_TIPO] == CONS_TIPO_VC && isset($field[CONS_XML_SPECIAL]) && $field[CONS_XML_SPECIAL] == 'onlinevideo') {
				if (preg_match('/^([A-Za-z0-9_\-]){6,20}$/',$data[$fname])==1) {
					// valid youtube or vimeo ... vimeo are number only
					if (is_numeric($data[$fname])) {
						$data[$fname.'_url'] = "http://player.vimeo.com/video/".$data[$fname];
						$data[$fname.'_embed'] = "http://player.vimeo.com/video/".$data[$fname];
					} else {
						$data[$fname.'_url'] = "http://www.youtube.com/v/".$data[$fname];
						$data[$fname.'_embed'] = "http://www.youtube.com/embed/".$data[$fname];
					}
				} else {
					$data[$fname.'_url'] = "";
					$data[$fname.'_embed'] = "";
				}
			} elseif ($field[CONS_XML_TIPO] == CONS_TIPO_SERIALIZED && ($field[CONS_XML_SERIALIZED] == 1 || $field[CONS_XML_SERIALIZED] == 3) && isset($data[$fname])) {
				# serialized field with READ or ALL serialization mode
				$s = @unserialize($data[$fname]);
				if ($s !== false) {
					foreach ($s as $skey => $scontent) {
						$data[$fname."_".$skey] = $scontent;
					}
				}
				unset($s);
			}
		}
		switch ($field[CONS_XML_TIPO]) {
			case CONS_TIPO_INT:
			case CONS_TIPO_FLOAT:
			case CONS_TIPO_LINK:
				if (!isset($data[$fname]) || is_null($data[$fname]) || $data[$fname] == 0) $params['excludes'][] = "_toggle_".$fname;
			break;
			case CONS_TIPO_VC:
			case CONS_TIPO_TEXT:
				if (!isset($data[$fname]) || is_null($data[$fname]) || trim($data[$fname]) == '') $params['excludes'][] = "_toggle_".$fname;
			break;
			case CONS_TIPO_DATE:
			case CONS_TIPO_DATETIME:
				if (!isset($data[$fname]) || is_null($data[$fname]) || trim($data[$fname]) == '' || $data[$fname] == '0000-00-00' || $data[$fname] == '0000-00-00 00:00:00') $params['excludes'][] = "_toggle_".$fname;
			break;
			case CONS_TIPO_UPLOAD:
				if (isset($data[$fname]) && $data[$fname] == 'n' && (!isset($field[CONS_XML_NOIMG]) || isset($params[CONS_RUNCONTENT_NOIMGOVERRIDE]))) $params['excludes'][] = "_toggle_".$fname;
			break;
			case CONS_TIPO_ENUM:
				if (isset($data[$fname]) && $data[$fname] == 'n') $params['excludes'][] = "_toggle_".$fname;
			break;
		}

	}

	return $data;
}

class CModule {

	var $name = "";
	var $parent = null; # Framework object
	var $title = "";
	# database table and data
	var $dbname = "";
	var $keys = array('id');
	var $plugins = array(); # script plugins this module has (the actual objects are stored on the core::loadedPlugins
	var $order = ""; # SQL default order
	var $permissionOverride = ""; # permission string that overrides default
	var $unique = array(); # unique keys (The framework will create them, but won't remove any extra keys)
	var $hash = array(); # non-unique keys (The framework will create them, but won't remove any extra keys)
	var $linker = false; # this module is a linker module (have only 2 fields, which are links)
	var $options = array();
	var $fields = array();
	var $freeModule = false; # this module does not link to a user or group, thus controled ALWAYS by "World" area of permission string
	var $loaded = false;

	function __construct(&$parent, $name,$dbname="") {
		$this->parent = &$parent;
		$this->name = $name;
		$this->dbname = $dbname;
		$this->options = array( CONS_MODULE_VOLATILE => false,
								CONS_MODULE_MULTIKEYS => array(),
								CONS_MODULE_SYSTEM => false,
								CONS_MODULE_AUTOCLEAN => '',
								CONS_MODULE_PARENT => '',
								CONS_MODULE_META => ''
							  );
	}

	function loadPlugins() {
		foreach ($this->plugins as $scriptname) {
			if (!isset($this->parent->loadedPlugins[$scriptname]))
				$this->parent->addPlugin($scriptname,$this->name);
			else
				$this->parent->loadedPlugins[$scriptname]->moduleRelation = $this->name;
		}
	}

	/* getContents
	 * returns the contents in an array (avoid using it). It's better used on TREE structure modules, where it will actually build the tree and return it instead
	 * will return normal query if there is no field defined as parent (parent='field') on the meta.xml
	 */
	function getContents($order = "", $treeTitle = "", $where = "",$treeSeparator="\\",$originalSQL="") {
		# This is used on bi_adm::edit.php
		$output = array();
		if ($this->options[CONS_MODULE_PARENT] != '') {
			if ($order == "" && $this->order != "") {
				$odb = array();
			  	$ord = explode(",",$this->order);
				foreach ($ord as $orditem) {
					$orditem = trim($orditem);
					if (strpos($orditem,"+") !== false) {
						$orditem = str_replace("+","",$orditem);
						if ($orditem == $this->options[CONS_MODULE_PARENT]) continue; // will add automaticaly
						if (isset($this->fields[$orditem]))
							$odb[] = $this->name.".".$orditem." ASC";
						else
							$odb[] = $orditem." ASC";
					} else {
						$orditem = str_replace("-","",$orditem);
						if ($orditem == $this->options[CONS_MODULE_PARENT]) continue; // will add automaticaly
						if (isset($this->fields[$orditem]))
							$odb[] = $this->name.".".$orditem." DESC";
						else
							$odb[] = $orditem." DESC";
					}
				}
				$order = implode(",",$odb);
			}
			if ($originalSQL == "") // build SQL, will use where provided
				$sql = $this->get_base_sql($where,$this->name.".".$this->options[CONS_MODULE_PARENT]." ASC".($order != ''?','.$order:''));
			else { // give a full SQL, will just add proper order and treetitle
				$sql = $originalSQL;
				if (!is_array($sql)) $sql = $this->parent->dbo->sqlarray_break($sql);
				$sql['ORDER'] = explode(",",$this->name.".".$this->options[CONS_MODULE_PARENT]." ASC".($order != ''?','.$order:''));
			}
			if (!isset($this->fields['treetitle']))
				$sql['SELECT'][] = $this->name.".".($treeTitle==''?$this->title:$treeTitle)." as treetitle";
			$this->parent->dbo->query($sql,$r,$n);
			for ($c=0;$c<$n;$c++) {
				$tmpData = $this->parent->dbo->fetch_assoc($r);
				$params = array('module'=>$this);
				$tmpData['#'] = $c;
				$tmpData = prepareDataToOutput($this->parent->template,$params,$tmpData);
				$output[] = $tmpData;
			}
			$treeObj = new ttree();
			$treeObj->arrayToTree($output,$treeSeparator,$this->options[CONS_MODULE_PARENT],$treeTitle==''?$this->title:$treeTitle);
			return $treeObj;
		} else {
			$sql = $this->get_base_sql('',$order);
			$this->parent->dbo->query($sql,$r,$n);
			for ($c=0;$c<$n;$c++) {
				$tmpData = $this->parent->dbo->fetch_assoc($r);
				$params = array('module'=>$this);
				$tmpData = prepareDataToOutput($this->parent->template,$params,$tmpData);
				$tmpData['#'] = $c;
				$output[] = $tmpData;
			}
		}
		return $output;
	} # getContents

	function invalidHTML($text) { # hello ... bye WORD. Kids, don't copy&paste please!
		return (preg_match("@<meta|<link|<w:|<m:|<!\[endif\]|<o:|<xml@i",$text)==1);
	}
#-
	/*
	 * MODULE (me) is pointing to RMODULE. Convert MY KEYS $data (that point to RMODULE) to RMODULE keys so I can run a select from RMODULE to find itself
	 */
	function getRemoteKeys($rmodule,$data) {
		$where = array();
		foreach ($rmodule->fields as $key => $field) {
			if ($rmodule->fields[$key][CONS_XML_TIPO] == CONS_TIPO_INT) {
				if ($key == "id") {
					$mykey = $this->get_key_from($rmodule->name,"id_".$rmodule->name);
					if ($mykey != '' && isset($data[$mykey]))
						$where[] = $rmodule->name.".id='".$data[$mykey]."'" ;
				} else if (isset($this->field[$key]) && isset($data[$key])) {
					$where[] = $rmodule->name.".".$key."='".$data[$key]."'";
				} // else I can't decide how to link it
			} else if ($rmodule->fields[$key][CONS_XML_TIPO] == CONS_TIPO_LINK) {
				$mykey = $this->get_key_from($rmodule->fields[$key][CONS_XML_MODULE],"id_".$rmodule->fields[$key][CONS_XML_MODULE]);
				if ($mykey!='' && isset($data[$mykey]))
					$where[] = $rmodule->name.".".$key."='".$data[$mykey]."'";
			} else if (isset($this->field[$key]) && isset($data[$key])) {
				$where[] = $rmodule->name.".".$key."='".$data[$key]."'";
			} // else I can't decide how to link it
		}
		return $where;
	}
#-
	function get_advanced_sql($taglist,$embedWhere = "", $embedOrder = "", $embedLimit = "",$cacheTAG=false) {
		# This function searches the $taglist (from template) and only adds the sql required to fetch those data, thus preventing unnecessary joins and selects
		$sql = false;
		if (!isset($_REQUEST['nocache']) && $cacheTAG !== false ) {
			$sql = $this->parent->cacheControl->getCachedContent($cacheTAG,86400); # 1 day ... could be infinite actually
		}
		if (!$sql) {
			$sql = array("SELECT" => array(), "FROM" => array(), "LEFT" => array(), "WHERE" => array(), "GROUP" => array(), "ORDER" => array(), "LIMIT" => array(), "HAVING" => array());
			$sql['FROM'][] = $this->dbname." as ".$this->name;
			$pos = 0;
			foreach($this->fields as $nome => $campo) {
			  $haveItem = in_array($nome,$this->keys) || isset($taglist[$nome]) || isset($taglist['_toggle_'.$nome]); # keys are always present due to upload and other fields dependent on them indirectly
			  if (!$haveItem || $campo[CONS_XML_TIPO] == CONS_TIPO_LINK) { // we might have the id, but what about other data from inside the link?
			  	if ($campo[CONS_XML_TIPO] == CONS_TIPO_UPLOAD) {
			  		$haveItem = (isset($taglist[$nome."_1"]) ||
				  		isset($taglist[$nome."_1w"]) ||
						isset($taglist[$nome."_1h"]) ||
						isset($taglist[$nome."_1t"]) ||
						isset($taglist[$nome."_1s"]) ||
						isset($taglist[$nome."_1filename"]));
					if (!$haveItem && isset($campo[CONS_XML_THUMBNAILS])) {
						for ($c=2;$c<=count($campo[CONS_XML_THUMBNAILS]);$c++) {
							$fnamedata = $nome."_".$c;
							$haveItem = (isset($taglist[$fnamedata]) ||
						  		isset($taglist[$fnamedata."w"]) ||
								isset($taglist[$fnamedata."h"]) ||
								isset($taglist[$fnamedata."t"]) ||
								isset($taglist[$fnamedata."s"]) ||
								isset($taglist[$fnamedata."filename"]));
							if ($haveItem) break;
						}
					}
				} else if ($campo[CONS_XML_TIPO] == CONS_TIPO_VC && isset($campo[CONS_XML_SPECIAL]) && $campo[CONS_XML_SPECIAL] == 'onlinevideo') {
					$haveItem = (isset($taglist[$nome."_url"]) ||
				  				isset($taglist[$nome."_embed"]));
				} else if ($campo[CONS_XML_TIPO] == CONS_TIPO_SERIALIZED && ($campo[CONS_XML_SERIALIZED] == 1 || $campo[CONS_XML_SERIALIZED] == 3)) {
					foreach ($taglist as $key => $one) {
						if (substr($key,0,strlen($nome)+1) == $nome."_") {
							$haveItem = true;
							break;
						}
					}
				} else if ($campo[CONS_XML_TIPO] == CONS_TIPO_LINK) {
				  // search all fields inside this one
				  $extrakey = array();
				  $linkname = $campo[CONS_XML_MODULE];
				  $remodeModule = $this->parent->loaded($linkname);
				  $tablecast = substr($nome,3); # id_[name] ... removes id_
				  if (in_array($tablecast,array("group","from","to","as","having","order","by","join","left","right"))) #reserved words that could cause issues on the SQL
				  	$tablecast .= "s"; # keyword, add a "s" to prevent it from causing SQL problems
				  $insideHaveitem = false;
				  foreach ($remodeModule->fields as $cremote_nome => $remote_campo) {
				  	if (isset($taglist[$tablecast."_".$cremote_nome])) {
				  		$insideHaveitem = true;
						break;
					}
				  }
				  if ($insideHaveitem) {
					  foreach ($remodeModule->fields as $cremote_nome => $remote_campo) {
						if ($cremote_nome != $remodeModule->keys[0]) {
						  # do not add main key (this module should have it anyway)
						  $rmod_nome = $tablecast;
						  $trmod_nome = substr($rmod_nome,0,strlen($rmod_nome)-2);
						  while (substr($rmod_nome,strlen($rmod_nome)-2) == "_e" && !isset($this->fields['id_'.$trmod_nome])) {
							$rmod_nome = $trmod_nome;
							$trmod_nome = substr($rmod_nome,0,strlen($rmod_nome)-2);
						  }
						  if (isset($taglist[$rmod_nome."_".$cremote_nome]))
							$sql['SELECT'][] = $tablecast.".".$cremote_nome." as ".$rmod_nome."_".$cremote_nome;
						}
						if ($remote_campo[CONS_XML_TIPO] == CONS_TIPO_LINK) {
						  if ($remote_campo[CONS_XML_MODULE] == $this->name && (!isset($remote_campo[CONS_XML_JOIN]) || $remote_campo[CONS_XML_JOIN] == "from"))
							# mandatory key to myself (parent)?
							$extrakey[] = $tablecast.".".$cremote_nome."=".$this->name.".".$this->keys[0];
						  else if (in_array($cremote_nome,$remodeModule->keys) &&
								   in_array($cremote_nome,$this->keys)) {
							$extrakey[] = $tablecast.".".$cremote_nome."=".$this->name.".".$cremote_nome;
						  }
						}
					  }
					  if (isset($campo[CONS_XML_JOIN]) && $campo[CONS_XML_JOIN] == "left") {
						$linker = array();
						foreach ($remodeModule->keys as $rkey) {
							if ($rkey == "id")
								$linker[] = $tablecast.".$rkey = ".$this->name.".".$nome;
							else if (in_array($rkey,$this->options[CONS_MODULE_MULTIKEYS])) {
								$linker[] = $tablecast.".$rkey = ".$this->name.".".$rkey;
							} else { // not a parent nor main key, how to link?
								if ($remodeModule->fields[$rkey][CONS_XML_MODULE] == $this->name)
									$linker[] = $tablecast.".$rkey = ".$this->name.".".$this->keys[0];
								else {
									$localField = $this->get_key_from($remodeModule->fields[$rkey][CONS_XML_MODULE]);
									$linker[] = $tablecast.".$rkey = ".$this->name.".".$localField;
								}
							}
						}
						$sql['LEFT'][] = ($remodeModule->dbname." as ".$tablecast." ON ".implode(" AND ",$linker)).(count($extrakey)>0 && count($linker)>0?" AND ":"").implode(" AND ",$extrakey);
					  } else {
					  	$sql['FROM'][] = $remodeModule->dbname." as ".$tablecast;
						foreach ($remodeModule->keys as $rkey) {
							if ($rkey == "id")
								$sql['WHERE'][] = $tablecast.".$rkey = ".$this->name.".".$nome;
							else if (in_array($rkey,$this->options[CONS_MODULE_MULTIKEYS])) {
								$sql['WHERE'][] = $tablecast.".$rkey = ".$this->name.".".$rkey;
							} else if ($remodeModule->fields[$rkey][CONS_XML_TIPO] == CONS_TIPO_LINK) { // not a parent nor main key, is a link to another table
								if ($remodeModule->fields[$rkey][CONS_XML_MODULE] == $this->name)
									$sql['WHERE'][] = $tablecast.".$rkey = ".$this->name.".".$this->keys[0];
								else {
									$localField = $this->get_key_from($remodeModule->fields[$rkey][CONS_XML_MODULE]);
									$sql['WHERE'][] = $tablecast.".$rkey = ".$this->name.".".$localField;
								}
							} else {// not simple id, parent or link. Its a non-standard ID for another table
								$sql['WHERE'][] = $tablecast.".$rkey = ".$this->name.".".$nome;
							}
						}
						foreach ($extrakey as $exk)
							$sql['WHERE'][] = $exk;
					  }
					  $pos++;
				    } # $insideHaveitem
				  } # multiple ifs
				} # haveitem
				if ($haveItem) $sql['SELECT'][] = (strpos($nome,".")===false?$this->name.".".$nome." as $nome":$nome);

			} # for
			if (count($sql['SELECT'])==0) $sql['SELECT'][] = $this->name.".*";
			if ($cacheTAG !== false) { # store cache
				$this->parent->cacheControl->addCachedContent($cacheTAG,$sql,false); # stores without the where
			}
		} # build sql

		if ($embedWhere != "") array_unshift($sql['WHERE'],$embedWhere);
		if ($this->order != "" && $embedOrder == "") {
			$ord = explode(",",$this->order);
			foreach ($ord as $orditem) {
				$orditem = trim($orditem);
				if (strpos($orditem,"+") !== false) {
					$orditem = str_replace("+","",$orditem);
					if (isset($this->fields[$orditem]))
						$sql['ORDER'][] = $this->name.".".$orditem." ASC";
					else
						$sql['ORDER'][] = $orditem." ASC";
				} else {
					$orditem = str_replace("-","",$orditem);
					if (isset($this->fields[$orditem]))
						$sql['ORDER'][] = $this->name.".".$orditem." DESC";
					else
						$sql['ORDER'][] = $orditem." DESC";
				}
			}
		}
		if ($embedOrder != "") $sql['ORDER'][] = $embedOrder;
		if ($embedLimit != "") $sql['LIMIT'] = (is_array($embedLimit)?$embedLimit:array($embedLimit));
		return $sql;
	}
#-
	function get_base_sql($embedWhere = "", $embedOrder = "", $embedLimit = "",$noJoin=false) {
	  $sql = false;
	  if (!$this->parent->debugmode && !$noJoin && is_file(CONS_PATH_CACHE.$_SESSION['CODE']."/".$this->dbname."_list.cache") && !isset($_REQUEST['nocache'])) {
		$sql = unserialize(cReadFile(CONS_PATH_CACHE.$_SESSION['CODE']."/".$this->dbname."_list.cache"));
	  }
	  if (!$sql) {
	  	$sql = array("SELECT" => array(), "FROM" => array(), "LEFT" => array(), "WHERE" => array(), "GROUP" => array(), "ORDER" => array(), "LIMIT" => array(), "HAVING" => array());
		$sql['FROM'][] = $this->dbname." as ".$this->name;
		$pos = 0;
		foreach($this->fields as $nome => $campo) {
		  $extrakey = array();
		  if ($campo[CONS_XML_TIPO] == CONS_TIPO_LINK && !$noJoin) {
			  $linkname = $campo[CONS_XML_MODULE];
			  $remodeModule = $this->parent->loaded($linkname);
			  $tablecast = substr($nome,3); # id_[name] ... removes id_
			  if (in_array($tablecast,array("group","from","to","as","having","order","by","join","left","right"))) #reserved words that could cause issues on the SQL
			  	$tablecast .= "s"; # keyword, add a "s" to prevent it from causing SQL problems
			  foreach ($remodeModule->fields as $cremote_nome => $remote_campo) {
				if ($cremote_nome != $remodeModule->keys[0]) {
				  # do not add main key (this module should have it anyway)
				  $rmod_nome = $tablecast;
				  $trmod_nome = substr($rmod_nome,0,strlen($rmod_nome)-2);
				  while (substr($rmod_nome,strlen($rmod_nome)-2) == "_e" && !isset($this->fields['id_'.$trmod_nome])) {
					$rmod_nome = $trmod_nome;
					$trmod_nome = substr($rmod_nome,0,strlen($rmod_nome)-2);
				  }

				  $sql['SELECT'][] = $tablecast.".".$cremote_nome." as ".$rmod_nome."_".$cremote_nome;
				}
				if ($remote_campo[CONS_XML_TIPO] == CONS_TIPO_LINK) {
				  if ($remote_campo[CONS_XML_MODULE] == $this->name && (!isset($remote_campo[CONS_XML_JOIN]) || $remote_campo[CONS_XML_JOIN] == "from"))
					# mandatory key to myself (parent)?
					$extrakey[]= $tablecast.".".$cremote_nome."=".$this->name.".".$this->keys[0];
				  else if (in_array($cremote_nome,$remodeModule->keys) &&
						   in_array($cremote_nome,$this->keys)) {
					$extrakey[]= $tablecast.".".$cremote_nome."=".$this->name.".".$cremote_nome;
				  }
				}
			  }
			  if (isset($campo[CONS_XML_JOIN]) && $campo[CONS_XML_JOIN] == "left") {
				$linker = array();
				foreach ($remodeModule->keys as $rkey) {
					if ($rkey == "id")
						$linker[] = $tablecast.".$rkey = ".$this->name.".".$nome;
					else if (in_array($rkey,$this->options[CONS_MODULE_MULTIKEYS])) {
						$linker[] = $tablecast.".$rkey = ".$this->name.".".$rkey;
					} else { // not a parent nor main key, how to link?
						if ($remodeModule->fields[$rkey][CONS_XML_MODULE] == $this->name)
							$linker[] = $tablecast.".$rkey = ".$this->name.".".$this->keys[0];
						else {
							$localField = $this->get_key_from($remodeModule->fields[$rkey][CONS_XML_MODULE]);
							$linker[] = $tablecast.".$rkey = ".$this->name.".".$localField;
						}
					}
				}
				$sql['LEFT'][] = ($remodeModule->dbname." as ".$tablecast." ON ".implode(" AND ",$linker)).(count($extrakey)>0 && count($linker)>0?" AND ":"").implode(" AND ",$extrakey);
			  } else {
				$sql['FROM'][] = $remodeModule->dbname." as ".$tablecast;
				foreach ($remodeModule->keys as $rkey) {
					if ($rkey == "id")
						$sql['WHERE'][]= $tablecast.".$rkey = ".$this->name.".".$nome;
					else if (in_array($rkey,$this->options[CONS_MODULE_MULTIKEYS])) {
						$sql['WHERE'][] = $tablecast.".$rkey = ".$this->name.".".$rkey;
					} else if ($remodeModule->fields[$rkey][CONS_XML_TIPO] == CONS_TIPO_LINK) { // not a parent nor main key, is a link to another table
						if ($remodeModule->fields[$rkey][CONS_XML_MODULE] == $this->name)
							$sql['WHERE'][] = $tablecast.".$rkey = ".$this->name.".".$this->keys[0];
						else {
							$localField = $this->get_key_from($remodeModule->fields[$rkey][CONS_XML_MODULE]);
							$sql['WHERE'][] = $tablecast.".$rkey = ".$this->name.".".$localField;
						}
					} else {// not simple id, parent or link. Its a non-standard ID for another table
						$sql['WHERE'][] = $tablecast.".$rkey = ".$this->name.".".$nome;
					}
				}
				foreach ($extrakey as $exk)
					$sql['WHERE'][] = $exk;
			  }
			  $pos++;
		  }
		}
		array_unshift($sql['SELECT'],$this->name.".*");
		if (!$noJoin && $this->parent->debugmode && !(is_file(CONS_PATH_CACHE.$_SESSION['CODE']."/".$this->dbname."_list.cache")) && !isset($_REQUEST['nocache'])) // save simple cache
			cWriteFile(CONS_PATH_CACHE.$_SESSION['CODE']."/".$this->dbname."_list.cache",serialize($sql));
	  } # !$sql

	  // embeds:

	  if ($embedWhere != "") array_unshift($sql['WHERE'],$embedWhere);
	  if ($this->order != "" && $embedOrder == "") {
	  	$ord = explode(",",$this->order);
		foreach ($ord as $orditem) {
			$orditem = trim($orditem);
			if (strpos($orditem,"+") !== false) {
				$orditem = str_replace("+","",$orditem);
				if (isset($this->fields[$orditem]))
					$sql['ORDER'][] = $this->name.".".$orditem." ASC";
				else
					$sql['ORDER'][] = $orditem." ASC";
			} else {
				$orditem = str_replace("-","",$orditem);
				if (isset($this->fields[$orditem]))
					$sql['ORDER'][] = $this->name.".".$orditem." DESC";
				else
					$sql['ORDER'][] = $orditem." DESC";
			}
		}
	  }
	  if ($embedOrder != "") $sql['ORDER'][] = $embedOrder;
	  if ($embedLimit != "") $sql['LIMIT'] = (is_array($embedLimit)?$embedLimit:array($embedLimit));

	  // done!

	  return $sql;
	} # get_base_sql

	function check_mandatory($data,$action) {
		# checks if a mandatory field is missing or invalid
		# called by runAction if mfo is false
		$mandatory = array();
		# add parents if they are not already keys
		foreach ($this->keys as $pkey)
			# note that auto increments should be ignored on include
			if (!in_array($pkey,$mandatory) && ($action != CONS_ACTION_INCLUDE || strpos($this->fields[$pkey][CONS_XML_SQL],"AUTO_INCREMENT") === false))
				$mandatory[] = $pkey;
  		$missing = array();
  		if ($action == CONS_ACTION_UPDATE || $action == CONS_ACTION_INCLUDE) {
  			# require both keys (parents are considered keys even if they are not) AND mandatory, while any other action only keys
  			foreach ($this->fields as $fname => $field) {
  				# adds mandatory fields to list
  				if (isset($field[CONS_XML_MANDATORY]) && !in_array($fname,$mandatory)) {
  					# note that auto increments should be ignored on include
					if (strpos($field[CONS_XML_SQL],"AUTO_INCREMENT") === false)
  						$mandatory[] = $fname;
				}
  			}
  		}
  		foreach ($mandatory as $field) {
  			if ($field != "" && $this->fields[$field][CONS_XML_TIPO] != CONS_TIPO_UPLOAD) {
  				if (
  					 # INCLUDE and a mandatory field did not arrive
  					($action == CONS_ACTION_INCLUDE && (!isset($data[$field]) || $data[$field] === "") && !isset($this->fields[$field][CONS_XML_DEFAULT]))	||
  					 # UPDATE and a mandatory field CAME empty (and it is not CONS_XML_IGNORENEDIT)
					($action == CONS_ACTION_UPDATE && isset($data[$field]) && ($data[$field] === 0 || $data[$field] === "") && !isset($this->fields[$field][CONS_XML_IGNORENEDIT])) ||
					 # UPDATE and a mandatory link field CAME zero (and it is not CONS_XML_IGNORENEDIT)
					($action == CONS_ACTION_UPDATE && isset($data[$field]) && ($data[$field] === 0 || $data[$field] === '') && $this->fields[$field][CONS_XML_TIPO] == CONS_TIPO_LINK && !isset($this->fields[$field][CONS_XML_IGNORENEDIT])) ||
					 # UPDATE require keys always
					($action == CONS_ACTION_UPDATE && in_array($field,$this->keys) && !isset($data[$field]))
				   ) {
					# this item might be a broken date, check for this
					if (($this->fields[$field][CONS_XML_TIPO] == CONS_TIPO_DATE ||
						 $this->fields[$field][CONS_XML_TIPO] == CONS_TIPO_DATETIME) &&
						isset($data[$field."_day"]) &&
						isset($data[$field."_month"]) &&
						isset($data[$field."_year"]))
						continue; # ok came as a broken date
					if (($this->fields[$field][CONS_XML_TIPO] == CONS_TIPO_DATE ||
						 $this->fields[$field][CONS_XML_TIPO] == CONS_TIPO_DATETIME) &&
						 (isset($this->fields[$field][CONS_XML_TIMESTAMP]) ||
						 isset($this->fields[$field][CONS_XML_UPDATESTAMP])))
						 continue; # ok, no date but it's set to automatic
					$missing[] = $field;
				} else { # if (huge)
					# field came, but is it valid? (hack!)
					if (isset($data[$field]) && ($this->fields[$field][CONS_XML_TIPO] == CONS_TIPO_INT || $this->fields[$field][CONS_XML_TIPO] == CONS_TIPO_FLOAT) && !is_numeric(str_replace(",",".",$data[$field]))) {
						$missing[] = $field;
						if (strpos($data[$field],"<")!==false) {
							$this->parent->errorControl->raise(125,$data[$field]);
						}
					}
				}
  			} else if ($field != "" && $action == CONS_ACTION_INCLUDE && $this->fields[$field][CONS_XML_TIPO] == CONS_TIPO_UPLOAD && (!isset($_FILES[$field]) || $_FILES[$field]['error'] != 0)) {
  				$this->parent->errorControl->raise(200+(isset($_FILES[$field])?$_FILES[$field]['error']:4),$field);
  				$missing[] = $field; # mandatory file upload did not come or failed to upload
  			}
  		} # foreach
		return $missing;
	} # check_mandatory

	function get_key_from($module,$fast_reference = "",$all = false) {
		# Returns which of my fields points to that module, with a fast_reference (works like a cache)
		# all will return an array with all fields that point to said module, instead of the FIRST
		if (is_object($module)) $module = $module->name;
		$results = array();
		if ($fast_reference != "") { # checks if fast_refrence is correct
			if (isset($this->fields[$fast_reference]) && $this->fields[$fast_reference][CONS_XML_TIPO] == CONS_TIPO_LINK && $this->fields[$fast_reference][CONS_XML_MODULE] == $module) {
				if ($all)
					$results[] = $fast_reference;
				else
					return $fast_reference; # that's right
			}
		}
		foreach ($this->fields as $name => $field)
			if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK && $field[CONS_XML_MODULE] == $module) {
				if ($all) {
					if (!in_array($name,$results)) $results[] = $name;
				} else
					return $name;
			}
		# if I got here, I do not have such key
		return $all?$results:"";
	} # get_key_from

	function getKeys(&$whereStruct, &$keyArray, $data, $fromRemote = "", $addMe = false) {
		# return the keys in all formats functions might need, returns sucess on aquiring keys (data have all keys) then:
		# $whereStruct is a WHERE string which would result only this item in a SQL select
		# $keyArray is an array with all keys (name => key)
		# if fromRemote is set with a MODULE name, reports data came from another module and thus my main ID is set in a different format (checks which on that module)
		# addMe forces my module name at the SQL (standard, but getKeys might be used in other ways)

		$whereStruct = "";
		$keyArray = array();
		$haveAll = true; # have all keys = result of this function
		foreach ($this->keys as $x => $key) {
			if (!isset($data[$key]) || ($fromRemote != "" && $key == $this->keys[0])) {
				# key is missing or this came from a remote module and we are checking our main key
				if ($fromRemote != "") {
					#remote key
					$remoteModule = $this->fields[$key][CONS_XML_TIPO] == CONS_TIPO_LINK?$this->fields[$key][CONS_XML_MODULE]:$this->name;
					$remoteModuleObj = $this->parent->loaded($remoteModule);
					$remoteKey = $remoteModule->get_key_from($remoteModule,"id_".$remoteModule); # gets which field is the key on the remote module which points to this module, works with only one
					if ($remoteKey == "" || !isset($data[$remoteKey])) {
						$haveAll = false;
						continue;
					} else
						$data[$key] = $data[$remoteKey];
				} else {
					$haveAll = false;
					break;
				}
			}
			$whereStruct .= ($addMe?$this->name.".":"").$key."=\"".$data[$key]."\" AND ";
			$keyArray[$key] = $data[$key];
		}
		if ($whereStruct != "")
			$whereStruct = substr($whereStruct,0,strlen($whereStruct)-4);
		return $haveAll;
	} # getKeys

	function deleteUploads($kA, $field = "", $ids = "", $basefile = "") {
		# delete file uploads for the specified item
		# if only the keys are sent, delete ALL files for this item
		#	simple ID: all thumbnail for that particular field
		#	full ID: the specific thumbnail


		# TODO: not working for serialized ($basefile) and this is also wrong, should allow field only


		$dels = 0;
		if ($field != "" && $ids != "") { # use field/ids, if this is a simple ID, delete all files for this field, otherwise only the keyed file
		 	$total = isset($this->fields[$field][CONS_XML_THUMBNAILS]) ? count($this->fields[$field][CONS_XML_THUMBNAILS]) : 1;
		 	$path = CONS_FMANAGER.$this->name."/".(isset($this->fields[$field][CONS_XML_FILEPATH])?$this->fields[$field][CONS_XML_FILEPATH]:"");
		 	if ($path[strlen($path)-1] != "/") $path .= "/";
		 	$idc = explode("_",$ids);
		 	if (count($idc) == count($this->keys)) { # simple ID (the IDs for the field)
	 			$basefile = $field."_".$ids."_";
	 			for ($c=1;$c<=$total;$c++) {
	 				$fileName = $path.($c>1?"t/":"").$basefile.$c;
	 				$ext = "";
	 				if (locateAnyFile($fileName,$ext))
	 					if (is_file($fileName) && unlink($fileName)) $dels++;

	 			}
		 	} else { # full ID
		 		$basefile = $field."_".$ids;
		 		for ($c=1;$c<=$total;$c++) {
		 			$fileName = $path.($c>1?"t/":"").$basefile."_".$c;
		 			$ext = "";
	 				if (locateAnyFile($fileName,$ext))
	 					if (is_file($fileName) && unlink($fileName)) $dels++;
		 		}
		 	}
		} else { # no field specified, delete ALL files for this item
			$string_ids = "";
			foreach ($kA as $id => $value) {
				$string_ids .= $value."_";
			}
			if ($string_ids != "") {
				$string_ids = substr($string_ids,0,strlen($string_ids)-1);
				foreach ($this->fields as $name => $field) {
					if ($field[CONS_XML_TIPO] == CONS_TIPO_UPLOAD) {
						$dels += $this->deleteUploads("",$name,$string_ids);
					}
				}
			}
		}
		return $dels;
	} #deleteUploads

	function prepareUpload($name,$kA,&$data) {
		# Returns the same errCode from storefile, plus 8 (unable to create thumbails), 9 (near time limit), 10 (quota exceeded)
		# file format should be [field][ids_]#_thumbid (or 1 for no thumb)

		if (!isset($_FILES[$name]))
			return 4;
		else if ($_FILES[$name]['error'] <5 && $_FILES[$name]['error'] != 0)
			return $_FILES[$name]['error']; // 1~4

		$isImg = isset($this->fields[$name][CONS_XML_TWEAKIMAGES]) || isset($this->fields[$name][CONS_XML_THUMBNAILS]);

		if ($isImg) { # prepare thumbnail handling variables, as well conditional thumbs

			if (isset($this->fields[$name][CONS_XML_CONDTHUMBNAILS])) {
				preg_match("@ENUM \(([^)]*)\).*@",$this->fields[$this->fields[$name][CONS_XML_CONDTHUMBNAILS][0]][CONS_XML_SQL],$regs);
				$thumbsettings = explode(";",$this->fields[$name][CONS_XML_CONDTHUMBNAILS][1]);
				$enums = explode(",",$regs[1]);
				$c = 0;
				$found = false;
				foreach ($enums as $reg) {
					if ($data[$this->fields[$name][CONS_XML_CONDTHUMBNAILS][0]] == str_replace("'","",$reg)) {
						$thumbsettings = explode("|",$thumbsettings[$c]);
						$found = true;
						break;
					}
					$c++;
				}
				if (!$found) {
					$thumbsettings = isset($this->fields[$name][CONS_XML_THUMBNAILS])?$this->fields[$name][CONS_XML_THUMBNAILS]:explode("|",$thumbsettings[0]);
				}
			} else {
				$thumbsettings= isset($this->fields[$name][CONS_XML_THUMBNAILS])?$this->fields[$name][CONS_XML_THUMBNAILS]:array(array(0,0));
			}

		}

		# quota test
		$this->parent->loadDimconfig(true);
		if (isset($this->parent->dimconfig['_usedquota']) && isset($this->parent->dimconfig['quota']) && $this->parent->dimconfig['quota'] > 0) {
			if ($this->parent->dimconfig['_usedquota'] > $this->parent->dimconfig['quota'])
				return 10; # quota exceeded
		}

		# prepares path
		$path = CONS_FMANAGER.$this->name."/";
		if (!is_dir($path)) makeDirs($path);

		if (isset($this->fields[$name][CONS_XML_FILEPATH])) { # custom path
			$path .= $this->fields[$name][CONS_XML_FILEPATH];
			if ($path[strlen($path)-1] != "/") $path .= "/";
			if (!is_dir($path)) safe_mkdir($path);
		}

		# prepares filename with item keys
		$filename = $name."_";
		foreach ($kA as $id => $value)
			$filename .= $value."_";

		# prepares filetype filter
		if (isset($this->fields[$name][CONS_XML_FILETYPES])) {
			$ftypes = "udef:".$this->fields[$name][CONS_XML_FILETYPES];
		} else
			$ftypes = "";

		# prepares watermark and/or crop (for images)
		$WM_TODO = array();
		if (isset($this->fields[$name][CONS_XML_TWEAKIMAGES])) {
			foreach ($this->fields[$name][CONS_XML_TWEAKIMAGES] as $c => $WM) {
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
						if ($resample)
							$thisTODO['resample'] = explode(",",$thumbsettings[$c]);
						$TODO[] = $thisTODO;
					} else if ($concept[0] == "croptofit") {
						$TODO[] = "C".(isset($concept[1])?$concept[1]:'');
					}
				}
				$WM_TODO[$c] = $TODO;
			}
		}

		# perform upload and possible thumbnails if the file was uploaded
		$errCode = 4; # supose no upload

		if (isset($_FILES[$name])) {

			# initial size check (no need to even try if it's bigger)
			$mfs = isset($this->fields[$name][CONS_XML_FILEMAXSIZE])?$this->fields[$name][CONS_XML_FILEMAXSIZE]:0;
			if ($mfs >0 && !$isImg) {
				if (filesize($_FILES[$name]['tmp_name'])>$mfs) {
					@unlink($_FILES[$name]['tmp_name']);
					return 2; # file larger than allowed by field and is not an image (thus, we cannot reduce)
				}
			}

			# perform upload
			$thisFilename = $path.$filename."1";
			$errCode = storeFile($_FILES[$name],$thisFilename,$ftypes); # <----------------- upload happens here

			$arquivo = explode(".",$thisFilename);
			$ext = strtolower(array_pop($arquivo)); # <-- ext for the file
			$arquivo = implode(".",$arquivo);

			# if ok and is an image, check thumbnails
			if ($errCode == 0 && $isImg) {

				# delete other images (could have uploaded a different image type than before on edit)
				$exts = array("jpg","gif","swf","png","jpeg","ico","bmp");
				foreach ($exts as $x => $sext) {
					if ($sext != $ext && is_file($arquivo.".".$sext))
						@unlink($arquivo.".".$sext);
				}

				# if this is not an JPG image, and it's larger then mfs, won't work at all. Abort
				if ($mfs > 0 && filesize($thisFilename)>$mfs && $ext != 'jpg') {
					@unlink($thisFilename);
					return 2; # file larger than allowed by field and is not a resizable image
				}


				if ($ext == "swf") { // might have tweakimages (isImg) but accept flash

					#check if the dimensions of the flash are too big
					$dim = explode(",",$thumbsettings[0]);
					$h = getimagesize($thisFilename);
					if ($h[2] != IMAGETYPE_SWF && $h[2] != IMAGETYPE_SWC) {
						die();
						return 7; // swf but not a swf!?
					} else {
						if ($h[0] > $dim[0] || $h[1] > $dim[1]) {
							return 12; // too big
						}
					}

				} else {

					# $thisFilename has the image untreated.
					# Create all thumbnails, then treat the main image:

					$thumbVersions = count($thumbsettings);
					if ($thumbVersions > 1) { # has other versions/thumbnails, work these first
						if (!is_dir($path."t/")) makeDirs($path."t/");
						for ($tb=1;$tb<$thumbVersions;$tb++) { # for all thumbs ...
							if ($this->parent->nearTimeLimit()) {
								return 9;
							}
							$dim = explode(",",$thumbsettings[$tb]); # remember, the array start at 0, not 1
							$thisFilenameT = $path."t/".$filename.($tb+1);
							if (!resizeImage($thisFilename,$thisFilenameT,$dim[0],isset($dim[1])?$dim[1]:0,0,isset($WM_TODO[$tb])?$WM_TODO[$tb]:array()) == 2) {
								# error!
								@unlink($thisFilename);
								return 8; // whoever called this function should also perform cleanup
							}
						} #for each thumb
					}

					# done, process main image
					$dim = explode(",",$thumbsettings[0]);
					if (resizeImageCond($thisFilename,$dim[0],isset($dim[1])?$dim[1]:0,isset($WM_TODO[0])?$WM_TODO[0]:array())==2) {
						@unlink($thisFilename);
						return 8; // whoever called this function should also perform cleanup
					}

					# check mfs
					clearstatcache(); # resize could have changed file size
					if ($mfs > 0 && filesize($thisFilename) > $mfs) {
						if ($ext == 'jpg') {
							$miniatura_id = imagecreatefromjpeg($thisFilename);
							imagejpeg($miniatura_id,$thisFilename, 50); // reduce step2
							imagedestroy($miniatura_id);
							clearstatcache();
							if (filesize($thisFilename) > $mfs) {
								unlink($thisFilename);
								return 2; // unable to reduce more
							}
						} else { # can't reduce quality on non-jpg
							unlink($thisFilename);
							return 2; // unable to reduce more
						}
					}
				}

			}
		} # upload + image handling ok?

		if ($errCode == 0) {
			$this->parent->dimconfig['_usedquota'] += filesize($thisFilename); # simple quota controler, note this is not counting thumbs
			$this->parent->saveConfig();
		}
		return $errCode;
	} #prepareUpload
#-
	private function autoPrune($enumPruneCache,$data) { # MULTIKEY COMPLIANT!
		// data set an enum which has a autoprune value. Locate all other fields in the database with that value, count if we exceed the autoprune set, and turn the oldest one to the default enum
		// get order ready, by time (locate time field)

		$order = "";
		foreach ($this->fields as $fname => $field) {
			if (isset($field[CONS_XML_TIMESTAMP])) {
				$order = " ORDER BY $fname ASC"; // so the FIRST is the OLDEST
			}
		}
		foreach ($enumPruneCache as $fieldData) { // each is a pair fname, number
			$field = $fieldData[0];
			$maxItems = $fieldData[1];
			$newvalue = str_replace("'","",str_replace('"','',$fieldData[2]));
			if ($maxItems == 0 || $maxItems == '*') continue; // er, shouldn't even get here
			$sql = "SELECT ".implode(",",$this->keys)." FROM ".$this->dbname." WHERE $field=\"".$data[$field]."\"$order";
			if ($this->parent->dbo->query($sql,$r,$n) && $n>$maxItems) {
				$otherData = $this->parent->dbo->fetch_assoc($r);
				// set otherData to new enum
				$sql = "UPDATE ".$this->dbname." SET $field=\"".$newvalue."\" WHERE ";
				foreach ($this->keys as $kname)
					$sql .= $kname."=\"".$otherData[$kname]."\" AND ";
				$sql = substr($sql,0,strlen($sql)-5); // remove lastAND
				$this->parent->dbo->simpleQuery($sql);
			}

		}
	}
#-
	private function sqlParameter($isADD,&$data,$name,&$field,&$EnumPrunecache,$isSerialized=false,$kA='',$wS='') {
		$output = false;
		$encapsulation = $isSerialized?'':'"';
		switch( $field[CONS_XML_TIPO] ) {
			case CONS_TIPO_INT:
				if (isset($data[$name]) && $data[$name] !== "" && is_numeric($data[$name]))
					$output = $data[$name];
				else if ($isADD && isset($field[CONS_XML_DEFAULT]))
					$output = $field[CONS_XML_DEFAULT];
				break;
			case CONS_TIPO_LINK:
				if ($field[CONS_XML_LINKTYPE] == CONS_TIPO_INT || $field[CONS_XML_LINKTYPE] == CONS_TIPO_FLOAT) $encapsulation = '';
				if (isset($data[$name]) && (($data[$name] !== '' && $data[$name] !== 0) || !isset($field[CONS_XML_MANDATORY]))) {
					# non-mandatory links accept 0 values, otherwise 0 is not acceptable
					if (((!$isADD && isset($field[CONS_XML_IGNORENEDIT])) || $isADD) && ($data[$name] === 0 || $data[$name] === '')) break;
					else if (($field[CONS_XML_LINKTYPE] == CONS_TIPO_INT || $field[CONS_XML_LINKTYPE] == CONS_TIPO_FLOAT) && $data[$name] === '') $data[$name]=0;

					# if this is a parent, check if this won't create a cyclic parenting
					if ($data[$name] !== 0 && $data[$name] !== '' && $field[CONS_XML_MODULE] == $this->name && $this->options[CONS_MODULE_PARENT] == $name) {
						if (!$isADD && $data[$name] == $data[$this->keys[0]]) {
							$data[$name] = 0;
							$this->parent->errorControl->raise(128,$name,$this->name,"Parent=Self");
							if (isset($field[CONS_XML_MANDATORY])) return false;
						} else {
							$antiCicle = array($data[$this->keys[0]]);
							$idP = $data[$name];
							while ($idP !== 0) {
								$idP = $this->parent->dbo->fetch("SELECT $name FROM ".$this->dbname." WHERE ".$this->keys[0]."=$idP");
								if (in_array($idP,$antiCicle)) break; // cicle!
								$antiCicle[] = $idP;
							}
							unset($antiCicle);
							if ($idP !== 0) {
								# did not reach root
								$data[$name] = 0;
								$this->parent->errorControl->raise(128,$name,$this->name);
								if (isset($field[CONS_XML_MANDATORY])) return false;
							}
						}
					}
					$output = $encapsulation.$data[$name].$encapsulation;
				} else if ($isADD && isset($field[CONS_XML_DEFAULT])) {
					if ($field[CONS_XML_DEFAULT] == "%UID%" && defined("CONS_AUTH_USERMODULE") && $field[CONS_XML_MODULE] == CONS_AUTH_USERMODULE && $_SESSION[CONS_SESSION_ACCESS_LEVEL]>0 && isset($_SESSION[CONS_SESSION_ACCESS_USER]['id']))
						$output = $encapsulation.$_SESSION[CONS_SESSION_ACCESS_USER]['id'].$encapsulation;
					else if ($field[CONS_XML_DEFAULT] != "%UID%")
						$output = $encapsulation.$field[CONS_XML_DEFAULT].$encapsulation;
				}
				break;
			case CONS_TIPO_FLOAT:
				if (isset($data[$name]) && $data[$name] !== "") {
					$data[$name] = fv($data[$name]);
					if (is_numeric($data[$name]))
						$output = str_replace(",",".",$data[$name]);
					else if ($isADD && isset($field[CONS_XML_DEFAULT]))
						$output = $field[CONS_XML_DEFAULT];
				} else if ($isADD && isset($field[CONS_XML_DEFAULT]))
					$output = $field[CONS_XML_DEFAULT];
				break;
			case CONS_TIPO_VC:
				if (isset($data[$name])) {
					if (!isset($field[CONS_XML_SPECIAL]) || $field[CONS_XML_SPECIAL] != "urla") {
						if (!isset($field[CONS_XML_CUSTOM]))
							$data[$name] = cleanString($data[$name],isset($field[CONS_XML_HTML]),$_SESSION[CONS_SESSION_ACCESS_LEVEL]==100);
						else if (!$isSerialized)
							$data[$name] = addslashes_EX($data[$name],true);
					}
					if (isset($field[CONS_XML_SPECIAL])) {
						if ($field[CONS_XML_SPECIAL] == "urla") {
							if ((!isset($data[$name]) || $data[$name] == '')) {
								$source = isset($field[CONS_XML_SOURCE])?$field[CONS_XML_SOURCE]:"{".$this->title."}";
								$tp = new CKTemplate($this->parent->template);
								$tp->tbreak($source);
								$data[$name] = $tp->techo($data);
								unset($tp);
							}
							$data[$name] = str_replace("&gt;","",str_replace("&lt;","",str_replace("&quot;","",$data[$name])));
							$data[$name] = removeSimbols($data[$name],true,false,CONS_FLATTENURL);
						}
						if ($field[CONS_XML_SPECIAL] == "login" && $data[$name] != "") {
							if (!preg_match('/^([A-Za-z0-9_\-\.@]){4,20}$/',$data[$name])) {
								$data[$name] = "";
								$this->parent->errorControl->raise(129,$name,$this->name);
								break;
							}
						}
						if ($field[CONS_XML_SPECIAL] == "mail" && $data[$name] != "") {
							if (!isMail($data[$name])) {
								$data[$name] = "";
								$this->parent->errorControl->raise(130,$name,$this->name);
								break;
							}
						}
						if ($field[CONS_XML_SPECIAL] == "ucase" && $data[$name] != "") {
							$data[$name] = strtoupper($data[$name]);
						}
						if ($field[CONS_XML_SPECIAL] == "lcase" && $data[$name] != "") {
							$data[$name] = strtolower($data[$name]);
						}
						if ($field[CONS_XML_SPECIAL] == "path" && $data[$name] != "") {
							if (!preg_match('/^([A-Za-z0-9_\/\-]*)$/',$data[$name])) {
								$data[$name] = "";
								$this->parent->errorControl->raise(131,$name,$this->name);
								break;
							}
						}
						if ($field[CONS_XML_SPECIAL] == "onlinevideo" && $data[$name] != "") {
							if (!preg_match('/^([A-Za-z0-9_\-]){8,20}$/',$data[$name])) {
								$data[$name] = "";
								$this->parent->errorControl->raise(132,$name,$this->name);
								break;
							}
						}
						if ($field[CONS_XML_SPECIAL] == "time" && $data[$name] != "") {
							if (!preg_match('/^([0-9]){1,2}(:)([0-9]){1,2}$/',$data[$name])) {
								$data[$name] = "";
								$this->parent->errorControl->raise(133,$name,$this->name);
								break;
							} else {
								$data[$name] = explode(":",$data[$name]);
								$data[$name][0] = (strlen($data[$name][0])==1?"0":"").$data[$name][0];
								$data[$name][1] = (strlen($data[$name][1])==1?"0":"").$data[$name][1];
								$data[$name] = $data[$name][0].":".$data[$name][1];
							}
						}
					}
					if (!$isADD && isset($field[CONS_XML_IGNORENEDIT]) && $data[$name] == "") break;
					else if ($isADD && isset($field[CONS_XML_DEFAULT])) $data[$name] = $encapsulation.$field[CONS_XML_DEFAULT].$encapsulation;
					$output = $encapsulation.$data[$name].$encapsulation;
				}
				break;
			case CONS_TIPO_TEXT:
				if (isset($data[$name])) {
					# WYSIWYG garbage ...
					if (isset($field[CONS_XML_HTML]) && !isset($field[CONS_XML_CUSTOM])) {
						# WYSIWYG garbage
						$data[$name] = str_replace("&#160;"," ",trim($data[$name]));
						$data[$name] = trim(str_replace("<p></p>","",$data[$name]));
						if ($this->invalidHTML($data[$name])) { # external editors garbage that can break HTML
							$this->parent->errorControl->raise(135,$name,$this->name);
						}
					}
					if (!isset($field[CONS_XML_CUSTOM])) {
						$data[$name] = cleanString($data[$name],isset($field[CONS_XML_HTML]),$_SESSION[CONS_SESSION_ACCESS_LEVEL]==100);
					} else if (!$isSerialized) {
						$data[$name] = addslashes_EX($data[$name],true);
					}
					if (!$isADD&& isset($field[CONS_XML_IGNORENEDIT]) && $data[$name] == "") break;
					$output = $encapsulation.$data[$name].$encapsulation;
				} else if ($isADD && isset($field[CONS_XML_DEFAULT]))
					$output = $encapsulation.$field[CONS_XML_DEFAULT].$encapsulation;
				break;
			case CONS_TIPO_DATETIME:
			case CONS_TIPO_DATE:
				if (!isset($data[$name]) || $data[$name] == '') {
					if (!$isADD && isset($field[CONS_XML_UPDATESTAMP])) {
						$output = "NOW()";
						$data[$name] = date("Y-m-d").($field[CONS_XML_TIPO]==CONS_TIPO_DATETIME?" ".date("H:i:s"):""); // might be used by friendly url or such
						break;
					} else if ($isADD && (isset($field[CONS_XML_TIMESTAMP]) || isset($field[CONS_XML_UPDATESTAMP]))) {
					 	$output = "NOW()";
					 	$data[$name] = date("Y-m-d").($field[CONS_XML_TIPO]==CONS_TIPO_DATETIME?" ".date("H:i:s"):""); // might be used by friendly url or such
					 	break;
					}
				}
				if (!isset($data[$name]) && isset($data[$name."_day"])) {
				 	# date came into separated fields, merge them
				 	$theDate = $this->parent->intlControl->mergeDate($data,$name."_");
					if (!$theDate == false || (($theDate == "0000-00-00" || $theDate == "0000-00-00 00:00:00") && isset($field[CONS_XML_IGNORENEDIT])))
						break; # empty date can be ignored, or corrupt date
					$output = $encapsulation.$theDate.$encapsulation;
				} else { # came in mySQL format or i18n fromat
				 	if (isset($data[$name]) && $data[$name] != "") {
				 		$data[$name] = trim($data[$name]);
				 		$theDate = $data[$name];
				 		$theDate = $this->parent->intlControl->dateToSql($theDate,$field[CONS_XML_TIPO]==CONS_TIPO_DATETIME); // handles any format of human or sql date
						if ($theDate === false) {
							if (substr($data[$name],0,5) == "NOW()") {
				 				$output = $data[$name];
				 				$data[$name] = date("Y-m-d").($field[CONS_XML_TIPO]==CONS_TIPO_DATETIME?" ".date("H:i:s"):""); // might be used by friendly url or such
							} else
				 				$this->parent->errorControl->raise(134,$name,$this->name);
						} else {
							$output = $encapsulation.$theDate.$encapsulation;
							$data[$name] = $theDate; // other fields might need it
						}
				 	} else if (isset($data[$name])) { // blank
				 		if (!$isADD && isset($field[CONS_XML_IGNORENEDIT])) break;
				 		$output = (isset($field[CONS_XML_MANDATORY]) && $field[CONS_XML_MANDATORY]?$encapsulation."0000-00-00".($field[CONS_XML_TIPO]==CONS_TIPO_DATETIME?" 00:00:00":"").$encapsulation:'NULL');
				 	}
				}
				break;
			case CONS_TIPO_ENUM:
				if (isset($data[$name])) {
					if ($data[$name] == "") { # enum does not accept empty values, this means it's a NON-MANDATORY enum comming empty = NULL
						$output = "NULL";
					} else {
						$data[$name] = str_replace("\"","",str_replace("'","",$data[$name]));
						$output = $encapsulation.$data[$name].$encapsulation;
						if (isset($field[CONS_XML_AUTOPRUNE])) { // possible prune
							//$EnumPrunecache
							preg_match("@ENUM \(([^)]*)\).*@",$field[CONS_XML_SQL],$regs);
							$enums = explode(",",$regs[1]);
							$pruneRecipient = "";
							for ($ec=0;$ec<count($enums);$ec++) {
								if (isset($field[CONS_XML_AUTOPRUNE][$ec]) && $field[CONS_XML_AUTOPRUNE][$ec] == '*')
									$pruneRecipient = $enums[$ec];
							}
							for ($ec=0;$ec<count($enums);$ec++) {
								if ("'".$data[$name]."'" == $enums[$ec]) {
									if (isset($field[CONS_XML_AUTOPRUNE][$ec]) && $field[CONS_XML_AUTOPRUNE][$ec] != '0' && $field[CONS_XML_AUTOPRUNE][$ec] != '*')
										$EnumPrunecache[] = array($name,$field[CONS_XML_AUTOPRUNE][$ec],$pruneRecipient);
									break; // for
								}
							}
						}
					}
				} else if ($isADD && isset($field[CONS_XML_DEFAULT]))
					$output = $encapsulation.$field[CONS_XML_DEFAULT].$encapsulation;
				break;

			case CONS_TIPO_OPTIONS:
				# must come as a string of 0 and 1
				if (isset($data[$name]) && strlen($data[$name])>= count($field[CONS_XML_OPTIONS])) {
					# test if they are all 0 and 1!
					$ok= true;
					for ($c=0;$c<strlen($data[$name]);$c++) {
						if ($data[$name][$c] != "0" && $data[$name][$c] != "1") {
							$ok = false;
							break;
						}
					}
					if ($ok)
						$output = $encapsulation.$data[$name].($isADD?'0000':'').$encapsulation;
				}
			break;
			case CONS_TIPO_UPLOAD:
				if (!$isADD) { # upload on add happens AFTER the SQL include, so if it fails, we don't even bother processing upload
					if (isset($data[$name."_delete"]) || (isset($_FILES[$name]) && $_FILES[$name]['error']==0 )) { // delete ou update
						$ids = "";
						foreach ($this->keys as $key)
							$ids .= $data[$key]."_";
						$ids = substr($ids,0,strlen($ids)-1);
						$this->deleteUploads($data, $name, $ids);
					}
					$upOk = $this->prepareUpload($name,$kA,$data);
					$upvalue = $upOk == '0'?'y':'n';
					if ($upOk != 0 && $upOk != 4) { # notification for the upload (4 = nothing sent, 0 = sent and ok)
						$this->parent->errorControl->raise(200+$upOk,$upOk,$this->name,$name);
					}
					if ($upOk != 4) $output = $encapsulation.$upvalue.$encapsulation; // we CHANGED the file, set if it is ok
					else { // no change, but take this oportunity and check if the file exists!
						$upvalue = 'n';
						$path = CONS_FMANAGER.$this->name."/";
						if (is_dir($path)) {
							if (isset($this->fields[$name][CONS_XML_FILEPATH])) {
								$path .= $this->fields[$name][CONS_XML_FILEPATH];
								if ($path[strlen($path)-1] != "/") $path .= "/";
								if (!is_dir($path)) safe_mkdir($path);
							}
							# prepares filename with item keys
							$filename = $path.$name."_";
							foreach ($this->keys as $key)
								$filename .= $data[$key]."_";
							$filename .= "1";
							$upvalue = locateAnyFile($filename,$ext,isset($this->fields[$name][CONS_XML_FILETYPES])?$this->fields[$name][CONS_XML_FILETYPES]:'')?'y':'n';
						}
						$output = $encapsulation.$upvalue.$encapsulation;
					}
				}
			break;
			case CONS_TIPO_ARRAY:
				if (isset($data[$name])) {
					if (is_array($data[$name]))
						$output = $data[$name];
					else { # came in serialized (JSON or php)
						if ($data[$name][0] == '[') # JSON
							$output = @json_decode($data[$name]);
						else
							$output = @unserialize($data[$name]); # we will serialize the whole thing
						if ($output === false) {
							$this->parent->errorControl->raise(189,$name,$this->name);
							$output= "";
						}
					}
				}
			break;
			case CONS_TIPO_SERIALIZED:
				if (isset($data[$name])) { // came raw data, we store as is, YOU should serialize raw data
					$data[$name] = addslashes_EX($data[$name],true);
					if (isset($field[CONS_XML_IGNORENEDIT]) && $data[$name] == "") break;
					$output = $encapsulation.$data[$name].$encapsulation;
				} else if ($this->fields[$name][CONS_XML_SERIALIZED] > 1) { // set to WRITE or ALL
					// note: we ADD fields, never replace, because we should allow partial edits, thus we need to read the original data first
					$sql = "SELECT $name FROM ".$this->name." WHERE $wS";
					$serialized = $this->parent->dbo->fetch($sql);
					if ($serialized === false) $serialized = array();
					$serializedFields = 0;
					foreach ($this->fields[$name][CONS_XML_SERIALIZEDMODEL] as $exname => &$exfield) {
						if (isset($data[$name."_".$exname])) {
							$outfield = $this->sqlParameter(true,$data,$name."_".$exname,$exfield,$EnumPrunecache,true);
							if ($outfield !== false && $outfield != 'NULL') $serialized[$exname] = $outfield; # we don't need to store NULL like in sql
						}
					}
					$output = $encapsulation.addslashes_EX(serialize($serialized)).$encapsulation;
				}
			break;
		} # switch
		return $output;
	}
#-
	function runAction($action,$data,$silent=false,$mfo=false,$startedAt="") {
		# mfo is "Mandatory Fields Ok", which removes the need to check them
		# returns TRUE or FALSE
		# check for auto_increment during insert on $parent->lastReturnCode
		if (is_object($action)) $this->parent->errorControl->raise(126);
		$this->parent->lastReturnCode = 0;
		unset($this->parent->storage['lastactiondata']);
		
		if (is_numeric($data)) {
			if ($action == CONS_ACTION_DELETE) {
				$id = $data;
				$data = array();
				$data[$this->keys[0]] = $id;
			} else {
				if (!$silent)
					$this->parent->errorControl->raise(187,$data,$this->name);
				return false;
			}
		}

		if (count($this->plugins) > 0 && ($action == CONS_ACTION_UPDATE || $action == CONS_ACTION_INCLUDE || $action == CONS_ACTION_DELETE)) {
			foreach ($this->plugins as $pname) {
				if (!$this->parent->loadedPlugins[$pname]->edit_parse($action, $data )) {
					if (!$silent)
						$this->parent->errorControl->raise(168,$pname,$this->name);
					return false;
				}
			}
		}

		if (!$mfo) {
	  		$missing = $this->check_mandatory($data,$action); # returns a list of mandatory fields missing or invalid
			if (count($missing)>0) {
				$this->parent->errorState = true;
				if (!$silent) {
					$this->parent->errorControl->raise(127,implode(",",$missing),$this->name);
				}
				return false;
			}
		}
		$EnumPrunecache = array();
		switch ($action) {
			
			case CONS_ACTION_UPDATE: ###################################################### UPDATE ############################################
				
				$wS = ""; # whereStruct
				$kA = array(); # keyArray
				$haveAllKeys = $this->getKeys($wS,$kA,$data); // is it ok not to have all keys?
				# security
				if ($this->parent->safety && $_SESSION[CONS_SESSION_ACCESS_LEVEL] < 100) {
					$Owner = $this->parent->authControl->checkOwner($this,$kA); // array with isOwner and isSameGroup
					$this->parent->lockPermissions(); # Load permissions to this, in case something changed
					if (!$this->parent->authControl->checkPermission($this,CONS_ACTION_UPDATE,$Owner,$data)) {
						$this->parent->errorControl->raise(151,'',$this->name);
						return false;
					}
				}
				$this->parent->notifyEvent($this,CONS_ACTION_UPDATE,$data,$startedAt,true); # early notify
				$sql = "UPDATE ".$this->dbname." SET ";
				$output = "";
				$outfield = false;
				foreach ($this->fields as $name => $field) {
					if ($this->parent->safety && isset($field[CONS_XML_RESTRICT]) && $_SESSION[CONS_SESSION_ACCESS_LEVEL] < $field[CONS_XML_RESTRICT] && !isset($field[CONS_XML_UPDATESTAMP])) {
						# safety is on and this is a restricted field, while the user trying to change it does not have enough level
						continue;
					}
					if ($name != $this->keys[0] && strpos($field[CONS_XML_SQL],"AUTO_INCREMENT") === false) { # cannot change main key or auto_increment ones
						$outfield = $this->sqlParameter(false,$data,$name,$field,$EnumPrunecache,false,$kA,$wS);
						if ($outfield !== false) $output .= $name."=".$outfield.",";
					} # if (not key)
				} #foreach
				unset($outfield);
				if ($output != "") {
					# removes end ,
					$output = substr($output,0,strlen($output)-1);
					$sql .= $output." WHERE ".$wS;
					if (!$this->parent->dbo->simpleQuery($sql,$this->parent->debugmode)) {
						$this->parent->errorState = true;
						$lastError = $this->parent->dbo->log[count($this->parent->dbo->log)-1];
						if (strpos(strtolower($lastError),"duplicate") === false) {
							if (!$silent) $this->parent->errorControl->raise(136,"",$this->name);
						} else if (!$silent) {
							$this->parent->errorControl->raise(137,"",$this->name);
						}
						return false;
					} else {
						$this->parent->notifyEvent($this,CONS_ACTION_UPDATE,$data,$startedAt); # later notify
						$this->parent->storage['lastactiondata'] = &$data;
					}
				} else {
					$this->parent->errorState = true;
					if (!$silent) $this->parent->errorControl->raise(138,"",$this->name);
					return false;
				}
				if (count($EnumPrunecache)!=0) $this->autoPrune($EnumPrunecache,$data);
				return true;
				break;

			case CONS_ACTION_INCLUDE: ###################################################### INCLUDE ############################################
				
				if ($this->parent->safety) { # checkPermission has this test but this is faster
					if ($this->parent->safety && $_SESSION[CONS_SESSION_ACCESS_LEVEL] < 100) {
						$this->parent->lockPermissions();
						if (!$this->parent->authControl->checkPermission($this,CONS_ACTION_INCLUDE,array(true,true,true,0))) {
							$this->parent->errorControl->raise(150,'',$this->name);
							return false; # cannot create even OWNED items
						}
					}
					# can create items
				}
				# if this module have multiple key fields, there is no auto_increment IF there is an id (id created automatically w/o AI)
				if (count($this->options[CONS_MODULE_MULTIKEYS])>0 && $this->keys[0] == "id") {
					$wheres = array();
					foreach ($this->options[CONS_MODULE_MULTIKEYS] as $field) {
						if ($field != "") {
							if (!isset($data[$field])) {
								# we need this parent data to create the id, but it's missing!
								$this->parent->errorState = true;
								if (!$silent)
									$this->parent->errorControl->raise(139,$field,$this->name);
								return false;
							}
							array_push($wheres,$field."=\"".$data[$field]."\"");
						}
					} # foreach
					$sql = "SELECT MAX(id) FROM ".$this->dbname.(count($wheres)!=0?" WHERE ".implode(" AND ",$wheres):"");
					$id = $this->parent->dbo->fetch($sql,$this->parent->debugmode);
					if (!$id) {
						# suposes it was empty
						$id = 1;
					} else
						$id++;
					$sql = "INSERT INTO ".$this->dbname." SET id='$id',";
					$data['id'] = $id;
				} else {
					$id = false;
					$sql = "INSERT INTO ".$this->dbname." SET ";
				}
				$output = "";
				$hasAuto = "";
				$outfield = false;

				foreach ($this->fields as $name => $field) {
					if ($this->parent->safety && isset($field[CONS_XML_RESTRICT]) && $_SESSION[CONS_SESSION_ACCESS_LEVEL] < $field[CONS_XML_RESTRICT]) {
						# safety is on and this is a restricted field, while the user trying to change it does not have enough level
						# however while ADDING a field that is mandatory, if it has no default you can add
						if (!isset($field[CONS_XML_MANDATORY]) || isset($field[CONS_XML_DEFAULT]))
							unset($data[$name]);
					}
					if (strpos(strtolower($field[CONS_XML_SQL]),"auto_increment") === false && !($this->keys[0] == "id" && $name == $this->keys[0] && count($this->options[CONS_MODULE_MULTIKEYS])>0 )) { # cannot change auto_increment or main key fields

						$outfield = $this->sqlParameter(true,$data,$name,$field,$EnumPrunecache);
						if ($outfield !== false) $output .= $name."=".$outfield.",";

						if ((!$outfield || !isset($data[$name]) || $data[$name] == '') && isset($field[CONS_XML_AUTOFILL]) && !isset($field[CONS_XML_DEFAULT])) {
							if (isset($data[$field[CONS_XML_AUTOFILL]])) {
								$data[$name] = $data[$field[CONS_XML_AUTOFILL]];
								// if the autofill field is HTML and this is NOT, remove HTML
								if ($field[CONS_XML_TIPO] == CONS_TIPO_TEXT && !isset($field[CONS_XML_HTML])) {
									if (isset($this->fields[$field[CONS_XML_AUTOFILL]][CONS_XML_HTML])) {
										$data[$name] = preg_replace("/(<)([^<>]*)(>)/","",$data[$name]);
									}
								}
								$output .= $name."=\"".$data[$name]."\",";
							}
						}
					} else # if (not AutoIncrement)
						$hasAuto = $name;
				} #foreach
				$id = 0;
				unset($outfield);
				if ($output != "") {
					# removes end ,
					$output = substr($output,0,strlen($output)-1);
					$sql .= $output;
					if (!$this->parent->dbo->simpleQuery($sql,$this->parent->debugmode)) {
						$this->parent->errorState = true;
						$lastError = $this->parent->dbo->log[count($this->parent->dbo->log)-1];
						if (strpos(strtolower($lastError),"duplicate") === false) {
							if (!$silent) $this->parent->errorControl->raise(140,$lastError,$this->name);
						} else if (!$silent) $this->parent->errorControl->raise(141,$lastError,$this->name);
						return false;
					} else { # post processing ...
						if ($this->keys[0] == "id") {
							$id = $this->parent->dbo->insert_id();
							if ($hasAuto != "") $data[$hasAuto] = $id;
							else $data['id'] = $id;
						}
						# check for uploads and urla
						$wS = ""; $kA = array();
						$this->getKeys($wS,$kA,$data);
						foreach ($this->fields as $name => $field) {
							if ($field[CONS_XML_TIPO] == CONS_TIPO_SERIALIZED) {
								foreach ($field[CONS_XML_SERIALIZEDMODEL] as $exname => $exfield) { #--- serialized uploads
									if ($exfield[CONS_XML_TIPO] == CONS_TIPO_UPLOAD) {
										$upOk = $this->prepareUpload($name."_".$exname,$kA,$data);
										if ($upOk != 4 && $upOk != 0) { // 4 = nothing sent, 0 = sent and ok
											# not mandatory but failed, warn about it but do not abort
											if (!$silent)
												$this->parent->errorControl->raise(200+$upOk,$upOk,$this->name,$name.'_'.$exname);
											//$this->deleteUploads($kA,$name."_".$exname,'',$name); // delete possible partial thumbnail process
										# so far, serialized uploads have no flag
										//} else if ($upOk == 0) {
										//	$this->parent->dbo->simpleQuery("UPDATE ".$this->dbname." SET $name='y' WHERE $wS");
										}
									}
								}
							}
							if ($field[CONS_XML_TIPO] == CONS_TIPO_UPLOAD) { #--- normal uploads
								$upOk = $this->prepareUpload($name,$kA,$data);
								if ($upOk != 0 && isset($field[CONS_XML_MANDATORY])) {
									# failed or didn't send upload but it's mandatory
									$this->parent->errorState = true;
									if (!$silent) $this->parent->errorControl->raise(200+$upOk,$upOk,$this->name,$name);
									# must remove inserted data!
									$this->parent->dbo->simpleQuery("DELETE FROM ".$this->dbname." WHERE ".$wS,$this->parent->debugmode);
									$this->deleteUploads($kA);
									return false;
								} else if ($upOk != 4 && $upOk != 0) { // 4 = nothing sent, 0 = sent and ok
									# not mandatory but failed, warn about it but do not abort
									if (!$silent) $this->parent->errorControl->raise(200+$upOk,$upOk,$this->name,$name);
									$this->deleteUploads($kA,$name); // delete possible partial thumbnail process
								} else if ($upOk == 0) {
									$this->parent->dbo->simpleQuery("UPDATE ".$this->dbname." SET $name='y' WHERE $wS");
								}
							} else if ($field[CONS_XML_TIPO] == CONS_TIPO_VC && isset($field[CONS_XML_SPECIAL]) && $field[CONS_XML_SPECIAL] == "urla" && (!isset($data[$name]) || $data[$name] == '')) {
								# EMPTY special VC urla might require the data to be fully processed to create the proper result, so we do it after the include
								$source = isset($field[CONS_XML_SOURCE])?$field[CONS_XML_SOURCE]:"{".$this->title."}";
								$tp = new CKTemplate($this->parent->template);
								$tp->tbreak($source);
								$urla = removeSimbols($tp->techo($data),true,false);
								if ($urla != '') {
									$this->parent->dbo->simpleQuery("UPDATE ".$this->dbname." SET $name=\"$urla\" WHERE $wS");
									$data[$name] = $urla;
								}
								unset($tp);
							}
						}
						$this->parent->lastReturnCode = $id;
						$this->parent->notifyEvent($this,CONS_ACTION_INCLUDE,$data,$startedAt,false); # later notify (there is no early notify for an include)
						$this->parent->lastReturnCode = $id; // notifyEvent could have changed/consumed lastReturnCode
						$this->parent->storage['lastactiondata'] = &$data;
						
					}
				} else {
					# null insert? error
					$this->parent->errorState = true;
					if (!$silent) $this->parent->errorControl->raise(142,"",$this->name);
					return false;
				}
				if (count($EnumPrunecache)!=0) $this->autoPrune($EnumPrunecache,$data);
				return true;
				break;

			case CONS_ACTION_DELETE: ###################################################### DELETE ############################################
				$wS = ""; $kA = array();
			
				$haveallKeys = $this->getKeys($wS,$kA,$data);
				# security
				$Owner = $this->parent->authControl->checkOwner($this,$kA); // array with isOwner and isSameGroup
				if ($this->parent->safety && $_SESSION[CONS_SESSION_ACCESS_LEVEL] < 100) {
					$this->parent->lockPermissions($this,$data,$Owner);
					if (!$this->parent->authControl->checkPermission($this,CONS_ACTION_DELETE,$Owner,$data)) {
						$this->parent->errorControl->raise(149,'',$this->name);
						return false;
					}
				}
				$this->parent->notifyEvent($this,CONS_ACTION_DELETE,$data,$startedAt,true); # early notify
				if ($this->parent->dbo->simpleQuery("DELETE FROM ".$this->dbname." WHERE ".$wS,$this->parent->debugmode)) {
					$this->deleteUploads($kA);
					$this->parent->notifyEvent($this,CONS_ACTION_DELETE,$data,$startedAt,false); # later notify
					return true;
				} else {
					$this->parent->errorState = true;
					if (!$silent)
						$this->parent->errorControl->raise(143,"",$this->name);
					return false;
				}
				break;
		} # switch
	} # run_action
#-
	function runContent(&$tp,$sql="",$tag="",$usePaging=false, $cacheTAG = false,$callback = false) {
		# callback function parameters: &template, &params, data AND returns data (if returns FALSE, will skip this item)
		# will return and set the $this->parent->lastReturnCode to a number (TOTAL possible, not just those listed) or the returned item (one item returned)
		# usePaging true will use 30 as default if no p_size comes in on templateParams, or $usePaging can be the actual page size number

		$this->parent->lastReturnCode = 0;
		$this->parent->lastFirstset = false;
		if (!isset($_REQUEST['nocache']) && $tag != "" && $cacheTAG !== false ) { # list and we have a possible cache. REMEMBER TO INCLUDE p_init and p_size ON THE TAG
			$cH = $this->parent->cacheControl->getCachedContent($cacheTAG);
			if ($cH !== false && is_array($cH)) { # cache present, show it
				$tp->assign($tag,$cH['payload']);
				$this->parent->lastFirstset = $cH['lfs'];
				$this->parent->lastReturnCode = $cH['lrc'];
				unset($this->parent->templateParams['reverse']);
				return $cH['count'];
			}
		}

		if ($callback === "") $callback = false; // common mistake
	  	if (!$this->loaded) { # get me started if not so
	  		$this->parent->loaded($this->name);
	  	}

	  	if ($tag == "") { # simple select
			$this->parent->templateParams['p_size'] = 1; # select only one (after testing if $sql is an array, will set LIMIT=1 to fast things up)
			$usePaging = false;
		}
	  	if (is_numeric($usePaging) && $usePaging > 0) { # enable pading if one is specified
	  		$this->parent->templateParams['p_size'] = $usePaging;
	  		$usePaging = true;
	  	}
	  	if ($usePaging) { # if enabled (was sent TRUE or with the page size), get start and end right.
	  		if (!isset($this->parent->templateParams['p_init']) || !is_numeric($this->parent->templateParams['p_init']) || $this->parent->templateParams['p_init']<0) {
				if (isset($_REQUEST['p_init']) && is_numeric($_REQUEST['p_init']) && $_REQUEST['p_init']>=0)
					$this->parent->templateParams['p_init'] = $_REQUEST['p_init'];
	  	  		else
	  	  			$this->parent->templateParams['p_init'] = 0;
	  	  	}
	  	  	if (!isset($this->parent->templateParams['p_size']) || !is_numeric($this->parent->templateParams['p_size']) || $this->parent->templateParams['p_size']<0) {
				if (isset($_REQUEST['p_size']) && is_numeric($_REQUEST['p_size']) && $_REQUEST['p_size']>=0)
					$this->parent->templateParams['p_size'] = $_REQUEST['p_size'];
				else
					$this->parent->templateParams['p_size'] = CONS_DEFAULT_PAGESIZE;
	  	  	}
	  	}
	  	$originalSQL = false;
		if (is_numeric($sql)) { # a number means just the ID
			$sql = $this->get_base_sql($this->name.".".$this->keys[0]."='$sql'");
			$originalSQL = true; // no change based on original SQL
		} else if ($sql == "") { # no sql? use the default
			$sql = $this->get_base_sql();
			$originalSQL = true; // no change based on original SQL
		} else if (is_array($sql) && count($sql)==3 && !isset($sql['SELECT'])) { # 3-part array (where, order, limit)
			$originalSQL = $sql[2] == ''; // no change based on original SQL only if no limit imposed
			# Consideration: also, if sql[2] have something, would void fast paging. But no problem, $originalSQL already does that
			$sql = $this->get_base_sql($sql[0],$sql[1],$sql[2]);
		} else if (!is_array($sql)) { # break into a sql array if came as a raw string
			$newsql = $this->parent->dbo->sqlarray_break($sql);
			if (count($newsql['SELECT'])==0 || count($newsql['FROM'])==0) {
				$this->parent->checkHackAttempt($sql);
				$this->parent->errorControl->raise(144,$sql,$this->name);
				unset($this->parent->templateParams['reverse']);
				return false;
			}
			$sql = $newsql;
		} else if (!isset($sql['FROM']) || !isset($sql['SELECT'])) {
			$this->parent->errorControl->raise(182,$sql,$this->name);
			return false;
		}
		if ($tag == '') $sql['LIMIT'] = array(1);
		$sql = $this->parent->authControl->forcePermissions($this,$sql);
		if ($sql === false) { # forcePermissions failed
			unset($this->parent->templateParams['reverse']);
			return false;
		}
		$this->parent->templateParams['core'] = &$this->parent;
		$this->parent->templateParams['module'] = &$this;
		if ($callback !== false) {
			if (!is_array($callback)) {
				$callback = array($callback);
			}
		} else
			$callback = array();
		if (!isset($this->parent->templateParams['noOutputParse']) || $this->parent->templateParams['noOutputParse'] !== true)
			array_unshift($callback,"prepareDataToOutput");
		$timeStart = getmicrotime();

		$obs = ob_get_length();
		if ($usePaging && ($originalSQL || (count($sql['FROM'])==1 && count($sql['LEFT'])==0 && count($sql['GROUP'])==0 && !(isset($sql['ORDER'][0]) && strpos(strtoupper($sql['ORDER'][0]),"RAND()") !== false))) ) {
			# faster PAGING if possible. Usefull for very large lists where we can AUTOMATICALLY make a LIMIT. For more complex lists which does not fall here, should send the limits manually
			# this is mostly because we should return the total number of entries so a paging can be worked out.

			$countSQL = $sql;
			$countSQL['SELECT'] = array("COUNT(*)");
			$countSQL['GROUP'] = array();
			$countSQL['ORDER'] = array();
			$total = $this->parent->dbo->fetch($countSQL,$this->parent->debugmode); # Count total items if not limited by paging

			if ($this->parent->templateParams['p_init'] != 0)
				$sql['LIMIT'][0] = $this->parent->templateParams['p_init'].",".$this->parent->templateParams['p_size'];
			else
				$sql['LIMIT'][0] = $this->parent->templateParams['p_size'];

			$this->parent->templateParams['p_init'] = 0;

			$ok = $this->parent->dbo->query($sql,$r,$n,$this->parent->debugmode);
			$endTime = getmicrotime();
			if (!$ok)
				 $this->parent->errorControl->raise(169,$tag,$this->name);
			$tp->fullpage($tag,$this->parent->dbo,$r,$n,$this->parent->templateParams,$callback);
			unset($this->parent->templateParams['reverse']);
			if ($tp->lastReturnedSet != false)
				$this->parent->lastReturnCode = $tp->lastReturnedSet;
			if ($tp->firstReturnedSet != false)
				$this->parent->lastFirstset = $tp->firstReturnedSet;
			$n = $total; #<-- will be used for paging display

  		} else {

			if (!$this->parent->dbo->query($sql,$r,$n,$this->parent->debugmode) && $this->parent->debugmode)
				$this->parent->errorControl->raise(146,$this->parent->dbo->log[count($this->parent->dbo->log)-1],$this->name);
			$endTime = getmicrotime();
			if (!$usePaging) $this->parent->templateParams['no_paging'] = true;
			if ($n != 0) {
				$tp->fullpage($tag,$this->parent->dbo,$r,$n,$this->parent->templateParams,$callback,CONS_MAXRUNCONTENTSIZE);
				unset($this->parent->templateParams['reverse']);
				if ($tp->lastReturnedSet != false)
					$this->parent->lastReturnCode = $tp->lastReturnedSet;
				if ($tp->firstReturnedSet != false)
					$this->parent->lastFirstset = $tp->firstReturnedSet;
			} else {
				$n = false;
				if ($tag != '') $tp->assign($tag);
			}
		}
		unset($this->parent->templateParams['reverse']);
		if ($obs !== false && $obs != ob_get_length())
			$this->parent->errorControl->raise(159,$tag,$this->name);

		$this->parent->templateParams = array();
		if ($endTime - $timeStart > CONS_SLOWQUERY_TH) {
			$this->parent->errorControl->raise(147,$endTime - $timeStart,$this->name,$this->parent->dbo->sqlarray_echo($sql));
		}
		if ($n!==false&&($n==1&&$tag=="")) $n = $this->parent->lastReturnCode;
		if ($cacheTAG !== false && $tag != '' && $n!==false) { # store cache
			$this->parent->cacheControl->addCachedContent($cacheTAG,array('payload'=>$tp->get($tag),'count'=>$n,'lfs' => $this->parent->lastFirstset, 'lrc' => $this->parent->lastReturnCode),true);
		}
		unset ($this->templateParans['grouping']);
		return $n;
	} # runContent

 	function notifyEvent(&$module,$action,$data,$startedAt="",$earlyNotify = false) {
 		# notifies this module about a change in another module
 		if (!$this->loaded) $this->parent->loadAllmodules(); # a notify will require all anyway
 		
 		foreach ($this->plugins as $pname) {
 			if ($this->parent->loadedPlugins[$pname]->moduleRelation == $this->name)
 				$this->parent->loadedPlugins[$pname]->notifyEvent($module,$action,$data,$startedAt,$earlyNotify); # script should also know about this
 		}

 		if (!$earlyNotify) { // the first notify comes before the action, thus we want AFTER the action, which means it was sucessful

	 		if ($action == CONS_ACTION_DELETE) {

	 			$wS = ""; # whereStruct
	 			$kA = array(); # keyArray
	 			$module->getKeys($wS,$kA,$data);
	 			$data = $kA; // we want only the keys of the module which called the notification

	 			if (isset($data[$module->keys[0]])) {
	 				# there is a key the same as one of mine. Try translate
	 				if ($this->moduleRelation != $module->name) {
	 					# not a notification of this exact module
	 					$key = $this->get_key_from($module->name,'id_'.$module->name); // how I link the module that changes?
	 					if ($key != "")
	 						$data[$key] = $data[$module->keys[0]];
	 					else
	 						return; # This module have nothing to do with that module
	 					unset($data[$module->keys[0]]); # prevent parent key from being present (we changed above)
	 				}
	 			} // at this point, $data is only MY keys that SHOULD point to the remote module

	 			$zerothem = true; # supposes one should ZERO (not delete) all modules which link to this

	 			// check if we have a link to that module
	 			$keys = 0;
	 			$lkey = $this->get_key_from($module,"id_".$module->name);

				foreach ($module->keys as $akey) {
					if ($akey != "id" && isset($this->fields[$akey])) {
	 					$keys++;
						if (isset($this->fields[$akey][CONS_XML_MANDATORY])) $zerothem = false; # cannot ZERO that field, so I must DELETE the whole item
					} else if ($akey == "id" && $lkey != '') {
	 					$keys++;
						if (isset($this->fields[$lkey][CONS_XML_MANDATORY])) $zerothem = false; # cannot ZERO that field, so I must DELETE the whole item
					}
				}

				if ($keys == count($module->keys)) { # I am linked to that field, so either zero or delete me
					$this->parent->deleteAllFrom($this,$data,$zerothem,$startedAt); # delete all my fields with this values (or zero them)
				}
				if ($module->name == $this->moduleRelation) { #one of my instances were deleted
					$this->deleteUploads($data);
				}
	 		}
 		}
	} #notifyEvent

	function generateBackup($echo=false) {
		$maxLine = 5000;
		$bck = CONS_PATH_BACKUP.$_SESSION['CODE']."/".$this->dbname.".sql";
		if (!is_dir(CONS_PATH_BACKUP.$_SESSION['CODE']."/")) makeDirs(CONS_PATH_BACKUP.$_SESSION['CODE']."/");
		if (is_file($bck)) @unlink($bck);
		$fd = fopen ($bck, "a");
		if ($fd) {
			$sql = "SELECT * FROM ".$this->dbname;
			$this->parent->dbo->query($sql,$r,$n);
			$baseLine = "INSERT INTO ".$this->dbname." (";
			foreach ($this->fields as $fn=>&$f)
				$baseLine .= $fn.",";
			$baseLine = substr($baseLine,0,strlen($baseLine)-1).") VALUES (";
			$line = $baseLine;
			for ($c=0;$c<$n;$c++) {
				$data = $this->parent->dbo->fetch_assoc($r);
				foreach ($this->fields as $fn=>&$f) {
					if ($f[CONS_XML_TIPO] == CONS_TIPO_INT || $f[CONS_XML_TIPO] == CONS_TIPO_FLOAT) // integer
						$line .= (is_numeric($data[$fn])?$data[$fn]:"NULL").",";
					else if ($f[CONS_XML_TIPO] == CONS_TIPO_DATE || $f[CONS_XML_TIPO] == CONS_TIPO_DATETIME) // dates
						$line .= ($data[$fn]!=''?$data[$fn]:"NULL").",";
					else if ($f[CONS_XML_TIPO] == CONS_TIPO_LINK) { // link, must get the link db type
						// TODO: format the output for null data as well?
						if ($this->parent->modules[$f[CONS_XML_MODULE]]->fields[$this->parent->modules[$f[CONS_XML_MODULE]]->keys[0]][CONS_XML_TIPO] == CONS_TIPO_INT)
							$line .= (is_numeric($data[$fn])?$data[$fn]:"NULL").",";
						else
							$line .= "\"".addslashes_EX($data[$fn],true)."\",";
					} else // not integer
						$line .= "\"".addslashes_EX($data[$fn],true)."\",";
				}
				$line = substr($line,0,strlen($line)-1).")"; // removes ,
				if (strlen($line)>$maxLine) {
					$line .= ";\n";
					fwrite($fd,$line);
					$line = $baseLine;
				} else
					$line .= ",(";
			}
			if ($line != $baseLine) {
				$line = substr($line,0,strlen($line)-2).";\n"; // removes ,(
				fwrite($fd,$line);
			}
			fclose($fd);
			if ($echo) {
				echo $line;
			}
		}
	}

}

