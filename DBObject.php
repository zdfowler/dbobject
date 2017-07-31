<?php
// Requires MySQLTable Object
require_once("MySQLTable.php");


class DBObject 
{
	protected $values;				// Array of object values based on assoc field index
	protected $fields;				// Array of fields being connected.
	protected $defaults = array();	//array for defaults in object

	protected $id;					// Value of current object ID
	protected $id_column;			// Column name mapped to ID for object.
	
	protected $error_msg;
	protected $consistent;				// Bool if consistent from initial load (1 = yes)
		
	protected $object_table;

	protected $has_many = array();			// one to many join, array
	protected $has_one = array();			// many to one join, array

    public function __construct($id="")
    {
		$this->fields = $this->loadFields();
		$this->values = $this->loadEmptyValues();

		// Does it matter if the things below it aren't consistent?
		$this->consistent = 1;
		
		if ($id != "" && $id > 0) {
			if ($this->load($id)) {
				// Load any "many" relationships
				$this->loadMany();
				// Load the "one" relationship
				$this->loadOne();
			} else {
				// could not load? Not sure what to do here.
			}
		} else {
			// Set defaults
			foreach ($this->defaults as $k=>$v) {
				$this->$k = $v;
			}
		}
    }
	protected function loadFields() {
		// Load fields based on table.
		$t = new MySQLTable($this->object_table);
		return $t->getTableFields();
	}
	/**
	 * Prepares values array.
	 * @return array Array of empty values with same count as fields array
	 */
	protected function loadEmptyValues() {
		$arr = array();
		foreach ($this->fields as $v) {
			$arr[$v] = '';
		}
		return $arr;
	}
	public function create() {
		$d = new MySQLTable($this->object_table);
		$ok = $d->addRow($this->fields, $this->values);
		if ($ok) {
			$this->setID($d->last_inserted_id);
			$this->consistent = 1; // Not needed
			return $this->load($this->id);
		} else {
			$this->error_msg = "Could not insert: " . $d->getError();
			return false;
		}
		unset($d);		
	}
	/**
	 * Sets the friendly-id member and column-id values at once
	 * @return void
	 * @param int $id ID to set
	 */
	private function setID($id) {
		$this->id = $id;
		$this->{$this->id_column} = $this->id;
	}
	public function getID() {
		return $this->id;
	}
	public function load($id = '') {
		$ret = true;	
		$d = new MySQLTable($this->object_table);
		$data = $d->getRows("*","$this->id_column = $id");
		if (is_array($data)) {
			if ($d->num_rows == 1) {
				foreach ($data[0] as $field=>$value) {
					$this->$field = $value;
				}
				$this->setID($id);
				$this->consistent = 1;
			} else {
				$this->error_msg = "Incorrect number of records returned.";
				$ret = false;
			}
		} else {
			$this->error_msg = "Query did not result in resource: " . $d->getError();
			$ret = false;
		}
		unset($d);		

		return $ret;		
	}
	public function save() {
		// Exclude id_col from lists
		$d = new MySQLTable($this->object_table);
		$where = "`$this->id_column` = $this->id";
		$ok = $d->updateRows($this->fields, $this->values, $where);
		if ($ok) {
			$this->consistent = 1;
			// This may not be an error??
			if ($d->num_rows_affected == 1) {
				return true;
			} else {
				$this->error_msg = "Incorrect rows affected.";
				return false;
			}
		} else {
			$this->error_msg = "DB error: " . $d->getError();
		}		
		unset($d);		

	}
	public function delete() {
		$d = new MySQLTable($this->object_table);
		$where = "`$this->id_column` = $this->id";
		$ok = $d->deleteRows($where);
		if ($ok) {
			$this->consistent = 0;
			$this->fields = $this->loadFields();
			$this->values = $this->loadEmptyValues();
//			$this->setID(''); // If this line is commented, then this->id can be used to return what was just deleted.
			return true;
		} else {
			$this->error_msg = "Could not delete: " . $d->getError();
			return false;
		}		
		unset($d);		

	}

	/**
	 * 
	 * @return 
	 */
	public function loadMany() {
		if (is_array($this->has_many)) {
			foreach ($this->has_many as $key=>$which) {
				$o = new $key();
				$d = new MySQLTable($which);
				$ids = $d->getRows($o->id_column, "$this->id_column = '$this->id'", "", "ASC");
				$b = array();
				
				foreach ($ids as $id) {
					$b[] = new $key($id[$o->id_column]);
				}
				
				$this->{$which} = $b;
				unset($d);		
			}
		}

	}
	public function loadOne() {
		// Ungood: tables are supposed to be plural.  So the object doesn't quite map right
		// TODO: fix plurality
		if (is_array($this->has_one)) {
			foreach ($this->has_one as $key=>$which) {
				$o = new $key();
				$d = new MySQLTable($which);
				$ids = $d->getRows($o->id_column, "$this->id_column = '$this->id'", "", "ASC");
				$b = new $key($ids[0][$o->id_column]);
				
				$this->{$which} = $b;
				unset($d);		
			}
		}
		
	}

	public function setError($msg) {
		$this->error_msg = $msg;
	}
	public function getError() {
		return $this->error_msg;
	}


	/**
	 * Override default operation and sets fields based on array.
	 * @return void
	 * @param array $fieldArray Simple array of field names to use.
	 */
	protected function setFields($fieldArray) {
		$this->fields = $fieldArray;
	}
	/**
	 * Only allows object members to be set if they are in field array.
	 * @return bool True if member is in fields list, else false.
	 */
	public function __set($name, $value) {
		// Limit properties to be based on fields.
		// Consistency is set if THIS object's fields are modified.
		// Consistency is not watched in deeper objects
		if (in_array($name,$this->fields) ) {
			if ($this->values[$name] != $value) {
				$this->consistent = 0;
				$this->values[$name] = $value;
			}
			return true;
		} elseif (in_array($name,$this->has_many)) {
			$this->values[$name] = $value;
			return true;
		} elseif (in_array($name,$this->has_one)) {
			$this->values[$name] = $value;
			return true;
		} else {
			return false;
		}
	}
	
	public function __get($name) {
		return $this->values[$name];
	}
	public function __toString() {
		$s = "Object " . get_class($this) . " {\n";
		
		foreach ($this->values as $name=>$value) {
			if (is_array($value) && is_object($value[0])) {
				$s .= "\t$name: " . get_class($value[0]) . " (" . count($value) . ")\n";
			} elseif (is_object($value)) {
				$s .= "\t$name: " . get_class($value) . " (1)\n";
			} else {
				$s .= "\t$name: $value\n";
			}
		}
		$s .= "\tid: $this->id\n";
		$s .= "\tconsistent: $this->consistent\n";
		$s .= "\terror_msg: $this->error_msg\n";
		$s .= "\tobject_table: $this->object_table\n";
		$s .= " }\n";
		return $s;
	}
}

?>