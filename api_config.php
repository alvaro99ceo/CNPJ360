<?php
/**
 * API Configuration
 * @version 1.0.0
 * @author Kidion
 */

// Configurações de segurança
define('API_VERSION', 'v1');
define('API_NAME', 'CNPJ360 API');
define('API_KEY_HEADER', 'X-API-Key');

// Carregar configurações do banco
require_once __DIR__ . '/../../config/database.php';

// Configuração CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configurações de rate limit
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_TIME', 3600);

// Carregar classe CNPJ
require_once __DIR__ . '/../../includes/CNPJ.php';

/**
 * Função para resposta JSON
 */
function api_response($data, $status = 200, $message = '') {
    http_response_code($status);
    $response = [
        'status' => $status >= 200 && $status < 300 ? 'success' : 'error',
        'code' => $status,
        'timestamp' => date('c'),
        'api_version' => API_VERSION,
        'data' => $data
    ];
    
    if (!empty($message)) {
        $response['message'] = $message;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Função para erro
 */
function api_error($message, $status = 400, $details = null) {
    $error = ['message' => $message];
    if ($details) {
        $error['details'] = $details;
    }
    api_response($error, $status, $message);
}

/**
 * Validação de API Key
 */
function validate_api_key() {
    $headers = getallheaders();
    $api_key = $headers[API_KEY_HEADER] ?? $_GET['api_key'] ?? null;
    
    if (!$api_key) {
        api_error('API Key é obrigatória', 401);
    }
    
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM api_keys WHERE api_key = :api_key AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW())";
        $stmt = $db->execute($sql, [':api_key' => $api_key]);
        $key_data = $stmt->fetch();
        
        if (!$key_data) {
            api_error('API Key inválida ou expirada', 401);
        }
        
        // Atualizar último uso
        $sql = "UPDATE api_keys SET last_used_at = NOW(), requests_count = requests_count + 1 WHERE id = :id";
        $db->execute($sql, [':id' => $key_data['id']]);
        
        return $key_data;
    } catch (Exception $e) {
        api_error('Erro ao validar API Key: ' . $e->getMessage(), 500);
    }
}

/**
 * Rate limiting
 */
function check_rate_limit($api_key_id) {
    if (!function_exists('apcu_fetch')) {
        return true;
    }
    
    $key = "api_rate_limit_{$api_key_id}";
    $requests = apcu_fetch($key, $success);
    
    if ($success && $requests >= RATE_LIMIT_REQUESTS) {
        api_error('Limite de requisições excedido. Aguarde 1 hora.', 429);
    }
    
    if ($success) {
        apcu_store($key, $requests + 1, RATE_LIMIT_TIME);
    } else {
        apcu_store($key, 1, RATE_LIMIT_TIME);
    }
    
    return true;
}