<?php
if (!defined('ABSPATH')) exit;

class WPCPM_SystemInfo {
    
    public static function get_info() {
        global $wpdb;
        
        $info = [
            'php_version' => phpversion(),
            'mysql_version' => $wpdb->db_version(),
            'wordpress_version' => get_bloginfo('version'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'php_memory_limit' => ini_get('memory_limit'),
            'wp_memory_limit' => WP_MEMORY_LIMIT,
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_input_vars' => ini_get('max_input_vars'),
            'disk_space' => self::get_disk_space(),
            'db_size' => self::get_database_size(),
            'active_plugins' => count(get_option('active_plugins', [])),
            'total_plugins' => count(self::get_all_plugins()),
            'active_theme' => wp_get_theme()->get('Name'),
            'site_url' => get_site_url(),
            'home_url' => get_home_url(),
            'wp_debug' => WP_DEBUG ? 'Enabled' : 'Disabled',
            'wp_debug_log' => WP_DEBUG_LOG ? 'Enabled' : 'Disabled',
            'multisite' => is_multisite() ? 'Yes' : 'No',
            'language' => get_locale(),
            'timezone' => get_option('timezone_string') ?: 'UTC',
            'date_format' => get_option('date_format'),
            'time_format' => get_option('time_format'),
        ];
        
        return [
            'success' => true,
            'data' => $info
        ];
    }
    
    private static function get_disk_space() {
        $total = disk_total_space(ABSPATH);
        $free = disk_free_space(ABSPATH);
        $used = $total - $free;
        
        return self::format_bytes($used) . ' / ' . self::format_bytes($total) . 
               ' (' . round(($used / $total) * 100, 2) . '% used)';
    }
    
    private static function get_database_size() {
        global $wpdb;
        
        $size = $wpdb->get_var("
            SELECT SUM(data_length + index_length) 
            FROM information_schema.TABLES 
            WHERE table_schema = '{$wpdb->dbname}'
        ");
        
        return self::format_bytes($size);
    }
    
    private static function get_all_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        return get_plugins();
    }
    
    private static function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
