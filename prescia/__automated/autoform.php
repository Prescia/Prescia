<?	# -------------------------------- Autoform will fill up the javascript required for the autoform.js to work
	# USAGE:
	# <AUTOFORM>
	#	Mandatory:
	#	<FORM>form NAME which will be validated</FORM>
	#	<WARNINGCLASS>css class which is used to show the warning DIV's over the fields when something is wrong</WARNINGCLASS>
	#	<TITLE>i18n term to be translated for the POPUP TITLE (sample: warning)</TITLE>
	#	Optional:
	#	<YOFFSET>Numeric value for the Y offset the DIV should appear ABOVE the field</YOFFSET>
	#	<XOFFSET>Numeric value for the X offset the DIV should appear RIGHT to the field</YOFFSET>
	#	<POSITION> absolute|fixed, where to put the divs with message (default is absolute) </POSITION>
	#	<MANDATORY>comma separated list of field names that are mandatory</MANDATORY>
	#	<TRANSLATION>comma separated list i18n terms that translate the MANDATORY fields (what is shown inside the warning div)</TRANSLATION>
	#	<DEFAULTS>comma separated list of default values for the MANDATORY fields (a.k.a. this values will be considered NOT FILLED)</DEFAULTS>
	#	<INTEGER>comma separated list of field names that are numeric integer values</INTEGER>
	#	<FLOAT>comma separated list of field names that are numeric floats (accept . and ,)
	# 	<IS_ID>comma separated list of field names that are Brazilian ID's (CPF or CNPJ)</IS_ID>
	#	<MAIL>comma separated list of field names that are E-mail</MAIL>
	#	<DATE>comma separated list of field names that are dates</DATE>
	#	<DATETIME>comma separated list of field names that are date times (time then date)</DATE>
	#	<TIME>comma separated list of field names that are times (h:m[:s])</TIME>
	#	<DATEFORMAT>preg for date, default is (([0-9]{1,2})([^0-9])){2}([0-9]{2,4})</DATEFORMAT>
	#	<CLASSOK>class to switch inputs if the field type is ok</CLASSOK>
	#	<CLASSFAIL>class to switch inputs if the field type is not ok</CLASSFAIL>
	#	<CALLBACK>function name that will also be called on form submission (prior to main validation)</CALLBACK>
	# </AUTOFORM>
	#
	# NOTE: the number of items in MANDATORY, TRANSLATION and DEFAULTS must be equal
	#	because they are related to each other: the 2nd TRANSLATION reffers to the 2nd MANDATORY and so forth
	#
	# IMPORTANT: the warning divs are positioned inside the BODY. The input fields SHOULD be positioned relative/absolute or position detection might fail and the warning div might show up in some random weird location
	# .warning_class { background: #440000; color: #ffffff; z-index: 500; font-weight:bold; line-height:20px;height:25px; min-width:200px; padding-left:4px; text-align:left }

class auto_autoform extends CautomatedModule  {

	function loadSettings() {
		$this->name = "autoform";
		$this->nested_folders = true;
		$this->nested_files = true;
		$this->nested_overright = false;
		$this->virtual_folders = true;
		$this->accepts_multiple = true;
		$this->sorting_weight = 0; // last so other automatos can add the form, if necessary
	}


