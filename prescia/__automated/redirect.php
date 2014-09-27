<?	# -------------------------------- Aff redirect class

class auto_redirect extends CautomatedModule  {

	# Will redirect any call to this folder or page to another page using the HTTP code specified
	# USAGE: <REDIRECT>/folder[,new filename OR "KEEP"[,HTTP redirect code]]</REDIRECT>
	# default filename is KEEP (will not change)
	# default HTTP redirect code is 200
	# 301: moved permanently
	# 302 or 307: temporarily moved (prefer 307) 
	# >>>>>>>>>>>>>> IMPORTANT NOTE: DOES NOT WORK FOR SUB-FOLDERS <<<<<<<<<<<<<<<<<<<

	function __construct(&$parent) {
		$this->parent = &$parent; // framework object
		$this->loadSettings();
	}

	function loadSettings() {
		$this->name = "redirect";
		$this->sorting_weight = 3;
	}


	function onCheckActions($definitions) {
		$params = explode(",",$definitions[0]);
		if ($params[0] != "/" && $params[0][strlen($params[0])-1] == "/")
			$params[0] = substr($params[0],strlen($params[0])-1); // should not end with /
		if (!isset($params[1])) $params[1] = "KEEP";
		if (!isset($params[2])) $params[2] = 200;
		if ($params[2] == 200) { // sweet change
			$this->parent->context_str = $params[0];
			$this->parent->context = explode("/",$params[0]);
			if ($params[1] != "KEEP")
				$this->parent->action = $params[1];
		} else {
			$qs = arrayToString();
			$this->parent->headerControl->internalFoward($params[0].($params[0]!="/"?"/":"").($params[1] != "KEEP"?$params[1]:$this->parent->original_action)."?".$qs,$params[2]);
			$this->parent->fastClose(404);
		}
	}
	
	function onRender($definitions) {
	}
	
	function onShow($definitions){
	}
	
}