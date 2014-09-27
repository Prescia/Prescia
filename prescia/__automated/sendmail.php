<?	/* -------------------------------- Simple sendmail using core functions
	NOTE: use POST form if you want the automatic template to work
	USAGE:

	<SENDMAIL>
		<MAILTO>mail to OR $_POST field to be used as such. If none especified will try to use adminmail from config, supports multiple comma delimited</MAILTO>
		<MAILPAIRING>if you have sessions, like 3 different mails selected by a select, you can pair them here. Example:</MAILPAIRING>
			<MAILTO>mail1@server,mail2@server,mail3@server</MAILTO>
			<MAILPAIRING>session=sales,session=admin,session=contact</MAILPAIRING>
			will use mail1@server if $_POST['session']='sales'
			will use mail2@server if $_POST['session']='admin'
			will use mail3@server if $_POST['session']='contact'
		<MAILFROM>mail from OR $_POST field to be used as such. If none especified will try to use adminmail from config</MAILFROM>
		<MAILSUBJECT>subject. Accepts template tags. If none will be "Mail from [domain]"</MAILSUBJECT>
		<MAILTEMPLATE>template file to be used and filled with $_POST data, inside mail/ folder (optional - if none especified will build the mail using $_POST data)</MAILTEMPLATE>
		<ONSUCESS>page to redirect if mail sent. You can add a log message (to be shown to the user - accepts template tags) after a comma, example: index,mail sent!</ONSUCESS>
		<ONFAIL>same as above, but if mail fails to be sent</ONFAIL>
		<MERGESESSION>session variable (array) to merge with POST. Session will take precedene on conflicts</MERGESESSION>
		<IGNOREKEYS>list of keys from POST or SESSION NOT TO include on the mail</IGNOREKEYS>
		<CONTENT>Allows you to specify the content of the message, accepts template tags which will be filled by the request (or session too if you use mergesession)</CONTENT>
		<DEBUG>if set (to anything), will NOT send ANY mail, just perform all tests and display what would be the e-mail sent</DEBUG>
  		<RUNATCONTENT>true|false</RUNATCONTENT> will run the sendmail at content level instead of action
  		<REQUIRED_KEYS>comma delimited list of required items</REQUIRED_KEYS> If keys not present, will run onFail order (tests $_POST only)
	</SENDMAIL>
	*/

class auto_sendmail extends CautomatedModule  {

	function loadSettings() {
		$this->name = "sendmail";
		$this->sorting_weight = 0; // last to run, so other automatos can fill in/remove mandatory keys, redirect etc
		$this->nested_folders = false;
		$this->nested_files = false;
		$this->virtual_folders = false;
		//$this->accepts_multiple = false;
	}

	function onCheckActions($definitions) {
		$runhere = isset($definitions['runatcontent'])?$definitions['runatcontent']!='true':true;
		if ($runhere) $this->sendMail($definitions);
	}

