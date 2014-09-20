<?
	# FROM => TO (full path using system constants)
	# Sample: CONS_PATH_SYSTEM."plugins/$sname/payload/[filename]" => CONS_PATH_PAGES.$_SESSION['CODE']."/[filename]"
	# It accepts folders (must end with /)
	return array(CONS_PATH_SYSTEM."plugins/$sname/payload/files/" => CONS_PATH_PAGES."_common/files/adm/"
		);
	
?>