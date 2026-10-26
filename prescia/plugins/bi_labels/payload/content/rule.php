<?php

/** @var CPrescia $core Core context injected by mod_bi_labels::onShow(). */
	$core->layout = 1;
	if (isset($core->loadedPlugins['bi_dev'])) $core->loadedPlugins['bi_dev']->devDisable = true; // do not show developer plugin
