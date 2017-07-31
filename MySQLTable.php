<?php
// TODO -- test join part of scripts
// Excaping!!?  So it doesn't slow things up.
// Recheck addRows

class MySQLTable
{

	// Uses test to switch between hosts.
/*	private $host 	= "localhost";
	private $user	= "root";
	private $pass	= "";
	private $schema	= "istattic";

	private $test_host 	= "localhost";
	private $test_user	= "root";
	private $test_pass	= "";
	private $test_schema	= "istattic";
	private $link;
	private $resource;
*/
	

	
	private $table;
	
	private $join_table = "";
	private $join_col1 = "";
	private $join_col2 = "";
	private $join_type = "0";		// 0 for natural, 1 for left inner
									// More for later.

	public $last_inserted_id;
	public $num_rows_affected;
	public $num_rows;
	private $error_code;
	private $error_msg;
	

	private $sql;
	
	public $error;
	
    /**
     *
     */
    function __construct($table = "",$test = false)
    {
    	$this->table = $table;
/*
		if (!$this->connect($test)) {
			die($this->error);
		}
*/		
    }
	/**
	 * Connects to database
	 * TODO not sure if this is needed.  Might be better to not.
	 * @return void
	 * @param bool $test[optional] Use testing values if true.
	 */
/*	 
	private function connect($test = false) {
		if ($test) {
			$this->link = mysql_connect($this->test_host,$this->test_user,$this->test_pass);
			if (!$this->link) {
				$this->setError("Can't connect to testing database. ");
			}
			$this->test_schema = mysql_select_db($this->test_schema, $this->link);
			if (!$this->test_schema) {
				$this->setError("Could not select testing database. ");
			}
		} else {
			$this->link = mysql_connect($this->host,$this->user,$this->pass);
			if (!$this->link) {
				$this->setError("Can't connect to production database. ");
			}
			$this->schema = mysql_select_db($this->schema, $this->link);
			if (!$this->schema) {
				$this->setError("Could not select production database. ");
			}
		}
		print $this->link;
		if ($this->error == "") {
			return true;
		} else {
			return false;
		}
	}
*/
	public function setTable($t) {
		$this->table = $t;			
	}
	public function getTable() {
		return $this->table;
	}
	
	/*
	 * Prepares an input value for MySQL string building.
	 * Uses mysql_escape string
	 * @return string Prepared string ready for SQL
	 * @param string $input String to prepare
	 */
	private function prepSqlInput($input) {
		if (get_magic_quotes_gpc()) {
			$input = stripslashes($input);
		} 
		return mysql_real_escape_string($input);
	}
	
	/**
	 * Adds rows to current table based on column and value arrays.
	 * @return bool True if able to add rows, else false with set error
	 * @param array $cols Array of column names to match with values
	 * @param array $vals Array of values to insert based on columns.  Can be one-level nested
	 */
	public function addRows($cols, $vals) {
		$sql = " INSERT INTO $this->table ";
		if (count($cols) != count($vals)) {
			$this->setError("Column and value counts do not match.");
			return false;

		}
		if (!is_array($cols)) {
			$this->setError("Expecting array for parameter 1");
			return false;
		}
		if (!is_array($vals)) {
			$this->setError("Expecting array for parameter 2");
			return false;
		}
		$sql .= " ( `" . join("`,`",$cols) . "`)";
		$sql .= " VALUES ( ";
		if (is_array($vals[0])) {
			// Nested array means >1 row to insert.
			$sent = 0;
			foreach ($vals as $valrow ){
				for ($v = 0; $v < count($valrow); $v++){
					$valrow[$v] = $this->prepSqlInput($valrow[$v]);
				}
				if ($sent > 0) {
					$sql .= " ), ( ";
				}
				$sql .= "'" . join("','", $valrow) . "'";
				$sent++;
			}
			
		} else {
				for ($v = 0; $v < count($vals); $v++){
					$vals[$v] = $this->prepSqlInput($vals[$v]);
				}
				$sql .= "'" . join("','", $vals) . "'";
		}
		
		$sql .= " ) ";
		$this->sql = $sql;
		if (mysql_query($sql)) {
			$this->num_rows_affected = mysql_affected_rows();
			$this->last_inserted_id = mysql_insert_id();
			return true;
		} else {
			$this->setError("Could not add rows");
			return false;
		}
	}
	
