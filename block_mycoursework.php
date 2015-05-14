<?php

require_once("{$CFG->dirroot}/user/profile/lib.php");
require_once("{$CFG->dirroot}/blocks/mycoursework/locallib.php");

/**
 *
 * Displays a block & UI to allow the reviewing of student course loads
 *
 * @author Learning Technology Enhancement Team, University of Strathclyde.
 * @copyright 2011 University of Strathclyde
 *
 */
class block_mycoursework extends block_base{
    public function init() {
        $this->title = get_string('pluginname', 'block_mycoursework');
        $this->cron = 0;
    }

    public function has_config() {
    	return true;
    }

    public function applicable_formats() {
        return array('my' => true);
    }

    public function get_content() {
        global $CFG, $PAGE, $USER, $COURSE;
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new StdClass;

		$starttime = microtime(true);

        $str_pluginname = get_string('pluginname', 'block_mycoursework');
        profile_load_data($USER);

        if (!property_exists($USER, 'profile_field_usertype')) {
            debugging("Required property <code>profile_field_usertype</code> doesn't exist", DEBUG_DEVELOPER);
            $this->content->text='';
            $this->content->footer='';
            return $this->content;
        }
        switch(strtolower($USER->profile_field_usertype)) {
            case 'staff':
                $this->content->text ="";
                break;
            case 'student':
            default:
            	$courses = enrol_get_my_courses();
            	$courseids = array_keys($courses);
            	$excludedclasses = explode(',', get_config('block_mycoursework','excludedclasses'));
            	foreach($courseids as $courseid) {
            		if (in_array($courseid, $excludedclasses)){
            			$this->content =null;
            			block_mycoursework_performance('block_mycoursework->get_content()-excludedclass', microtime(true) - $starttime);
						return $this->content;
            		}
            	}

            	$instances = block_mycoursework_items($USER, false);
            	$r = $PAGE->get_renderer('block_mycoursework');
            	$list = new mycoursework_block($instances);
            	$this->content->text = $r->render($list);
                break;
        }
        $this->content->footer = "<a href='{$CFG->wwwroot}/blocks/mycoursework/view.php?id={$USER->id}'>$str_pluginname</a>";

        //if (has_capability('block/mycoursework:usemycounsellees', context_system::instance())) {
            $mentees = block_mycoursework_get_mentees($USER);
            if (!empty($mentees)) {
                $str_viewmentees = get_string('viewmentees', 'block_mycoursework');
                $this->content->footer.="<br /><a href='{$CFG->wwwroot}/blocks/mycoursework/view.php?mode=1'>$str_viewmentees</a>";
            }
        //}
        $endtime= microtime(true);
        $duration = $endtime - $starttime;
        block_mycoursework_performance('block_mycoursework->get_content()', $duration);
        if (debugging()) {
//        	$this->content->footer .="<div>{$duration}secs</div>";
        }
        
        return $this->content;
    }

    public function print_item($cm, $class='', $return = true) {
        global $CFG, $DB;

		if ($cm->duedate != 0 ) {
        	$duedate = userdate($cm->duedate);
		} else {
			$duedate = "No due date";
		}


        $course = $DB->get_record('course', array('id' => $cm->course));

        $text ="<li class='$class'>{$duedate} - ";
        $text .= "<a href='{$CFG->wwwroot}/mod/{$cm->modname}/view.php?id={$cm->coursemodule}'>";
        $text .="{$cm->name}</a> - {$course->fullname}</li>";

        if (!$return) {
            echo $text;
        }
        return $text;
   	}

    public function instance_allow_multiple() {
        return false;
    }
}

