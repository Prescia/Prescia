<?  # -------------------------------- Flood Control
	# Prevents a page from being loaded multiple times in short period of time
	# USAGE: <FLOODCONTROL>[time in seconds]</FLOODCONTROL>
	# IMPORTANT: After been redirected to _floodcontrol.html, this will no longer run since the context changed, thus onShow will never run here

class auto_floodcontrol extends CautomatedModule  {


	function loadSettings() {
		$this->name = "floodcontrol";
		$this->sorting_weight = 3;
		//$this->accepts_multiple = false;
	}

	function onMeta(&$PS,$data,$exceptions,$layout,$context) {
		parent::onMeta($PS,$data,$exceptions,$layout,$context);
		if (!is_file(CONS_PATH_PAGES.$_SESSION['CODE']."/template/_floodcontrol.html")) {
			copy(CONS_PATH_SETTINGS."defaults/_floodcontrol.html", CONS_PATH_PAGES.$_SESSION['CODE']."/template/_floodcontrol.html");
		}
	}

	function onCheckActions($definitions) {
		$time = (int)$definitions[CONS_XMLPS_DEF];
		$page = md5($this->parent->context_str.$this->parent->action);
		if (!isset($_SESSION['floodControl']))
			$_SESSION['floodControl'] = array();
		if (!isset($_SESSION['floodControl'][$page])) { // never been here
			$_SESSION['floodControl'][$page] = date("Y-m-d H:i:s");
			return false;
		} else { // been here
			$lastTime = $_SESSION['floodControl'][$page];
			$currentTime = date("Y-m-d H:i:s");
			$timePassed = time_diff($currentTime,$lastTime);
			if ($timePassed < $time) { // in less than [time] seconds
				$this->parent->action = "_floodcontrol";
				$this->parent->context_str = "/";
				$this->parent->context = array("");
				return true;
			} else { // in more than [time] seconds
				$_SESSION['floodControl'][$page] = date("Y-m-d H:i:s");
				return false;
			}
		}
	}
}
