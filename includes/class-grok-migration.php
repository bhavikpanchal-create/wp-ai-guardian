<?php
/**
 * Grok API Migration Handler
 * 
 * Handles migration from Hugging Face/other AI providers to Grok API:
 * - Test connection
 * - Settings migration
 * - API key management
 * - Feature compatibility check
 * 
 * @package WP_AI_Guardian
 * @since 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_AIGuardian_Grok_Migration {
    
    /**
     * Grok AI Handler instance
     */
    private $grok_handler;
    
    /**
     * Constructor
     */
    public function __construct() {
        if (!class_exists('WP_AIGuardian_Grok_AI_Handler')) {
            require_once WPAIG_PLUGIN_DIR . 'includes/class-grok-ai-handler.php';
        }
        
        $this->grok_handler = new WP_AIGuardian_Grok_AI_Handler();
    }
    
    /**
     * Check if migration is needed
     * 
     * @return array Migration status
     */
    public function check_migration_status(): array {
        $old_api_key = get_option('wpaig_hf_api_key', '');
        $grok_api_key = get_option('wpaig_grok_api_key', '');
        $migrated = get_option('wpaig_migrated_from_hf', false);
        
        $status = [
            'needs_migration' => !empty($old_api_key) && empty($grok_api_key) && !$migrated,
            'old_provider' => $this->detect_old_provider($old_api_key),
            'has_old_key' => !empty($old_api_key),
            'has_new_key' => !empty($grok_api_key),
            'migration_completed' => $migrated,
            'recommendation' => ''
        ];
        
        if ($status['needs_migration']) {
            $status['recommendation'] = 'Migrate to Grok API for enhanced AI features';
        } elseif (empty($grok_api_key)) {
            $status['recommendation'] = 'Configure Grok API key to enable AI features';
        } else {
            $status['recommendation'] = 'Grok API configured and ready';
        }
        
        return $status;
    }
    
    /**
     * Detect old AI provider
     * 
     * @param string $api_key Old API key
     * @return string Provider name
     */
    private function detect_old_provider(string $api_key): string {
        if (empty($api_key)) {
            return 'none';
        }
        
        // Detect by key prefix/format
        if (strpos($api_key, 'hf_') === 0) {
            return 'huggingface';
        } elseif (strpos($api_key, 'sk-') === 0) {
            return 'openai';
        } elseif (strpos($api_key, 'gsk_') === 0) {
            return 'groq';
        } elseif (strpos($api_key, 'pplx-') === 0) {
            return 'perplexity';
        }
        
        return 'unknown';
    }
    
    /**
     * Perform automatic migration
     * 
     * @param string $grok_api_key New Grok API key
     * @return array Migration results
     */
    public function migrate(string $grok_api_key): array {
        try {
            // Validate Grok API key
            update_option('wpaig_grok_api_key', $grok_api_key);
            
            $test_result = $this->test_grok_connection();
            
            if (!$test_result['success']) {
                update_option('wpaig_grok_api_key', ''); // Rollback
                return [
                    'success' => false,
                    'message' => 'Grok API key validation failed: ' . ($test_result['message'] ?? 'Unknown error'),
                    'test_result' => $test_result
                ];
            }
            
            // Migrate settings
            $migrated_settings = $this->migrate_settings();
            
            // Mark migration as complete
            update_option('wpaig_migrated_from_hf', true);
            update_option('wpaig_migration_date', current_time('mysql'));
            
            // Update provider status
            update_option('wpaig_grok_status', 'active');
            update_option('wpaig_grok_last_test', current_time('mysql'));
            
            return [
                'success' => true,
                'message' => 'Successfully migrated to Grok API',
                'test_result' => $test_result,
                'migrated_settings' => $migrated_settings,
                'provider' => 'grok'
            ];
            
        } catch (Exception $e) {
            error_log('WPAIG Migration Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => WP_DEBUG ? $e->getMessage() : 'Migration failed'
            ];
        }
    }
    
    /**
     * Test Grok connection
     * 
     * @return array Test results
     */
    public function test_grok_connection(): array {
        $result = $this->grok_handler->test_connection();
        
        // Update status
        if ($result['success']) {
            update_option('wpaig_grok_status', 'connected');
            update_option('wpaig_grok_last_test', current_time('mysql'));
        } else {
            update_option('wpaig_grok_status', 'error');
        }
        
        // Log test
        $this->log_connection_test($result);
        
        return $result;
    }
    
    /**
     * Migrate settings from old provider
     * 
     * @return array Migrated settings
     */
    private function migrate_settings(): array {
        $migrated = [];
        
        // Migrate premium status (if it exists)
        $old_premium = get_option('wpaig_is_premium', false);
        if ($old_premium) {
            update_option('wpaig_is_premium', true);
            $migrated['premium_status'] = true;
        }
        
        // Migrate usage data structure
        $old_usage = get_option('wpaig_usage', []);
        if (!empty($old_usage)) {
            $new_usage = [
                'ai_calls' => $old_usage['ai_calls'] ?? 0,
                'security_scans' => $old_usage['scans'] ?? 0,
                'performance_scans' => 0,
                'spam_checks' => 0
            ];
            update_option('wpaig_feature_usage', $new_usage);
            $migrated['usage_data'] = true;
        }
        
        // Migrate notification settings
        $old_email_notify = get_option('wpaig_email_notifications');
        if ($old_email_notify !== false) {
            update_option('wpaig_email_notifications', $old_email_notify);
            $migrated['notifications'] = true;
        }
        
        return $migrated;
    }
    
    /**
     * Get connection status
     * 
     * @return array Connection status info
     */
    public function get_connection_status(): array {
        $grok_api_key = get_option('wpaig_grok_api_key', '');
        $status = get_option('wpaig_grok_status', 'not_configured');
        $last_test = get_option('wpaig_grok_last_test', '');
        
        $status_data = [
            'configured' => !empty($grok_api_key),
            'status' => $status,
            'last_test' => $last_test,
            'key_preview' => !empty($grok_api_key) ? 'xai-' . substr($grok_api_key, -8) : '',
            'status_label' => $this->get_status_label($status),
            'status_color' => $this->get_status_color($status)
        ];
        
        // Add usage stats if connected
        if ($status === 'connected' || $status === 'active') {
            $status_data['usage'] = $this->grok_handler->get_usage_stats();
        }
        
        return $status_data;
    }
    
    /**
     * Get human-readable status label
     * 
     * @param string $status Status code
     * @return string Status label
     */
    private function get_status_label(string $status): string {
        $labels = [
            'not_configured' => 'Not Configured',
            'connected' => 'Connected',
            'active' => 'Active',
            'error' => 'Connection Error',
            'testing' => 'Testing Connection...'
        ];
        
        return $labels[$status] ?? 'Unknown';
    }
    
    /**
     * Get status color
     * 
     * @param string $status Status code
     * @return string Color code
     */
    private function get_status_color(string $status): string {
        $colors = [
            'not_configured' => '#999',
            'connected' => '#46b450',
            'active' => '#46b450',
            'error' => '#dc3232',
            'testing' => '#00a0d2'
        ];
        
        return $colors[$status] ?? '#999';
    }
    
    /**
     * Log connection test
     * 
     * @param array $result Test result
     */
    private function log_connection_test(array $result): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_guardian_logs';
        
        $message = $result['success'] ? 
            'Grok API connection successful' : 
            'Grok API connection failed: ' . ($result['message'] ?? 'Unknown error');
        
        $wpdb->insert(
            $table_name,
            [
                'type' => 'grok_connection',
                'message' => $message,
                'timestamp' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }
    
    /**
     * Get migration history
     * 
     * @return array Migration history
     */
    public function get_migration_history(): array {
        return [
            'migrated' => get_option('wpaig_migrated_from_hf', false),
            'migration_date' => get_option('wpaig_migration_date', ''),
            'old_provider' => $this->detect_old_provider(get_option('wpaig_hf_api_key', '')),
            'current_provider' => 'grok',
            'grok_configured' => !empty(get_option('wpaig_grok_api_key', ''))
        ];
    }
    
    /**
     * Rollback migration (restore old settings)
     * 
     * @return array Rollback results
     */
    public function rollback(): array {
        try {
            // Clear Grok settings
            update_option('wpaig_grok_api_key', '');
            update_option('wpaig_grok_status', 'not_configured');
            update_option('wpaig_migrated_from_hf', false);
            
            return [
                'success' => true,
                'message' => 'Migration rolled back successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => WP_DEBUG ? $e->getMessage() : 'Rollback failed'
            ];
        }
    }
    
    /**
     * Get Grok API analytics
     * 
     * @param int $days Number of days to analyze
     * @return array Analytics data
     */
    public function get_analytics(int $days = 30): array {
        global $wpdb;
        
        $usage_table = $wpdb->prefix . 'ai_guardian_grok_usage';
        $date_from = date('Y-m-d', strtotime("-{$days} days"));
        
        // Get usage by type
        $usage_by_type = $wpdb->get_results($wpdb->prepare(
            "SELECT request_type, SUM(request_count) as total_requests, SUM(tokens_used) as total_tokens
            FROM {$usage_table}
            WHERE usage_date >= %s
            GROUP BY request_type
            ORDER BY total_requests DESC",
            $date_from
        ), ARRAY_A);
        
        // Get daily usage
        $daily_usage = $wpdb->get_results($wpdb->prepare(
            "SELECT usage_date, SUM(request_count) as requests, SUM(tokens_used) as tokens
            FROM {$usage_table}
            WHERE usage_date >= %s
            GROUP BY usage_date
            ORDER BY usage_date ASC",
            $date_from
        ), ARRAY_A);
        
        // Get success/error rates
        $success_rate = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(success_count) as successes,
                SUM(error_count) as errors,
                (SUM(success_count) / (SUM(success_count) + SUM(error_count)) * 100) as success_rate
            FROM {$usage_table}
            WHERE usage_date >= %s",
            $date_from
        ), ARRAY_A);
        
        // Get average response time
        $avg_response_time = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(response_time_ms)
            FROM {$usage_table}
            WHERE usage_date >= %s
            AND response_time_ms > 0",
            $date_from
        ));
        
        return [
            'period_days' => $days,
            'date_from' => $date_from,
            'usage_by_type' => $usage_by_type,
            'daily_usage' => $daily_usage,
            'success_rate' => round((float)($success_rate['success_rate'] ?? 0), 2),
            'total_requests' => (int)($success_rate['successes'] ?? 0) + (int)($success_rate['errors'] ?? 0),
            'successful_requests' => (int)($success_rate['successes'] ?? 0),
            'failed_requests' => (int)($success_rate['errors'] ?? 0),
            'avg_response_time_ms' => round((float)$avg_response_time, 2)
        ];
    }
    
    /**
     * Clear API key (for security)
     */
    public function clear_api_key(): bool {
        update_option('wpaig_grok_api_key', '');
        update_option('wpaig_grok_status', 'not_configured');
        
        return true;
    }
    
    /**
     * Verify API key format
     * 
     * @param string $api_key API key to verify
     * @return array Verification result
     */
    public function verify_api_key_format(string $api_key): array {
        if (empty($api_key)) {
            return [
                'valid' => false,
                'message' => 'API key cannot be empty'
            ];
        }
        
        // Grok/xAI API keys typically start with "xai-"
        if (strpos($api_key, 'xai-') !== 0) {
            return [
                'valid' => false,
                'message' => 'Invalid Grok API key format. Expected format: xai-...',
                'hint' => 'Get your API key from: https://console.x.ai'
            ];
        }
        
        if (strlen($api_key) < 20) {
            return [
                'valid' => false,
                'message' => 'API key appears too short'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'API key format is valid',
            'provider' => 'grok'
        ];
    }
}
