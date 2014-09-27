<?
	# FROM => TO (full path using system constants)
	# Sample: CONS_PATH_SYSTEM."plugins/$sname/payload/[filename]" => CONS_PATH_PAGES.$_SESSION['CODE']."/template/[filename]"
	return array(CONS_PATH_SYSTEM."plugins/$sname/payload/files/affbiblog.css" => CONS_PATH_PAGES.$_SESSION['CODE']."/files/affbiblog.css",
				 CONS_PATH_SYSTEM."plugins/$sname/payload/files/quotes.png" => CONS_PATH_PAGES.$_SESSION['CODE']."/files/quotes.png",
				 CONS_PATH_SYSTEM."plugins/$sname/payload/files/white50.png" => CONS_PATH_PAGES.$_SESSION['CODE']."/files/white50.png"
				);
?>