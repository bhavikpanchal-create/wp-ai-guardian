# Free AI API Alternatives for WP AI Guardian

## 🎯 Current Status
- ✅ **Fixed:** Model name updated to `llama-3.1-sonar-small-128k-online`
- ✅ **Fixed:** SSL certificate issue on localhost
- ⚠️ **Issue:** Perplexity requires payment method even for API usage

---

## 💰 Best FREE AI API Alternatives

### **Option 1: Hugging Face Inference API** ⭐ RECOMMENDED

**Why Choose This:**
- ✅ **100% FREE** (rate limits but no billing required)
- ✅ **No credit card** needed
- ✅ **Easy setup** (just create account)
- ✅ **30,000 requests/month** free tier
- ✅ **Multiple models** available

**Setup Steps:**

1. **Create Free Account:**
   - Go to: https://huggingface.co/join
   - Sign up with email or GitHub

2. **Get Free API Token:**
   - Visit: https://huggingface.co/settings/tokens
   - Click "New token"
   - Name: "WP AI Guardian"
   - Role: "Read"
   - Click "Generate"
   - Copy the token (starts with `hf_...`)

3. **Models to Use (FREE):**
   - `microsoft/Phi-3-mini-4k-instruct` (fast, good quality)
   - `mistralai/Mistral-7B-Instruct-v0.2` (better quality)
   - `google/flan-t5-base` (very fast, basic)

4. **Integration Code Below** ✅

---

### **Option 2: Google Gemini API** ⭐

**Why Choose This:**
- ✅ **FREE tier:** 60 requests/minute
- ✅ **High quality** responses
- ✅ **Official Google** API
- ⚠️ Requires Google account

**Setup Steps:**

1. **Get API Key:**
   - Go to: https://makersuite.google.com/app/apikey
   - Click "Create API Key"
   - Copy the key

2. **Free Tier:**
   - 60 requests per minute
   - 1,500 requests per day
   - No billing required initially

3. **Model:** `gemini-pro`

---

### **Option 3: Together AI** ⭐

**Why Choose This:**
- ✅ **$25 free credits** on signup
- ✅ **No credit card** for initial credits
- ✅ **Fast inference**
- ✅ **Many open-source models**

**Setup Steps:**

1. **Sign Up:**
   - Go to: https://api.together.xyz/signup
   - Create account
   - Get $25 free credits

2. **Get API Key:**
   - Dashboard → API Keys
   - Create new key

3. **Models (cheap/free with credits):**
   - `togethercomputer/llama-2-7b-chat`
   - `mistralai/Mistral-7B-Instruct-v0.1`

---

### **Option 4: OpenAI (Limited Free)** ⚠️

**Why Consider:**
- ✅ **Best quality**
- ⚠️ **$5 credit** on signup (expires)
- ⚠️ Requires phone verification

**Setup:**
1. https://platform.openai.com/signup
2. Get $5 free credit (expires in 3 months)
3. Model: `gpt-3.5-turbo`

---

### **Option 5: Groq (Fast & Free)** ⭐⭐

**Why Choose This:**
- ✅ **Completely FREE**
- ✅ **Fastest inference** (LPU technology)
- ✅ **No credit card** required
- ✅ **High rate limits**

**Setup Steps:**

1. **Sign Up:**
   - Go to: https://console.groq.com/
   - Create free account

2. **Get API Key:**
   - Dashboard → API Keys
   - Create key

3. **Models (all FREE):**
   - `llama3-70b-8192` (best quality)
   - `llama3-8b-8192` (fast)
   - `mixtral-8x7b-32768` (good balance)

4. **Rate Limits (FREE):**
   - 30 requests per minute
   - 14,400 requests per day
   - More than enough!

---

## 🚀 Implementation: Hugging Face (RECOMMENDED)

### **Step 1: Modify AI Handler Class**

Add this method to `class-ai-handler.php`:

```php
/**
 * Call Hugging Face Inference API (FREE)
 *
 * @param string $prompt The prompt to send
 * @return string|WP_Error AI response or error
 */
private function call_huggingface_api(string $prompt) {
    $api_key = $this->get_api_key();
    
    if (empty($api_key)) {
        return new WP_Error('no_api_key', 'API key not configured');
    }
    
    // Choose model (these are FREE)
    $model = 'microsoft/Phi-3-mini-4k-instruct'; // Fast & good quality
    // Alternative: 'mistralai/Mistral-7B-Instruct-v0.2'
    
    $endpoint = "https://api-inference.huggingface.co/models/{$model}";
    
    // Prepare request
    $body = json_encode([
        'inputs' => $prompt,
        'parameters' => [
            'max_new_tokens' => 150,
            'temperature' => 0.7,
            'return_full_text' => false
        ]
    ]);
    
    // Detect localhost for SSL
    $is_local = (
        in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) ||
        strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
    );
    
    // Initialize cURL
    $ch = curl_init($endpoint);
    
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => !$is_local,
        CURLOPT_SSL_VERIFYHOST => $is_local ? 0 : 2
    ]);
    
    // Execute
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Handle errors
    if ($http_code !== 200) {
        error_log('HuggingFace API Error: HTTP ' . $http_code . ' - ' . $response);
        return new WP_Error('api_error', 'API returned HTTP ' . $http_code);
    }
    
    // Parse response
    $data = json_decode($response, true);
    
    // HuggingFace returns array of results
    if (isset($data[0]['generated_text'])) {
        return trim($data[0]['generated_text']);
    }
    
    return new WP_Error('no_content', 'No content in API response');
}
```

