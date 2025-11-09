<?php
/**
 * WP AI Guardian Core Class
 *
 * @package WP_AI_Guardian
 * @since 1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WPAIG_Core {
    
    /**
     * Plugin classes
     */
    private $ai_handler;
    private $seo_ai;
    private $automator;
    private $freemium;
    private $performance;
    private $conflict_detector;
    
    /**
     * Initialize plugin
     */
    public function init(): void {
        // Load all required classes
        $this->load_dependencies();
        
        // Initialize all plugin modules
        $this->init_modules();
        
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Register AJAX handlers
        $this->register_ajax_handlers();
        
        // Register REST API routes
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // Set error handler for production
        if (!WP_DEBUG) {
            set_error_handler([$this, 'error_handler']);
        }
    }
    
    /**
     * Load all dependencies
     */
    private function load_dependencies(): void {
        $classes = [
            'class-ai-handler.php',
            'class-performance.php',
            'class-conflict-detector.php',
            'class-seo-ai.php',
            'class-automator.php',
            'class-freemium.php'
        ];
        
        foreach ($classes as $class) {
            $file = WPAIG_PLUGIN_DIR . 'includes/' . $class;
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
    
    /**
     * Initialize all plugin modules
     */
    private function init_modules(): void {
        try {
            // Initialize Freemium first (license check)
            if (class_exists('WP_AIGuardian_Freemium')) {
                $this->freemium = new WP_AIGuardian_Freemium();
            }
            
            // Initialize AI Handler
            if (class_exists('WP_AIGuardian_AI_Handler')) {
                $this->ai_handler = new WP_AIGuardian_AI_Handler();
            }
            
            // Initialize Performance
            if (class_exists('WP_AIGuardian_Performance')) {
                $this->performance = new WP_AIGuardian_Performance();
            }
            
            // Initialize Conflict Detector
            if (class_exists('WP_AIGuardian_Conflicts')) {
                $this->conflict_detector = new WP_AIGuardian_Conflicts();
            }
            
            // Initialize SEO AI
            if (class_exists('WP_AIGuardian_SEO_AI')) {
                $this->seo_ai = new WP_AIGuardian_SEO_AI();
            }
            
            // Initialize Automator
            if (class_exists('WP_AIGuardian_Automator')) {
                $this->automator = new WP_AIGuardian_Automator();
            }
        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log('WPAIG Module Init Error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Register all AJAX handlers
     */
    private function register_ajax_handlers(): void {
        $handlers = [
            'get_logs',
            'scan_conflicts',
            'deactivate_plugin',
            'optimize_performance',
            'analyze_seo',
            'get_workflows',
            'save_workflow',
            'delete_workflow',
            'test_workflow',
            'get_usage',
            'activate_license',
            'deactivate_license'
        ];
        
        foreach ($handlers as $handler) {
            add_action('wp_ajax_wpaig_' . $handler, [$this, 'ajax_' . $handler]);
        }
    }
    
    /**
     * Custom error handler for production
     */
    public function error_handler($errno, $errstr, $errfile, $errline): bool {
        // Log errors instead of displaying them in production
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $error_msg = "WPAIG Error [{$errno}]: {$errstr} in {$errfile}:{$errline}";
        error_log($error_msg);
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    /**
     * Plugin activation
     */
    public static function activate(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_guardian_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create custom table
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX type_idx (type),
            INDEX timestamp_idx (timestamp)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add plugin options
        add_option('wpaig_hf_api_key', '');
        add_option('wpaig_is_premium', false);
        
        // Log activation
        $wpdb->insert(
            $table_name,
            [
                'type' => 'system',
                'message' => 'WP AI Guardian plugin activated',
                'timestamp' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_guardian_logs';
        
        // Log deactivation before dropping table
        $wpdb->insert(
            $table_name,
            [
                'type' => 'system',
                'message' => 'WP AI Guardian plugin deactivated',
                'timestamp' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
        
        // Drop custom table
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        
        // Clear plugin options
        delete_option('wpaig_hf_api_key');
        delete_option('wpaig_is_premium');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_menu_page(
            'WP AI Guardian',
            'WP AI Guardian',
            'manage_options',
            'wp-ai-guardian',
            [$this, 'render_dashboard'],
            'dashicons-shield-alt',
            30
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts(string $hook): void {
        // Only load on our plugin pages
        if (strpos($hook, 'wp-ai-guardian') === false) {
            return;
        }
        
        // Use production React in production mode
        $react_env = WP_DEBUG ? 'development' : 'production.min';
        
        // Enqueue React
        wp_enqueue_script(
            'react',
            "https://unpkg.com/react@18/umd/react.{$react_env}.js",
            [],
            '18.0.0',
            true
        );
        
        wp_enqueue_script(
            'react-dom',
            "https://unpkg.com/react-dom@18/umd/react-dom.{$react_env}.js",
            ['react'],
            '18.0.0',
            true
        );
        
        // Enqueue custom dashboard script
        wp_enqueue_script(
            'wpaig-dashboard-js',
            WPAIG_PLUGIN_URL . 'assets/js/dashboard.js',
            ['react', 'react-dom'],
            WPAIG_VERSION,
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script('wpaig-dashboard-js', 'wpaigData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('wpaig/v1'),
            'nonce' => wp_create_nonce('wpaig_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'isPremium' => get_option('wpaig_is_premium', false),
            'hasApiKey' => !empty(get_option('wpaig_hf_api_key', '')),
            'version' => WPAIG_VERSION,
            'debug' => WP_DEBUG
        ]);
        
        // Enqueue dashboard styles
        wp_enqueue_style(
            'wpaig-dashboard-css',
            WPAIG_PLUGIN_URL . 'assets/css/dashboard.css',
            [],
            WPAIG_VERSION
        );
        
        // Keep admin styles for settings section
        wp_enqueue_style(
            'wpaig-admin',
            WPAIG_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WPAIG_VERSION
        );
        
        // Add inline minified CSS for performance
        $this->add_inline_critical_css();
    }
    
    /**
     * Add critical inline CSS (minified)
     */
    private function add_inline_critical_css(): void {
        $critical_css = '.wpaig-loading{display:flex;justify-content:center;align-items:center;min-height:200px}.wpaig-spinner{border:3px solid #f3f3f3;border-top:3px solid #2271b1;border-radius:50%;width:40px;height:40px;animation:spin 1s linear infinite}@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}';
        
        wp_add_inline_style('wpaig-admin', $critical_css);
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard(): void {
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wp-ai-guardian'));
        }
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle_form_submission();
        }
        
        // Settings Form Section
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wpaig-dashboard">
                <!-- Settings Form -->
                <div class="wpaig-settings-section">
                    <h2><?php esc_html_e('Settings', 'wp-ai-guardian'); ?></h2>
                    <form method="post" action="">
                        <?php wp_nonce_field('wpaig_settings_action', 'wpaig_settings_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="wpaig_hf_api_key">
                                        <?php esc_html_e('API Key', 'wp-ai-guardian'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input 
                                        type="password" 
                                        id="wpaig_hf_api_key" 
                                        name="wpaig_hf_api_key" 
                                        value="<?php echo esc_attr(get_option('wpaig_hf_api_key', '')); ?>" 
                                        class="regular-text"
                                    />
                                    <p class="description">
                                        <?php esc_html_e('Enter your API Key for AI-powered features.', 'wp-ai-guardian'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Premium Status', 'wp-ai-guardian'); ?>
                                </th>
                                <td>
                                    <label>
                                        <input 
                                            type="checkbox" 
                                            id="wpaig_is_premium" 
                                            name="wpaig_is_premium" 
                                            value="1"
                                            <?php checked(get_option('wpaig_is_premium', false), true); ?>
                                        />
                                        <?php esc_html_e('Enable Premium Features', 'wp-ai-guardian'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Save Settings', 'wp-ai-guardian')); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
        
        // Load React dashboard display
        require_once WPAIG_PLUGIN_DIR . 'admin/partials/dashboard-display.php';
    }
    
    /**
     * Handle form submission
     */
    private function handle_form_submission(): void {
        // Verify nonce
        if (!isset($_POST['wpaig_settings_nonce']) || 
            !wp_verify_nonce($_POST['wpaig_settings_nonce'], 'wpaig_settings_action')) {
            wp_die(__('Security check failed.', 'wp-ai-guardian'));
        }
        
        // Update API key
        if (isset($_POST['wpaig_hf_api_key'])) {
            update_option('wpaig_hf_api_key', sanitize_text_field($_POST['wpaig_hf_api_key']));
        }
        
        // Update premium status
        $is_premium = isset($_POST['wpaig_is_premium']) ? true : false;
        update_option('wpaig_is_premium', $is_premium);
        
        // Log the update
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_guardian_logs';
        $wpdb->insert(
            $table_name,
            [
                'type' => 'settings',
                'message' => 'Settings updated successfully',
                'timestamp' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
        
        // Show success message
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . esc_html__('Settings saved successfully!', 'wp-ai-guardian') . '</p>';
            echo '</div>';
        });
    }
    
    /**
     * AJAX handler to get logs
     */
    public function ajax_get_logs(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpaig_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_guardian_logs';
        
        // Get logs (last 50)
        $logs = $wpdb->get_results(
            "SELECT * FROM {$table_name} ORDER BY timestamp DESC LIMIT 50",
            ARRAY_A
        );
        
        // Calculate stats
        $stats = [
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"),
            'system' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE type = 'system'"),
            'settings' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE type = 'settings'")
        ];
        
        wp_send_json_success([
            'logs' => $logs,
            'stats' => $stats
        ]);
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes(): void {
        $namespace = 'wpaig/v1';
        $permission_callback = function() {
            return current_user_can('manage_options');
        };
        
        // Scan endpoint
        register_rest_route($namespace, '/scan', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_scan'],
            'permission_callback' => $permission_callback
        ]);
        
        // Performance endpoint
        register_rest_route($namespace, '/performance', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_optimize_performance'],
            'permission_callback' => $permission_callback
        ]);
        
        // SEO endpoint
        register_rest_route($namespace, '/seo', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_analyze_seo'],
            'permission_callback' => $permission_callback
        ]);
        
        // Workflows endpoints
        register_rest_route($namespace, '/workflows', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_workflows'],
            'permission_callback' => $permission_callback
        ]);
        
        register_rest_route($namespace, '/workflows', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_save_workflow'],
            'permission_callback' => $permission_callback
        ]);
        
        register_rest_route($namespace, '/workflows/(?P<id>\\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'rest_delete_workflow'],
            'permission_callback' => $permission_callback
        ]);
        
        // License endpoints
        register_rest_route($namespace, '/license', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_license'],
            'permission_callback' => $permission_callback
        ]);
        
        register_rest_route($namespace, '/license/activate', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_activate_license'],
            'permission_callback' => $permission_callback
        ]);
        
        register_rest_route($namespace, '/license/deactivate', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_deactivate_license'],
            'permission_callback' => $permission_callback
        ]);
        
        // Usage endpoint
        register_rest_route($namespace, '/usage', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_usage'],
            'permission_callback' => $permission_callback
        ]);
        
        // Health check endpoint (public)
        register_rest_route($namespace, '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_health_check'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * REST: Health check
     */
    public function rest_health_check(\WP_REST_Request $request): \WP_REST_Response {
        return new \WP_REST_Response([
            'status' => 'ok',
            'version' => WPAIG_VERSION,
            'timestamp' => current_time('mysql')
        ], 200);
    }
    
    /**
     * REST: Optimize performance
     */
    public function rest_optimize_performance(\WP_REST_Request $request): \WP_REST_Response {
        try {
            if (!$this->performance) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Performance module not loaded'
                ], 500);
            }
            
            $result = $this->performance->optimize();
            
            return new \WP_REST_Response([
                'success' => true,
                'data' => $result
            ], 200);
        } catch (Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => WP_DEBUG ? $e->getMessage() : 'Optimization failed'
            ], 500);
        }
    }
    
    /**
     * REST: Analyze SEO
     */
    public function rest_analyze_seo(\WP_REST_Request $request): \WP_REST_Response {
        try {
            // Perform SEO checks
            $score = 0;
            $checks = [];
            $issues = [];
            
            // Check site title
            $site_title = get_bloginfo('name');
            if (!empty($site_title) && strlen($site_title) >= 10 && strlen($site_title) <= 60) {
                $score += 15;
                $checks['title'] = true;
            } else {
                $checks['title'] = false;
                $issues[] = [
                    'type' => 'title',
                    'severity' => 'high',
                    'message' => 'Site title should be 10-60 characters'
                ];
            }
            
            // Check description
            $site_description = get_bloginfo('description');
            if (!empty($site_description) && strlen($site_description) >= 50 && strlen($site_description) <= 160) {
                $score += 15;
                $checks['description'] = true;
            } else {
                $checks['description'] = false;
                $issues[] = [
                    'type' => 'description',
                    'severity' => 'high',
                    'message' => 'Site description should be 50-160 characters'
                ];
            }
            
            return new \WP_REST_Response([
                'success' => true,
                'data' => [
                    'score' => $score,
                    'checks' => $checks,
                    'issues' => $issues
                ]
            ], 200);
        } catch (Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => WP_DEBUG ? $e->getMessage() : 'SEO analysis failed'
            ], 500);
        }
    }
    
    /**
     * REST: Get workflows
     */
    public function rest_get_workflows(\WP_REST_Request $request): \WP_REST_Response {
        $workflows = get_option('wpaig_workflows', []);
        return new \WP_REST_Response([
            'success' => true,
            'data' => $workflows
        ], 200);
    }
    
    /**
     * REST: Save workflow
     */
    public function rest_save_workflow(\WP_REST_Request $request): \WP_REST_Response {
        $workflow = $request->get_json_params();
        
        if (empty($workflow['name']) || empty($workflow['trigger']) || empty($workflow['action'])) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Missing required fields'
            ], 400);
        }
        
        $workflows = get_option('wpaig_workflows', []);
        $workflow['id'] = isset($workflow['id']) ? $workflow['id'] : time();
        $workflows[$workflow['id']] = $workflow;
        update_option('wpaig_workflows', $workflows);
        
        return new \WP_REST_Response([
            'success' => true,
            'data' => $workflow
        ], 200);
    }
    
    /**
     * REST: Delete workflow
     */
    public function rest_delete_workflow(\WP_REST_Request $request): \WP_REST_Response {
        $id = $request->get_param('id');
        $workflows = get_option('wpaig_workflows', []);
        
        if (!isset($workflows[$id])) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Workflow not found'
            ], 404);
        }
        
        unset($workflows[$id]);
        update_option('wpaig_workflows', $workflows);
        
        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Workflow deleted'
        ], 200);
    }
    
    /**
     * REST: Get license info
     */
    public function rest_get_license(\WP_REST_Request $request): \WP_REST_Response {
        if (!$this->freemium) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Freemium module not loaded'
            ], 500);
        }
        
        return new \WP_REST_Response([
            'success' => true,
            'data' => [
                'is_premium' => $this->freemium->is_premium(),
                'license' => $this->freemium->get_license_info()
            ]
        ], 200);
    }
    
    /**
     * REST: Activate license
     */
    public function rest_activate_license(\WP_REST_Request $request): \WP_REST_Response {
        if (!$this->freemium) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Freemium module not loaded'
            ], 500);
        }
        
        $license_key = $request->get_param('license_key');
        if (empty($license_key)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'License key required'
            ], 400);
        }
        
        $result = $this->freemium->activate_license($license_key);
        $status = $result['success'] ? 200 : 400;
        
        return new \WP_REST_Response($result, $status);
    }
    
    /**
     * REST: Deactivate license
     */
    public function rest_deactivate_license(\WP_REST_Request $request): \WP_REST_Response {
        if (!$this->freemium) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Freemium module not loaded'
            ], 500);
        }
        
        $result = $this->freemium->deactivate_license();
        $status = $result['success'] ? 200 : 400;
        
        return new \WP_REST_Response($result, $status);
    }
    
    /**
     * REST: Get usage
     */
    public function rest_get_usage(\WP_REST_Request $request): \WP_REST_Response {
        if (!$this->freemium) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Freemium module not loaded'
            ], 500);
        }
        
        return new \WP_REST_Response([
            'success' => true,
            'data' => [
                'is_premium' => $this->freemium->is_premium(),
                'usage' => $this->freemium->get_usage_summary(),
                'limits' => $this->freemium->get_free_limits()
            ]
        ], 200);
    }
    
    /**
     * REST API endpoint for scanning
     */
    public function rest_scan(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        
        // Simulate scan with dummy data
        $results = [
            [
                'issue' => 'Outdated plugin detected: Contact Form 7',
                'severity' => 'high',
                'category' => 'security'
            ],
            [
                'issue' => 'Large image files impacting page speed',
                'severity' => 'medium',
                'category' => 'performance'
            ],
            [
                'issue' => 'Missing meta description on 3 pages',
                'severity' => 'low',
                'category' => 'seo'
            ],
            [
                'issue' => 'jQuery conflict with theme',
                'severity' => 'medium',
                'category' => 'conflicts'
            ],
            [
                'issue' => 'Database tables need optimization',
                'severity' => 'low',
                'category' => 'performance'
            ]
        ];
        
        // Log scan activity
        $table_name = $wpdb->prefix . 'ai_guardian_logs';
        $wpdb->insert(
            $table_name,
            [
                'type' => 'scan',
                'message' => sprintf('Quick scan completed - Found %d issues', count($results)),
                'timestamp' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
        
        return new \WP_REST_Response([
            'success' => true,
            'results' => $results,
            'timestamp' => current_time('mysql')
        ], 200);
    }
    
    /**
     * AJAX handler for conflict scanning
     */
    public function ajax_scan_conflicts(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpaig_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Load conflict detector
        require_once WPAIG_PLUGIN_DIR . 'includes/class-conflict-detector.php';
        
        // Run scan
        $detector = new WP_AIGuardian_Conflicts();
        $results = $detector->scan();
        
        // Return results
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler for plugin deactivation (Premium only)
     */
    public function ajax_deactivate_plugin(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpaig_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        // Check permissions
        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Check premium status
        if (!get_option('wpaig_is_premium', false)) {
            wp_send_json_error(['message' => 'Premium feature only']);
            return;
        }
        
        // Get plugin file
        $plugin_file = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';
        
        if (empty($plugin_file)) {
            wp_send_json_error(['message' => 'Plugin file required']);
            return;
        }
        
        // Load conflict detector
        require_once WPAIG_PLUGIN_DIR . 'includes/class-conflict-detector.php';
        
        // Deactivate plugin
        $detector = new WP_AIGuardian_Conflicts();
        $success = $detector->auto_deactivate($plugin_file);
        
        if ($success) {
            wp_send_json_success([
                'message' => 'Plugin deactivated successfully',
                'plugin' => $plugin_file
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to deactivate plugin']);
        }
    }
    
    /**
     * AJAX handler for performance optimization
     */
    public function ajax_optimize_performance(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpaig_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Load performance optimizer
        require_once WPAIG_PLUGIN_DIR . 'includes/class-performance.php';
        
        // Run optimization
        $optimizer = new WP_AIGuardian_Performance();
        $results = $optimizer->optimize();
        
        // Return results
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler for SEO analysis
     */
    public function ajax_analyze_seo(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpaig_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Load AI handler
        require_once WPAIG_PLUGIN_DIR . 'includes/class-ai-handler.php';
        
        $ai_handler = new WP_AIGuardian_AI_Handler();
        $is_premium = $ai_handler->is_premium();
        
        // Analyze site SEO
        $analysis = $this->perform_seo_analysis($is_premium, $ai_handler);
        
        // Get posts without SEO data
        $posts = $this->get_posts_without_seo();
        
        // Return results
        wp_send_json_success([
            'analysis' => $analysis,
            'posts' => $posts
        ]);
    }
    
    /**
     * Perform SEO analysis
     *
     * @param bool $is_premium Premium status
     * @param WP_AIGuardian_AI_Handler $ai_handler AI handler instance
     * @return array Analysis results
     */
    private function perform_seo_analysis($is_premium, $ai_handler): array {
        $issues = [];
        $score = 100;
        
        // Check site title
        $site_title = get_bloginfo('name');
        if (empty($site_title)) {
            $issues[] = [
                'title' => 'Missing Site Title',
                'description' => 'Your site doesn\'t have a title. Add one in Settings → General.',
                'severity' => 'high',
                'fixable' => false
            ];
            $score -= 15;
        } elseif (strlen($site_title) < 10) {
            $issues[] = [
                'title' => 'Site Title Too Short',
                'description' => 'Your site title is too short. Aim for 30-60 characters for better SEO.',
                'severity' => 'medium',
                'fixable' => false
            ];
            $score -= 5;
        }
        
        // Check site description
        $site_desc = get_bloginfo('description');
        if (empty($site_desc)) {
            $issues[] = [
                'title' => 'Missing Site Description',
                'description' => 'Add a tagline in Settings → General to describe your site.',
                'severity' => 'medium',
                'fixable' => false
            ];
            $score -= 10;
        }
        
        // Check permalink structure
        $permalink_structure = get_option('permalink_structure');
        if (empty($permalink_structure) || $permalink_structure === '/?p=%post_id%') {
            $issues[] = [
                'title' => 'Non-SEO Friendly Permalinks',
                'description' => 'Use SEO-friendly permalinks. Go to Settings → Permalinks and select "Post name".',
                'severity' => 'high',
                'fixable' => false
            ];
            $score -= 20;
        }
        
        // Check for posts without SEO data
        $posts_count = wp_count_posts('post');
        $posts_without_seo = $this->count_posts_without_seo();
        
        if ($posts_without_seo > 0) {
            $percentage = ($posts_without_seo / max($posts_count->publish, 1)) * 100;
            if ($percentage > 50) {
                $issues[] = [
                    'title' => 'Many Posts Missing SEO Data',
                    'description' => "{$posts_without_seo} posts don't have SEO optimization. Use the AI SEO Optimizer in post editor.",
                    'severity' => 'high',
                    'fixable' => true
                ];
                $score -= 15;
            } elseif ($percentage > 20) {
                $issues[] = [
                    'title' => 'Some Posts Missing SEO Data',
                    'description' => "{$posts_without_seo} posts need SEO optimization.",
                    'severity' => 'medium',
                    'fixable' => true
                ];
                $score -= 8;
            }
        }
        
        // Check for XML sitemap
        $sitemap_exists = $this->check_sitemap_exists();
        if (!$sitemap_exists) {
            $issues[] = [
                'title' => 'No XML Sitemap Detected',
                'description' => 'Install an SEO plugin like Yoast or RankMath to generate an XML sitemap.',
                'severity' => 'medium',
                'fixable' => false
            ];
            $score -= 10;
        }
        
        // Check for robots.txt
        $robots_txt = $this->check_robots_txt();
        if (!$robots_txt) {
            $issues[] = [
                'title' => 'Missing robots.txt',
                'description' => 'Create a robots.txt file to guide search engines.',
                'severity' => 'low',
                'fixable' => false
            ];
            $score -= 5;
        }
        
        // Get AI recommendations if premium
        $recommendations = null;
        if ($is_premium && count($issues) > 0) {
            $issues_text = implode('; ', array_map(function($issue) {
                return $issue['title'];
            }, $issues));
            
            $prompt = "SEO Analysis for WordPress site:\n\n";
            $prompt .= "Site: " . get_bloginfo('name') . "\n";
            $prompt .= "Issues found: {$issues_text}\n\n";
            $prompt .= "Provide 3-5 actionable SEO recommendations to improve this WordPress site. Be specific and practical.";
            
            $ai_response = $ai_handler->generate($prompt);
            
            if (!is_wp_error($ai_response)) {
                // Try to parse as list
                $lines = explode("\n", $ai_response);
                $recommendations = [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (preg_match('/^\d+[\.\)]\s*(.+)$/', $line, $matches)) {
                        $recommendations[] = $matches[1];
                    } elseif (preg_match('/^[-*]\s*(.+)$/', $line, $matches)) {
                        $recommendations[] = $matches[1];
                    } elseif (!empty($line) && strlen($line) > 20) {
                        $recommendations[] = $line;
                    }
                }
                
                if (empty($recommendations)) {
                    $recommendations = $ai_response;
                }
            }
        }
        
        // Ensure score doesn't go below 0
        $score = max(0, $score);
        
        return [
            'score' => $score,
            'issues' => $issues,
            'recommendations' => $recommendations
        ];
    }
    
    /**
     * Get posts without SEO data
     *
     * @return array Posts without SEO optimization
     */
    private function get_posts_without_seo(): array {
        global $wpdb;
        
        // Query posts that don't have SEO meta data
        $query = "
            SELECT p.ID, p.post_title, p.post_date
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wpaig_seo_data'
            WHERE p.post_type IN ('post', 'page')
            AND p.post_status = 'publish'
            AND pm.meta_id IS NULL
            ORDER BY p.post_date DESC
            LIMIT 20
        ";
        
        $results = $wpdb->get_results($query);
        
        $posts = [];
        foreach ($results as $row) {
            $posts[] = [
                'id' => $row->ID,
                'title' => $row->post_title,
                'date' => mysql2date('F j, Y', $row->post_date),
                'edit_url' => get_edit_post_link($row->ID)
            ];
        }
        
        return $posts;
    }
    
    /**
     * Count posts without SEO data
     *
     * @return int Number of posts without SEO
     */
    private function count_posts_without_seo(): int {
        global $wpdb;
        
        $query = "
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wpaig_seo_data'
            WHERE p.post_type IN ('post', 'page')
            AND p.post_status = 'publish'
            AND pm.meta_id IS NULL
        ";
        
        return (int) $wpdb->get_var($query);
    }
    
    /**
     * Check if sitemap exists
     *
     * @return bool True if sitemap exists
     */
    private function check_sitemap_exists(): bool {
        $sitemap_urls = [
            home_url('/sitemap.xml'),
            home_url('/sitemap_index.xml'),
            home_url('/wp-sitemap.xml')
        ];
        
        foreach ($sitemap_urls as $url) {
            $response = wp_remote_head($url);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if robots.txt exists
     *
     * @return bool True if robots.txt exists
     */
    private function check_robots_txt(): bool {
        $robots_url = home_url('/robots.txt');
        $response = wp_remote_head($robots_url);
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * AJAX handler for getting workflows
     */
    public function ajax_get_workflows(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpaig_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Load automator
        require_once WPAIG_PLUGIN_DIR . 'includes/class-automator.php';
        $automator = new WP_AIGuardian_Automator();
        
        // Get workflows and metadata
        $workflows = $automator->get_workflows();
        $triggers = $automator->get_triggers();
        $actions = $automator->get_actions();
        
        wp_send_json_success([
            'workflows' => $workflows,
            'triggers' => $triggers,
            'actions' => $actions,
            'is_premium' => $automator->is_premium(),
            'free_limit' => WP_AIGuardian_Automator::FREE_WORKFLOW_LIMIT
        ]);
    }
    
    /**
     * AJAX handler for saving workflow
     */
    public function ajax_save_workflow(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpaig_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Get workflow data
        $workflow = isset($_POST['workflow']) ? json_decode(stripslashes($_POST['workflow']), true) : null;
        
        if (!$workflow) {
            wp_send_json_error(['message' => 'Invalid workflow data']);
            return;
        }
        
        // Validate required fields
        if (empty($workflow['name']) || empty($workflow['trigger']) || empty($workflow['action'])) {
            wp_send_json_error(['message' => 'Name, trigger, and action are required']);
            return;
        }
        
        // Load automator
        require_once WPAIG_PLUGIN_DIR . 'includes/class-automator.php';
        $automator = new WP_AIGuardian_Automator();
        
        // Save workflow
        $result = $automator->save_workflow($workflow);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }
        
        wp_send_json_success([
            'message' => 'Workflow saved successfully',
            'workflows' => $automator->get_workflows()
        ]);
    }
    
    /**
     * AJAX handler for deleting workflow
     */
    public function ajax_delete_workflow(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpaig_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Get workflow ID
        $workflow_id = isset($_POST['workflow_id']) ? sanitize_text_field($_POST['workflow_id']) : '';
        
        if (empty($workflow_id)) {
            wp_send_json_error(['message' => 'Workflow ID required']);
            return;
        }
        
        // Load automator
        require_once WPAIG_PLUGIN_DIR . 'includes/class-automator.php';
        $automator = new WP_AIGuardian_Automator();
        
        // Delete workflow
        $automator->delete_workflow($workflow_id);
        
        wp_send_json_success([
            'message' => 'Workflow deleted successfully',
            'workflows' => $automator->get_workflows()
        ]);
    }
    
    /**
     * AJAX handler for testing workflow
     */
    public function ajax_test_workflow(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpaig_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Get workflow ID
        $workflow_id = isset($_POST['workflow_id']) ? sanitize_text_field($_POST['workflow_id']) : '';
        
        if (empty($workflow_id)) {
            wp_send_json_error(['message' => 'Workflow ID required']);
            return;
        }
        
        // Load automator
        require_once WPAIG_PLUGIN_DIR . 'includes/class-automator.php';
        $automator = new WP_AIGuardian_Automator();
        
        // Test workflow
        $result = $automator->test_workflow($workflow_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for getting usage data
     */
    public function ajax_get_usage(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpaig_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Load freemium handler
        require_once WPAIG_PLUGIN_DIR . 'includes/class-freemium.php';
        $freemium = new WP_AIGuardian_Freemium();
        
        wp_send_json_success([
            'is_premium' => $freemium->is_premium(),
            'usage' => $freemium->get_usage_summary(),
            'license' => $freemium->get_license_info(),
            'limits' => $freemium->get_free_limits()
        ]);
    }
    
    /**
     * AJAX handler for activating license
     */
    public function ajax_activate_license(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpaig_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
        
        if (empty($license_key)) {
            wp_send_json_error(['message' => 'License key required']);
            return;
        }
        
        // Load freemium handler
        require_once WPAIG_PLUGIN_DIR . 'includes/class-freemium.php';
        $freemium = new WP_AIGuardian_Freemium();
        
        $result = $freemium->activate_license($license_key);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX handler for deactivating license
     */
    public function ajax_deactivate_license(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpaig_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Load freemium handler
        require_once WPAIG_PLUGIN_DIR . 'includes/class-freemium.php';
        $freemium = new WP_AIGuardian_Freemium();
        
        $result = $freemium->deactivate_license();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}
