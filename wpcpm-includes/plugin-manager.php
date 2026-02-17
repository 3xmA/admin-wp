<?php
if (!defined('ABSPATH')) exit;

class WPCPM_PluginManager {
    
    public static function get_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        error_reporting(E_ERROR);
        
        try {
            $all_plugins = get_plugins();
            $active_plugins = get_option('active_plugins', []);
            
            $result = [];
            
            foreach ($all_plugins as $plugin_file => $plugin_data) {
                $result[] = [
                    'file' => $plugin_file,
                    'name' => $plugin_data['Name'] ?? 'Unknown',
                    'description' => $plugin_data['Description'] ?? '',
                    'version' => $plugin_data['Version'] ?? 'N/A',
                    'author' => $plugin_data['Author'] ?? 'Unknown',
                    'active' => in_array($plugin_file, $active_plugins)
                ];
            }
            
            return [
                'success' => true,
                'data' => ['plugins' => $result]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    public static function toggle_plugin($plugin_file) {
        if (!function_exists('activate_plugin')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $active_plugins = get_option('active_plugins', []);
        
        if (in_array($plugin_file, $active_plugins)) {
            deactivate_plugins($plugin_file);
            $message = 'Plugin deactivated';
        } else {
            $result = activate_plugin($plugin_file);
            
            if (is_wp_error($result)) {
                return [
                    'success' => false,
                    'message' => $result->get_error_message()
                ];
            }
            
            $message = 'Plugin activated';
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
    }
    
    public static function delete_plugin($plugin_file) {
        if (!function_exists('delete_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        deactivate_plugins($plugin_file);
        
        $result = delete_plugins([$plugin_file]);
        
        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => $result->get_error_message()
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Plugin deleted successfully'
        ];
    }
    
    public static function upload_plugin($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Upload error'];
        }
        
        if (!function_exists('unzip_file')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        
        WP_Filesystem();
        
        $plugin_dir = WP_PLUGIN_DIR;
        $temp_file = $file['tmp_name'];
        
        $zip = new ZipArchive();
        if ($zip->open($temp_file) !== true) {
            return ['success' => false, 'message' => 'Invalid ZIP file'];
        }
        $zip->close();
        
        $result = unzip_file($temp_file, $plugin_dir);
        
        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => $result->get_error_message()
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Plugin uploaded successfully'
        ];
    }
    
    public static function update_all() {
        if (!function_exists('wp_update_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        
        if (!function_exists('wp_update_themes')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        
        wp_update_plugins();
        wp_update_themes();
        
        $updates = [
            'plugins' => 0,
            'themes' => 0,
            'core' => 0
        ];
        
        $plugin_updates = get_site_transient('update_plugins');
        if (!empty($plugin_updates->response)) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            
            $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
            
            foreach ($plugin_updates->response as $plugin => $data) {
                $result = $upgrader->upgrade($plugin);
                if (!is_wp_error($result) && $result !== false) {
                    $updates['plugins']++;
                }
            }
        }
        
        $theme_updates = get_site_transient('update_themes');
        if (!empty($theme_updates->response)) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            
            $upgrader = new Theme_Upgrader(new WP_Ajax_Upgrader_Skin());
            
            foreach ($theme_updates->response as $theme => $data) {
                $result = $upgrader->upgrade($theme);
                if (!is_wp_error($result) && $result !== false) {
                    $updates['themes']++;
                }
            }
        }
        
        $core_updates = get_core_updates();
        if (!empty($core_updates) && $core_updates[0]->response === 'upgrade') {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            
            $upgrader = new Core_Upgrader(new WP_Ajax_Upgrader_Skin());
            $result = $upgrader->upgrade($core_updates[0]);
            
            if (!is_wp_error($result) && $result !== false) {
                $updates['core'] = 1;
            }
        }
        
        return [
            'success' => true,
            'message' => 'Updates completed',
            'data' => $updates
        ];
    }
}
