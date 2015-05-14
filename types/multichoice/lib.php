<?php

class block_mycoursework_type_multichoice extends block_mycoursework_type {
    
    function __construct($cm) {
        parent::__construct($cm);
        $this->modname = "multichoice";
        $this->viewcap = 'mod/multichoice:view';
    }
    
    function check_submission($user) {
        global $DB;
        return $DB->record_exists('multichoice_answers', array('multichoiceid' => $this->id, 'userid'=> $user->id));
    }
    
    public static function get_duedate_field() {
        return 'timeclose';
    }
    
}