<?php
require_once($CFG->dirroot.'/mod/assign/locallib.php');

class block_mycoursework_type_assign extends block_mycoursework_type {

	/**
	 * Holds a reference to the assignment activity object.
	 */
	var $_assign;
	var $_courseobject;
	function __construct($cm) {
		parent::__construct($cm);
		$this->modname = "assign";
		$this->viewcap = 'mod/assign:grade';
	}
	
	/**
	 * Return the assignment instance associated with this type.
	 */
	function assign() {
		global $DB;
		if(is_null($this->_assign)) {
			$course = $DB->get_record('course', array('id' => $this->course));
			$this->_assign = new assign(
				context_module::instance($this->coursemodule->id),
				$this->coursemodule,
				$course
			);
		}
		return $this->_assign;
	}
	
	/**
	 * Stores if this object's assign object has active submission plugins
	 */
	var $_requiresSubmission = null;
	
	/**
	 * Check if the assignment requires a submission.
	 * 
	 * This caches the result for the life of this object.
	 */
	function requiresSubmission() {
		if (is_null($this->_requiresSubmission)) {
			$skiptypes = array('strathfm');
			$a = $this->assign();
			$_requiresSubmission = false;
			foreach ($a->get_submission_plugins() as $i=>$plugin) {
				//var_dump($plugin->get_type());
				$t = $plugin->get_type();
                if (!in_array($t, $skiptypes) && $plugin->is_enabled() && $plugin->is_visible() && $plugin->allow_submissions()) {
                    $_requiresSubmission = true;
                    break;
                }
            }
			$this->_requiresSubmission = $_requiresSubmission;
		}
		return $this->_requiresSubmission;
	}

	function get_deadline_objects($user, $returnsubmissionstateinfo = true) {
		global $DB, $CFG;
		//get the assign instance
		$starttime= microtime(true);
		$deadlines = array();
		$isteamassignment = $DB->get_field('assign', 'teamsubmission', array('id' => $this->coursemodule->instance));
		$str_is_teamsubmission = '';
		
		$deadlines = parent::get_deadline_objects($user, $returnsubmissionstateinfo);
		if ($isteamassignment) {
			if (count($deadlines) != 1) {
				//something's gone horribly wrong!
				$a = new stdClass();
				$a->cmid = $this->coursemodule->id;
				print_error('incorrectnumberofdeadlinesreturned', 'block_mycoursework', $a);
				return array();
			}
			$str_is_teamsubmission = 'Group Submission';

			$candidateduestate = $deadlines[0]->duestate;
			$candidatestatestring = $deadlines[0]->state;
			$candidatename = $candidatename = $deadlines[0]->activityname.' (Group assignment)';

			//should only be a single deadline
			if ($deadlines[0]->duestate != BLOCK_MYCOURSEWORK_STATUS_DONE) {
				$course = $DB->get_record('course', array('id' => $this->coursemodule->course), '*', MUST_EXIST);
				$context = context_module::instance($this->coursemodule->id);
				require_once($CFG->dirroot.'/mod/assign/locallib.php');
				$assign = new assign($context, $this->coursemodule, $course);
				if($assign->get_instance()->requireallteammemberssubmit) {
					$candidatename = $deadlines[0]->activityname.' (Group assignment, group sign off required)';
				}
				$teamsubmission = false;

				if ($submissiongroup =$assign->get_submission_group($user->id)) {
					$teamsubmission = $assign->get_group_submission($user->id, $submissiongroup->id, false);
				}
				if (!$this->requiresSubmission()) {
					$candidateduestate = '';
				}
				else if ($submissiongroup === false || $teamsubmission === false) {
					$deadlines[0]->state = 'Not Submitted';
				} else {
					if ($teamsubmission->status == "submitted") {
						$candidatestatestring = 'Submitted';
						$candidateduestate = BLOCK_MYCOURSEWORK_STATUS_NOT_DUE;
					} else {
						$candidatestatestring = ucfirst($teamsubmission->status);
					}
					$usersubmission = $assign->get_user_submission($user->id, false);

					if($assign->get_instance()->requireallteammemberssubmit) {	//NOTE this already caches the DB record in get_instance()
						//display *our* submission status
						$memberstosubmit = $assign->get_submission_group_members_who_have_not_submitted($submissiongroup->id, true);
						$c_memberstosubmit = count($memberstosubmit);
						if ($c_memberstosubmit == 0) {
							//everyone has signed off
							$candidatestatestring = 'Submitted and Signed off';
						}else {
							if (in_array($user->id, $memberstosubmit)) {
								$candidatestatestring .= ' Not Signed off';
								//we *don't* change the status or due ness!
							} else {
								$candidatestatestring .= ' Awaiting Sign Offs by '.$c_memberstosubmit;
								//we *don't* change the status or due ness!
							}
						}
					} else {
						//display who submitted
						//'don't think we can do this cheaply
					}
				}
			}
			$deadlines[0]->activityname = $candidatename;
			$deadlines[0]->duestate = $candidateduestate;
			$deadlines[0]->state = $candidatestatestring;
			$deadlines[0]->pdaviewitemurl = $this->get_view_url($this->coursemodule->id, $user);
		} else {
			// Individual assignment
			if (
				!$this->requiresSubmission()
			) {
				/* 
				 * If there are no submissions handled by myplace, 
				 * we shall warn the user that the deadline is coming up, but once 
				 * it passes we stop nagging them.
				 */
				if (
					$deadlines[0]->duestate == BLOCK_MYCOURSEWORK_STATUS_OVERDUE 
				) {
					$deadlines[0]->duestate = BLOCK_MYCOURSEWORK_STATUS_UNKNOWN;
				}
				$deadlines[0]->state = get_string('nottracked', 'block_mycoursework');
				//var_dump($deadlines);
			} else {
				//debugging('Myplace submission required', DEBUG_DEVELOPER);
			}
		}
		$duration = microtime(true) - $starttime;
		block_mycoursework_performance("type_assign:get_deadline_objects() $str_is_teamsubmission", $duration);
		return $deadlines;
	}

	function get_view_url($cmid, $user) {
		return new moodle_url('/mod/assign/view.php', array(
				'id'=>$cmid,
				'action' => 'grade',
				'rownum' => 0,
				'userid' => $user->id
	 	    ));
	}

    /**
     *
     */
	function check_submission($user) {
		global $DB;
        if ($submissionrecord = $DB->get_record('assign_submission', array('assignment' => $this->id, 'userid'=> $user->id))) {
            switch ($submissionrecord->status) {
            /*
                // here in case we want to make these report different statuses.
                case ASSIGN_SUBMISSION_STATUS_NEW:
                case ASSIGN_SUBMISSION_STATUS_REOPENED:
                case ASSIGN_SUBMISSION_STATUS_DRAFT:
                    
                break;
            */
                case ASSIGN_SUBMISSION_STATUS_SUBMITTED:
			return BLOCK_MYCOURSEWORK_SUBMISSION_SUBMITTED;
                break;
            }
        }
		return BLOCK_MYCOURSEWORK_SUBMISSION_NOT_SUBMITTED;
	}

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

	public static function get_duedate_field() {
	    return 'duedate';
	}


}
