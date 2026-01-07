<?php
if (!defined('ABSPATH')) exit;

class DIM_Module_Manager {

    public static function get_all_modules() {
        $modules_dir = DIM_PATH . 'modules/';
        $modules = [];

        if (!is_dir($modules_dir)) return $modules;

        foreach (glob($modules_dir . '*', GLOB_ONLYDIR) as $dir) {
            $key = basename($dir);
            $modules[$key] = [
                'label' => ucwords(str_replace('-', ' ', $key)),
                'path'  => $dir,
                'file'  => $dir . '/' . $key . '.php',
            ];
        }

        return $modules;
    }

    public static function get_enabled_modules() {
        return get_option('dim_enabled_modules', []);
    }

    public static function is_enabled($key) {
        $enabled = self::get_enabled_modules();
        return !empty($enabled[$key]);
    }

    public static function save($posted_modules) {
        $all = self::get_all_modules();
        $clean = [];

        foreach ($all as $key => $data) {
            $clean[$key] = isset($posted_modules[$key]);
        }

        update_option('dim_enabled_modules', $clean);
    }
}
