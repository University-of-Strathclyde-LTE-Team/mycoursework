<?php

require_once($CFG->dirroot.'/grade/querylib.php');

/**
 * Defines base assignmnent interaction class
 * @author igs03102
 *
 */
abstract class block_mycoursework_type {

	public $id = "";
	public $name = "";
	public $coursemodule = null;
	/**
	 * Holds data returned from DB
	 */
	public $activityinfo = null;
	public $course = "";

	public $duedate = "";
	public $modname = "";

	public $viewcap = "";

	public $submitted = false;
	
	//private $_has_submission;

	/**
	 * 
	 * @param stdClass $activityinfo Database information about the activity and it's due dates
	 */
	public function __construct($activityinfo) {
 		$this->id = $activityinfo->id;
		$this->name = $activityinfo->name;
		$this->course = $activityinfo->courseid;
		$this->coursemodule = $activityinfo->coursemodule;
		$this->activityinfo = $activityinfo;
		$this->duedate = $activityinfo->duedate;
		
		// Magically bind properties
		$prefix = $activityinfo->modtype.'_';
	    foreach (get_object_vars($activityinfo) as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $attr = str_replace($prefix, '', $key);
                if (!property_exists($this, $attr)) {
                    $this->$attr = $value;
                }
            }
        }
	}

	/**
	 * Function to check if the assigment has been submitted by user
	 * @param unknown_type $assignment
	 */
	abstract protected function check_submission($user);
	
	private $_has_submission = null;
	function has_submission($user) {
		if (is_null($this->_has_submission)) {
			$this->_has_submission = $this->check_submission($user);
			
		} 
		return $this->_has_submission;
	}
	
	/**
	 * Returns a state string based on the submitted,not submitted, graded 
	 * @param unknown_type $user
	 * @return string
	 */
	function get_state($user) {
		return $this->has_submission($user->id) ?"Submitted": "Not Submitted";
	}
	
	static function get_timedue() {
		if (get_config('block_mycoursework', 'duewindow')) {
			$duewithindays = get_config('block_mycoursework', 'duewindow');
		} else {
			$duewithindays = 14;
		}
		$now = time();
		$duewithinseconds = $duewithindays * 24 * 60 * 60;
		$duetime = $now + $duewithinseconds;
		return $duetime;
	}
	/**
	 * Returns the "dueness" of the deadline
	 * @param stdClass $user User object
	 * @returns int a BLOCK_MYCOURSEWORK_STATUS_* constant indicating if the deadline is not due, due soon or over due.
	 */
	function get_deadline_status($user, $duetime) {
		if ($duetime == 0) {
			return BLOCK_MYCOURSEWORK_STATUS_NOT_DUE;
		}
		$now = time();
		$timedue = block_mycoursework_type::get_timedue();;
		$rel = $timedue - $duetime; 
		if ( $duetime > $timedue) {
			// assignment's due time later than the window period so definitately isn't due soon.
			//echo 'Not due within 14 days';
			return BLOCK_MYCOURSEWORK_STATUS_NOT_DUE;
		} else if ($duetime <= $now) {
				//echo 'time passed';
				return BLOCK_MYCOURSEWORK_STATUS_OVERDUE;
		} else {
			//Due date is within 14 days (or defined window) so due soon
			//echo 'due within 14 days';
			return BLOCK_MYCOURSEWORK_STATUS_DUE_SOON;
		}
	}

	/**
	 * Returns a deadline object for each deadline in the activity.
	 * 
	 * Default implementation returns a single deadline with either the 
	 * activity's due date or a duedate that has been overridden for a specific user.
	 * @param $user
	 * @param $duetime time to check against
	 * @param $returnsubmissionstateinfo If you aren't going to need the information 
	 * on the submission's state (e.g not submitted|submitted|not graded|graded) then pass in
	 * false.
	 * @returns array Array of block_mycoursework_deadline objects
	 */
	function get_deadline_objects($user, $returnsubmissionstateinfo = true) {
		global $OUTPUT;
		$d = new block_mycoursework_deadline();


		$d->duedate = $this->activityinfo->duedate;

		$gis = false;
		$gi = false;
		$gr = false; //this is the DB grade_grade record
		$g = false;  //This is tbe grade_grade object that corresponds to $gr
		$gf = false;
		$gis = grade_get_grade_items_for_activity($this->coursemodule, true);

		$fg = false;		//actual grade
		$gmax = false;		//max grade possible
		if ($gis !== false) {
			$gi = array_pop($gis);
		}
		if ($gi !== false) {
			$gr= $gi->get_final($user->id);
			if (is_null($gr)) {
				$gr = false;
			}
		}

		if ($gr !== false) {
			$g = new grade_grade(array('userid' => $user->id, 'itemid' => $gi->id));
			if (!is_null($gr->finalgrade)) {
				$d->grade = $gr;
				$fg  = round($g->finalgrade, $gi->decimals);
				$gmax = round($g->rawgrademax, $gi->decimals);
				
			}
			
		}

//		if ($gis !== false && $gi !== false && $g !== false && $fg !== false) {
		if ($fg !== false && $gmax !== false) {
			if ($g == false || $g->is_hidden()) {
				$d->state = get_string('notreleased', 'block_mycoursework');
			} else {
				$d->state = "{$fg}/{$gmax}";
			}
			$d->pdaviewitemurl = $this->get_view_url($this->coursemodule->id, $user);
/*
			if ($g!== false && !empty($g->feedback)) {
				$d->state .= "<h3>Feedback</h3>".$g->feedback;
			}
*/
			$d->submissionstate = BLOCK_MYCOURSEWORK_SUBMISSION_SUBMITTED;
			$d->duestate = BLOCK_MYCOURSEWORK_STATUS_DONE;
			//$d->duestate = BLOCK_MYCOURSEWORK_STATUS_NOT_DUE;
		} else {
			
			$d->submissionstate = $this->has_submission($user);
			$d->duestate = $this->get_deadline_status($user, $d->duedate);
			$d->state = $this->get_state($user);
/*
				$d->state .='<pre>';
				$d->state .= print_r($gis, true);
				$d->state .= print_r($gi,true);
				$d->state .= print_r($g,true);
				$d->state .= '</pre>';
*/
			
		}
		
		$classurl = new moodle_url('/course/view.php', array('id' =>$this->activityinfo->courseid));
		$d->class = $OUTPUT->action_link($classurl, $this->activityinfo->shortname);
		$activityurl = new moodle_url('/mod/'.$this->activityinfo->modtype.'/view.php', array('id' => $this->coursemodule->id));	
		$d->activityname = $OUTPUT->action_link($activityurl, $this->activityinfo->name); 
		$d->viewitemurl = $activityurl;
		if ($this->activityinfo->overrideduedate) {
			$d->duedate = $this->activityinfo->overrideduedate;
		}
		return array($d);
	}

	/**
	 * Function to return the name of the field containing the due date
	 * for the activity (ignoring user extensions)
	 */
	public static function get_duedate_field() {
		return null;
	}
	
	/**
	 * Get SQL values to override the due date for a given user.
	 *
	 * @return array column name, join SQL fragment
	 */
	public static function get_duedate_override($userid) {
		return array(null, null);
	}
	
	/**
	 * Get joins to other tables required for this module
	 * 
	 * @param string $type the module type (e.g. choice)
	 * @param string $modalias the alias for the module
	 */
	public static function get_joins($type, $modalias) {
	    global $CFG;
	    // We use prefix here to avoid horrible syntax with the variable interpolations
	    return array("left join {$CFG->prefix}{$type} {$modalias} on ({$modalias}.id = cm.instance and m.name = '{$type}')");
	}

	function get_view_url($cmid, $user) {
		return false;
	}

	static function get_types() {
		global $CFG;
		$types = get_list_of_plugins('/blocks/mycoursework/types/');
		foreach ($types as $type) {
			$path = $CFG->dirroot.'/blocks/mycoursework/types/'.$type.'/lib.php';
			if (file_exists($path)) {
				require_once($path);
			}
		}
		
		return $types;
	}
}
