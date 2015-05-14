<?php

class block_mycoursework_type_data extends block_mycoursework_type {
    
    function __construct($cm) {
        parent::__construct($cm);
        $this->modname = "data";
        $this->viewcap = 'mod/data:view';
    }
    
    function check_submission($userid) {
        return false;
        
    }
    
    public static function get_duedate_field() {
        return 'assesstimefinish';
    }
    
    public static function get_joins($type, $modalias) {
        global $CFG;
        $prefix = $CFG->prefix;
        // We use prefix here to avoid horrible syntax with the variable interpolations
        return array("left join {$prefix}{$type} {$modalias} on ({$modalias}.id = cm.instance and m.name = '{$type}' and {$modalias}.assessed = 1)");
    }
    
    public static function get_other_fields() {
        return array('assessed');
    }
    
    function get_deadline_objects($user, $returnsubmissionstateinfo = true) {
        global $DB;
        
        $results = parent::get_deadline_objects($user,$returnsubmissionstateinfo);
    
        $postcountsql = "SELECT COUNT(*) FROM {data_records} dr
            WHERE dr.dataid = :dataid
            AND dr.userid = :userid
            AND dr.timecreated < :duedate";
    
        $posts = $DB->count_records_sql($postcountsql, array(
                'dataid' => $this->id,
                'userid' => $user->id,
                'duedate' => $this->duedate
        ));

        $results[0]->state = 'Entries made: ' . $posts;
        //$results[0]->status = BLOCK_MYCOURSEWORK_STATUS_UNDEFINED;
        $results[0]->submissionstate = BLOCK_MYCOURSEWORK_SUBMISSION_UNDEFINED;
        return $results;
    }
    
}