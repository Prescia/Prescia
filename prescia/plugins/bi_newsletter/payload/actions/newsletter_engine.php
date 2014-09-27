<? /* -------------------- Newsleter submission engine
 * This will also be used to send the "preview"
 */



	if (!$core->authControl->checkPermission('bi_newsletter')) { // allowed to use this?
		echo "e: Permission denied";
		$core->close();
	}

	// we require WHICH newsletter, and either where we are at the submission queue OR a preview mail
	if (!isset($_REQUEST['id']) || (!isset($_REQUEST['position']) && !isset($_REQUEST['mail']))) {
		echo "e: Missing newsletter submission data";
		$core->close();
	}

	$id = $_REQUEST['id'];

	if (isset($_REQUEST['position'])) {
		$isTest =false; // <-- not a test
		$position = $_REQUEST['position'];
	} else {
		$mailTest = $_REQUEST['mail'];
		$isTest = true; // <-- a test
		$position = -1;
	}

	if (isset($core->loadedPlugins['bi_stats'])) { // if this client has statistics, do not log this
		$core->loadedPlugins['bi_stats']->doNotLogMe = true;
	}

	// load up newsletter (data) and newsletter_send (submission queue)
	$nls = $core->loaded('bi_NEWSLETTER_SEND');
	$nlPlugin = $core->loadedPlugins['bi_newsletter'];
	$attemptSubmit = $nlPlugin->packsize; // if a submission fails, it will try less to prevent timeout (a failure consumes some seconds)

	// get submision data
	$sql = $nls->get_base_sql("bi_newsletter_send.id = $id");
	$ntp = new CKTemplate(); // <-- garbage
	$data = $core->runContent($nls,$ntp,$sql,"",false,false); // used only to get data
	$mailSent = "";
	if ($data != false) {
		$recipients = @unserialize($data['recipients']); // submission list
		if ($recipients == false) {
			echo "e: Recipient list is corrupt!";
		} else {
			$RAWmail = $core->prepareMail($nlPlugin->templateFile); // build template, using templateFile (can be nothing)
			$OPTOUT = new CKTemplate($core->template); // prepare OPTOUT string
			$OPTOUT->tbreak($nlPlugin->optout_txt);
			$OPTOUT->assign("ABSOLUTE_URL","http://".$core->domain.CONS_INSTALL_ROOT);
			$OPTHIT = new CKTemplate($core->template); // prepare HIT DETECTOR
			$OPTHIT->tbreak($nlPlugin->opthit_txt);
			$OPTHIT->assign("ABSOLUTE_URL","http://".$core->domain.CONS_INSTALL_ROOT);
			$core->headerControl->addHeader(CONS_HC_HEADER,200); // tell the browser this page must ALWAYS me loaded
			$core->headerControl->addHeader(CONS_HC_CACHE,"Cache-Control: no-cache, must-revalidate");
			for ($s = 0; $s < $attemptSubmit; $s++) {
				$position++;
				if ($position <= count($recipients)) {
					$mail = clone $RAWmail;
					if ($position == 1) { // starting newsletter (first mail)
						$data['obs'] .= $core->langOut('newsletter_envio_iniciado_em')." ".date('H:i:s d/m/Y');
						$data['progress'] = 0;
						$data['data_inicio'] = date("Y-m-d H:i:s");
						$core->runAction($nls,CONS_ACTION_UPDATE,$data);
					}
					$nextMail = $isTest?$mailTest:$recipients[$position-1]; // mail to the mailTest or a real recipient
					$nextMail = explode("<",$nextMail); // support for "mail <mail>"
					if (count($nextMail)<=2) {
						$nextMail[0] = trim($nextMail[0]); // name
						if (count($nextMail)==1)
							$nextMail[1] = $nextMail[0]; // not extended format
						else
							$nextMail[1] = trim(str_replace(">","",$nextMail[1])); // extended format mail
						if (isMail($nextMail[1])) { // valid mail
							$mailSent .= $nextMail[1];
							$text = str_replace("{NOME}",$nextMail[0],$data['newsletter_texto']);
							$text = str_replace("{MAIL}",$nextMail[1],$text);
							$subject = str_replace("{NOME}",$nextMail[0],$data['newsletter_mail_title']);
							$subject = str_replace("{MAIL}",$nextMail[1],$subject);
							$mail->assign("TITLE",$subject);
							$mail->assign("CONTENT",$text.$OPTOUT->techo(array("mail"=>$nextMail[1])).$OPTHIT->techo(array("mail"=>$nextMail[1],"nid"=>$id)));
							if (substr($subject,0,3) != "NS:") $subject = "NS:".$subject; // add NS: flag on title, as per ethics guides for internet newsletter
							if (sendMail($nextMail[1],$subject,$mail,$core->dimconfig['newslettersourcemail'],"",true)) { // send mail
								$mailSent .= "(ok),";
							} else {
								$mailSent .= "(fail),";
							}
						}
					}
					if ($isTest) break;
					usleep($nlPlugin->packsleep * 1000);
				} else {
					$position = count($recipients);
					break;
				}
			} # for
		}
		if (!$isTest) {
			$sql = "UPDATE ".$nls->dbname." SET progress=$position WHERE id=$id";
			$core->dbo->simpleQuery($sql);
			echo $position."|".$mailSent;
		} else
			echo $core->langOut('newsletter_test_msg').": ".$mailSent;
	} else
		echo "e: Error when fetching newsletter data<br/>Sql where ".$core->dbo->sqlarray_echo($sql);

	$core->close();

