<?php

class block_mycoursework_type_turnitintool extends block_mycoursework_type {
    
    function __construct($cm) {
        parent::__construct($cm);
        $this->modname = "turnitintool";
        $this->viewcap = 'mod/turnitintool:view';
    }
    
    function check_submission($user) {
        // TODO: Implement
        return false;
    }
    
    public static function get_duedate_field() {
        return 'defaultdtdue';
    }
    
    function get_deadline_objects($user, $returnsubmissionstateinfo = true) {
        global $DB, $OUTPUT;
        
        $result = array();
        
        $sql = "SELECT t.id, t.name, tp.id, tp.partname, tp.dtdue, ts.id submission_id, ts.submission_modified
            FROM mdl_turnitintool t
            JOIN mdl_turnitintool_parts tp
            ON t.id = tp.turnitintoolid
            LEFT JOIN mdl_turnitintool_submissions ts
            ON tp.turnitintoolid = ts.turnitintoolid
            AND tp.id = ts.submission_part
            AND ts.userid = ?
            WHERE t.id = ?";
        
        $parts = $DB->get_records_sql($sql, array($user->id, $this->id));
        
        foreach ($parts as $part) {
            $d = new block_mycoursework_deadline();
            $classurl = new moodle_url('/course/view.php', array('id' =>$this->activityinfo->courseid));
            $d->class = $OUTPUT->action_link($classurl, $this->activityinfo->shortname);
            $activityurl = new moodle_url('/mod/'.$this->activityinfo->modtype.'/view.php', array('id' => $this->coursemodule->id));
            $name = $part->name . "(" . $part->partname . ")";
            $d->activityname = $OUTPUT->action_link($activityurl, $name);
            $d->duedate = $part->dtdue;
            if (is_null($part->submission_id)) {
                $d->duestate = $this->get_deadline_status($user, $part->dtdue); 
                //$this->_get_status(time(), $part->dtdue, false);
		$d->submissionstate = BLOCK_MYCOURSEWORK_SUBMISSION_NOT_SUBMITTED;
                $d->state = "Not submitted";
            } else {
                //$d->status = $this->_get_status(time(), $part->dtdue, true);
                $d->duestate = $this->get_deadline_status($user, $part->dtdue);
		$d->submissionstate = BLOCK_MYCOURSEWORK_SUBMISSION_SUBMITTED;

                $d->state = "Submitted";
            }
            
            $result[] = $d;
        }
        return $result;
    }
    
}
