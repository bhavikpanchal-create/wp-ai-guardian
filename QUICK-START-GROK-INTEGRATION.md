# 🚀 QUICK START: Grok Integration

## ✅ What's Done

**7 new classes created:**
1. ✅ `class-grok-ai-handler.php` - Grok API communication
2. ✅ `class-security-scanner.php` - Security analysis
3. ✅ `class-performance-analyzer.php` - Performance analysis
4. ✅ `class-plugin-health.php` - Plugin conflict detection
5. ✅ `class-spam-filter.php` - AI spam filter
6. ✅ `class-database-manager.php` - Database schema management
7. ✅ `class-grok-migration.php` - Migration from old providers

---

## 🔧 Integration Steps (5 Minutes)

### **Step 1: Update Core Class** (2 min)

Open: `includes/class-wpaig-core.php`

**Add to `load_dependencies()` method (around line 58):**

```php
private function load_dependencies(): void {
    $classes = [
        'class-ai-handler.php',
        'class-performance.php',
        'class-conflict-detector.php',
        'class-seo-ai.php',
        'class-automator.php',
        'class-freemium.php',
        
        // NEW: Grok AI Features
        'class-grok-ai-handler.php',
        'class-security-scanner.php',
        'class-performance-analyzer.php',
        'class-plugin-health.php',
        'class-spam-filter.php',
        'class-database-manager.php',
        'class-grok-migration.php'
    ];
    
    foreach ($classes as $class) {
        $file = WPAIG_PLUGIN_DIR . 'includes/' . $class;
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
```

**Add class properties (around line 19):**

```php
class WPAIG_Core {
    
    // Existing properties
    private $ai_handler;
    private $seo_ai;
    private $automator;
    private $freemium;
    private $performance;
    private $conflict_detector;
    
    // NEW: Add these
    private $grok_handler;
    private $security_scanner;
    private $performance_analyzer;
    private $plugin_health;
    private $spam_filter;
    private $grok_migration;
```

**Add to `init_modules()` method (around line 78):**

```php
private function init_modules(): void {
    try {
        // Existing modules
        $this->freemium = new WP_AIGuardian_Freemium();
        $this->ai_handler = new WP_AIGuardian_AI_Handler();
        $this->performance = new WP_AIGuardian_Performance();
        $this->conflict_detector = new WP_AIGuardian_Conflicts();
        $this->seo_ai = new WP_AIGuardian_SEO_AI();
        $this->automator = new WP_AIGuardian_Automator();
        
        // NEW: Initialize Grok modules
        $this->grok_handler = new WP_AIGuardian_Grok_AI_Handler();
        $this->security_scanner = new WP_AIGuardian_Security_Scanner();
        $this->performance_analyzer = new WP_AIGuardian_Performance_Analyzer();
        $this->plugin_health = new WP_AIGuardian_Plugin_Health();
        $this->spam_filter = new WP_AIGuardian_Spam_Filter();
        $this->grok_migration = new WP_AIGuardian_Grok_Migration();
        
        // NEW: Install/update database tables
        if (WP_AIGuardian_Database_Manager::needs_update()) {
            WP_AIGuardian_Database_Manager::install_tables();
        }
        
    } catch (Exception $e) {
        if (WP_DEBUG) {
            error_log('WPAIG Module Init Error: ' . $e->getMessage());
        }
    }
}
```

---

### **Step 2: Test Database Installation** (1 min)

**WordPress Admin → WP AI Guardian**

The database tables will be created automatically on first load.

**Verify tables exist:**
```sql
-- In phpMyAdmin or database tool
SHOW TABLES LIKE 'wp_ai_guardian_%';

-- Should see:
-- wp_ai_guardian_scans
-- wp_ai_guardian_grok_usage
-- wp_ai_guardian_spam_training
```

---

### **Step 3: Get Grok API Key** (1 min)

1. Go to: https://console.x.ai
2. Sign up / Log in
3. Navigate to API Keys section
4. Create new API key
5. Copy key (starts with `xai-`)

---

### **Step 4: Configure Grok API** (1 min)

**WordPress Admin → Settings → WP AI Guardian**

```php
// Or set directly via PHP:
update_option('wpaig_grok_api_key', 'xai-YOUR-KEY-HERE');
```

**Test connection:**
```php
// In WordPress admin or debug file
$migration = new WP_AIGuardian_Grok_Migration();
$result = $migration->test_grok_connection();

if ($result['success']) {
    echo "✅ Connected to Grok API!";
} else {
    echo "❌ Connection failed: " . $result['message'];
}
```

