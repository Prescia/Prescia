<?/*--------------------------------\
  | dbo.php - Master version
  | if you want more logging, set quickmode to false after creating the object
  | last change: 14.8.20 to support some php 5.4 improvements
-*/

class CDBO {

	private $ctype = '';
  	private $connection = false; // MySQL resource OR MySQLi Object
  	private $host = "";
  	private $user = "";
  	private $password = "";
  	private $database = "";
	public $serverVersion = 0;
  	public $debugmode = false;
  	public $log = array();
  	public $dbc = 0; // how many queries run
  	public $dbt = 0; // SQL time
  	public $quickmode = true; // if false, will store all query logs into $log and store time (dbt) and queries (dbc) counts, otherwise only errors
  	public $allow_select_fast_foward = true; // allows array_break to fast-foward SELECT section and quickly detect FROM/JOIN
	public $errorRaised = false;

  	public function __construct($host="", $user="", $password="", $database="", $debug = false) {
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
		$this->database = $database;
		$this->debugmode = $debug;
		$this->connection = false;
  	} // __construct

  	function __destructor() {
  		if ($this->connection) $this->close();
  	}

  	public function setdatabase($db) {
  		if ($this->connection === false)
  			$this->database = $db;
  	}

  	function connect($retry=1,$host="",$user="",$password="",$database="") { // SHOULD BE EXTENDED and return true on sucess

  		return false; // WHEN EXTENDING, REMOVE THIS

  		if ($host != "") $this->host = $host;
  		if ($user != "") $this->user = $user;
  		if ($password != "") $this->password = $password;
  		if ($database != "") $this->database = $database;
  		$sd = getmicrotime();

  		/*
		COPY/PASTE THE WHOLE FUNCTION AND ADD HERE WHATEVER CODE WHICH CONNECTS TO THE DATABASE HERE. USE $retry VALUE TO RETRY MORE THEN ONE TIME ON FAILURE (use sleep(1) between retries)
   		SHOULD ALSO SET NAMES 'utf8' AND DO A DATABASE SELECTION TO $this->database
     	*/

   	  	$this->dbt = getmicrotime() - $sd;
   	  	return true;
  	} // connect

	function select_db($db) { // SHOULD BE EXTENDED and return true on sucess
		return false; // whatever code to select database and return true on sucess
	} // select_db

	function close() { // SHOULD BE EXTENDED
    	if ($this->connection) {
    		// whatever code to close conenction and free resources ...
    	}
    	$this->connection = false;
	} // close

	function query($sql, &$result, &$numrows, $debugmode= null) { // SHOULD BE EXTENDED, replace $result with resource/object for result, $numrows with rows returned. Returns true on sucess
		return false; // WHEN EXTENDING, REMOVE THIS

		// COPY/PASTE ALL CONTENT TO GUARANTEE DEBUGMODE AND BENCHMARK ARE IN PLACE

		if (!$this->connection) return false;
		if (is_null($debugmode)) $debugmode = $this->debugmode;
    	$this->dbc++;
    	if (is_array($sql)) $sql = $this->sqlarray_echo($sql);
    	if (!$this->quickmode) array_push($this->log,$sql);
    	$numrows = 0;
    	$sd = getmicrotime();

    	// $result = WHATEVER CODE TO PERFORM THE QUERY

    	$this->dbt += getmicrotime() - $sd;
    	if ($result!==false) {

			// if (strpos($sql,"SELECT")!==false) $numrows = WHATEVER CODE TO GET ROW NUMBERS ON RESULT

      		return true;
    	} else {
      		// $err = WHATEVER CODE TO GET ERROR MESSAGE
			if ($this->quickmode) array_push($this->log,$sql); // didnt log before
      		array_push($this->log,$err);
			if ($debugmode)
      			echo $err."\n<br/>\n".$sql;
      		return false;
    	}
    } // query

