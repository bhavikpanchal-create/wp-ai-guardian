<?php
/**
 * SEO AI Optimizer
 *
 * @package WP_AIGuardian
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_AIGuardian_SEO_AI extends WP_AIGuardian_AI_Handler {
    
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
        // Add metabox to post/page editor
        add_action('add_meta_boxes', [$this, 'add_seo_metabox']);
        
        // Enqueue scripts for editor
        add_action('admin_enqueue_scripts', [$this, 'enqueue_editor_scripts']);
        
        // AJAX handlers
        add_action('wp_ajax_wpaig_seo_optimize', [$this, 'ajax_optimize_seo']);
        
        // Save SEO data
        add_action('save_post', [$this, 'save_seo_data'], 10, 2);
        
        // Register Gutenberg block if active
        if (function_exists('register_block_type')) {
            add_action('init', [$this, 'register_gutenberg_block']);
        }
    }
    
    /**
     * Add SEO AI metabox to post/page editor
     */
    public function add_seo_metabox(): void {
        $post_types = ['post', 'page'];
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'wpaig_seo_ai',
                '🤖 AI SEO Optimizer',
                [$this, 'render_metabox'],
                $post_type,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Render metabox content
     *
     * @param WP_Post $post Current post object
     */
    public function render_metabox($post): void {
        // Nonce for security
        wp_nonce_field('wpaig_seo_optimize', 'wpaig_seo_nonce');
        
        // Get saved SEO data
        $seo_data = get_post_meta($post->ID, '_wpaig_seo_data', true);
        $is_premium = $this->is_premium();
        
        ?>
        <div id="wpaig-seo-metabox-root" 
             data-post-id="<?php echo esc_attr($post->ID); ?>"
             data-is-premium="<?php echo $is_premium ? '1' : '0'; ?>">
            <!-- React component will mount here -->
            <div class="wpaig-seo-loading">
                <p>Loading AI SEO Optimizer...</p>
            </div>
        </div>
        
        <!-- Display current SEO data -->
        <?php if ($seo_data && is_array($seo_data)): ?>
            <div class="wpaig-seo-current" style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                <h4 style="margin: 0 0 10px 0;">Current SEO Data:</h4>
                
                <?php if (isset($seo_data['title'])): ?>
                    <p style="margin: 5px 0;"><strong>Title:</strong><br>
                    <small><?php echo esc_html($seo_data['title']); ?></small></p>
                <?php endif; ?>
                
                <?php if (isset($seo_data['meta_description'])): ?>
                    <p style="margin: 5px 0;"><strong>Meta Description:</strong><br>
                    <small><?php echo esc_html($seo_data['meta_description']); ?></small></p>
                <?php endif; ?>
                
                <?php if (isset($seo_data['keywords']) && is_array($seo_data['keywords'])): ?>
                    <p style="margin: 5px 0;"><strong>Keywords:</strong><br>
                    <small><?php echo esc_html(implode(', ', $seo_data['keywords'])); ?></small></p>
                <?php endif; ?>
                
                <?php if ($is_premium && isset($seo_data['summary'])): ?>
                    <p style="margin: 5px 0;"><strong>Summary:</strong><br>
                    <small><?php echo esc_html(wp_trim_words($seo_data['summary'], 20)); ?></small></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$is_premium): ?>
            <div style="margin-top: 10px; padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; font-size: 12px;">
                <strong>⭐ Premium:</strong> Unlock full SEO optimization with 300-word summaries, FAQ generation, and advanced keywords.
            </div>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Enqueue editor scripts
     *
     * @param string $hook Current admin page
     */
    public function enqueue_editor_scripts($hook): void {
        // Only load on post/page editor
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }
        
        // Enqueue React component
        wp_enqueue_script(
            'wpaig-seo-ai',
            WPAIG_PLUGIN_URL . 'assets/js/seo-ai.js',
            ['wp-element', 'wp-components', 'wp-data', 'wp-api-fetch'],
            WPAIG_VERSION,
            true
        );
        
        // Enqueue styles
        wp_enqueue_style(
            'wpaig-seo-ai',
            WPAIG_PLUGIN_URL . 'assets/css/seo-ai.css',
            [],
            WPAIG_VERSION
        );
        
        // Localize script with data
        wp_localize_script('wpaig-seo-ai', 'wpaigSEO', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpaig_seo_nonce'),
            'isPremium' => $this->is_premium(),
            'postId' => get_the_ID()
        ]);
    }
    
    /**
     * AJAX handler for SEO optimization
     */
    public function ajax_optimize_seo(): void {
        // Verify nonce
        check_ajax_referer('wpaig_seo_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Get post ID and content
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        
        if (!$post_id) {
            wp_send_json_error(['message' => 'Invalid post ID']);
            return;
        }
        
        // If no content provided, get from post
        if (empty($content)) {
            $post = get_post($post_id);
            $content = $post ? $post->post_content : '';
            $title = $post ? $post->post_title : '';
        }
        
        if (empty($content)) {
            wp_send_json_error(['message' => 'No content to optimize']);
            return;
        }
        
        // Generate excerpt for AI
        $excerpt = wp_trim_words(strip_tags($content), 150);
        
        // Check if premium
        $is_premium = $this->is_premium();
        
        // Generate AI recommendations
        $seo_data = $this->generate_seo_data($title, $excerpt, $is_premium);
        
        if (is_wp_error($seo_data)) {
            wp_send_json_error([
                'message' => 'AI optimization failed',
                'error' => $seo_data->get_error_message()
            ]);
            return;
        }
        
        // Save to post meta
        update_post_meta($post_id, '_wpaig_seo_data', $seo_data);
        
        wp_send_json_success([
            'message' => 'SEO optimized successfully!',
            'data' => $seo_data
        ]);
    }
    
    /**
     * Generate SEO data using AI
     *
     * @param string $title Post title
     * @param string $excerpt Content excerpt
     * @param bool $is_premium Premium status
     * @return array|WP_Error SEO data or error
     */
    private function generate_seo_data(string $title, string $excerpt, bool $is_premium) {
        if ($is_premium) {
            // Premium: Full optimization
            $prompt = "Analyze this content and provide comprehensive SEO optimization:\n\n";
            $prompt .= "Title: {$title}\n";
            $prompt .= "Content: {$excerpt}\n\n";
            $prompt .= "Please provide:\n";
            $prompt .= "1. SEO-optimized title (60 chars max)\n";
            $prompt .= "2. Meta description (155 chars max)\n";
            $prompt .= "3. 10 relevant keywords\n";
            $prompt .= "4. 300-word SEO summary\n";
            $prompt .= "5. 3 FAQ questions with answers\n\n";
            $prompt .= "Format as JSON with keys: title, meta_description, keywords (array), summary, faqs (array of {question, answer})";
        } else {
            // Free: Basic optimization
            $prompt = "Analyze this content and provide basic SEO optimization:\n\n";
            $prompt .= "Title: {$title}\n";
            $prompt .= "Content: {$excerpt}\n\n";
            $prompt .= "Please provide:\n";
            $prompt .= "1. SEO-optimized title (60 chars max)\n";
            $prompt .= "2. Meta description (155 chars max)\n";
            $prompt .= "3. 5 relevant keywords\n\n";
            $prompt .= "Format as JSON with keys: title, meta_description, keywords (array)";
        }
        
        // Call AI
        $ai_response = $this->generate($prompt);
        
        if (is_wp_error($ai_response)) {
            return $ai_response;
        }
        
        // Try to parse as JSON
        $seo_data = $this->parse_ai_response($ai_response, $is_premium);
        
        return $seo_data;
    }
    
    /**
     * Parse AI response into structured data
     *
     * @param mixed $response AI response
     * @param bool $is_premium Premium status
     * @return array Parsed SEO data
     */
    private function parse_ai_response($response, bool $is_premium): array {
        // If response is already array (fallback), return it
        if (is_array($response)) {
            return $response;
        }
        
        // Try to extract JSON from response
        $json_match = [];
        if (preg_match('/\{[\s\S]*\}/', $response, $json_match)) {
            $json_data = json_decode($json_match[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
                return $json_data;
            }
        }
        
        // Fallback: Parse text response
        $seo_data = [
            'title' => '',
            'meta_description' => '',
            'keywords' => []
        ];
        
        // Extract title
        if (preg_match('/title[:\s]+([^\n]+)/i', $response, $matches)) {
            $seo_data['title'] = trim($matches[1], '"\' ');
        }
        
        // Extract meta description
        if (preg_match('/meta[_\s]description[:\s]+([^\n]+)/i', $response, $matches)) {
            $seo_data['meta_description'] = trim($matches[1], '"\' ');
        }
        
        // Extract keywords
        if (preg_match('/keywords[:\s]+([^\n]+)/i', $response, $matches)) {
            $keywords_str = trim($matches[1], '"\' []');
            $seo_data['keywords'] = array_map('trim', explode(',', $keywords_str));
        }
        
        // Premium fields
        if ($is_premium) {
            // Extract summary
            if (preg_match('/summary[:\s]+([^\n]{50,})/i', $response, $matches)) {
                $seo_data['summary'] = trim($matches[1]);
            }
            
            // Extract FAQs (simplified)
            $seo_data['faqs'] = [];
            if (preg_match_all('/(?:question|q)[:\s]+([^\n]+)/i', $response, $questions)) {
                if (preg_match_all('/(?:answer|a)[:\s]+([^\n]+)/i', $response, $answers)) {
                    for ($i = 0; $i < min(count($questions[1]), count($answers[1])); $i++) {
                        $seo_data['faqs'][] = [
                            'question' => trim($questions[1][$i]),
                            'answer' => trim($answers[1][$i])
                        ];
                    }
                }
            }
        }
        
        return $seo_data;
    }
    
    /**
     * Save SEO data when post is saved
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function save_seo_data($post_id, $post): void {
        // Check if our nonce is set and valid
        if (!isset($_POST['wpaig_seo_nonce']) || 
            !wp_verify_nonce($_POST['wpaig_seo_nonce'], 'wpaig_seo_optimize')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // SEO data is saved via AJAX, so nothing to do here
        // But we keep this hook for future extensions
    }
    
    /**
     * Register Gutenberg block for SEO AI
     */
    public function register_gutenberg_block(): void {
        // Register block script
        wp_register_script(
            'wpaig-seo-block',
            WPAIG_PLUGIN_URL . 'assets/js/seo-block.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-editor'],
            WPAIG_VERSION,
            true
        );
        
        // Register block
        register_block_type('wpaig/seo-ai', [
            'editor_script' => 'wpaig-seo-block',
            'render_callback' => [$this, 'render_gutenberg_block']
        ]);
    }
    
    /**
     * Render Gutenberg block
     *
     * @param array $attributes Block attributes
     * @return string Block HTML
     */
    public function render_gutenberg_block($attributes): string {
        $post_id = get_the_ID();
        $seo_data = get_post_meta($post_id, '_wpaig_seo_data', true);
        
        if (!$seo_data || !is_array($seo_data)) {
            return '<div class="wpaig-seo-block"><p>No SEO data available. Use the AI SEO Optimizer in the sidebar.</p></div>';
        }
        
        ob_start();
        ?>
        <div class="wpaig-seo-block">
            <h3>🤖 AI-Generated SEO Summary</h3>
            <?php if (isset($seo_data['summary'])): ?>
                <div class="wpaig-seo-summary">
                    <?php echo wp_kses_post(wpautop($seo_data['summary'])); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($seo_data['faqs']) && is_array($seo_data['faqs'])): ?>
                <div class="wpaig-seo-faqs">
                    <h4>Frequently Asked Questions</h4>
                    <?php foreach ($seo_data['faqs'] as $faq): ?>
                        <div class="wpaig-faq-item">
                            <strong><?php echo esc_html($faq['question']); ?></strong>
                            <p><?php echo esc_html($faq['answer']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
