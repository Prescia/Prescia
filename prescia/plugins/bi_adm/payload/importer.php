<?

class CImporter {

	var $parent = null;

	function __construct(&$parent) {
		$this->parent = &$parent;
	}

	function fields(&$module,$isexport=false) {
		$c = 0;
		$result = array();
		foreach ($module->fields as $name => $field) {
			$c++;
			if (($isexport || !in_array($name,$module->keys)) && $field[CONS_XML_TIPO] != CONS_TIPO_UPLOAD) {
				$saida = array('#' => $c,
							   'name' => $name,
							   'islinker' => false,
							   'type' => $field[CONS_XML_TIPO],
							   'mandatory' => isset($field[CONS_XML_MANDATORY]) && $field[CONS_XML_MANDATORY] && !isset($field[CONS_XML_DEFAULT])?1:0,
							   'hasdefault' => isset($field[CONS_XML_DEFAULT]) && $field[CONS_XML_DEFAULT]?1:0
				);
				if ($field[CONS_XML_TIPO] == CONS_TIPO_ENUM && preg_match("/ENUM \(([^)]*)\).*/i",$field[CONS_XML_SQL],$regs)) {
					$saida['enum'] = " ".$regs[1];
				} else
				$saida['enum'] = "";
				if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK)
					# [ THIS MODULE ] ----> [ REMOTE MODULE ]
					$saida['remoteModule'] = $field[CONS_XML_MODULE]; # name, not object, for performance
				$result[] = $saida;
			}
		}
		# search linkers
		#	[ THIS MODULE ]  <--- [ LINKER MODULE ] ---> [ REMOTE MODULE ]
		# TODO: This code is working, but the import is not
		/*
		foreach ($this->parent->modules as $modname => $mod) {
			if ($modname != $module->name && $mod->linker) {
				$fmodule = ""; # name of the remote module this linker will link to
				$isLinked = false;
				foreach ($mod->fields as $name => $field) {
					# for each field on the linker module
					if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK && $field[CONS_XML_MODULE] == $module->name) {
					# this linker module links to me!
					$isLinked = true;
				} else if ($field[CONS_XML_TIPO] == CONS_TIPO_LINK && $fmodule == "") # first link (not me), save name
					$fmodule = $field[CONS_XML_MODULE];
				}
				if ($isLinked) {
					# ok, linker to this module!
					$c++;
					$saida = array('#' => $c,
							   'name' => $modname,
							   'type' => 0,
							   'enum' => "",
							   'islinker' => true,
							   'remoteModule' => $fmodule # performance and compatibility with remote mode
					);
					$result[] = $saida;
				}
			}
		}
	 */
		return $result;
	} # fields

	function hasRemotes(&$fields) {
		foreach ($fields as $idx => $field) {
			if ($field['type'] == 0 || $field['type'] ==  CONS_TIPO_LINK)
			return true;
		}
		return false;
	}

	function getField(&$fields,$number) {
		# fields is the result of the fields function above
		if ($number == 0) return -1;
	foreach ($fields as $idx => $field) {
		if ($field['#'] == $number)
		return $idx;
	}
	return -1;
	}

} // CImporter