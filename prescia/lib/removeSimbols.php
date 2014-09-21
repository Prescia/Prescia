<?/*--------------------------------\
  | removeSimbols : Prepares a string to be used as a filename
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses:
-*/

	function removeSimbols($data,$remove_ext=false,$allowSpaces=false,$separator="_") {
	  	#Turns a string in a valid file, remove folders and if you want points (remove_ext). Will always turn into lowercase
	  	$data = preg_replace("/((%)([0-9abcdef]{2}))/i","_",$data); // turns %## in _
	  	# renames some accented characters (instead of turning into _)
	  	$a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ'.(!$allowSpaces?" \t":"");
	    $b = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'.(!$allowSpaces?$separator.$separator:"");
	  	$data = utf8_decode($data);
	  	$data = strtr($data,utf8_decode($a),$b);
	  	$data = utf8_encode(strtolower($data));
		$l = strlen($data);
		$s = "";
		$allows = "qazwsxedcrfvtgbyhnujmikolp1234567890-_".($allowSpaces?" \t":"").($remove_ext?"":".");
		for ($c=0;$c<$l;$c++)
			$s .= (strpos($allows,$data[$c]) !== false)?$data[$c]:"";
		return $s;
	}
