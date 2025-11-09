<?php
/**
 * Test Script for Conflict Detector
 * 
 * @package WP_AI_Guardian
 */

/**
 * BROWSER CONSOLE TEST
 * 
 * 1. Go to WP AI Guardian dashboard
 * 2. Click on "Conflicts" tab
 * 3. Open browser console (F12)
 * 4. Paste this code:
 */
?>
<script>
/*

// === CONFLICT DETECTOR TEST SUITE ===

console.log('=== WP AI Guardian - Conflict Detector Test ===\n');

// Test 1: Scan for conflicts
async function test1_ScanConflicts() {
    console.log('Test 1: Scanning for conflicts...');
    
    try {
        const response = await fetch(wpaigData.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'wpaig_scan_conflicts',
                nonce: wpaigData.nonce
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('✓ Scan successful!');
            console.log('  Tested plugins:', data.data.tested_count);
            console.log('  Total plugins:', data.data.total_plugins);
            console.log('  Conflicts found:', data.data.conflicts.length);
            console.log('  Premium status:', data.data.is_premium);
            
            if (data.data.conflicts.length > 0) {
                console.log('\n  Conflicts:');
                data.data.conflicts.forEach((c, i) => {
                    console.log(`  ${i+1}. ${c.plugin}`);
                    console.log(`     Issue: ${c.issue}`);
                    console.log(`     Severity: ${c.severity}`);
                    if (c.ai_fix) {
                        console.log(`     AI Fix: ${c.ai_fix.substring(0, 80)}...`);
                    }
                });
            }
            
            return data.data;
        } else {
            console.error('✗ Scan failed:', data.data?.message);
            return null;
        }
    } catch (error) {
        console.error('✗ Request error:', error);
        return null;
    }
}

// Test 2: Try to deactivate plugin (Premium only)
async function test2_DeactivatePlugin(pluginFile) {
    console.log('\nTest 2: Attempting plugin deactivation...');
    console.log('  Plugin:', pluginFile);
    
    try {
        const response = await fetch(wpaigData.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'wpaig_deactivate_plugin',
                nonce: wpaigData.nonce,
                plugin: pluginFile
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('✓ Deactivation successful!');
            console.log('  Message:', data.data.message);
            return true;
        } else {
            console.log('✗ Deactivation failed:', data.data.message);
            return false;
        }
    } catch (error) {
        console.error('✗ Request error:', error);
        return false;
    }
}

// Test 3: Full workflow test
async function test3_FullWorkflow() {
    console.log('\nTest 3: Full workflow test...');
    
    // Step 1: Scan
    console.log('Step 1: Running scan...');
    const scanResults = await test1_ScanConflicts();
    
    if (!scanResults) {
        console.error('✗ Workflow aborted: Scan failed');
        return;
    }
    
    // Step 2: Check if conflicts found
    if (scanResults.conflicts.length === 0) {
        console.log('✓ No conflicts found - workflow complete');
        return;
    }
    
    // Step 3: Try deactivation if premium
    if (scanResults.is_premium) {
        console.log('\nStep 2: Testing deactivation (Premium user)...');
        console.log('  Note: This is a DRY RUN - not actually deactivating');
        console.log('  First conflicting plugin:', scanResults.conflicts[0].plugin);
        console.log('  Would deactivate:', scanResults.conflicts[0].file);
        
        // Uncomment to actually test deactivation:
        // await test2_DeactivatePlugin(scanResults.conflicts[0].file);
    } else {
        console.log('\nStep 2: Skipped (requires Premium)');
    }
    
    console.log('\n✓ Workflow test complete!');
}

// Run all tests
async function runAllTests() {
    console.log('\n' + '='.repeat(60));
    console.log('RUNNING ALL TESTS');
    console.log('='.repeat(60) + '\n');
    
    await test3_FullWorkflow();
    
    console.log('\n' + '='.repeat(60));
    console.log('TESTS COMPLETE');
    console.log('='.repeat(60));
}

// Auto-run tests
runAllTests();

*/
</script>

<?php
/**
 * PHP CLI TEST
 * 
 * Run via WP-CLI:
 * wp eval-file wp-content/plugins/wp-ai-guardian/TEST-CONFLICTS.php
 */

