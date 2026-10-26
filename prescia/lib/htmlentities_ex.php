<?php
/*--------------------------------\
  | htmlentities_ex : basic htmlentities wont convert latin (or others) chars ...
  | Made for Prescia family framework (cc) Caio Vianna de Lima Netto @ www.prescia.net
  | Free to use, change and redistribute, but please keep the above disclamer.
  | Uses: Turn non ASCII accents to amp codes since htmlentities does not do it
  | Still in use?
-*/

		function htmlentities_ex($str) {
		    return \Prescia\Services\Sanitizer::escapeHtml((string)$str);
		}