---

### **Step 5: Test Features** (<1 min)

**Security Scan:**
```php
$scanner = new WP_AIGuardian_Security_Scanner();
$result = $scanner->run_scan();
echo "Security Score: " . $result['results']['score'];
```

**Performance Analysis:**
```php
$analyzer = new WP_AIGuardian_Performance_Analyzer();
$result = $analyzer->run_analysis();
echo "Performance Score: " . $result['results']['score'];
```

**Spam Check:**
```php
$filter = new WP_AIGuardian_Spam_Filter();
$result = $filter->classify_comment(
    "Great post! http://spam.com",
    ['author' => 'Test', 'email' => 'test@test.com']
);
echo "Spam: " . ($result['is_spam'] ? 'Yes' : 'No');
```

---

## 🎨 What's Next: Phase 3

### **PROMPT 8: Enhanced React Dashboard**

You'll need to update `assets/js/dashboard.js` to add:
- Security score card component
- Performance score card component
- Recent issues widget
- API status indicator
- Quick action buttons

### **PROMPT 9: Admin Settings Page**

Update settings form in `class-wpaig-core.php` to add:
- Grok API key field
- Migration wizard
- Test connection button
- Usage statistics display

### **PROMPT 10: AJAX Handlers**

Add to `class-wpaig-core.php`:
```php
// In register_ajax_handlers() add:
'test_grok_connection',
'run_security_scan',
'run_performance_analysis',
'check_plugin_health',
'bulk_check_spam'
```

---

## 🐛 Troubleshooting

### **Tables Not Created?**

```php
// Force table creation
WP_AIGuardian_Database_Manager::install_tables();

// Check for errors
global $wpdb;
echo $wpdb->last_error;
```

### **Grok API Connection Failed?**

1. Verify API key starts with `xai-`
2. Check internet connection
3. Enable WP_DEBUG to see error details
4. Check if firewall blocks api.x.ai

### **Classes Not Loading?**

```php
// Check file exists
$file = WPAIG_PLUGIN_DIR . 'includes/class-grok-ai-handler.php';
if (file_exists($file)) {
    echo "✅ File exists";
} else {
    echo "❌ File missing: " . $file;
}
```

---

## 📊 Quick Reference

### **Available Classes:**

```php
// Global access via core
global $wpaig_core;

// Grok API
$wpaig_core->grok_handler->test_connection();
$wpaig_core->grok_handler->analyze_security($data);

// Security Scanner
$wpaig_core->security_scanner->run_scan();
$wpaig_core->security_scanner->get_latest_scan();

// Performance Analyzer
$wpaig_core->performance_analyzer->run_analysis();
$wpaig_core->performance_analyzer->get_latest_analysis();

// Plugin Health
$wpaig_core->plugin_health->run_health_check();
$wpaig_core->plugin_health->detect_conflicts();

// Spam Filter
$wpaig_core->spam_filter->classify_comment($content, $meta);
$wpaig_core->spam_filter->bulk_check_pending(50);

// Migration
$wpaig_core->grok_migration->test_grok_connection();
$wpaig_core->grok_migration->get_connection_status();
```

### **Database Manager (Static):**

```php
WP_AIGuardian_Database_Manager::install_tables();
WP_AIGuardian_Database_Manager::get_stats();
WP_AIGuardian_Database_Manager::cleanup_old_data(90);
WP_AIGuardian_Database_Manager::export_data('all');
```

---

## ✅ Checklist

- [ ] Step 1: Updated `class-wpaig-core.php` with new classes
- [ ] Step 2: Verified database tables created
- [ ] Step 3: Obtained Grok API key from console.x.ai
- [ ] Step 4: Configured API key in WordPress
- [ ] Step 5: Tested connection and features

---

## 🎉 Success!

Once all steps are complete, you'll have:

✅ Grok AI integration working
✅ Security scanning with AI analysis
✅ Performance analysis with recommendations
✅ Plugin conflict detection
✅ AI-powered spam filtering
✅ Usage tracking and analytics
✅ Migration from old providers

**Ready for Phase 3 UI implementation!** 🚀

---

## 📚 Documentation

- `GROK-MIGRATION-PHASE1-2-COMPLETE.md` - Full technical documentation
- `REST-API-REFERENCE.md` - REST API endpoints
- `CORE-INTEGRATION-COMPLETE.md` - Core architecture
- `ALL-FEATURES-COMPLETE.md` - Master feature list

---

**Need help? All classes are fully documented with PHPDoc comments!**
