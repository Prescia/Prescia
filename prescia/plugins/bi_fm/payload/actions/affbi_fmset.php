<?

	if (!$core->authControl->checkPermission('bi_fm','change_fmp')) return; // cannot change permissions

	$data = array(
		'filenm' => $_GET['file'],
		'has_expiration' => 'n',
		'id_allowed_group' => '0',
		'allowed_users' => '',
		'expiration_date' => '0000-00-00',
		);
	if ($data['filenm'] != '' && $data['filenm'][0] == '/') $data['filenm'] = substr($data['filenm'],1);
	$isFolder = $data['filenm'] != '' && $data['filenm'][strlen($data['filenm'])-1] == '/';
	if (isset($_GET['id_allowed_group']) && is_numeric($_GET['id_allowed_group'])) {
		$data['id_allowed_group'] = $_GET['id_allowed_group'];
		$data['allowed_users'] = '';
	} else if (isset($_GET['allowed_users'])){
		$data['allowed_users'] = $_GET['allowed_users'];
		$data['id_allowed_group'] = 0;
	}
	if (isset($_GET['ed']) && !$isFolder) {
		if (isData($_GET['ed'],$mysql_ed)) { // if it is a valid date, will put in $mysql_ed
			$data['has_expiration'] = 'y';
			$data['expiration_date'] = $mysql_ed;
		}
	}
	$mod = $core->loaded('bi_fm');
	$sql = "SELECT filenm FROM ".$mod->dbname." WHERE filenm LIKE \"".$data['filenm']."\"";
	$hasData = $core->dbo->fetch($sql) !== false;
	$core->safety = false;
	if ($hasData)
		$core->runAction('bi_fm',CONS_ACTION_UPDATE,$data);
	else
		$core->runAction('bi_fm',CONS_ACTION_INCLUDE,$data);
	$core->safety = true;

  	$core->close();

