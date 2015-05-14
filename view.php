<?php
/**
 * Displays a table of all of the assignments that a student has
 * and the deadlines.
 */

require_once('../../config.php');

require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/grade/querylib.php');
require_once("{$CFG->dirroot}/user/profile/lib.php");
require_once($CFG->dirroot.'/blocks/mycoursework/locallib.php');
require_once($CFG->dirroot.'/blocks/mycoursework/lib.php');
require_login();

$pdpclassid = 17716;

$mode = optional_param('mode', false, PARAM_INT);
$context = context_system::instance();

$can_use_mycoursework = has_capability('block/mycoursework:usemycoursework', $context);

if (!$can_use_mycoursework) {
	print_error('nopermissions', 'error','', 'Use My Coursework');
}

$PAGE->set_context($context);
$PAGE->set_title('View My Coursework');
$PAGE->set_pagelayout('standard');
$mentees = false;
$uid = optional_param('id', false, PARAM_INT);

if ($mode == BLOCK_MYCOURSEWORK_MODE_MY_COUNSELEES) {
	$mentees = block_mycoursework_get_mentees($USER);
//	var_dump($mentees);
	if (count($mentees) != 0 || $mentees!== false) {
		$can_view_any_user_assignments = true;
		$u_mentees = array_values($mentees);
		if ($uid == false) {
			if (count($u_mentees) >0 && $uid === false) {
				$uid = $u_mentees[0]->instanceid;
			} else {
				$uid = false;
			}
		}
	} else {
		$mode = false;
		exit();
	}
	$pageurl = new moodle_url('/blocks/mycoursework/view.php', array('mode' => $mode, 'id' => $uid));
} else {
	$uid = $USER->id;
	$pageurl = new moodle_url('/blocks/mycoursework/view.php', array('id' => $uid));
}

$PAGE->set_url('/blocks/mycoursework/view.php', array('mode' => $mode, 'id' => $uid));

$cutoff =     time() + get_config('block_mycoursework', 'duewindow') * 24 * 60 *60;

$str_myassignments = get_string('myassignments', 'block_mycoursework');
$str_mymentees = get_string('mymentees', 'block_mycoursework');
$str_overdue = get_string('overdue', 'block_mycoursework');
$str_duesoon = get_string('duesoon', 'block_mycoursework');
$str_notdue = get_string('noduedate', 'block_mycoursework');
$str_notduesoon = get_string('notduesoon', 'block_mycoursework');
$str_due = get_string('due', 'block_mycoursework');
$str_name = get_string('name');
$str_class = get_string('course');
$str_grade =get_string('grade');
$str_graded =get_string('graded', 'block_mycoursework');
$str_notsubmitted= get_string('notsubmitted', 'block_mycoursework');
$str_submitted = get_string('submitted', 'block_mycoursework');
$str_notgraded = get_string('notgraded', 'block_mycoursework');
$str_notreleased = get_string('notreleased', 'block_mycoursework');
$str_state = '';
$str_viewfeedback = get_string('viewfeedback', 'block_mycoursework');
$str_today_marker = 'Next Assignment';
$str_status = get_string('status');
$str_mark = get_string('mark', 'block_mycoursework');
$str_selectstudent = get_string('selectstudent', 'block_mycoursework');
$str_stats = get_string('statistics');

$can_view_any_user_assignments = false;//has_capability('block/mycoursework:usemycounsellees', $context);
if ($uid != false) {
	if (! $u = $DB->get_record('user', array('id' => $uid))) {
		//echo $OUTPUT->notification('invaliduser');
		print_error('invaliduser');
		exit();
	}
	$instances = block_mycoursework_items($u);
}
/*
if ($mentees !== false) {
	//$instances = block_mycoursework_get_all_user_assignments($u, 'timedue ASC', true);
} else {
	//$instances = block_mycoursework_get_all_user_assignments($u);
	$instances = block_mycoursework_items($u);
}*/

$r = $PAGE->get_renderer('block_mycoursework');

echo $OUTPUT->header();

if ($mode == 1) {
	$PAGE->set_url('/blocks/mycoursework/view.php', array('mode' => $mode, 'id' => $uid));

	echo $OUTPUT->heading($str_mymentees);
	echo $OUTPUT->box_start('generalbox generaltable');    //displays the user jump list
	echo $str_selectstudent;
	$m_options = array();
	foreach ($mentees as $m) {
		$m_options[$m->instanceid] = fullname($m);
	}
	$selecturl = new moodle_url('/blocks/mycoursework/view.php',
			array('mode' => 1)
	);
	echo $OUTPUT->single_select($selecturl, 'id', $m_options, $uid);
	echo $OUTPUT->box_end();    //end of the user jump list
	if ($uid !== false) {
		$stats = new block_mycoursework_stats($u);
		$stats->fill_stats();
		echo $OUTPUT->heading(fullname($u));    //display the seelected user name

		//user information container
		echo $OUTPUT->container_start('', 'block_mycourses_staff_info');
		echo $OUTPUT->container(
				get_string('profile').": <a href='{$CFG->wwwroot}/user/view.php?id={$u->id}' class='headercolor'>".fullname($u)."</a>"
				);
		echo $OUTPUT->container_start();


		//icons/legend container
		$a= new stdclass;
		$a->overdueicon = "{$CFG->wwwroot}/blocks/mycoursework/pix/redalert.png";
		$a->dueicon =  "{$CFG->wwwroot}/blocks/mycoursework/pix/LibraryDueNow.png";
		$a->notdueicon =  "{$CFG->wwwroot}/blocks/mycoursework/pix/green.png";
		$a->days = get_config('block_mycoursework', 'duewindow');
		echo $OUTPUT->box(
				'<strong>Icons</strong>'.get_string('iconinfo', 'block_mycoursework', $a). $r->render($stats),
				'',
				'block_mycoursework_legend_staff'
		);


		echo $OUTPUT->container_end();
		echo $OUTPUT->container_end();
	} else {
		echo $OUTPUT->box(get_string('chooseauser','block_mycoursework'));
	}
} else {
	$u = $USER;
	echo $OUTPUT->heading($str_myassignments);
	echo $OUTPUT->box_start();
	echo get_string('markedasdueinXdays', 'block_mycoursework', get_config('block_mycoursework', 'duewindow')).block_mycoursework_get_pdp_link($u);
	echo $OUTPUT->box_end();
}
if ($uid !== false) {
	$list = new mycoursework_list($instances,$pageurl, $mode);
	echo $r->render($list);
}
echo $OUTPUT->footer();
exit();

