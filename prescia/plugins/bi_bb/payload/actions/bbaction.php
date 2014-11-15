<?

	// safety: are we logged to perform actions? if not, kick out to 403 (unless you are registering)
	// we test post include (self) because that's the most basic permission
	if (!$core->authControl->checkPermission('FORUMPOST',CONS_ACTION_INCLUDE,array(true,false,false)) && $_POST['bbaction'] != 'profile') {
		$core->action = 403;
		return;
	}

	// yes but do we have an action to perform anyway?
	if (!$core->queryOk(array("haveinfo","bbaction"))) {
		$core->action = 404;
		return;
	}

	$_REQUEST['nocache'] = true; // no caches on actions

	// ok let's get to work
	switch ($_POST['bbaction']) {
		case 'tpreview': // preview a thread
			if (!$core->queryOk(array("#id_forum","ttitle","fmessage"))) {
				$core->action = "index";
				$core->log[] = "Error on preview";
				break;
			}
			$core->action = "preview"; // send me to preview screen (same for both)
			return;
		break;
		case 'preview': // preview a post
			if (!$core->queryOk(array("#id_forumthread","#id_forum","fmessage"))) {
				$core->action = "index";
				$core->log[] = "Error on preview";
				break;
			}
			$core->action = "preview"; // send me to preview screen (same for both)
			return;
		break;
		case 'tpost': // post thread
			if (!$core->queryOk(array("#id_forum","ttitle","fmessage"))) {
				$core->action = "index";
				$core->log[] = "Error on post";
				break;
			}
			$postData = array('id_forum' => $_POST['id_forum'],
							  'title' => $_POST['ttitle'],
							  'video' => isset($_POST['video'])?$_POST['video']:'',
							  'tags' => isset($_POST['tags'])?$_POST['tags']:'',
							  'id_author' => $_SESSION[CONS_SESSION_ACCESS_USER]['id']
							  );
			$threadobj = $core->loaded('forumthread');
			if (!isset($_REQUEST['operationmode'])) { // UDM could have filled this for us
				$_REQUEST['operationmode'] = $core->dbo->fetch("SELECT operationmode FROM ".$threadobj->dbname." WHERE id=".$_POST['id_forum']);
			}
			if ($_REQUEST['operationmode'] == 'bb') {
				// on BB mode, people can't post images directly
				$_REQUEST['image_delete'] = 'checked';
				if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
					@unlink ($_FILES['image']['tmp_name']);
					unset($_FILES['image']);
				}
			}
			$ok = $core->runAction($threadobj,CONS_ACTION_INCLUDE,$postData);
			if (!$ok) {
				$core->action = "forum";
				$_REQUEST['id'] = $_POST['id_forum'];
				$core->log[] = "Error adding Thread";
				return;
			} else {
				$core->action = $core->storage['lastactiondata']['urla'];
				$_POST['url'] = $core->action;
				$_POST['id_forumthread'] = $core->lastReturnCode;
			}
			// no break: continue on to add post
		case 'post': // post a comment
			if (!$core->queryOk(array("#id_forum","#id_forumthread","fmessage"))) {
				$core->action = "index";
				$core->log[] = "Error on post";
				// fail to post comment but thread created ... destroy thread
				if ($_POST['bbaction'] =='tpost') $core->simpleQuery("DELETE FROM bb_thread WHERE id=".$_POST['id_forumthread']);
				return;
			}
			$postData = array('id_forum' => $_POST['id_forum'],
							  'id_forumthread' => $_POST['id_forumthread'],
							  'content' => $_POST['fmessage'],
							  'id_author' => $_SESSION[CONS_SESSION_ACCESS_USER]['id'],
							  'props' => serialize(array()));
			$ok = $core->runAction('forumpost',CONS_ACTION_INCLUDE,$postData);
			if ($ok) {
				// kill cache for the post, it changed!
				$core->cacheControl->killCache("postsforidt".$_POST['id_forumthread']."idf".$_POST['id_forum']."*"); // thread view
				$core->cacheControl->killCache("threadsfor".$_POST['id_forumthread']."p*"); // forum view
				$core->headerControl->internalFoward($_POST['url']."?lastpage=true");
			} else {
				// fail to post comment but thread created ... destroy thread
				if ($_POST['bbaction'] =='tpost') $core->simpleQuery("DELETE FROM bb_thread WHERE id=".$_POST['id_forumthread']);
				$core->log[] = "Error adding Post";
				$core->action = "forum";
				$_REQUEST['id'] = $_POST["id_forum"];
			}
			return;
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
			if (isset($_POST['ipp'])) $up['pfim'] = $_POST['ipp'];
			if (isset($_POST['lang'])) $up['lang'] = $_POST['lang'];
			$data = array(
				'name' => $_POST['name'],
				'email' => isset($_POST['email'])?$_POST['email']:'',
				'login'=> isset($_POST['ulogin'])?$_POST['ulogin']:'',
				'password' => $_POST['upassword'],
				'userprefs' => serialize($up),
				'id_group' => $this->registrationGroup
				);
			if ($core->logged()) {
				$data['id'] = $_SESSION[CONS_SESSION_ACCESS_USER]['id'];
				unset($data['id_group']);
				unset($data['login']);
				if ( $_POST['upassword'] == '') unset($_POST['upassword']);
				$ok = $core->runAction('users',CONS_ACTION_UPDATE,$data);
			} else {
				if ($core->tCaptcha('captcha',true)) {
					$core->safety = false; // allow to register at all costs
					$ok = $core->runAction('users',CONS_ACTION_INCLUDE,$data);
					$core->safety = true;
					if ($ok) {
						$id = $core->lastReturnCode;
						$core->authControl->logUser($id,CONS_AUTH_SESSION_NEW);
					}
					else $core->authControl->logsGuest();
				}
			}
			$core->action = "profile";
			if ($ok) $core->headerControl->internalFoward($this->bbfolder."profile.html?nocache=true");
			return;
		break;
	}
	$core->action = "503";