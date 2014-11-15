<?
	$this->tCaptcha('safetycode',true);
	if(isset($_POST['haveinfo']) and $_POST['haveinfo'] == "1") {

		if ($this->queryok(array("nome","email","mensagem","safetycode"))) {
			$mailTO = 'prescia@prescia.net';
			$mail = $this->prepareMail();

			if (sendMail($mailTO,"Contato pelo site",$mail,"prescia@prescia.net")) {
				$this->log[] = "Obrigado por entrar em contato. Sua mensagem foi enviada com sucesso!";
			} else
				$this->log[] = "Erro ao enviar mail";
		} else {
			$this->log[] = "Erro ao enviar mail - dados insuficientes";
			$this->action = "contato";
		}
	 }
	