<?

	$core->layout = 2;
	// groups = ids,
	// isInclude = true|false
	// [fname]_match = c|i|d + [fname] = field
	// extras = mails,

	######################## Build SQL to choose mails ################## <- same as actions/newsletter_go
	$ids = explode(",",$_REQUEST['groups']);
	$isInclude = isset($_REQUEST['isInclude']) && $_REQUEST['isInclude'] == "true";

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

	$sql = "SELECT count(distinct r.id) FROM ".$mod->dbname." as r, sys_news_link as l WHERE ".implode(" AND ",$where);
	$result = $core->dbo->fetch($sql);
	if ($result !== false) echo $result." ".$core->langOut('emails_selected_to_send');
	else echo $core->langOut('error_on_newsletter_count').", SQL where: ".$sql;

	$core->close();