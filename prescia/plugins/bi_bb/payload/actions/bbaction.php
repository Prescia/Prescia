<?
	
	// safety: are we logged to perform actions? if not, kick out to 403 (unless you are registering)
	// we test post include (self) because that's the most basic permission
	if (!$core->authControl->checkPermission('FORUMPOST',CONS_ACTION_INCLUDE,true) && $_POST['bbaction'] != 'profile')
		$core->fastClose(403); // no permission, bye
	
	// yes but do we have an action to perform anyway?
	if (!$core->queryOk(array("haveinfo","bbaction")))
		$core->fastClose(404); // no sir
	
	// ok let's get to work
	switch ($_POST['bbaction']) {
		case 'tpreview': // preview a thread
			if (!$core->queryOk(array("#id_forum","ftitle","fmessage"))) {
				$core->action = "index";
				$core->log[] = "Error on preview";
				break;
			}
			$core->action = "preview"; // send me to preview screen (same for both)
		break;
		case 'preview': // preview a post
			if (!$core->queryOk(array("#id_forumthread","#id_forum","fmessage"))) {
				$core->action = "index";
				$core->log[] = "Error on preview";
				break;
			}
			$core->action = "preview"; // send me to preview screen (same for both)
		break;
		case 'tpost': // post thread
			if (!$core->queryOk(array("#id_forum","ftitle","fmessage"))) {
				$core->action = "index";
				$core->log[] = "Error on post";
				break;
			}
			$postData = array('id_forum' => $_POST['id_forum'],
							  'title' => $_POST['ftitle'],
							  );
			$ok = $core->runAction('forumthread',CONS_ACTION_INCLUDE,$postData);
			if (!$ok) {
				$this->action = "forum";
				$core->log[] = "Error adding Thread";
				break;
			} else {
				$this->parent->action = $this->parent->storage['lastactiondata']['urla'];
				$_POST['id_forumthread'] = $this->parent->lastReturnCode;
			}
			// no break: continue on to add post
		case 'post': // post a comment
			if (!$core->queryOk(array("#id_forum","#id_forumthread","fmessage"))) {
				$core->action = "index";
				$core->log[] = "Error on post";
				// fail to post comment but thread created ... destroy thread
				if ($_POST['bbaction'] =='tpost') $core->simpleQuery("DELETE FROM bb_thread WHERE id=".$_POST['id_forumthread']);
				break;
			}
			$postData = array('id_forum' => $_POST['id_forum'],
							  'id_forumthread' => $_POST['id_forumthread'],
							  'content' => $_POST['fmessage'],
							  'id_author' => $_SESSION[CONS_SESSION_ACCESS_USER]['id'],
							  'props' => serialize(array()));
			$ok = $core->runAction('forumpost',CONS_ACTION_INCLUDE,$postData);
			if ($ok)
				$core->headerControl->internalFoward($_POST['url']."?lastpage=true");
			else {
				// fail to post comment but thread created ... destroy thread
				if ($_POST['bbaction'] =='tpost') $core->simpleQuery("DELETE FROM bb_thread WHERE id=".$_POST['id_forumthread']);
				$core->log[] = "Error adding Post";
			}
		break;	
		case 'edit': // edit a comment
		break;
		case 'delete': // delete a comment (delete first comment of a thread to delete it all)
		break;
		case 'flag': // flag a comment
		break;
		case 'propup': // prop up a comment
		break;
		case 'propdown': // prop down a comment
		break;
		case "profile":
			if ($core->logged()) $up = $_SESSION[CONS_SESSION_ACCESS_USER]['userprefs'];
			else $up = array();
			$up['pfim'] = $_POST['ipp'];
			$up['lang'] = $_POST['lang'];
			$data = array(
				'name' => $_POST['name'],
				'email' => $_POST['email'],
				'login'=> isset($_POST['ulogin'])?$_POST['ulogin']:'',
				'password' => $_POST['upassword'],
				'userprefs' => serialize($up),
				'id_group' => $this->registrationGroup
				);
			$core->safety = false; // allow to register at all costs
			if ($core->logged()) {
				$data['id'] = $_SESSION[CONS_SESSION_ACCESS_USER]['id'];
				unset($data['id_group']);
				unset($data['login']);
				if ( $_POST['upassword'] == '') unset($_POST['upassword']);
				$ok = $core->runAction('users',CONS_ACTION_UPDATE,$data);
			} else {
				$ok = $core->runAction('users',CONS_ACTION_INCLUDE,$data);
				if ($ok) {
					$id = $core->lastReturnCode;
					$core->authControl->logUser($id,CONS_AUTH_SESSION_NEW);
				}
				else $core->authControl->logsGuest();
			}
			$core->safety = true;
			$core->headerControl->internalFoward($this->contextfriendlyfolderlist[0]."profile.html?nocache=true");
		break;
	}
		