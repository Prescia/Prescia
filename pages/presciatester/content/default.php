<?
	// basic frameset
	$this->frame("basefile.html:BASEFILE_CONTENT","frame.html:FRAME_CONTENT");

	// HTML includes for prototype/scriptaculous
	$this->addLink('prototype.js');
	$this->addLink('scriptaculous/scriptaculous.js?load=effects');

	// HTML includes just for prototype OO part
	#$this->addLink('prototype_oop.js');

	// HTML includes just for prototype OO and Ajax part
	#$this->addLink('prototype_ajax.js');

	// Sample to load a style for mobile (layout 3) and others
	#if ($this->layout != 3)
	#	$this->addLink('css/prescia.css');
	#else
	#	$this->addLink('css/prescia_mob.css');

	// some basic javascript functions
	#$this->addLink('common.js');

	// bootstrap?
	$this->addScript('bootstrap');

	// jQuery?
	#$this->addLink('jquery.js'); // will serve latest installed version

	// Loads template for current page
	$this->loadTemplate();

	if ($this->action != 'index') $this->template->assign("_onlyonindex");