	function onShow($definitions) {
		# main functional preparation and check
		$defcount = 0;
		if (!$this->parent->addLink("common.js")) $this->parent->errorControl->raise(402,'autoformautomato',"","common.js failed to be linked");
		if (!$this->parent->addLink("validators.js")) $this->parent->errorControl->raise(402,'autoformautomato',"","validators.js failed to be linked");
		if (!$this->parent->addLink("autoform/autoform.js")) $this->parent->errorControl->raise(402,'autoformautomato',"","autoform.js failed to be linked");
		foreach ($definitions as $fs) {
			if (count($fs)>0 && ($fs[CONS_XMLPS_LAYOUT] == $this->parent->layout || $fs[CONS_XMLPS_LAYOUT] == -1)) { # this layout or ALL layouts
				$definition = $fs[CONS_XMLPS_DEF];
				$defcount++;
				if (!isset($definition['form'])) $this->parent->errorControl->raise(402,'autoformautomato',"","Form not specified ($defcount)");
				if (!isset($definition['warningclass'])) $this->parent->errorControl->raise(402,'autoformautomato',"","Warningclass not specified ($defcount)");
				if (!isset($definition['yoffset'])) $definition['yoffset'] = "10";
				if (!isset($definition['xoffset'])) $definition['xoffset'] = "0";
				if (!isset($definition['position'])) $definition['position'] = "absolute";
				if (!isset($definition['title'])) $this->parent->errorControl->raise(402,'autoformautomato',"","title not specified ($defcount)");
				$mandatory = isset($definition['mandatory'])?explode(",",$definition['mandatory']):false;
				$translation = isset($definition['translation'])?explode(",",$definition['translation']):false;
				$defaults = isset($definition['defaults'])?explode(",",$definition['defaults']):false;
				$integer = isset($definition['integer'])?explode(",",$definition['integer']):false;
				$float = isset($definition['float'])?explode(",",$definition['float']):false;
				$is_id = isset($definition['is_id'])?explode(",",$definition['is_id']):false;
				$mail = isset($definition['mail'])?explode(",",$definition['mail']):false;
				$date = isset($definition['date'])?explode(",",$definition['date']):false;
				$datetime = isset($definition['datetime'])?explode(",",$definition['datetime']):false;
				$time = isset($definition['time'])?explode(",",$definition['time']):false;
				$dateformat = isset($definition['dateformat'])?$definition['dateformat']:"(([0-9]{1,2})([^0-9])){2}([0-9]{2,4})";
				$classOK = isset($definition['classok'])?$definition['classok']:"";
				$classFAIL = isset($definition['classfail'])?$definition['classfail']:"";
				$callback = isset($definition['callback'])?$definition['callback']:"";
				$js = "Autoform$defcount = new CAutoform('".$definition['form']."',{\n";
				if (count($mandatory) != count($translation))
					$translation = false;
				if (count($mandatory) != count($translation))
					$translation = false;
				if ($mandatory !== false) {
					$js .= "mandatory: [";
					foreach ($mandatory as $item)
						$js .= "'$item',";
					$js = substr($js,0,strlen($js)-1); // removes last ,
					$js .= "],";
				}
				if ($translation !== false) {
					$js .= "translation: [";
					foreach ($translation as $item)
						$js .= "\"".$this->parent->langOut($item)."\",";
					$js = substr($js,0,strlen($js)-1); // removes last ,
					$js .= "],";
				}
				if ($defaults !== false) {
					$js .= "defaults: [";
					foreach ($defaults as $item)
						$js .= "'$item',";
					$js = substr($js,0,strlen($js)-1); // removes last ,
					$js .= "],";
				}
				if ($integer !== false) {
					$js .= "integer: [";
					foreach ($integer as $item)
						$js .= "'$item',";
					$js = substr($js,0,strlen($js)-1); // removes last ,
					$js .= "],";
				}
				if ($float !== false) {
					$js .= "float: [";
					foreach ($float as $item)
						$js .= "'$item',";
					$js = substr($js,0,strlen($js)-1); // removes last ,
					$js .= "],";
				}
				if ($is_id !== false) {
					$js .= "is_id: [";
					foreach ($is_id as $item)
						$js .= "'$item',";
					$js = substr($js,0,strlen($js)-1); // removes last ,
					$js .= "],";
				}
				if ($mail !== false) {
					$js .= "mail: [";
					foreach ($mail as $item)
						$js .= "'$item',";
					$js = substr($js,0,strlen($js)-1); // removes last ,
					$js .= "],";
				}
				if ($date !== false) {
					$js .= "date: [";
					foreach ($date as $item)
						$js .= "'$item',";
					$js = substr($js,0,strlen($js)-1); // removes last ,
					$js .= "],";
				}
				if ($datetime !== false) {
					$js .= "datetime: [";
					foreach ($datetime as $item)
						$js .= "'$item',";
					$js = substr($js,0,strlen($js)-1); // removes last ,
					$js .= "],";
				}
				if ($time !== false) {
					$js .= "time: [";
					foreach ($time as $item)
						$js .= "'$item',";
					$js = substr($js,0,strlen($js)-1); // removes last ,
					$js .= "],";
				}
				if ($js[strlen($js)-1] == ",")
					$js = substr($js,0,strlen($js)-1); // removes last ,
				$js .= "\n},'".$definition['warningclass']."',".
							   $definition['yoffset'].",".
							   $definition['xoffset'].",'".
							   $definition['position']."',".
							   "\"".$this->parent->langOut($definition['title'])."\",".
							   "'$dateformat','$classOK','$classFAIL','$callback',".($this->parent->debugmode?"true":"false").");\n";
				$this->parent->template->constants['HEADUSERTAGS'] .= "\n<script type=\"text/javascript\"><!--\n$js//--></script>";
			}
		}
	}

}
