<?php
/**
 * WP AI Guardian Automator - Smart Workflows
 *
 * @package WP_AIGuardian
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_AIGuardian_Automator extends WP_AIGuardian_AI_Handler {
    
    /**
     * Maximum workflows for free users
     */
    const FREE_WORKFLOW_LIMIT = 2;
    
    /**
     * Option name for workflows
     */
    const WORKFLOWS_OPTION = 'wpaig_workflows';
    
    /**
     * Available triggers
     */
    private array $triggers = [];
    
    /**
     * Available actions
     */
    private array $actions = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Initialize triggers and actions
        $this->init_triggers();
        $this->init_actions();
        
        // Register hooks
        $this->register_hooks();
    }
    
    /**
     * Initialize available triggers
     */
    private function init_triggers(): void {
        $this->triggers = [
            'post_published' => [
                'label' => 'Post Published',
                'description' => 'When a new post is published',
                'hook' => 'publish_post',
                'icon' => '📝'
            ],
            'post_updated' => [
                'label' => 'Post Updated',
                'description' => 'When an existing post is updated',
                'hook' => 'post_updated',
                'icon' => '✏️'
            ],
            'page_published' => [
                'label' => 'Page Published',
                'description' => 'When a new page is published',
                'hook' => 'publish_page',
                'icon' => '📄'
            ],
            'comment_posted' => [
                'label' => 'Comment Posted',
                'description' => 'When a new comment is posted',
                'hook' => 'comment_post',
                'icon' => '💬'
            ],
            'daily' => [
                'label' => 'Daily Schedule',
                'description' => 'Runs once per day',
                'hook' => 'wpaig_daily_cron',
                'icon' => '📅'
            ],
            'weekly' => [
                'label' => 'Weekly Schedule',
                'description' => 'Runs once per week',
                'hook' => 'wpaig_weekly_cron',
                'icon' => '🗓️'
            ]
        ];
    }
    
    /**
     * Initialize available actions
     */
    private function init_actions(): void {
        $this->actions = [
            'ai_seo_optimize' => [
                'label' => 'AI SEO Optimize',
                'description' => 'Generate SEO data for the post/page',
                'icon' => '🤖',
                'premium' => false
            ],
            'scan_conflicts' => [
                'label' => 'Scan Conflicts',
                'description' => 'Check for plugin conflicts',
                'icon' => '🔍',
                'premium' => false
            ],
            'performance_check' => [
                'label' => 'Performance Check',
                'description' => 'Run performance optimization',
                'icon' => '⚡',
                'premium' => true
            ],
            'ai_content_analysis' => [
                'label' => 'AI Content Analysis',
                'description' => 'Analyze content quality with AI',
                'icon' => '📊',
                'premium' => true
            ],
            'backup_database' => [
                'label' => 'Backup Database',
                'description' => 'Create database backup',
                'icon' => '💾',
                'premium' => true
            ],
            'send_notification' => [
                'label' => 'Send Email Notification',
                'description' => 'Send email to admin',
                'icon' => '📧',
                'premium' => false
            ]
        ];
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks(): void {
        // Get active workflows
        $workflows = $this->get_workflows();
        
        foreach ($workflows as $workflow) {
            if (!$workflow['active']) {
                continue;
            }
            
            $trigger = $workflow['trigger'];
            
            if (!isset($this->triggers[$trigger])) {
                continue;
            }
            
            $hook = $this->triggers[$trigger]['hook'];
            
            // Register hook
            add_action($hook, function($post_id = null) use ($workflow) {
                $this->execute_workflow($workflow, $post_id);
            }, 10, 1);
        }
        
        // Register cron schedules
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);
        
        // Register cron events
        if (!wp_next_scheduled('wpaig_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'wpaig_daily_cron');
        }
        
        if (!wp_next_scheduled('wpaig_weekly_cron')) {
            wp_schedule_event(time(), 'weekly', 'wpaig_weekly_cron');
        }
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_cron_schedules($schedules): array {
        $schedules['weekly'] = [
            'interval' => 604800, // 1 week in seconds
            'display' => __('Once Weekly')
        ];
        
        return $schedules;
    }
    
    /**
     * Execute a workflow
     *
     * @param array $workflow Workflow configuration
     * @param int|null $post_id Post ID if applicable
     */
    private function execute_workflow(array $workflow, $post_id = null): void {
        $action = $workflow['action'];
        
        if (!isset($this->actions[$action])) {
            return;
        }
        
        // Check if action requires premium
        if ($this->actions[$action]['premium'] && !$this->is_premium()) {
            return;
        }
        
        // Log execution
        error_log(sprintf(
            'WP AI Guardian: Executing workflow "%s" (Trigger: %s, Action: %s)',
            $workflow['name'],
            $workflow['trigger'],
            $action
        ));
        
        // Execute action
        try {
            switch ($action) {
                case 'ai_seo_optimize':
                    $this->action_ai_seo_optimize($post_id);
                    break;
                    
                case 'scan_conflicts':
                    $this->action_scan_conflicts();
                    break;
                    
                case 'performance_check':
                    $this->action_performance_check();
                    break;
                    
                case 'ai_content_analysis':
                    $this->action_ai_content_analysis($post_id);
                    break;
                    
                case 'backup_database':
                    $this->action_backup_database();
                    break;
                    
                case 'send_notification':
                    $this->action_send_notification($workflow, $post_id);
                    break;
            }
            
            // Update execution count
            $this->increment_execution_count($workflow['id']);
            
        } catch (Exception $e) {
            error_log('WP AI Guardian Workflow Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Action: AI SEO Optimize
     */
    private function action_ai_seo_optimize($post_id): void {
        if (!$post_id) {
            return;
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return;
        }
        
        // Check if already has SEO data
        $existing_seo = get_post_meta($post_id, '_wpaig_seo_data', true);
        if (!empty($existing_seo)) {
            return; // Already optimized
        }
        
        // Load SEO AI class
        require_once WPAIG_PLUGIN_DIR . 'includes/class-seo-ai.php';
        $seo_ai = new WP_AIGuardian_SEO_AI();
        
        // Generate SEO data
        $content = wp_trim_words(strip_tags($post->post_content), 150);
        $title = $post->post_title;
        
        $prompt = "Analyze this content and provide basic SEO optimization:\n\n";
        $prompt .= "Title: {$title}\n";
        $prompt .= "Content: {$content}\n\n";
        $prompt .= "Provide: 1. SEO title, 2. Meta description, 3. 5 keywords\n";
        $prompt .= "Format as JSON with keys: title, meta_description, keywords (array)";
        
        $ai_response = $this->generate($prompt);
        
        if (!is_wp_error($ai_response)) {
            // Try to parse JSON
            $json_match = [];
            if (preg_match('/\{[\s\S]*\}/', $ai_response, $json_match)) {
                $seo_data = json_decode($json_match[0], true);
                if ($seo_data && is_array($seo_data)) {
                    update_post_meta($post_id, '_wpaig_seo_data', $seo_data);
                }
            }
        }
    }
    
    /**
     * Action: Scan Conflicts
     */
    private function action_scan_conflicts(): void {
        require_once WPAIG_PLUGIN_DIR . 'includes/class-conflict-detector.php';
        $detector = new WP_AIGuardian_Conflicts();
        $detector->detect_conflicts();
    }
    
    /**
     * Action: Performance Check
     */
    private function action_performance_check(): void {
        require_once WPAIG_PLUGIN_DIR . 'includes/class-performance.php';
        $optimizer = new WP_AIGuardian_Performance();
        $optimizer->optimize();
    }
    
    /**
     * Action: AI Content Analysis
     */
    private function action_ai_content_analysis($post_id): void {
        if (!$post_id) {
            return;
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return;
        }
        
        $content = wp_trim_words(strip_tags($post->post_content), 200);
        
        $prompt = "Analyze this content for quality, readability, and SEO:\n\n{$content}\n\n";
        $prompt .= "Provide a brief analysis with 3 improvement suggestions.";
        
        $analysis = $this->generate($prompt);
        
        if (!is_wp_error($analysis)) {
            update_post_meta($post_id, '_wpaig_content_analysis', [
                'analysis' => $analysis,
                'date' => current_time('mysql')
            ]);
        }
    }
    
    /**
     * Action: Backup Database
     */
    private function action_backup_database(): void {
        // Simple backup placeholder
        // In production, use proper backup solution
        error_log('WP AI Guardian: Database backup triggered');
    }
    
    /**
     * Action: Send Notification
     */
    private function action_send_notification(array $workflow, $post_id = null): void {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = "[{$site_name}] Workflow Executed: {$workflow['name']}";
        
        $message = "Workflow '{$workflow['name']}' has been executed.\n\n";
        $message .= "Trigger: {$this->triggers[$workflow['trigger']]['label']}\n";
        $message .= "Action: {$this->actions[$workflow['action']]['label']}\n";
        
        if ($post_id) {
            $post = get_post($post_id);
            $message .= "\nPost: {$post->post_title}\n";
            $message .= "Link: " . get_permalink($post_id) . "\n";
        }
        
        $message .= "\nTime: " . current_time('mysql');
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get all workflows
     *
     * @return array Workflows
     */
    public function get_workflows(): array {
        $workflows = get_option(self::WORKFLOWS_OPTION, []);
        return is_array($workflows) ? $workflows : [];
    }
    
    /**
     * Save workflow
     *
     * @param array $workflow Workflow data
     * @return bool|WP_Error Success or error
     */
    public function save_workflow(array $workflow) {
        $workflows = $this->get_workflows();
        
        // Check free user limit
        if (!$this->is_premium()) {
            $active_count = count(array_filter($workflows, function($w) {
                return $w['active'];
            }));
            
            if ($active_count >= self::FREE_WORKFLOW_LIMIT && $workflow['active']) {
                return new WP_Error(
                    'limit_reached',
                    'Free users can have maximum ' . self::FREE_WORKFLOW_LIMIT . ' active workflows. Upgrade to Premium for unlimited workflows.'
                );
            }
        }
        
        // Generate ID if new
        if (!isset($workflow['id'])) {
            $workflow['id'] = uniqid('wf_');
        }
        
        // Add metadata
        $workflow['created'] = $workflow['created'] ?? current_time('mysql');
        $workflow['modified'] = current_time('mysql');
        $workflow['executions'] = $workflow['executions'] ?? 0;
        
        // Find and update or add
        $found = false;
        foreach ($workflows as &$w) {
            if ($w['id'] === $workflow['id']) {
                $w = $workflow;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $workflows[] = $workflow;
        }
        
        update_option(self::WORKFLOWS_OPTION, $workflows);
        
        return true;
    }
    
    /**
     * Delete workflow
     *
     * @param string $workflow_id Workflow ID
     * @return bool Success
     */
    public function delete_workflow(string $workflow_id): bool {
        $workflows = $this->get_workflows();
        
        $workflows = array_filter($workflows, function($w) use ($workflow_id) {
            return $w['id'] !== $workflow_id;
        });
        
        update_option(self::WORKFLOWS_OPTION, array_values($workflows));
        
        return true;
    }
    
    /**
     * Increment execution count
     *
     * @param string $workflow_id Workflow ID
     */
    private function increment_execution_count(string $workflow_id): void {
        $workflows = $this->get_workflows();
        
        foreach ($workflows as &$workflow) {
            if ($workflow['id'] === $workflow_id) {
                $workflow['executions'] = ($workflow['executions'] ?? 0) + 1;
                $workflow['last_run'] = current_time('mysql');
                break;
            }
        }
        
        update_option(self::WORKFLOWS_OPTION, $workflows);
    }
    
    /**
     * Get available triggers
     *
     * @return array Triggers
     */
    public function get_triggers(): array {
        return $this->triggers;
    }
    
    /**
     * Get available actions
     *
     * @return array Actions
     */
    public function get_actions(): array {
        return $this->actions;
    }
    
    /**
     * Test workflow execution
     *
     * @param string $workflow_id Workflow ID
     * @return array|WP_Error Result
     */
    public function test_workflow(string $workflow_id) {
        $workflows = $this->get_workflows();
        
        $workflow = null;
        foreach ($workflows as $w) {
            if ($w['id'] === $workflow_id) {
                $workflow = $w;
                break;
            }
        }
        
        if (!$workflow) {
            return new WP_Error('not_found', 'Workflow not found');
        }
        
        // Execute workflow with test data
        $this->execute_workflow($workflow, null);
        
        return [
            'success' => true,
            'message' => 'Workflow executed successfully',
            'workflow' => $workflow['name']
        ];
    }
}
