<?php
/**
 * Security Scanner Class
 * 
 * Collects comprehensive security data from WordPress installation,
 * sends to Grok AI for analysis, and stores results in database
 * 
 * @package WP_AI_Guardian
 * @since 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_AIGuardian_Security_Scanner {
    
    /**
     * Grok AI Handler instance
     */
    private $grok_handler;
    
    /**
     * Database table for scans
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ai_guardian_scans';
        
        // Load Grok handler if not already loaded
        if (!class_exists('WP_AIGuardian_Grok_AI_Handler')) {
            require_once WPAIG_PLUGIN_DIR . 'includes/class-grok-ai-handler.php';
        }
        
        $this->grok_handler = new WP_AIGuardian_Grok_AI_Handler();
    }
    
    /**
     * Run complete security scan
     * 
     * @return array Scan results with AI analysis
     */
    public function run_scan(): array {
        try {
            // Collect security data
            $security_data = $this->collect_security_data();
            
            // Send to Grok AI for analysis
            $ai_analysis = $this->grok_handler->analyze_security($security_data);
            
            if (!$ai_analysis['success']) {
                return [
                    'success' => false,
                    'message' => $ai_analysis['message'] ?? 'AI analysis failed'
                ];
            }
            
            // Prepare scan results
            $results = [
                'scan_id' => uniqid('scan_', true),
                'timestamp' => current_time('mysql'),
                'security_data' => $security_data,
                'ai_analysis' => $ai_analysis,
                'score' => $ai_analysis['score'],
                'grade' => $ai_analysis['grade'],
                'vulnerabilities' => $ai_analysis['vulnerabilities'],
                'recommendations' => $ai_analysis['recommendations'],
                'critical_issues' => $ai_analysis['critical_issues']
            ];
            
            // Store in database
            $this->store_scan_results($results);
            
            return [
                'success' => true,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            error_log('WPAIG Security Scan Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => WP_DEBUG ? $e->getMessage() : 'Security scan failed'
            ];
        }
    }
    
    /**
     * Collect comprehensive security data
     * 
     * @return array Security information
     */
    private function collect_security_data(): array {
        global $wpdb;
        
        $data = [
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
            'wordpress' => $this->check_wordpress_security(),
            'plugins' => $this->check_plugins_security(),
            'themes' => $this->check_themes_security(),
            'users' => $this->check_users_security(),
            'files' => $this->check_file_permissions(),
            'database' => $this->check_database_security(),
            'ssl' => $this->check_ssl_status(),
            'configuration' => $this->check_wp_config(),
            'server' => $this->check_server_security()
        ];
        
        return $data;
    }
    
    /**
     * Check WordPress core security
     */
    private function check_wordpress_security(): array {
        global $wp_version;
        
        // Get latest WordPress version
        $latest_version = $this->get_latest_wp_version();
        
        return [
            'current_version' => $wp_version,
            'latest_version' => $latest_version,
            'is_outdated' => version_compare($wp_version, $latest_version, '<'),
            'debug_mode' => WP_DEBUG,
            'script_debug' => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG,
            'auto_updates' => $this->check_auto_updates(),
            'wp_cron_enabled' => !defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON
        ];
    }
    
    /**
     * Check plugins security
     */
    private function check_plugins_security(): array {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        $outdated = [];
        $inactive = [];
        
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $is_active = in_array($plugin_path, $active_plugins);
            
            // Check if outdated (simplified check)
            if ($is_active) {
                $update_plugins = get_site_transient('update_plugins');
                if (isset($update_plugins->response[$plugin_path])) {
                    $outdated[] = [
                        'name' => $plugin_data['Name'],
                        'current' => $plugin_data['Version'],
                        'available' => $update_plugins->response[$plugin_path]->new_version ?? 'unknown'
                    ];
                }
            } else {
                $inactive[] = $plugin_data['Name'];
            }
        }
        
        return [
            'total' => count($all_plugins),
            'active' => count($active_plugins),
            'inactive' => count($inactive),
            'outdated' => $outdated,
            'inactive_list' => array_slice($inactive, 0, 10) // First 10
        ];
    }
    
    /**
     * Check themes security
     */
    private function check_themes_security(): array {
        $all_themes = wp_get_themes();
        $active_theme = wp_get_theme();
        $outdated = [];
        
        // Check for theme updates
        $update_themes = get_site_transient('update_themes');
        
        foreach ($all_themes as $theme_slug => $theme) {
            if (isset($update_themes->response[$theme_slug])) {
                $outdated[] = [
                    'name' => $theme->get('Name'),
                    'current' => $theme->get('Version'),
                    'available' => $update_themes->response[$theme_slug]['new_version'] ?? 'unknown'
                ];
            }
        }
        
        return [
            'total' => count($all_themes),
            'active' => $active_theme->get('Name'),
            'active_version' => $active_theme->get('Version'),
            'outdated' => $outdated
        ];
    }
    
    /**
     * Check users security
     */
    private function check_users_security(): array {
        $users = get_users(['fields' => ['user_login', 'user_email', 'display_name']]);
        $admins = get_users(['role' => 'administrator']);
        
        $has_admin_username = false;
        $weak_usernames = [];
        
        foreach ($users as $user) {
            if (strtolower($user->user_login) === 'admin') {
                $has_admin_username = true;
            }
            
            // Check for common weak usernames
            if (in_array(strtolower($user->user_login), ['admin', 'administrator', 'test', 'demo', 'user'])) {
                $weak_usernames[] = $user->user_login;
            }
        }
        
        return [
            'total_users' => count($users),
            'admin_count' => count($admins),
            'has_admin_username' => $has_admin_username,
            'weak_usernames' => $weak_usernames
        ];
    }
    
    /**
     * Check file permissions
     */
    private function check_file_permissions(): array {
        $issues = [];
        
        // Check critical files
        $files_to_check = [
            'wp-config.php' => ABSPATH . 'wp-config.php',
            '.htaccess' => ABSPATH . '.htaccess',
            'index.php' => ABSPATH . 'index.php'
        ];
        
        foreach ($files_to_check as $name => $path) {
            if (file_exists($path)) {
                $perms = fileperms($path);
                $octal_perms = substr(sprintf('%o', $perms), -4);
                
                // wp-config.php should be 400, 440, or 600
                if ($name === 'wp-config.php' && !in_array($octal_perms, ['0400', '0440', '0600'])) {
                    $issues[] = [
                        'file' => $name,
                        'current' => $octal_perms,
                        'recommended' => '0600',
                        'severity' => 'high'
                    ];
                }
            }
        }
        
        // Check uploads directory
        $upload_dir = wp_upload_dir();
        if (is_dir($upload_dir['basedir'])) {
            $perms = fileperms($upload_dir['basedir']);
            $octal_perms = substr(sprintf('%o', $perms), -4);
            
            if ($octal_perms === '0777') {
                $issues[] = [
                    'file' => 'uploads directory',
                    'current' => $octal_perms,
                    'recommended' => '0755',
                    'severity' => 'medium'
                ];
            }
        }
        
        return [
            'issues_count' => count($issues),
            'issues' => $issues
        ];
    }
    
    /**
     * Check database security
     */
    private function check_database_security(): array {
        global $wpdb;
        
        // Check table prefix
        $default_prefix = 'wp_';
        $uses_default_prefix = ($wpdb->prefix === $default_prefix);
        
        // Get database size
        $db_size = $wpdb->get_var("
            SELECT SUM(data_length + index_length) / 1024 / 1024 
            FROM information_schema.TABLES 
            WHERE table_schema = '{$wpdb->dbname}'
        ");
        
        return [
            'prefix' => $wpdb->prefix,
            'uses_default_prefix' => $uses_default_prefix,
            'size_mb' => round($db_size, 2),
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate
        ];
    }
    
    /**
     * Check SSL status
     */
    private function check_ssl_status(): array {
        $is_ssl = is_ssl();
        $site_url = get_site_url();
        $home_url = get_home_url();
        
        $uses_https_site = (strpos($site_url, 'https://') === 0);
        $uses_https_home = (strpos($home_url, 'https://') === 0);
        
        return [
            'current_connection' => $is_ssl,
            'site_url_https' => $uses_https_site,
            'home_url_https' => $uses_https_home,
            'fully_enabled' => ($is_ssl && $uses_https_site && $uses_https_home)
        ];
    }
    
    /**
     * Check wp-config.php security
     */
    private function check_wp_config(): array {
        $issues = [];
        
        // Check security keys
        $keys = ['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY'];
        $undefined_keys = [];
        
        foreach ($keys as $key) {
            if (!defined($key) || constant($key) === 'put your unique phrase here') {
                $undefined_keys[] = $key;
            }
        }
        
        if (!empty($undefined_keys)) {
            $issues[] = [
                'type' => 'weak_security_keys',
                'severity' => 'high',
                'keys' => $undefined_keys
            ];
        }
        
        // Check file editing
        $file_edit_disabled = defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT;
        
        return [
            'undefined_keys' => $undefined_keys,
            'file_edit_disabled' => $file_edit_disabled,
            'issues' => $issues
        ];
    }
    
    /**
     * Check server security
     */
    private function check_server_security(): array {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'display_errors' => ini_get('display_errors') === '1',
            'expose_php' => ini_get('expose_php') === '1'
        ];
    }
    
    /**
     * Get latest WordPress version
     */
    private function get_latest_wp_version(): string {
        $version_check = get_site_transient('update_core');
        
        if (isset($version_check->updates[0]->version)) {
            return $version_check->updates[0]->version;
        }
        
        return get_bloginfo('version');
    }
    
    /**
     * Check auto-updates status
     */
    private function check_auto_updates(): bool {
        // Check if auto-updates are enabled
        if (defined('AUTOMATIC_UPDATER_DISABLED') && AUTOMATIC_UPDATER_DISABLED) {
            return false;
        }
        
        if (defined('WP_AUTO_UPDATE_CORE') && WP_AUTO_UPDATE_CORE === false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Store scan results in database
     * 
     * @param array $results Scan results to store
     * @return bool Success status
     */
    private function store_scan_results(array $results): bool {
        global $wpdb;
        
        $data = [
            'scan_type' => 'security',
            'scan_date' => $results['timestamp'],
            'score' => $results['score'],
            'grade' => $results['grade'],
            'data' => wp_json_encode($results['security_data']),
            'ai_analysis' => wp_json_encode($results['ai_analysis']),
            'recommendations' => wp_json_encode($results['recommendations']),
            'status' => 'completed'
        ];
        
        $inserted = $wpdb->insert(
            $this->table_name,
            $data,
            ['%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($inserted === false) {
            error_log('WPAIG: Failed to store scan results - ' . $wpdb->last_error);
            return false;
        }
        
        // Clean up old scans (keep last 50)
        $this->cleanup_old_scans(50);
        
        return true;
    }
    
    /**
     * Get scan history
     * 
     * @param int $limit Number of scans to retrieve
     * @return array Scan history
     */
    public function get_scan_history(int $limit = 10): array {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE scan_type = 'security' 
            ORDER BY scan_date DESC 
            LIMIT %d",
            $limit
        ), ARRAY_A);
        
        // Decode JSON fields
        foreach ($results as &$result) {
            if (isset($result['data'])) {
                $result['data'] = json_decode($result['data'], true);
            }
            if (isset($result['ai_analysis'])) {
                $result['ai_analysis'] = json_decode($result['ai_analysis'], true);
            }
            if (isset($result['recommendations'])) {
                $result['recommendations'] = json_decode($result['recommendations'], true);
            }
        }
        
        return $results;
    }
    
    /**
     * Get latest scan
     * 
     * @return array|null Latest scan results
     */
    public function get_latest_scan(): ?array {
        $history = $this->get_scan_history(1);
        return !empty($history) ? $history[0] : null;
    }
    
    /**
     * Clean up old scans
     * 
     * @param int $keep Number of scans to keep
     */
    private function cleanup_old_scans(int $keep = 50): void {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} 
            WHERE scan_type = 'security' 
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM {$this->table_name} 
                    WHERE scan_type = 'security' 
                    ORDER BY scan_date DESC 
                    LIMIT %d
                ) tmp
            )",
            $keep
        ));
    }
    
    /**
     * Get security score trend
     * 
     * @param int $days Number of days to analyze
     * @return array Score trend data
     */
    public function get_score_trend(int $days = 30): array {
        global $wpdb;
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(scan_date) as date, AVG(score) as avg_score, COUNT(*) as scan_count
            FROM {$this->table_name} 
            WHERE scan_type = 'security' 
            AND scan_date >= %s
            GROUP BY DATE(scan_date)
            ORDER BY date ASC",
            $date_from
        ), ARRAY_A);
        
        return $results;
    }
}
