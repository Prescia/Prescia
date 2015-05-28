<?/*--------------------------------\
  | mysqli - Mysqli database connector
  | NOTE: the connection on this module reffer to mysqli object. However, this class was optimized for ONE MYSQLI OBJECT RUNNING AT A TIME
  | These optimizations are noted where a #(*) exists (use of procedural mode for performance)
  | Note: use of this version is preffered over standard mysql
-*/


class CDBO_mysqli extends CDBO  {

	public function __construct($host="", $user="", $password="", $database="", $debug = false) {
  		parent::__construct($host,$user,$password,$database,$debug);
		$this->ctype = 'mysqli';
  	} // __construct

  	function connect($retry=1,$host="",$user="",$password="",$database="") {
  		if ($host != "") $this->host = $host;
  		if ($user != "") $this->user = $user;
  		if ($password != "") $this->password = $password;
  		if ($database != "") $this->database = $database;
		if ($this->delayedconn == 0) { // delayed connection, connects only if you really use DB
			$this->delayedconn = 1;
			return true;
		}
		$this->delayedconn = 2;
  		$sd = getmicrotime();

		$this->connection = new mysqli($this->host,$this->user,$this->password); // do not mute this
		while ((!$this->connection || $this->connection->connect_errno) && $retry>0) {
        	sleep(1);
  			$this->connection = new mysqli($this->host,$this->user,$this->password); // do not mute this
  			$retry--;
      	}
      	if (!$this->connection) {
      		array_push($this->log,"Unable to connect to host ".$this->host.": ".mysqli_connect_error()); #(*)
			$this->connection = false;
			$this->errorRaised = true;
        	return false;
      	} else
			$this->serverVersion = $this->connection->server_version;
			if ($this->serverVersion < 50006) // 5.0.6
      			$this->connection->query("SET NAMES 'utf8'");
			else
				$this->connection->set_charset("utf8");
      		if ($this->database != '' && !$this->connection->select_db($this->database)) {
      			array_push($this->log,"Unable to select database ".$this->database.": ".$this->connection->error);
				$this->close();
				$this->connection = false;
				$this->errorRaised = true;
      			return false;
			}
   	  	$this->dbt = getmicrotime() - $sd;
   	  	return true;
  	} // connect

  	public function escape($str) { // database specific escape function
  		return $this->connection->real_escape_string($str);
	}
  	
	function select_db($db) {
		return $this->connection && $this->connection->select_db($db);
	} // select_db

	function close() {
    	if (isset($this->connection) && $this->connection) {
    		$this->connection->close();
    	}
		$this->host = '';
  		$this->user = '';
  		$this->password = '';
  		$this->database = '';
    	$this->connection = false;
	} // close

	function query($sql, &$result, &$numrows, $debugmode= null) {
		if ($this->delayedconn == 1) $this->connect();
		if (!$this->connection) return false;
    	$this->dbc++;
    	if (is_array($sql)) $sql = $this->sqlarray_echo($sql);
    	if (!$this->quickmode) array_push($this->log,$sql);
    	$numrows = 0;
    	$sd = getmicrotime();
    	$result = $this->connection->query($sql);
    	$this->dbt += getmicrotime() - $sd;
    	if (is_Object($result)) {
			if (strpos($sql,"SELECT")!==false) $numrows = $result->num_rows;
      		return true;
    	} else {
      		$err = $this->connection->error;
			$this->errorRaised = true;
			if ($this->quickmode) array_push($this->log,$sql); // didnt log before
      		array_push($this->log,$err);
      		if (is_null($debugmode)) $debugmode = $this->debugmode;
			if ($debugmode)
      			echo $sql."\n (echo from dbo)<br/>\n".$err;
      		return false;
    	}
    } // query

	function simpleQuery($sql,$debugmode  = null) {
		if ($this->delayedconn == 1) $this->connect();
		if (!$this->connection) return false;
		if (is_array($sql)) $sql = $this->sqlarray_echo($sql);
		$this->dbc++;
    	if (!$this->quickmode) {
    		array_push($this->log,$sql);
    		$sd = getmicrotime();
    		$result = $this->connection->query($sql);
    		$this->dbt += getmicrotime() - $sd;
    	} else
    		$result = $this->connection->query($sql);
    	if ($result === false) {
      		$err = $this->connection->error;
			$this->errorRaised = true;
      		if ($this->quickmode) array_push($this->log,$sql); // did not log sql, log now
      		array_push($this->log, $err);
      		if (is_null($debugmode)) $debugmode = $this->debugmode;
      		if ($debugmode && strpos($err,"Duplicate")===false)
      			echo $sql."\n (echo from dbo)<br/>\n".$err;
      		return false;
    	} else if ($result !== true)
    		mysqli_free_result($result);
    	return true;
	} // simpleQuery

	function fetch($sql, $abortOnError = false) {
		if ($this->delayedconn == 1) $this->connect();
		if (!$this->connection) return false;
    	$this->dbc++;
    	if (is_array($sql)) $sql = $this->sqlarray_echo($sql);
    	if (!$this->quickmode) array_push($this->log,$sql);
    	$sd = getmicrotime();
    	$result = $this->connection->query($sql);
    	$this->dbt += getmicrotime() - $sd;
    	if (is_Object($result) && ($numrows = $result->num_rows) && $numrows>0) {
			$value = mysqli_fetch_row($result); #(*)
			$value = $value[0];
			mysqli_free_result($result);
         	return $value;
    	} else if ($result === false) { // error, not "not found"
    		$err = $this->connection->error;
			$this->errorRaised = true;
      		if ($this->quickmode) array_push($this->log,$sql); // did not log sql, log now
      		array_push($this->log, $err);
      		if ($abortOnError)
      			die ($sql."\n (echo from dbo)<br/>\n".$err);
      		return false;
    	} else if ($result !== true)
    		mysqli_free_result($result);
    	return false;
	} // fetch

	function insert_id() {
		return mysqli_insert_id($this->connection); #(*)
	}

	function fetch_assoc($r) {
		return mysqli_fetch_assoc($r); #(*)
	}

	function fetch_row($r) {
		// same as mysql_fetch_row. Creates an interface in case you want to change database type
		return mysqli_fetch_row($r); #(*)
	}

}
