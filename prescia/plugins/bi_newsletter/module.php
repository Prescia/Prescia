<?	# -------------------------------- Nekoi's Newsletter

$this->noBotProtectOnAjax = true;

class mod_bi_newsletter extends CscriptedModule  {

	var $recipientList = "bi_recipients"; // change the XML of bi_NEWSLINK to reflect this
	var $recipientMailField = "mail"; // if the above module have a different field for mail, set here
	var $recipientNameField = "name"; // same as above
	var $templateFile = ""; // set a name for the template to be always used (should be inside the /mail/ folder)
	var $packsize = 2; // on each ajax submit, send this ammound of mails
	var $packsleep = 250; // ms to wait between each sendMail (total wait between packs is this value plus the timeout in newsletter_go, and then obviously network times)
	var $admFolder = "adm";
	var $optout_txt = "\n<br/><a class=\"format-it\" style=\"font-size:9px\" href=\"{ABSOLUTE_URL}nl_optout.php?mail={mail}\">{_t}click_here_to_unsubscribe{/t}</a>\n";
	var $optout_msg = "your_mail_have_been_removed_from_the_mailing_list"; // this is a locale tag
	var $opthit_txt = "\n<img src=\"{ABSOLUTE_URL}nl_hit.gif?mail={mail}&nid={nid}\" alt=\"Newsletter read notification image\"/>";

	// NOTE: $packsize * $packsleep should not exceed about half the php timeout, should also do not keep the browser waiting. Suggested value: max 5s

	private $contextfriendlyfolderlist = array();
	private $isAdminPage = false; // cache for testing if have stats module

	function loadSettings() {
		$this->name = "bi_newsletter";
		$this->moduleRelation = "bi_newsletter";
		$this->parent->onMeta[] = $this->name;
		$this->parent->onActionCheck[] = $this->name;
		#$this->parent->onRender[] = $this->name;
		$this->parent->on404[] = $this->name;
		$this->parent->onShow[] = $this->name;
		#$this->parent->onEcho[] = $this->name;
		#$this->parent->onCron[] = $this->name;
		#$this->customFields = array();
		$this->admOptions = array("new"=>"edit.php?module=bi_newsletter",
								  "list"=>"list.php?module=bi_newsletter",
								  "nlsend"=>"newsletter_send.php",
								  "nlmonitor"=>"list.php?module=bi_newsletter_send",
		);

	}

	function onMeta() {

		$sql = $this->parent->modules['bi_newsgroup']->get_base_sql("bi_newsgroup.id=1");
		if (!$this->parent->dbo->query($sql,$r,$n) || $n==0) {
			# database present (query ok) but empty ... create default groups
			$this->parent->dbo->simpleQuery("INSERT INTO ".$this->parent->modules['bi_newsgroup']->dbname." SET name='Default', id=1");
			$this->parent->log[] = "Default newsletter group created (id=1)";
		}


	}
	function onCheckActions() {
		$this->admFolder = explode(",",$this->admFolder);
		for ($c=0;$c<count($this->admFolder);$c++) {
			$this->admFolder[$c] = trim($this->admFolder[$c]," /");
			$this->contextfriendlyfolderlist[] = ($this->admFolder[$c]==""?"/":("/".$this->admFolder[$c]."/"));
		}
		if (in_array($this->parent->context_str,$this->contextfriendlyfolderlist)) {
			$this->isAdminPage = true;
		}

		if ($this->isAdminPage && is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/".$this->parent->action.".php")) {
			$core = &$this->parent;
			include CONS_PATH_SYSTEM."plugins/".$this->name."/payload/actions/".$this->parent->action.".php";
			return;
		}
	}

	function on404($action, $context = "") {
		if ($this->isAdminPage && is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html")) {
			if ($_SESSION[CONS_SESSION_ACCESS_LEVEL]<$this->admRestrictionLevel)
				$this->parent->fastClose(403);
			else {
				return CONS_PATH_SYSTEM."plugins/".$this->name."/payload/template/$action.html";
			}
		}
		if ($this->parent->context_str == "/" && $this->parent->action == "nl_optout" && (isset($_REQUEST['mail']) || isset($_REQUEST['email']))) {
			$this->unsubscribe(isset($_REQUEST['mail'])?$_REQUEST['mail']:$_REQUEST['email']);
			$this->parent->log[] = $this->parent->langOut($this->optout_msg);
			$this->parent->setLog(CONS_LOGGING_SUCCESS);
			$this->parent->headerControl->internalFoward("/index.html");
		}
		if ($this->parent->context_str == "/" && $this->parent->action == "nl_hit" && (isset($_REQUEST['mail']) || isset($_REQUEST['email'])) && isset($_REQUEST['nid']) && is_numeric($_REQUEST['nid'])) {
			$this->hit(isset($_REQUEST['mail'])?$_REQUEST['mail']:$_REQUEST['email'],$_REQUEST['nid']);
			// echo a white 1x1 gif
			header("Content-type: image/gif");
			$im = ImageCreate( 1, 1 );
			$white = ImageColorAllocate( $im, 255, 255, 255 );
			ImageGif( $im );
			imagefill($im, 0, 0, $white);
			ImageDestroy( $im ); // Free memory
			$this->parent->layout=2; // if close fails ...
			$this->parent->close();
		}
		return false;
	}

