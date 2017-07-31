# dbobject
My effort at a simplistic ORM layer for MySQL from Aug 2009.

- DBObject.php : Main ORM class.
- MySQLTable. : table gateway used by DBObject
- Project.php : sample class file where I was starting to create related records.
- Task.php : Example of using DBObject.php

# Sample Use

Task.php
```
// Requires DBObject object
require_once("DBObject.php");

class Task extends DBObject
{
	protected $object_table	 	= 'tasks';
	protected $id_column		= 'task_id';  // Defines primary id column
}

```


index.php
```
require_once("Task.php")
$t = new Task();
$t->some_column = 'value';
$t->save();
```
