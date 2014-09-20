<?
	# FROM => TO (full path using system constants)
	# Sample: CONS_PATH_SYSTEM."plugins/$sname/payload/[filename]" => CONS_PATH_PAGES.$_SESSION['CODE']."/template/[filename]"
	return array(CONS_PATH_SYSTEM."plugins/$sname/payload/getmyres.js" => CONS_PATH_JSFRAMEWORK."getmyres.js",
				CONS_PATH_SYSTEM."plugins/$sname/payload/files/" => CONS_PATH_PAGES."_common/files/adm/"
			);
?>