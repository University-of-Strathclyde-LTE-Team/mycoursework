<?php

class block_mycoursework_eventhandler {
    
    public static function assessable_uploaded(assignsubmission_file\event\assessable_uploaded $event) {
        global $DB;
        $data = $event->get_data();
        if (!$DB->record_exists('enrol', array('enrol' => 'strathpdp', 'courseid' => $data['courseid']))) {
            return;
        }
        if (!$course = $DB->get_record('course', array('id' => $data['courseid']))) {
            return;
        }
        if (!$user = $DB->get_record('user', array('id' => $data['userid']))) {
            return;
        }
        if (!$submission = $DB->get_record($data['objecttable'], array('id' => $data['objectid']))) {
            return;
        }

        $usercontext = context_user::instance($data['userid']);
        $user->fullname = fullname($user);
        $a = new stdClass();
        $a->user = $user;
        $a->userfullname = fullname($user);
        $a->coursefullname = $course->fullname;
        $cm = get_coursemodule_from_instance('assign', $submission->assignment);
        $assignmenturl = new moodle_url('/mod/assign/view.php', array('id' => $cm->id, 'action' => 'grading'));
        $a->assignmenturl = $assignmenturl->out();
        
        $subject = get_string('pdpnotificationsubject', 'block_mycoursework', $a);
        $message = get_string('pdpnotificationmessage', 'block_mycoursework', $a);
        
        // Construct message
        $eventdata = new stdClass();
        $eventdata->component         = 'block_mycoursework'; //your component name
        $eventdata->name              = 'pdp_notification'; //this is the message name from messages.php
        $eventdata->userfrom          = get_admin();
        // $eventdata->userto            = $touser;
        $eventdata->subject           = $subject;
        $eventdata->fullmessage       = '';
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml   = $message;
        $eventdata->smallmessage      = '';
        $eventdata->notification      = 1; //this is only set to 0 for personal messages between users
        
        /*
         * We target all users on the submitting user to receive a notification
         */
        $pdas = get_users_by_capability($usercontext, 'block/mycoursework:viewpdainformation');
        $c_pdas = count($pdas);
        $countlimit= get_config('block_mycoursework','pdacountnotifylimit');
        
        if (is_numeric($countlimit) && $c_pdas >= $countlimit) {
            email_to_user(core_user::get_support_user(), core_user::get_noreply_user(), 
                    "PDA Notification Limit Breached", 
                    "Myplace attempted to send a PDP submission notification to more than {$c_pdas} Personal Development Advisors.\n
                    The notifications have *not* been sent as this may represent a poor configuration.\n
                    This information should be sent on to the LTE Developers.\n\n
                    JSON Encoded version event data follows:\n
                    =============================================================================================================\n
                    ".json_encode($event));
        } else {
            foreach ($pdas as $pda) {
                $eventdata->userto = $pda;
                message_send($eventdata);
            }
        }
    }
    
}
