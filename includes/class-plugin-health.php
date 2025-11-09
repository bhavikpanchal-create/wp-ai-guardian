<?php
/**
 * Plugin Health Checker Class
 * 
 * Detects and analyzes:
 * - Plugin conflicts
 * - Available plugin updates
 * - Unused/inactive plugins
 * - Plugin compatibility
 * - Recommendations for optimization
 * 
 * @package WP_AI_Guardian
 * @since 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_AIGuardian_Plugin_Health {
    
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
     * Run complete plugin health check
     * 
     * @return array Health check results
     */
    public function run_health_check(): array {
        try {
            $health_data = [
                'conflicts' => $this->detect_conflicts(),
                'updates' => $this->check_updates(),
                'unused' => $this->find_unused_plugins(),
                'compatibility' => $this->check_compatibility(),
                'recommendations' => []
            ];
            
            // Get AI analysis for conflicts
            if (!empty($health_data['conflicts']['potential_conflicts'])) {
                $conflict_analysis = $this->analyze_conflicts_with_ai($health_data);
                if ($conflict_analysis['success']) {
                    $health_data['ai_recommendations'] = $conflict_analysis;
                }
            }
            
            // Generate overall health score
            $health_data['health_score'] = $this->calculate_health_score($health_data);
            $health_data['health_grade'] = $this->get_health_grade($health_data['health_score']);
            
            return [
                'success' => true,
                'data' => $health_data
            ];
            
        } catch (Exception $e) {
            error_log('WPAIG Plugin Health Check Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => WP_DEBUG ? $e->getMessage() : 'Health check failed'
            ];
        }
    }
    
    /**
     * Detect plugin conflicts
     * 
     * @return array Conflict detection results
     */
    public function detect_conflicts(): array {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        
        $conflicts = [];
        $warnings = [];
        
        // Check for multiple caching plugins
        $caching_plugins = $this->find_plugins_by_category('cache', $active_plugins, $all_plugins);
        if (count($caching_plugins) > 1) {
            $conflicts[] = [
                'type' => 'multiple_caching',
                'severity' => 'high',
                'plugins' => $caching_plugins,
                'message' => 'Multiple caching plugins detected. This can cause conflicts and performance issues.',
                'recommendation' => 'Keep only one caching plugin active'
            ];
        }
        
        // Check for multiple SEO plugins
        $seo_plugins = $this->find_plugins_by_category('seo', $active_plugins, $all_plugins);
        if (count($seo_plugins) > 1) {
            $conflicts[] = [
                'type' => 'multiple_seo',
                'severity' => 'medium',
                'plugins' => $seo_plugins,
                'message' => 'Multiple SEO plugins detected. This can cause duplicate meta tags.',
                'recommendation' => 'Keep only one SEO plugin active'
            ];
        }
        
        // Check for multiple security plugins
        $security_plugins = $this->find_plugins_by_category('security', $active_plugins, $all_plugins);
        if (count($security_plugins) > 2) {
            $warnings[] = [
                'type' => 'multiple_security',
                'severity' => 'low',
                'plugins' => $security_plugins,
                'message' => 'Multiple security plugins detected. This may cause conflicts.',
                'recommendation' => 'Consider consolidating to 1-2 security plugins'
            ];
        }
        
        // Check for multiple backup plugins
        $backup_plugins = $this->find_plugins_by_category('backup', $active_plugins, $all_plugins);
        if (count($backup_plugins) > 1) {
            $warnings[] = [
                'type' => 'multiple_backup',
                'severity' => 'low',
                'plugins' => $backup_plugins,
                'message' => 'Multiple backup plugins detected.',
                'recommendation' => 'One backup plugin is usually sufficient'
            ];
        }
        
        // Check for jQuery/JavaScript conflicts (common issue)
        $js_heavy_plugins = $this->find_js_heavy_plugins($active_plugins, $all_plugins);
        if (count($js_heavy_plugins) > 5) {
            $warnings[] = [
                'type' => 'js_heavy',
                'severity' => 'medium',
                'plugins' => array_slice($js_heavy_plugins, 0, 10),
                'message' => 'Many JavaScript-heavy plugins detected. This may cause conflicts and slow performance.',
                'recommendation' => 'Review and deactivate unnecessary plugins'
            ];
        }
        
        return [
            'total_plugins' => count($all_plugins),
            'active_plugins' => count($active_plugins),
            'conflicts_found' => count($conflicts),
            'warnings_found' => count($warnings),
            'potential_conflicts' => $conflicts,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Check for plugin updates
     * 
     * @return array Update check results
     */
    public function check_updates(): array {
        // Force update check
        wp_update_plugins();
        
        $update_plugins = get_site_transient('update_plugins');
        
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        
        $outdated = [];
        $critical_updates = [];
        
        if (isset($update_plugins->response) && is_array($update_plugins->response)) {
            foreach ($update_plugins->response as $plugin_path => $update_data) {
                $plugin_info = $all_plugins[$plugin_path] ?? null;
                
                if ($plugin_info) {
                    $update = [
                        'name' => $plugin_info['Name'],
                        'current_version' => $plugin_info['Version'],
                        'new_version' => $update_data->new_version ?? 'unknown',
                        'slug' => dirname($plugin_path),
                        'is_active' => in_array($plugin_path, $active_plugins)
                    ];
                    
                    $outdated[] = $update;
                    
                    // Check for major version updates (potential breaking changes)
                    if ($this->is_major_update($plugin_info['Version'], $update_data->new_version ?? '0')) {
                        $critical_updates[] = $update;
                    }
                }
            }
        }
        
        return [
            'has_updates' => count($outdated) > 0,
            'update_count' => count($outdated),
            'critical_update_count' => count($critical_updates),
            'outdated_plugins' => $outdated,
            'critical_updates' => $critical_updates,
            'recommendation' => count($outdated) > 0 ? 
                'Update plugins to ensure security and compatibility' : 
                'All plugins are up to date'
        ];
    }
    
    /**
     * Find unused/inactive plugins
     * 
     * @return array Unused plugins list
     */
    public function find_unused_plugins(): array {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        
        $inactive_plugins = [];
        $potentially_unused = [];
        
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            if (!in_array($plugin_path, $active_plugins)) {
                $inactive_plugins[] = [
                    'name' => $plugin_data['Name'],
                    'version' => $plugin_data['Version'],
                    'path' => $plugin_path,
                    'slug' => dirname($plugin_path)
                ];
            }
        }
        
        // Check last deactivation time (if we tracked it)
        $deactivation_times = get_option('wpaig_plugin_deactivation_times', []);
        
        foreach ($inactive_plugins as $plugin) {
            if (isset($deactivation_times[$plugin['path']])) {
                $days_inactive = (time() - $deactivation_times[$plugin['path']]) / DAY_IN_SECONDS;
                
                if ($days_inactive > 30) {
                    $potentially_unused[] = array_merge($plugin, [
                        'days_inactive' => round($days_inactive)
                    ]);
                }
            }
        }
        
        return [
            'inactive_count' => count($inactive_plugins),
            'inactive_plugins' => $inactive_plugins,
            'unused_count' => count($potentially_unused),
            'potentially_unused' => $potentially_unused,
            'recommendation' => count($inactive_plugins) > 0 ? 
                'Consider removing inactive plugins to reduce security risks' : 
                'No inactive plugins found'
        ];
    }
    
    /**
     * Check plugin compatibility
     * 
     * @return array Compatibility check results
     */
    public function check_compatibility(): array {
        global $wp_version;
        
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        
        $incompatible = [];
        $warnings = [];
        
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            if (in_array($plugin_path, $active_plugins)) {
                // Check WordPress version compatibility
                if (isset($plugin_data['RequiresWP'])) {
                    if (version_compare($wp_version, $plugin_data['RequiresWP'], '<')) {
                        $incompatible[] = [
                            'name' => $plugin_data['Name'],
                            'issue' => 'requires_newer_wp',
                            'requires_wp' => $plugin_data['RequiresWP'],
                            'current_wp' => $wp_version,
                            'severity' => 'high'
                        ];
                    }
                }
                
                // Check PHP version compatibility
                if (isset($plugin_data['RequiresPHP'])) {
                    if (version_compare(PHP_VERSION, $plugin_data['RequiresPHP'], '<')) {
                        $incompatible[] = [
                            'name' => $plugin_data['Name'],
                            'issue' => 'requires_newer_php',
                            'requires_php' => $plugin_data['RequiresPHP'],
                            'current_php' => PHP_VERSION,
                            'severity' => 'critical'
                        ];
                    }
                }
                
                // Check for known problematic plugins (example list)
                $problematic_slugs = ['hello-dolly', 'sample-plugin'];
                $slug = dirname($plugin_path);
                
                if (in_array($slug, $problematic_slugs)) {
                    $warnings[] = [
                        'name' => $plugin_data['Name'],
                        'issue' => 'known_issues',
                        'message' => 'This plugin is known to cause issues in some environments',
                        'severity' => 'low'
                    ];
                }
            }
        }
        
        return [
            'compatible' => count($incompatible) === 0,
            'incompatible_count' => count($incompatible),
            'incompatible_plugins' => $incompatible,
            'warnings' => $warnings,
            'wp_version' => $wp_version,
            'php_version' => PHP_VERSION
        ];
    }
    
    /**
     * Analyze conflicts with Grok AI
     */
    private function analyze_conflicts_with_ai(array $health_data): array {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        
        $plugin_list = [];
        foreach ($active_plugins as $plugin_path) {
            if (isset($all_plugins[$plugin_path])) {
                $plugin_list[] = [
                    'name' => $all_plugins[$plugin_path]['Name'],
                    'version' => $all_plugins[$plugin_path]['Version']
                ];
            }
        }
        
        $data = [
            'active_plugins' => $plugin_list,
            'conflicts' => $health_data['conflicts']['potential_conflicts'],
            'error_log' => $this->get_recent_php_errors(10)
        ];
        
        return $this->grok_handler->detect_conflicts($data);
    }
    
    /**
     * Get recent PHP errors from log
     */
    private function get_recent_php_errors(int $limit = 10): string {
        $error_log_path = ini_get('error_log');
        
        if (empty($error_log_path) || !file_exists($error_log_path)) {
            return 'No error log available';
        }
        
        $errors = [];
        $handle = fopen($error_log_path, 'r');
        
        if ($handle) {
            while (($line = fgets($handle)) !== false && count($errors) < $limit) {
                if (stripos($line, 'plugin') !== false || stripos($line, 'fatal') !== false) {
                    $errors[] = $line;
                }
            }
            fclose($handle);
        }
        
        return implode("\n", array_slice($errors, -$limit));
    }
    
    /**
     * Find plugins by category
     */
    private function find_plugins_by_category(string $category, array $active_plugins, array $all_plugins): array {
        $keywords = [
            'cache' => ['cache', 'caching', 'speed', 'optimize', 'super cache', 'w3 total', 'wp rocket', 'litespeed'],
            'seo' => ['seo', 'yoast', 'rank math', 'all in one seo', 'seopress'],
            'security' => ['security', 'wordfence', 'sucuri', 'ithemes', 'shield', 'firewall'],
            'backup' => ['backup', 'updraft', 'backwpup', 'duplicator', 'jetpack backup']
        ];
        
        $category_keywords = $keywords[$category] ?? [];
        $found_plugins = [];
        
        foreach ($active_plugins as $plugin_path) {
            if (isset($all_plugins[$plugin_path])) {
                $plugin_name = strtolower($all_plugins[$plugin_path]['Name']);
                $plugin_desc = strtolower($all_plugins[$plugin_path]['Description'] ?? '');
                
                foreach ($category_keywords as $keyword) {
                    if (stripos($plugin_name, $keyword) !== false || stripos($plugin_desc, $keyword) !== false) {
                        $found_plugins[] = $all_plugins[$plugin_path]['Name'];
                        break;
                    }
                }
            }
        }
        
        return $found_plugins;
    }
    
    /**
     * Find JavaScript-heavy plugins
     */
    private function find_js_heavy_plugins(array $active_plugins, array $all_plugins): array {
        $js_heavy_keywords = ['slider', 'gallery', 'builder', 'editor', 'page builder', 'elementor', 'visual composer', 'divi'];
        $found_plugins = [];
        
        foreach ($active_plugins as $plugin_path) {
            if (isset($all_plugins[$plugin_path])) {
                $plugin_name = strtolower($all_plugins[$plugin_path]['Name']);
                
                foreach ($js_heavy_keywords as $keyword) {
                    if (stripos($plugin_name, $keyword) !== false) {
                        $found_plugins[] = $all_plugins[$plugin_path]['Name'];
                        break;
                    }
                }
            }
        }
        
        return $found_plugins;
    }
    
    /**
     * Check if update is a major version
     */
    private function is_major_update(string $current, string $new): bool {
        $current_parts = explode('.', $current);
        $new_parts = explode('.', $new);
        
        if (isset($current_parts[0]) && isset($new_parts[0])) {
            return (int)$new_parts[0] > (int)$current_parts[0];
        }
        
        return false;
    }
    
    /**
     * Calculate overall health score
     */
    private function calculate_health_score(array $health_data): int {
        $score = 100;
        
        // Deduct for conflicts
        $conflicts_count = $health_data['conflicts']['conflicts_found'] ?? 0;
        $score -= ($conflicts_count * 15);
        
        // Deduct for warnings
        $warnings_count = $health_data['conflicts']['warnings_found'] ?? 0;
        $score -= ($warnings_count * 5);
        
        // Deduct for outdated plugins
        $outdated_count = $health_data['updates']['update_count'] ?? 0;
        $score -= ($outdated_count * 3);
        
        // Deduct for critical updates
        $critical_count = $health_data['updates']['critical_update_count'] ?? 0;
        $score -= ($critical_count * 10);
        
        // Deduct for inactive plugins
        $inactive_count = $health_data['unused']['inactive_count'] ?? 0;
        $score -= min($inactive_count * 2, 20); // Max 20 points deduction
        
        // Deduct for incompatible plugins
        $incompatible_count = $health_data['compatibility']['incompatible_count'] ?? 0;
        $score -= ($incompatible_count * 20);
        
        return max(0, min(100, $score));
    }
    
    /**
     * Get health grade from score
     */
    private function get_health_grade(int $score): string {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
    
    /**
     * Track plugin deactivation
     */
    public function track_plugin_deactivation(string $plugin_path): void {
        $deactivation_times = get_option('wpaig_plugin_deactivation_times', []);
        $deactivation_times[$plugin_path] = time();
        update_option('wpaig_plugin_deactivation_times', $deactivation_times);
    }
}
