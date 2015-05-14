<?php
class block_mycoursework_type_assignment extends block_mycoursework_type {
	
	public $assignmenttype = null;
	
	function __construct($cm) {
		parent::__construct($cm);
		$this->modname = "assignment";
		$this->assignmenttype = $cm->assignmenttype;
		$this->viewcap = 'mod/assignment:grade';
	}
	
	function check_submission($user) {
		global $DB;
		switch(strtolower($this->assignmenttype)) {
			case 'group':
				{
					if ($submission = $DB->get_records('group_assignment_submissions', array('assignment' => $this->id, 'userid' => $user->id))) {
						return true;
					}
				}
				break;
			default:
				{
					if ($submission = $DB->get_record('assignment_submissions', array('assignment' => $this->id, 'userid'=> $user->id) )) {
						return true;
					}
				}
				break;
		}
		return false;
	}
	
	static function get_duedate_field() {
	    return 'timedue';
	}
	
}