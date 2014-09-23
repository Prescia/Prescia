<?

	

	$fobj = $this->loaded('functions');
	$sql = "SELECT ".($_SESSION[CONS_SESSION_LANG]=='en'?'description':'descriptionpt as description').",holder, name, parameters, autocallorder, internals FROM ".$fobj->dbname." ORDER BY holder ASC, name ASC";
	
	
	$this->templateParams['grouping'] = 'holder:_functiontitle';
	$this->runContent('functions',$this->template,$sql,"_function");
	
	