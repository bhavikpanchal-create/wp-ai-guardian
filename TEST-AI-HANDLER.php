<?php
/**
 * Test Script for AI Handler
 * 
 * USAGE: Add to functions.php temporarily or run via WP-CLI
 * 
 * @package WP_AI_Guardian
 */

// Uncomment to test (add to functions.php temporarily)
/*
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    
    // Test AI Handler
    $ai = new WP_AIGuardian_AI_Handler();
    
    echo '<div class="notice notice-info"><p><strong>AI Handler Test Results:</strong></p>';
    
    // Test 1: Simple prompt
    echo '<p><strong>Test 1: Simple AI Query</strong></p>';
    $response1 = $ai->generate('What are the top 3 WordPress security tips?');
    echo '<pre>' . esc_html(print_r($response1, true)) . '</pre>';
    
    // Test 2: Check usage stats
    echo '<p><strong>Test 2: Usage Statistics</strong></p>';
    $stats = $ai->get_usage_stats();
    echo '<pre>' . esc_html(print_r($stats, true)) . '</pre>';
    
    // Test 3: Cached response (same prompt)
    echo '<p><strong>Test 3: Cached Response (same prompt)</strong></p>';
    $start = microtime(true);
    $response2 = $ai->generate('What are the top 3 WordPress security tips?');
    $time = round((microtime(true) - $start) * 1000, 2);
    echo '<p>Response time: ' . $time . 'ms (should be instant if cached)</p>';
    
    echo '</div>';
});
*/

/**
 * WP-CLI Command for testing
 * 
 * USAGE: wp eval-file wp-content/plugins/wp-ai-guardian/TEST-AI-HANDLER.php
 */
if (defined('WP_CLI') && WP_CLI) {
    
    WP_CLI::line('=== AI Handler Test Suite ===');
    WP_CLI::line('');
    
    $ai = new WP_AIGuardian_AI_Handler();
    
    // Test 1: Basic generation
    WP_CLI::line('Test 1: Basic AI Generation');
    WP_CLI::line('----------------------------');
    $response = $ai->generate('How to optimize WordPress performance?', 5);
    WP_CLI::line('Response: ' . (is_array($response) ? json_encode($response) : $response));
    WP_CLI::line('');
    
    // Test 2: Check stats
    WP_CLI::line('Test 2: Usage Statistics');
    WP_CLI::line('------------------------');
    $stats = $ai->get_usage_stats();
    WP_CLI::line('Calls today: ' . $stats['calls_today']);
    WP_CLI::line('Is premium: ' . ($stats['is_premium'] ? 'Yes' : 'No'));
    WP_CLI::line('Last reset: ' . $stats['last_reset']);
    WP_CLI::line('Next reset: ' . $stats['next_reset']);
    WP_CLI::line('');
    
    // Test 3: Cache test
    WP_CLI::line('Test 3: Cache Performance');
    WP_CLI::line('-------------------------');
    
    $prompt = 'What is WordPress?';
    
    // First call (API)
    $start = microtime(true);
    $ai->generate($prompt);
    $time1 = round((microtime(true) - $start) * 1000, 2);
    WP_CLI::line('First call (API): ' . $time1 . 'ms');
    
    // Second call (Cache)
    $start = microtime(true);
    $ai->generate($prompt);
    $time2 = round((microtime(true) - $start) * 1000, 2);
    WP_CLI::line('Second call (Cache): ' . $time2 . 'ms');
    WP_CLI::line('Speed improvement: ' . round($time1 / $time2, 2) . 'x faster');
    WP_CLI::line('');
    
    // Test 4: Free tier limit
    WP_CLI::line('Test 4: Free Tier Limiting');
    WP_CLI::line('--------------------------');
    update_option('wpaig_is_premium', false);
    
    for ($i = 1; $i <= 5; $i++) {
        $response = $ai->generate("Test prompt $i", 3);
        $is_limited = strpos($response, 'Upgrade for more AI') !== false;
        WP_CLI::line("Call $i: " . ($is_limited ? 'BLOCKED (limit reached)' : 'SUCCESS'));
    }
    WP_CLI::line('');
    
    // Test 5: Premium unlimited
    WP_CLI::line('Test 5: Premium Mode (Unlimited)');
    WP_CLI::line('---------------------------------');
    update_option('wpaig_is_premium', true);
    $ai->reset_daily_counter(); // Reset for clean test
    
    for ($i = 1; $i <= 5; $i++) {
        $response = $ai->generate("Premium test $i", 3);
        $is_limited = strpos($response, 'Upgrade for more AI') !== false;
        WP_CLI::line("Call $i: " . ($is_limited ? 'ERROR' : 'SUCCESS'));
    }
    WP_CLI::line('');
    
    WP_CLI::success('All tests completed!');
}

/**
 * REST API Test (JavaScript Console)
 * 
 * USAGE: Open browser console on WordPress admin page and paste:
 */
?>

<!-- JavaScript REST API Test -->
<script>
/*
// Copy this to browser console to test REST API

async function testAIHandler() {
    console.log('=== AI Handler REST API Test ===\n');
    
    try {
        // Get nonce from page
        const nonce = wpaigData.restNonce;
        const restUrl = wpaigData.restUrl;
        
        console.log('1. Testing basic AI generation...');
        const response1 = await fetch(restUrl + 'wpaig/v1/ai-generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce
            },
            body: JSON.stringify({
                prompt: 'What is WordPress?',
                max_calls: 3
            })
        });
        
        const data1 = await response1.json();
        console.log('Response:', data1);
        console.log('Cached:', data1.cached);
        console.log('Calls remaining:', data1.calls_remaining);
        console.log('');
        
        console.log('2. Testing cached response (same prompt)...');
        const response2 = await fetch(restUrl + 'wpaig/v1/ai-generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce
            },
            body: JSON.stringify({
                prompt: 'What is WordPress?',
                max_calls: 3
            })
        });
        
        const data2 = await response2.json();
        console.log('Cached:', data2.cached, '(should be true)');
        console.log('');
        
        console.log('✓ Tests completed successfully!');
        
    } catch (error) {
        console.error('Test failed:', error);
    }
}

// Run the test
testAIHandler();
*/
</script>

<?php
/**
 * Quick Integration Example
 * 
 * Add this to your scan results to show AI suggestions:
 */
?>

<!-- Example Integration in Scan Results -->
<?php
/*
// In class-wpaig-core.php rest_scan() method, add:

public function rest_scan($request): array {
    // ... existing scan logic ...
    
    // Add AI suggestions to results
    if (get_option('wpaig_is_premium', false)) {
        $ai = new WP_AIGuardian_AI_Handler();
        
        foreach ($results as &$result) {
            $prompt = "How to fix WordPress issue: {$result['issue']}? Provide a brief solution.";
            $result['ai_suggestion'] = $ai->generate($prompt, 10);
        }
    }
    
    return [
        'success' => true,
        'results' => $results
    ];
}
*/
?>
