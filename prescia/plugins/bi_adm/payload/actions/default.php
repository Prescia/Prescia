<?php

/** @var CPrescia $core Runtime payload context injected by the framework. */
/** @var mod_bi_adm $this Runtime module context injected by the framework. */

	$core->loadAllmodules();
	# Basic safety checks (redirect to login on module's checkAction, here for extra safety)
	if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<$this->admRestrictionLevel) $this->parent->action = "login";
	if ($core->action == "login" && $_SESSION[CONS_SESSION_ACCESS_LEVEL]>=$this->admRestrictionLevel) { # logged, so redirect login to index
		$up = $_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'];
		if (!is_array($up)) $up = presciaSafeUnserialize($up);
		if (is_array($up)) {
			$core->action = isset($core->modules[$up['init']])?"list":$up['init'];
			if (isset($core->modules[$up['init']])) $_REQUEST['module'] = $up['init'];
		} else
			$core->action = 'index';
		$core->storage['up'] = $up;
	}



?>