<?php
/**
 * Quick API Key Update Script
 * 
 * Load this file in browser: http://localhost/wp-ai-guardian/wp-content/plugins/wp-ai-guardian/UPDATE-API-KEY.php
 */

// Load WordPress
require_once(dirname(__DIR__, 3) . '/wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied. Please log in as admin.');
}

echo '<h1>WP AI Guardian - Update API Key</h1>';
echo '<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { color: blue; }
.box { background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; }
.btn { background: #2271b1; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; margin: 10px 5px; }
.btn:hover { background: #135e96; }
pre { background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
</style>';

// Handle form submission
if (isset($_POST['update_key'])) {
    $new_key = sanitize_text_field($_POST['api_key']);
    
    if (!empty($new_key)) {
        update_option('wpaig_hf_api_key', $new_key);
        echo '<div class="box"><p class="success">✓ API Key updated successfully!</p></div>';
        
        // Clear AI cache
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpaig_ai_%'");
        echo '<div class="box"><p class="success">✓ AI cache cleared</p></div>';
    } else {
        echo '<div class="box"><p class="error">✗ Please enter an API key</p></div>';
    }
}

// Show current status
echo '<div class="box">';
echo '<h2>Current Status</h2>';

$current_key = get_option('wpaig_hf_api_key', '');
if (!empty($current_key)) {
    echo '<p class="success">✓ API Key configured</p>';
    echo '<p>Current key: <code>' . substr($current_key, 0, 15) . '...</code> (' . strlen($current_key) . ' characters)</p>';
} else {
    echo '<p class="error">✗ No API Key configured</p>';
}

$is_premium = get_option('wpaig_is_premium', false);
if ($is_premium) {
    echo '<p class="success">✓ Premium enabled</p>';
} else {
    echo '<p class="error">✗ Premium disabled</p>';
}
echo '</div>';

// Update form
echo '<div class="box">';
echo '<h2>Update API Key</h2>';
echo '<p>Enter your new Perplexity Pro API key:</p>';
echo '<form method="post">';
echo '<input type="text" name="api_key" value="" placeholder="pplx-..." size="60" style="padding: 10px; font-size: 14px; width: 100%; max-width: 500px; margin: 10px 0; font-family: monospace;" /><br>';
echo '<button type="submit" name="update_key" class="btn">Update API Key</button>';
echo '</form>';

echo '<p class="info"><strong>Your new key:</strong> <code>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</code></p>';
echo '<p class="info">Copy and paste the key above into the form.</p>';
echo '</div>';

// Test AI after update
if (isset($_POST['update_key']) && !empty($_POST['api_key'])) {
    echo '<div class="box">';
    echo '<h2>Testing AI Connection...</h2>';
    
    require_once WPAIG_PLUGIN_DIR . 'includes/class-ai-handler.php';
    $ai_handler = new WP_AIGuardian_AI_Handler();
    
    $test_prompt = "Say 'Hello, WordPress!' in one sentence.";
    $response = $ai_handler->generate($test_prompt, 1);
    
    echo '<pre>';
    print_r($response);
    echo '</pre>';
    
    if (is_string($response) && strpos(strtolower($response), 'hello') !== false) {
        echo '<p class="success">✓ AI API is working perfectly!</p>';
        echo '<p class="info">🎉 You can now use AI-powered features in Performance Optimizer, Conflict Detector, and more!</p>';
    } else if (is_array($response) && isset($response['suggestions'])) {
        echo '<p class="error">✗ Still getting fallback response</p>';
        echo '<p class="info">Possible issues:</p>';
        echo '<ul>';
        echo '<li>API key may be invalid</li>';
        echo '<li>Check if key is active in Perplexity dashboard</li>';
        echo '<li>Verify you have credits/payment method added</li>';
        echo '</ul>';
    }
    echo '</div>';
}

echo '<hr>';
echo '<p><a href="' . admin_url('admin.php?page=wp-ai-guardian') . '" class="btn">← Back to Dashboard</a></p>';
echo '<p><a href="http://localhost/wp-ai-guardian/wp-content/plugins/wp-ai-guardian/CHECK-PREMIUM.php" class="btn">Check Status</a></p>';
