<?/* -------------------------------- Prescia extra core functions
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
  | These functions are not used so often, so to reduce parse time from the PHP compiler, they where removed from the core.php
-*/

/* 	rss echos a rss XML
Expected data in the $data array (see rsstemplate.xml):
title, link, description, language
module, category, itemlinktemplate, itemtitle, itemdescription
*/

if (!isset($data['title']) ||
	!isset($data['link']) ||
	!isset($data['description']) ||
	!isset($data['language']) ||
	!isset($data['module']) ||
	!isset($data['itemlinktemplate']) ||
	!isset($data['itemtitle']) ||
	!isset($data['itemdescription'])
	) return false;
if ($data['link'] == '') $data['link'] = "http://".$this->domain."/";

$NOWdate = gmdate("D, d M Y H:i:s")." GMT";
$rssTemplate = new CKTemplate($this->template);
$rssTemplate->fetch(CONS_PATH_SETTINGS."defaults/rsstemplate.xml");
$rssTemplate->fill($data);
$rssTemplate->assign("ABSOLUTE_URL","http://".$this->domain."/");
$rssTemplate->assign("date",$NOWdate);
$rssTemplate->assign("year",date("Y"));

if ($imgtitle == "") $rssTemplate->assign("_image");

$mylist = array();

$modules = explode(",",$data['module']);
$category = explode(",",isset($data['category'])?$data['category']:'');
$ilt = explode(",",$data['itemlinktemplate']);
$it = explode(",",$data['itemtitle']);
$idesc = explode(",",$data['itemdescription']);

if (count($modules) != count($ilt) &&
	count($modules) != count($it) &&
	count($module) != count($idesc)) {
	return false;
}

$itemsPerModule = ceil(15/count($modules)); // rss standard asks for 15 tops
$hasCategory = false;

$rssId = 0;
foreach ($modules as $mod) {
	$module = $this->loaded($mod);
	if (!$module) continue;
	$sql = $module->get_base_sql('','',$itemsPerModule);

	$hasCategory = $hasCategory || (isset($category[$rssId]) && $category[$rssId] != "");
	$this->dbo->query($sql,$r,$n);
	$dateField = "";

	foreach ($module->fields as $fname => &$field) {
		if ($field[CONS_XML_TIPO] == CONS_TIPO_DATETIME && isset($field[CONS_XML_TIMESTAMP]))
		$dateField = $fname;
	}

	$ppage = new CKTemplate($this->template);
	$ppage->tbreak($ilt[$rssId]);
	for ($c=0;$c<$n;$c++) {
		$dados = $this->dbo->fetch_assoc($r);
		$rssItem = array("title" => $dados[$it[$rssId]],
									 "description" => $dados[$idesc[$rssId]],
									 "date" => $dateField != "" ? (gmdate("D, d M Y H:i:s",tomktime($dados[$dateField]))." GMT") : $NOWdate,
									 "link" => $ppage->techo($dados));
		if (!isset($category[$rssId]) || $category[$rssId] != "")
		if ($hasCategory) $rssItem['category'] = $dados[$category[$rssId]];
		$mylist[] = $rssItem;
	}
	$rssId++;
}

function datesort($a,$b) {
	return datecompare($a['date'],$b['date'])?1:-1;
}

uksort($mylist,"datesort");

$obj = $rssTemplate->get("_itens");
$temp = "";
$exclude = !$hasCategory ? array("_category") : array();
foreach ($mylist as $item) {
	$temp .= $obj->techo($item,$exclude);
}
$rssTemplate->assign("_itens",$temp);

if ($echoHeader) header("Content-Type:application/rss+xml");
return $rssTemplate->techo();
