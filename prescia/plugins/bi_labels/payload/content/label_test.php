<?php

/** @var CPrescia $core Core context injected by mod_bi_labels::onShow(). */
	$labelInt = static function ($value, int $minimum, int $maximum) use ($core): int {
		$parsed = filter_var($value, FILTER_VALIDATE_INT);
		if ($parsed === false || $parsed < $minimum || $parsed > $maximum) $core->fastClose(400);
		return (int)$parsed;
	};
	$cols = $labelInt($_REQUEST['cols'] ?? null, 1, 100);
	$rows = $labelInt($_REQUEST['rows'] ?? null, 1, 100);
	if ($cols * $rows > 10000) $core->fastClose(400);
	$pfl = $labelInt($_REQUEST['pfl'] ?? null, 0, 10000);
	$pft = $labelInt($_REQUEST['pft'] ?? null, 0, 10000);
	$sw = $labelInt($_REQUEST['sw'] ?? null, 3, 10000)-2; // -2 because THIS version has borders
	$sh = $labelInt($_REQUEST['sh'] ?? null, 3, 10000)-2;
	$ol = $labelInt($_REQUEST['ol'] ?? null, 0, 10000);
	$ot = $labelInt($_REQUEST['ot'] ?? null, 0, 10000);
	$fontsize = $labelInt($_REQUEST['fontsize'] ?? null, 1, 1000);
	
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
