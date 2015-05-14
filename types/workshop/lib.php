<?php
class block_mycoursework_type_workshop extends block_mycoursework_type {

	function __construct($cm) {
		parent::__construct($cm);
		$this->modname = "workshop";
		$this->viewcap = 'mod/workshop:grade';
	}

	function get_view_url($cmid, $user) {
		return new moodle_url('/mod/workshop/view.php', array(
				'id'=>$cmid,
				'action' => 'grade',
				'rownum' => 0,
				'useridlist' => $user->id
		));
	}
	function get_active_due_date() {
		switch($this->item->workshop_phase) {
			case "20":
				$this->duedate =  $this->item->workshop_duedate_submissionend;
				break;
			case "30":
				$this->duedate = $this->item->workshop_duedate_assessmentend;
				break;
		}
		return $this->duedate;
	}
	
	/**
	 * @deprecated ?
	 * @param unknown_type $userid
	 * @param unknown_type $deadline
	 * @return number|boolean
	 */
	/*
	function calculate_due_state($userid, $deadline) {
		global $DB;
		switch($this->item->workshop_phase) {
			case "20":
				if ($this->check_submission($userid)) {
					return 0;
				} else {
					return $this->_is_due($deadline);
				}
				break;
			case "30":
				//return $DB->record_exists('workshop_assessments', array('workshopid' => $this->id, 'authorid'=> $userid));
				if (false) {	//@TODO fix this 
					//$DB->record_exists('workshop_submissions', array('workshopid' => $this->id, 'authorid'=> $userid))) {
					return 0;
				} else {
					return $this->_is_due($deadline);
				}
				break;
		}
		return false;
	}
	*/
	function _is_due($deadline) {
		$rel = ($this->item->duedate - $deadline);
		if ($rel <0) {
			//item is over due
			return 2;
		} else {
			return 1;
		}
		
	}
	
