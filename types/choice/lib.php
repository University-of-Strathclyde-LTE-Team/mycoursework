<?php

class block_mycoursework_type_choice extends block_mycoursework_type {
    
    function __construct($cm) {
        parent::__construct($cm);
        $this->modname = "choice";
        $this->viewcap = 'mod/choice:view';
    }
    
    function check_submission($user) {
        global $DB;
        return $DB->record_exists('choice_answers', array('choiceid' => $this->id, 'userid'=> $user->id));
    }
    
    public static function get_duedate_field() {
        return 'timeclose';
    }
    
}