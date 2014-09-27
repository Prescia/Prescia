<?	# -------------------------------- Aff scripts, all plugins inherit this

class mod_bi_poll extends CscriptedModule  {

	var $templatePoll = ""; // file with template to show Poll (leave blank for default)
	var $templateResults = ""; // file with template to show Results (leave blank for default)


	function __construct(&$parent,$moduleRelation="") {
		$this->parent = &$parent; // framework object
		$this->moduleRelation = $moduleRelation;
		$this->loadSettings();
	}

	function loadSettings() {
		$this->name = "bi_poll";
		#$this->parent->onMeta[] = $this->name;
		#$this->parent->onActionCheck[] = $this->name;
		#$this->parent->onRender[] = $this->name;
		$this->parent->on404[] = $this->name;
		#$this->parent->onShow[] = $this->name;
		#$this->parent->onEcho[] = $this->name;
		#$this->parent->onCron[] = $this->name;
		#$this->parent->registerTclass($this,'');
		$this->customFields = array("answers","results");

	}

	function onMeta() {
		# Run this function during meta-load (debugmode >>ONLY<<)
		###### -> Construct should add this module to the onMeta array
	}

	function on404($action, $context = "") {
		if ($this->parent->layout == 2 && $this->parent->context_str == "/" && $this->parent->action == "bi_poll") {
			$this->doNotLogMe = true;
			if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
				if (isset($_REQUEST['vote'])) $this->voteOnPoll($_REQUEST['id'],$_REQUEST['vote']);
				$result = $this->echoPoll($_REQUEST['id'],true);
				$result->assign("_removeonpopup");
				echo $result->techo();
			} else
				echo "404";
			$this->parent->close(true);
		}
		return false;
	}

	function edit_parse($action,&$data) {
		# happens before runAction so the personalized system can fix informations on this field (only for INSERT and UPDATE)
		# return TRUE if data is ready for runAction, FALSE on error or permission denied
		if (isset($data['answers'])) $data['answers'] = str_replace(",,",",",trim($data['answers']));
		return true;
	}

	function field_interface($field,$action,&$data) { // REMEMBER: fields must be declared in the construct, at customFields array
		# checks if this field should be displayed differently or not at all on an administrative enviroment
		# return TRUE to use default interface, FALSE not to display or the STRING that will replace the area
		if ($field=='answers') {
			$tp = new CKTemplate($this->parent->template);
			$tp->tbreak(cReadFile(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/answers_template.html"));
			return $tp->techo($data);
		} else if ($field=='results') {
			if ($action) return false;
			else {
				$template = new CKTemplate($this->parent->template);
				$template->tbreak(cReadFile(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/results_template.html"));
				$template->fill($data);
				$answers = explode("\n",$data['answers']);
				$results = unserialize($data['results']);
				if ($results === false) $results = array();
				$total = 0;
				foreach ($results as $vid => $c)
					$total += $c;
				$template->assign("total",$total);
				if ($total == 0) $total = 1;
				$tp = $template->get("_answers");
				$temp = "";
				$validVote = 0;
				foreach ($answers as $a) {
					if ($a != '') {
						$aData = array('#' => $validVote,
								'percent' => isset($results[$validVote])?ceil(100*$results[$validVote]/$total):0,
								'votes' =>isset($results[$validVote])?$results[$validVote]:0,
								'answer' => $a);
						$temp .= $tp->techo($aData);
						$validVote++;
					}
				}
				$template->assign("_answers",$temp);
				return $template->techo();
			}
		}
		return true;
	}

	function voteOnPoll($pollId,$options) {
		if (!is_array($options)) $options = array($options);
		$mod = $this->parent->loaded($this->moduleRelation);
		$results = "SELECT results FROM ".$mod->dbname." WHERE id=\"$pollId\"";
		$results = $this->parent->dbo->fetch($results);
		if ($results !== false) {
			$results = $results != ''?unserialize($results):array();
			foreach ($options as $opt) {
				if (isset($results[$opt]))
					$results[$opt]++;
				else
					$results[$opt] = 1;
			}
			$aData = array('id' => $pollId,
						'results' => serialize($results));
			$this->parent->safety = false;
			$mod->runAction(CONS_ACTION_UPDATE,$aData,true,true);
			$this->parent->safety = true;
			setcookie("voted".$pollId,"true",time()+84600);
		}
	}

	function getValidPoll() {
		// returns the Id for a valid poll
		$mod = $this->parent->loaded($this->moduleRelation);
		$sql = "SELECT id FROM ".$mod->dbname." WHERE date_start < NOW() AND date_end > NOW() ORDER BY ordem ASC, date_created ASC LIMIT 1";
		return $this->parent->dbo->fetch($sql);
	}

	function echoPoll($pollId,$forceResults=false) {
		/* returns the template for a poll
		 * $forceResults will make the results be shown, instead of auto-detecting
		 */
		$mod = $this->parent->loaded($this->moduleRelation);
		$template = new CKTemplate($this->parent->template);
		if ($this->parent->dbo->query("SELECT * FROM ".$mod->dbname." WHERE id=\"$pollId\"",$r,$n) && $n>0) {
			$data = $this->parent->dbo->fetch_assoc($r);
			// has the user voted?
			$hasVoted = isset($_COOKIE['voted'.$pollId]);
			$isMultiple = $data['accept_multiple'] == 'y'; #TODO: implement multiple options (different template)
			if ($hasVoted || $forceResults) {
				if ($this->templateResults == "") { // show RESULTS, show default
					$template->tbreak(cReadFile(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/defaultr_template.html"));
				} else { // show RESULTS, show templateResults
					if (!is_file($this->templateResults)) $this->parent->log[] = "Poll file (results) not found";
					$template->tbreak(cReadFile($this->templateResults));
				}
				$template->fill($data);
				$answers = explode("\n",$data['answers']);
				$results = unserialize($data['results']);
				if ($results === false) $results = array();
				$total = 0;
				foreach ($results as $vid => $c)
					$total += $c;
				$template->assign("total",$total);
				if ($total == 0) $total = 1;
				$tp = $template->get("_answers");
				$temp = "";
				$validVote = 0;
				foreach ($answers as $a) {
					if ($a != '') {
						$aData = array('#' => $validVote,
									   'percent' => isset($results[$validVote])?ceil(100*$results[$validVote]/$total):0,
										'votes' =>isset($results[$validVote])?$results[$validVote]:0,
									   'answer' => $a);
						$temp .= $tp->techo($aData);
						$validVote++;
					}
				}
				$template->assign("_answers",$temp);
				return $template;
			} else if ($this->templatePoll == "") { // show POLL, use default
				$template->tbreak(cReadFile(CONS_PATH_SYSTEM."plugins/".$this->name."/payload/default_template.html"));
			} else { // show POLL, use templatePoll
				if (!is_file($this->templatePoll)) {
					$this->parent->log[] = "Poll file not found";
					return "";
				}
				$template->tbreak(cReadFile($this->templatePoll));
			}
			$template->fill($data);
			$answers = explode("\n",$data['answers']);
			$tp = $template->get("_answers");
			$temp = "";
			$validVote = 0;
			foreach ($answers as $a) {
				if ($a != '') {
					$aData = array('#' => $validVote,
								   'answer' => $a);
					$temp .= $tp->techo($aData);
					$validVote++;
				}
			}
			$template->assign("_answers",$temp);
			return $template;
		} else {
			$template->tbreak("404");
			return $template;
		}
	}

}