	function get_deadline_objects($user, $returnsubmissionstateinfo = true) {
		global $OUTPUT, $DB;
		$submission = new block_mycoursework_deadline();
		$assessment = new block_mycoursework_deadline();
		
		$w = $DB->get_record('workshop', array('id' => $this->coursemodule->instance));
		
		$gis = grade_get_grade_items_for_activity($this->coursemodule, true);
		$gi = array_pop($gis);
		$g= $gi->get_final($user->id);
		if (!is_null($g) && !is_null($g->finalgrade)) {
			//not marked
			$submission->state = "{$g->finalgrade}";
			$submission->status = BLOCK_MYCOURSEWORK_STATUS_DONE;
			$assessment->state = "{$g->finalgrade}";
			$submission->status = BLOCK_MYCOURSEWORK_STATUS_DONE;
			$d->grade = $g;
		} else {
			$submissionrecord = $DB->get_record('workshop_submissions', array('workshopid' => $w->id, 'authorid'=> $user->id), '*',IGNORE_MISSING);
			$assessmentrecords = $DB->get_records('workshop_assessments', array('reviewerid' => $user->id));
				
			$submission->submissionstate = $this->get_submission_state($submissionrecord); 
			$submission->duestate = $this->get_deadline_status($user, $w->submissionend);
			if ($submissionrecord !== false) {
				$submission->state = "Submitted"; 
			} else {
				$submission->state = "Not Submitted";
			}
			
			$assessment->submissionstate = $this->get_assessment_state($assessmentrecords);
			$assessment->duestate = $this->get_deadline_status($user, $w->assessmentend);
			if (count($assessmentrecords) >0 ) {
				$assessment->state = "Submitted";
			} else {
				$assessment->state = "Not Submitted";
			}
		}
		
		
		$classurl = new moodle_url('/course/view.php', array('id' =>$this->activityinfo->courseid));
		$activityurl = new moodle_url('/mod/'.$this->activityinfo->modtype.'/view.php', array('id' => $this->coursemodule->id));
		
		$submission->class = $OUTPUT->action_link($classurl, $this->activityinfo->shortname);
		$submission->activityname =  $OUTPUT->action_link($activityurl, $this->activityinfo->name. "&nbsp;(Submission)");
		$submission->duedate = $w->submissionend;
		
		$assessment->class = $OUTPUT->action_link($classurl, $this->activityinfo->shortname);
		$assessment->activityname =  $OUTPUT->action_link($activityurl, $this->activityinfo->name. "&nbsp;(Assessment)");
		$assessment->duedate = $w->assessmentend;
		
		

		/*
		$d = new block_mycoursework_deadline();
		$classurl = new moodle_url('/course/view.php', array('id' =>$this->activityinfo->courseid));
		$d->class = $OUTPUT->action_link($classurl, $this->activityinfo->shortname);
		$activityurl = new moodle_url('/mod/'.$this->activityinfo->modtype.'/view.php', array('id' => $this->coursemodule->id));
		$d->activityname = $OUTPUT->action_link($activityurl, $this->activityinfo->name);
		;
		$d->status = $this->get_status($user);
		if ($returnsubmissionstateinfo) {
			$d->state = $this->get_state($user);
		} else {
			$d->state = null;
		}
		$d->duedate = $this->activityinfo->duedate;
		if ($this->activityinfo->overrideduedate) {
			$d->duedate = $this->activityinfo->overrideduedate;
		}
		*/
		return array($submission, $assessment);
	}
	function get_submission_state($submission) {
		//return $this->check_submission($user->id) ?"Submitted": "Not Submitted";
		if ($submission === false) {
			return BLOCK_MYCOURSEWORK_SUBMISSION_NOT_SUBMITTED;
		} else {
			return BLOCK_MYCOURSEWORK_SUBMISSION_SUBMITTED;
		}
	}
	function get_assessment_state($assessments) {
		//return $this->check_submission($user->id) ?"Submitted": "Not Submitted";
		if (count($assessments) == 0 ) {
			return BLOCK_MYCOURSEWORK_SUBMISSION_NOT_SUBMITTED;
		} else {
			return BLOCK_MYCOURSEWORK_SUBMISSION_SUBMITTED;
		}
				
	}
/*
	function get_submission_status($user, $duetime) {
		global $DB;
		return $this->get_deadline_status($user, $duetime);

		return $this->_get_status($submission->timemodified, $w->submissionend, true);
	}
	
	function get_assessment_status($w, $assessments){
		global $DB;
		if (count($assessments) ==0 ) {
			//not done any
			return $this->_get_status(time(), $w->assessmentend, false);
		}
		return BLOCK_MYCOURSEWORK_STATUS_DUE_SOON;//$DB->get_record('workshop_assessments	', array('workshopid' => $w->id, 'authorid'=> $user->id));
	}
	*/
	/**
	 * This gets the "current" submission status(non-PHPdoc)
	 * @see block_mycoursework_type::check_submission()
	 */
	function check_submission($user) {
		
		/*
		global $DB;
		
		$cm = get_coursemodule_from_id("workshop", $this->coursemodule->id);
		if ($cm === false) {
			//ERM there's something bad here
			throw new moodle_exception('cmnotfound','block_mycoursework');
		}
		$instance = $DB->get_record('workshop', array('id' => $cm->instance));
		$submission = $DB->get_record('workshop_submissions', array('workshopid' => $w->id, 'authorid'=> $user->id), '*',IGNORE_MISSING);
		$assessment = new stdClass();
		
		switch($this->activityinfo->workshop_phase) {
			case "20":		
				return $this->get_submission_status($instance, $submission);
				break;
			case "30":
				return $this->get_assessment_status($instances, $assessment);
				//return $DB->record_exists('workshop_assessments', array('workshopid' => $this->id, 'authorid'=> $userid));
				break;
		}
		return false;
		*/
	}
	
	/*function get_state($user) {
		
		global $DB;
	
		$cm = get_coursemodule_from_id("workshop", $this->coursemodule->id);
		if ($cm === false) {
			//ERM there's something bad here
			throw new moodle_exception('cmnotfound','block_mycoursework');
		}
		$instance = $DB->get_record('workshop', array('id' => $cm->instance));
	
		switch($this->activityinfo->workshop_phase) {
			case "20":
				return $this->get_submission_state($instance, $user);
				break;
			case "30":
				return $this->get_assessment_state($instances, $user);
				//return $DB->record_exists('workshop_assessments', array('workshopid' => $this->id, 'authorid'=> $userid));
				break;
		}
		return '';
		
	}*/
/*
	public static function get_duedate_override($userid) {
		$fields = "modassignuserflags.extensionduedate";
		$join = "LEFT JOIN {assign_user_flags} modassignuserflags
		ON
		(
		modassigntbl.id = modassignuserflags.assignment
		AND
		modassignuserflags.userid = ?
		)";
		return array($fields, $join);
		//return array('modassigngrades.extensionduedate', "left join {assign_grades} modassigngrades on (modassigntbl.id = modassigngrades.assignment and modassigngrades.userid = ?)");
	}
*/
	public static function get_duedate_field() {
		return array('submissionend','assessmentend');//,'phase');
	}

	public static function get_other_fields() {
		return array('phase');
	}

}
