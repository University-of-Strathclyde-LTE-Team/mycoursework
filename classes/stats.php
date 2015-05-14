<?php
/**
 * Stats generator for MyCoursework
 * @author igs03102
 *
 */
class block_mycoursework_stats implements renderable{
	private $_items;

	/*Stats counters*/
	private $c_user_activity = 0;
	private $c_user_activity_with_due_date = 0;
	private $c_user_activity_with_due_date_submitted = 0;
	private $c_user_activity_with_due_date_submitted_and_graded = 0;
	private $c_user_activity_with_due_date_submitted_and_ungraded = 0;
	private $c_user_activity_with_due_date_notsubmitted = 0;
	private $c_user_activity_with_due_date_notsubmitted_and_graded = 0;
	private $c_user_activity_without_due_date = 0;
	private $c_user_activity_without_due_date_submitted = 0;
	private $c_user_activity_without_due_date_notsubmitted = 0;
	private $c_user_activity_due_soon = 0;
	private $c_user_activity_submitted = 0;
	private $c_user_activity_not_submitted = 0;
	private $c_user_activity_graded = 0;
	private $c_user_activity_ungraded = 0;
	private $c_user_activity_submission_undefined =0;
	private $c_user_averagegrade = 0;
	private $i_user_grade_running_total =0;
	private $i_user_grade_running_max =0;
	private $i_user_averagegrade_calc ='';
	private $p_deadlines_submitted = 0;
	private $p_deadlines_notsubmitted = 0;
	private $p_activity_completion=0;



	function __construct($user) {
		//$activities = block_mycoursework_items($user);
		$activities = block_mycoursework_raw_items($user);
		$this->_user = $user;
		$this->_items = $activities;
	}

	function __get($name) {
		$prefixes = array('c_','p_');
		foreach($prefixes as $prefix) {
			$propname = "{$prefix}{$name}";

			if (property_exists($this, $propname)) {

				return $this->$propname;
			}
		}
	}