	function simpleQuery($sql,$debugmode  = null) { // SHOULD BE EXTENDED, this one is used for queries which you don't care for the result, only if the suceeded (true/false on return)
		return false; // WHEN EXTENDING, REMOVE THIS

		// COPY/PASTE ALL CONTENT TO GUARANTEE DEBUGMODE AND BENCHMARK ARE IN PLACE

		if (!$this->connection) return false;
		if (is_null($debugmode)) $debugmode = $this->debugmode;
		if (is_array($sql)) $sql = $this->sqlarray_echo($sql);
		$this->dbc++;
    	if (!$this->quickmode) {
    		array_push($this->log,$sql);
    		$sd = getmicrotime();
    		// $result = WHATEVER CODE TO PERFORM THE QUERY
    		$this->dbt += getmicrotime() - $sd;
    	} else
    		// $result = WHATEVER CODE TO PERFORM THE QUERY
    	if ($result === false) {

      		// $err = WHATEVER CODE TO GET ERROR MESSAGE
      		if ($this->quickmode) array_push($this->log,$sql); // did not log sql, log now
      		array_push($this->log, $err);
      		if ($debugmode && strpos($err,"Duplicate")===false) // NOTE THIS TEST MIGHT BE DIFFERENT IN SOME LANGUAGES. THIS WILL NOT ECHO THE ERROR MESSAGE ON "Duplicate item on insert" KIND OF ERROR
      			echo ($err."\n<br/>\n".$sql);
      		return false;
    	} else
	    	// FREE RESOURCES CODE HERE
    	return true;
	} // simpleQuery

	function fetch($sql, $abortOnError = false) { // SHOULD BE EXTENDED, this one is used for queries which return the first row of the first result, or false if none found
		# fetches ONE value of ONE table, as in SELECT id FROM ...
		return false; // WHEN EXTENDING, REMOVE THIS
		// COPY/PASTE ALL CONTENT TO GUARANTEE DEBUGMODE AND BENCHMARK ARE IN PLACE
		if (!$this->connection) return false;
    	$this->dbc++;
    	if (is_array($sql)) $sql = $this->sqlarray_echo($sql);
    	if (!$this->quickmode) array_push($this->log,$sql);
    	$sd = getmicrotime();

    	// $result = WHATEVER CODE TO PERFORM THE QUERY

    	$this->dbt += getmicrotime() - $sd;

    	//$numrows = WHATEVER CODE TO GET ROW NUMBERS ON RESULT

    	if ($result !== false && $numrows>0) {

    		//$value = WHATEVER CODE TO GET THE FIRST ROW OF THE FIRST RESULT, LIKE mysql_result($result,0,0); IN MYSQL
    		//THEN FREE RESOURCES

      		return $value;
    	} else if ($result === false) { # error, not "not found"
    		// $err = WHATEVER CODE TO GET ERROR MESSAGE
      		if ($this->quickmode) array_push($this->log,$sql); // did not log sql, log now
      		array_push($this->log, $err);
      		if ($abortOnError)
      			die ($err."\n<br/>\n".$sql);
      		return false;
    	} else
    		// FREE RESOURCES
    	return false;
	} // fetch

	function insert_id() { // SHOULD BE EXTENDED
		return false; // LAST AUTO_INCREMENT ID
	}

	function fetch_assoc($r) { // SHOULD BE EXTENDED
		return false;
	}

	function fetch_row($r) { // SHOULD BE EXTENDED
		return false;
	}

	# FROM NOW ON, NO NEED TO EXTEND OF CHANGE --

