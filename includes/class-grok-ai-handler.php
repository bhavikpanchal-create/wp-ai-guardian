<?php
/**
 * Grok AI Handler Class
 * 
 * Handles all Grok API (xAI) interactions for:
 * - Test connection
 * - Security analysis
 * - Performance analysis
 * - Conflict detection
 * - Spam classification
 * 
 * @package WP_AI_Guardian
 * @since 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_AIGuardian_Grok_AI_Handler {
    
    /**
     * Grok API endpoint
     */
    private const API_ENDPOINT = 'https://api.x.ai/v1/chat/completions';
    
    /**
     * API key from settings
     */
    private $api_key;
    
    /**
     * Usage tracking
     */
    private $usage_option = 'wpaig_grok_usage';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('wpaig_grok_api_key', '');
    }
    
    /**
     * Test Grok API connection
     * 
     * @return array Connection status and details
     */
    public function test_connection(): array {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'message' => 'Grok API key not configured',
                'status' => 'error'
            ];
        }
        
        try {
            $response = $this->make_api_call(
                'Say "Connection successful" if you can read this.',
                'test',
                100
            );
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Grok API connected successfully',
                    'status' => 'connected',
                    'model' => $response['model'] ?? 'grok-beta',
                    'response_time' => $response['response_time'] ?? 0
                ];
            }
            
            return [
                'success' => false,
                'message' => $response['error'] ?? 'Connection failed',
                'status' => 'error'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => WP_DEBUG ? $e->getMessage() : 'Connection test failed',
                'status' => 'error'
            ];
        }
    }
    
    /**
     * Perform security analysis
     * 
     * @param array $security_data Security scan data
     * @return array Security score and recommendations
     */
    public function analyze_security(array $security_data): array {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'message' => 'Grok API key not configured'
            ];
        }
        
        // Build security analysis prompt
        $prompt = $this->build_security_prompt($security_data);
        
        try {
            $response = $this->make_api_call($prompt, 'security', 1500);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? 'Security analysis failed'
                ];
            }
            
            // Parse AI response
            $analysis = $this->parse_security_response($response['content']);
            
            // Track usage
            $this->track_usage('security_analysis');
            
            return [
                'success' => true,
                'score' => $analysis['score'],
                'grade' => $analysis['grade'],
                'vulnerabilities' => $analysis['vulnerabilities'],
                'recommendations' => $analysis['recommendations'],
                'critical_issues' => $analysis['critical_issues'],
                'summary' => $analysis['summary']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => WP_DEBUG ? $e->getMessage() : 'Analysis failed'
            ];
        }
    }
    
    /**
     * Perform performance analysis
     * 
     * @param array $performance_data Performance metrics
     * @return array Performance score and recommendations
     */
    public function analyze_performance(array $performance_data): array {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'message' => 'Grok API key not configured'
            ];
        }
        
        // Build performance analysis prompt
        $prompt = $this->build_performance_prompt($performance_data);
        
        try {
            $response = $this->make_api_call($prompt, 'performance', 1500);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? 'Performance analysis failed'
                ];
            }
            
            // Parse AI response
            $analysis = $this->parse_performance_response($response['content']);
            
            // Track usage
            $this->track_usage('performance_analysis');
            
            return [
                'success' => true,
                'score' => $analysis['score'],
                'grade' => $analysis['grade'],
                'bottlenecks' => $analysis['bottlenecks'],
                'recommendations' => $analysis['recommendations'],
                'quick_wins' => $analysis['quick_wins'],
                'summary' => $analysis['summary']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => WP_DEBUG ? $e->getMessage() : 'Analysis failed'
            ];
        }
    }
    
    /**
     * Detect plugin conflicts
     * 
     * @param array $plugin_data Active plugins and their data
     * @return array Conflict detection results
     */
    public function detect_conflicts(array $plugin_data): array {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'message' => 'Grok API key not configured'
            ];
        }
        
        // Build conflict detection prompt
        $prompt = $this->build_conflict_prompt($plugin_data);
        
        try {
            $response = $this->make_api_call($prompt, 'conflict', 1000);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? 'Conflict detection failed'
                ];
            }
            
            // Parse AI response
            $analysis = $this->parse_conflict_response($response['content']);
            
            // Track usage
            $this->track_usage('conflict_detection');
            
            return [
                'success' => true,
                'conflicts' => $analysis['conflicts'],
                'warnings' => $analysis['warnings'],
                'recommendations' => $analysis['recommendations'],
                'compatible_alternatives' => $analysis['alternatives']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => WP_DEBUG ? $e->getMessage() : 'Detection failed'
            ];
        }
    }
    
    /**
     * Classify spam content
     * 
     * @param string $content Content to analyze
     * @param array $metadata Additional context (author, IP, etc.)
     * @return array Spam classification results
     */
    public function classify_spam(string $content, array $metadata = []): array {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'message' => 'Grok API key not configured'
            ];
        }
        
        // Build spam classification prompt
        $prompt = $this->build_spam_prompt($content, $metadata);
        
        try {
            $response = $this->make_api_call($prompt, 'spam', 500);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? 'Spam classification failed'
                ];
            }
            
            // Parse AI response
            $analysis = $this->parse_spam_response($response['content']);
            
            // Track usage
            $this->track_usage('spam_classification');
            
            return [
                'success' => true,
                'is_spam' => $analysis['is_spam'],
                'confidence' => $analysis['confidence'],
                'spam_score' => $analysis['score'],
                'spam_indicators' => $analysis['indicators'],
                'recommended_action' => $analysis['action'],
                'reasoning' => $analysis['reasoning']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => WP_DEBUG ? $e->getMessage() : 'Classification failed'
            ];
        }
    }
    
    /**
     * Make Grok API call
     * 
     * @param string $prompt User prompt
     * @param string $context Context type for system message
     * @param int $max_tokens Maximum tokens in response
     * @return array API response
     */
    private function make_api_call(string $prompt, string $context = 'general', int $max_tokens = 1000): array {
        $start_time = microtime(true);
        
        // System messages based on context
        $system_messages = [
            'test' => 'You are a connection test assistant. Respond concisely.',
            'security' => 'You are a WordPress security expert. Analyze security data and provide scores (0-100), identify vulnerabilities, and give actionable recommendations. Respond in JSON format.',
            'performance' => 'You are a WordPress performance expert. Analyze performance metrics and provide scores (0-100), identify bottlenecks, and give optimization recommendations. Respond in JSON format.',
            'conflict' => 'You are a WordPress plugin compatibility expert. Analyze plugin combinations, detect conflicts, and suggest alternatives. Respond in JSON format.',
            'spam' => 'You are a spam detection expert. Analyze content and classify as spam or legitimate. Provide confidence score (0-100) and reasoning. Respond in JSON format.',
            'general' => 'You are a helpful WordPress assistant.'
        ];
        
        $system_message = $system_messages[$context] ?? $system_messages['general'];
        
        $body = [
            'model' => 'grok-beta',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $system_message
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $max_tokens,
            'temperature' => 0.7,
            'stream' => false
        ];
        
        $args = [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ],
            'body' => wp_json_encode($body),
            'sslverify' => !$this->is_local_environment()
        ];
        
        $response = wp_remote_post(self::API_ENDPOINT, $args);
        
        // Calculate response time
        $response_time = round((microtime(true) - $start_time) * 1000, 2);
        
        // Handle errors
        if (is_wp_error($response)) {
            error_log('WPAIG Grok API Error: ' . $response->get_error_message());
            return [
                'success' => false,
                'error' => $response->get_error_message(),
                'response_time' => $response_time
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            error_log('WPAIG Grok API HTTP Error: ' . $status_code . ' - ' . $body);
            return [
                'success' => false,
                'error' => "API returned status code {$status_code}",
                'response_time' => $response_time
            ];
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('WPAIG Grok API JSON Error: ' . json_last_error_msg());
            return [
                'success' => false,
                'error' => 'Invalid JSON response from API',
                'response_time' => $response_time
            ];
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            error_log('WPAIG Grok API Response Error: Missing content in response');
            return [
                'success' => false,
                'error' => 'Invalid API response structure',
                'response_time' => $response_time
            ];
        }
        
        return [
            'success' => true,
            'content' => $data['choices'][0]['message']['content'],
            'model' => $data['model'] ?? 'grok-beta',
            'usage' => $data['usage'] ?? [],
            'response_time' => $response_time
        ];
    }
    
    /**
     * Build security analysis prompt
     */
    private function build_security_prompt(array $data): string {
        $prompt = "Analyze this WordPress site's security:\n\n";
        
        if (isset($data['wp_version'])) {
            $prompt .= "WordPress Version: {$data['wp_version']}\n";
        }
        
        if (isset($data['outdated_plugins'])) {
            $prompt .= "Outdated Plugins: " . count($data['outdated_plugins']) . "\n";
        }
        
        if (isset($data['file_permissions'])) {
            $prompt .= "File Permissions Issues: {$data['file_permissions']}\n";
        }
        
        if (isset($data['ssl_enabled'])) {
            $prompt .= "SSL Enabled: " . ($data['ssl_enabled'] ? 'Yes' : 'No') . "\n";
        }
        
        if (isset($data['admin_username'])) {
            $prompt .= "Uses 'admin' username: " . ($data['admin_username'] ? 'Yes' : 'No') . "\n";
        }
        
        $prompt .= "\nProvide: security_score (0-100), grade (A-F), vulnerabilities[], recommendations[], critical_issues[], summary in JSON format.";
        
        return $prompt;
    }
    
    /**
     * Build performance analysis prompt
     */
    private function build_performance_prompt(array $data): string {
        $prompt = "Analyze this WordPress site's performance:\n\n";
        
        if (isset($data['page_load_time'])) {
            $prompt .= "Page Load Time: {$data['page_load_time']}s\n";
        }
        
        if (isset($data['db_queries'])) {
            $prompt .= "Database Queries: {$data['db_queries']}\n";
        }
        
        if (isset($data['memory_usage'])) {
            $prompt .= "Memory Usage: {$data['memory_usage']}MB\n";
        }
        
        if (isset($data['total_plugins'])) {
            $prompt .= "Total Plugins: {$data['total_plugins']}\n";
        }
        
        if (isset($data['caching_enabled'])) {
            $prompt .= "Caching: " . ($data['caching_enabled'] ? 'Enabled' : 'Disabled') . "\n";
        }
        
        if (isset($data['image_optimization'])) {
            $prompt .= "Image Optimization: " . ($data['image_optimization'] ? 'Yes' : 'No') . "\n";
        }
        
        $prompt .= "\nProvide: performance_score (0-100), grade (A-F), bottlenecks[], recommendations[], quick_wins[], summary in JSON format.";
        
        return $prompt;
    }
    
    /**
     * Build conflict detection prompt
     */
    private function build_conflict_prompt(array $data): string {
        $prompt = "Analyze these WordPress plugins for conflicts:\n\n";
        
        if (isset($data['active_plugins'])) {
            $prompt .= "Active Plugins:\n";
            foreach ($data['active_plugins'] as $plugin) {
                $prompt .= "- {$plugin['name']} (v{$plugin['version']})\n";
            }
        }
        
        if (isset($data['error_log'])) {
            $prompt .= "\nRecent Errors:\n{$data['error_log']}\n";
        }
        
        $prompt .= "\nProvide: conflicts[], warnings[], recommendations[], compatible_alternatives[] in JSON format.";
        
        return $prompt;
    }
    
    /**
     * Build spam classification prompt
     */
    private function build_spam_prompt(string $content, array $metadata): string {
        $prompt = "Classify this comment as spam or legitimate:\n\n";
        $prompt .= "Content: {$content}\n\n";
        
        if (!empty($metadata['author'])) {
            $prompt .= "Author: {$metadata['author']}\n";
        }
        
        if (!empty($metadata['email'])) {
            $prompt .= "Email: {$metadata['email']}\n";
        }
        
        if (!empty($metadata['url'])) {
            $prompt .= "URL: {$metadata['url']}\n";
        }
        
        if (!empty($metadata['ip'])) {
            $prompt .= "IP: {$metadata['ip']}\n";
        }
        
        $prompt .= "\nProvide: is_spam (true/false), confidence (0-100), spam_score (0-100), indicators[], recommended_action, reasoning in JSON format.";
        
        return $prompt;
    }
    
    /**
     * Parse security analysis response
     */
    private function parse_security_response(string $content): array {
        $data = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return [
                'score' => $data['security_score'] ?? 0,
                'grade' => $data['grade'] ?? 'F',
                'vulnerabilities' => $data['vulnerabilities'] ?? [],
                'recommendations' => $data['recommendations'] ?? [],
                'critical_issues' => $data['critical_issues'] ?? [],
                'summary' => $data['summary'] ?? 'Analysis completed'
            ];
        }
        
        // Fallback parsing if not JSON
        return [
            'score' => 50,
            'grade' => 'C',
            'vulnerabilities' => [],
            'recommendations' => [$content],
            'critical_issues' => [],
            'summary' => 'Analysis completed with partial results'
        ];
    }
    
    /**
     * Parse performance analysis response
     */
    private function parse_performance_response(string $content): array {
        $data = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return [
                'score' => $data['performance_score'] ?? 0,
                'grade' => $data['grade'] ?? 'F',
                'bottlenecks' => $data['bottlenecks'] ?? [],
                'recommendations' => $data['recommendations'] ?? [],
                'quick_wins' => $data['quick_wins'] ?? [],
                'summary' => $data['summary'] ?? 'Analysis completed'
            ];
        }
        
        // Fallback parsing
        return [
            'score' => 50,
            'grade' => 'C',
            'bottlenecks' => [],
            'recommendations' => [$content],
            'quick_wins' => [],
            'summary' => 'Analysis completed with partial results'
        ];
    }
    
    /**
     * Parse conflict detection response
     */
    private function parse_conflict_response(string $content): array {
        $data = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return [
                'conflicts' => $data['conflicts'] ?? [],
                'warnings' => $data['warnings'] ?? [],
                'recommendations' => $data['recommendations'] ?? [],
                'alternatives' => $data['compatible_alternatives'] ?? []
            ];
        }
        
        // Fallback parsing
        return [
            'conflicts' => [],
            'warnings' => [],
            'recommendations' => [$content],
            'alternatives' => []
        ];
    }
    
    /**
     * Parse spam classification response
     */
    private function parse_spam_response(string $content): array {
        $data = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return [
                'is_spam' => $data['is_spam'] ?? false,
                'confidence' => $data['confidence'] ?? 0,
                'score' => $data['spam_score'] ?? 0,
                'indicators' => $data['indicators'] ?? [],
                'action' => $data['recommended_action'] ?? 'approve',
                'reasoning' => $data['reasoning'] ?? ''
            ];
        }
        
        // Fallback parsing
        $is_spam = stripos($content, 'spam') !== false && stripos($content, 'not spam') === false;
        
        return [
            'is_spam' => $is_spam,
            'confidence' => 50,
            'score' => $is_spam ? 80 : 20,
            'indicators' => [],
            'action' => $is_spam ? 'spam' : 'approve',
            'reasoning' => $content
        ];
    }
    
    /**
     * Track API usage
     */
    private function track_usage(string $type): void {
        $usage = get_option($this->usage_option, []);
        
        $today = date('Y-m-d');
        
        if (!isset($usage[$today])) {
            $usage[$today] = [];
        }
        
        if (!isset($usage[$today][$type])) {
            $usage[$today][$type] = 0;
        }
        
        $usage[$today][$type]++;
        
        // Keep only last 30 days
        $usage = array_slice($usage, -30, 30, true);
        
        update_option($this->usage_option, $usage);
    }
    
    /**
     * Get API usage statistics
     * 
     * @return array Usage stats
     */
    public function get_usage_stats(): array {
        $usage = get_option($this->usage_option, []);
        
        $today = date('Y-m-d');
        $this_month = date('Y-m');
        
        $stats = [
            'today' => $usage[$today] ?? [],
            'this_month' => [],
            'total' => []
        ];
        
        foreach ($usage as $date => $types) {
            if (strpos($date, $this_month) === 0) {
                foreach ($types as $type => $count) {
                    if (!isset($stats['this_month'][$type])) {
                        $stats['this_month'][$type] = 0;
                    }
                    $stats['this_month'][$type] += $count;
                }
            }
            
            foreach ($types as $type => $count) {
                if (!isset($stats['total'][$type])) {
                    $stats['total'][$type] = 0;
                }
                $stats['total'][$type] += $count;
            }
        }
        
        return $stats;
    }
    
    /**
     * Check if running on local environment
     */
    private function is_local_environment(): bool {
        $server_name = $_SERVER['SERVER_NAME'] ?? '';
        return in_array($server_name, ['localhost', '127.0.0.1', '::1']) || 
               strpos($server_name, '.local') !== false;
    }
}
