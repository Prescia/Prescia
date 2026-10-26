<?php /* ---------------------------------
   | PART OF stats MODULE
--*/

/** @var CPrescia $core Core context injected by the BI Stats action loader. */

		header('Content-Type: text/plain; charset=UTF-8');
		header('X-Content-Type-Options: nosniff');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		if (!isset($_SESSION[CONS_SESSION_ACCESS_LEVEL]) || $_SESSION[CONS_SESSION_ACCESS_LEVEL] < 10) {
			http_response_code(403);
			echo "Forbidden";
			$core->close(true);
			return;
		}

		$ip = filter_var($_REQUEST['ip'] ?? '', FILTER_VALIDATE_IP);
		if ($ip === false) {
			http_response_code(400);
			echo "Invalid IP";
			$core->close(true);
			return;
		}

		$rt = $core->loaded('statsrt');
		$r = false;
		$n = 0;
		if ($core->dbo->queryPrepared("SELECT * from ".$rt->dbname." WHERE ip=?", 's', array($ip), $r, $n) && $n>0) {
			$dados = $core->dbo->fetch_assoc($r);
			$safe = static function ($value): string {
				return htmlspecialchars(substr((string)$value, 0, 4096), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			};
			echo "Hora de entrada: ".$safe(fd($dados['data_ini'],"H:i:s"))."\n";
			echo "Último contato: ".$safe(fd($dados['data'],"H:i:s"))."\n";
			echo "Navegador: ".$safe($dados['agent'])."\nCaminho Percorrido:\n";
			echo $safe(str_replace(",","\n⤹ ",(string)$dados['fullpath']));
	} else
		echo "IP not found";

	$core->close();
