<? /* ---------------------------------
   | PART OF stats MODULE
--*/

	$ip = cleanString($_REQUEST['ip']);

	$rt = $core->loaded('statsrt');
	if ($core->dbo->query("SELECT * from ".$rt->dbname." WHERE ip='$ip'",$r,$n) && $n>0) {
		$dados = $core->dbo->fetch_assoc($r);
		echo "Hora de entrada: ".fd($dados['data_ini'],"H:i:s")."\n";
		echo "Último contato: ".fd($dados['data'],"H:i:s")."\n";
		echo "Navegador: ".$dados['agent']."\nCaminho Percorrido:\n";
		echo str_replace(",","\n⤹ ",$dados['fullpath']);
	} else
		echo "IP not found";

	$core->close();

