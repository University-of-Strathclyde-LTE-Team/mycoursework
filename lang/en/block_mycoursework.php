<?php
/**
 * block - myassignments
 * @author University of Strathclyde
 */

//any language strings should go here.
/* Note ERROR messages are at the end of this file! */
$string['pluginname'] = 'My Coursework';
$string['blockname']  = 'My Coursework';
$string['chooseauser'] = 'Please select a student';
$string['coursework']  = '{$a}\'s Coursework';

$string['myassignments'] = 'My Coursework';
$string['mymentees']    = 'My PD Students';
$string['viewmentees'] = 'My PD Students';
$string['excludedclasses'] = 'Excluded Classes';
$string['excludedclasses_desc'] = 'Students enrolled in these classes will <strong>not</strong> see the my course work block.';
$string['excludedclasses_help'] = 'Students enrolled in these classes will <strong>not</strong> see the my course work block.';
$string['overdue']    =     'Overdue';
$string['duesoon']     =     'Due Soon';
$string['notduesoon']     =     'Not Due Soon';
$string['noduedate']    = 'No Due Date';
$string['due']         =    'Due Date';
$string['name']        =    'Name';
$string['course']    =    'Course';
$string['state']    =    'State';

$string['duewindow'] =     'Number of Days before due date to flag assignments';
$string['iconinfo']  = '<ul style=\'list-style:none\'><li><img src=\'{$a->overdueicon}\' alt=\'Overdue Icon\' style=\'width:30px\' />- Submission Date has passed and no submission has been made.</li><li><img src=\'{$a->dueicon}\' style=\'width:30px\' alt=\'Due soon icon\'/> - Assignment is due soon</li><li><img src=\'{$a->notdueicon}\'  style=\'width:30px\' alt=\'Not due yet icon\'/> - Assignment isn\'t due in the next {$a->days} days.</li><li>No icon indicates a submitted assignment</li></ul>';

$string['markedasdueinXdays'] = 'Activities are flagged as due soon {$a} days before they are actually due';
$string['mark']        = 'Mark: ';

$string['modsupport'] = 'Module support';
$string['modsupport_info'] = 'These settings allow selection of which modules will be shown in the block.';

$string['mycoursework:myaddinstance'] = 'Add instance';
$string['mycoursework:addinstance'] = 'Add My coursework block to page';
$string['mycoursework:notifysubmission'] = 'Notify user of assignment submissions?';
$string['mycoursework:usemycounsellees'] = 'Use My Counsellees';
$string['mycoursework:usemycoursework'] = 'Use My Coursework';
$string['mycoursework:viewpdainformation'] = 'View PDA information about a student';
$string['mycoursework:reportmycoursework'] = 'Display Coursework Deadlines';

$string['graded']        = 'Graded. ';
$string['noactivitieswithduedate'] = 'No activities with a due date.';
$string['nogradedactivities'] = 'No graded activities';
$string['notreleased']     =    'Grade Not Released';
$string['notgraded']     =    'Not Graded';
$string['notsubmitted'] = 'Not Submitted';
$string['nottracked'] = 'Submission is not handled via Myplace';
$string['supportlabel'] = '{$a} shown';
$string['supportlabel_desc'] = 'Are "{$a}" modules shown in the MyCoursework block';
$string['viewfeedback'] = 'View Feedback';

$string['messageprovider:pdp_notification'] = 'Notification of PDP submissions by advisees';

$string['pdacountnotifylimit'] = 'PDA Limit for notifications';
$string['pdacountnotifylimit_desc'] = 'If more than this number of PDAs are being notified an email wil be sent to LTE team to notify and the delivery aborted';

$string['pdpnotificationsubject'] = 'PDP submission';
$string['pdpnotificationmessage'] = 'Student {$a->userfullname} has submitted a piece of PDP work to {$a->coursefullname}. <a href="{$a->assignmenturl}">View the submission here</a>.';
$string['pdaroles'] = 'Personal Development Advisor Roles';
$string['pdaroles_desc'] = 'Select the roles that have "block/mycoursework:viewpdainformation" capability which are classified as "PDAs"';
$string['recordperformanceinfo'] = 'Record Performance information';
$string['recordperformanceinfo_desc'] = 'Time taken to execute code blocks will be logged to <em>dataroot</em>/temp/performancing';
$string['selectstudent'] = 'Choose a Student:';
$string['nostatisticsavailable'] = 'No Statistics available';
$string['stat_user_grade_avg'] = 'User\'s Average Grade';
$string['stat_percentage_due_work_submitted'] = 'Percentage of activities (with a due date) submitted';
$string['stat_percentage_due_work_not_submitted'] = 'Percentage of activities (with a due date) not submitted';
$string['stat_percentage_all_work_completed'] = 'Percentage of listed activities  completed';
$string['stat_percentage_due_work_completed'] = 'Percentage of due activities  completed';
$string['stat_activities_due_soon'] = 'Number of activities due soon';
$string['stat_percentage_all_activities_graded'] = 'Percentage of all activities graded';
$string['stat_percentage_due_activities_graded'] = 'Percentage of activities with a due date graded';
$string['stat_percentage_not_due_activities_graded'] = 'Percentage of activities without a due date graded';
$string['nostats'] = 'No Stats available';

$string['sessionstart'] = "Session starts";
$string['submitted'] = 'Submitted';

/* errors */
$string['incorrectnumberofdeadlinesreturned'] = 'An incorrect number of deadlines was returned. CMID:{$a->cmid}';