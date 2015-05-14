<?php

require_once($CFG->dirroot.'/mod/forum/lib.php');

class block_mycoursework_type_forum extends block_mycoursework_type {
    
    function __construct($cm) {
        parent::__construct($cm);
        $this->modname = "forum";
        $this->viewcap = 'mod/forum:view';
    }
    
    function check_submission($user) {
        // Always returns false as we can't define submission
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

        $postcountsql = "SELECT COUNT(*) FROM {forum_posts} p
            JOIN {forum_discussions} d ON d.id = p.discussion
            WHERE d.forum = :forumid
            AND p.userid = :userid
            AND p.created < :duedate";

        $results = parent::get_deadline_objects($user,$returnsubmissionstateinfo);
        
        $posts = $DB->count_records_sql($postcountsql, array(
        	'forumid' => $this->id,
            'userid' => $user->id,
            'duedate' => $this->duedate
        ));
        $results[0]->state = 'Posts made: ' . $posts;
        $results[0]->submissionstate = BLOCK_MYCOURSEWORK_SUBMISSION_UNDEFINED;
        return $results;
    }
    
}