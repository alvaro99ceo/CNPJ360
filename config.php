<?php
// config/config.php
require_once __DIR__ . '/database.php';

// Configurações do sistema
define('SITE_NAME', 'CNPJ360');
define('SITE_URL', 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
define('SITE_DOMAIN', $_SERVER['HTTP_HOST']);

// Configurações de API
define('API_BRASILAPI_URL', 'https://brasilapi.com.br/api/cnpj/v1/');
define('API_RECEITAWS_URL', 'https://www.receitaws.com.br/v1/cnpj/');
define('API_RECEITAWS_TOKEN', ''); // Opcional: token para ReceitaWS

// Configurações de cache
define('CACHE_TTL', 86400); // 24 horas em segundos

// Configurações de créditos
define('CREDITOS_CONSULTA_COMPLETA', 1);
define('CREDITOS_CONSULTA_BASICA', 0);

// Configurações de ambiente
define('DEBUG_MODE', true); // Coloque false em produção
define('LOG_ERRORS', true);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Inicia sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função de debug
function debug($data, $exit = false) {
    if (DEBUG_MODE) {
        echo "<pre style='background: #f4f4f4; padding: 10px; border: 1px solid #ddd; margin: 10px;'>";
        print_r($data);
        echo "</pre>";
        if ($exit) exit;
    }
}

// Função para log de erros
function logError($message, $context = []) {
    if (LOG_ERRORS) {
        $logMessage = date('Y-m-d H:i:s') . " - " . $message;
        if (!empty($context)) {
            $logMessage .= " - Context: " . json_encode($context);
        }
        error_log($logMessage);
    }
}

// Inicializa conexão com banco de dados
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    logError("Falha ao conectar ao banco: " . $e->getMessage());
    if (DEBUG_MODE) {
        die("Erro de conexão: " . $e->getMessage());
    } else {
        die("Erro interno do servidor. Contate o administrador.");
    }
}