<?php
/**
 * Plugin Name: WP AI Guardian
 * Description: AI-powered troubleshooter: Detects conflicts, optimizes speed/SEO, auto-fixes in premium. Lightweight & secure.
 * Version: 1.0
 * Author: Your Name
 * License: GPL v2
 * Text Domain: wp-ai-guardian
 * Requires PHP: 8.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPAIG_VERSION', '1.0');
define('WPAIG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPAIG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPAIG_PLUGIN_FILE', __FILE__);

// Load core class only - it handles all dependencies
require_once WPAIG_PLUGIN_DIR . 'includes/class-wpaig-core.php';

/**
 * Initialize plugin on plugins_loaded hook
 * Core class loads all dependencies and initializes all modules
 */
function wpaig_init() {
    try {
        $GLOBALS['wpaig_core'] = new WPAIG_Core();
        $GLOBALS['wpaig_core']->init();
    } catch (Exception $e) {
        if (WP_DEBUG) {
            error_log('WPAIG Initialization Error: ' . $e->getMessage());
        }
        
        // Show admin notice on error
        add_action('admin_notices', function() use ($e) {
            if (current_user_can('manage_options')) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>WP AI Guardian:</strong> Plugin initialization failed. ';
                if (WP_DEBUG) {
                    echo 'Error: ' . esc_html($e->getMessage());
                } else {
                    echo 'Please check error logs or enable WP_DEBUG for details.';
                }
                echo '</p></div>';
            }
        });
    }
}
add_action('plugins_loaded', 'wpaig_init', 10);

// Activation hook
register_activation_hook(__FILE__, ['WPAIG_Core', 'activate']);

// Deactivation hook
register_deactivation_hook(__FILE__, ['WPAIG_Core', 'deactivate']);
