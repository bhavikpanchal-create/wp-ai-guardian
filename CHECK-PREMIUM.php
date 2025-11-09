<?php
/**
 * Quick Premium Status Check
 * 
 * Load this file in browser: http://localhost/wp-ai-guardian/wp-content/plugins/wp-ai-guardian/CHECK-PREMIUM.php
 */

// Load WordPress
require_once(dirname(__DIR__, 3) . '/wp-load.php');


if (!current_user_can('manage_options')) {
    die('Access denied. Please log in as admin.');
}

echo '<h1>WP AI Guardian - Premium Status Check</h1>';
echo '<style>body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }</style>';

echo '<h2>Premium Status</h2>';
$is_premium = get_option('wpaig_is_premium', false);
if ($is_premium) {
    echo '<p class="success">✓ Premium is ENABLED</p>';
} else {
    echo '<p class="error">✗ Premium is DISABLED</p>';
    echo '<p class="info">To enable: Check "Enable Premium Features" in Settings and click "Save Settings"</p>';
}

echo '<h2>API Key Status</h2>';
$api_key = get_option('wpaig_hf_api_key', '');
if (!empty($api_key)) {
    echo '<p class="success">✓ API Key is configured (' . strlen($api_key) . ' characters)</p>';
    echo '<p class="info">Key starts with: ' . substr($api_key, 0, 15) . '...</p>';
    
    // Detect provider
    $provider = 'Unknown';
    if (strpos($api_key, 'gsk_') === 0) {
        $provider = '⚡ Groq (FREE & Fast)';
    } elseif (strpos($api_key, 'pplx-') === 0) {
        $provider = '🔮 Perplexity';
    } elseif (strpos($api_key, 'hf_') === 0) {
        $provider = '🤗 Hugging Face';
    } elseif (strpos($api_key, 'sk-') === 0) {
        $provider = '🤖 OpenAI';
    }
    echo '<p class="success">✓ Provider: <strong>' . $provider . '</strong></p>';
} else {
    echo '<p class="error">✗ No API Key configured</p>';
    echo '<p class="info">Add your AI API Key in Settings (Groq/Perplexity/HuggingFace)</p>';
}

echo '<h2>AI Handler Test</h2>';
require_once WPAIG_PLUGIN_DIR . 'includes/class-ai-handler.php';
$ai_handler = new WP_AIGuardian_AI_Handler();

// Test AI call
echo '<p>Testing AI API call...</p>';
$test_prompt = "Say 'Hello, WordPress!' in one sentence.";

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = $ai_handler->generate($test_prompt, 1);

echo '<h3>Response:</h3>';
echo '<pre>';
print_r($response);
echo '</pre>';

if (is_string($response) && strpos(strtolower($response), 'hello') !== false) {
    echo '<p class="success">✓ AI API is working perfectly!</p>';
} else if (is_array($response) && isset($response['suggestions'])) {
    echo '<p class="error">✗ AI API returned fallback response</p>';
    echo '<p class="info"><strong>This means the API call failed.</strong></p>';
    
    // Check for WordPress errors in logs
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file)) {
        $logs = file_get_contents($log_file);
        $recent_logs = array_slice(array_filter(explode("\n", $logs)), -20);
        $ai_logs = array_filter($recent_logs, function($line) {
            return strpos($line, 'WP AI Guardian') !== false;
        });
        
        if (!empty($ai_logs)) {
            echo '<h3>Recent Error Logs:</h3>';
            echo '<pre style="background: #fff3cd; border: 1px solid #856404; padding: 10px; max-height: 300px; overflow: auto;">';
            echo implode("\n", $ai_logs);
            echo '</pre>';
        }
    }
    
    echo '<p class="info"><strong>Possible reasons:</strong></p>';
    echo '<ul>';
    echo '<li><strong>No payment method:</strong> Perplexity requires a payment method even for API usage</li>';
    echo '<li><strong>Invalid API key:</strong> Check if the key is correct in Perplexity dashboard</li>';
    echo '<li><strong>Billing issue:</strong> Verify your Perplexity account has credits</li>';
    echo '<li><strong>API endpoint:</strong> Perplexity may have changed their API</li>';
    echo '</ul>';
    
    echo '<p class="info"><strong>Next steps:</strong></p>';
    echo '<ol>';
    echo '<li>Visit <a href="https://www.perplexity.ai/settings/api" target="_blank">Perplexity API Settings</a></li>';
    echo '<li>Verify your API key is active</li>';
    echo '<li>Add a payment method if not already added</li>';
    echo '<li>Check if you have available credits</li>';
    echo '</ol>';
} else {
    echo '<p class="error">✗ Unexpected response format</p>';
}

echo '<h2>Database Check</h2>';
global $wpdb;
$logs_table = $wpdb->prefix . 'ai_guardian_logs';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$logs_table'") === $logs_table;

if ($table_exists) {
    echo '<p class="success">✓ Logs table exists</p>';
    $log_count = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table");
    echo '<p>Total logs: ' . $log_count . '</p>';
} else {
    echo '<p class="error">✗ Logs table missing</p>';
    echo '<p class="info">Try deactivating and reactivating the plugin</p>';
}

echo '<h2>Quick Fix Actions</h2>';
echo '<p><strong>If Premium is disabled:</strong></p>';
echo '<pre>update_option(\'wpaig_is_premium\', true);</pre>';
echo '<p><strong>Or via WP-CLI:</strong></p>';
echo '<pre>wp option update wpaig_is_premium 1</pre>';

echo '<hr>';
echo '<p><a href="' . admin_url('admin.php?page=wp-ai-guardian') . '">← Back to WP AI Guardian Dashboard</a></p>';
