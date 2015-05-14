<?php

class block_mycoursework_type_peerassessment extends block_mycoursework_type {
    
    function __construct($cm) {
        parent::__construct($cm);
        $this->modname = "peerassessment";
        $this->viewcap = 'mod/peerassessment:view';
    }
    
    function check_submission($user) {
        global $DB;        
        return $DB->record_exists('peerassessment_ratings', array('peerassessment' => $this->id, 'userid' => $user->id));
    }
    
    public static function get_duedate_field() {
        return 'timedue';
    }
    
    public function get_deadline_objects($user, $returnsubmissionstateinfo = true) {
        global $DB;
        $deadlines = parent::get_deadline_objects($user, $returnsubmissionstateinfo);
        if ($frequency = $DB->get_field('peerassessment', 'frequency', array('id' => $this->id))) {
            if ($frequency == 1) { // weekly - not really supported
                $deadlines[0]->duestate = BLOCK_MYCOURSEWORK_STATUS_DUE_SOON;
                $deadlines[0]->state = "Due weekly";
                $deadlines[0]->duedate = 0;
            }
        }
        return $deadlines;
    }
    
}
