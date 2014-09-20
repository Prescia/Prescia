<?

	$core->layout = 1;
	if (isset($core->loadedPlugins['bi_dev'])) $core->loadedPlugins['bi_dev']->devDisable = true; // do not show developer plugin
