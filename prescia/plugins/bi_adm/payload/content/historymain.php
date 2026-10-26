<?php

/** @var CPrescia $core Runtime payload context injected by the framework. */

	if (!$core->authControl->checkPermission('bi_adm','can_undo'))
		$core->fastClose(403);


	$uM = $core->loaded('bi_undo');
	$core->runContent($uM,$core->template,"","_events");