	function sqlarray_break($sql) {
		// converts a SELECT SQL into an array-like structure to enable easy customization of selects
	    $s = strlen($sql);
	    $buffer = "";
	    $word = "";
	    $saida = array("SELECT" => array(), "FROM" => array(), "LEFT" => array(), "WHERE" => array(), "GROUP" => array(), "ORDER" => array(), "LIMIT" => array(), "HAVING" => array());
	    $lookfor = array("SELECT", "FROM", "LEFT", "WHERE", "GROUP", "ORDER", "LIMIT","AND","HAVING");
	    $si = 0;
	    $po = false; // open parenthesis
	    $nsi = 0;
	    for ($c = 0; $c < $s; $c++) {
	      if ($this->allow_select_fast_foward && $si == 1) { // fast foward
	        $pos = strpos($sql,"FROM");
	        $allsel = substr($sql,$c,$pos-$c);
	        $saida['SELECT'] = explode(",",$allsel);
	        $c = $pos+4; // "FROM"
	        $si = 2;
	      }
	      $char = $sql[$c];
	      if ($char == " " || $char == "\r" || $char == "\n" || $char == "\t") {
	        if ($word != "" && in_array($word,$lookfor)) {
	          if ($word == "AND" && $si != 4) { // AND outside WHERE
	            $word = "";
	            $buffer .= $char;
	            continue;
	          }
	          $buffer = substr($buffer,0,strlen($buffer)-strlen($word));
	          if ($word == "GROUP" || $word == "ORDER") {
	            // BY look ahead
	            $c += 3;
	            if ($word == "GROUP")
	              $nsi = 5;
	            else
	              $nsi = 6;
	          } else if ($word == "LEFT") {
	            // JOIN look ahead
	            $c += 5;
	            $nsi = 3;
	          } else if ($word == "SELECT") {
	            $nsi = 1;
	          } else if ($word == "FROM") {
	            $nsi = 2;
	          } else if ($word == "WHERE") {
	            $nsi = 4;
	          } else if ($word == "AND") {
	            $nsi = 4; // forces break here
	          } else if ($word == "LIMIT") {
	            $nsi = 7;
	          } else {
	            $nsi = 0;
	          }
	        }
	        $word = "";
	      } else
	        $word .= $char;
	      switch ($si) {
	        case 0: // Nothing?
	          $buffer .= $char;
	        break;
	        case 1: // SELECT
	          if ($char == "," || $nsi != 0) {
	            array_push($saida['SELECT'],trim($buffer));
	            $buffer = "";
	          } else
	            $buffer .= $char;
	        break;
	        case 2: // FROM (inner join)
	          if ($char == "," || $nsi != 0) {
	            array_push($saida['FROM'],trim($buffer));
	            $buffer = "";
	          } else if ($char != '(' && $char != ")")
	            $buffer .= $char;
	        break;
	        case 3: // LEFT
	          if ($nsi != 0) {
	            array_push($saida['LEFT'],trim($buffer));
	            $buffer = "";
	          } else
	            $buffer .= $char;
	        break;
	        case 4: // WHERE
	          if ($nsi != 0) {
	            array_push($saida['WHERE'],trim($buffer));
	            $buffer = "";
	          } else
	            $buffer .= $char;
	        break;
	        case 5: // GROUP
	          if ($char == "," || $nsi != 0) {
	            array_push($saida['GROUP'],trim($buffer));
	            $buffer = "";
	          } else
	            $buffer .= $char;
	        break;
	        case 6: // ORDER
	          if ($char == "," || $nsi != 0) {
	            array_push($saida['ORDER'],trim($buffer));
	            $buffer = "";
	          } else
	            $buffer .= $char;
	        break;
	        case 7: // estou no LIMIT
	          $buffer .= $char;
	        break;
	        case 9: // HAVING
	          if ($char == "," || $nsi != 0) {
	            array_push($saida['HAVING'],trim($buffer));
	            $buffer = "";
	          } else
	            $buffer .= $char;
	        break;
	      }
	      if ($nsi != 0) {
	        $si = $nsi;
	        $nsi = 0;
	      }
	    }
	    switch ($si) {
	      case 1:
	        array_push($saida['SELECT'],trim($buffer));
	      break;
	      case 2:
	        array_push($saida['FROM'],trim($buffer));
	      break;
	      case 3:
	        array_push($saida['LEFT'],trim($buffer));
	      break;
	      case 4:
	        array_push($saida['WHERE'],trim($buffer));
	      break;
	      case 5:
	        array_push($saida['GROUP'],trim($buffer));
	      break;
	      case 6:
	        array_push($saida['ORDER'],trim($buffer));
	      break;
	      case 7:
	        array_push($saida['LIMIT'],trim($buffer));
	      break;
	      case 9:
	        array_push($saida['HAVING'],trim($buffer));
	      break;
	    }
	    return $saida;
	} // sqlarray_break

