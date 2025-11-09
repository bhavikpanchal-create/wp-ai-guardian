<?php
/**
 * Performance Optimizer
 *
 * @package WP_AI_Guardian
 * @since 1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WP_AIGuardian_Performance extends WP_AIGuardian_AI_Handler {
    
    /**
     * Performance metrics
     */
    private array $metrics = [];
    
    /**
     * Optimization results
     */
    private array $results = [];
    
    /**
     * Target speed boost percentage
     */
    private const TARGET_BOOST = 50;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        // Free features - always active
        add_filter('the_content', [$this, 'add_lazy_loading'], 99);
        add_filter('post_thumbnail_html', [$this, 'add_lazy_loading'], 99);
        
        // Footer optimizations
        add_action('wp_footer', [$this, 'inject_lazy_script'], 999);
        
        // Premium features
        if ($this->is_premium()) {
            add_action('template_redirect', [$this, 'enable_object_cache']);
            add_action('wp_enqueue_scripts', [$this, 'minify_assets'], 999);
        }
    }
    
    /**
     * Main optimization method
     *
     * @return array Optimization results
     */
    public function optimize(): array {
        $this->results = [];
        $this->metrics = [];
        
        try {
            // Measure baseline performance
            $baseline = $this->measure_baseline();
            $this->metrics['baseline'] = $baseline;
            
            // Free optimizations
            try {
                $this->optimize_images();
            } catch (Exception $e) {
                error_log('WP AI Guardian: Image optimization error: ' . $e->getMessage());
                $this->results['images_optimized'] = 0;
            }
            
            $this->apply_lazy_loading();
            
            // Premium optimizations
            if ($this->is_premium()) {
                try {
                    $this->optimize_queries();
                } catch (Exception $e) {
                    error_log('WP AI Guardian: Query optimization error: ' . $e->getMessage());
                }
                
                try {
                    $this->optimize_assets();
                } catch (Exception $e) {
                    error_log('WP AI Guardian: Asset optimization error: ' . $e->getMessage());
                }
                
                try {
                    $this->get_ai_recommendations();
                } catch (Exception $e) {
                    error_log('WP AI Guardian: AI recommendations error: ' . $e->getMessage());
                }
            }
            
            // Measure after optimization
            $optimized = $this->measure_performance();
        } catch (Exception $e) {
            error_log('WP AI Guardian: Critical optimization error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Optimization failed: ' . $e->getMessage()
            ];
        }
        $this->metrics['optimized'] = $optimized;
        
        // Calculate improvement
        $improvement = $this->calculate_improvement($baseline, $optimized);
        $this->metrics['improvement'] = $improvement;
        
        // Generate report
        $report = $this->generate_report();
        
        // Log optimization
        $this->log_optimization($report);
        
        return $report;
    }
    
    /**
     * Measure baseline site performance
     *
     * @return array Performance metrics
     */
    private function measure_baseline(): array {
        $metrics = [];
        
        // Measure query performance
        timer_start();
        $query = new WP_Query([
            'posts_per_page' => 10,
            'post_type' => 'post',
            'post_status' => 'publish'
        ]);
        $metrics['query_time'] = timer_stop(0, 3);
        wp_reset_postdata();
        
        // Measure database queries
        global $wpdb;
        $metrics['db_queries'] = $wpdb->num_queries;
        
        // Check image count
        $images = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        $metrics['total_images'] = count($images);
        
        // Check unoptimized images
        $unoptimized = 0;
        foreach (array_slice($images, 0, 20) as $image_id) {
            $meta = wp_get_attachment_metadata($image_id);
            if (!isset($meta['sizes']) || empty($meta['sizes'])) {
                $unoptimized++;
            }
        }
        $metrics['unoptimized_images'] = $unoptimized;
        
        // Memory usage
        $metrics['memory_usage'] = memory_get_usage(true);
        $metrics['memory_peak'] = memory_get_peak_usage(true);
        
        // Check active plugins (affects performance)
        $metrics['active_plugins'] = count(get_option('active_plugins', []));
        
        return $metrics;
    }
    
    /**
     * Measure current performance
     *
     * @return array Performance metrics
     */
    private function measure_performance(): array {
        return $this->measure_baseline();
    }
    
    /**
     * Optimize images (Free feature)
     */
    private function optimize_images(): void {
        $images = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => 50, // Limit for performance
            'fields' => 'ids'
        ]);
        
        $optimized = 0;
        
        foreach ($images as $image_id) {
            $file = get_attached_file($image_id);
            
            if (!$file || !file_exists($file)) {
                continue;
            }
            
            // Check file size - optimize if > 1MB
            $file_size = filesize($file);
            
            if ($file_size > 1048576) { // 1MB in bytes
                // Try to compress the image
                $compressed = $this->compress_image($file, $file_size);
                
                if ($compressed) {
                    // Regenerate thumbnails for consistency
                    $new_meta = wp_generate_attachment_metadata($image_id, $file);
                    if ($new_meta && !is_wp_error($new_meta)) {
                        wp_update_attachment_metadata($image_id, $new_meta);
                    }
                    $optimized++;
                }
            } else {
                // For small images, just ensure thumbnails exist
                $meta = wp_get_attachment_metadata($image_id);
                if (!isset($meta['sizes']) || empty($meta['sizes'])) {
                    $new_meta = wp_generate_attachment_metadata($image_id, $file);
                    if ($new_meta && !is_wp_error($new_meta)) {
                        wp_update_attachment_metadata($image_id, $new_meta);
                        $optimized++;
                    }
                }
            }
        }
        
        $this->results['images_optimized'] = $optimized;
    }
    
    /**
     * Compress a single image file
     *
     * @param string $file_path Path to image file
     * @param int $original_size Original file size
     * @return bool True if compressed successfully
     */
    private function compress_image(string $file_path, int $original_size): bool {
        // Check if GD library is available
        if (!function_exists('imagecreatefromjpeg')) {
            error_log('WP AI Guardian: GD library not available for image compression');
            return false;
        }
        
        // Skip very large files to avoid memory issues (> 10MB)
        if ($original_size > 10485760) {
            error_log('WP AI Guardian: Skipping compression for very large file: ' . basename($file_path));
            return false;
        }
        
        // Increase memory limit temporarily for image processing
        $original_memory_limit = ini_get('memory_limit');
        @ini_set('memory_limit', '256M');
        
        try {
            // Get image info
            $image_info = @getimagesize($file_path);
            
            if (!$image_info) {
                @ini_set('memory_limit', $original_memory_limit);
                return false;
            }
            
            $mime_type = $image_info['mime'];
            
            // Load image based on type
            $image = null;
            switch ($mime_type) {
                case 'image/jpeg':
                case 'image/jpg':
                    $image = @imagecreatefromjpeg($file_path);
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($file_path);
                    break;
                case 'image/gif':
                    $image = @imagecreatefromgif($file_path);
                    break;
                default:
                    @ini_set('memory_limit', $original_memory_limit);
                    return false;
            }
            
            if (!$image) {
                @ini_set('memory_limit', $original_memory_limit);
                return false;
            }
        } catch (Exception $e) {
            error_log('WP AI Guardian: Error loading image: ' . $e->getMessage());
            @ini_set('memory_limit', $original_memory_limit);
            return false;
        }
        
        // Get dimensions
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Resize if too large (max 2000px on longest side)
        $max_dimension = 2000;
        if ($width > $max_dimension || $height > $max_dimension) {
            if ($width > $height) {
                $new_width = $max_dimension;
                $new_height = intval(($height / $width) * $max_dimension);
            } else {
                $new_height = $max_dimension;
                $new_width = intval(($width / $height) * $max_dimension);
            }
            
            // Create resized image
            $resized = imagecreatetruecolor($new_width, $new_height);
            
            // Preserve transparency for PNG
            if ($mime_type === 'image/png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
            }
            
            // Resize
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }
        
        // Create backup
        $backup_path = $file_path . '.backup';
        @copy($file_path, $backup_path);
        
        // Save compressed image
        $success = false;
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $success = imagejpeg($image, $file_path, 80); // 80% quality
                break;
            case 'image/png':
                $success = imagepng($image, $file_path, 7); // Compression level 7
                break;
            case 'image/gif':
                $success = imagegif($image, $file_path);
                break;
        }
        
        imagedestroy($image);
        
        // Check if compression was successful and saved space
        if ($success) {
            $new_size = filesize($file_path);
            
            // If new file is larger or only slightly smaller, restore backup
            if ($new_size >= $original_size * 0.95) {
                @rename($backup_path, $file_path);
                return false;
            }
            
            // Success - remove backup
            @unlink($backup_path);
            
            // Log the compression
            error_log(sprintf(
                'WP AI Guardian: Compressed image %s from %s to %s (%.1f%% reduction)',
                basename($file_path),
                size_format($original_size),
                size_format($new_size),
                (($original_size - $new_size) / $original_size) * 100
            ));
            
            // Restore memory limit
            @ini_set('memory_limit', $original_memory_limit);
            
            return true;
        }
        
        // Failed - restore backup
        if (file_exists($backup_path)) {
            @rename($backup_path, $file_path);
        }
        
        // Restore memory limit
        @ini_set('memory_limit', $original_memory_limit);
        
        return false;
    }
    
    /**
     * Apply lazy loading to images
     */
    private function apply_lazy_loading(): void {
        // This is handled via filters in init_hooks()
        $this->results['lazy_loading'] = 'enabled';
    }
    
    /**
     * Add lazy loading attribute to images
     *
     * @param string $content HTML content
     * @return string Modified content
     */
    public function add_lazy_loading(string $content): string {
        // Add loading="lazy" to img tags
        $content = preg_replace(
            '/<img(?![^>]*loading=)([^>]+)>/i',
            '<img loading="lazy"$1>',
            $content
        );
        
        return $content;
    }
    
    /**
     * Inject lazy loading script in footer
     */
    public function inject_lazy_script(): void {
        ?>
        <script>
        // Lazy loading fallback for older browsers
        if ('loading' in HTMLImageElement.prototype === false) {
            document.addEventListener('DOMContentLoaded', function() {
                var lazyImages = document.querySelectorAll('img[loading="lazy"]');
                if (lazyImages.length > 0) {
                    var imageObserver = new IntersectionObserver(function(entries) {
                        entries.forEach(function(entry) {
                            if (entry.isIntersecting) {
                                var img = entry.target;
                                img.src = img.dataset.src || img.src;
                                imageObserver.unobserve(img);
                            }
                        });
                    });
                    lazyImages.forEach(function(img) {
                        imageObserver.observe(img);
                    });
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Optimize database queries (Premium)
     */
    private function optimize_queries(): void {
        if (!$this->is_premium()) {
            return;
        }
        
        // Enable object caching for common queries
        $cached_queries = 0;
        
        // Cache recent posts
        $cache_key = 'wpaig_recent_posts';
        if (false === get_transient($cache_key)) {
            $posts = get_posts([
                'posts_per_page' => 10,
                'post_type' => 'post'
            ]);
            set_transient($cache_key, $posts, 3600); // 1 hour
            $cached_queries++;
        }
        
        // Cache popular posts (if views meta exists)
        $cache_key = 'wpaig_popular_posts';
        if (false === get_transient($cache_key)) {
            $posts = get_posts([
                'posts_per_page' => 10,
                'meta_key' => 'views',
                'orderby' => 'meta_value_num',
                'order' => 'DESC'
            ]);
            set_transient($cache_key, $posts, 3600);
            $cached_queries++;
        }
        
        // Cache categories
        $cache_key = 'wpaig_categories';
        if (false === get_transient($cache_key)) {
            $categories = get_categories(['hide_empty' => true]);
            set_transient($cache_key, $categories, 3600);
            $cached_queries++;
        }
        
        $this->results['queries_cached'] = $cached_queries;
    }
    
    /**
     * Enable object cache for template redirects
     */
    public function enable_object_cache(): void {
        if (!$this->is_premium()) {
            return;
        }
        
        // Cache the current query
        global $wp_query;
        
        if (is_singular()) {
            $post_id = get_queried_object_id();
            $cache_key = 'wpaig_post_' . $post_id;
            
            if (false === wp_cache_get($cache_key)) {
                wp_cache_set($cache_key, $wp_query, '', 3600);
            }
        }
    }
    
    /**
     * Optimize CSS/JS assets (Premium)
     */
    private function optimize_assets(): void {
        if (!$this->is_premium()) {
            return;
        }
        
        // This is handled via filter in init_hooks()
        $this->results['assets_minified'] = 'enabled';
    }
    
    /**
     * Minify CSS and JS assets
     */
    public function minify_assets(): void {
        if (!$this->is_premium()) {
            return;
        }
        
        // Start output buffering for minification
        ob_start([$this, 'minify_html_output']);
    }
    
    /**
     * Minify HTML output
     *
     * @param string $buffer HTML buffer
     * @return string Minified HTML
     */
    public function minify_html_output(string $buffer): string {
        // Simple minification: Remove comments and extra whitespace
        
        // Remove HTML comments (except IE conditionals)
        $buffer = preg_replace('/<!--(?!\[if).*?-->/s', '', $buffer);
        
        // Remove whitespace between tags
        $buffer = preg_replace('/>\s+</', '><', $buffer);
        
        // Minify inline CSS
        $buffer = preg_replace_callback(
            '/<style[^>]*>(.*?)<\/style>/is',
            function($matches) {
                return '<style>' . $this->minify_css($matches[1]) . '</style>';
            },
            $buffer
        );
        
        // Minify inline JavaScript
        $buffer = preg_replace_callback(
            '/<script[^>]*>(.*?)<\/script>/is',
            function($matches) {
                // Skip external scripts
                if (strpos($matches[0], 'src=') !== false) {
                    return $matches[0];
                }
                return '<script>' . $this->minify_js($matches[1]) . '</script>';
            },
            $buffer
        );
        
        return $buffer;
    }
    
    /**
     * Minify CSS
     *
     * @param string $css CSS code
     * @return string Minified CSS
     */
    private function minify_css(string $css): string {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
        
        return trim($css);
    }
    
    /**
     * Minify JavaScript
     *
     * @param string $js JavaScript code
     * @return string Minified JS
     */
    private function minify_js(string $js): string {
        // Remove single-line comments
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*.*?\*\//s', '', $js);
        
        // Remove whitespace (basic)
        $js = preg_replace('/\s+/', ' ', $js);
        
        return trim($js);
    }
    
    /**
     * Get AI recommendations (Premium)
     */
    private function get_ai_recommendations(): void {
        if (!$this->is_premium()) {
            return;
        }
        
        // Prepare metrics for AI
        $baseline = $this->metrics['baseline'] ?? [];
        
        $prompt = sprintf(
            "WordPress site performance optimization needed. Current metrics: Load time %.2fs, %d database queries, %d unoptimized images, %d active plugins, %s memory usage. Provide 3 specific optimization recommendations.",
            $baseline['query_time'] ?? 0,
            $baseline['db_queries'] ?? 0,
            $baseline['unoptimized_images'] ?? 0,
            $baseline['active_plugins'] ?? 0,
            size_format($baseline['memory_usage'] ?? 0, 2)
        );
        
        // Get AI recommendations
        $ai_response = $this->generate($prompt, 10);
        
        $this->results['ai_recommendations'] = $ai_response;
    }
    
    /**
     * Calculate improvement percentage
     *
     * @param array $baseline Baseline metrics
     * @param array $optimized Optimized metrics
     * @return array Improvement data
     */
    private function calculate_improvement(array $baseline, array $optimized): array {
        $improvement = [];
        
        // Query time improvement
        if (isset($baseline['query_time']) && isset($optimized['query_time'])) {
            $baseline_time = (float) $baseline['query_time'];
            $optimized_time = (float) $optimized['query_time'];
            
            if ($baseline_time > 0) {
                $improvement['query_time'] = round(
                    (($baseline_time - $optimized_time) / $baseline_time) * 100,
                    2
                );
            }
        }
        
        // Database queries reduction
        if (isset($baseline['db_queries']) && isset($optimized['db_queries'])) {
            $improvement['db_queries'] = round(
                (($baseline['db_queries'] - $optimized['db_queries']) / $baseline['db_queries']) * 100,
                2
            );
        }
        
        // Memory usage reduction
        if (isset($baseline['memory_usage']) && isset($optimized['memory_usage'])) {
            $improvement['memory'] = round(
                (($baseline['memory_usage'] - $optimized['memory_usage']) / $baseline['memory_usage']) * 100,
                2
            );
        }
        
        // Calculate overall score (0-100)
        $score = $this->calculate_pagespeed_score($optimized);
        $improvement['score'] = $score;
        
        // Check if target met
        $improvement['target_met'] = ($improvement['query_time'] ?? 0) >= self::TARGET_BOOST;
        
        return $improvement;
    }
    
    /**
     * Calculate PageSpeed-like score
     *
     * @param array $metrics Performance metrics
     * @return int Score (0-100)
     */
    private function calculate_pagespeed_score(array $metrics): int {
        $score = 100;
        
        // Deduct for slow query time
        $query_time = (float) ($metrics['query_time'] ?? 0);
        if ($query_time > 2.0) {
            $score -= 30;
        } elseif ($query_time > 1.0) {
            $score -= 15;
        } elseif ($query_time > 0.5) {
            $score -= 5;
        }
        
        // Deduct for high DB queries
        $db_queries = $metrics['db_queries'] ?? 0;
        if ($db_queries > 100) {
            $score -= 20;
        } elseif ($db_queries > 50) {
            $score -= 10;
        }
        
        // Deduct for unoptimized images
        $unoptimized = $metrics['unoptimized_images'] ?? 0;
        if ($unoptimized > 20) {
            $score -= 15;
        } elseif ($unoptimized > 10) {
            $score -= 10;
        } elseif ($unoptimized > 5) {
            $score -= 5;
        }
        
        // Deduct for too many plugins
        $plugins = $metrics['active_plugins'] ?? 0;
        if ($plugins > 30) {
            $score -= 15;
        } elseif ($plugins > 20) {
            $score -= 10;
        }
        
        // Deduct for high memory usage
        $memory = $metrics['memory_usage'] ?? 0;
        if ($memory > 128 * 1024 * 1024) { // > 128MB
            $score -= 10;
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Generate optimization report
     *
     * @return array Report data
     */
    private function generate_report(): array {
        $baseline = $this->metrics['baseline'] ?? [];
        $optimized = $this->metrics['optimized'] ?? [];
        $improvement = $this->metrics['improvement'] ?? [];
        
        return [
            'success' => true,
            'baseline' => [
                'query_time' => $baseline['query_time'] ?? 0,
                'db_queries' => $baseline['db_queries'] ?? 0,
                'total_images' => $baseline['total_images'] ?? 0,
                'unoptimized_images' => $baseline['unoptimized_images'] ?? 0,
                'memory_usage' => size_format($baseline['memory_usage'] ?? 0, 2),
                'active_plugins' => $baseline['active_plugins'] ?? 0
            ],
            'optimized' => [
                'query_time' => $optimized['query_time'] ?? 0,
                'db_queries' => $optimized['db_queries'] ?? 0,
                'memory_usage' => size_format($optimized['memory_usage'] ?? 0, 2)
            ],
            'improvements' => $improvement,
            'optimizations' => $this->results,
            'score' => [
                'current' => $improvement['score'] ?? 0,
                'target' => self::TARGET_BOOST,
                'target_met' => $improvement['target_met'] ?? false,
                'rating' => $this->get_score_rating($improvement['score'] ?? 0)
            ],
            'is_premium' => $this->is_premium(),
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Get score rating
     *
     * @param int $score Score value
     * @return string Rating
     */
    private function get_score_rating(int $score): string {
        if ($score >= 90) {
            return 'Excellent';
        } elseif ($score >= 75) {
            return 'Good';
        } elseif ($score >= 50) {
            return 'Fair';
        } else {
            return 'Poor';
        }
    }
    
    /**
     * Log optimization results
     *
     * @param array $report Report data
     */
    private function log_optimization(array $report): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_guardian_logs';
        
        $message = sprintf(
            'Performance optimization: Score %d/100 (%s), Speed improved by %.1f%%',
            $report['score']['current'],
            $report['score']['rating'],
            $report['improvements']['query_time'] ?? 0
        );
        
        $wpdb->insert(
            $table_name,
            [
                'type' => 'performance',
                'message' => $message,
                'timestamp' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }
    
    /**
     * Get current performance metrics
     *
     * @return array Current metrics
     */
    public function get_current_metrics(): array {
        return $this->measure_baseline();
    }
    
    /**
     * Clear performance caches
     */
    public function clear_caches(): void {
        // Clear transients
        delete_transient('wpaig_recent_posts');
        delete_transient('wpaig_popular_posts');
        delete_transient('wpaig_categories');
        
        // Clear object cache
        wp_cache_flush();
    }
}