	function sendMail($definitions) {
		$definitions = $definitions[CONS_XMLPS_DEF];
		$rk = isset($definitions['required_keys'])?explode(",",$definitions['required_keys']):array();
		$mailto = isset($definitions['mailto'])?$definitions['mailto']:(isset($this->parent->dimconfig['contactmail'])?$this->parent->dimconfig['contactmail']:"");

		$mailto = str_replace("&lt;","<",$mailto);
		$mailto = str_replace("&gt;",">",$mailto);
		$pairing= isset($definitions['mailpairing'])?$definitions['mailpairing']:"";
		$isDebug = isset($definitions['debug']);
		if (!isMail($mailto,true)) { // mailto is not a valid mail, is it a list?
			if (strpos($mailto,",")===false) { // not a list, try POST
				$mailto = isset($_POST[$mailto])?$_POST[$mailto]:"";
			} else { // list, guarantees we have only valid mails
				$mailto = preg_replace("/( |\t|\r|\n)+/"," ",$mailto);
				$nmt = array();
				$mailto = explode(",",$mailto);
				foreach ($mailto as $mt) {
					if (isMail(trim($mt),true)) $nmt[] = trim($mt);
				}
				if ($pairing != '') { // pairing system
					$mt = array();
					$pairing = explode(",",$pairing);
					if (count($pairing) != count($mailto)) {
						$this->parent->errorControl->raise(403,'sendmailautomato',"","Pairing does not match number of mails!");
					}
					for ($c=0;$c<count($pairing);$c++) {
						$cond = $pairing[$c];
						$cond = explode("=",$cond);
						$isnot = false;
						if ($cond[0][strlen($cond[0])-1] == "!") {
							$isnot = true;
							$cond[0] = str_replace("!","",$cond[0]);
						}
						$cond[0] = trim($cond[0]);
						$cond[1] = trim($cond[1]);
						if (isset($_POST[$cond[0]])) {
							if ($isnot && $_POST[$cond[0]] != $cond[1]) {
								$mt[] = $mailto[$c];
							}
							if (!$isnot && $_POST[$cond[0]] == $cond[1]) {
								$mt[] = $mailto[$c];
							}
						}
					}
					$mailto = implode(",",$mt);
					if ($mailto == "") {
						$this->parent->errorControl->raise(403,'sendmailautomato',"","mailto using pairing failed, tests were ".implode(",",$pairing));
						return false;
					}
				} else
					$mailto = implode(",",$nmt); // send to all
			}
		}
		if ($mailto == "") {
			$this->parent->errorControl->raise(403,'sendmailautomato',"","no destination mail specified (dimconfig 'contactmail' not found neither)");
			return false;
		}
		$mailfrom = isset($definitions['mailfrom'])?$definitions['mailfrom']:(isset($this->parent->dimconfig['contactmail'])?$this->parent->dimconfig['contactmail']:"");
		if (!isMail($mailfrom) && isset($_POST[$mailfrom])) $mailfrom = $_POST[$mailfrom];
		if ($mailfrom == "") {
			$this->parent->errorControl->raise(403,'sendmailautomato',"","no origin mail specified");
			return;
		}
		$subject = isset($definitions['mailsubject'])?$definitions['mailsubject']:"Mail from ".$this->parent->domain;
		$template = isset($definitions['mailtemplate'])?$definitions['mailtemplate']:"";
		$content = isset($definitions['content'])?$definitions['content']:"";
		$onsucess = isset($definitions['onsucess'])?$definitions['onsucess']:"";
		$onfail = isset($definitions['onfail'])?$definitions['onfail']:"";
		$ms = isset($definitions['mergesession'])?$definitions['mergesession']:"";
		$ignoreList = isset($definitions['ignorekeys'])?explode(",",$definitions['ignorekeys']):array();
		if ($ms != '' && isset($_SESSION[$ms])) {
			foreach ($_SESSION[$ms] as $key => $scontent)
				if ($scontent != '') $_POST[$key] = $scontent;
		}
		foreach ($ignoreList as $iL)
			unset($_POST[$iL]);
		if ($onsucess != '') {
			$onsucess = explode(",",$onsucess);
			if (count($onsucess)==1) $onsucess[] = "Mail ok";
		}
		if ($onfail != '') {
			$onfail = explode(",",$onfail);
			if (count($onfail)==1) $onfail[] = "Mail error";
		}
		$ntp = new CKTemplate($this->parent->template);
		$ntp->tbreak($subject);
		$subject = $ntp->techo($_POST);
		$mail = $this->parent->prepareMail($template,$_POST);
		if ($template == "" && $content == "") {
			if (is_file(CONS_PATH_SETTINGS."defaults/automail.html")) {
				$smh = new CKTemplate($this->parent->template);
				$smh->fetch(CONS_PATH_SETTINGS."defaults/automail.html");
				$objField = $smh->get("_field");
				$temp = "";
				foreach ($_POST as $name => $data) {
					if ($name != "haveinfo" && !in_array($name,$ignoreList)) {
						$temp .= $objField->techo(array('name' => $name,'content' => nl2br($data)));
					}
				}
				$smh->assign("_field",$temp);
				$template = $smh->techo();
				unset($smh);
				unset($objField);
			} else {
				foreach ($_POST as $name => $data) {
					if ($name != "haveinfo" && !in_array($name,$ignoreList)) {
						if (strpos($data,"\n")!==false)
							$template .= "<strong>$name</strong>: <br/><blockquote>".nl2br($data)."</blockquote>\n";
						else
							$template .= "<strong>$name</strong>: ".$data."<br/>\n";
					}
				}
			}
			$template .= "<br/>\n<br/>".$this->parent->langOut('sendmail_sent_from')." : ".$this->parent->domain.$this->parent->context_str.$this->parent->action."<br/>\n";
			$mail->assign("CONTENT",$template);
		} else if ($content != "") {
			$ct = new CKTemplate($this->parent->template);
			$ct->tbreak($content);
			$mail->assign("CONTENT",$ct->techo($_POST));
		}

		if (is_array($onsucess)) {
			$ntp = new CKTemplate($this->parent->template);
			$ntp->tbreak($onsucess[0]);
			$temp = $ntp->techo($_POST);
			$ntp = new CKTemplate($this->parent->template);
			$ntp->tbreak($onsucess[1]);
			$onsucess = array($temp,$ntp->techo($_POST));
		}
		if (is_array($onfail)) {
			$ntp = new CKTemplate($this->parent->template);
			$ntp->tbreak($onfail[0]);
			$temp = $ntp->techo($_POST);
			$ntp = new CKTemplate($this->parent->template);
			$ntp->tbreak($onfail[1]);
			$onfail = array($temp,$ntp->techo($_POST));
		}

		$rkok = true;
		foreach ($rk as $key) {
			if (!isset($_POST[$key])) $rkok = false;
		}

		if ($isDebug) {
			$mail->assign("IP",CONS_IP);
			$mail->assign("HOUR",date("H:i"));
			$mail->assign("DATA",date("d/m/Y"));
			$mail->assign("DATE",date("m/d/Y"));
			echo "SENDMAIL DEBUGMODE:<br/>";
			echo "REQUIRED_KEYS: ".($rkok?"ok":fail)."<br/>";
			echo "MAILTO = $mailto<br/>";
			echo "MAILFROM = $mailfrom<br/>";
			echo "SUBJECT = $subject<br/>";
			echo "ONSUCESS = ".(is_array($onsucess)?implode(",",$onsucess):"")."<br/>";
			echo "ONFAIL = ".(is_array($onfail)?implode(",",$onfail):"")."<br/>";
			echo "MAIL follows:<br/>";
			echo "<hr/>";
			echo $mail->techo();
			$this->parent->close(true);
			die();
		}

		$ok = $rkok && sendmail($mailto,$subject,$mail,$mailfrom);
		$this->parent->errorControl->raise(405,($ok?"sucess":"fail".($rkok?"(rkok)":"(failed rk)")),'sendmail',$mailto);
		if ($ok && is_array($onsucess)) {
			if ($onsucess[0] != '')	$this->parent->action = $onsucess[0];
			$this->parent->log[] = $onsucess[1];
			if ($onsucess[0] != '')	$this->parent->headerControl->internalFoward($onsucess[0]);
		} else if (!$ok && is_array($onfail)) {
			if ($onfail[0] != '') $this->parent->action = $onfail[0];
			$this->parent->log[] = $onfail[1];
			if ($onfail[0] != '') $this->parent->headerControl->internalFoward($onfail[0]);
		}

		unset($ntp);
	}

	function onRender($definitions) {
		$runhere = isset($definitions['runatcontent'])?$definitions['runatcontent']=='true':false;
		if ($runhere) $this->sendMail($definitions);
	}
}