if (defined('WP_CLI') && WP_CLI) {
    
    WP_CLI::line('=== WP AI Guardian - Conflict Detector PHP Test ===');
    WP_CLI::line('');
    
    // Load detector
    require_once WPAIG_PLUGIN_DIR . 'includes/class-conflict-detector.php';
    
    // Test 1: Basic scan
    WP_CLI::line('Test 1: Basic Conflict Scan');
    WP_CLI::line('----------------------------');
    
    $detector = new WP_AIGuardian_Conflicts();
    $results = $detector->scan();
    
    WP_CLI::line('Tested: ' . $results['tested_count'] . ' plugins');
    WP_CLI::line('Total: ' . $results['total_plugins'] . ' plugins');
    WP_CLI::line('Conflicts: ' . count($results['conflicts']));
    WP_CLI::line('Premium: ' . ($results['is_premium'] ? 'Yes' : 'No'));
    WP_CLI::line('');
    
    // Show conflicts
    if (count($results['conflicts']) > 0) {
        WP_CLI::line('Conflicts Found:');
        foreach ($results['conflicts'] as $i => $conflict) {
            WP_CLI::line(($i + 1) . ". {$conflict['plugin']}");
            WP_CLI::line("   Issue: {$conflict['issue']}");
            WP_CLI::line("   Severity: {$conflict['severity']}");
            WP_CLI::line("   Type: {$conflict['type']}");
            if (isset($conflict['ai_fix'])) {
                WP_CLI::line("   AI Fix: " . substr($conflict['ai_fix'], 0, 80) . '...');
            }
            WP_CLI::line('');
        }
    } else {
        WP_CLI::success('No conflicts detected!');
    }
    
    // Test 2: Get plugin details
    WP_CLI::line('Test 2: Plugin Details Lookup');
    WP_CLI::line('------------------------------');
    
    $active_plugins = get_option('active_plugins', []);
    if (!empty($active_plugins)) {
        $test_plugin = $active_plugins[0];
        $details = $detector->get_plugin_details($test_plugin);
        
        WP_CLI::line("Plugin: {$details['name']}");
        WP_CLI::line("Version: {$details['version']}");
        WP_CLI::line("Author: {$details['author']}");
        WP_CLI::line('');
    }
    
    // Test 3: Premium features check
    WP_CLI::line('Test 3: Premium Features');
    WP_CLI::line('------------------------');
    
    $is_premium = get_option('wpaig_is_premium', false);
    WP_CLI::line('Premium status: ' . ($is_premium ? 'ENABLED' : 'DISABLED'));
    
    if ($is_premium) {
        WP_CLI::line('Available features:');
        WP_CLI::line('  ✓ AI diagnostics');
        WP_CLI::line('  ✓ Auto-deactivation');
        WP_CLI::line('  ✓ Unlimited scans');
    } else {
        WP_CLI::line('To enable premium features:');
        WP_CLI::line('  wp option update wpaig_is_premium 1');
    }
    WP_CLI::line('');
    
    // Test 4: Performance test
    WP_CLI::line('Test 4: Performance Test');
    WP_CLI::line('------------------------');
    
    $start = microtime(true);
    $results = $detector->scan();
    $time = round((microtime(true) - $start) * 1000, 2);
    
    WP_CLI::line("Scan time: {$time}ms");
    WP_CLI::line("Plugins tested: {$results['tested_count']}");
    WP_CLI::line("Time per plugin: " . round($time / $results['tested_count'], 2) . "ms");
    WP_CLI::line('');
    
    WP_CLI::success('All tests completed!');
}

/**
 * WORDPRESS ADMIN TEST
 * 
 * Add to functions.php temporarily:
 */
?>

<!-- Add to functions.php for admin notice test -->
<?php
/*
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    
    // Only show on our plugin page
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'wp-ai-guardian') === false) return;
    
    // Load detector
    require_once WPAIG_PLUGIN_DIR . 'includes/class-conflict-detector.php';
    
    // Run scan
    $detector = new WP_AIGuardian_Conflicts();
    $results = $detector->scan();
    
    // Show results
    $conflicts_count = count($results['conflicts']);
    $class = $conflicts_count > 0 ? 'notice-warning' : 'notice-success';
    
    echo '<div class="notice ' . $class . '">';
    echo '<p><strong>Conflict Scan Results:</strong></p>';
    echo '<p>Tested: ' . $results['tested_count'] . ' plugins</p>';
    echo '<p>Conflicts: ' . $conflicts_count . '</p>';
    
    if ($conflicts_count > 0) {
        echo '<ul>';
        foreach ($results['conflicts'] as $conflict) {
            echo '<li>' . esc_html($conflict['plugin']) . ': ' . 
                 esc_html($conflict['issue']) . ' (' . 
                 esc_html($conflict['severity']) . ')</li>';
        }
        echo '</ul>';
    }
    
    echo '</div>';
});
*/
?>

<!-- QUICK FRONTEND TEST -->
<script>
/*
// Quick test on any WordPress admin page with browser console

// Check if on WP AI Guardian page
if (typeof wpaigData !== 'undefined') {
    console.log('✓ WP AI Guardian data available');
    console.log('  AJAX URL:', wpaigData.ajaxUrl);
    console.log('  Nonce:', wpaigData.nonce ? 'Present' : 'Missing');
    
    // Quick conflict scan
    fetch(wpaigData.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'wpaig_scan_conflicts',
            nonce: wpaigData.nonce
        })
    })
    .then(r => r.json())
    .then(d => {
        console.log('\nConflict Scan Result:');
        console.log('  Success:', d.success);
        if (d.success) {
            console.log('  Conflicts:', d.data.conflicts.length);
            console.log('  Tested:', d.data.tested_count);
        }
    });
} else {
    console.log('✗ Not on WP AI Guardian page');
    console.log('  Navigate to: WP Admin > WP AI Guardian');
}
*/
</script>
