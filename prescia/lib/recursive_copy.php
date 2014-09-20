<?/*--------------------------------\
  | recursive_copy Recursivelly copy a FOLDER into another FOLDER, with all contents. Will ignore and even delete Thumbs.db
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses: listFiles, safe_mkdir
-*/

	# Returns the number of copies made
	function recursive_copy($source,$destination) {
	    $counter = 0;
	    if (substr($source,strlen($source),1)!="/") $source .= "/";
	    if (substr($destination,strlen($destination),1)!="/") $destination .= "/";
	    if (!is_dir($destination)) makeDirs($destination);
	    $itens = listFiles($source);
	    foreach($itens as $id => $name) {
	      if ($name[0] == "/") $name = substr($name,1);
	      if (is_file($source.$name)) { // file
	      	if ($name != "Thumbs.db") {
		        $counter ++;
		        if (!copy ($source.$name,$destination.$name))
		          echo "Error: ".$source.$name." -> ".$destination.$name."<br/>";
		        else
		          safe_chmod($destination.$name,0775);
	      	} else
	      		@unlink($source.$name);
	      } else if(is_dir($source.$name)) { // dir
	        if (!is_dir($destination.$name))
	         safe_mkdir($destination.$name);
	        $counter += recursive_copy($source.$name,$destination.$name);
	      }
	    }
	    return $counter;
	}
