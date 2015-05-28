<? /* -------------------------------------------------
 * content/default.php
 * This file runs AFTER the actions/ are done, also AFTER the template is created, but nothing is in it at this point.
 * So, mandatory commands here are at least a $this->loadTemplate();
 * We also suggest a $this->frame([...]) to set up the page structure in a more organized way. 
 * Remember, the template will only be available after $this->loadTemplate(), but parts that are loaded in the frame can be accessed after $this->frame
 * 
 * DO NOT use content/ to run actions that might change how your page will be displayed or result in an error, do that in the actions/ so you can redirect to a proper land page
 * 
 * 
 * If you want this to run for sub-folders, just call this from them (like include CONS_PATH_PAGES.$_SESSION['CODE']."/content/default.php")
 * 
 * 
 * Yes you can remove all this comments when you are done
*/
 
	// basic frameset
	$this->frame("basefile.html:BASEFILE_CONTENT","frame.html:FRAME_CONTENT"); // YOUR content will be put in {FRAME_CONTENT} of frame.html, that is put in {BASEFILE_CONTENT} of basefile.html

	// HTML includes for prototype/scriptaculous?
	#$this->addLink('prototype.js');
	#$this->addLink('scriptaculous/scriptaculous.js?load=effects');

	// HTML includes just for prototype OO part?
	#$this->addLink('prototype_oop.js');

	// HTML includes just for prototype Ajax part (requires the OO part above)?
	#$this->addLink('prototype_ajax.js');

	// Sample to load a style for mobile (layout 3) and others
	#if ($this->layout != 3)
	#	$this->addLink('css/prescia.css');
	#else
	#	$this->addLink('css/prescia_mob.css');

	// some basic javascript functions
	#$this->addLink('common.js');

	// bootstrap?
	#$this->addScript('bootstrap');

	// jQuery?
	#$this->addLink('jquery.js'); // will serve latest installed version

	// Loads template for current page
	$this->loadTemplate();
	
	// your code for changing the template comes after this. HERE only for stuff that must run ALWAYS. 
	// For code that runs in, say, "mypage.html", create "mypage.php" in contents/ and run your code from there