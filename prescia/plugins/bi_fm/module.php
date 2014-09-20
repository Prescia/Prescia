<?	# -------------------------------- Safe FileManager


class mod_bi_fm extends CscriptedModule  {

	// -- internal variables:
	var $currentDir = false;
	var $cache = array();

	function __construct(&$parent,$moduleRelation="") {
		$this->parent = &$parent; // framework object
		$this->moduleRelation = $moduleRelation;
		$this->loadSettings();
	}

	function loadSettings() {
		$this->name = "bi_fm";
		$this->moduleRelation = "bi_fm";
		$this->parent->onMeta[] = $this->name;
		$this->parent->onActionCheck[] = $this->name;
		#$this->parent->onRender[] = $this->name;
		$this->parent->on404[] = $this->name;
		#$this->parent->onShow[] = $this->name;
		#$this->parent->onEcho[] = $this->name;
		$this->parent->onCron[] = $this->name;
		#$this->parent->registerTclass($this,'');
		$this->customFields = array('filenm','allowed_users');
		$this->customPermissions = array(
				'fmp_master' => 'fmp_master', // pode ver todos os arquivos, independente de permissão
				'change_fmp' => 'change_fmp'); // pode editar permissão
	}


	function onMeta() {
		# Run this function during meta-load (debugmode >>ONLY<<)
		###### -> Construct should add this module to the onMeta array
		if (!is_dir(CONS_FMANAGER.CONS_FMANAGER_SAFE)) safe_mkdir(CONS_FMANAGER.CONS_FMANAGER_SAFE);
		if (!isset($this->parent->dimconfig['default_fm_time']))
			$this->parent->dimconfig['default_fm_time'] = 30; // default expiration date, set 0 to none

	}

