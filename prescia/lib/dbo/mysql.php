<?/*--------------------------------\
  | mysql - Mysql database connector
  | NOTE: the connection on this module reffer to the resource id, not the actual database connection, as in any raw mysql implementation
  | This library is deprecated and might have bugs
-*/


class CDBO_mysql extends CDBO {

  	public function __construct($host="", $user="", $password="", $database="", $debug = false) {
  		parent::__construct($host,$user,$password,$database,$debug);
		$this->ctype = 'mysql';
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

		$this->connection =mysql_connect($this->host,$this->user,$this->password); // do not mute this
		while (!$this->connection && $retry>0) {
        	sleep(1);
  			$this->connection = mysql_connect($this->host,$this->user,$this->password); // do not mute this
  			$retry--;
      	}
      	if (!$this->connection) {
      		array_push($this->log,"Unable to connect to host ".$this->host.": ".mysql_error());
			$this->errorRaised = true;
        	return false;
  		} else {
  			// all php before 5.3 would default to latin1 if not explicit
  			$this->serverVersion = mysqli_get_server_version($this->connection);
			if ($this->serverVersion < 50006)
      			mysql_query("SET NAMES 'utf8'"); 
			else {
				mysqli_set_charset($this->connection, "utf8");
			}
			if ($this->database != '' && !@mysql_select_db($this->database)) {
      			array_push($this->log,"Unable to select database ".$this->database.": ".mysql_error());
				$this->errorRaised = true;
				$this->close();
      			return false;
   	  		}
      	}
   	  	$this->dbt = getmicrotime() - $sd;
   	  	return true;
  	} // connect

	function select_db($db) {
		return mysql_select_db($db);
	} // select_db

	function close() {
    	if (isset($this->connection) && $this->connection) {
   			mysql_close($this->connection);
    	}
		$this->database = '';
    	$this->connection = false;
	} // close

	function query($sql, &$result, &$numrows, $debugmode= null) {
		# debugmode will ECHO the error. It NEVER DIES
		if ($this->delayedconn == 1) $this->connect();
		if (!$this->connection) return false;
    	$this->dbc++;
    	if (is_array($sql)) $sql = $this->sqlarray_echo($sql);
    	if (!$this->quickmode) array_push($this->log,$sql);
    	$numrows = 0;
    	$sd = getmicrotime();
    	$result = mysql_query($sql);
    	$this->dbt += getmicrotime() - $sd;
    	if ($result!==false) {
			if (strpos($sql,"SELECT")!==false) $numrows =mysql_num_rows($result);
      		return true;
    	} else {
      		$err = mysql_error();
			$this->errorRaised = true;
			if ($this->quickmode) array_push($this->log,$sql); // didnt log before
      		array_push($this->log,$err);
      		if (is_null($debugmode)) $debugmode = $this->debugmode;  // if is null, use the object's debugmode
			if ($debugmode)
      			echo $sql."\n<br/>\n".$err;
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
    		$result = mysql_query($sql);
    		$this->dbt += getmicrotime() - $sd;
    	} else
    		$result = mysql_query($sql);
    	if ($result === false) {
    		$this->errorRaised = true;
    		if (is_null($debugmode)) $debugmode = $this->debugmode; // if is null, use the object's debugmode
      		$err = mysql_error();
      		if ($this->quickmode) array_push($this->log,$sql); // did not log sql, log now
      		array_push($this->log, $err);
      		if ($debugmode && strpos($err,"Duplicate")===false)
      			echo $sql."\n<br/>\n".$err;
      		return false;
    	} else
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
    	$result = mysql_query($sql);
    	$this->dbt += getmicrotime() - $sd;
    	if ($result !== false && ($numrows = mysql_num_rows($result)) && $numrows>0) {
    		$value = mysql_result($result,0,0);
	      	mysql_free_result($result);
      		return $value;
    	} else if ($result === false) { // error, not "not found"
    		$err = mysql_error();
			$this->errorRaised = true;
      		if ($this->quickmode) array_push($this->log,$sql); // did not log sql, log now
      		array_push($this->log, $err);
      		if ($abortOnError)
      			die($sql."\n<br/>\n".$err);
      		return false;
    	} else
    		mysql_free_result($result);
    	return false;
	} // fetch

	function insert_id() {
		return mysql_insert_id();
	}

	function fetch_assoc($r) {
		return mysql_fetch_assoc($r);
	}

	function fetch_row($r) {
		return mysql_fetch_row($r);
	}

}
