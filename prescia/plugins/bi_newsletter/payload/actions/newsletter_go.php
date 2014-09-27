<?

	if (!$core->authControl->checkPermission('bi_newsletter')) {
		$this->action = '403';
		return;
	}


	if (isset($_REQUEST['register'])) {

		$modTemplate = $core->loaded('bi_newsletter');
		if ($_REQUEST['mode']==1) {
			$dataTemplate = array(
				'title' => $_REQUEST['nttitle'],
				'mail_title' => $_REQUEST['ntetitle'],
				'texto' => $_REQUEST['nttext']
				);
			if ($core->runAction($modTemplate,CONS_ACTION_INCLUDE,$dataTemplate)) {
				$_REQUEST['id_newsletter'] = $core->lastReturnCode;
				$dataTemplate['id'] = $core->lastReturnCode;
			} else {
				$core->log[] = $core->langOut('newsletter_creation_fail');
				$core->action = "newsletter_send";
				$_REQUEST = array();
				$_GET = array();
				$_POST = array();
				return;
			}
		}

		$nldata = array(
					'id_newsletter' => $_REQUEST['id_newsletter'],
					'title' => $_REQUEST['name_newsletter'],
					'obs' => 'Criada em '.date("H:i:s d/m/Y")."\n",
					'recipients' => array(),
					'recipients_received' => '',
					'progress' => '0',
					'data_inicio' => date("Y-m-d H:i:s")
		);

		// groups = ids,
		// isInclude = true|false
		// [fname]_match = c|i|d + [fname] = field
		// extras = mails,
		// name_newsletter

		######################## Build SQL to choose mails ################## <- same as actions/newsletter_squery
		$ids = explode(",",$_REQUEST['groups']);
		$isInclude = isset($_REQUEST['isInclude']);

		$validIds = array();
		foreach ($ids as $id) {
			if (is_numeric($id))
				$validIds[] = $id;
		}

		$nl = $core->loadedPlugins['bi_newsletter'];
		$mod = $core->loaded($nl->recipientList);
		$mailName = isset($mod->fields['mail'])?'mail':'email';

		$where = array();
		if (count($validIds) == 0) $validIds[] = 0;
		$where[] = "l.id_recipient=r.id";
		$where[] = "l.id_newsgroup ".($isInclude?"IN":"NOT IN")." (".implode(",",$validIds).")";
		$where[] = "r.".$mailName." LIKE \"%@%\"";
		$where[] = "r.receber_newsletter='y'";

		foreach ($mod->fields as $fname => $field) {
			if (isset($_REQUEST[$fname."_c"]) && isset($_REQUEST[$fname."_match"]) && isset($_REQUEST[$fname])) {
				$tempwhere = "r.".$fname;
				switch($_REQUEST[$fname."_match"]) {
					case "c":
						$tempwhere .= " LIKE \"%".$_REQUEST[$fname]."%\"";
						break;
					case "i":
						$tempwhere .= "=\"".$_REQUEST[$fname]."\"";
						break;
					case "d":
						$tempwhere .= "<>\"".$_REQUEST[$fname]."\"";
						break;
				}
				$where[] = $tempwhere;
			}
		}
		######################################################################

		$sql = "SELECT r.name, r.".$mailName." FROM ".$mod->dbname." as r, sys_news_link as l WHERE ".implode(" AND ",$where)." GROUP BY r.id";
		$mails = array();

		if ($core->dbo->query($sql,$r,$n) && $n>0) {
			for ($c=0;$c<$n;$c++) {
				list($name,$mail) = $core->dbo->fetch_row($r);
				if (!isset($mails[$mail])) $mails[$mail] = $name; // <-- fastest way to check for an item in a big array!
			}
		}

		if (isset($_REQUEST['extras'])) {
			$extra = str_replace("\n",",",$_REQUEST['extras']);
			$extra = preg_replace("/(\t| |\r)/","",$extra);
			$extra = explode(",",$extra);
			foreach ($extra as $mail) {
				$mail = trim($mail);
				if (isMail($mail) && !isset($mails[$mail]))
					$mails[$mail] = $mail;
			}
		}

		foreach ($mails as $mail => $name) {
			$nldata['recipients'][] = "$name <$mail>";
		}

		$nldata['recipients'] = serialize($nldata['recipients']);

		$ok = $core->runAction('bi_newsletter_send',CONS_ACTION_INCLUDE,$nldata);
		if ($ok) {
			$core->log[] = $core->langOut('newsletter_send_created');
			$core->headerControl->internalFoward('newsletter_go.html?id='.$core->lastReturnCode);
		}

	} else if (!isset($_REQUEST['id'])) {
		$core->action = '404';
	} else if (isset($_REQUEST['obs']) && isset($_REQUEST['haveinfo'])) {
		$nldata = array("id" => $_REQUEST['id'],
						"obs" => $_REQUEST['obs']);
		$core->runAction('bi_newsletter_send',CONS_ACTION_UPDATE,$nldata);
	}