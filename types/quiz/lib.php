<?php
class block_mycoursework_type_quiz extends block_mycoursework_type {
	
	function __construct($cm) {
		parent::__construct($cm);
		$this->modname = "quiz";
		$this->viewcap = 'mod/quiz:view';
	}
	
	function check_submission($user) {
		global $DB;
		
		return $DB->record_exists('quiz_attempts', array('quiz' => $this->id, 'userid' => $user->id));
		
		return false;
	}
	
	public static function get_duedate_override($userid) {
	    return array('modquizoverrides.timeclose', "left join {quiz_overrides} modquizoverrides on (modquiztbl.id = modquizoverrides.quiz and modquizoverrides.userid = ?)");
	}

	static function get_duedate_field() {
	    return 'timeclose';
	}
	
}