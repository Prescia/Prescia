<?
	$ok = false;
	if (isset($_REQUEST['haveinfo'])) {
		$step = $this->dimconfig['presciastage'];
		$this->loadAllmodules(); // we need all of them so plugin on presciamkey (presciacounter) works

		switch ($step) {
			case 'start': # just created
			
				$ok = $this->dimconfig['i_am_alive'] == 'presciacounter plugin is alive <3';
				if (!$ok) {
					$this->log [] = "<b>ERROR: Plugin presciacounter was not loaded properly!</b>";
					break;
				}
				
				$this->authControl->logUser(1,CONS_AUTH_SESSION_NEW);
				# some alpha
				$this->log[] = "Alpha 1";
				$data = array('id_parent' => 0,
							  'title' => 'root 1');
				$ok = $this->runAction('presciaalpha',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$root1 = $this->lastReturnCode;
				$this->log[] = "Alpha 2";
				$data = array('id_parent' => 0,
							  'title' => 'root 2');
				$ok = $this->runAction('presciaalpha',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$root2 = $this->lastReturnCode;
				$this->log[] = "Alpha 3";
				$data = array('id_parent' => $root2,
							  'title' => 'child of root 2');
				$ok = $this->runAction('presciaalpha',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$root3 = $this->lastReturnCode;
				# some beta
				$this->log[] = "Beta 1";
				$data = array('id_nested' => $root1,
							  'title' => 'nested inside root 1');
				$ok = $this->runAction('presciabeta',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$beta1 = $this->lastReturnCode;
				$this->log[] = "Beta 2 (play with escapes)";
				$data = array('id_nested' => $root3,
							  'title' => 'nested inside child 2 "quoted" \"escapequoted\" \'single quoted\'');
				$ok = $this->runAction('presciabeta',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$beta2 = $this->lastReturnCode;
				# linkers
				$data = array('id_a' => $root1,
							  'id_b' => $beta1);
				$this->log[] = "Link 1";
				$ok = $this->runAction('prescialinker',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$data = array('id_a' => $root2,
							  'id_b' => $beta2);
				$this->log[] = "Link 2";
				$ok = $this->runAction('prescialinker',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$data = array('id_a' => $root2,
							  'id_b' => $beta1);
				$this->log[] = "Link 3";
				$ok = $this->runAction('prescialinker',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				# TOR
				$this->log[] = "Tor 1";
				$data = array(
				'alpha' => 'key',
				'beta' => 1,
				'title' => 'A key',
				'makemefamous' => 'y',
				'randomtext' => '<br>some random data</br><b>bold</b>',
				'randomhtml' => '<div style="float:left">lefty</div><hr/>',
				'randomhtmllt' => '<br>no complex stuff here <div style="float:left"><ul><li>be gone<iframe src="bla"/></li></ul></div><hr/>',
				'leavemealone' => '<frame><body>{}',
				'autourl' => 'onekey', // should obbey
				'ignoreme' => 'keepme',
				'sosmall' => 100,
				'id_someone' => 1, // master
				'id_author' => 2, // admin
				'onlyonchave' => 'hehehehe',
				'readmeonly' => 'hu',
				'getawaylowly' => 0.5,
				'showmaoptions' => '101',
				'id_alpha' => '', // no error
				'id_beta' => '', // no error
				'oneofakind' => 1,
				'mylanguage' => $_SESSION[CONS_SESSION_LANG]);

				$_FILES['somefile'] = array( 'error'=>0, 'tmp_name' => "robots.txt", 'virtual'=>true, 'name'=> "robots.txt"	);
				$_FILES['someimage'] = array( 'error'=>0, 'tmp_name' => CONS_PATH_PAGES.$_SESSION['CODE']."/files/prescia.png", 'virtual'=>true, 'name'=> "prescia1.png"	);
				$_FILES['conditionedimage'] = array( 'error'=>0, 'tmp_name' => CONS_PATH_PAGES.$_SESSION['CODE']."/files/prescia.png", 'virtual'=>true, 'name'=> "prescia2.png"	);

				$ok = $this->runAction('presciator',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->log[] = "Tor 2";
				$data = array(
				'alpha' => 'key',
				'beta' => 2,
				'title' => 'Another (infamous) key',
				'makemefamous' => 'n',
				'randomhtml' => '<div style="float:right">righty</div><hr/>',
				'randomtext' => '<br>some random data</br><b>bold</b>',
				'randomhtmllt' => '<br data-something="garbage">no <button name="tester">button</button> complex stuff here <div style="float:left"><ul><li>be gone<iframe src="bla"/></li></ul></div><hr/>',
				'leavemealone' => '<frame><body>{}',
				'ignoreme' => 'keepmetoo',
				'manualurl' => 'another-infamous-key-key-datehaha', // override!?
				'sosmall' => 1,
				'id_someone' => 2,
				'id_author' => 2,
				'onlyonchave' => 'hsdfsdfsdfa',
				'getawaylowly' => 1.5,
				'showmaoptions' => '010',
				'id_alpha' => $root1,
				'id_beta' => $beta1,
				'oneofakind' => 2,
				'mylanguage' => $_SESSION[CONS_SESSION_LANG]);
				
				$ok = $this->runAction('presciator',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->log[] = "Tor 3";
				$data = array(
				'alpha' => 'chave',
				'beta' => 1,
				'title' => 'Oh my, chave?',
				'makemefamous' => 'y',
				'randomtext' => 'yadda',
				'randomhtml' => 'so simple',
				'randomhtmllt' => 'yodda <code>code</code>',
				'leavemealone' => '{}{}',
				'ignoreme' => 'keepmethree',
				'sosmall' => 0,
				'id_someone' => 1,
				'id_author' => 1,
				'onlyonchave' => '00',
				'readmeonly' => 'he',
				'getawaylowly' => 1.5,
				'showmaoptions' => '000',
				'id_alpha' => $root3,
				'id_beta' => $beta2,
				'oneofakind' => 3,
				'mylanguage' => $_SESSION[CONS_SESSION_LANG]);

				unset($_FILES['somefile']);
				unset($_FILES['someimage']);
				$_FILES['conditionedimage'] = array( 'error'=>0, 'tmp_name' => CONS_PATH_PAGES.$_SESSION['CODE']."/files/prescia.png", 'virtual'=>true, 'name'=> "prescia3.png"	);

				$ok = $this->runAction('presciator',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->log[] = "Tor 4 (play with tags and quotes)";
				$data = array(
				'alpha' => 'kage',
				'beta' => 4,
				'title' => 'Sore wa kage desu',
				'makemefamous' => 'y',
				'randomtext' => 'yadda',
				'randomhtml' => '<b><i>html!</i></b>(\")',
				'randomhtmllt' => 'yodda(")(\")',
				'leavemealone' => '{}{}[][]"',
				'ignoreme' => 'keepmefooour',
				'sosmall' => 50,
				'id_someone' => 0,
				'id_author' => 1,
				'onlyonchave' => 'hu',
				'getawaylowly' => 222222,
				'showmaoptions' => '111',
				'id_alpha' => $root2,
				'id_beta' => '',
				'oneofakind' => 4,
				'mylanguage' => $_SESSION[CONS_SESSION_LANG]);

				unset($_FILES['somefile']);
				unset($_FILES['someimage']);
				$_FILES['conditionedimage'] = array( 'error'=>0, 'tmp_name' => CONS_PATH_PAGES.$_SESSION['CODE']."/files/prescia.png", 'virtual'=>true, 'name'=> "prescia4.png"	);
				$ok = $this->runAction('presciator',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				# a moron group
				$this->log[] = "Group WD40";
				$data = array('name' => "WD40",
							  'level' => 40,
							  'active' => 'y'
						);
				$ok = $this->runAction('groups',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$gwd4 =  $this->lastReturnCode;
				# a moron user
				$this->log[] = "WD40 user";
				$data = array(
					'id_group' => $gwd4,
					'name' => "I am a WD40 user",
					'email' => "dont.care@dont.see",
					'login' => "wd40user",
					'password' => "wd40user",
					'active' => 'y'
						);
				$ok = $this->runAction('users',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$gwd4u =  $this->lastReturnCode;
				# tor with that poor user
				$this->log[] = "Tor 5 (wd40 user)";
				$data = array(
				'alpha' => 'kage',
				'beta' => 2,
				'title' => 'Sore wa kage desu - matta',
				'makemefamous' => 'n',
				'randomtext' => 'yadda',
				'randomhtmllt' => 'yodda',
				'leavemealone' => '{}{}[][]"',
				'ignoreme' => 'keepmefooour',
				'sosmall' => 5,
				'id_someone' => 0,
				'id_author' => $gwd4u,
				'onlyonchave' => 'he',
				'getawaylowly' => 231,
				'showmaoptions' => '110',
				'id_alpha' => $root1,
				'id_beta' => '',
				'oneofakind' => 23,
				'mylanguage' => $_SESSION[CONS_SESSION_LANG]);

				unset($_FILES['somefile']);
				unset($_FILES['someimage']);
				$_FILES['conditionedimage'] = array( 'error'=>0, 'tmp_name' => CONS_PATH_PAGES.$_SESSION['CODE']."/files/prescia.png", 'virtual'=>true, 'name'=> "prescia5.png"	);

				$ok = $this->runAction('presciator',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				# lets make some multiple links
				$this->log[] = "Multiple linker MKEY 1 with file";
				$data = array(
					'id_tor' => 'key',
					'id_tor_beta' => 1,
					'hithere' => 'yes, you there',
					'changesintor' => 0);
				$_FILES['someimage'] = array( 'error'=>0, 'tmp_name' => CONS_PATH_PAGES.$_SESSION['CODE']."/files/prescia.png", 'virtual'=>true, 'name'=> "prescia1.png"	);
				$ok = $this->runAction('presciamkey',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				
				$this->log[] = "Multiple linker MKEY 2 with file";
				$data = array(
					'id_tor' => 'key',
					'id_tor_beta' => 2,
					'hithere' => 'stay there',
					'changesintor' => 0);
				$ok = $this->runAction('presciamkey',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				unset($_FILES['someimage']);
				$this->log[] = "Multiple linker MKEY 3";
				$data = array(
					'id_tor' => 'chave',
					'id_tor_beta' => 1,
					'hithere' => 'linking much?',
					'changesintor' => 0);
				$ok = $this->runAction('presciamkey',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->log[] = "Multiple linker MKEY 4";
				$data = array(
					'id_tor' => 'kage',
					'id_tor_beta' => 4,
					'hithere' => 'final edit? ofc not',
					'changesintor' => 0);
				$ok = $this->runAction('presciamkey',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				# now lets use some CMS
				$this->log[] = "ROOT cms 'hello.html'";
				$data = array(
					'code' => 1,
					'page' => 'hello',
					'lang' => $_SESSION[CONS_SESSION_LANG],
					'title' => "HELLO",
					'header' => "CMS HELLO",
					'content' => "Good bye, and thanks for all the fish 'single quoted'",
					'publish' => 'y',
					'locked' => 'y');
				$ok = $this->runAction('contentman',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->log[] = "Virtual folder cms 'magic/duh.html'";
				$data = array(
					'code' => 1,
					'page' => 'magic/duh', // this is wrong on purpose
					'lang' => $_SESSION[CONS_SESSION_LANG],
					'title' => "DUH",
					'header' => "CMS DUH",
					'content' => "This was written by a member of the third most intelligent race on the planet",
					'publish' => 'y',
					'locked' => 'y');
				$ok = $this->runAction('contentman',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				# SEO much?
				$this->log[] = "SEO to index called home";
				$data = array('page' => 'index',
							'alias' => 'home',
							'lang' => $_SESSION[CONS_SESSION_LANG],
							'title' => "you have been SEO'ed",
							'publicar' => 'y');
				$ok = $this->runAction('SEO',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->log[] = "SEO to root CMS";
				$data = array('page' => 'hello',
							'alias' => 'vhello',
							'lang' => $_SESSION[CONS_SESSION_LANG],
							'title' => "SEO Hello",
							'publicar' => 'y');
				$ok = $this->runAction('SEO',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->log[] = "ROOT SEO to virtual CMS";
				$data = array('page' => '/magic/duh',
							'alias' => 'vduh',
							'lang' => $_SESSION[CONS_SESSION_LANG],
							'title' => "SEO DUH",
							'publicar' => 'y');
				$ok = $this->runAction('SEO',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->log[] = "Virtual SEO to index";
				$data = array('page' => 'index',
							'alias' => '/magic/gohome',
							'lang' => $_SESSION[CONS_SESSION_LANG],
							'title' => "Back from the SEO",
							'publicar' => 'y');
				$ok = $this->runAction('SEO',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->log[] = "Crazy SEO to a crazy UDM/FML";
				$data = array('page' => 'onekey/another-infamous-key-key-datehaha.html',
							'alias' => 'seome',
							'lang' => $_SESSION[CONS_SESSION_LANG],
							'title' => "If this works we are officialy nuts",
							'publicar' => 'y');
				$ok = $this->runAction('SEO',CONS_ACTION_INCLUDE,$data);
				if ($ok) {
					// automatic log should have set we are in deep trouble for some "warnings", but they were expected, so success
					$this->setLog(CONS_LOGGING_SUCCESS,"",true);
					$this->log[] = "------------------------------------------------------------------------------";
					$this->log[] = "<b>PLEASE SET ALL PERMISSIONS TO TRUE ON 'PRESCIATOR' FOR WD40 GROUP BEFORE CONTINUING</b>";
					$this->log[] = "Remember the admin is at /admin/, and password should be master / <b>presciatester{day}</b>";
					$this->log[] = "------------------------------------------------------------------------------";
					$this->dimconfig['presciastage'] = 'creation';
					$this->saveConfig();
				}
			break;
			case 'creation': # just main fill, WITH ERRORS!
			
			
				$this->authControl->logUser(1,CONS_AUTH_SESSION_NEW); // log master
				$ok = true;
				$this->log[] = "Alpha error: no title (expected error: 127)";
				$data = array('id_parent' => 0,
							  'title' => '');
				$ok = !$this->runAction('presciaalpha',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->errorState = false;
				$this->log[] = "Alpha error: ciclic parent (expected error: 128)";
				$id = $this->dbo->fetch("SELECT id FROM dba LIMIT 1");
				$data = array('id_parent' => $id,
							  'id' => $id,
							  'title' => 'this should be interesting');
				$ok = $this->runAction('presciaalpha',CONS_ACTION_UPDATE,$data); // aborts id_parent but runs
				if (!$ok) break;
				$this->errorState = false;
				$this->log[] = "Beta error: mandatory nest set to nothing (expected error: 127)";
				$data = array('id_nested' => '',
							  'title' => 'nested inside nothing');
				$ok = !$this->runAction('presciabeta',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->errorState = false;
				$this->log[] = "Beta error: mandatory nest not set (expected error: 127)";
				$data = array('title' => 'nested inside null');
				$ok = !$this->runAction('presciabeta',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->errorState = false;
				$this->log[] = "Linker error: wtf a link is missing! (expected error: 127)";
				$data = array('id_a' => $id);
				$ok = !$this->runAction('prescialinker',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->errorState = false;
				if (!$this->dbo->query("SELECT alpha,beta,oneofakind FROM dbp LIMIT 2",$r,$n) || $n !=2) {
					$this->log[] = "FAILED to select 2 Presciator";
					$ok = false;
					break;
				}
				list($a1,$b1,$o1) = $this->dbo->fetch_row($r);
				list($a2,$b2,$o2) = $this->dbo->fetch_row($r);
				unset($r);
				
				$this->log[] = "Fun with TOR 1: overflow fields (expected error: 136, and DBO raw output)";
				$data = array('alpha' => $a1,
							  'beta' => $b1,
							  'sosmall' => 300, // max should be 127
							  'onlyonchave' => '12345678901234567890this is overvlow', // set to 20 max
							  );
				$ok = !$this->runAction('presciator',CONS_ACTION_UPDATE,$data);
				if (!$ok) break;
				$this->errorState = false;
				
				$this->log[] = "Fun with TOR 2: duplicate unique key (expected error: 137)";
				$data = array('alpha' => $a2,
							  'beta' => $b2,
							  'oneofakind' => $o1
							  );
				$ok = !$this->runAction('presciator',CONS_ACTION_UPDATE,$data);
				if (!$ok) break;
				$this->errorState = false;
				
				$this->log[] = "Fun with TOR 3: invalid file upload type (expected error: 202)";
				$_FILES['somefile'] = array( 'error'=>0, 'tmp_name' => CONS_PATH_PAGES.$_SESSION['CODE']."/files/prescia.png", 'virtual'=>true, 'name'=> "prescia1.png"	);
				$data = array('alpha' => $a2,
							  'beta' => $b2);
				$ok = $this->runAction('presciator',CONS_ACTION_UPDATE,$data); // will update what it can (aka nothing)
				unset($_FILES['somefile']);
				if (!$ok) break;
				$this->errorState = false;
				
				$this->log[] = "Logging in with low-level user";
				$uid = $this->dbo->fetch("SELECT id FROM auth_users WHERE login='wd40user'");
				if ($uid === false) {
					$ok = false;
					$this->log[] = "Failed to fetch wd40 user";
					return;
				}
				$this->authControl->logUser($uid,CONS_AUTH_SESSION_NEW);
				
				$this->log[] = "Fun with TOR 4: trying to edit fields only higher levels can (expected error: 145)";
				$data = array('alpha' => $a1,
							  'beta' => $b1,
							  'getawaylowly' => 100,
							  );
				$ok = $this->runAction('presciator',CONS_ACTION_UPDATE,$data); // will succeed, ignoring getawaylowly
				$this->errorState = false;
				
				$this->log[] = "Permission denied test (if wd40 has no permission to presciaalpha) (expected errors: 150 + 306)";
				$data = array('id_parent' => 0,
							  'title' => 'not going to happen pal');
				$ok = !$this->runAction('presciaalpha',CONS_ACTION_INCLUDE,$data);
				$this->errorState = false;
				
				
			
				
				// should have counted one change on each $a1+$b1, $a2+$b2 and the edit to delete key+1 somefile
				
				
				
				
				$this->log[] = "Logging off ...";
				$this->authControl->logsGuest();
				
				if ($ok) {
					// automatic log should have set we are in deep trouble for all the errors, set we are not, they were expected
					$this->setLog(CONS_LOGGING_SUCCESS,"",true);
					$this->dimconfig['presciastage'] = 'pass1';
					$this->saveConfig();
				}
				
			break;
			case 'pass1': // test file management
			
				$ok = true;	
				//order to delete a specific file field from presciator ... should NOT delete all fields  
				// alpha=key&beta=1& should have ALL files

				$this->log[] = "Logging in with low-level user";
				$uid = $this->dbo->fetch("SELECT id FROM auth_users WHERE login='wd40user'");
				if ($uid === false) {
					$ok = false;
					$this->log[] = "Failed to fetch wd40 user";
					return;
				}
				$this->authControl->logUser($uid,CONS_AUTH_SESSION_NEW); // log wd40
				
				// are files there?
				$f = CONS_FMANAGER."presciator/somefile_key_1_1";
				$yes1 = locateAnyFile($f,$e); // any file because it is a txt
				$f = CONS_FMANAGER."presciator/someimage_key_1_1";
				$yes2 = locateFile($f,$e);
				$f = CONS_FMANAGER."presciator/conditionedimage_key_1_1";
				$yes3 = locateFile($f,$e); // should be 100x100
				$f = CONS_FMANAGER."presciator/conditionedimage_chave_1_1";
				$yes3 = locateFile($f,$e2); // should be 200x200
				
				if ($yes1 && $yes2 && $yes3) {
					$this->log[] = "File management test on presciator. All files detected - ok";
					
					$h = getimagesize(CONS_FMANAGER."presciator/conditionedimage_key_1_1.".$e);
					$h2 = getimagesize(CONS_FMANAGER."presciator/conditionedimage_chave_1_1.".$e2);
					if ($h[0] !== 100 || $h[1] != 100 || $h2[0] !== 200 || $h[2] != 200) {
						$this->log[] = "<b>ERROR</b>: conditioned file-size were cropped to wrong size (tested key_1 and chave_1 conditionedimages)";
						$ok = false;
					}
					else $this->log[] = "Conditioned file-size seems working";
					
					$this->log[] = "Testing delete a file, no error expected";
					$data = array('alpha' => 'key',
							  'beta' => 1,
							  'somefile_delete' => 'on'
							  );
					$ok = $this->runAction('presciator',CONS_ACTION_UPDATE,$data); // will succeed, and delete somefile
					$this->errorState = false;
					
					clearstatcache();
					
					$f = CONS_FMANAGER."presciator/somefile_key_1_1";
					$yes1 = locateAnyFile($f,$e);
					$f = CONS_FMANAGER."presciator/someimage_key_1_1";
					$yes2 = locateFile($f,$e);
					
					if (!$yes1 && $yes2) $this->log[] = "Success, file has been deleted, other file fields intact!";
					else {
						$this->log[] = "<b>ERROR</b>: ".($yes1?"File was not deleted!":"").(!$yes2?"Other files were also deleted, should only delete one field!":"");
						$ok = false;
					}
					
				} else {
					$this->log[] = "<b>ERROR</b>: some files on presciator key_1 were not found, all should be present:".($yes1?"somefile ok,":"somefile missing,").($yes2?"someimage ok,":"someimage missing,").($yes3?"conditionedimage ok.":"conditionedimage missing.");
					$ok = false;
				}
			
				$this->log[] = "Logging off ...";
				$this->authControl->logsGuest();
				
				if ($ok) {
					// automatic log should have set we are in deep trouble for all the errors, set we are not, they were expected
					$this->setLog(CONS_LOGGING_SUCCESS,"",true);
					$this->dimconfig['presciastage'] = 'pass2';
					$this->saveConfig();
				}
				
			break;
			case 'pass2': // undo test 
				$ok = true;
			
				$this->authControl->logUser(1,CONS_AUTH_SESSION_NEW);
				
				
			
				if ($ok) {
					$this->dimconfig['presciastage'] = 'pass3';
					$this->saveConfig();
				}
			break;
			case 'pass3': // end (delete all)
				$ok = true;
				
				$this->log[] = "This is the end dude";
				$this->dimconfig['presciastage'] = 'end';
				$this->saveConfig();
			break;

		}
		if ($ok) {
			$this->log[]= "Done done, all working so far";
		} else {
			$this->log[]= "Something is wrong. Check the error, fix it, reset the test and try again";
		}
	}
	$this->action = "index";