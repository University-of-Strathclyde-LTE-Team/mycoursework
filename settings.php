<?php
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $roles = get_roles_with_capability('block/mycoursework:viewpdainformation', CAP_ALLOW);
    $rolechoices = role_fix_names($roles, null, ROLENAME_ORIGINAL, true);
    if (count($rolechoices) == 0) {
        $rolechoices = array(''=>"No Appropriate Roles Configured");
    }
        $settings->add(
                new admin_setting_configmulticheckbox(
                        'block_mycoursework/pdaroles',
                        get_string('pdaroles', 'block_mycoursework'),
                        get_string('pdaroles_desc', 'block_mycoursework'),
                        '',
                        $rolechoices)
        );
/*     } else {
        echo 'No PDA appropriate roles configured';


    /* $settings->add(
            new admin_setting_configtext('block_mycoursework/pdaroles',
                    get_string('pdaroles', 'block_mycoursework'),
                    get_string('pdaroles_desc', 'block_mycoursework'),'', PARAM_INT)
    ); */
    $settings->add(
            new admin_setting_configtext('block_mycoursework/pdacountnotifylimit',
                    get_string('pdacountnotifylimit', 'block_mycoursework'),
                    get_string('pdacountnotifylimit_desc', 'block_mycoursework'),
                    20,
		    PARAM_INT)
            );
	$settings->add(
	        new admin_setting_configtext('block_mycoursework/duewindow', get_string('duewindow', 'block_mycoursework'), '', 14, PARAM_INT)

	);

	$settings->add(
			new admin_setting_configtext('block_mycoursework/sessionstart', get_string('sessionstart', 'block_mycoursework'), '', 1340600400)
	);


	$settings->add(
			new admin_setting_configtext('block_mycoursework/excludedclasses', get_string('excludedclasses', 'block_mycoursework'),
					 get_string('excludedclasses_desc','block_mycoursework'), '')
			);

	$settings->add(
			new admin_setting_configcheckbox('block_mycoursework/recordperformanceinfo',
					get_string('recordperformanceinfo', 'block_mycoursework'),
					get_string('recordperformanceinfo', 'block_mycoursework'), false)
			);

	// Module settings
	$settings->add(
	        new admin_setting_heading('modsupport', get_string('modsupport', 'block_mycoursework'), get_string('modsupport_info', 'block_mycoursework'))
	);

	$types = block_mycoursework_type::get_types();

	foreach ($types as $type) {
	    if (is_null(core_component::get_plugin_directory('mod', $type))) {
	        continue;
	    }

	    $settings->add(
	        new admin_setting_configcheckbox('block_mycoursework/support' . $type,
	                get_string('supportlabel', 'block_mycoursework', get_string('pluginname', $type)),
	                get_string('supportlabel_desc', 'block_mycoursework', get_string('pluginname', $type)),
	                false)
	    );
	}
}
