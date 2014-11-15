<?

	$core->layout = 2;
	$currentLabels = isset($core->dimconfig['_labels'])?$core->dimconfig['_labels']:array();
	if (isset($_REQUEST['id']) && isset($currentLabels[$_REQUEST['id']])) {
		$cL = $currentLabels[$_REQUEST['id']];
		echo $_REQUEST['id']."|";
		echo $cL['name']."|";
		echo $cL['module']."|";
		echo $cL['content']."|";
		echo $cL['cols']."|";
		echo $cL['rows']."|";
		echo $cL['pfl']."|";
		echo $cL['pft']."|";
		echo $cL['sw']."|";
		echo $cL['sh']."|";
		echo $cL['ol']."|";
		echo $cL['ot']."|";
		echo $cL['fontsize']."|";
	} else
		echo "e";
	$core->close();
	