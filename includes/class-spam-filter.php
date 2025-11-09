<?php
/**
 * Spam Filter Class
 * 
 * AI-powered spam detection for:
 * - Comment spam classification
 * - Auto-moderation of comments
 * - Bulk check pending comments
 * - Accuracy tracking and learning
 * 
 * @package WP_AI_Guardian
 * @since 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_AIGuardian_Spam_Filter {
    
    /**
     * Grok AI Handler instance
     */
    private $grok_handler;
    
    /**
     * Training data table
     */
    private $training_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->training_table = $wpdb->prefix . 'ai_guardian_spam_training';
        
        // Load Grok handler
        if (!class_exists('WP_AIGuardian_Grok_AI_Handler')) {
            require_once WPAIG_PLUGIN_DIR . 'includes/class-grok-ai-handler.php';
        }
        
        $this->grok_handler = new WP_AIGuardian_Grok_AI_Handler();
        
        // Register hooks
        $this->register_hooks();
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks(): void {
        // Intercept comments before they're saved
        add_filter('preprocess_comment', [$this, 'check_comment_spam'], 10, 1);
        
        // Add custom column to comments list
        add_filter('manage_edit-comments_columns', [$this, 'add_spam_score_column']);
        add_action('manage_comments_custom_column', [$this, 'display_spam_score_column'], 10, 2);
        
        // Add meta box to comment edit screen
        add_action('add_meta_boxes_comment', [$this, 'add_spam_details_meta_box']);
    }
    
    /**
     * Check comment for spam before saving
     * 
     * @param array $commentdata Comment data
     * @return array Modified comment data
     */
    public function check_comment_spam(array $commentdata): array {
        // Skip if auto-moderation is disabled
        if (!get_option('wpaig_auto_moderate_comments', false)) {
            return $commentdata;
        }
        
        // Skip for logged-in users with moderate_comments capability
        if (is_user_logged_in() && current_user_can('moderate_comments')) {
            return $commentdata;
        }
        
        // Prepare metadata
        $metadata = [
            'author' => $commentdata['comment_author'] ?? '',
            'email' => $commentdata['comment_author_email'] ?? '',
            'url' => $commentdata['comment_author_url'] ?? '',
            'ip' => $commentdata['comment_author_IP'] ?? ''
        ];
        
        // Classify with AI
        $result = $this->classify_comment($commentdata['comment_content'], $metadata);
        
        if ($result['success'] && $result['is_spam']) {
            // Store spam classification
            add_comment_meta(0, '_wpaig_spam_score', $result['spam_score'], true);
            add_comment_meta(0, '_wpaig_spam_confidence', $result['confidence'], true);
            add_comment_meta(0, '_wpaig_spam_indicators', wp_json_encode($result['spam_indicators']), true);
            
            // Auto-moderate based on confidence
            $auto_spam_threshold = get_option('wpaig_auto_spam_threshold', 80);
            
            if ($result['confidence'] >= $auto_spam_threshold) {
                // Mark as spam
                add_filter('pre_comment_approved', function() {
                    return 'spam';
                }, 99);
                
                // Log the action
                $this->log_spam_action('auto_spam', $commentdata, $result);
            } else {
                // Hold for moderation
                add_filter('pre_comment_approved', function() {
                    return 0;
                }, 99);
                
                $this->log_spam_action('hold_moderation', $commentdata, $result);
            }
        }
        
        return $commentdata;
    }
    
    /**
     * Classify a single comment
     * 
     * @param string $content Comment content
     * @param array $metadata Comment metadata
     * @return array Classification results
     */
    public function classify_comment(string $content, array $metadata = []): array {
        // Basic spam checks first (save AI credits)
        $basic_spam_score = $this->basic_spam_check($content, $metadata);
        
        // If obviously spam or obviously legitimate, skip AI
        if ($basic_spam_score >= 90) {
            return [
                'success' => true,
                'is_spam' => true,
                'confidence' => 95,
                'spam_score' => $basic_spam_score,
                'spam_indicators' => ['Basic heuristics detected spam'],
                'recommended_action' => 'spam',
                'reasoning' => 'Failed basic spam checks',
                'used_ai' => false
            ];
        }
        
        if ($basic_spam_score <= 10) {
            return [
                'success' => true,
                'is_spam' => false,
                'confidence' => 90,
                'spam_score' => $basic_spam_score,
                'spam_indicators' => [],
                'recommended_action' => 'approve',
                'reasoning' => 'Passed basic checks',
                'used_ai' => false
            ];
        }
        
        // Use AI for uncertain cases
        $result = $this->grok_handler->classify_spam($content, $metadata);
        
        if ($result['success']) {
            $result['used_ai'] = true;
            
            // Store for training
            $this->store_classification($content, $metadata, $result);
        }
        
        return $result;
    }
    
    /**
     * Bulk check pending comments
     * 
     * @param int $limit Number of comments to check
     * @return array Bulk check results
     */
    public function bulk_check_pending(int $limit = 50): array {
        $comments = get_comments([
            'status' => 'hold',
            'number' => $limit,
            'orderby' => 'comment_date',
            'order' => 'DESC'
        ]);
        
        $results = [
            'total' => count($comments),
            'spam' => 0,
            'legitimate' => 0,
            'uncertain' => 0,
            'details' => []
        ];
        
        foreach ($comments as $comment) {
            $metadata = [
                'author' => $comment->comment_author,
                'email' => $comment->comment_author_email,
                'url' => $comment->comment_author_url,
                'ip' => $comment->comment_author_IP
            ];
            
            $classification = $this->classify_comment($comment->comment_content, $metadata);
            
            if ($classification['success']) {
                // Update comment meta
                update_comment_meta($comment->comment_ID, '_wpaig_spam_score', $classification['spam_score']);
                update_comment_meta($comment->comment_ID, '_wpaig_spam_confidence', $classification['confidence']);
                update_comment_meta($comment->comment_ID, '_wpaig_spam_indicators', wp_json_encode($classification['spam_indicators']));
                
                // Categorize
                if ($classification['is_spam'] && $classification['confidence'] >= 70) {
                    $results['spam']++;
                    $category = 'spam';
                } elseif (!$classification['is_spam'] && $classification['confidence'] >= 70) {
                    $results['legitimate']++;
                    $category = 'legitimate';
                } else {
                    $results['uncertain']++;
                    $category = 'uncertain';
                }
                
                $results['details'][] = [
                    'comment_id' => $comment->comment_ID,
                    'author' => $comment->comment_author,
                    'category' => $category,
                    'spam_score' => $classification['spam_score'],
                    'confidence' => $classification['confidence'],
                    'recommendation' => $classification['recommended_action']
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Basic spam check (heuristics)
     * 
     * @param string $content Comment content
     * @param array $metadata Comment metadata
     * @return int Spam score (0-100)
     */
    private function basic_spam_check(string $content, array $metadata): int {
        $score = 0;
        
        // Check content length
        $content_length = strlen($content);
        if ($content_length < 10) {
            $score += 20;
        }
        
        // Check for excessive links
        $link_count = substr_count(strtolower($content), 'http');
        if ($link_count > 3) {
            $score += 30;
        } elseif ($link_count > 1) {
            $score += 15;
        }
        
        // Check for spam keywords
        $spam_keywords = ['viagra', 'cialis', 'casino', 'poker', 'lottery', 'pills', 'pharmacy', 'replica', 'rolex'];
        foreach ($spam_keywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $score += 40;
                break;
            }
        }
        
        // Check for suspicious patterns
        if (preg_match('/\[url=/i', $content)) {
            $score += 25;
        }
        
        // Check for excessive capitals
        $uppercase_count = preg_match_all('/[A-Z]/', $content);
        if ($uppercase_count > $content_length * 0.5) {
            $score += 15;
        }
        
        // Check email domain
        if (!empty($metadata['email'])) {
            $disposable_domains = ['tempmail.com', '10minutemail.com', 'guerrillamail.com'];
            $email_domain = substr(strrchr($metadata['email'], '@'), 1);
            
            if (in_array($email_domain, $disposable_domains)) {
                $score += 20;
            }
        }
        
        // Check for URL in author name
        if (!empty($metadata['author']) && stripos($metadata['author'], 'http') !== false) {
            $score += 25;
        }
        
        return min(100, $score);
    }
    
    /**
     * Store classification for training
     */
    private function store_classification(string $content, array $metadata, array $result): void {
        global $wpdb;
        
        $data = [
            'content' => $content,
            'author' => $metadata['author'] ?? '',
            'email' => $metadata['email'] ?? '',
            'classification' => $result['is_spam'] ? 'spam' : 'legitimate',
            'confidence' => $result['confidence'],
            'spam_score' => $result['spam_score'],
            'indicators' => wp_json_encode($result['spam_indicators']),
            'created_at' => current_time('mysql')
        ];
        
        $wpdb->insert(
            $this->training_table,
            $data,
            ['%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
        );
    }
    
    /**
     * Log spam action
     */
    private function log_spam_action(string $action, array $commentdata, array $result): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_guardian_logs';
        
        $message = sprintf(
            'Spam filter: %s - Author: %s, Confidence: %d%%, Score: %d',
            $action,
            $commentdata['comment_author'] ?? 'Unknown',
            $result['confidence'],
            $result['spam_score']
        );
        
        $wpdb->insert(
            $table_name,
            [
                'type' => 'spam_filter',
                'message' => $message,
                'timestamp' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }
    
    /**
     * Get accuracy statistics
     * 
     * @return array Accuracy stats
     */
    public function get_accuracy_stats(): array {
        global $wpdb;
        
        // Get feedback data (when users manually mark spam/ham)
        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->training_table} 
            WHERE feedback IS NOT NULL"
        );
        
        $correct = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->training_table} 
            WHERE feedback IS NOT NULL 
            AND classification = feedback"
        );
        
        $accuracy = $total > 0 ? round(($correct / $total) * 100, 1) : 0;
        
        // Get classification breakdown
        $spam_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->training_table} 
            WHERE classification = 'spam'"
        );
        
        $ham_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->training_table} 
            WHERE classification = 'legitimate'"
        );
        
        // Get recent performance
        $recent = $wpdb->get_results(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN feedback = classification THEN 1 ELSE 0 END) as correct
            FROM {$this->training_table}
            WHERE feedback IS NOT NULL 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC",
            ARRAY_A
        );
        
        return [
            'overall_accuracy' => $accuracy,
            'total_classifications' => (int)$total,
            'correct_classifications' => (int)$correct,
            'spam_detected' => (int)$spam_count,
            'legitimate_detected' => (int)$ham_count,
            'recent_performance' => $recent
        ];
    }
    
    /**
     * Provide feedback on classification
     * 
     * @param int $comment_id Comment ID
     * @param string $feedback 'spam' or 'legitimate'
     * @return bool Success status
     */
    public function provide_feedback(int $comment_id, string $feedback): bool {
        global $wpdb;
        
        $comment = get_comment($comment_id);
        
        if (!$comment) {
            return false;
        }
        
        // Find the classification record
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$this->training_table} 
            WHERE content = %s 
            AND author = %s 
            ORDER BY created_at DESC 
            LIMIT 1",
            $comment->comment_content,
            $comment->comment_author
        ));
        
        if ($record) {
            $wpdb->update(
                $this->training_table,
                ['feedback' => $feedback],
                ['id' => $record->id],
                ['%s'],
                ['%d']
            );
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Add spam score column to comments list
     */
    public function add_spam_score_column(array $columns): array {
        $columns['wpaig_spam_score'] = 'AI Spam Score';
        return $columns;
    }
    
    /**
     * Display spam score in column
     */
    public function display_spam_score_column(string $column, int $comment_id): void {
        if ($column === 'wpaig_spam_score') {
            $spam_score = get_comment_meta($comment_id, '_wpaig_spam_score', true);
            $confidence = get_comment_meta($comment_id, '_wpaig_spam_confidence', true);
            
            if ($spam_score !== '') {
                $color = $spam_score >= 70 ? 'red' : ($spam_score >= 40 ? 'orange' : 'green');
                echo '<span style="color: ' . $color . '; font-weight: bold;">';
                echo esc_html($spam_score) . '%';
                echo '</span>';
                
                if ($confidence !== '') {
                    echo '<br><small>(' . esc_html($confidence) . '% confidence)</small>';
                }
            } else {
                echo '—';
            }
        }
    }
    
    /**
     * Add spam details meta box
     */
    public function add_spam_details_meta_box(): void {
        add_meta_box(
            'wpaig_spam_details',
            'AI Spam Analysis',
            [$this, 'render_spam_details_meta_box'],
            'comment',
            'normal',
            'high'
        );
    }
    
    /**
     * Render spam details meta box
     */
    public function render_spam_details_meta_box($comment): void {
        $spam_score = get_comment_meta($comment->comment_ID, '_wpaig_spam_score', true);
        $confidence = get_comment_meta($comment->comment_ID, '_wpaig_spam_confidence', true);
        $indicators = get_comment_meta($comment->comment_ID, '_wpaig_spam_indicators', true);
        
        if ($spam_score === '') {
            echo '<p>No AI analysis available for this comment.</p>';
            echo '<button type="button" class="button" onclick="wpaigAnalyzeComment(' . $comment->comment_ID . ')">Analyze Now</button>';
            return;
        }
        
        $indicators = json_decode($indicators, true);
        
        ?>
        <div class="wpaig-spam-details">
            <p><strong>Spam Score:</strong> <?php echo esc_html($spam_score); ?>%</p>
            <p><strong>Confidence:</strong> <?php echo esc_html($confidence); ?>%</p>
            
            <?php if (!empty($indicators)): ?>
                <p><strong>Spam Indicators:</strong></p>
                <ul>
                    <?php foreach ($indicators as $indicator): ?>
                        <li><?php echo esc_html($indicator); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <p>
                <button type="button" class="button" onclick="wpaigProvideFeedback(<?php echo $comment->comment_ID; ?>, 'spam')">
                    Mark as Spam
                </button>
                <button type="button" class="button" onclick="wpaigProvideFeedback(<?php echo $comment->comment_ID; ?>, 'legitimate')">
                    Mark as Legitimate
                </button>
            </p>
        </div>
        <?php
    }
}
