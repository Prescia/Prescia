<?php

/** @var CPrescia $core Runtime payload context injected by the framework. */

	include_once CONS_PATH_SYSTEM."console.php";

	console($core,$_REQUEST['q']);
