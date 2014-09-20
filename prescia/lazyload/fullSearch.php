<?/* -------------------------------- Prescia extra core functions
  | Copyleft (ɔ) 2011+, Caio Vianna de Lima Netto (www.prescia.net)
  | LICENSE TYPE: BSD-new/ɔ
  | These functions are not used so often, so to reduce parse time from the PHP compiler, they where removed from the core.php
-*/

/* Each item in $parameters can have:
 * 'module' => (mandatory) name of module to be searched
 * 'title' => (optional) which field will be returned in 'title' (default is the module's title)
 * 'description' => (mandatory) which field will be returned in 'description'
 * 'link' => (optional) how to link to this item (can be a template)
 * 'limit' => (optional) how many to return in this module
 * 'where' => (optional) where to be included in module's SQL
 * 'date' => (optional) which field will be used as date (for ordering)
 * 'order' => (optional) SQL order

  Sample code to prepare parameters:

$parameters = array();
$parameters[] = array('module' => '',
					  'description' => '',
					  'link' => '',
					  'limit' => '',
					  'where' => '');

  Will return: module, title, description, date, link and id

*/


$mylist = array();

$c = 0;
foreach ($parameters as $mod) {
	$module = $this->loaded($mod['module']);
	if (!$module) continue;

	$title = isset($mod['title'])?$mod['title']:$module->title;
	$desc = $mod['description'];
	$link = isset($mod['link'])?$mod['link']:'';
	$limit = isset($mod['limit'])?$mod['limit']:15;
	$where = isset($mod['where'])?$mod['where']:'';
	$order = isset($mod['order'])?$mod['order']:'';

	$sql = $module->get_base_sql($where,$order,$limit);
	$this->dbo->query($sql,$r,$n);

	$dateField = isset($mod['date'])?$mod['date']:'';
	if ($dateField == '') {
		foreach ($module->fields as $fname => &$field) {
			if (($field[CONS_XML_TIPO] == CONS_TIPO_DATETIME || $field[CONS_XML_TIPO] == CONS_TIPO_DATE) && isset($field[CONS_XML_TIMESTAMP]))
			$dateField = $fname;
		}
	}

	if ($link != '') {
		$ppage = new CKTemplate($this->template);
		$ppage->tbreak($link);
	}
	for ($c=0;$c<$n;$c++) {
		$dados = $this->dbo->fetch_assoc($r);
		$resultData = array("module" => $mod['module'],
							"title" => $dados[$title],
							"description" => $dados[$desc],
							"date" => $dateField != "" ? $dados[$dateField] : date("Y-m-d H:i:s"),
							"link" => $link != '' ? $ppage->techo($dados) : '',
							"id" => $dados[$module->keys[0]]); // TODO: does not support multiple keys
		$mylist[] = $resultData;
	}
	$c++;
}

if (!$groupPerModule) {
	function datesort($a,$b) {
		return datecompare($a['date'],$b['date'])?1:-1;
	}

	uksort($mylist,"datesort");
}

return $mylist; // returns: module, title, description, date, link, id

