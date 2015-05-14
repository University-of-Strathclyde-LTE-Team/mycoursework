<?php

/**
 * Defines the data displayed on the mycoursework table.
 * @author igs03102
 *
 */
class block_mycoursework_deadline {
	
	/**
	 * Status column text
	 * @var string
	 */
	public $duestate;
	
	/**
	 * 
	 * @var unknown_type
	 */
	public $submissionstate;
	
	/**
	 * Reported Due date for activity deadline
	 * @var int
	 */
	public $duedate;
	
	/**
	 * Display text for class
	 * 
	 * This probably should be a hyperlink that includes the name of the class
	 * @var string
	 */
	public $class;
	/**
	 * Display text for activity
	 * This should be the hyperlink to the activity/student submission page
	 * @var string
	 */
	public $activityname;
	/**
	 * Submission "state"
	 * 
	 * Should be a hyperlink to the student's submission
	 * Typical text is Not Submitted | Submitted | Not Graded | Graded. XXXgrade 
	 * @var unknown_type
	 */
	public $state;
	
	/**
	 * 
	 * @var moodle_url
	 */
	public $viewitemurl = null;

	/**
	 * 
	 * @var moodle_url
	 */
	public $pdaviewitemurl = null;
	
	/**
	 * Holds the grades_grade object that represents the final grade
	 * 
	 * Null if not graded
	 * @var unknown_type
	 */
	public $grade = null;

	
}