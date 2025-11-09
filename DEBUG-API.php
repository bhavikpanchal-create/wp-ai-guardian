<?php
/**
 * API Debug Tool - Direct API Call Test
 * 
 * Load: http://localhost/wp-ai-guardian/wp-content/plugins/wp-ai-guardian/DEBUG-API.php
 */

// Load WordPress
require_once(dirname(__DIR__, 3) . '/wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied. Please log in as admin.');
}

echo '<h1>WP AI Guardian - Direct API Test</h1>';
echo '<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; max-width: 1000px; margin: 0 auto; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { color: blue; }
.warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto; max-height: 400px; border: 1px solid #ddd; }
h2 { border-bottom: 2px solid #2271b1; padding-bottom: 10px; margin-top: 30px; }
.step { background: #fff; padding: 15px; border-left: 4px solid #2271b1; margin: 15px 0; }
</style>';

// Get saved API key
$api_key = get_option('wpaig_hf_api_key', '');

if (empty($api_key)) {
    echo '<div class="warning">⚠️ <strong>No API key configured!</strong> Please add it in Settings first.</div>';
    echo '<p><a href="' . admin_url('admin.php?page=wp-ai-guardian&tab=settings') . '">→ Go to Settings</a></p>';
    exit;
}

echo '<div class="step">';
echo '<h2>Step 1: API Key Verification</h2>';
echo '<p class="success">✓ API Key found</p>';
echo '<p>Key: <code>' . substr($api_key, 0, 20) . '...</code> (' . strlen($api_key) . ' characters)</p>';
echo '</div>';

echo '<div class="step">';
echo '<h2>Step 2: Direct cURL Test</h2>';

// Detect provider
$provider = 'unknown';
if (strpos($api_key, 'gsk_') === 0) {
    $provider = 'groq';
    $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
    $model = 'llama-3.1-8b-instant'; // Updated model
} elseif (strpos($api_key, 'pplx-') === 0) {
    $provider = 'perplexity';
    $endpoint = 'https://api.perplexity.ai/chat/completions';
    $model = 'llama-3.1-sonar-small-128k-online';
} else {
    $provider = 'unknown';
    $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
    $model = 'llama-3.1-8b-instant'; // Updated model
}

echo '<p style="background: #e7f3ff; padding: 10px; border-radius: 5px; border-left: 4px solid #2271b1;">';
echo '<strong>🤖 Detected Provider:</strong> <code>' . strtoupper($provider) . '</code>';
echo '</p>';

echo '<p>Making direct API call to ' . ucfirst($provider) . '...</p>';

// Prepare request
$prompt = "Say 'Hello, WordPress!' in one sentence.";

$body = [
    'model' => $model,
    'messages' => [
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ],
    'max_tokens' => 800, // Increased for complete responses
    'temperature' => 0.7
];

$json_body = json_encode($body);

echo '<h3>Request Details:</h3>';
echo '<pre>';
echo "Provider: " . strtoupper($provider) . "\n";
echo "Endpoint: $endpoint\n";
echo "Model: $model\n";
echo "Prompt: $prompt\n";
echo "Max tokens: 150\n";
echo "API Key: " . substr($api_key, 0, 15) . "...\n";
echo '</pre>';

// Initialize cURL
$ch = curl_init($endpoint);

if ($ch === false) {
    echo '<p class="error">✗ Failed to initialize cURL</p>';
    exit;
}

// Detect localhost
$is_local = (
    in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) ||
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false
);

if ($is_local) {
    echo '<p class="info">ℹ️ Localhost detected - SSL verification disabled for development</p>';
}

// Set cURL options
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $json_body,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_body)
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => !$is_local,  // Disable on localhost
    CURLOPT_SSL_VERIFYHOST => $is_local ? 0 : 2,  // Disable on localhost
    CURLOPT_VERBOSE => false
]);

echo '<p>Executing request...</p>';

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
$curl_info = curl_getinfo($ch);

curl_close($ch);

echo '</div>';

// Display results
echo '<div class="step">';
echo '<h2>Step 3: Response Analysis</h2>';

if ($response === false || !empty($curl_error)) {
    echo '<p class="error">✗ cURL Error: ' . htmlspecialchars($curl_error) . '</p>';
    echo '<h3>cURL Info:</h3>';
    echo '<pre>' . print_r($curl_info, true) . '</pre>';
    exit;
}

echo '<p><strong>HTTP Status Code:</strong> <code>' . $http_code . '</code></p>';

if ($http_code === 200) {
    echo '<p class="success">✓ HTTP 200 OK - Request successful!</p>';
} else {
    echo '<p class="error">✗ HTTP ' . $http_code . ' - Request failed</p>';
}

