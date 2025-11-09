# AI Handler - Perplexity API Integration

## ✅ Implementation Complete

**File:** `includes/class-ai-handler.php`  
**Size:** 8.96 KB ✅ (Under 10KB requirement)  
**Status:** Integrated and active

---

## 🎯 Features Implemented

### 1. **Perplexity API Integration**
- ✅ Model: `llama-3.1-sonar-small-128k-chat`
- ✅ Endpoint: `https://api.perplexity.ai/chat/completions`
- ✅ API Key: Built-in (ready to use)
- ✅ Method: cURL (no external dependencies)
- ✅ Max tokens: 150
- ✅ Temperature: 0.7

### 2. **Caching System**
- ✅ WordPress transients for 1-hour cache
- ✅ Cache key: `wpaig_ai_` + MD5 of prompt
- ✅ Automatic cache hit/miss tracking
- ✅ No duplicate API calls for same prompts

### 3. **Free Tier Limiting**
- ✅ Default: 3 calls per day for free users
- ✅ Daily counter stored in WP options
- ✅ Automatic daily reset via WP cron
- ✅ Fallback reset check (if cron fails)
- ✅ Unlimited calls for premium users
- ✅ Custom message when limit reached

### 4. **Error Handling & Fallback**
- ✅ API error detection
- ✅ cURL error handling
- ✅ JSON parsing validation
- ✅ HTTP status code checking
- ✅ Predefined fallback responses
- ✅ Graceful degradation

### 5. **REST API Endpoint**
- ✅ Endpoint: `/wp-json/wpaig/v1/ai-generate`
- ✅ Method: POST
- ✅ Nonce verification
- ✅ Permission check (manage_options)
- ✅ Usage tracking in response

---

## 📖 Usage Examples

### **PHP Usage (In Plugin)**

```php
// Get AI handler instance
$ai_handler = new WP_AIGuardian_AI_Handler();

// Basic usage
$response = $ai_handler->generate('How to fix 404 errors in WordPress?');
echo $response;

// With custom max calls
$response = $ai_handler->generate('Optimize WordPress database', 5);

// Get usage statistics
$stats = $ai_handler->get_usage_stats();
print_r($stats);
// Output:
// Array (
//     [calls_today] => 2
//     [last_reset] => 2025-11-08
//     [is_premium] => false
//     [next_reset] => 2025-11-09
// )
```

### **REST API Usage (JavaScript)**

```javascript
// From React Dashboard
async function getAIHelp(prompt) {
    try {
        const response = await fetch(wpaigData.restUrl + 'wpaig/v1/ai-generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': wpaigData.restNonce
            },
            body: JSON.stringify({
                prompt: prompt,
                max_calls: 3
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('AI Response:', data.response);
            console.log('Cached:', data.cached);
            console.log('Calls Remaining:', data.calls_remaining);
            console.log('Premium:', data.is_premium);
        }
    } catch (error) {
        console.error('AI request failed:', error);
    }
}

// Example usage
getAIHelp('How to fix slow WordPress site?');
```

### **cURL Command (Testing)**

```bash
curl -X POST "http://your-site.com/wp-json/wpaig/v1/ai-generate" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE_HERE" \
  --cookie "wordpress_logged_in_HASH=YOUR_COOKIE" \
  -d '{
    "prompt": "What are best WordPress security practices?",
    "max_calls": 3
  }'
```

---

## 🔒 Security Features

1. **API Key Protection**
   - Stored as private constant
   - Never exposed to frontend
   - SSL verified connections

2. **Access Control**
   - REST endpoint requires admin capabilities
   - Nonce verification on all requests
   - WP permission checks

3. **Rate Limiting**
   - Free tier: 3 calls/day (configurable)
   - Premium: Unlimited
   - Daily auto-reset

4. **Input Sanitization**
   - Prompt validation
   - JSON encoding/decoding safety
   - cURL timeout protection

---

## 📊 Response Formats

### **Success Response (String)**
```php
"To fix 404 errors in WordPress, you should: 1) Check your permalink settings..."
```

### **Limit Reached Response (String)**
```php
"Upgrade for more AI - Free tier limit reached for today. Get unlimited AI calls with Premium (₹999/month)."
```

### **Fallback Response (Array)**
```php
[
    'fix' => 'Check logs manually',
    'suggestions' => [
        'Review WordPress debug.log file',
        'Check PHP error logs',
        'Verify plugin compatibility',
        'Clear cache and try again',
        'Contact support if issue persists'
    ],
    'note' => 'AI service temporarily unavailable. Using fallback recommendations.'
]
```

### **REST API Response (JSON)**
```json
{
    "success": true,
    "response": "AI generated content here...",
    "cached": false,
    "calls_remaining": 2,
    "is_premium": false
}
```

