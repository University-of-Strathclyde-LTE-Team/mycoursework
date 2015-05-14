<?php
/**
 * block - myassignments
 * @author University of Strathclyde
 */

$capabilities = array(

		//TODO: deprecate this cap
    'block/mycoursework:usemycoursework' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'student' => CAP_ALLOW
        )
    ),
    'block/mycoursework:reportmycoursework' => array(
				'riskbitmask' => RISK_PERSONAL,
				'captype' => 'write',
				'contextlevel' => CONTEXT_COURSE,
				'archetypes' => array(
						'manager' => CAP_ALLOW,
						'student' => CAP_ALLOW
				)
	),
    'block/mycoursework:myaddinstance' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),

    'block/mycoursework:addinstance' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
        )
    ),
    'block/mycoursework:viewpdainformation' => array(
            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_USER,
            'archetypes' => array(
            )
    ),
    /**
     * Should user be notified of assignment submissions
     */
    'block/mycoursework:notifysubmission' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array()
    ),
);
