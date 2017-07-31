<?php

// Requires DBObject object
require_once("DBObject.php");

class Task extends DBObject
{
	protected $object_table	 	= 'tasks';
	protected $id_column		= 'task_id';
	

	public function markCompleted() {
		$this->completed = date("YmdHis");
		$this->closed = 1;
		if ($this->save()) {
			return true;
		} else {
			$this->error_msg = "Error: Task $id could not be completed." . $this->error_msg;
			return false;
		}
	}
	

}

?>