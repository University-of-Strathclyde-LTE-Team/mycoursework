<?php

require_once($CFG->dirroot.'/group/lib.php');

class block_mycoursework_type_groupselect extends block_mycoursework_type {
    
    protected $belongstogroupingsql = "SELECT 'x'
            FROM {groupings_groups} gg
            JOIN {groups_members} gm
            ON gg.groupid = gm.groupid
            WHERE gg.groupingid = :groupingid
            AND   gm.userid = :userid";
    protected $belongstoanygroupsql = "SELECT 'x'
            FROM {groups} g
            JOIN {groups_members} gm
            ON g.id = gm.groupid
            WHERE g.courseid = :courseid
            AND   gm.userid = :userid";
    
    function __construct($cm) {
        parent::__construct($cm);
        $this->modname = "groupselect";
        $this->viewcap = 'mod/groupselect:view';
    }
    
    function check_submission($user) {
        global $DB;
        if ($this->targetgrouping) {
            return $DB->record_exists_sql($this->belongstogroupingsql, array(
            	'groupingid' => $this->targetgrouping,
                'userid' => $user->id
            ));
        } else {
            return $DB->record_exists_sql($this->belongstoanygroupsql, array(
                    'courseid' => $this->course,
                    'userid' => $user->id
            ));
        }
        return false;
    }
    
    public static function get_duedate_field() {
        return 'timedue';
    }
    
    public static function get_other_fields() {
        return array('targetgrouping');
    }
    
}