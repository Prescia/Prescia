<?php

/** @var CPrescia $core Runtime payload context injected by the framework. */
/** @var mod_bi_adm $this Runtime module context injected by the framework. */

		$core->layout = 2;
	if (CONS_ONSERVER && (is_file("maint.txt") || is_file("heavymaint.html"))) echo "n";
	else echo "y";

	$core->close();
