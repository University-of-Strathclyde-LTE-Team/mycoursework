<?php

class block_mycoursework_type_glossary extends block_mycoursework_type {
    
    function __construct($cm) {
        parent::__construct($cm);
        $this->modname = "glossary";
        $this->viewcap = 'mod/glossary:view';
    }
    
    function check_submission($user) {
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
    
    function get_deadline_objects($user, $returnsubmissionstateinfo = true) {
        global $DB;
    
        $postcountsql = "SELECT COUNT(*) FROM {glossary_entries}
            WHERE glossaryid = :glossaryid
            AND userid = :userid
            AND timecreated < :duedate";
    
        $results = parent::get_deadline_objects($user, $returnsubmissionstateinfo);
    
        $posts = $DB->count_records_sql($postcountsql, array(
                'glossaryid' => $this->id,
                'userid' => $user->id,
                'duedate' => $this->duedate
        ));
        $results[0]->state = 'Entries made: ' . $posts;
        $results[0]->submissionstate = BLOCK_MYCOURSEWORK_SUBMISSION_UNDEFINED;
        return $results;
    }
    
}