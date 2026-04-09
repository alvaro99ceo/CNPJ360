<?php
/**
 * Endpoints para gerenciamento de API Keys
 */

// Apenas administradores podem criar API Keys
if (!isset($_SESSION['usuario_id']) || !isAdmin($_SESSION['usuario_id'])) {
    api_error('Acesso não autorizado', 401);
}

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Listar API Keys do usuário
        $sql = "SELECT id, api_key, name, status, requests_count, created_at, expires_at, last_used_at 
                FROM api_keys 
                WHERE usuario_id = :usuario_id 
                ORDER BY created_at DESC";
        $stmt = $db->execute($sql, [':usuario_id' => $_SESSION['usuario_id']]);
        $keys = $stmt->fetchAll();
        
        // Ocultar chave completa
        foreach ($keys as &$key) {
            $key['api_key'] = substr($key['api_key'], 0, 8) . '...' . substr($key['api_key'], -8);
        }
        
        api_response(['api_keys' => $keys]);
        break;
        
    case 'POST':
        // Criar nova API Key
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['name'])) {
            api_error('Nome da API Key é obrigatório', 400);
        }
        
        $api_key = bin2hex(random_bytes(32));
        $expires_at = !empty($input['expires_days']) ? date('Y-m-d H:i:s', strtotime("+{$input['expires_days']} days")) : null;
        
        $sql = "INSERT INTO api_keys (usuario_id, api_key, name, expires_at, created_at) 
                VALUES (:usuario_id, :api_key, :name, :expires_at, NOW())";
        
        $db->execute($sql, [
            ':usuario_id' => $_SESSION['usuario_id'],
            ':api_key' => $api_key,
            ':name' => $input['name'],
            ':expires_at' => $expires_at
        ]);
        
        api_response([
            'api_key' => $api_key,
            'message' => 'API Key criada com sucesso. Guarde esta chave, ela não será exibida novamente.'
        ], 201);
        break;
        
    case 'DELETE':
        // Revogar API Key
        $key_id = $segments[2] ?? '';
        if (empty($key_id)) {
            api_error('ID da API Key é obrigatório', 400);
        }
        
        $sql = "UPDATE api_keys SET status = 'revoked' WHERE id = :id AND usuario_id = :usuario_id";
        $db->execute($sql, [
            ':id' => $key_id,
            ':usuario_id' => $_SESSION['usuario_id']
        ]);
        
        api_response(['message' => 'API Key revogada com sucesso']);
        break;
}

function isAdmin($usuario_id) {
    // Verificar se o usuário é administrador
    $db = Database::getInstance();
    $sql = "SELECT is_admin FROM usuarios WHERE id = :id";
    $stmt = $db->execute($sql, [':id' => $usuario_id]);
    $user = $stmt->fetch();
    
    return $user && $user['is_admin'] == 1;
}