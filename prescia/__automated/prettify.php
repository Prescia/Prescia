<?  # -------------------------------- Prettify automato
	# Will add CSS, JS and call to Google Prettify
	# USAGE: <PRETTIFY>true</PRETTIFY>
	# Then, add class="prettyprint" on whatever you want to Prettify
	
class auto_prettify extends CautomatedModule  {

	function loadSettings() {
		$this->name = "prettify"; 
		$this->sorting_weight = 0; 
		//$this->accepts_multiple = false;
	}

	function onShow($definitions){
		# adds code for prettify
		$this->parent->addLink("prettify/prettify.js");
		$this->parent->addLink("prettify/prettify.css");
		$this->parent->template->constants['HEADUSERTAGS'] .= "\n<script type=\"text/javascript\">\naddEventListener('load', function (event) { prettyPrint() }, false);\n</script>";		
	}
	
}
