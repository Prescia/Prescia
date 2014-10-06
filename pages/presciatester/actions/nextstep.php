<?
	$ok = false;
	if (isset($_REQUEST['haveinfo'])) {
		$step = $this->dimconfig['presciastage'];

		switch ($step) {
			case 'start': # just created
				$ok = true;
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
				$this->log[] = "Beta 2";
				$data = array('id_nested' => $root3,
							  'title' => 'nested inside chield 2');
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
				'onlyonchave' => 'he',
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
				'onlyonchave' => 'ha',
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
				'onlyonchave' => 'ha',
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
				$this->log[] = "Tor 4";
				$data = array(
				'alpha' => 'kage',
				'beta' => 4,
				'title' => 'Sore wa kage desu',
				'makemefamous' => 'y',
				'randomtext' => 'yadda',
				'randomhtml' => '<b><i>html!</i></b>',
				'randomhtmllt' => 'yodda',
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
				$this->log[] = "Multiple linker MKEY 1";
				$data = array(
					'id_tor' => 'key',
					'id_tor_beta' => 1,
					'hithere' => 'yes, you there',
					'changesintor' => 0);
				$ok = $this->runAction('presciamkey',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
				$this->log[] = "Multiple linker MKEY 2";
				$data = array(
					'id_tor' => 'key',
					'id_tor_beta' => 2,
					'hithere' => 'stay there',
					'changesintor' => 0);
				$ok = $this->runAction('presciamkey',CONS_ACTION_INCLUDE,$data);
				if (!$ok) break;
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
					'content' => "Good bye, and thanks for all the fish",
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
					$this->dimconfig['presciastage'] = 'creation';
					$this->saveConfig();
				}
			break;
			case 'creation': # just main fill
				$ok = true;
			break;
			case 'pass1':
				$ok = true;
			break;
			case 'pass2':
				$ok = true;
			break;
			case 'passn': // error/end
				$ok = true;
				$this->log[] = "This is the end dude";
			break;

		}
		if ($ok) {
			$this->log[]= "Done done, all working so far";
		} else {
			$this->log[]= "Something is wrong. Check the error, fix it, reset the test and try again";
		}
	}
	$this->action = "index";