---

## ⚙️ Configuration

### **Change Daily Free Limit**
```php
// In your code
$response = $ai_handler->generate($prompt, 10); // 10 calls instead of 3
```

### **Manual Counter Reset**
```php
$ai_handler->reset_daily_counter();
```

### **Check Current Usage**
```php
$stats = $ai_handler->get_usage_stats();
echo "Calls today: " . $stats['calls_today'];
```

### **Enable Premium (Unlimited)**
```php
update_option('wpaig_is_premium', true);
```

---

## 🔄 Cron Job

**Hook:** `wpaig_reset_ai_counter`  
**Schedule:** Daily  
**Function:** Resets call counter to 0

**Manual trigger:**
```php
do_action('wpaig_reset_ai_counter');
```

**Check next scheduled time:**
```php
$timestamp = wp_next_scheduled('wpaig_reset_ai_counter');
echo date('Y-m-d H:i:s', $timestamp);
```

---

## 🧪 Testing Checklist

### ✅ Basic Functionality
```php
// Test 1: First call (should hit API)
$result = $ai->generate('Test prompt 1');
// ✓ Should return AI response

// Test 2: Same prompt (should use cache)
$result = $ai->generate('Test prompt 1');
// ✓ Should return instantly from cache

// Test 3: Different prompt
$result = $ai->generate('Test prompt 2');
// ✓ Should hit API again
```

### ✅ Free Tier Limiting
```php
// Make 3 calls as free user
update_option('wpaig_is_premium', false);
$ai->generate('Prompt 1');
$ai->generate('Prompt 2');
$ai->generate('Prompt 3');

// 4th call should fail
$result = $ai->generate('Prompt 4');
// ✓ Should return "Upgrade for more AI..."
```

### ✅ Premium Access
```php
update_option('wpaig_is_premium', true);

// Make 10+ calls
for ($i = 0; $i < 15; $i++) {
    $result = $ai->generate("Prompt $i");
    // ✓ All should succeed
}
```

### ✅ Error Handling
```php
// Simulate API failure (temporarily change API_KEY to invalid)
$result = $ai->generate('Test');
// ✓ Should return fallback array
// ✓ Should not increment counter
```

---

## 📈 Integration Points

### **In Scan Results**
```php
// Get AI suggestions for found issues
foreach ($issues as $issue) {
    $ai_fix = $ai->generate("How to fix: {$issue['message']}");
    $issue['ai_suggestion'] = $ai_fix;
}
```

### **In Dashboard Widget**
```javascript
// Add "Ask AI" button to each issue
e('button', {
    className: 'wpaig-btn wpaig-btn-sm',
    onClick: () => askAIForHelp(issue.issue)
}, '🤖 Ask AI')
```

### **In Settings Page**
```php
// Show AI usage stats
$stats = $ai->get_usage_stats();
echo sprintf(
    'AI Calls Today: %d / %s',
    $stats['calls_today'],
    $stats['is_premium'] ? 'Unlimited' : '3'
);
```

---

## 🚀 Next Steps

1. **Test REST Endpoint**
   - Use browser console or Postman
   - Verify nonce and permissions

2. **Integrate into Dashboard**
   - Add "Ask AI" buttons to scan results
   - Display AI suggestions in UI

3. **Add to Documentation**
   - User guide for AI features
   - Premium benefits explanation

4. **Monitor Usage**
   - Track daily API calls
   - Monitor cache hit rates
   - Check error logs

---

## 📦 File Structure

```
wp-ai-guardian/
├── includes/
│   ├── class-wpaig-core.php
│   └── class-ai-handler.php ✅ NEW (8.96 KB)
└── wp-ai-guardian.php (updated)
```

---

## ✅ Requirements Met

- ✅ PHP class `WP_AIGuardian_AI_Handler`
- ✅ File `includes/class-ai-handler.php`
- ✅ Lightweight: 8.96 KB (< 10KB) ✅
- ✅ cURL for API calls only
- ✅ Method `generate($prompt, $max_calls=3)`
- ✅ Perplexity API integration
- ✅ WordPress transient caching (1 hour)
- ✅ Free tier limiting with daily reset
- ✅ Cron job for counter reset
- ✅ Fallback responses
- ✅ JSON parsing and extraction
- ✅ REST API endpoint `/wp-json/wpaig/v1/ai-generate`
- ✅ Nonce verification
- ✅ Constructor with cron init
- ✅ No external dependencies
- ✅ Integrated into plugin

---

**Status:** ✅ **READY TO USE**  
**API Calls:** Ready to test  
**Caching:** Active  
**Rate Limiting:** Active  
**REST Endpoint:** `/wp-json/wpaig/v1/ai-generate`
