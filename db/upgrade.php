<?php

function xmldb_block_mycoursework_upgrade($oldversion, $block) {
    global $DB;

    // Move settings to plugin settings
    if ($oldversion < 2014032100) {
        set_config('duewindow', get_config('', 'block_mycoursework_duewindow'), 'block_mycoursework');
        set_config('sessionstart', get_config('', 'block_mycoursework_sessionstart'), 'block_mycoursework');
        // Savepoint reached.
        upgrade_block_savepoint(true, 2014032100, 'mycoursework');
    }

    return true;
}