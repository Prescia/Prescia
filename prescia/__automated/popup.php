<?	/* -------------------------------- AFF Popup automato
	Displays a popup ONE TIME
	USE: <POPUP>page (full URL please)|popup parameters</POPUP>
	EXAMPLE: <POPUP>popup.jpg|height=947,width=1254,status=no,resizable= no, scrollbars=no, toolbar=no,location=no,menubar=no</POPUP>
    */

class auto_popup extends CautomatedModule  {

	var $parent = null; // framework object

	# nested behaviours are for automatos which apply on a FOLDER, and define if this is being defined for all files on that folder, or even nested folders/files
	var $nested_folders = false; // EXISTING nested folders will also receive the automato
	var $nested_files = true; // EXISTING files will also receive the automato
	var $virtual_folders = true; // Run on virtual folders (files which does not exist - but are resolved by another automato such as friendly url)
	var $sorting_weight = 1; // keep it 0-3, the bigger value, earlier it's called

	function __construct(&$parent) {
		$this->parent = &$parent; // framework object
		$this->loadSettings();
	}

	function loadSettings() {
		$this->name = "popup";
	}

	function onShow($definitions){
		$def = explode("|",$definitions[0]);
		$url = $def[0];
		$params =  (isset($def[1])?$def[1]:"");
		if (!isset($_SESSION['popup_'.str_replace("/","_",$this->parent->context_str)])) {
			$this->parent->template->constants['HEADUSERTAGS'] .= "\n<script type=\"text/javascript\"><!--\nwindow.open(\"$url\",\"popup\",\"$params\");\n//--></script>";
			$_SESSION['popup_'.str_replace("/","_",$this->parent->context_str)] = true;
		}
	}

	function devCheck() {
	}

}
