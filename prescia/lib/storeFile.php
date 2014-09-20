<?/*--------------------------------\
  | storeFile : Simple upload handing, with type control
  | Made for Prescia free framework (cc) Caio Vianna de Lima Netto
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses: safe_chmod
-*/


function storeFile($file,&$destination,$type="",$completeDebug=false) {
	/*  Stores an uploaded file (sent the $_FILES item not the array in $file) at the $destination file
	|   You can simulate an uploaded file by sending the $file array in the same format, plus 'virtual' = true so it copies instead of upload_move
	|    On virtual, fill these: error=0, tmp_name = [file], virtual=true, name= [filename]
	|   destination = the file to be saved, with OPTIONAL extension (the script will fill the appropriate
	|	 extension if it can detect the file type from $type or internal checkup - yet better send w/o extension)
	|    uppon sucess, $destination will RETURN the final path/file of the uploaded file
	|   type = defines the expected file type:
		'auto' = any file
		'image' = jpg,gif,png,jpeg
		'html' = htm, html
		'docs' = doc,rtf,pps,ppt,pdf,htm etc
		'udef:...' = allow you to specify the extensions, separated by comma
	|
	|  ERROR CODES:
	|  0 - Upload ok
	|  1 - File was larger than allowed on the server - upload fail
	|  2 - File was larger than allowed by the page (MAX_FILE_SIZE) - upload fail
	|  3 - Upload incomplete (might also be triggered if there is no permission to save at destination folder, of you forgot the multipart encode)
	|  4 - Nothing sent (no file sent)
	|  5 - Upload of invalid extension
		|  7 - File extension differs from file content (image,zip,rar), thus causing GD/zip issues
   */
	################################
	$NO_SCRIPTS = true; // <-- will rename .php, .asp, .jsp to .html
	################################

	if (!is_array($file)) $file = $_FILES[$file];

	$isauto = false;
	$desiredExtension = ""; // JUST extension (with dot)
	$desiredFilename = $destination; // WITHOUT extension (with path)

	if ($file['error'] == 0) { // upload complete
	  if ($type != "") { // Type control
		$desiredExtension = "";
		switch($type) {
		  case "auto": // Anything
			$type ="udef:([^\.]+)";
			$isauto = true;
		  break;
		  case "image": // Simple image files
			$type="udef:jpg,gif,png,jpeg";
		  break;
		  case "html": // HTML files
			$type="udef:htm,html,xhtml";
		  break;
		  case "docs": // documents
			$type="udef:doc,rtf,pps,ppt,pdf,htm,html,docx,xls,xlsx,txt,zip,rar,7z,odt,gz";
		  break;
		}
		if (substr($type,0,5) == "udef:") { // looks for the extension sent
		  $type = substr($type,5);
		  $type = explode(",",$type);
		  foreach($type as $x => $text) {
			if (preg_match("/^(.*)(\.".$text.")$/i",$file['name'],$regs)==1) $desiredExtension = ($isauto?".".$regs[3]:".$text");
		  }
		}
		if ($desiredExtension == "") { // invalid extension
		  if (!isset($file['virtual'])) @unlink ($file['tmp_name']);
		  if ($completeDebug) echo "Invalid Extension while checking type $type";
		  return 5; # invalid extension
		}
		if (is_file($file['tmp_name'])) {
			if (($desiredExtension == ".jpg" || $desiredExtension == ".gif" || $desiredExtension == ".png" || $desiredExtension == ".jpeg")) {
				// is an image file, checks if it REALLY is an image file
				$i = @getimagesize($file['tmp_name']);
				if ($i===false || !isset($i[2]) || ($i[2] != IMAGETYPE_JPEG && $i[2] != IMAGETYPE_PNG && $i[2] != IMAGETYPE_GIF && $i[2] != IMAGETYPE_BMP)) {
				  if (!isset($file['virtual'])) @unlink ($file['tmp_name']);
				  if ($completeDebug) echo "File should be an image (.jpg, .gif, .png, .jpeg) but it wasn't";
				  return 7; # not an image!
				}
				if ($i[2] == IMAGETYPE_JPEG || $i[2] == IMAGETYPE_BMP) $desiredExtension = ".jpg"; # we will convert bmp to jpg
				if ($i[2] == IMAGETYPE_PNG) $desiredExtension = ".png";
				if ($i[2] == IMAGETYPE_GIF) $desiredExtension = ".gif";
			}
			if ($desiredExtension == ".zip" || $desiredExtension == ".rar") {
				$fh = @fopen($file['tmp_name'], "r");
				if (!$fh) {
  					if ($completeDebug) echo "Unable to open file to check compressed type content";
					return 7;
				}
				$blob = fgets($fh, 5);
				fclose($fh);
				if ($desiredExtension == ".zip" && strpos($blob, 'PK') === false) {
					if ($completeDebug) echo "File should be an .zip file, but contents are not";
					return 7;
				}
				if ($desiredExtension == ".rar" && strpos($blob, 'Rar') === false) {
					if ($completeDebug) echo "File should be an .rar file, but contents are not";
					return 7;
				}
			}
		} else {
		  	if ($completeDebug) echo $file['tmp_name']." not found to test it's type";
		  	return 3; # upload incomplete (tmp_name missing)
		}
	  } else { # no type control, use extension from the submited file

	  	$desiredExtension = explode(".",$file['name']);
		$desiredExtension = ".".array_pop($desiredExtension);

	  }

	  if (strpos($desiredFilename,".")!==false) { // $desiredFilename should have only the filename, not extension
	  	// remove extension (if came)
	  	$desiredFilename = explode(".",$desiredFilename);
	  	array_pop($desiredFilename);
	  	$desiredFilename = implode(".",$desiredFilename);
	  }

	  ## we have $desiredExtension, $isimage and $desiredFilename ##

	  if ($desiredFilename == '') {
	  	if ($completeDebug) echo "File without name after removing extension! hack attempt?";
	  	return 3; # trying to send a dot file? no thanks hack-attempt
	  }

	  if ($NO_SCRIPTS && ($desiredExtension == ".php" || $desiredExtension == ".asp" || $desiredExtension == ".jsp")) {
	  	$desiredExtension .= ".html"; // change output extension
	  }

	  if (is_file($desiredFilename.$desiredExtension)) @unlink ($desiredFilename.$desiredExtension); # we will replace if file exists

	  // virtual call can simulate an upload just to use storefile, thus ...
	  if (isset($file['virtual']))
		 $ok = copy($file['tmp_name'],$desiredFilename.$desiredExtension);
	  else
	  	 $ok = move_uploaded_file($file['tmp_name'],$desiredFilename.$desiredExtension);

	  // ####################### UPLOAD COMPLETE ###################

	  if ($ok) {
		safe_chmod($desiredFilename.$desiredExtension,"0775"); // guarantee we can handle it in the future
		$destination = $desiredFilename.$desiredExtension;
		return 0; // ok
	  } else {
	  	if ($completeDebug) echo "copy or move_uploaded_file to ".$desiredFilename.$desiredExtension." failed";
		return 3; // failed upload
	  }

	} else { // $_FILE error
	  if (!isset($file['virtual']) && ($file['error'] == 3) && (is_file($file['tmp_name']))) // partial/failed upload
		@unlink ($file['tmp_name']);
	  if ($completeDebug) echo "Returning raw PHP upload code";
	  return $file['error'];
	}
}
