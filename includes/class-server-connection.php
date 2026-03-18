<?php
/**
 * Server Connection Management Class
 * 
 * Handles connections to external servers, APIs, and databases
 * 
 * @package CourierFinancePlugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIT_Server_Connection
{
    private static $instance = null;
    private $config = [];
    
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        $this->load_config();
    }
    
    /**
     * Load server configuration from WordPress options
     */
    private function load_config()
    {
        $this->config = [
            'server_name' => get_option('kit_server_name', ''),
            'server_type' => get_option('kit_server_type', ''),
            'server_host' => get_option('kit_server_host', ''),
            'server_port' => get_option('kit_server_port', 3306),
            'server_username' => get_option('kit_server_username', ''),
            'server_password' => get_option('kit_server_password', ''),
            'server_database' => get_option('kit_server_database', ''),
            'server_ssl' => get_option('kit_server_ssl', 0),
            'api_endpoint' => get_option('kit_api_endpoint', ''),
            'api_timeout' => get_option('kit_api_timeout', 30),
            'api_headers' => get_option('kit_api_headers', ''),
            'api_retry_attempts' => get_option('kit_api_retry_attempts', 3),
            'webhook_url' => get_option('kit_webhook_url', ''),
            'webhook_secret' => get_option('kit_webhook_secret', ''),
            'webhook_events' => get_option('kit_webhook_events', [])
        ];
    }
    
    /**
     * Test server connection based on configuration
     */
    public function test_connection($config = null)
    {
        if ($config) {
            $this->config = array_merge($this->config, $config);
        }
        
        $server_type = $this->config['server_type'];
        
        switch ($server_type) {
            case 'mysql':
                return $this->test_mysql_connection();
            case 'api':
                return $this->test_api_connection();
            case 'webhook':
                return $this->test_webhook_connection();
            case 'ftp':
                return $this->test_ftp_connection();
            default:
                return [
                    'success' => false,
                    'message' => 'Invalid server type specified',
                    'details' => ['server_type' => $server_type]
                ];
        }
    }
    
    /**
     * Test MySQL database connection
     */
    private function test_mysql_connection()
    {
        try {
            $host = $this->config['server_host'];
            $port = $this->config['server_port'];
            $username = $this->config['server_username'];
            $password = $this->config['server_password'];
            $database = $this->config['server_database'];
            $ssl = $this->config['server_ssl'];
            
            if (empty($host) || empty($username)) {
                return [
                    'success' => false,
                    'message' => 'Host and username are required for MySQL connection',
                    'details' => ['host' => $host, 'username' => $username]
                ];
            }
            
            $dsn = "mysql:host={$host};port={$port}";
            if (!empty($database)) {
                $dsn .= ";dbname={$database}";
            }
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            if ($ssl) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = true;
            }
            
            $pdo = new PDO($dsn, $username, $password, $options);
            
            // Test query
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            
            return [
                'success' => true,
                'message' => 'MySQL connection successful',
                'details' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'ssl' => $ssl ? 'enabled' : 'disabled',
                    'test_query_result' => $result
                ]
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'MySQL connection failed: ' . $e->getMessage(),
                'details' => [
                    'host' => $host ?? 'not set',
                    'port' => $port ?? 'not set',
                    'database' => $database ?? 'not set',
                    'error_code' => $e->getCode()
                ]
            ];
        }
    }
    
    /**
     * Test API connection
     */
    private function test_api_connection()
    {
        try {
            $endpoint = $this->config['api_endpoint'];
            $timeout = $this->config['api_timeout'];
            $headers = $this->config['api_headers'];
            
            if (empty($endpoint)) {
                return [
                    'success' => false,
                    'message' => 'API endpoint is required',
                    'details' => ['endpoint' => $endpoint]
                ];
            }
            
            $request_headers = [
                'User-Agent' => 'CourierFinancePlugin/1.0',
                'Content-Type' => 'application/json'
            ];
            
            if (!empty($headers)) {
                $custom_headers = json_decode($headers, true);
                if (is_array($custom_headers)) {
                    $request_headers = array_merge($request_headers, $custom_headers);
                }
            }
            
            $args = [
                'timeout' => $timeout,
                'headers' => $request_headers,
                'method' => 'GET'
            ];
            
            $response = wp_remote_get($endpoint, $args);
            
            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'message' => 'API connection failed: ' . $response->get_error_message(),
                    'details' => [
                        'endpoint' => $endpoint,
                        'timeout' => $timeout,
                        'error' => $response->get_error_message()
                    ]
                ];
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            return [
                'success' => $response_code >= 200 && $response_code < 300,
                'message' => $response_code >= 200 && $response_code < 300 
                    ? 'API connection successful' 
                    : 'API returned error status',
                'details' => [
                    'endpoint' => $endpoint,
                    'response_code' => $response_code,
                    'response_body' => substr($response_body, 0, 500) . (strlen($response_body) > 500 ? '...' : ''),
                    'timeout' => $timeout
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'API connection test failed: ' . $e->getMessage(),
                'details' => [
                    'endpoint' => $endpoint ?? 'not set',
                    'error' => $e->getMessage()
                ]
            ];
        }
    }
    
    /**
     * Test webhook connection
     */
    private function test_webhook_connection()
    {
        try {
            $webhook_url = $this->config['webhook_url'];
            $webhook_secret = $this->config['webhook_secret'];
            
            if (empty($webhook_url)) {
                return [
                    'success' => false,
                    'message' => 'Webhook URL is required',
                    'details' => ['webhook_url' => $webhook_url]
                ];
            }
            
            $test_payload = [
                'event' => 'test_connection',
                'timestamp' => current_time('mysql'),
                'source' => 'courier_finance_plugin',
                'data' => [
                    'message' => 'This is a test webhook from Courier Finance Plugin'
                ]
            ];
            
            if (!empty($webhook_secret)) {
                $test_payload['signature'] = hash_hmac('sha256', json_encode($test_payload), $webhook_secret);
            }
            
            $args = [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'CourierFinancePlugin/1.0'
                ],
                'body' => json_encode($test_payload),
                'method' => 'POST'
            ];
            
            $response = wp_remote_post($webhook_url, $args);
            
            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'message' => 'Webhook test failed: ' . $response->get_error_message(),
                    'details' => [
                        'webhook_url' => $webhook_url,
                        'error' => $response->get_error_message()
                    ]
                ];
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            return [
                'success' => $response_code >= 200 && $response_code < 300,
                'message' => $response_code >= 200 && $response_code < 300 
                    ? 'Webhook test successful' 
                    : 'Webhook returned error status',
                'details' => [
                    'webhook_url' => $webhook_url,
                    'response_code' => $response_code,
                    'response_body' => substr($response_body, 0, 500) . (strlen($response_body) > 500 ? '...' : ''),
                    'test_payload_sent' => $test_payload
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Webhook test failed: ' . $e->getMessage(),
                'details' => [
                    'webhook_url' => $webhook_url ?? 'not set',
                    'error' => $e->getMessage()
                ]
            ];
        }
    }
    
    /**
     * Test FTP connection
     */
    private function test_ftp_connection()
    {
        try {
            $host = $this->config['server_host'];
            $port = $this->config['server_port'];
            $username = $this->config['server_username'];
            $password = $this->config['server_password'];
            $ssl = $this->config['server_ssl'];
            
            if (empty($host) || empty($username)) {
                return [
                    'success' => false,
                    'message' => 'Host and username are required for FTP connection',
                    'details' => ['host' => $host, 'username' => $username]
                ];
            }
            
            if ($ssl) {
                $connection = ftp_ssl_connect($host, $port ?: 21, 10);
            } else {
                $connection = ftp_connect($host, $port ?: 21, 10);
            }
            
            if (!$connection) {
                return [
                    'success' => false,
                    'message' => 'Failed to connect to FTP server',
                    'details' => [
                        'host' => $host,
                        'port' => $port ?: 21,
                        'ssl' => $ssl ? 'enabled' : 'disabled'
                    ]
                ];
            }
            
            $login = ftp_login($connection, $username, $password);
            
            if (!$login) {
                ftp_close($connection);
                return [
                    'success' => false,
                    'message' => 'FTP login failed - check credentials',
                    'details' => [
                        'host' => $host,
                        'port' => $port ?: 21,
                        'username' => $username,
                        'ssl' => $ssl ? 'enabled' : 'disabled'
                    ]
                ];
            }
            
            // Test listing directory
            $files = ftp_nlist($connection, '.');
            ftp_close($connection);
            
            return [
                'success' => true,
                'message' => 'FTP connection successful',
                'details' => [
                    'host' => $host,
                    'port' => $port ?: 21,
                    'username' => $username,
                    'ssl' => $ssl ? 'enabled' : 'disabled',
                    'files_in_root' => is_array($files) ? count($files) : 0
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'FTP connection failed: ' . $e->getMessage(),
                'details' => [
                    'host' => $host ?? 'not set',
                    'port' => $port ?? 'not set',
                    'error' => $e->getMessage()
                ]
            ];
        }
    }
    
    /**
     * Send webhook notification
     */
    public function send_webhook($event, $data = [])
    {
        $webhook_url = $this->config['webhook_url'];
        $webhook_events = $this->config['webhook_events'];
        
        if (empty($webhook_url) || !in_array($event, $webhook_events)) {
            return false;
        }
        
        $payload = [
            'event' => $event,
            'timestamp' => current_time('mysql'),
            'source' => 'courier_finance_plugin',
            'data' => $data
        ];
        
        if (!empty($this->config['webhook_secret'])) {
            $payload['signature'] = hash_hmac('sha256', json_encode($payload), $this->config['webhook_secret']);
        }
        
        $args = [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'CourierFinancePlugin/1.0'
            ],
            'body' => json_encode($payload),
            'method' => 'POST'
        ];
        
        $response = wp_remote_post($webhook_url, $args);
        
        if (is_wp_error($response)) {
            error_log('Webhook failed: ' . $response->get_error_message());
            return false;
        }
        
        return wp_remote_retrieve_response_code($response) >= 200 && wp_remote_retrieve_response_code($response) < 300;
    }
    
    /**
     * Make API request
     */
    public function api_request($endpoint, $method = 'GET', $data = [], $headers = [])
    {
        $base_url = rtrim($this->config['api_endpoint'], '/');
        $full_url = $base_url . '/' . ltrim($endpoint, '/');
        
        $default_headers = [
            'User-Agent' => 'CourierFinancePlugin/1.0',
            'Content-Type' => 'application/json'
        ];
        
        if (!empty($this->config['api_headers'])) {
            $custom_headers = json_decode($this->config['api_headers'], true);
            if (is_array($custom_headers)) {
                $default_headers = array_merge($default_headers, $custom_headers);
            }
        }
        
        $request_headers = array_merge($default_headers, $headers);
        
        $args = [
            'timeout' => $this->config['api_timeout'],
            'headers' => $request_headers,
            'method' => $method
        ];
        
        if (!empty($data)) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($full_url, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }
        
        return [
            'success' => true,
            'status_code' => wp_remote_retrieve_response_code($response),
            'body' => wp_remote_retrieve_body($response),
            'headers' => wp_remote_retrieve_headers($response)
        ];
    }
    
    /**
     * Get connection status
     */
    public function get_connection_status()
    {
        $status = [
            'database' => 'connected', // WordPress database is always connected
            'external_api' => 'not_configured',
            'webhook' => 'not_configured'
        ];
        
        if (!empty($this->config['api_endpoint'])) {
            $api_test = $this->test_api_connection();
            $status['external_api'] = $api_test['success'] ? 'connected' : 'error';
        }
        
        if (!empty($this->config['webhook_url'])) {
            $webhook_test = $this->test_webhook_connection();
            $status['webhook'] = $webhook_test['success'] ? 'connected' : 'error';
        }
        
        return $status;
    }
}
