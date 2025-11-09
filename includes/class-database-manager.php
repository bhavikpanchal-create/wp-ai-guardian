<?php
/**
 * Database Manager Class
 * 
 * Handles database schema creation and updates for:
 * - wp_ai_guardian_scans table
 * - wp_ai_guardian_grok_usage table
 * - wp_ai_guardian_spam_training table
 * - WordPress options initialization
 * 
 * @package WP_AI_Guardian
 * @since 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_AIGuardian_Database_Manager {
    
    /**
     * Database version
     */
    private const DB_VERSION = '1.0';
    
    /**
     * Install/update database tables
     */
    public static function install_tables(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Create scans table
        self::create_scans_table($charset_collate);
        
        // Create Grok usage table
        self::create_grok_usage_table($charset_collate);
        
        // Create spam training table
        self::create_spam_training_table($charset_collate);
        
        // Initialize options
        self::initialize_options();
        
        // Update database version
        update_option('wpaig_db_version', self::DB_VERSION);
    }
    
    /**
     * Create scans table
     */
    private static function create_scans_table(string $charset_collate): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_guardian_scans';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            scan_type varchar(50) NOT NULL,
            scan_date datetime NOT NULL,
            score int(3) DEFAULT 0,
            grade varchar(2) DEFAULT 'F',
            data longtext,
            ai_analysis longtext,
            recommendations longtext,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY scan_type (scan_type),
            KEY scan_date (scan_date),
            KEY score (score),
            KEY status (status)
        ) {$charset_collate};";
        
        dbDelta($sql);
    }
    
    /**
     * Create Grok usage table
     */
    private static function create_grok_usage_table(string $charset_collate): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_guardian_grok_usage';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usage_date date NOT NULL,
            request_type varchar(50) NOT NULL,
            request_count int(10) DEFAULT 1,
            tokens_used int(10) DEFAULT 0,
            response_time_ms int(10) DEFAULT 0,
            success_count int(10) DEFAULT 0,
            error_count int(10) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usage_date_type (usage_date, request_type),
            KEY usage_date (usage_date),
            KEY request_type (request_type)
        ) {$charset_collate};";
        
        dbDelta($sql);
    }
    
    /**
     * Create spam training table
     */
    private static function create_spam_training_table(string $charset_collate): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_guardian_spam_training';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            content text NOT NULL,
            author varchar(255) DEFAULT '',
            email varchar(255) DEFAULT '',
            classification varchar(20) NOT NULL,
            confidence int(3) DEFAULT 0,
            spam_score int(3) DEFAULT 0,
            indicators text,
            feedback varchar(20) DEFAULT NULL,
            feedback_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY classification (classification),
            KEY feedback (feedback),
            KEY created_at (created_at),
            KEY author (author(100)),
            KEY email (email(100))
        ) {$charset_collate};";
        
        dbDelta($sql);
    }
    
    /**
     * Initialize WordPress options
     */
    private static function initialize_options(): void {
        $default_options = [
            // Grok API settings
            'wpaig_grok_api_key' => '',
            'wpaig_grok_last_test' => '',
            'wpaig_grok_status' => 'not_configured',
            
            // Auto-moderation settings
            'wpaig_auto_moderate_comments' => false,
            'wpaig_auto_spam_threshold' => 80,
            
            // Scan settings
            'wpaig_auto_scan_enabled' => false,
            'wpaig_auto_scan_frequency' => 'daily',
            'wpaig_last_security_scan' => '',
            'wpaig_last_performance_scan' => '',
            
            // Usage tracking
            'wpaig_grok_usage' => [],
            'wpaig_feature_usage' => [
                'security_scans' => 0,
                'performance_scans' => 0,
                'spam_checks' => 0,
                'conflict_checks' => 0
            ],
            
            // Notification settings
            'wpaig_email_notifications' => false,
            'wpaig_notification_email' => get_option('admin_email'),
            'wpaig_notify_on_critical' => true,
            
            // Display settings
            'wpaig_dashboard_widgets' => true,
            'wpaig_show_admin_bar_menu' => true,
            
            // Advanced settings
            'wpaig_cache_results' => true,
            'wpaig_cache_duration' => 3600, // 1 hour
            'wpaig_debug_mode' => false,
            
            // Migration flag
            'wpaig_migrated_from_hf' => false
        ];
        
        foreach ($default_options as $option_name => $default_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $default_value);
            }
        }
    }
    
    /**
     * Check if database needs update
     * 
     * @return bool True if update needed
     */
    public static function needs_update(): bool {
        $current_version = get_option('wpaig_db_version', '0');
        return version_compare($current_version, self::DB_VERSION, '<');
    }
    
    /**
     * Get database statistics
     * 
     * @return array Database stats
     */
    public static function get_stats(): array {
        global $wpdb;
        
        $stats = [];
        
        // Scans table stats
        $scans_table = $wpdb->prefix . 'ai_guardian_scans';
        $stats['scans'] = [
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$scans_table}"),
            'security' => $wpdb->get_var("SELECT COUNT(*) FROM {$scans_table} WHERE scan_type = 'security'"),
            'performance' => $wpdb->get_var("SELECT COUNT(*) FROM {$scans_table} WHERE scan_type = 'performance'"),
            'last_scan' => $wpdb->get_var("SELECT MAX(scan_date) FROM {$scans_table}")
        ];
        
        // Grok usage stats
        $usage_table = $wpdb->prefix . 'ai_guardian_grok_usage';
        $stats['grok_usage'] = [
            'total_requests' => $wpdb->get_var("SELECT SUM(request_count) FROM {$usage_table}"),
            'total_tokens' => $wpdb->get_var("SELECT SUM(tokens_used) FROM {$usage_table}"),
            'this_month' => $wpdb->get_var(
                "SELECT SUM(request_count) FROM {$usage_table} 
                WHERE usage_date >= DATE_FORMAT(NOW(), '%Y-%m-01')"
            )
        ];
        
        // Spam training stats
        $training_table = $wpdb->prefix . 'ai_guardian_spam_training';
        $stats['spam_training'] = [
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$training_table}"),
            'spam' => $wpdb->get_var("SELECT COUNT(*) FROM {$training_table} WHERE classification = 'spam'"),
            'legitimate' => $wpdb->get_var("SELECT COUNT(*) FROM {$training_table} WHERE classification = 'legitimate'"),
            'with_feedback' => $wpdb->get_var("SELECT COUNT(*) FROM {$training_table} WHERE feedback IS NOT NULL")
        ];
        
        // Table sizes
        $stats['table_sizes'] = [
            'scans' => self::get_table_size($scans_table),
            'grok_usage' => self::get_table_size($usage_table),
            'spam_training' => self::get_table_size($training_table)
        ];
        
        return $stats;
    }
    
    /**
     * Get table size in MB
     * 
     * @param string $table_name Table name
     * @return float Size in MB
     */
    private static function get_table_size(string $table_name): float {
        global $wpdb;
        
        $size = $wpdb->get_var($wpdb->prepare(
            "SELECT (data_length + index_length) / 1024 / 1024 
            FROM information_schema.TABLES 
            WHERE table_schema = %s 
            AND table_name = %s",
            $wpdb->dbname,
            $table_name
        ));
        
        return round((float)$size, 2);
    }
    
    /**
     * Clean up old data
     * 
     * @param int $days Keep data newer than this many days
     * @return array Cleanup results
     */
    public static function cleanup_old_data(int $days = 90): array {
        global $wpdb;
        
        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $results = [];
        
        // Clean scans table
        $scans_table = $wpdb->prefix . 'ai_guardian_scans';
        $deleted_scans = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$scans_table} WHERE scan_date < %s",
            $date_threshold
        ));
        $results['scans_deleted'] = $deleted_scans;
        
        // Clean Grok usage table
        $usage_table = $wpdb->prefix . 'ai_guardian_grok_usage';
        $deleted_usage = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$usage_table} WHERE usage_date < %s",
            date('Y-m-d', strtotime("-{$days} days"))
        ));
        $results['usage_deleted'] = $deleted_usage;
        
        // Clean spam training table
        $training_table = $wpdb->prefix . 'ai_guardian_spam_training';
        $deleted_training = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$training_table} WHERE created_at < %s AND feedback IS NULL",
            $date_threshold
        ));
        $results['training_deleted'] = $deleted_training;
        
        // Optimize tables
        $wpdb->query("OPTIMIZE TABLE {$scans_table}");
        $wpdb->query("OPTIMIZE TABLE {$usage_table}");
        $wpdb->query("OPTIMIZE TABLE {$training_table}");
        
        $results['success'] = true;
        $results['date_threshold'] = $date_threshold;
        
        return $results;
    }
    
    /**
     * Export data for backup
     * 
     * @param string $type Data type to export
     * @return array Exported data
     */
    public static function export_data(string $type = 'all'): array {
        global $wpdb;
        
        $export = [];
        
        if ($type === 'all' || $type === 'scans') {
            $scans_table = $wpdb->prefix . 'ai_guardian_scans';
            $export['scans'] = $wpdb->get_results(
                "SELECT * FROM {$scans_table} ORDER BY scan_date DESC LIMIT 100",
                ARRAY_A
            );
        }
        
        if ($type === 'all' || $type === 'usage') {
            $usage_table = $wpdb->prefix . 'ai_guardian_grok_usage';
            $export['grok_usage'] = $wpdb->get_results(
                "SELECT * FROM {$usage_table} ORDER BY usage_date DESC LIMIT 100",
                ARRAY_A
            );
        }
        
        if ($type === 'all' || $type === 'spam_training') {
            $training_table = $wpdb->prefix . 'ai_guardian_spam_training';
            $export['spam_training'] = $wpdb->get_results(
                "SELECT * FROM {$training_table} WHERE feedback IS NOT NULL LIMIT 1000",
                ARRAY_A
            );
        }
        
        $export['exported_at'] = current_time('mysql');
        $export['site_url'] = get_site_url();
        
        return $export;
    }
    
    /**
     * Drop all plugin tables (for uninstall)
     */
    public static function drop_tables(): void {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'ai_guardian_scans',
            $wpdb->prefix . 'ai_guardian_grok_usage',
            $wpdb->prefix . 'ai_guardian_spam_training'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
    
    /**
     * Reset all data (keep tables)
     */
    public static function reset_data(): void {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'ai_guardian_scans',
            $wpdb->prefix . 'ai_guardian_grok_usage',
            $wpdb->prefix . 'ai_guardian_spam_training'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("TRUNCATE TABLE {$table}");
        }
    }
}
