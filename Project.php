<?php
// Requires DBObject object
require_once("DBObject.php");
	

class Project extends DBObject
{
	protected $object_table	 	= 'projects';
	protected $id_column		= 'project_id';
	protected $has_many 		= array("MeetingNote" => "meeting_notes");
	protected $defaults 		= array("project_name" => "sample",
										"project_desc" => "description");
}

?>