<?php
if (!defined('ABSPATH')) exit;

add_action('plugins_loaded', function () {
    // Kill switch: disable all modules if constant is defined
    if (defined('DIM_KILL_SWITCH') && DIM_KILL_SWITCH === true) {
        return;
    }

    $modules = DIM_Module_Manager::get_all_modules();

    foreach ($modules as $key => $module) {

        if (
            DIM_Module_Manager::is_enabled($key)
            && file_exists($module['file'])
        ) {
            require_once $module['file'];
        }
    }
});
