<?/* -------------------------------- Prescia - simple text captcha
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
  | This is pretty much a placeholder, but should work for basic safety since bots are THAT stupid
  | For real safety please either replace this with a image based one, or use third-party captcha
-*/

	//function tCaptcha($key,$checkStage=false) {

	if ($checkStage) {
		if ($this->layout == 2) return; // do not reset on ajax windows
		if (isset($_SESSION['antibotnumber']) && isset($_REQUEST[$key]) && isset($_REQUEST['captchaseed'])) {
			$seed = $_REQUEST['captchaseed'];
			$ok = (isset($_SESSION['antibotnumber'][$seed]) && $_SESSION['antibotnumber'][$seed] == $_REQUEST[$key] && $_SESSION['antibotnumber'][$seed] != "*");
			if (!$ok) $this->log[] = $this->langOut('captcha_fail');
			else $_SESSION['antibotnumber'][$seed] = "*"; // one use only
		} else {
			$ok = false; # if it didn't work, consider fail
		}
		unset($_REQUEST['captchaseed']);
		unset($_GET['captchaseed']);
		unset($_POST['captchaseed']);
		if (!$ok) {
			# on your script, test if the key is on the $_POST (or get, request, whatever). If it is not, it was wrong.
			unset($_REQUEST[$key]);
			unset($_GET[$key]);
			unset($_POST[$key]);
		}
	} else {
		$page = $this->original_action;
		$page = explode(".",$page);
		$generateNew = $this->layout != 2; // do not generate on ajax
		if ($generateNew && count($page)>1) {
			$ext = strtolower(array_pop($page));
			if ($ext != "htm" && $ext != "html" && $ext != "php") {
				$generateNew = false; // do not generate on weird pages
			}
		}
		if ($generateNew || !isset($_SESSION['antibotnumber']) || count($_SESSION['antibotnumber']) == 0) {
			// generate new captcha
			if (!isset($_SESSION['antibotnumber'])) $_SESSION['antibotnumber'] = array();
			$x = (string)rand(1000,9999);
			$seed = count($_SESSION['antibotnumber']);
			$_SESSION['antibotnumber'][$seed] = $x;
			$x = (string)$x;
		} else {
			end($_SESSION['antibotnumber']);
			$seed = key($_SESSION['antibotnumber']);
			reset($_SESSION['antibotnumber']);
			$x = $_SESSION['antibotnumber'][$seed];
		}
		$output = "";
		for ($c=0;$c<strlen($x);$c++) {
			$a = rand(1,7);
			// add in the middle of the captcha, a hidden input with the seed field. this is used if you have multiple captchas on the same page
			if ($c == 2) $output .= "<input type=\"hidden\" name=\"captchaseed\" value=\"".$seed."\"/>";
			switch ($a) {
				case 1:
					$output .= $x[$c]."<span style='display:none'>".rand(1,99)."</span>";
				break;
				case 2:
					$output .= "<span>".$x[$c]."</span>";
				break;
				case 3:
					$output .= "<strong>\t".$x[$c]."</strong>\n";
				break;
				case 4:
					$output .= "&#".(48+$x[$c]).";";
				break;
				case 5:
					$output .= "\n\n\n\n\r\t\n<!-- 21 -->".$x[$c]."\n";
				break;
				case 6:
					$output .= $x[$c];
				break;
				default:
					$output .= "<b>".$x[$c]."</b>";
				break;
			}
		}
		$this->template->assign($key,$output);
	}
