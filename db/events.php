<?php

use assignment_online\event\assessable_uploaded;
// Events 2 API code

$observers = array(
	
    array(
	   'eventname' => 'assignsubmission_file\event\assessable_uploaded',
       'callback' => 'block_mycoursework_eventhandler::assessable_uploaded'
    )

);