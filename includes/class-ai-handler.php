<?php
/**
 * AI Handler - Perplexity API Integration
 *
 * @package WP_AI_Guardian
 * @since 1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WP_AIGuardian_AI_Handler {
    
    /**
     * Perplexity API endpoint
     */
    private const API_ENDPOINT = 'https://api.perplexity.ai/chat/completions';
    
    /**
     * Cache expiration (1 hour)
     */
    private const CACHE_EXPIRY = 3600;
    
    /**
     * Daily call counter option name
     */
    private const COUNTER_OPTION = 'wpaig_ai_daily_calls';
    
    /**
     * Last reset date option name
     */
    private const RESET_DATE_OPTION = 'wpaig_ai_reset_date';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize daily reset cron
        $this->init_cron();
        
        // Register REST API endpoint
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    /**
     * Initialize cron for daily counter reset
     */
    private function init_cron(): void {
        if (!wp_next_scheduled('wpaig_reset_ai_counter')) {
            wp_schedule_event(time(), 'daily', 'wpaig_reset_ai_counter');
        }
        
        add_action('wpaig_reset_ai_counter', [$this, 'reset_daily_counter']);
    }
    
    /**
     * Reset daily AI call counter
     */
    public function reset_daily_counter(): void {
        update_option(self::COUNTER_OPTION, 0);
        update_option(self::RESET_DATE_OPTION, current_time('Y-m-d'));
    }
    
    /**
     * Check if daily reset is needed (fallback if cron fails)
     */
    private function check_daily_reset(): void {
        $last_reset = get_option(self::RESET_DATE_OPTION, '');
        $today = current_time('Y-m-d');
        
        if ($last_reset !== $today) {
            $this->reset_daily_counter();
        }
    }
    
    /**
     * Get current call count
     */
    private function get_call_count(): int {
        $this->check_daily_reset();
        return (int) get_option(self::COUNTER_OPTION, 0);
    }
    
    /**
     * Increment call count
     */
    private function increment_call_count(): void {
        $count = $this->get_call_count();
        update_option(self::COUNTER_OPTION, $count + 1);
    }
    
    /**
     * Check if user is premium
     */
    public function is_premium(): bool {
        return (bool) get_option('wpaig_is_premium', false);
    }
    
    /**
     * Get API key from settings
     */
    private function get_api_key(): string {
        return get_option('wpaig_hf_api_key', '');
    }
    
    /**
     * Generate AI response
     *
     * @param string $prompt The prompt to send to AI
     * @param int $max_calls Maximum free calls allowed per day
     * @return string|array AI response or error message
     */
    public function generate(string $prompt, int $max_calls = 3) {
        // Check cache first
        $cache_key = 'wpaig_ai_' . md5($prompt);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Check free tier limit
        if (!$this->is_premium() && $this->get_call_count() >= $max_calls) {
            return 'Upgrade for more AI - Free tier limit reached for today. Get unlimited AI calls with Premium (₹999/month).';
        }
        
        // Detect provider and call appropriate API
        $api_key = $this->get_api_key();
        $provider = $this->detect_provider($api_key);
        
        switch ($provider) {
            case 'groq':
                $response = $this->call_groq_api($prompt);
                break;
            case 'perplexity':
                $response = $this->call_perplexity_api($prompt);
                break;
            default:
                // Default to Groq if provider unknown but key exists
                $response = !empty($api_key) 
                    ? $this->call_groq_api($prompt) 
                    : new WP_Error('no_provider', 'Unknown API provider');
                break;
        }
        
        // Handle API errors with fallback
        if (is_wp_error($response)) {
            return $this->get_fallback_response();
        }
        
        // Increment counter (only after successful call)
        if (!$this->is_premium()) {
            $this->increment_call_count();
        }
        
        // Cache the response
        set_transient($cache_key, $response, self::CACHE_EXPIRY);
        
        return $response;
    }
    
    /**
     * Call Perplexity API using cURL
     *
     * @param string $prompt The prompt to send
     * @return string|WP_Error AI response or error
     */
    private function call_perplexity_api(string $prompt) {
        // Prepare request body
        $body = [
            'model' => 'llama-3.1-sonar-small-128k-online', // Updated model name
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 800, // Increased for complete recommendations
            'temperature' => 0.7
        ];
        
        $json_body = json_encode($body);
        
        // Initialize cURL
        $ch = curl_init(self::API_ENDPOINT);
        
        if ($ch === false) {
            return new WP_Error('curl_init_failed', 'Failed to initialize cURL');
        }
        
        // Get API key
        $api_key = $this->get_api_key();
        
        if (empty($api_key)) {
            curl_close($ch);
            return new WP_Error('no_api_key', 'API key not configured');
        }
        
        // Detect if running on localhost/local development
        $is_local = (
            in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) ||
            strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
            strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false
        );
        
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
            // Disable SSL verification on localhost for development
            CURLOPT_SSL_VERIFYPEER => !$is_local,
            CURLOPT_SSL_VERIFYHOST => $is_local ? 0 : 2
        ]);
        
        // Execute request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);
        
        // Handle cURL errors
        if ($response === false || !empty($curl_error)) {
            return new WP_Error('curl_error', 'cURL error: ' . $curl_error);
        }
        
        // Handle HTTP errors
        if ($http_code !== 200) {
            error_log('WP AI Guardian: API Error - HTTP ' . $http_code . ' - Response: ' . substr($response, 0, 500));
            return new WP_Error('api_error', 'API returned HTTP ' . $http_code . ': ' . substr($response, 0, 200));
        }
        
        // Parse JSON response
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('WP AI Guardian: JSON Parse Error - ' . json_last_error_msg() . ' - Response: ' . substr($response, 0, 500));
            return new WP_Error('json_error', 'Failed to parse JSON response: ' . json_last_error_msg());
        }
        
        // Extract content from response
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }
        
        // If no content found, log and return error
        error_log('WP AI Guardian: No content in response - ' . print_r($data, true));
        return new WP_Error('no_content', 'No content in API response');
    }
    
    /**
     * Call Groq API using cURL (FREE & FAST)
     *
     * @param string $prompt The prompt to send
     * @return string|WP_Error AI response or error
     */
    private function call_groq_api(string $prompt) {
        // Groq API endpoint
        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
        
        // Prepare request body
        $body = [
            'model' => 'llama-3.1-8b-instant', // Current FREE model (updated)
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 800, // Increased for complete recommendations
            'temperature' => 0.7
        ];
        
        $json_body = json_encode($body);
        
        // Initialize cURL
        $ch = curl_init($endpoint);
        
        if ($ch === false) {
            return new WP_Error('curl_init_failed', 'Failed to initialize cURL');
        }
        
        // Get API key
        $api_key = $this->get_api_key();
        
        if (empty($api_key)) {
            curl_close($ch);
            return new WP_Error('no_api_key', 'API key not configured');
        }
        
        // Detect if running on localhost/local development
        $is_local = (
            in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) ||
            strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
            strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false
        );
        
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
            // Disable SSL verification on localhost for development
            CURLOPT_SSL_VERIFYPEER => !$is_local,
            CURLOPT_SSL_VERIFYHOST => $is_local ? 0 : 2
        ]);
        
        // Execute request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);
        
        // Handle cURL errors
        if ($response === false || !empty($curl_error)) {
            return new WP_Error('curl_error', 'cURL error: ' . $curl_error);
        }
        
        // Handle HTTP errors
        if ($http_code !== 200) {
            error_log('WP AI Guardian (Groq): API Error - HTTP ' . $http_code . ' - Response: ' . substr($response, 0, 500));
            return new WP_Error('api_error', 'Groq API returned HTTP ' . $http_code . ': ' . substr($response, 0, 200));
        }
        
        // Parse JSON response
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('WP AI Guardian (Groq): JSON Parse Error - ' . json_last_error_msg() . ' - Response: ' . substr($response, 0, 500));
            return new WP_Error('json_error', 'Failed to parse JSON response: ' . json_last_error_msg());
        }
        
        // Extract content from response (same format as OpenAI)
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }
        
        // If no content found, log and return error
        error_log('WP AI Guardian (Groq): No content in response - ' . print_r($data, true));
        return new WP_Error('no_content', 'No content in API response');
    }
    
    /**
     * Detect API provider from API key format
     *
     * @param string $api_key The API key
     * @return string Provider name: 'groq', 'perplexity', 'huggingface', or 'unknown'
     */
    private function detect_provider(string $api_key): string {
        if (strpos($api_key, 'gsk_') === 0) {
            return 'groq';
        } elseif (strpos($api_key, 'pplx-') === 0) {
            return 'perplexity';
        } elseif (strpos($api_key, 'hf_') === 0) {
            return 'huggingface';
        } elseif (strpos($api_key, 'sk-') === 0) {
            return 'openai';
        }
        return 'unknown';
    }
    
    /**
     * Get fallback response when API fails
     *
     * @return array Predefined fallback suggestions
     */
    private function get_fallback_response(): array {
        return [
            'fix' => 'Check logs manually',
            'suggestions' => [
                'Review WordPress debug.log file',
                'Check PHP error logs',
                'Verify plugin compatibility',
                'Clear cache and try again',
                'Contact support if issue persists'
            ],
            'note' => 'AI service temporarily unavailable. Using fallback recommendations.'
        ];
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes(): void {
        register_rest_route('wpaig/v1', '/ai-generate', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_ai_generate'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }
    
    /**
     * REST API endpoint for AI generation
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_ai_generate($request): WP_REST_Response {
        // Verify nonce
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Invalid nonce'
            ], 403);
        }
        
        // Get prompt from request
        $prompt = $request->get_param('prompt');
        
        if (empty($prompt)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Prompt is required'
            ], 400);
        }
        
        // Get max calls (default 3)
        $max_calls = (int) $request->get_param('max_calls') ?: 3;
        
        // Generate AI response
        $response = $this->generate($prompt, $max_calls);
        
        // Return response
        return new WP_REST_Response([
            'success' => true,
            'response' => $response,
            'cached' => get_transient('wpaig_ai_' . md5($prompt)) !== false,
            'calls_remaining' => $this->is_premium() ? 'unlimited' : max(0, $max_calls - $this->get_call_count()),
            'is_premium' => $this->is_premium()
        ], 200);
    }
    
    /**
     * Get API usage stats
     *
     * @return array Usage statistics
     */
    public function get_usage_stats(): array {
        $this->check_daily_reset();
        
        return [
            'calls_today' => $this->get_call_count(),
            'last_reset' => get_option(self::RESET_DATE_OPTION, 'Never'),
            'is_premium' => $this->is_premium(),
            'next_reset' => date('Y-m-d', strtotime('tomorrow'))
        ];
    }
}