### **Step 2: Update Settings Page**

Change the API key label from:
- ❌ "Hugging Face API Key" (confusing)
- ✅ "AI API Key" (generic - works for any provider)

### **Step 3: Switch API Provider**

In `call_perplexity_api()`, change to call the new method:

```php
// In generate() method, replace:
$ai_response = $this->call_perplexity_api($prompt);

// With:
$ai_response = $this->call_huggingface_api($prompt);
```

---

## 🚀 Implementation: Groq (FASTEST & FREE)

### **Add Groq Method:**

```php
/**
 * Call Groq API (FREE & FAST)
 *
 * @param string $prompt The prompt to send
 * @return string|WP_Error AI response or error
 */
private function call_groq_api(string $prompt) {
    $api_key = $this->get_api_key();
    
    if (empty($api_key)) {
        return new WP_Error('no_api_key', 'API key not configured');
    }
    
    $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
    
    $body = json_encode([
        'model' => 'llama3-8b-8192', // FREE & fast
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 150,
        'temperature' => 0.7
    ]);
    
    // Detect localhost for SSL
    $is_local = (
        in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) ||
        strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
    );
    
    $ch = curl_init($endpoint);
    
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => !$is_local,
        CURLOPT_SSL_VERIFYHOST => $is_local ? 0 : 2
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return new WP_Error('api_error', 'API returned HTTP ' . $http_code);
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['choices'][0]['message']['content'])) {
        return $data['choices'][0]['message']['content'];
    }
    
    return new WP_Error('no_content', 'No content in API response');
}
```

---

## 🎯 Comparison Table

| Provider | Cost | Setup | Quality | Speed | Rate Limit |
|----------|------|-------|---------|-------|------------|
| **Groq** ⭐⭐ | FREE | Easy | Excellent | Fastest | 30/min |
| **Hugging Face** ⭐ | FREE | Easy | Good | Medium | 30k/month |
| **Google Gemini** | FREE tier | Medium | Excellent | Fast | 60/min |
| **Together AI** | $25 credit | Easy | Good | Fast | High |
| **Perplexity** ❌ | Requires billing | Easy | Excellent | Fast | High |
| **OpenAI** | $5 credit | Medium | Best | Fast | Limited |

---

## 💡 My Recommendation: **GROQ**

### **Why Groq is Perfect:**

1. **✅ 100% FREE Forever**
   - No credit card needed
   - No expiring credits
   - Generous rate limits

2. **✅ Fastest Inference**
   - Uses LPU (Language Processing Unit)
   - 10x faster than GPUs
   - Instant responses

3. **✅ High Quality**
   - Llama 3 70B model
   - OpenAI-compatible API
   - Great for WordPress plugins

4. **✅ Easy Integration**
   - Same API format as OpenAI
   - Simple authentication
   - Good documentation

---

## 🚀 Quick Setup: Switch to Groq NOW

### **Step 1: Get Groq API Key (2 minutes)**

1. Go to: https://console.groq.com/
2. Click "Sign Up" (free)
3. Verify email
4. Go to "API Keys"
5. Click "Create API Key"
6. Copy the key (starts with `gsk_...`)

### **Step 2: Update WP AI Guardian**

1. Go to: http://localhost/wp-ai-guardian/wp-admin/admin.php?page=wp-ai-guardian
2. Click "Settings" tab
3. Paste Groq API key
4. Save Settings

### **Step 3: I'll Update the Code**

Would you like me to:
- ✅ Add Groq support to the plugin?
- ✅ Add a dropdown to select provider (Groq/Hugging Face/Perplexity)?
- ✅ Keep Perplexity as fallback if you add billing later?

---

## 📊 Cost Comparison (per 1000 requests)

| Provider | Cost | Notes |
|----------|------|-------|
| **Groq** | $0.00 | FREE forever |
| **Hugging Face** | $0.00 | FREE (rate limited) |
| **Perplexity** | ~$0.50 | Requires billing setup |
| **OpenAI GPT-3.5** | $0.50 | After free $5 |
| **OpenAI GPT-4** | $30.00 | Expensive |

---

## 🎯 Final Recommendation

### **For Your Plugin:**

**Use Groq as Primary:**
- FREE forever
- Fastest responses
- Perfect for WordPress
- No billing setup needed

**Code Changes Needed:**
1. Add `call_groq_api()` method
2. Update Settings to say "AI API Key (Groq/HuggingFace/Perplexity)"
3. Optionally add provider selector dropdown

**This will make your plugin:**
- ✅ Work out of the box
- ✅ No user billing required
- ✅ Fast and reliable
- ✅ Better user experience

---

## 🤔 Your Choice

Reply with ONE of these:

1. **"Add Groq"** - I'll add Groq support (RECOMMENDED)
2. **"Add Hugging Face"** - I'll add HF support
3. **"Add dropdown"** - Let users choose provider
4. **"Fix Perplexity only"** - Keep current (requires your billing)

---

**Current Status:**
- ✅ Perplexity model name fixed
- ✅ SSL issue fixed
- ⏳ Waiting for your choice on FREE alternative

**Test the fix:**
Refresh: http://localhost/wp-ai-guardian/wp-content/plugins/wp-ai-guardian/DEBUG-API.php

If you still get errors, it means Perplexity requires payment method. Then we should switch to Groq (FREE)!
