<?php

require_once($CFG->dirroot.'/blocks/mycoursework/lib.php');
function block_mycoursework_performance($block, $duration) {
	global $CFG;
	static $displayed = false;
	if (get_config('block_mycoursework','recordperformanceinfo')) {
		error_log("{$block} {$duration} \n", 3,"{$CFG->dataroot}/temp/performancing");
	} else {
		if ($displayed == false) {
//			debugging('Mycoursework performance recording is disabled', DEBUG_DEVELOPER);
			$displayed = true;
		}

	}
}
/**
 * Get all relevant items for the currently logged in user.
 */
function block_mycoursework_items($user = false, $returnsubmissionstateinfo = true) {
	global $CFG, $USER;
	if ($user == false) {
		$user = $USER;
	}
	$starttime = microtime(true);
	$rawitems = block_mycoursework_raw_items($user, $returnsubmissionstateinfo);

	if (get_config('block_mycoursework', 'duewindow')) {
		$duewithindays = get_config('block_mycoursework', 'duewindow');
	} else {
		$duewithindays = 14;
	}

	$duewithinseconds = $duewithindays * 24 * 60 * 60;
	$duetime = time() + $duewithinseconds;

	$ignorebefore = 0;
    $sessionstart = get_config('block_mycoursework', 'sessionstart');
	if($sessionstart){
		$ignorebefore = $sessionstart;
	}

	$items = array();
	$due_items = array();
	$overdue_items = array();
	$submitted_items = array();
	$duetime = time();

	foreach ($rawitems as $item) {

		// Map mod fields onto item. We could let these be mapped in the type
		// constructor, but they're required fields so we do them here
		foreach (array('id', 'name') as $attr) {
			$attrprop = $item->modtype.'_'.$attr;
			$item->$attr = $item->$attrprop;
		}

		// Check whether mod is relevant
		if ($item->duedate !=0 && $item->duedate < $ignorebefore) {
			continue;
		}
		// Check whether mod is available to the user
		$cm = get_coursemodule_from_id($item->modtype, $item->coursemoduleid);
		if (!\core_availability\info_module::is_user_visible($cm, $user->id)) {
			continue;
		}
		$item->coursemodule = $cm;
		$objname ='block_mycoursework_type_'.$item->modtype;
		$itemobj = new $objname($item);

		$deadlines = $itemobj->get_deadline_objects($user, $returnsubmissionstateinfo);
		foreach($deadlines as $d) {
			$items[] = $d;
		}
	}

	//now sort based on the item's duedate value
	usort($items, function($a, $b) {
		if ($a->duedate == $b->duedate) {
			return 0;
		}
		if ($a->duedate == 0) {
			return 1;
		}
		if ($b->duedate == 0) {
			return -1;
		}
		return ($a->duedate < $b->duedate) ? -1 :1;
	});
	$duration = microtime(true) - $starttime;
	block_mycoursework_performance('block_mycoursework_items', $duration);

	return $items;
}

