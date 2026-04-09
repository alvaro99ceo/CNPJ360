<?php
/**
 * Gerador de API Keys - Versão Simplificada
 * Acesse: https://kidion.online/cnpj360/api/gerar_api_key.php?senha=admin123
 */

require_once __DIR__ . '/config/api_config.php';

// Configuração de segurança
$senha_acesso = 'admin123';

// Verificar senha
if (!isset($_GET['senha']) || $_GET['senha'] !== $senha_acesso) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acesso Restrito - CNPJ360 API</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .card { border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header bg-white text-center py-4">
                            <i class="fas fa-key fa-3x text-primary"></i>
                            <h3 class="mt-2">Acesso Restrito</h3>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="mb-3">
                                    <label class="form-label">Senha de Acesso</label>
                                    <input type="password" name="senha" class="form-control" required autofocus>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Acessar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit();
}

$db = Database::getInstance();

// Processar criação da API Key
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_POST['usuario_id'] ?? null;
    $name = $_POST['name'] ?? 'API Key ' . date('Y-m-d H:i:s');
    $expires_days = $_POST['expires_days'] ?? 365;
    
    if ($usuario_id) {
        $api_key = 'kidion_' . bin2hex(random_bytes(20));
        $expires_at = $expires_days ? date('Y-m-d H:i:s', strtotime("+{$expires_days} days")) : null;
        
        try {
            $sql = "INSERT INTO api_keys (usuario_id, api_key, name, expires_at, created_at) 
                    VALUES (:usuario_id, :api_key, :name, :expires_at, NOW())";
            
            $db->execute($sql, [
                ':usuario_id' => $usuario_id,
                ':api_key' => $api_key,
                ':name' => $name,
                ':expires_at' => $expires_at
            ]);
            
            $success = true;
            $nova_chave = $api_key;
        } catch (Exception $e) {
            $error = 'Erro ao criar API Key: ' . $e->getMessage();
        }
    } else {
        $error = 'Selecione um usuário';
    }
}

// Buscar usuários
$sql = "SELECT id, nome, email FROM usuarios ORDER BY id ASC";
$stmt = $db->execute($sql);
$usuarios = $stmt->fetchAll();

// Buscar API Keys existentes
$sql = "SELECT ak.*, u.nome as usuario_nome, u.email as usuario_email
        FROM api_keys ak 
        LEFT JOIN usuarios u ON ak.usuario_id = u.id 
        ORDER BY ak.created_at DESC 
        LIMIT 20";
$stmt = $db->execute($sql);
$keys = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de API Keys - CNPJ360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f5f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 0; margin-bottom: 30px; }
        .card { border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .api-key-box { background: #1a202c; border-radius: 12px; padding: 15px; margin: 15px 0; }
        .api-key-text { font-family: monospace; font-size: 14px; word-break: break-all; color: #48bb78; background: #2d3748; padding: 10px; border-radius: 8px; }
        .btn-copy { cursor: pointer; transition: all 0.2s; }
        .btn-copy:hover { transform: scale(1.05); }
        .badge-active { background: #48bb78; }
        .badge-expired { background: #f56565; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h1><i class="fas fa-key me-2"></i> Gerador de API Keys</h1>
                    <p class="lead mb-0">Crie e gerencie chaves de API para integração com o CNPJ360</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="/cnpj360/" class="btn btn-light"><i class="fas fa-home"></i> Voltar</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>✅ API Key criada com sucesso!</strong>
                <div class="mt-2">
                    <label class="fw-bold">Sua chave de API:</label>
                    <div class="input-group mt-1">
                        <input type="text" id="nova-chave" class="form-control font-monospace" value="<?php echo $nova_chave; ?>" readonly>
                        <button class="btn btn-primary" onclick="copiarChave()"><i class="fas fa-copy"></i> Copiar</button>
                    </div>
                    <small class="text-muted"><i class="fas fa-exclamation-triangle text-warning"></i> Guarde esta chave! Ela não será exibida novamente.</small>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-plus-circle text-primary"></i> Criar Nova API Key</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Usuário</label>
                                <select name="usuario_id" class="form-select" required>
                                    <option value="">Selecione um usuário</option>
                                    <?php foreach ($usuarios as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['nome']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nome da API Key</label>
                                <input type="text" name="name" class="form-control" placeholder="Ex: API Produção, Integração ERP" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Validade</label>
                                <select name="expires_days" class="form-select">
                                    <option value="30">30 dias</option>
                                    <option value="90">90 dias</option>
                                    <option value="180">180 dias</option>
                                    <option value="365" selected>1 ano</option>
                                    <option value="">Nunca expirar</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-key me-2"></i> Gerar API Key</button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle text-info"></i> Como Usar</h5>
                    </div>
                    <div class="card-body">
                        <p>Inclua sua API Key no header:</p>
                        <pre class="bg-dark text-light p-2 rounded small">X-API-Key: sua_chave_aqui</pre>
                        <p>Exemplo cURL:</p>
                        <pre class="bg-dark text-light p-2 rounded small">curl -X GET "https://kidion.online/cnpj360/api/v1/cnpj/27865757000102" \
  -H "X-API-Key: sua_chave_aqui"</pre>
                    </div>
                </div>
            </div>
            
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> API Keys Cadastradas</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr><th>Nome</th><th>Chave</th><th>Status</th><th>Usos</th><th>Expira</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($keys as $key): 
                                        $key_preview = substr($key['api_key'], 0, 20) . '...';
                                        $expirada = $key['expires_at'] && strtotime($key['expires_at']) < time();
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($key['name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($key['usuario_nome'] ?? 'N/A'); ?></small></td>
                                        <td><code class="small"><?php echo $key_preview; ?></code></td>
                                        <td><?php if ($key['status'] === 'active' && !$expirada): ?><span class="badge bg-success">Ativa</span><?php elseif ($expirada): ?><span class="badge bg-warning">Expirada</span><?php else: ?><span class="badge bg-danger">Revogada</span><?php endif; ?></td>
                                        <td><?php echo $key['requests_count']; ?></td>
                                        <td><?php echo $key['expires_at'] ? date('d/m/Y', strtotime($key['expires_at'])) : 'Nunca'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($keys)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Nenhuma API Key cadastrada.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copiarChave() {
            const input = document.getElementById('nova-chave');
            if (input) {
                input.select();
                document.execCommand('copy');
                alert('API Key copiada para a área de transferência!');
            }
        }
    </script>
</body>
</html>