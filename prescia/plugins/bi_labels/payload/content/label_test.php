<?

	$cols = $_REQUEST['cols'];
	$rows = $_REQUEST['rows'];
	$pfl = $_REQUEST['pfl'];
	$pft = $_REQUEST['pft'];
	$sw = $_REQUEST['sw']-2; // -2 because THIS version has borders
	$sh = $_REQUEST['sh']-2;
	$ol = $_REQUEST['ol'];
	$ot = $_REQUEST['ot'];
	$fontsize = $_REQUEST['fontsize'];
	
	$core->template->assign("fontsize",$fontsize);
	
	$core->template->assign("fullwidth",$pfl + ($sw*($cols)) + ($ol*($cols-1)) + 2);
	$core->template->assign("fullheight",$pft + ($sh*($rows)) + ($ot*($rows-1)) + 2);
	
	$etq = 1;
	$temp = "";
	$tp = $core->template->get("_etiqueta");
	for ($line=1;$line<=$rows;$line++) {
		for ($col=1;$col<=$cols;$col++) {
			$outdata = array(
				'width' => $sw,
				'height' => $sh,
				'left' => $pfl + (($sw+$ol)*($col-1)),
				'top' => $pft + (($sh+$ot)*($line-1)),
				'#' => $etq
				);
			$temp .= $tp->techo($outdata);
			$etq++;
		}
	}
	$core->template->assign("_etiqueta",$temp);
