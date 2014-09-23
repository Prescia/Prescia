<?
	// basic frameset
	$this->frame("basefile.html:BASEFILE_CONTENT","frame.html:FRAME_CONTENT");

	// HTML includes for prototype/scriptaculous
	#$this->addLink('prototype.js');
	#$this->addLink('scriptaculous/scriptaculous.js?load=effects');

	// HTML includes just for prototype OO part
	#$this->addLink('prototype_oop.js');

	// HTML includes just for prototype OO and Ajax part
	#$this->addLink('prototype_ajax.js');

	$this->addLink('css/prescia.css');

	// some basic javascript functions
	$this->addLink('common.js');

	// bootstrap?
	$this->addScript('bootstrap');

	// Loads template for current page
	$this->loadTemplate();

	// Note, you should treat $this->virtualFolder here or in the page, or the system will 404