function block_mycoursework_raw_items($user = false, $returnsubmissionstateinfo = true) {
    global $CFG, $USER, $DB;
    $starttime =  microtime(true);
    if ($user === false ) {
    	$user = $USER;
    }
    $types = block_mycoursework_type::get_types();
    $prefix = $CFG->prefix;

    $modfields = array();
    $duedatefields = array(); // needed for COALESCE
    $idfields = array(); // needed for another COALESCE
    $duedateoverridefields = array();
    $duedateoverrideparams = array();
    $useridparamcount = 2; // How many instances of the user id do we need to pass?
    $modcount = 0; // For generating table alias
    $modlines = array();
    $validtypes = array();
    $modotherfields = array(); // cache of otherfields
    $config = (array)get_config('block_mycoursework');

    if (!$installedmodules = $DB->get_records('modules', array(), 'name ASC', 'name, visible')) {
        print_error('moduledoesnotexist', 'error');
    }

    foreach ($types as $type) {

        if (!isset($installedmodules[$type])) {
            continue;
        }

        if (!$installedmodules[$type]->visible) {
            continue;
        }

        if (!isset($config['support' . $type]) || !$config['support' . $type]) {
            continue;
        }

        require_once($CFG->dirroot.'/blocks/mycoursework/types/' . $type . '/lib.php');
        if (!method_exists('block_mycoursework_type_'.$type, 'get_duedate_field')) {
            debugging("Mycoursework type $type does not support get_duedate_field() method");
            continue;
        }
        $validtypes[] = $type;
        $modalias = 'mod'.$type.'tbl'; //mod' . $modcount++;
        $modfields[] = "{$modalias}.name '{$type}_name'";
        $modfields[] = "{$modalias}.id '{$type}_id'";
        $idfields[] = "{$modalias}.id";
        $duedatefield = call_user_func('block_mycoursework_type_'.$type.'::get_duedate_field');
        if (is_array($duedatefield)) {
        	foreach($duedatefield as $fieldname) {
        		$modfields[] = "{$modalias}.{$fieldname} '{$type}_duedate_{$fieldname}'";
        		$duedatefields[] = "{$modalias}.{$fieldname}";
        	}
        } else {
        	$modfields[] = "{$modalias}.{$duedatefield} '{$type}_duedate'";
        	$duedatefields[] = "{$modalias}.{$duedatefield}";
        }

        if (method_exists('block_mycoursework_type_'.$type, 'get_other_fields')) {
            $modotherfields[$type] = array();
	        $otherfields = call_user_func('block_mycoursework_type_'.$type.'::get_other_fields');
	        if (is_array($otherfields)) {
	        	foreach($otherfields as $fieldname) {
	        	    $modotherfields[$type][] = $fieldname;
	         		$modfields[] = "{$modalias}.{$fieldname} '{$type}_{$fieldname}'";
	        	}
	        } else {
	            $modotherfields[$type] = $otherfields;
	         	$modfields[] = "{$modalias}.{$duedatefield} '{$type}_{$otherfields}'";
			}
        }

        //$modlines[] = "left join $prefix{$type} {$modalias} on ({$modalias}.id = cm.instance and m.name = '{$type}')";
        $typejoins = call_user_func('block_mycoursework_type_'.$type.'::get_joins', $type, $modalias);
        $modlines = array_merge($modlines, $typejoins);

        if (method_exists('block_mycoursework_type_'.$type, 'get_duedate_override')) {
            $duedateoverride = call_user_func('block_mycoursework_type_'.$type.'::get_duedate_override', $user->id);
            if (!empty($duedateoverride[0])) {
                $modfields[] = "{$duedateoverride[0]} '{$type}_duedate_override'";
                $duedateoverridefields[] = "$duedateoverride[0]";
                $modlines[] = $duedateoverride[1];
                $useridparamcount++;
            }
        }
    }

    // MYC-62. If no types enabled, don't crash
    if (count($validtypes) == 0) {
        return array();
    }

    //$coalesce = "COALESCE (" . implode(', ', array_merge($duedateoverridefields, $duedatefields)) . ")";
    $duedatecoalesce = "COALESCE (" . implode(', ', $duedatefields) . ")";
    $overrideduedatecoalesce = "COALESCE (" . implode(', ', $duedateoverridefields) . ")";
    $idfieldscoalesce = "COALESCE (" . implode(', ', $idfields) . ")";
    $sql = "SELECT DISTINCT cm.id, cm.id coursemoduleid, m.name 'modtype', c.id 'courseid', c.shortname, " . implode(', ', $modfields)
        . ", $duedatecoalesce duedate, $overrideduedatecoalesce overrideduedate from {course_modules} cm
        join {modules} m on cm.module = m.id "
        . implode(' ', $modlines)
        ." join {course} c on c.id = cm.course
        join {enrol} e on c.id = e.courseid
        join {user_enrolments} ue on e.id = ue.enrolid
        where ue.userid = ?
        and ue.status = 0
        and cm.visible = 1
        and m.name in ('" . implode("', '", $validtypes) . "')
        and $idfieldscoalesce IS NOT NULL
        order by duedate, c.id";

    //echo $sql;
    // If we ever try to filter by ignorebefore here, remember we can't use aliases in WHERE:
    // http://stackoverflow.com/questions/942571/using-column-alias-in-where-clause-of-mysql-query-produces-an-error
	//$DB->set_debug(true);
    $rawitems = $DB->get_records_sql($sql, array_fill(0, $useridparamcount, $user->id));
    $duration = microtime(true) - $starttime;
    block_mycoursework_performance('block_mycoursework_raw_items', $duration);
    return $rawitems;
}
        /*

    	if (true){//$item->duedate != 0) {

    		//item doesn't have a due date
	        $str_duetime = date('r', $item->duedate);
	        $rel = ($item->duedate - $duetime);

	        if (method_exists($itemobj, 'calculate_due_state')) {
	        	$duestate = $itemobj->calculate_due_state($user->id, $duetime);
	        	switch ($duestate) {
	        		case 0:	//not due
	        			$submitted_items[] = $itemobj;
	        			break;
	        		case 1: //due
	        			$due_items[] = $itemobj;
	        			break;
	        		case 2: //overdue;
	        			$overdue_items[] = $itemobj;
	        			break;
	        	}
	        } else {

		        if ( $rel >0) {
					// assignment isn't within cut off for being due.
					$items[] = $itemobj;
				} else if ($rel <= 0) {
					//assignment is due or passed!
					if ($item->duedate - time() <0) {
						//assignment is passed due
						$submitted = block_mycoursework_has_submission($item, $user->id);
						if ($submitted === false) {
							$itemobj->submitted = false;
							$overdue_items[] = $itemobj;
						} else {
							//a submission has been made so it isn't over due.
							$itemobj->submitted = true;
							$submitted_items[] = $itemobj;
						}
						//}
					} else {
						//assignment is coming up
						$due_items[] = $itemobj;
					}
				}
	        }

		}
		$items[] = $itemobj;
    }
    // TODO: Some kind of sort in here????
    return array($overdue_items, $due_items, $submitted_items, $items);
}*/
function block_mycoursework_get_pdp_link($u) {
	global $OUTPUT;
    
	$out = '';
	profile_load_data($u);
	/*
	 * SPIDER PDP handling
	*/
	$strspiderpdp = '';
	$ppio         = false;
	$cluster      = false;
	$regno        = false;
	if (property_exists($u, 'profile_field_registrationno')) {
		$regno = $u->profile_field_registrationno;
	} else {
		echo $OUTPUT->notification("Profile Field <code>registrationno</code> must be available");
	}
	if (property_exists($u, 'profile_field_progteachingorgcode')) {
		$ppio = $u->profile_field_progteachingorgcode;
	} else {
		$out .= $OUTPUT->notification("Profile Field <code>progteachingorgcode</code> must be available");
	}
	return $out;
}
