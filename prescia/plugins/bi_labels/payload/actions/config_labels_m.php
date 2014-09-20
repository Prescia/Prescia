<?

	$core->layout = 2;
	if (!isset($_REQUEST['m'])) $core->fastClose(404);
	$module = $_REQUEST['m'];
	$module = $core->loaded($module);
	if (!$module) $core->fastClose(404);
	
	$output = "";
	foreach ($module->fields as $fname => $field) {
		if ($field[CONS_XML_TIPO] != CONS_TIPO_OPTIONS && $field[CONS_XML_TIPO] != CONS_TIPO_UPLOAD) {
			if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK) {
				$rmodule = $core->loaded($field[CONS_XML_MODULE]);
				$output .= "{".substr($fname,3)."_".$rmodule->title."} ";
			} else if ($fname != 'id')
				$output .= "{".$fname."} ";
		}
	}
	echo $output;
	$core->close();