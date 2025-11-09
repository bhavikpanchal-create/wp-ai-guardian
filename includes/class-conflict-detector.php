<?php
/**
 * Plugin Conflict Detector
 *
 * @package WP_AI_Guardian
 * @since 1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WP_AIGuardian_Conflicts extends WP_AIGuardian_AI_Handler {
    
    /**
     * Maximum plugins to scan (performance limit)
     */
    private const MAX_PLUGINS = 50;
    
    /**
     * Known problematic plugin patterns
     */
    private const KNOWN_ISSUES = [
        'yoast' => 'May cause slow admin queries',
        'elementor' => 'Heavy frontend resource usage',
        'wordfence' => 'Can block legitimate requests',
        'wp-rocket' => 'Cache conflicts with other plugins',
        'jetpack' => 'Multiple feature conflicts possible'
    ];
    
    /**
     * Scan for plugin conflicts
     *
     * @return array Conflict scan results
     */
    public function scan(): array {
        $results = [];
        $active_plugins = $this->get_active_plugins();
        
        // Limit to max plugins for performance
        if (count($active_plugins) > self::MAX_PLUGINS) {
            $active_plugins = array_slice($active_plugins, 0, self::MAX_PLUGINS);
            $results['warning'] = sprintf(
                'Scanning first %d plugins only (performance limit)',
                self::MAX_PLUGINS
            );
        }
        
        $conflicts = [];
        $tested_count = 0;
        
        foreach ($active_plugins as $plugin_file) {
            $tested_count++;
            
            // Get plugin name
            $plugin_name = $this->get_plugin_name($plugin_file);
            
            // Check for known issues first (fast)
            $known_issue = $this->check_known_issues($plugin_file, $plugin_name);
            if ($known_issue) {
                $conflicts[] = [
                    'plugin' => $plugin_name,
                    'file' => $plugin_file,
                    'issue' => $known_issue,
                    'severity' => 'medium',
                    'type' => 'known_issue'
                ];
                continue;
            }
            
            // Test plugin for conflicts (slower, but thorough)
            $test_result = $this->test_plugin($plugin_file, $plugin_name);
            
            if ($test_result !== null) {
                $conflicts[] = $test_result;
            }
        }
        
        $results['conflicts'] = $conflicts;
        $results['tested_count'] = $tested_count;
        $results['total_plugins'] = count($this->get_active_plugins());
        $results['is_premium'] = $this->is_premium();
        
        // Add AI diagnostics for premium users
        if ($this->is_premium() && !empty($conflicts)) {
            $results = $this->add_ai_diagnostics($results);
        }
        
        // Log scan
        $this->log_scan($results);
        
        return $results;
    }
    
    /**
     * Get active plugins
     *
     * @return array Active plugin files
     */
    private function get_active_plugins(): array {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $active = get_option('active_plugins', []);
        
        // Filter out our own plugin
        $active = array_filter($active, function($plugin) {
            return strpos($plugin, 'wp-ai-guardian') === false;
        });
        
        return array_values($active);
    }
    
    /**
     * Get plugin name from file path
     *
     * @param string $plugin_file Plugin file path
     * @return string Plugin name
     */
    private function get_plugin_name(string $plugin_file): string {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        
        if (isset($all_plugins[$plugin_file]['Name'])) {
            return $all_plugins[$plugin_file]['Name'];
        }
        
        // Fallback: Extract from file path
        $parts = explode('/', $plugin_file);
        return ucwords(str_replace(['-', '_'], ' ', $parts[0]));
    }
    
    /**
     * Check for known plugin issues
     *
     * @param string $plugin_file Plugin file
     * @param string $plugin_name Plugin name
     * @return string|null Issue description or null
     */
    private function check_known_issues(string $plugin_file, string $plugin_name): ?string {
        $plugin_slug = strtolower($plugin_name);
        
        foreach (self::KNOWN_ISSUES as $pattern => $issue) {
            if (strpos($plugin_slug, $pattern) !== false || 
                strpos($plugin_file, $pattern) !== false) {
                return $issue;
            }
        }
        
        return null;
    }
    
    /**
     * Test individual plugin for conflicts
     *
     * @param string $plugin_file Plugin file path
     * @param string $plugin_name Plugin name
     * @return array|null Conflict info or null
     */
    private function test_plugin(string $plugin_file, string $plugin_name): ?array {
        $issues = [];
        
        // Test 1: Database query performance
        $query_issue = $this->test_query_performance($plugin_file);
        if ($query_issue) {
            $issues[] = $query_issue;
        }
        
        // Test 2: JavaScript conflicts
        $js_issue = $this->test_javascript_conflicts($plugin_file);
        if ($js_issue) {
            $issues[] = $js_issue;
        }
        
        // Test 3: PHP errors
        $php_issue = $this->test_php_errors($plugin_file);
        if ($php_issue) {
            $issues[] = $php_issue;
        }
        
        // If issues found, return conflict
        if (!empty($issues)) {
            return [
                'plugin' => $plugin_name,
                'file' => $plugin_file,
                'issue' => implode('; ', $issues),
                'severity' => $this->calculate_severity($issues),
                'type' => 'detected'
            ];
        }
        
        return null;
    }
    
    /**
     * Test query performance with plugin temporarily disabled
     *
     * @param string $plugin_file Plugin file
     * @return string|null Issue description
     */
    private function test_query_performance(string $plugin_file): ?string {
        global $wpdb;
        
        // Baseline query time
        $start = microtime(true);
        $query = new WP_Query([
            'posts_per_page' => 1,
            'post_type' => 'post',
            'post_status' => 'publish',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ]);
        $baseline_time = microtime(true) - $start;
        wp_reset_postdata();
        
        // Test with plugin "disabled" (filtered)
        $start = microtime(true);
        add_filter('option_active_plugins', function($plugins) use ($plugin_file) {
            return array_diff($plugins, [$plugin_file]);
        });
        
        $query = new WP_Query([
            'posts_per_page' => 1,
            'post_type' => 'post',
            'post_status' => 'publish',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ]);
        $filtered_time = microtime(true) - $start;
        wp_reset_postdata();
        
        // Remove filter
        remove_all_filters('option_active_plugins');
        
        // Check if plugin significantly slows queries (>30% slower)
        if ($baseline_time > $filtered_time * 1.3) {
            $slowdown = round(($baseline_time / $filtered_time - 1) * 100, 1);
            return "Slow query performance ({$slowdown}% slower)";
        }
        
        return null;
    }
    
    /**
     * Test for JavaScript conflicts
     *
     * @param string $plugin_file Plugin file
     * @return string|null Issue description
     */
    private function test_javascript_conflicts(string $plugin_file): ?string {
        // Simulate JS conflict detection by checking script dependencies
        global $wp_scripts;
        
        if (!is_a($wp_scripts, 'WP_Scripts')) {
            return null;
        }
        
        // Check for jQuery version conflicts
        if (isset($wp_scripts->registered['jquery'])) {
            $jquery = $wp_scripts->registered['jquery'];
            
            // Check plugin's scripts
            foreach ($wp_scripts->registered as $handle => $script) {
                if (isset($script->src) && strpos($script->src, dirname($plugin_file)) !== false) {
                    // Check if script has jQuery dependency but loads before it
                    if (in_array('jquery', $script->deps) && 
                        !in_array($handle, $jquery->deps)) {
                        return 'Potential jQuery dependency conflict';
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Test for PHP errors
     *
     * @param string $plugin_file Plugin file
     * @return string|null Issue description
     */
    private function test_php_errors(string $plugin_file): ?string {
        // Clear previous errors
        error_clear_last();
        
        // Attempt to trigger plugin hooks
        ob_start();
        
        try {
            // Trigger common hooks that might expose issues
            do_action('init');
            do_action('wp_loaded');
            
            // Check for errors
            $error = error_get_last();
            
            if ($error && 
                ($error['type'] === E_ERROR || 
                 $error['type'] === E_WARNING || 
                 $error['type'] === E_PARSE)) {
                
                // Check if error is from this plugin
                if (isset($error['file']) && 
                    strpos($error['file'], dirname(WP_PLUGIN_DIR . '/' . $plugin_file)) !== false) {
                    return 'PHP error detected: ' . substr($error['message'], 0, 50);
                }
            }
        } catch (Exception $e) {
            ob_end_clean();
            return 'Exception thrown: ' . substr($e->getMessage(), 0, 50);
        }
        
        ob_end_clean();
        
        return null;
    }
    
    /**
     * Calculate severity based on issues
     *
     * @param array $issues Array of issue descriptions
     * @return string Severity level
     */
    private function calculate_severity(array $issues): string {
        $issue_text = strtolower(implode(' ', $issues));
        
        if (strpos($issue_text, 'error') !== false || 
            strpos($issue_text, 'exception') !== false ||
            strpos($issue_text, 'crash') !== false) {
            return 'high';
        }
        
        if (strpos($issue_text, 'slow') !== false || 
            strpos($issue_text, 'conflict') !== false) {
            return 'medium';
        }
        
        return 'low';
    }
    
    /**
     * Add AI diagnostics for premium users
     *
     * @param array $results Scan results
     * @return array Results with AI diagnostics
     */
    private function add_ai_diagnostics(array $results): array {
        $conflicts = $results['conflicts'];
        
        // Prepare prompt for AI
        $conflict_summary = [];
        foreach ($conflicts as $conflict) {
            $conflict_summary[] = "{$conflict['plugin']}: {$conflict['issue']}";
        }
        
        $prompt = sprintf(
            "WordPress plugin conflicts detected:\n%s\n\nProvide specific fix steps for each conflict. Be concise.",
            implode("\n", $conflict_summary)
        );
        
        // Get AI analysis
        $ai_response = $this->generate($prompt, 10);
        
        // Add AI recommendations to each conflict
        foreach ($results['conflicts'] as &$conflict) {
            $conflict['ai_fix'] = $ai_response;
            $conflict['can_auto_fix'] = true;
        }
        
        $results['ai_analysis'] = $ai_response;
        
        return $results;
    }
    
    /**
     * Auto-deactivate conflicting plugin (Premium only)
     *
     * @param string $plugin_file Plugin file to deactivate
     * @return bool Success status
     */
    public function auto_deactivate(string $plugin_file): bool {
        if (!$this->is_premium()) {
            return false;
        }
        
        if (!current_user_can('activate_plugins')) {
            return false;
        }
        
        // Deactivate plugin
        deactivate_plugins($plugin_file);
        
        // Log action
        $this->log_deactivation($plugin_file);
        
        return true;
    }
    
    /**
     * Log scan results
     *
     * @param array $results Scan results
     */
    private function log_scan(array $results): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_guardian_logs';
        
        $message = sprintf(
            'Conflict scan: %d conflicts found in %d plugins',
            count($results['conflicts']),
            $results['tested_count']
        );
        
        $wpdb->insert(
            $table_name,
            [
                'type' => 'conflict_scan',
                'message' => $message,
                'timestamp' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }
    
    /**
     * Log plugin deactivation
     *
     * @param string $plugin_file Plugin file
     */
    private function log_deactivation(string $plugin_file): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_guardian_logs';
        
        $plugin_name = $this->get_plugin_name($plugin_file);
        
        $wpdb->insert(
            $table_name,
            [
                'type' => 'auto_deactivate',
                'message' => "Auto-deactivated: {$plugin_name}",
                'timestamp' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }
    
    /**
     * Get detailed plugin info
     *
     * @param string $plugin_file Plugin file
     * @return array Plugin details
     */
    public function get_plugin_details(string $plugin_file): array {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        
        if (isset($all_plugins[$plugin_file])) {
            return [
                'name' => $all_plugins[$plugin_file]['Name'],
                'version' => $all_plugins[$plugin_file]['Version'],
                'author' => $all_plugins[$plugin_file]['Author'],
                'description' => $all_plugins[$plugin_file]['Description']
            ];
        }
        
        return [
            'name' => $this->get_plugin_name($plugin_file),
            'version' => 'Unknown',
            'author' => 'Unknown',
            'description' => ''
        ];
    }
}
