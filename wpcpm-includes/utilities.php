<?php
if (!defined('ABSPATH')) exit;

class WPCPM_Utilities {
    
    public static function clean_database() {
        global $wpdb;
        
        $deleted = 0;
        
        $revisions = $wpdb->query("
            DELETE FROM {$wpdb->posts} 
            WHERE post_type = 'revision'
        ");
        $deleted += $revisions;
        
        $spam = $wpdb->query("
            DELETE FROM {$wpdb->comments} 
            WHERE comment_approved = 'spam'
        ");
        $deleted += $spam;
        
        $trash = $wpdb->query("
            DELETE FROM {$wpdb->comments} 
            WHERE comment_approved = 'trash'
        ");
        $deleted += $trash;
        
        $orphaned_meta = $wpdb->query("
            DELETE FROM {$wpdb->commentmeta} 
            WHERE comment_id NOT IN (SELECT comment_ID FROM {$wpdb->comments})
        ");
        $deleted += $orphaned_meta;
        
        $orphaned_post_meta = $wpdb->query("
            DELETE FROM {$wpdb->postmeta} 
            WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})
        ");
        $deleted += $orphaned_post_meta;
        
        $transients = $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%' 
            OR option_name LIKE '_site_transient_%'
        ");
        $deleted += $transients;
        
        $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table[0]}");
        }
        
        return [
            'success' => true,
            'message' => 'Database cleaned successfully',
            'data' => ['deleted' => $deleted]
        ];
    }
    
    public static function clear_cache() {
        wp_cache_flush();
        
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        global $wpdb;
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%' 
            OR option_name LIKE '_site_transient_%'
        ");
        
        flush_rewrite_rules();
        
        self::clear_plugin_cache();
        
        return [
            'success' => true,
            'message' => 'Cache cleared successfully'
        ];
    }
    
    private static function clear_plugin_cache() {
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        
        if (class_exists('LiteSpeed_Cache_API')) {
            LiteSpeed_Cache_API::purge_all();
        }
        
        if (class_exists('autoptimizeCache')) {
            autoptimizeCache::clearall();
        }
    }
    
    public static function toggle_maintenance($enable) {
        $maintenance_file = ABSPATH . '.maintenance';
        
        if ($enable) {
            $content = '<?php $upgrading = ' . time() . '; ?>';
            file_put_contents($maintenance_file, $content);
            $message = 'Maintenance mode enabled';
        } else {
            if (file_exists($maintenance_file)) {
                unlink($maintenance_file);
            }
            $message = 'Maintenance mode disabled';
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
    }
    
    public static function get_error_log() {
        $log_file = ini_get('error_log');
        
        if (!$log_file || !file_exists($log_file)) {
            $possible_logs = [
                ABSPATH . 'wp-content/debug.log',
                ABSPATH . 'error_log',
                '/var/log/php_errors.log'
            ];
            
            foreach ($possible_logs as $log) {
                if (file_exists($log)) {
                    $log_file = $log;
                    break;
                }
            }
        }
        
        if (!$log_file || !file_exists($log_file)) {
            return [
                'success' => false,
                'message' => 'Error log not found'
            ];
        }
        
        $lines = [];
        $fp = fopen($log_file, 'r');
        
        if ($fp) {
            fseek($fp, -1, SEEK_END);
            $pos = ftell($fp);
            $line_count = 0;
            
            while ($pos > 0 && $line_count < 1000) {
                fseek($fp, $pos--);
                $char = fgetc($fp);
                
                if ($char === "\n") {
                    $line_count++;
                }
            }
            
            while (!feof($fp)) {
                $lines[] = fgets($fp);
            }
            
            fclose($fp);
        }
        
        return [
            'success' => true,
            'data' => [
                'log' => implode('', $lines),
                'file' => $log_file,
                'size' => filesize($log_file)
            ]
        ];
    }
    
    public static function get_redirects() {
        $redirects = get_option('wpcpm_redirects', []);
        
        return [
            'success' => true,
            'data' => ['redirects' => $redirects]
        ];
    }
    
    public static function save_redirect($from, $to, $type = '301') {
        $redirects = get_option('wpcpm_redirects', []);
        
        $redirects[] = [
            'id' => uniqid(),
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'created' => time()
        ];
        
        update_option('wpcpm_redirects', $redirects);
        
        if (!has_action('template_redirect', [__CLASS__, 'handle_redirects'])) {
            add_action('template_redirect', [__CLASS__, 'handle_redirects']);
        }
        
        return [
            'success' => true,
            'message' => 'Redirect saved successfully'
        ];
    }
    
    public static function delete_redirect($id) {
        $redirects = get_option('wpcpm_redirects', []);
        
        $redirects = array_filter($redirects, function($redirect) use ($id) {
            return $redirect['id'] !== $id;
        });
        
        update_option('wpcpm_redirects', array_values($redirects));
        
        return [
            'success' => true,
            'message' => 'Redirect deleted successfully'
        ];
    }
    
    public static function handle_redirects() {
        $redirects = get_option('wpcpm_redirects', []);
        $current_url = $_SERVER['REQUEST_URI'];
        
        foreach ($redirects as $redirect) {
            if ($redirect['from'] === $current_url) {
                wp_redirect($redirect['to'], intval($redirect['type']));
                exit;
            }
        }
    }
}

add_action('template_redirect', ['WPCPM_Utilities', 'handle_redirects']);
