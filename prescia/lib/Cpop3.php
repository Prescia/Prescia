<? # Prescia simple pop3 controler

	class Cpop3 { // http://www.ietf.org/rfc/rfc1939.txt

		private $server;
		private $port;
		private $buffer;
		private $user;
		private $pass;
		private $status;
		private $fp;
		public $lastError;
		private $log;
		public $logging;


		public function __construct($server,$port=110, $buffer = 512) {
			$this->log = array();
			$this->logging = false;
			$this->status = 0;
			$this->server = $server;
			$this->port = $port;
			$this->buffer = $buffer;
			$this->lastError = "";
		}

		public function getlog() {
			return $this->log;
		}

		public function setServer($server,$port=110) {
			if ($this->status != 0) {
				$this->lastError = "Already connected";
				return false;
			}
			$this->server = $server;
			$this->port = $port;
		}

		public function connect($user,$pass,$runLogin=true) {
			if ($this->status != 0) {
				$this->lastError = "Already connected";
				return false;
			}
			$this->log = array();
			$this->user = $user;
			$this->pass = $pass;
			if (!$this->fp = fsockopen($this->server, $this->port, $errno, $errstr)) {
				$this->lastError = "FSockopen Error [$errno] [$errstr]";
				return false;
			}
			stream_set_blocking($this->fp,true); // every call will wait a return
			$reply = fgets($this->fp,$this->buffer);
			if ($this->replyok($reply)) { // connection ok?
				$this->status = 1;
				if ($runLogin)
					return $this->login();

				return true;
			} else
				return false;
		}

		private function replyok($data) {
			if ($this->logging) $this->log[] = "<".$data;
			return $data != "" && preg_match("@^\+OK@",$data)==1;
		}

		public function login($newUser="",$newPass="") {
			if ($this->status != 1) {
				$this->lastError = "Incorrect state";
				return false;
			}
			if ($newUser != "") $this->user = $newUser;
			if ($newPass != "") $this->pass = $newPass;
			if ($this->logging) $this->log[] = ">USER ".$this->user;
			fwrite($this->fp,"USER ".$this->user."\r\n");
			$reply = fgets($this->fp,$this->buffer);
			if ($this->replyok($reply)) { // user ok?
				if ($this->logging) $this->log[] = ">PASS ****";
				fwrite($this->fp,"PASS ".$this->pass."\r\n");
				$reply = fgets($this->fp,$this->buffer);
				if ($this->replyok($reply)) {
					$this->status = 2;
					return true;
				} else
					$this->lastError = "Password failed, response was $reply";
			} else
				$this->lastError = "Login failed, response was $reply";
			$this->logout();
			return false;
		}

		public function stat() {
			if ($this->status != 2) {
				$this->lastError = "Incorrect state";
				return false;
			}
			if ($this->logging) $this->log[] = ">STAT";
			fwrite($this->fp,"STAT\r\n");
		 	$reply = fgets($this->fp,$this->buffer);
		 	if ($this->replyok($reply)) {
				list($ok,$count,$size) = explode(" ",str_replace("\r\n","",$reply));
				return array($count,$size);
		 	} else {
		 		$this->lastError = "STAT failed, response was $reply";
		 		return false;
		 	}
		}

		public function headers($total,$start=1) {
			if ($this->status != 2) {
				$this->lastError = "Incorrect state";
				return false;
			}
			if ($total == 0) return array();
			$response = array();
			for ($msg = $start;$msg <= $total+$start-1; $msg ++ ){
				if ($this->logging) $this->log[] = ">TOP $msg 0";
				fwrite($this->fp,"TOP $msg 0\r\n");
		 		$reply = fgets($this->fp,$this->buffer);
		 		if ($this->replyok($reply)) {
		 			$header = "";
			 		while ($reply != ".\r\n") {
			 			$reply = fgets($this->fp,$this->buffer);
			 			if ($this->logging) $this->log[] = "<".$reply;
			 			if ($reply != ".\r\n" && $reply != " \r\n") {
			 				$header .= $reply;
			 			}
			 		}
					$response[] = $header;
		 		} else {
		 			$this->lastError = "TOP $msg failed, response was $reply";
		 			return false;
		 		}
		 	}
		 	return $response;
		}

		public function parseHeader($header) {
			$header = explode("\r\n",$header);
			$parameters = array();
			$lp = "";
			foreach ($header as $line) {
				if (preg_match("@^([^ :]+):(.*)$@",$line,$regs) == 1) {
					$parameters[strtolower($regs[1])]=trim($regs[2]);
					$lp = strtolower($regs[1]);
				} else if ($lp != "") {
					$parameters[$lp] .= " ".trim($line);
				}
			}
			return $parameters;

		}

		public function uidl($total) {
			if ($this->status != 2) {
				$this->lastError = "Incorrect state";
				return false;
			}
			if ($this->logging) $this->log[] = ">UIDL";
			if ($total == 0) return array();
			$total++; // to get the .
			$response = array();
			fwrite($this->fp,"UIDL\r\n");
		 	$reply = fgets($this->fp,$this->buffer);
		 	if ($this->replyok($reply)) {
		 		while ($reply != "." && $total > 0) {
		 			$reply = str_replace("\r","",fgets($this->fp,$this->buffer));
		 			if ($this->logging) $this->log[] = "<".$reply;
		 			$reply = str_replace("\n","",$reply);
		 			if ($reply != ".") {
		 				$response[] = explode(" ",$reply);
		 			}
		 			$total--;
		 		}
		 		return $response;
		 	} else {
		 		$this->lastError = "UIDL failed, response was $reply";
				return false;
		 	}
		}

		public function logout() {
			stream_set_blocking($this->fp,false); // wont wait
			if ($this->logging) $this->log[] = ">QUIT";
			fwrite($this->fp,"QUIT\r\n");
			$reply = fgets($this->fp,$this->buffer);
			fclose($this->fp);
			unset($this->fp);
			$this->status = 0;
		}
	}

