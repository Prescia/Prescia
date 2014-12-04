<?
	# FROM => TO (full path using system constants)
	# Sample: CONS_PATH_SYSTEM."plugins/$sname/payload/[filename]" => CONS_PATH_PAGES.$_SESSION['CODE']."/template/[filename]"
	return array(
		CONS_PATH_SYSTEM."plugins/$sname/payload/bi_auth_activate.html" => CONS_PATH_PAGES.$_SESSION['CODE']."/mail/bi_auth_activate.html",
		CONS_PATH_SYSTEM."plugins/$sname/payload/bi_auth_activated.html" => CONS_PATH_PAGES.$_SESSION['CODE']."/mail/bi_auth_activated.html",
		CONS_PATH_SYSTEM."plugins/$sname/payload/bi_auth_welcome.html" => CONS_PATH_PAGES.$_SESSION['CODE']."/mail/bi_auth_welcome.html",
		);
?>