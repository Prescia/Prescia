<?  # -------------------------------- /calendar automato
	# Will add CSS, JS and some parameters for a calendar box
	# USAGE: <CALENDAR>imgPath</CALENDAR>
	# Common usage: <CALENDAR>/pages/_js/calendar/gifs</CALENDAR>
	# To default img path, just use <CALENDAR>true</CALENDAR>
	
class auto_calendar extends CautomatedModule  {

	function loadSettings() {
		$this->name = "calendar";
		$this->sorting_weight = 0; // last, if we still need it at all
		//$this->accepts_multiple = false;
	}

	function onShow($definitions){
		
		$this->parent->addLink("calendar/dyncalendar.css");
		$this->parent->addLink("calendar/dyncalendar.js");
		$this->parent->template->constants['HEADUSERTAGS'] .= "\n<script type=\"text/javascript\"><!--\ncalendarHandler = new dynCalendar(".($definitions[0]!='true'?'"'.$definitions[0].'"':'').");\n//--></script>";

	}
	
	

}
