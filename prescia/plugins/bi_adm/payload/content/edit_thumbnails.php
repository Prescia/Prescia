<?

	define ("CONS_MAX_W",700);

	# For sure the module is set (checked at action), load parameters
	$module = $core->loaded($_REQUEST['module']);
	$field = $_REQUEST['field'];
	$formKeys = "";

	## ------------------------------------------------
	# Header and general module data
	$core->template->assign("module",$module->name);

	$sql = $module->get_base_sql(false,true);
	foreach ($module->keys as $key) {
		if (isset($_REQUEST[$key]) && !is_array($_REQUEST[$key]) && $_REQUEST[$key] != "") {
			$formKeys .= "<input type='hidden' name='$key' value=\"".$_REQUEST[$key]."\" />";
			$sql['WHERE'][] = $module->name.".$key = \"".$_REQUEST[$key]."\"";
		} else {
			$core->fastClose(404);
			return;
		}
	}

	$data = $module->runContent($core->template,$sql);
	if ($core->errorState) {
		$core->log[] = CONS_ERROR_TAG." Unknown error on SQL select or permission denied";
		return;
	}
	$core->template->assign("id",$data[$module->keys[0]]);
	if ($module->title != "") $core->template->assign("title",$data[$module->title]);
	$core->template->assign("imagem",$data[$field."_1"]);
	$core->template->assign("imagem_t",$data[$field."_1t"]);
	$core->template->assign("imagem_w",$data[$field."_1w"]);
	$core->template->assign("imagem_h",$data[$field."_1h"]);
	$core->template->assign("randomseed",rand(1000,9999).date("YmdHis"));
	

	# Thumbnail local
	$W = $data[$field."_1w"];
	$H = $data[$field."_1h"];
	$redPro = 1;
	if ($W>CONS_MAX_W) { # reduces proportionally to fit
	     $W = CONS_MAX_W;
	     $H = floor(($data[$field."_1h"] / $data[$field."_1w"]) * $W);
	     $redPro = $H / $data[$field."_1h"];
	}
	$core->template->assign("imagem_wr",$W);
	$core->template->assign("imagem_hr",$H);
	$core->template->assign("redPro",$redPro);


	#fills in image/thumbnail data
	$fieldObj = $module->fields[$field];
	$total = isset($module->fields[$field][CONS_XML_THUMBNAILS]) ? count($module->fields[$field][CONS_XML_THUMBNAILS]) : 1;

	if ($total == 0) {
		$core->template->assign("_thumbnails","");
		$core->template->assign("max","*x*");
	} else if (isset($module->fields[$field][CONS_XML_THUMBNAILS])) {
		$core->template->assign("max",str_replace(",","x",$module->fields[$field][CONS_XML_THUMBNAILS][0]));
		$temp = "";
		$tObj = $core->template->get("_thumbnails");
		for($c=1;$c<$total;$c++) {
			$mD = explode(",",$module->fields[$field][CONS_XML_THUMBNAILS][$c]);
			$dados = array("#" => $c,
						   "imagem" => isset($data[$field."_".($c+1)."t"])?$data[$field."_".($c+1)."t"]:"",
						   "imagem_w" => isset($data[$field."_".($c+1)."w"])?$data[$field."_".($c+1)."w"]:"",
						   "imagem_h" => isset($data[$field."_".($c+1)."h"])?$data[$field."_".($c+1)."h"]:"",
						   "max" => $mD[0]."x".$mD[1],
						   "max_w" => $mD[0],
						   "max_h" => $mD[1]
						  );
			$crop = isset($module->fields[$field][CONS_XML_TWEAKIMAGES]) && isset($module->fields[$field][CONS_XML_TWEAKIMAGES][$c]) && strpos($module->fields[$field][CONS_XML_TWEAKIMAGES][$c],"croptofit") !== false;
			$temp .= $tObj->techo($dados,$crop?array("_nonCrop"):array("_crop"));
		}
		$core->template->assign("_thumbnails",$temp);
		$temp = "";
	} else {
		$core->template->assign("_thumbnails","");
		$core->template->assign("max","*x*");
	}

	$core->template->assign("sKeys",$formKeys);
	$core->template->assign("field",$field);

?>