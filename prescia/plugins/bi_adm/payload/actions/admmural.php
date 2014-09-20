<?

	$admmural = $_POST['admmural'];
	cWriteFile(CONS_PATH_DINCONFIG.$_SESSION['CODE']."/mural.txt",$admmural);
	$core->close();
	
