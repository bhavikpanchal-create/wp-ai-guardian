<?php
/**
 * WP AI Guardian Freemium Handler
 *
 * @package WP_AIGuardian
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_AIGuardian_Freemium {
    
    /**
     * Usage option name
     */
    const USAGE_OPTION = 'wpaig_usage';
    
    /**
     * License option name
     */
    const LICENSE_OPTION = 'wpaig_license';
    
    /**
     * EDD API URL (placeholder)
     */
    const EDD_STORE_URL = 'https://your-store.com';
    const EDD_ITEM_NAME = 'WP AI Guardian Premium';
    
    /**
     * Free tier limits
     */
    private array $free_limits = [
        'ai_calls_per_month' => 50,
        'workflows' => 2,
        'image_optimization_per_month' => 20,
        'seo_optimization_per_month' => 30,
        'scans_per_day' => 5
    ];
    
    /**
     * Usage data
     */
    private array $usage = [];
    
    /**
     * License data
     */
    private array $license = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_usage();
        $this->load_license();
        
        // Register hooks
        add_action('admin_enqueue_scripts', [$this, 'enqueue_upsell_scripts']);
        add_action('wp_ajax_wpaig_check_limit', [$this, 'ajax_check_limit']);
        add_action('wp_ajax_wpaig_activate_license', [$this, 'ajax_activate_license']);
        add_action('wp_ajax_wpaig_deactivate_license', [$this, 'ajax_deactivate_license']);
        
        // Daily reset hook
        add_action('wpaig_daily_reset', [$this, 'reset_daily_usage']);
        if (!wp_next_scheduled('wpaig_daily_reset')) {
            wp_schedule_event(time(), 'daily', 'wpaig_daily_reset');
        }
        
        // Monthly reset hook
        add_action('wpaig_monthly_reset', [$this, 'reset_monthly_usage']);
        if (!wp_next_scheduled('wpaig_monthly_reset')) {
            wp_schedule_event(time(), 'monthly', 'wpaig_monthly_reset');
        }
    }
    
    /**
     * Load usage data
     */
    private function load_usage(): void {
        $this->usage = get_option(self::USAGE_OPTION, [
            'ai_calls' => 0,
            'workflows_active' => 0,
            'images_optimized' => 0,
            'seo_optimizations' => 0,
            'scans_today' => 0,
            'last_reset_daily' => date('Y-m-d'),
            'last_reset_monthly' => date('Y-m')
        ]);
    }
    
    /**
     * Load license data
     */
    private function load_license(): void {
        $this->license = get_option(self::LICENSE_OPTION, [
            'key' => '',
            'status' => 'invalid',
            'expires' => '',
            'checked' => ''
        ]);
    }
    
    /**
     * Check if user is premium
     */
    public function is_premium(): bool {
        // Legacy check from settings
        if (get_option('wpaig_is_premium', false)) {
            return true;
        }
        
        // License check
        return $this->license['status'] === 'valid';
    }
    
    /**
     * Check if limit reached
     *
     * @param string $feature Feature name
     * @return array [allowed, limit, current, remaining]
     */
    public function check_limit(string $feature): array {
        // Premium users have no limits
        if ($this->is_premium()) {
            return [
                'allowed' => true,
                'limit' => 'unlimited',
                'current' => 0,
                'remaining' => 'unlimited'
            ];
        }
        
        // Map feature to usage key and limit
        $mapping = [
            'ai_call' => ['ai_calls', 'ai_calls_per_month'],
            'workflow' => ['workflows_active', 'workflows'],
            'image_optimization' => ['images_optimized', 'image_optimization_per_month'],
            'seo_optimization' => ['seo_optimizations', 'seo_optimization_per_month'],
            'scan' => ['scans_today', 'scans_per_day']
        ];
        
        if (!isset($mapping[$feature])) {
            return ['allowed' => true, 'limit' => 0, 'current' => 0, 'remaining' => 0];
        }
        
        [$usage_key, $limit_key] = $mapping[$feature];
        $current = $this->usage[$usage_key] ?? 0;
        $limit = $this->free_limits[$limit_key];
        $remaining = max(0, $limit - $current);
        
        return [
            'allowed' => $current < $limit,
            'limit' => $limit,
            'current' => $current,
            'remaining' => $remaining
        ];
    }
    
    /**
     * Increment usage
     *
     * @param string $feature Feature name
     * @return bool Success
     */
    public function increment_usage(string $feature): bool {
        $mapping = [
            'ai_call' => 'ai_calls',
            'workflow' => 'workflows_active',
            'image_optimization' => 'images_optimized',
            'seo_optimization' => 'seo_optimizations',
            'scan' => 'scans_today'
        ];
        
        if (!isset($mapping[$feature])) {
            return false;
        }
        
        $usage_key = $mapping[$feature];
        $this->usage[$usage_key] = ($this->usage[$usage_key] ?? 0) + 1;
        
        return update_option(self::USAGE_OPTION, $this->usage);
    }
    
    /**
     * Set workflow count
     */
    public function set_workflow_count(int $count): bool {
        $this->usage['workflows_active'] = $count;
        return update_option(self::USAGE_OPTION, $this->usage);
    }
    
    /**
     * Reset daily usage
     */
    public function reset_daily_usage(): void {
        $this->usage['scans_today'] = 0;
        $this->usage['last_reset_daily'] = date('Y-m-d');
        update_option(self::USAGE_OPTION, $this->usage);
    }
    
    /**
     * Reset monthly usage
     */
    public function reset_monthly_usage(): void {
        $this->usage['ai_calls'] = 0;
        $this->usage['images_optimized'] = 0;
        $this->usage['seo_optimizations'] = 0;
        $this->usage['last_reset_monthly'] = date('Y-m');
        update_option(self::USAGE_OPTION, $this->usage);
    }
    
    /**
     * Get usage summary
     */
    public function get_usage_summary(): array {
        return [
            'ai_calls' => [
                'current' => $this->usage['ai_calls'] ?? 0,
                'limit' => $this->free_limits['ai_calls_per_month'],
                'period' => 'month'
            ],
            'workflows' => [
                'current' => $this->usage['workflows_active'] ?? 0,
                'limit' => $this->free_limits['workflows'],
                'period' => 'total'
            ],
            'images' => [
                'current' => $this->usage['images_optimized'] ?? 0,
                'limit' => $this->free_limits['image_optimization_per_month'],
                'period' => 'month'
            ],
            'seo' => [
                'current' => $this->usage['seo_optimizations'] ?? 0,
                'limit' => $this->free_limits['seo_optimization_per_month'],
                'period' => 'month'
            ],
            'scans' => [
                'current' => $this->usage['scans_today'] ?? 0,
                'limit' => $this->free_limits['scans_per_day'],
                'period' => 'day'
            ]
        ];
    }
    
    /**
     * Enqueue upsell modal scripts
     */
    public function enqueue_upsell_scripts($hook): void {
        if (strpos($hook, 'wpaig') === false) {
            return;
        }
        
        wp_add_inline_script('wpaig-dashboard', $this->get_upsell_modal_code(), 'after');
    }
    
    /**
     * Get upsell modal code
     */
    private function get_upsell_modal_code(): string {
        ob_start();
        ?>
        // Upsell Modal Function
        window.wpaigShowUpsell = function(feature, limit) {
            const modal = document.createElement('div');
            modal.className = 'wpaig-upsell-modal';
            modal.innerHTML = `
                <div class="wpaig-upsell-overlay"></div>
                <div class="wpaig-upsell-content">
                    <button class="wpaig-upsell-close" onclick="this.closest('.wpaig-upsell-modal').remove()">✕</button>
                    <div class="wpaig-upsell-header">
                        <h2>🚀 Upgrade to Premium</h2>
                        <p>You've reached the free tier limit</p>
                    </div>
                    <div class="wpaig-upsell-body">
                        <div class="wpaig-limit-info">
                            <strong>${feature}</strong> limit: ${limit}
                        </div>
                        <div class="wpaig-pricing-cards">
                            <div class="wpaig-pricing-card">
                                <h3>Monthly</h3>
                                <div class="wpaig-price">₹999<span>/month</span></div>
                                <ul>
                                    <li>✓ Unlimited AI calls</li>
                                    <li>✓ Unlimited workflows</li>
                                    <li>✓ Unlimited optimizations</li>
                                    <li>✓ Premium actions</li>
                                    <li>✓ Priority support</li>
                                </ul>
                                <a href="https://your-store.com/checkout?plan=monthly" class="wpaig-btn wpaig-btn-primary" target="_blank">
                                    Buy Monthly
                                </a>
                            </div>
                            <div class="wpaig-pricing-card wpaig-featured">
                                <div class="wpaig-badge">BEST VALUE</div>
                                <h3>Yearly</h3>
                                <div class="wpaig-price">₹9,999<span>/year</span></div>
                                <div class="wpaig-savings">Save ₹2,000!</div>
                                <ul>
                                    <li>✓ Everything in Monthly</li>
                                    <li>✓ 2 months FREE</li>
                                    <li>✓ Priority updates</li>
                                    <li>✓ Advanced features</li>
                                    <li>✓ Lifetime updates</li>
                                </ul>
                                <a href="https://your-store.com/checkout?plan=yearly" class="wpaig-btn wpaig-btn-primary" target="_blank">
                                    Buy Yearly
                                </a>
                            </div>
                            <div class="wpaig-pricing-card">
                                <h3>Lifetime</h3>
                                <div class="wpaig-price">₹24,999<span>/once</span></div>
                                <ul>
                                    <li>✓ Everything in Yearly</li>
                                    <li>✓ One-time payment</li>
                                    <li>✓ No recurring fees</li>
                                    <li>✓ Unlimited sites</li>
                                    <li>✓ VIP support</li>
                                </ul>
                                <a href="https://your-store.com/checkout?plan=lifetime" class="wpaig-btn wpaig-btn-primary" target="_blank">
                                    Buy Lifetime
                                </a>
                            </div>
                        </div>
                        <div class="wpaig-money-back">
                            <p>💰 30-Day Money-Back Guarantee • 🔒 Secure Payment</p>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Close on overlay click
            modal.querySelector('.wpaig-upsell-overlay').addEventListener('click', function() {
                modal.remove();
            });
        };
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX: Check limit
     */
    public function ajax_check_limit(): void {
        check_ajax_referer('wpaig_nonce', 'nonce');
        
        $feature = isset($_POST['feature']) ? sanitize_text_field($_POST['feature']) : '';
        
        if (empty($feature)) {
            wp_send_json_error(['message' => 'Feature required']);
            return;
        }
        
        $limit = $this->check_limit($feature);
        
        wp_send_json_success([
            'limit' => $limit,
            'is_premium' => $this->is_premium(),
            'usage_summary' => $this->get_usage_summary()
        ]);
    }
    
    /**
     * Activate license via EDD API
     */
    public function activate_license(string $license_key): array {
        // Demo mode: Accept any key starting with "WPAIG-" without API call
        if (strpos($license_key, 'WPAIG-') === 0) {
            $this->license = [
                'key' => $license_key,
                'status' => 'valid',
                'expires' => date('Y-m-d', strtotime('+1 year')),
                'checked' => current_time('mysql')
            ];
            
            update_option(self::LICENSE_OPTION, $this->license);
            update_option('wpaig_is_premium', true);
            
            return [
                'success' => true,
                'message' => 'Demo license activated successfully! All premium features unlocked.',
                'expires' => $this->license['expires']
            ];
        }
        
        // Real license activation via EDD API
        $api_params = [
            'edd_action' => 'activate_license',
            'license' => $license_key,
            'item_name' => urlencode(self::EDD_ITEM_NAME),
            'url' => home_url()
        ];
        
        $response = wp_remote_post(self::EDD_STORE_URL, [
            'timeout' => 15,
            'sslverify' => true,
            'body' => $api_params
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Could not connect to license server. Please check your internet connection or try again later.'
            ];
        }
        
        $license_data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($license_data['license']) && $license_data['license'] === 'valid') {
            $this->license = [
                'key' => $license_key,
                'status' => 'valid',
                'expires' => $license_data['expires'] ?? '',
                'checked' => current_time('mysql')
            ];
            
            update_option(self::LICENSE_OPTION, $this->license);
            update_option('wpaig_is_premium', true);
            
            return [
                'success' => true,
                'message' => 'License activated successfully!',
                'expires' => $this->license['expires']
            ];
        }
        
        return [
            'success' => false,
            'message' => isset($license_data['error']) ? $license_data['error'] : 'Invalid license key. Please check and try again.'
        ];
    }
    
    /**
     * Deactivate license
     */
    public function deactivate_license(): array {
        $license_key = $this->license['key'] ?? '';
        
        if (empty($license_key)) {
            return [
                'success' => false,
                'message' => 'No active license'
            ];
        }
        
        // If not a demo key, call EDD API to deactivate
        if (strpos($license_key, 'WPAIG-') !== 0) {
            $api_params = [
                'edd_action' => 'deactivate_license',
                'license' => $license_key,
                'item_name' => urlencode(self::EDD_ITEM_NAME),
                'url' => home_url()
            ];
            
            wp_remote_post(self::EDD_STORE_URL, [
                'timeout' => 15,
                'sslverify' => true,
                'body' => $api_params
            ]);
        }
        
        // Clear license data
        $this->license = [
            'key' => '',
            'status' => 'invalid',
            'expires' => '',
            'checked' => ''
        ];
        
        update_option(self::LICENSE_OPTION, $this->license);
        update_option('wpaig_is_premium', false);
        
        return [
            'success' => true,
            'message' => 'License deactivated successfully'
        ];
    }
    
    /**
     * AJAX: Activate license
     */
    public function ajax_activate_license(): void {
        check_ajax_referer('wpaig_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
        
        if (empty($license_key)) {
            wp_send_json_error(['message' => 'License key required']);
            return;
        }
        
        $result = $this->activate_license($license_key);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Deactivate license
     */
    public function ajax_deactivate_license(): void {
        check_ajax_referer('wpaig_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        $result = $this->deactivate_license();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get license info
     */
    public function get_license_info(): array {
        return $this->license;
    }
    
    /**
     * Get free limits
     */
    public function get_free_limits(): array {
        return $this->free_limits;
    }
}