	/**
	 * Adds rows to current table based on column and value arrays.
	 * @return bool True if able to add rows, else false with set error
	 * @param array $cols Array of column names to match with values
	 * @param array $vals Array of values to insert based on columns.  Can be one-level nested
	 */
	public function addRow($cols, $vals) {
		$sql = " INSERT INTO $this->table ";
		if (count($cols) != count($vals)) {
			$this->setError("Column and value counts do not match.");
			return false;
		}
		if (!is_array($cols)) {
			$this->setError("Expecting array for parameter 1");
			return false;
		}
		if (!is_array($vals)) {
			$this->setError("Expecting array for parameter 2");
			return false;
		}
		$sql .= " ( `" . join("`,`",$cols) . "` )";
		$sql .= " VALUES ( ";
		foreach ($vals as $k=>$v) {
			$vals[$k] = $this->prepSqlInput($v);
		}
		$sql .= "'" . join("','", $vals) . "'";
		$sql .= " ) ";
		$this->sql = $sql;
		if (mysql_query($sql)) {
			$this->num_rows_affected = mysql_affected_rows();
			$this->last_inserted_id = mysql_insert_id();
			return true;
		} else {
			$this->setError("Could not add row");
			return false;
		}
	}	
	/**
	 * Updates row(s) in database.
	 * @return true if able to update rows.
	 * @param array $cols Names of columns to update
	 * @param array $vals values to update cols
	 * @param string $where[optional] Optional WHERE clause statements.
	 */
	public function updateRows($cols, $vals, $where="") {
		$sql = "UPDATE $this->table SET ";
		if (count($cols) != count($vals)) {
			$this->setError("Column counts do not match");
			return false;
		}
		$x = 0;
		foreach ($vals as $v) {
			if ($x >= 1) {
				$sql .= " , ";
			}
			$sql .= "`$cols[$x]` = '" . $this->prepSqlInput($v) . "'";
			$x++;
		}
		if ($where != "") {
			$sql .= "  WHERE $where ";
		}
		$this->sql = $sql;
		if (mysql_query($sql)) {
			$this->num_rows_affected = mysql_affected_rows();
			return true;
		} else {
			$this->setError("Could not update rows.");
			return false;
		}
	}


/**
 * Performs a simple SELECT statement and returns results as an assoc array with rows as the primary index.
 * Example: $objects = $objMySQLDB->getRows();  $object[0] is first row, $object[1] is second.
 * @return array Assoc array of results
 * @param array $cols[optional] Columns to select
 * @param string $where[optional] WHERE statement (excluding the WHERE)
 * @param string $orderby[optional] Column to order by
 * @param string $order[optional] ASC|DESC for order, default ASC
 */
	public function getRows($cols = "", $where="", $orderby = "", $order = "ASC") {
		$sql = " SELECT " ;
		if (is_array($cols)) {
			$sql .= join(", ",$cols);
		} elseif ($cols != "" ) {
			$sql .= " $cols ";
		} else {
			$sql .= " * ";
		}
		$sql .= "FROM $this->table ";
		if ($this->join_table != "") {
			$sql .= $this->sqlJoin();
		} else {
			$sql .= " WHERE 1 ";
		}
		if ($where != "") {
			$sql .= "AND $where ";
		}
		if ($orderby != "" ){
			$sql .= " ORDER BY $orderby $order";
		}
		$this->sql = $sql;
		$res = mysql_query($sql);
		if (is_resource($res)) {
			$this->num_rows = mysql_num_rows($res);
			return $this->arrPrepArray($res);
		} else {
			$this->setError("Could not load result.");
			return false;
		}
	}

	/**
	 * Deletes rows from db based on where clause
	 * @return bool True on success, else false.
	 * @param string $where WHERE clause to use
	 * @param bool $override[optional] Method will not delete without where clause unless override is true.
	 */
	public function deleteRows($where,$override=false) {
		$sql = "DELETE FROM $this->table ";
		if ($where == "" && $override !== true) {
			$this->setError("Really should specify limit to delete.");
		} elseif ($where == "" && $override === true) {
			// No where clause.
		} else {
			$sql .= " WHERE $where ";
		}
		$this->sql = $sql;
		if (mysql_query($sql)) {
			$this->num_rows_affected = mysql_affected_rows();
			return true;
		} else {
			$this->setError("Could not delete");
			return false;
		}
	}

/**
 * Takes a resource from a SQL SELECT return and returns array of data.
 * Rows are accessed by the first index.  Columns the second.
 * @return array Associative array of results
 * @param resource $resource Passed PHP resource to SQL result
 */
	private function arrPrepArray($resource) {

		if (!is_resource($resource)) {
			$this->setError("Could not load array; result not a resource. ");
			return false;
		}
		$a = array();
		$x = 0;
		while ($row = mysql_fetch_assoc($resource)) {
			foreach ($row as $k=>$v) {
				$a[$x][$k] = $v;
			}
			$x++;
		}
		return $a;
	}

	/**
	 * Sets join table for use with getRows -- joins not tested
	 * @return void
	 * @param string $table Table name to join to.
	 * @param string $col1 Column on this->join_table to join with
	 * @param string $col2 Column on this->table to join to
	 */
	public function setJoin($table,$col1,$col2,$join_type=0) {
		$this->join_table = $table;
		$this->join_col1 = $col1;
		$this->join_col2 = $col2;
		$this->join_type = $join_type;
	}
	
/**
 * Not tested.
 * @return 
 * 
 */	
 	private function sqlJoin() {
		$s = "";
		if ($this->join_table != "") {
			switch ($this->join_type) {
				case 0:  $s = ", $this->join_table WHERE $this->table.$this->join_col2 = $this->join_table.$this->join_col1 "; 
							$ok = true;
							break;
				case 1: $s = " LEFT JOIN $this->join_table ON $this->table.$this->join_col2 = $this->join_table.$this->join_col1 "; 
							$ok = true;
							break;
				default: $this->setError("Expects a matching join type."); 
							$ok = false;
			}
		} else {
			$this->setError("No join table selected");
			$ok = false;
			
		}
		if ($ok) {
			return $s;
		} else {
			die("Join parameters not right.");
		}
	}
	
	public function rows_affected() {
		return $this->num_rows;
	}
	
	protected function setError($msg) {
		$this->error_code = mysql_errno();
		$this->error_msg = mysql_error();
		$this->error = $msg;
	}
	public function getError() {
		return "MySQLTable Error: $this->error";
	}
	public function getMySqlError() {
		return "MySQL Error: $this->error_msg";
	}
	public function showSQL() { 
		return $this->sql;
	}
	
	public function getTableFields() {
		$sql = "SHOW COLUMNS FROM $this->table";
		$this->sql = $sql;
		$result = mysql_query($sql);
		if (!$result) {
			$this->setError("Could not get columns.");
			return false;
		}
		$arr = array();
		if (mysql_num_rows($result) > 0) {
		    while ($row = mysql_fetch_assoc($result)) {
		        $arr[] = $row['Field'];
		    }
		}		
		return $arr;
	}

}
?>