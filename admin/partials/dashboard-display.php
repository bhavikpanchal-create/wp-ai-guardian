<?php
/**
 * Dashboard Display - React Root Wrapper
 *
 * @package WP_AI_Guardian
 * @since 1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap wpaig-dashboard-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wpaig-react-root" id="wpaig-dashboard-root">
        <!-- React app will mount here -->
        <div class="wpaig-loading">
            <p><?php esc_html_e('Loading dashboard...', 'wp-ai-guardian'); ?></p>
        </div>
    </div>
</div>
