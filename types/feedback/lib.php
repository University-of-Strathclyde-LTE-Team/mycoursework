<?php

class block_mycoursework_type_feedback extends block_mycoursework_type {
    
    function __construct($cm) {
        parent::__construct($cm);
        $this->modname = "feedback";
        $this->viewcap = 'mod/feedback:view';
    }
    
    function check_submission($user) {
        global $DB;
        return $DB->record_exists('feedback_completed', array('feedback' => $this->id, 'userid'=> $user->id));
    }
    
    public static function get_duedate_field() {
        return 'timeclose';
    }
    
}