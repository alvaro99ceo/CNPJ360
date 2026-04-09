<?php
/**
 * Endpoint de estatísticas
 */

// Validar API Key
$api_key_data = validate_api_key();
check_rate_limit($api_key_data['id']);

try {
    $db = Database::getInstance();
    
    // Total de empresas
    $stmt = $db->execute("SELECT COUNT(*) as total FROM empresas");
    $total_empresas = $stmt->fetch()['total'];
    
    // Empresas por UF
    $stmt = $db->execute("SELECT uf, COUNT(*) as total FROM empresas WHERE uf IS NOT NULL GROUP BY uf ORDER BY total DESC LIMIT 10");
    $por_uf = $stmt->fetchAll();
    
    // Empresas por situação
    $stmt = $db->execute("SELECT situacao_cadastral, COUNT(*) as total FROM empresas WHERE situacao_cadastral IS NOT NULL GROUP BY situacao_cadastral");
    $por_situacao = $stmt->fetchAll();
    
    // Total de consultas hoje
    $stmt = $db->execute("SELECT COUNT(*) as total FROM empresas WHERE DATE(ultima_consulta_api) = CURDATE()");
    $consultas_hoje = $stmt->fetch()['total'];
    
    // Top CNAEs
    $stmt = $db->execute("SELECT cnae_principal, COUNT(*) as total FROM empresas WHERE cnae_principal IS NOT NULL GROUP BY cnae_principal ORDER BY total DESC LIMIT 10");
    $top_cnaes = $stmt->fetchAll();
    
    api_response([
        'total_empresas' => (int)$total_empresas,
        'consultas_hoje' => (int)$consultas_hoje,
        'empresas_por_uf' => $por_uf,
        'empresas_por_situacao' => $por_situacao,
        'top_cnaes' => $top_cnaes,
        'ultima_atualizacao' => date('c')
    ]);
    
} catch (Exception $e) {
    api_error('Erro ao buscar estatísticas: ' . $e->getMessage(), 500);
}