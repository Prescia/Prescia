<?
	if (isset($_REQUEST['haveinfo'])) {
		$this->dbo->simpleQuery("TRUNCATE dbp");
		$this->dbo->simpleQuery("TRUNCATE dba");
		$this->dbo->simpleQuery("TRUNCATE dbb");
		$this->dbo->simpleQuery("TRUNCATE dbmk");
		$this->dbo->simpleQuery("TRUNCATE dbl");
		$this->dbo->simpleQuery("TRUNCATE session_manager");
		$this->dbo->simpleQuery("TRUNCATE bb_forum");
		$this->dbo->simpleQuery("TRUNCATE bb_thread");
		$this->dbo->simpleQuery("TRUNCATE bb_post");
		$this->dbo->simpleQuery("TRUNCATE app_content");
		$this->dbo->simpleQuery("TRUNCATE sys_seo");
		$this->dbo->simpleQuery("TRUNCATE sys_undo");
		$this->dbo->simpleQuery("DROP TABLE auth_users,auth_groups");
		$this->dimconfig['presciastage'] = 'start';
		$this->saveConfig();
		// purge logs
		$listFiles = listFiles(CONS_PATH_LOGS,"/^([^a]).*(\.log)$/i",false,false,true);
		foreach ($listFiles as $file)
			@unlink(CONS_PATH_LOGS.$file);
		$this->action = "index";
		$this->headerControl->internalFoward("/index.html");
	}