	function onShow(){
		if ($this->isAdminPage && is_file(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/content/".$this->parent->action.".php")) {
			$core = &$this->parent;
			include CONS_PATH_SYSTEM."plugins/".$this->name."/payload/content/".$this->parent->action.".php";
			return;
		}
	}

	function edit_parse($action,&$data) {
		# happens before runAction so the personalized system can fix informations on this field (only for INSERT and UPDATE)
		# return TRUE if data is ready for runAction, FALSE on error or permission denied
		if (isset($data['texto'])) {
			# replaces src="/ with src="http://{domain}/
			$data['texto'] = str_replace(" src=\"/"," src=\"http://".$this->parent->domain."/",$data['texto']);
		}
		return true;
	}

	function notifyEvent(&$module,$action,$data,$startedAt="",$earlyNofity =false) {
		# notify followup for this field (happens before standard notify)
		# this is called TWICE: one BEFORE (earlyNotify) and one AFTER the action. Delete parsers should always focus on the earlyNotify pass
		if ($module === false) return;
		if ($this->parent->dimconfig['newsletterdefaultgroup'] != 0 && $module->name ==$this->recipientList && $action == CONS_ACTION_INCLUDE && !$earlyNofity && isset($data['id'])) {
			// the mail was REGISTERED, add default group
			$nll = $this->parent->loaded('bi_newslink');
			$nid = $data['id'];
			$sql = "INSERT INTO ".$nll->dbname." SET id_recipient='$nid', id_newsgroup='".$this->parent->dimconfig['newsletterdefaultgroup']."'";
			$this->parent->dbo->simpleQuery($sql);
		}
	}

	function subscribe($data, $quiet=false, $redirect = false) { /* data must have at least 'mail' or 'email'*/
		if (!isset($data['mail']) && isset($data['email'])) $data['mail'] = $data['email'];

		// Tests if the email is valid
		if(!isMail($data['mail'])){
			if (!$quiet) {
				$this->parent->log[] = $this->parent->langOut("nl_insert_fail").' ('.$this->parent->langOut("invalid_mail").")";
				$this->parent->setLog(CONS_LOGGING_ERROR);
			}
			if($redirect!==false) $this->parent->headerControl->internalFoward("/".$redirect);
			return false;
		}

		if ($this->parent->dimconfig['newsletterdefaultgroup'] == 0) {
			$this->parent->log[] = $this->parent->langOut("nl_insert_fail")." (configError)";
			$this->parent->setLog(CONS_LOGGING_ERROR);
			if($redirect!==false) $this->parent->headerControl->internalFoward("/".$redirect);
			return false;
		}

		$nlu = $this->parent->loaded($this->recipientList);
		$sql = "SELECT id FROM ".$nlu->dbname." WHERE ".$this->recipientMailField."=\"".$data['mail']."\"";
		if ($this->parent->dbo->query($sql,$r,$n) && $n>0)
			list($id_exists) = $this->parent->dbo->fetch_row($r);
		if ($n>0) {
			if (!$quiet){
				$this->parent->log[] = $this->parent->langOut("nl_already_on");
				$this->parent->setLog(CONS_LOGGING_NOTICE);
				if($redirect!== false) $this->parent->headerControl->internalFoward("/".$redirect);
			}
			$data['id'] = $id_exists;
			// update current ingo
			$data['receber_newsletter'] = 'y';
			$this->parent->safety = false; // always include, no safety check
			$ok = $this->parent->runAction($nlu,CONS_ACTION_UPDATE,$data);
			$this->parent->safety = true;
			return $ok;
		}
		$data['receber_newsletter'] = 'y';
		$this->parent->safety = false; // always include, no safety check
		$ok = $this->parent->runAction($nlu,CONS_ACTION_INCLUDE,$data);
		$this->parent->safety = true;
		if ($ok) {
			// done
			if (!$quiet) {
				$this->parent->log[] = $this->parent->langOut("nl_insert_ok");
				$this->parent->setLog(CONS_LOGGING_SUCCESS);
			}
		} else if (!$quiet) {
			$this->parent->log[] = $this->parent->langOut("nl_insert_fail");
			$this->parent->setLog(CONS_LOGGING_ERROR);
		}

		if($redirect!==false) $this->parent->headerControl->internalFoward("/".$redirect);

		return $ok;
	}

	function unsubscribe($mail) {
		// obvious, right?
		$nlu = $this->parent->loaded($this->recipientList);
		$sql = "UPDATE ".$nlu->dbname." SET receber_newsletter='n' WHERE ".$this->recipientMailField."=\"".cleanString($mail)."\"";
		$this->parent->dbo->simpleQuery($sql);
	}

	function hit($mail,$nid) {
		// the fake image in the newsletter points here, which will report that $mail has read $nid newsletter
		$nls = $this->parent->loaded('bi_NEWSLETTER_SEND');
		$sql = "SELECT recipients_received FROM ".$nls->dbname." WHERE id=$nid";
		$mr = $this->parent->dbo->fetch($sql);
		if ($mr !== false) {
			if ($mr != '') $mr = unserialize($mr);
			else $mr = array();
			if (!isset($mr[$mail])) { // this is faster than using in_array
				$mr[$mail] = true;
				$mr = serialize($mr);
				$sql = "UPDATE ".$nls->dbname." SET recipients_received=\"".addslashes($mr)."\" WHERE id=$nid";
				$this->parent->dbo->simpleQuery($sql);
			}
		}
	}

	function cbsend(&$template, &$params, $data, $processed = false) {
		$total = @unserialize($data['recipients']);
		if ($total !== false) {
			$total = count($total);
			if ($total == 0) $total = 1;
			$percentagem = $data['progress']/$total;
			$data['progress_title'] = ceil(100*$data['progress']/$total)."%";
			$data['progress'] = "<div style='width:100px;background:#ffffff;height:8px;padding:1px;border:1px solid #000000'><div style='float:left;width:".(ceil(100*$percentagem))."px;height:8px;background:#3030ff'></div></div>";
		}
		return $data;
	}

}

