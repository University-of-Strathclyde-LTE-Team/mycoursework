<?php
/**
 * block - myassignments
 * @author University of Strathclyde
 */
require_once("{$CFG->dirroot}/lib/gradelib.php");
require_once("{$CFG->dirroot}/mod/assignment/lib.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once("types/lib.php");
define('BLOCK_MYCOURSEWORK_MODE_MY_COURSEWORK', 0);
define('BLOCK_MYCOURSEWORK_MODE_MY_COUNSELEES', 1);

define('BLOCK_MYCOURSEWORK_SUBMISSION_UNDEFINED', -1);    
define('BLOCK_MYCOURSEWORK_SUBMISSION_NOT_SUBMITTED', 0);    
define('BLOCK_MYCOURSEWORK_SUBMISSION_SUBMITTED', 1);    

define('BLOCK_MYCOURSEWORK_STATUS_NOT_DUE', 0);    
define('BLOCK_MYCOURSEWORK_STATUS_DUE_SOON', 1);
define('BLOCK_MYCOURSEWORK_STATUS_OVERDUE', 2);    
define('BLOCK_MYCOURSEWORK_STATUS_DONE', 3);
define('BLOCK_MYCOURSEWORK_STATUS_UNKNOWN', 4);


/**
 * Returns an array of any type that has a timedue within the specifed number of days.
*/
//if (!function_exists('block_mycoursework_get_due_assignments')) {
function block_mycoursework_get_due_assignments($USER, $duewithindays = -1) {
    global $CFG, $DB;

    //@TODO need a fix that makes this more efficient for user with a large number of classes.

    if ($duewithindays == -1) {
        if (get_config('block_mycoursework', 'duewindow')) {
            $duewithindays = get_config('block_mycoursework', 'duewindow');
        } else {
            $duewithindays = 14;
        }
    }
    $duewithinseconds = $duewithindays * 24 * 60 * 60;
    $duetime = time() + $duewithinseconds;

    $ignorebefore = 0;
    $sessionstart = get_config('block_mycoursework', 'sessionstart');
    if($sessionstart){
        $ignorebefore = $sessionstart;
    }

    $assignments = array();
    $due_assignments = array();
    $overdue_assignments = array();
    $submitted_assignments = array();

    $all_assignments = block_mycoursework_get_all_user_assignments($USER);

    foreach ($all_assignments as $a) {
        if($a->duedate < $ignorebefore) {
            //if the assignment is due BEFORE the start of this session then it is not overdue - the data hasn't been changed yet.
            continue;
        }

        $str_duetime = date('r', $a->duedate);
        $rel = ($a->duedate - $duetime);

        if ( $rel >0) {
            // assignment isn't within cut off for being due.
            $assignments[] = $a;
        } else if ($rel <= 0) {
            //assignment is due or passed!
            if ($a->duedate - time() <0) {
                //assignment is passed due
                $submitted = block_mycoursework_has_submission($a, $USER->id);
                if ($submitted === false) {
                    $overdue_assignments[] = $a;
                } else {
                    //a submission has been made so it isn't over due.
                    $submitted_assignments[] = $a;
                }
                //}
            } else {
                //assignment is coming up
                $due_assignments[] = $a;
            }
        }

    }



    return array($overdue_assignments, $due_assignments, $submitted_assignments, $assignments);

}


function block_mycoursework_has_submission($o, $userid) {
    if (method_exists($o, "check_submission")) {
        return $o->check_submission($userid);
    }
    return false;
}


function block_mycoursework_is_datetime_before_cutoff($timedue, $cutoff) {
    
    $rel = ($timedue - $cutoff );
    if ( $rel >0) {
        return BLOCK_MYCOURSEWORK_STATUS_NOT_DUE;
    } else {
        if ($timedue  - time() < 0) {
            return BLOCK_MYCOURSEWORK_STATUS_OVER_DUE;
        }
    }
    return BLOCK_MYCOURSEWORK_STATUS_DUE_SOON;
}


function block_mycoursework_get_all_user_assignments($user, $sort = 'timedue ASC', $showinvisible= false) {

    //fetch a list of courses that the student is enrolled on
    $mycourses = enrol_get_users_courses($user->id);
    $assigns = block_mycoursework_get_all_instances_in_courses_at_once($mycourses, $user->id, $showinvisible);
    //usort($assigns, 'block_mycoursework_compare_times');

    return $assigns;

}

function block_mycoursework_compare_times($insta, $instb) {
    $fielda = 'timedue';
    $fieldb = 'timedue';
    if (!property_exists($insta, $fielda)) {
        $fielda = property_exists($insta, 'timeclose')?'timeclose': false;
    }
    if (!property_exists($instb, $fieldb)) {
        $fieldb = property_exists($instb, 'timeclose')?'timeclose': false ;
    }
    if ($insta->$fielda == 0) {    //means its not set so should go up!
        return 1;
    }
    if ($instb->$fieldb == 0) {    //means its not set so should go down!
        return -1;
    }

    if ($fielda === false | $fieldb === false) {
        return 1;    //always bigger so goes up the list
    }
    if ($insta->$fielda == $instb->$fieldb) {
        return 0;
    }
    return ($insta->$fielda  < $instb->$fieldb) ? -1:1;
}

function block_mycoursework_get_all_instances_in_courses_at_once($courses, $userid=null, $includeinvisible=false) {
    global $CFG, $DB;

    //$time = time();
    $modulenames = block_mycoursework_type::get_types(); 
    //block_mycoursework_get_types();
    if (!is_array($modulenames) || empty($modulenames) || !is_array($courses) || empty($courses)) {
        debugging("No courses!");
        return array();
    }

    $outputarray = array();
    foreach ($courses as $course) {

        if (!$course->visible) {
            continue;
        }

        if ($category = $DB->get_record('course_categories', array('id'=>$course->category))) {
            if (!$category->visible) {
                continue;
            }
        }

        $context = CONTEXT_COURSE::instance($course->ctxinstance);
        /*if(!has_capability('block/mycoursework:reportmycoursework', $context)) {
            continue;
        }*/

        if (!property_exists($course, 'modinfo')) {
            $course->modinfo = array();
        }
        if (!property_exists($course, 'sectioncache')) {
            $course->sectioncache = array();
        }
        $modinfo = get_fast_modinfo($course, $userid);

        foreach ($modulenames as $modulename) {
            if (empty($modinfo->instances[$modulename])) {
                //debugging("No instances!");
                continue;
            }

            $instance_ids = array();

            foreach ($modinfo->instances[$modulename] as $cm) {
                if (!$includeinvisible and !$cm->uservisible) {
                    //debugging("Not visible!");
                    continue;
                }
                $instance_ids[] = $cm->id;
            }
            if (count($instance_ids) ==0 ) {
                continue;
            }
                
            $instances = $DB->get_records_sql(
                    "SELECT
                    cm.id AS coursemodule,
                    m.*,
                    cs.section,
                    cm.visible AS visible,
                    cm.groupmode,
                    cm.groupingid,
                    cm.groupmembersonly,
                    md.name AS modname
                    FROM
                    {$CFG->prefix}course_modules cm,
                    {$CFG->prefix}course c,
                    {$CFG->prefix}course_sections cs,
                    {$CFG->prefix}modules md,
                    {$CFG->prefix}$modulename m
                    WHERE
                    cm.id IN (".implode(',',$instance_ids).") AND
                            cm.course = c.id AND
                            cm.section = cs.id AND
                            cm.module = md.id AND
                            cm.instance = m.id
                            ");

            if($instances && count($instances) > 0) {
                foreach ($instances as $instance) {
                    $objname ='block_mycoursework_type_'.$modulename;

                    if (!empty($cm->extra)) {
                        $instance->extra = urlencode($cm->extra); // bc compatibility
                    }

                    $obj = new $objname($instance);

                    $outputarray[] = $obj;
                }
            } else {
                //debugging("No instances found for CM->".$cm->id);
            }
        }
    }

//     $time = $time - time();
//     debugging("block_mycoursework_get_all_instances_in_courses_at_once Time taken: ".$time);
    return $outputarray;

}

/**
 * Returns array of user context with the associated User DB records 
 * @param unknown_type $USER
 * @return multitype:|boolean
 */
function block_mycoursework_get_mentees($USER) {
    global $CFG, $DB;
    $rawpdaRoleIds = get_config('block_mycoursework', 'pdaroles');
    $pdaRoleIds = explode(",", $rawpdaRoleIds);
    list($roleidssql, $roleidparams) = $DB->get_in_or_equal($pdaRoleIds);
    $sql = "SELECT DISTINCT c.instanceid, u.*
        FROM {role_assignments} ra
        JOIN {context} c ON ra.contextid = c.id AND c.contextlevel = ".CONTEXT_USER ."
        JOIN {user} u ON c.instanceid = u.id
        WHERE ra.userid = ? AND ra.roleid {$roleidssql}
        ORDER BY u.lastname ASC";

    $params = array($USER->id);
    $params = array_merge($params, $roleidparams);
    $usercontexts = $DB->get_records_sql($sql, $params);
    //var_dump($usercontexts);
        return $usercontexts;
//    }
    return false;
}
