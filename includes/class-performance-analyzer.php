<?php
/**
 * Performance Analyzer Class
 * 
 * Analyzes WordPress site performance including:
 * - Load time analysis
 * - Database performance
 * - Image optimization status
 * - Caching configuration
 * - CDN usage
 * 
 * @package WP_AI_Guardian
 * @since 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_AIGuardian_Performance_Analyzer {
    
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
        
        // Load Grok handler
        if (!class_exists('WP_AIGuardian_Grok_AI_Handler')) {
            require_once WPAIG_PLUGIN_DIR . 'includes/class-grok-ai-handler.php';
        }
        
        $this->grok_handler = new WP_AIGuardian_Grok_AI_Handler();
    }
    
    /**
     * Run complete performance analysis
     * 
     * @return array Analysis results with AI recommendations
     */
    public function run_analysis(): array {
        try {
            // Collect performance data
            $performance_data = $this->collect_performance_data();
            
            // Send to Grok AI for analysis
            $ai_analysis = $this->grok_handler->analyze_performance($performance_data);
            
            if (!$ai_analysis['success']) {
                return [
                    'success' => false,
                    'message' => $ai_analysis['message'] ?? 'AI analysis failed'
                ];
            }
            
            // Prepare results
            $results = [
                'scan_id' => uniqid('perf_', true),
                'timestamp' => current_time('mysql'),
                'performance_data' => $performance_data,
                'ai_analysis' => $ai_analysis,
                'score' => $ai_analysis['score'],
                'grade' => $ai_analysis['grade'],
                'bottlenecks' => $ai_analysis['bottlenecks'],
                'recommendations' => $ai_analysis['recommendations'],
                'quick_wins' => $ai_analysis['quick_wins']
            ];
            
            // Store in database
            $this->store_analysis_results($results);
            
            return [
                'success' => true,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            error_log('WPAIG Performance Analysis Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => WP_DEBUG ? $e->getMessage() : 'Performance analysis failed'
            ];
        }
    }
    
    /**
     * Collect comprehensive performance data
     * 
     * @return array Performance metrics
     */
    private function collect_performance_data(): array {
        return [
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
            'load_time' => $this->measure_load_time(),
            'database' => $this->analyze_database_performance(),
            'images' => $this->analyze_image_optimization(),
            'caching' => $this->check_caching_status(),
            'cdn' => $this->check_cdn_usage(),
            'server' => $this->get_server_metrics(),
            'themes_plugins' => $this->analyze_themes_plugins(),
            'assets' => $this->analyze_assets()
        ];
    }
    
    /**
     * Measure page load time
     */
    private function measure_load_time(): array {
        $start_time = microtime(true);
        
        // Simulate homepage request
        $response = wp_remote_get(home_url(), [
            'timeout' => 30,
            'sslverify' => false,
            'redirection' => 5
        ]);
        
        $load_time = microtime(true) - $start_time;
        
        $data = [
            'load_time' => round($load_time, 3),
            'load_time_ms' => round($load_time * 1000, 0),
            'status' => 'unknown'
        ];
        
        if (!is_wp_error($response)) {
            $status_code = wp_remote_retrieve_response_code($response);
            $data['status_code'] = $status_code;
            $data['response_size'] = strlen(wp_remote_retrieve_body($response));
            
            // Categorize performance
            if ($load_time < 1.0) {
                $data['status'] = 'excellent';
            } elseif ($load_time < 2.0) {
                $data['status'] = 'good';
            } elseif ($load_time < 3.0) {
                $data['status'] = 'average';
            } else {
                $data['status'] = 'slow';
            }
        } else {
            $data['error'] = $response->get_error_message();
            $data['status'] = 'error';
        }
        
        return $data;
    }
    
    /**
     * Analyze database performance
     */
    private function analyze_database_performance(): array {
        global $wpdb;
        
        // Get database size
        $db_size = $wpdb->get_var("
            SELECT SUM(data_length + index_length) / 1024 / 1024 
            FROM information_schema.TABLES 
            WHERE table_schema = '{$wpdb->dbname}'
        ");
        
        // Count queries in a test run
        $queries_before = get_num_queries();
        
        // Simulate common operations
        get_posts(['posts_per_page' => 10]);
        get_option('siteurl');
        
        $queries_after = get_num_queries();
        $query_count = $queries_after - $queries_before;
        
        // Check for transients
        $transient_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%'"
        );
        
        // Check for autoloaded options
        $autoload_size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) / 1024 
            FROM {$wpdb->options} 
            WHERE autoload = 'yes'"
        );
        
        // Check for post revisions
        $revision_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'revision'"
        );
        
        return [
            'size_mb' => round($db_size, 2),
            'query_count' => $query_count,
            'transient_count' => $transient_count,
            'autoload_size_kb' => round($autoload_size, 2),
            'revision_count' => $revision_count,
            'prefix' => $wpdb->prefix,
            'charset' => $wpdb->charset,
            'optimization_needed' => (
                $transient_count > 100 || 
                $autoload_size > 1000 || 
                $revision_count > 500
            )
        ];
    }
    
    /**
     * Analyze image optimization
     */
    private function analyze_image_optimization(): array {
        $upload_dir = wp_upload_dir();
        
        // Count images
        $image_count = 0;
        $total_size = 0;
        $unoptimized_count = 0;
        
        if (is_dir($upload_dir['basedir'])) {
            $images = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($upload_dir['basedir']),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($images as $file) {
                if ($file->isFile()) {
                    $ext = strtolower($file->getExtension());
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $image_count++;
                        $size = $file->getSize();
                        $total_size += $size;
                        
                        // Consider large images as unoptimized
                        if ($size > 500000) { // > 500KB
                            $unoptimized_count++;
                        }
                    }
                }
            }
        }
        
        // Check for WebP support
        $has_webp_plugin = (
            is_plugin_active('webp-express/webp-express.php') ||
            is_plugin_active('ewww-image-optimizer/ewww-image-optimizer.php')
        );
        
        return [
            'total_images' => $image_count,
            'total_size_mb' => round($total_size / 1024 / 1024, 2),
            'average_size_kb' => $image_count > 0 ? round(($total_size / $image_count) / 1024, 2) : 0,
            'unoptimized_count' => $unoptimized_count,
            'unoptimized_percentage' => $image_count > 0 ? round(($unoptimized_count / $image_count) * 100, 1) : 0,
            'has_webp_support' => $has_webp_plugin,
            'optimization_needed' => ($unoptimized_count > 10 || !$has_webp_plugin)
        ];
    }
    
    /**
     * Check caching status
     */
    private function check_caching_status(): array {
        $caching_plugins = [
            'wp-super-cache/wp-cache.php' => 'WP Super Cache',
            'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache',
            'wp-fastest-cache/wpFastestCache.php' => 'WP Fastest Cache',
            'litespeed-cache/litespeed-cache.php' => 'LiteSpeed Cache',
            'wp-rocket/wp-rocket.php' => 'WP Rocket',
            'cache-enabler/cache-enabler.php' => 'Cache Enabler'
        ];
        
        $active_cache_plugins = [];
        
        foreach ($caching_plugins as $plugin_path => $plugin_name) {
            if (is_plugin_active($plugin_path)) {
                $active_cache_plugins[] = $plugin_name;
            }
        }
        
        // Check for object caching
        $has_object_cache = wp_using_ext_object_cache();
        
        // Check for page caching (basic detection)
        $headers = @get_headers(home_url(), 1);
        $has_page_cache = false;
        
        if ($headers && isset($headers['X-Cache'])) {
            $has_page_cache = true;
        }
        
        // Check for browser caching
        $has_browser_cache = false;
        if ($headers && isset($headers['Cache-Control'])) {
            $has_browser_cache = true;
        }
        
        return [
            'active_plugins' => $active_cache_plugins,
            'plugin_count' => count($active_cache_plugins),
            'has_object_cache' => $has_object_cache,
            'has_page_cache' => $has_page_cache,
            'has_browser_cache' => $has_browser_cache,
            'caching_enabled' => (count($active_cache_plugins) > 0 || $has_object_cache),
            'optimization_needed' => (count($active_cache_plugins) === 0 && !$has_object_cache)
        ];
    }
    
    /**
     * Check CDN usage
     */
    private function check_cdn_usage(): array {
        $cdn_plugins = [
            'jetpack/jetpack.php' => 'Jetpack (Photon CDN)',
            'cloudflare/cloudflare.php' => 'Cloudflare',
            'cdn-enabler/cdn-enabler.php' => 'CDN Enabler',
            'wp-cloudflare-page-cache/wp-cloudflare-super-page-cache.php' => 'Cloudflare Page Cache'
        ];
        
        $active_cdn_plugins = [];
        
        foreach ($cdn_plugins as $plugin_path => $plugin_name) {
            if (is_plugin_active($plugin_path)) {
                $active_cdn_plugins[] = $plugin_name;
            }
        }
        
        // Check if any static assets are served from CDN
        $homepage_content = wp_remote_retrieve_body(
            wp_remote_get(home_url(), ['timeout' => 10, 'sslverify' => false])
        );
        
        $cdn_detected = false;
        $cdn_providers = ['cloudflare', 'cloudfront', 'akamai', 'fastly', 'cdn', 'stackpath'];
        
        foreach ($cdn_providers as $provider) {
            if (stripos($homepage_content, $provider) !== false) {
                $cdn_detected = true;
                break;
            }
        }
        
        return [
            'active_plugins' => $active_cdn_plugins,
            'plugin_count' => count($active_cdn_plugins),
            'cdn_detected' => $cdn_detected,
            'cdn_enabled' => (count($active_cdn_plugins) > 0 || $cdn_detected),
            'recommendation' => !$cdn_detected ? 'Consider using a CDN for faster content delivery' : null
        ];
    }
    
    /**
     * Get server metrics
     */
    private function get_server_metrics(): array {
        return [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() !== false,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
        ];
    }
    
    /**
     * Analyze themes and plugins count
     */
    private function analyze_themes_plugins(): array {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        
        return [
            'total_plugins' => count($all_plugins),
            'active_plugins' => count($active_plugins),
            'inactive_plugins' => count($all_plugins) - count($active_plugins),
            'total_themes' => count(wp_get_themes()),
            'optimization_needed' => (count($active_plugins) > 30)
        ];
    }
    
    /**
     * Analyze CSS/JS assets
     */
    private function analyze_assets(): array {
        global $wp_scripts, $wp_styles;
        
        $css_count = 0;
        $js_count = 0;
        
        if (isset($wp_styles->registered)) {
            $css_count = count($wp_styles->registered);
        }
        
        if (isset($wp_scripts->registered)) {
            $js_count = count($wp_scripts->registered);
        }
        
        // Check for minification plugins
        $minification_plugins = [
            'autoptimize/autoptimize.php' => 'Autoptimize',
            'wp-super-minify/wp-super-minify.php' => 'WP Super Minify',
            'fast-velocity-minify/fvm.php' => 'Fast Velocity Minify'
        ];
        
        $active_minify = [];
        foreach ($minification_plugins as $plugin_path => $plugin_name) {
            if (is_plugin_active($plugin_path)) {
                $active_minify[] = $plugin_name;
            }
        }
        
        return [
            'css_files' => $css_count,
            'js_files' => $js_count,
            'total_assets' => $css_count + $js_count,
            'minification_active' => !empty($active_minify),
            'minification_plugins' => $active_minify,
            'optimization_needed' => (($css_count + $js_count) > 20 && empty($active_minify))
        ];
    }
    
    /**
     * Store analysis results in database
     */
    private function store_analysis_results(array $results): bool {
        global $wpdb;
        
        $data = [
            'scan_type' => 'performance',
            'scan_date' => $results['timestamp'],
            'score' => $results['score'],
            'grade' => $results['grade'],
            'data' => wp_json_encode($results['performance_data']),
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
            error_log('WPAIG: Failed to store performance results - ' . $wpdb->last_error);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get analysis history
     */
    public function get_analysis_history(int $limit = 10): array {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE scan_type = 'performance' 
            ORDER BY scan_date DESC 
            LIMIT %d",
            $limit
        ), ARRAY_A);
        
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
     * Get latest analysis
     */
    public function get_latest_analysis(): ?array {
        $history = $this->get_analysis_history(1);
        return !empty($history) ? $history[0] : null;
    }
    
    /**
     * Get performance score trend
     */
    public function get_score_trend(int $days = 30): array {
        global $wpdb;
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(scan_date) as date, AVG(score) as avg_score, COUNT(*) as scan_count
            FROM {$this->table_name} 
            WHERE scan_type = 'performance' 
            AND scan_date >= %s
            GROUP BY DATE(scan_date)
            ORDER BY date ASC",
            $date_from
        ), ARRAY_A);
        
        return $results;
    }
}
