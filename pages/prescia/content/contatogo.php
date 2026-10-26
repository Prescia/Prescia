<?php

/** @var CPrescia $this Page content context injected by CPrescia::renderPage(). */
		$this->template->assign("catchdebug",implode("<br/>",$this->log));
	$this->log = array();
