<?/*--------------------------------\
  | utf8_truncate : Truncates a utf-8 string w/o breaking utf-8 words
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses:
-*/

	function utf8_truncate($str,$size=50) {
		if ($size<0) return "";
		$len = strlen($str);
		if ($len<=$size) return $str; # trying to truncate something that is already smaller
		$charlast = ord($str[$size]);
		while ($charlast > 0x7F) {
			$size++;
			if ($charlast < 0xBF) break; # starting another word
			if ($len<=$size) return $str; # UTF encoded is larger word-wise, but smaller character-wise
			$charlast = ord($str[$size]);
		}
	  	return substr($str,0,$size);
	}