  	function sqlarray_merge($sql, $sql2, $override = false) {
	  	// merge two SQL-ARRAYs into one. Override will cause data fom the second SQL override repeated data from the first (faster)
	    // SELECT
	    if ($override && count($sql2['SELECT'])>0) $sql['SELECT'] = $sql2['SELECT'];
	    else {
	      foreach ($sql2['SELECT'] as $x => $sel) {
	        if (!in_array($sel,$sql['SELECT'])) array_push($sql['SELECT'],$sel);
	      }
	    }
	    // FROM
	    foreach ($sql2['FROM'] as $x => $sel) {
	        if (!in_array($sel,$sql['FROM'])) array_push($sql['FROM'],$sel);
	    }
	    // LEFT
	    foreach ($sql2['LEFT'] as $x => $sel) {
	        if (!in_array($sel,$sql['LEFT'])) array_push($sql['LEFT'],$sel);
	    }
	    // WHERE
	    foreach ($sql2['WHERE'] as $x => $sel) {
	        if (!in_array($sel,$sql['WHERE'])) array_push($sql['WHERE'],$sel);
	    }
	    // group
	    if ($override && count($sql2['GROUP'])>0) $sql['GROUP'] = $sql2['GROUP'];
	    else {
	      foreach ($sql2['GROUP'] as $x => $sel) {
	        if (!in_array($sel,$sql['GROUP'])) array_push($sql['GROUP'],$sel);
	      }
	    }
	    // having
	    foreach ($sql2['HAVING'] as $x => $sel) {
	      if (!in_array($sel,$sql['HAVING'])) array_push($sql['HAVING'],$sel);
	    }
	    // ORDER
	    if ($override && count($sql2['ORDER'])>0) $sql['ORDER'] = $sql2['ORDER'];
	    else {
	      foreach ($sql2['ORDER'] as $x => $sel) {
	        if (!in_array($sel,$sql['ORDER'])) array_push($sql['ORDER'],$sel);
	      }
	    }
	    // LIMIT
	    if ($override && count($sql2['LIMIT'])>0) $sql['LIMIT'] = $sql2['LIMIT'];
	    else {
	      foreach ($sql2['LIMIT'] as $x => $sel) {
	        if (!in_array($sel,$sql['LIMIT'])) array_push($sql['LIMIT'],$sel);
	      }
	    }
	    return $sql;
	} // sqlarray_merge

	function sqlarray_echo($sql) {
	  	// "implodes" the SQL-ARRAY into string
	    $sqlout = "SELECT ".implode(",",$sql["SELECT"])." FROM (".implode(",",$sql["FROM"]).")";
	    if (count($sql['LEFT']) > 0) $sqlout .= " LEFT JOIN ".implode(" LEFT JOIN ",$sql['LEFT']);
	    if (count($sql['WHERE']) > 0) $sqlout .= " WHERE ".implode(" AND ",$sql['WHERE']);
	    if (count($sql['GROUP']) > 0) $sqlout .= " GROUP BY ".implode(",",$sql['GROUP']);
	    if (count($sql['HAVING']) > 0) $sqlout .= " HAVING ".implode(" AND ",$sql['HAVING']);
	    if (count($sql['ORDER']) > 0) $sqlout .= " ORDER BY ".implode(",",$sql['ORDER']);
	    if (count($sql['LIMIT']) > 0) $sqlout .= " LIMIT ".implode(",",$sql['LIMIT']);
	    return $sqlout;
	} // sqlarray_echo

	function import($file) {
		if (!is_file($file)) return false;
		$sql = cReadFile($file);
		$query = "";
		$total = strlen($sql);
		$inQuote = "";
		$this->quickmode = true;
		$q = 0;
		for ($c=0;$c<$total;$c++) {
			$char = $sql[$c];
			if ($inQuote == "") { // not in quote
				if (($char == "\"" || $char == "'" || $char == "`")) {
					$inQuote = $char;
					$query .= $char;
				} else if ($char == ";") { // end query
					if (!$this->simpleQuery($query,false)) {
						$this->log[] = "import error in char $c, query was: $query";
						return false;
					}
					$q++;
					$query = "";
				} else
					$query .= $char;
			} else { // in quote
				if ($char == $inQuote)
					$inQuote = "";
				$query .= $char;
			}
		}
		$this->quickmode = false;
		unset($sql);
		return $q;
	}

}