echo '<h3>Raw Response:</h3>';
echo '<pre>' . htmlspecialchars(substr($response, 0, 2000)) . '</pre>';

// Try to parse JSON
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo '<p class="error">✗ JSON Parse Error: ' . json_last_error_msg() . '</p>';
} else {
    echo '<p class="success">✓ Valid JSON response</p>';
    
    echo '<h3>Parsed Response:</h3>';
    echo '<pre>' . print_r($data, true) . '</pre>';
    
    // Check for error in response
    if (isset($data['error'])) {
        echo '<div class="warning">';
        echo '<h3>❌ API Error Detected:</h3>';
        echo '<p><strong>Type:</strong> ' . ($data['error']['type'] ?? 'Unknown') . '</p>';
        echo '<p><strong>Message:</strong> ' . ($data['error']['message'] ?? 'Unknown') . '</p>';
        echo '</div>';
        
        // Provide specific guidance
        if (strpos($data['error']['message'] ?? '', 'payment') !== false || 
            strpos($data['error']['message'] ?? '', 'credit') !== false ||
            strpos($data['error']['message'] ?? '', 'billing') !== false) {
            echo '<div class="warning">';
            echo '<h3>💳 Payment/Billing Issue</h3>';
            echo '<p><strong>Action Required:</strong></p>';
            echo '<ol>';
            echo '<li>Go to <a href="https://www.perplexity.ai/settings/billing" target="_blank">Perplexity Billing Settings</a></li>';
            echo '<li>Add a payment method (credit card)</li>';
            echo '<li>Add credits to your account</li>';
            echo '<li>Wait a few minutes for activation</li>';
            echo '<li>Return here and refresh this page</li>';
            echo '</ol>';
            echo '</div>';
        }
        
        if (strpos($data['error']['message'] ?? '', 'api_key') !== false ||
            strpos($data['error']['message'] ?? '', 'authentication') !== false ||
            strpos($data['error']['message'] ?? '', 'unauthorized') !== false) {
            echo '<div class="warning">';
            echo '<h3>🔑 API Key Issue</h3>';
            echo '<p><strong>Action Required:</strong></p>';
            echo '<ol>';
            echo '<li>Go to <a href="https://www.perplexity.ai/settings/api" target="_blank">Perplexity API Settings</a></li>';
            echo '<li>Verify your API key is active</li>';
            echo '<li>Copy the key again</li>';
            echo '<li>Update it in <a href="' . admin_url('admin.php?page=wp-ai-guardian') . '">WP AI Guardian Settings</a></li>';
            echo '</ol>';
            echo '</div>';
        }
    }
    
    // Check for successful response
    if (isset($data['choices'][0]['message']['content'])) {
        echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 5px; margin: 20px 0;">';
        echo '<h3 style="color: #155724; margin-top: 0;">🎉 SUCCESS! AI API is Working!</h3>';
        echo '<p><strong>AI Response:</strong></p>';
        echo '<p style="font-size: 16px; font-style: italic;">"' . htmlspecialchars($data['choices'][0]['message']['content']) . '"</p>';
        echo '<p class="success">✓ Your Perplexity API is fully functional!</p>';
        echo '<p>You can now use AI-powered features in WP AI Guardian.</p>';
        echo '</div>';
    }
}

echo '</div>';

// Final recommendations
echo '<div class="step">';
echo '<h2>Next Steps</h2>';

if ($http_code === 200 && isset($data['choices'][0]['message']['content'])) {
    echo '<p class="success">✓ Everything is working! Go test the Performance Optimizer.</p>';
    echo '<p><a href="' . admin_url('admin.php?page=wp-ai-guardian') . '" style="background: #2271b1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 10px;">→ Go to Dashboard</a></p>';
} else {
    echo '<p class="error">The API call failed. Follow the recommendations above.</p>';
    echo '<h3>Common Solutions:</h3>';
    echo '<ul>';
    echo '<li><strong>HTTP 401:</strong> Invalid API key - check and update it</li>';
    echo '<li><strong>HTTP 402:</strong> Payment required - add billing to Perplexity</li>';
    echo '<li><strong>HTTP 429:</strong> Rate limit - wait a few minutes</li>';
    echo '<li><strong>HTTP 500:</strong> Perplexity server issue - try again later</li>';
    echo '</ul>';
}

echo '</div>';

echo '<hr style="margin: 40px 0;">';
echo '<p style="text-align: center;"><a href="' . admin_url('admin.php?page=wp-ai-guardian') . '">← Back to Dashboard</a> | ';
echo '<a href="http://localhost/wp-ai-guardian/wp-content/plugins/wp-ai-guardian/CHECK-PREMIUM.php">Check Premium Status</a></p>';
