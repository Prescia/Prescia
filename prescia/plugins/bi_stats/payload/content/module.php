<?php

/** @var CPrescia $core Runtime payload context injected by the framework. */
/** @var mod_bi_stats $this Runtime module context injected by the framework. */

		$graphWidth = 200;
		$core->loadAllmodules();

	# Locate public pages related to a single module from the publicPages settings (ignore those with no parameters)
	# This only works with simple-ID modules, always use the ID format for publicPages, do not use friendly url or it won't appear here

	$moduleToPage = array();

		foreach ($this->parent->modules as $name => $module) {
		if (isset($module->options[CONS_MODULE_PUBLIC]) && $module->options[CONS_MODULE_PUBLIC] != "" && strpos($module->options[CONS_MODULE_PUBLIC],"?") !== false && count($module->keys)==1) {
			$page = explode("?",$module->options[CONS_MODULE_PUBLIC]); # remove parameters
			if (strpos($page[1],$module->keys[0]."={".$module->keys[0]."}")!==false) { # the key provided is really this module's key?
				$page = explode(".",$page[0]); # remove extension
				$page = $page[0]; # raw page
				if ($page != "")
					$moduleToPage[$name] = $page[0]=="/"?substr($page,1):$page;
			}
		}
	}

		$obj = $this->parent->template->get("_modules");
	$temp = "";
	$selmod = isset($_REQUEST['stm'])?$_REQUEST['stm']:'';
	foreach ($moduleToPage as $name => $page) {
		$dados = array('name' => $name,
					   'selected' => $name == $selmod ? 1 : 0);
		$temp .= $obj->techo($dados);
	}
		$this->parent->template->assign("_modules",$temp);

	$max = 1;
	if ($selmod != "") {
		$stats = array();
			$statsh = $core->loaded('STATSDAILY');
			$statmod = $core->loaded($selmod);
		$page = $moduleToPage[$selmod];
		$sql = "SELECT sum(s.hits) as hits,s.hid as id,".$statmod->name.".".$statmod->title." as titulo
				FROM ".$statsh->dbname." as s, ".$statmod->dbname." as ".$statmod->name."
			    WHERE s.page=? AND
					  s.data>NOW()-INTERVAL 1 MONTH AND
					  ".$statmod->name.".".$statmod->keys[0]."=s.hid
				GROUP BY s.hid";
		$r = false;
		$n = 0;
			$this->parent->dbo->queryPrepared($sql,"s",array($page),$r,$n);
		for ($c=0;$c<$n;$c++) {
				$data = $this->parent->dbo->fetch_assoc($r);
			$data['page'] = $page;
			$stats[] = $data;
			if ($data['hits']>$max) $max = $data['hits'];
		}

		function cmp($a, $b) {
	    	if ($a['hits'] == $b['hits']) {
	    		return 0;
	    	}
	    	return ($a['hits'] < $b['hits']) ? 1 : -1;
		}
		usort($stats,'cmp');

		$min = ceil($max/20);

		$temp = "";
		$obj = $this->parent->template->get("_item");
		foreach ($stats as $statdata) {
			if ($statdata['hits'] <= $min) break;
			$dados = array('titulo' => $statdata['titulo'],
						   'hits' => $statdata['hits'],
						   'page' => $statdata['page'],
						   'id' => $statdata['id'],
						   'width' => ceil($graphWidth * $statdata['hits'] / $max)
						   );
			$temp .= $obj->techo($dados);

		}
		$this->parent->template->assign("_item",$temp);
		$this->parent->template->assign("max",$max);
		$this->parent->template->assign("min",$min);
	} else {
		$this->parent->template->assign("_moduleSelected","");
	}






?>
