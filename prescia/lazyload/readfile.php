<? // ------------------------ Prescia readfile

	# readfile($file,$ext="",$exit=true,$filename="",$forceAttach=false,$cachetime=6000) {

	if ($ext == "") {
		$ext = explode(".",$file);
		$ext = array_pop($ext);
	}

	$ext = strtolower($ext);
	if ($ext == "php" || $ext == "asp" || $ext == "jsp") $this->fastClose('403'); # don't serve scripts
	if (!function_exists('getMime')) include CONS_PATH_INCLUDE."getMime.php"; # as needed
	$mime = getMime($ext);
	$attachMode = $forceAttach || getMimeMode($ext,true);
	@ob_end_clean();

	header("Content-Description: File Transfer");
	header("Content-Length: ".filesize($file));
	header("Pragma: public");
	if (!is_numeric($cachetime) || $cachetime<5) $cachetime = 5;
	header("Cache-Control: public,max-age=".$cachetime.",s-maxage=".$cachetime);
	if ($mime != "")
		header("Content-type: $mime");
	else
		header("Content-type: application/octet-stream");
	if ($filename == "") $filename = $file;
	$exfile = explode("/",$filename);
	$filetitle = array_pop($exfile);
   	header("Content-Disposition: ".($attachMode?"attachment":"inline")."; filename=\"".$filetitle."\""); // estava como attachment
   	$this->close(false); # disconnects from DB
   	usleep(10);
	readfile($file);
	usleep(10);
	if ($exit) die();
	else {
		@ob_start();
		$this->dbconnect(); # reconnect
	}