	function dump() {
		$vars = get_object_vars($this);
		echo "<pre>";
		//var_dump($vars);
		echo "Working variables<br />";
		foreach($vars as $name=>$value) {
			if (substr($name,0,2)=="i_") {
				$n = str_pad($name, 55," ", STR_PAD_LEFT);
				$v = str_pad($value, 5," ", STR_PAD_LEFT);
				echo "{$n}:{$v}</br >";
			}
		}
		echo "Final stats<br />";
		foreach($vars as $name=>$value) {
			if (substr($name,0,2)=="c_") {
				$n = str_pad($name, 55," ", STR_PAD_LEFT);
				$v = str_pad($value, 5," ", STR_PAD_LEFT);
				echo "{$n}:{$v}";
				//echo trim(var_dump($value));
				echo "</br >";
			}
		}
		echo "Percentage stats<br />";
		foreach($vars as $name=>$value) {
			if (substr($name,0,2)=="p_") {
				$n = str_pad($name, 55," ", STR_PAD_LEFT);
				$v = str_pad(round($value*100,0).'%', 5," ", STR_PAD_LEFT);
				echo "{$n}:{$v}";
				//echo trim(var_dump($value));
				echo "</br >";
			}
			}
		echo "</pre>";
	}
	/**
	 * Internal method that performs the actual loading of stats
	 *
	 * This is just in case we ever want to re-plumb the generation without
	 * changing the exposed interface.
	 */
	function _fill_stats2() {
		$this->_items = block_mycoursework_items($this->_user);	//throw away the "raw" items the constructor fetched.
		foreach($this->_items as $i) {
			//var_dump($i);

			if ($i->submissionstate == BLOCK_MYCOURSEWORK_SUBMISSION_UNDEFINED) {
				//deadlines that we can't define a submission for get skipped and don't contribute to stats
				$this->c_user_activity_submission_undefined++;
			} else {
				$this->c_user_activity++;
				if ($i->duedate) {	//activities with Due dates
					$this->c_user_activity_with_due_date++;
					if ($i->submissionstate == BLOCK_MYCOURSEWORK_SUBMISSION_SUBMITTED) {
						$this->c_user_activity_with_due_date_submitted++;
					} else {
						$this->c_user_activity_with_due_date_notsubmitted++;
					}

				} else {
					//does not have due date
					$this->c_user_activity_without_due_date++;
					if ($i->submissionstate == BLOCK_MYCOURSEWORK_SUBMISSION_SUBMITTED) {
						$this->c_user_activity_without_due_date_submitted++;


					} else {
						$this->c_user_activity_without_due_date_notsubmitted++;
					}
				}

				if ($i->duestate == BLOCK_MYCOURSEWORK_STATUS_DONE) {
					//$this->c_user_activity_graded++;
				} else if ($i->duestate == BLOCK_MYCOURSEWORK_STATUS_OVERDUE) {

				} else if ($i->duestate == BLOCK_MYCOURSEWORK_STATUS_DUE_SOON) {
					$this->c_user_activity_due_soon++;
				} else if ($i->duestate == BLOCK_MYCOURSEWORK_STATUS_NOT_DUE) {

				}
				if($i->submissionstate == BLOCK_MYCOURSEWORK_SUBMISSION_NOT_SUBMITTED) {
					$this->c_user_activity_not_submitted++;
					if (!is_null($i->grade)) {
						//activity not submitted but a grade awarded
						$this->c_user_activity_with_due_date_notsubmitted_and_graded++;
					} else{
						//$this->c_user_activity_with_due_date_notsubmitted_and_ungraded++;
					}
				} else if ($i->submissionstate == BLOCK_MYCOURSEWORK_SUBMISSION_SUBMITTED) {
					$this->c_user_activity_submitted++;
					if (!is_null($i->grade)) {
						//activity submitted and a grade awarded
						$this->c_user_activity_with_due_date_submitted_and_graded++;
					} else {
						$this->c_user_activity_with_due_date_submitted_and_ungraded++;
					}
				}
				//perform grade book check if we can
				if (!is_null($i->grade)){
					//if (!$i->grade->hidden) {
						//we have a grade item in the dead line object
					$this->c_user_activity_graded++;
					$this->i_user_grade_running_total+= $i->grade->finalgrade;	//this is only going to work if all grades are out of the same value!
					if ($this->i_user_averagegrade_calc =='') {
						$this->i_user_averagegrade_calc .= $i->grade->finalgrade;
					} else {
						$this->i_user_averagegrade_calc .= " + ".$i->grade->finalgrade;
					}
					//}
				} else {
					$this->c_user_activity_ungraded++;
				}
			}
			//This section applies to everything
			if ($this->c_user_activity_graded >0) {
				$this->c_user_averagegrade = $this->i_user_grade_running_total / $this->c_user_activity_graded;

			} else {
				$this->c_user_averagegrade = null;//'No Graded activities';
			}
		}
		$this->i_user_averagegrade_calc = "({$this->i_user_averagegrade_calc})/{$this->c_user_activity_graded}";//'<br />=>'. $this->i_user_grade_running_total ."/".$this->c_user_activity_graded;

		//calculate percentage values
		$this->p_activity_completion = $this->c_user_activity_submitted / $this->c_user_activity ;

		if ( $this->c_user_activity_with_due_date >0) {
		    $this->p_deadlines_submitted = $this->c_user_activity_with_due_date_submitted / $this->c_user_activity_with_due_date;
		    $this->p_deadlines_notsubmitted = $this->c_user_activity_with_due_date_notsubmitted / $this->c_user_activity_with_due_date;
		} else {
		    $this->p_deadlines_submitted = get_string('noactivitieswithduedate', 'block_mycoursework');
		    $this->p_deadlines_notsubmitted = get_string('noactivitieswithduedate', 'block_mycoursework');
		}
	}
	function fill_stats() {
		$this->_fill_stats2();
	}
	function _fillstats() {
		$activitygrades_dump = array();
		foreach($this->_items as $item) {
			// Check whether mod is available to the user

			foreach (array('id', 'name') as $attr) {
				$attrprop = $item->modtype.'_'.$attr;
				$item->$attr = $item->$attrprop;
			}

			// Check whether mod is relevant
			/* if ($item->duedate < $ignorebefore) {
				continue;
			} */
			$cm = get_coursemodule_from_id($item->modtype, $item->coursemoduleid);
			if (!\core_availability\info_module::is_user_visible($cm, $this->_user->id)) {
				continue;
			}
			$item->coursemodule = $cm;
			$objname ='block_mycoursework_type_'.$item->modtype;
			$itemobj = new $objname($item);
			//$itemobj->check_submission($this->_user);
			$itemobj->has_submission($this->_user);

			//load grade information for activity & user
			$gis = false;
			$gi = false;
			$g = false;

			$gis = grade_get_grade_items_for_activity($item->coursemodule, true);
			if ($gis !== false) {
				$gi = array_pop($gis);
			}
			if ($gi !== false) {
				$g= $gi->get_final($this->_user->id);
			}

			if ($gis && $gi && g && !is_null($g->finalgrade)) {
				//stat is graded
				$this->c_user_activity_graded++;
				$this->i_user_grade_running_total+= $g->finalgrade;
				$this->c_user_activity_with_due_date_submitted_and_graded++;
			} else {
				//may or may not be submitted, but definitely not graded
				if ($itemobj->has_submission($this->_user)) {
					if ($itemobj->duedate) {
						$this->c_user_activity_with_due_date_submitted++;
						$this->c_user_activity_with_due_date_submitted_and_ungraded++;
					} else {
						$this->c_user_activity_without_due_date_submitted++;
					}
				} else {
					if ($item->duedate) {
						$this->c_user_activity_with_due_date_notsubmitted++;
					} else {
						$this->c_user_activity_without_due_date_notsubmitted++;
					}
				}
			}
			if ($item->duedate) {
				$this->c_user_activity_with_due_date++;
			} else {
				$this->c_user_activity_without_due_date++;
			}

			$this->c_user_activity++;	//always increment the number of user activities

		}
		//finished filling the data
		if ($this->c_user_activity_graded >0) {
			echo 'ag';
			$c_user_averagegrade = $this->i_user_grade_running_total / $this->c_user_activity_graded;
		} else {
			$c_user_averagegrade = 'No Graded activities';
		}
	}
}