	function on404($action, $context = "") {
		if ($this->parent->context_str == $this->admFolder && is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html")) {
			if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<$this->admRestrictionLevel)
				$this->parent->fastClose(403);
			else {
				return CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html";
			}
		}
		return false;
	}

	function onCheckActions() {
		$core = &$this->parent; // php 5.4 namespaces could come in handy now -_-

		if ($this->parent->context_str == $this->admFolder && is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/".$this->parent->action.".php")) {
			include CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/".$this->parent->action.".php";
			return;
		}

		if ($this->isInsideSafe($this->parent->original_context_str) && $this->parent->action != '404') {
			$arquivo = $this->parent->original_action;
			$arquivo_path = $this->parent->original_context_str.$arquivo;
			$arquivo_path = substr($arquivo_path,1);
			if ($ext == "") {
				$ext = explode(".",$file);
				$ext = array_pop($ext);
			}
			$ext = strtolower($ext);
			$compareFile = substr($arquivo_path,strlen(CONS_FMANAGER.CONS_FMANAGER_SAFE."/"));
			//--
			$canDownload = $this->canSee($compareFile);
			$downloadAs = $arquivo;

			if ($canDownload) {
				$this->parent->readfile($arquivo_path,$ext,true,$downloadAs,true);
				$this->parent->close();
			} else
				$this->parent->action = '403';

		}


	}

	function onCron($isDay=false) { # cron Triggered, isDay or isHour
		###### -> Construct should add this module to the onCron array
		if ($isDay) {
			$mod = $this->parent->loaded($this->moduleRelation);
			$sql = "SELECT filenm FROM ".$mod->dbname." WHERE has_expiration='y' AND expiration_date <> '0000-00-00' AND expiration_date < NOW()";
			if ($this->parent->dbo->query($sql,$r,$n) && $n>0) {
				// clean up files which reached expiration
				for ($c=0;$c<$n;$c++) {
					list($filenm) = $this->parent->dbo->fetch_row($r);
					$file = CONS_FMANAGER.CONS_FMANAGER_SAFE."/".$filenm;
					@unlink($file); // bye bye
				}
				$this->parent->errorControl->raise(526,$n,'bi_fm');
			}
			$sql = "DELETE FROM ".$mod->dbname." WHERE has_expiration='y' AND expiration_date <> '0000-00-00' AND expiration_date < NOW()";
			$this->parent->dbo->simpleQuery($sql);
		}
	}

	function devCheck() {
		# implement this to raise errors during meta-developer checks if the plugins is not properly installed or configured
	}

	function isInsideSafe($dir) { // should be full path
		if ($dir[0] != '/') $dir = "/".$dir;
		if ($dir[strlen($dir)-1] != '/') $dir .= "/";
		return substr($dir,0,strlen("/".CONS_FMANAGER.CONS_FMANAGER_SAFE.'/')) == "/".CONS_FMANAGER.CONS_FMANAGER_SAFE."/";
	}

	function notifyEvent(&$module,$action,$data,$startedAt="",$earlyNotify = false) {
		if ($module === false && $action == "fmanager_upload") {
			// if this is a safe upload, apply user permission
			$arquivo = $data;
			$core = &$this->parent;
			$mod = $this->parent->loaded($this->moduleRelation);
			if ($arquivo[0]!='/') $arquivo = "/".$arquivo;
			if (substr($arquivo,0,strlen("/".CONS_FMANAGER.CONS_FMANAGER_SAFE."/")) == "/".CONS_FMANAGER.CONS_FMANAGER_SAFE."/"){
				// yes, it is
				$arquivo = substr($arquivo,strlen(CONS_FMANAGER.CONS_FMANAGER_SAFE)+2); // +2 for the /.../
				$data = array(
					'filenm' => $arquivo,
					'has_expiration' => $core->dimconfig['default_fm_time'] == 0 ? 'n' : 'y',
					'id_allowed_group' => '0',
					'allowed_users' => $_SESSION[CONS_SESSION_ACCESS_USER]['id'],
					'expiration_date' => '0000-00-00',
					);
				if ($core->dimconfig['default_fm_time'] != '0') {
					$today = date("Y-m-d");
					$today = datecalc($today,0,0,$core->dimconfig['default_fm_time']);
					$data['expiration_date'] = $today;
				}
				$buffer = $core->safety;
				$core->safety = false;

				$sql = "SELECT filenm FROM ".$mod->dbname." WHERE filenm LIKE \"".$arquivo."\"";
				$hasData = $core->dbo->fetch($sql) !== false;
				if ($hasData)
					$core->runAction($mod,CONS_ACTION_UPDATE,$data);
				else
					$core->runAction($mod,CONS_ACTION_INCLUDE,$data);

				$core->safety = $buffer;
			}
		} else if ($module === false && $action == "fmanager_delete") {
			$arquivo = $data;
			$core = &$this->parent;
			$mod = $this->parent->loaded($this->moduleRelation);
			if ($arquivo[0]!='/') $arquivo = "/".$arquivo;
			if (substr($arquivo,0,strlen("/".CONS_FMANAGER.CONS_FMANAGER_SAFE."/")) == "/".CONS_FMANAGER.CONS_FMANAGER_SAFE."/") {
				$arquivo = substr($arquivo,strlen(CONS_FMANAGER.CONS_FMANAGER_SAFE)+2); // +2 for the /.../
				$sql = "DELETE FROM ".$mod->dbname." WHERE filenm LIKE \"".$arquivo."\"";
				$buffer = $core->safety;
				$core->safety = false;
				$core->dbo->simpleQuery($sql);
				$core->safety = $buffer;
			}
		}

	}

	function getPermissions($file) {
		if (strpos($file,CONS_FMANAGER_SAFE."/") !== false)
			$file = substr($file,strlen(CONS_FMANAGER_SAFE)+1); // removes SAFE+/
		if (!isset($this->cache[$file]) || $this->currentDir === false) {
			// test manually in the database, if no cache available
			$mod = $this->parent->loaded('bi_fm');
			$sql = "SELECT id_allowed_group,allowed_users,has_expiration,expiration_date FROM ".$mod->dbname." WHERE filenm LIKE \"".$file."\"";
			if ($this->parent->dbo->query($sql,$r,$n) && $n == 1) {
				list($idG,$idU,$heD,$eD) = $this->parent->dbo->fetch_row($r);
				$idU = explode(",",$idU);
				$idUclean = array();
				foreach ($idU as $idUitem) // removes invalid/null items
					if ($idUitem != '' && $idUitem != 0) $idUclean[] = $idUitem;
				$this->cache[$file] = array($idG,$idUclean,$heD=='y'?$eD:'');
			} else
				return array(-1,array(),'');
		}
		return $this->cache[$file];
	}

	function canSee($file) {
		if ($_SESSION[CONS_SESSION_ACCESS_LEVEL] == 100) return true; // master can see all
		if ($this->parent->authControl->checkPermission('bi_fm','fmp_master')) return true; // as per configuration, this is a master user
		if ($file == '' || $file == '/') return true; // everyone can see the root
		if (strpos($file,CONS_FMANAGER_SAFE."/") !== false)
			$file = substr($file,strlen(CONS_FMANAGER_SAFE)+1); // removes SAFE+/

		$p = $this->getPermissions($file);
		if ($p[0]==-1) return false; // not allowed at all (missing data in database?)
		$idG = $p[0];
		$idU = $p[1];
		if ($idG != 0) return ($idG == $_SESSION[CONS_SESSION_ACCESS_USER]['id_group']);
		else return (in_array($_SESSION[CONS_SESSION_ACCESS_USER]['id'],$idU));

	}

	function cachePermissions($dir) {
		if ($this->currentDir == $dir) return; // already cached
		$this->currentDir = $dir;
		$this->cache = array();

		$dir = substr($dir,strlen(CONS_FMANAGER_SAFE)+1); // removes SAFE+ /

		$mod = $this->parent->loaded('bi_fm');
		$sql = "SELECT id_allowed_group,allowed_users,has_expiration,expiration_date,filenm FROM ".$mod->dbname." WHERE filenm LIKE \"".$dir."%\"";
		if ($this->parent->dbo->query($sql,$r,$n)) {
			for ($c=0;$c<$n;$c++) {
				list($idG,$idU,$heD,$eD,$fln) = $this->parent->dbo->fetch_row($r);
				$idU = explode(",",$idU);
				$idUclean = array();
				foreach ($idU as $idUitem) // removes invalid/null items
					if ($idUitem != '' && $idUitem != 0) $idUclean[] = $idUitem;
				$this->cache[$fln] = array($idG,$idUclean,$heD == 'y' ? $eD : '');
			}
		}
	}
}

