<?php
/**
 * Endpoints para consulta de CNPJ
 */

// Validar API Key
$api_key_data = validate_api_key();
check_rate_limit($api_key_data['id']);

// Pegar CNPJ da URL
$cnpj = $segments[1] ?? '';

if (empty($cnpj)) {
    api_error('CNPJ é obrigatório', 400);
}

// Validar formato do CNPJ
$cnpj_clean = preg_replace('/[^0-9]/', '', $cnpj);
if (strlen($cnpj_clean) !== 14) {
    api_error('CNPJ inválido. Deve conter 14 dígitos.', 400);
}

// Instanciar classe CNPJ
$usuario_id = $api_key_data['usuario_id'] ?? 0;
$cnpjObj = new CNPJ($usuario_id);

try {
    $resultado = $cnpjObj->consultar($cnpj_clean);
    
    if (isset($resultado['error'])) {
        api_error($resultado['error'], 404);
    }
    
    // Formatar resposta
    $response = [
        'cnpj' => $cnpjObj->formatarCNPJ($resultado['cnpj']),
        'cnpj_limpo' => $resultado['cnpj'],
        'razao_social' => $resultado['razao_social'],
        'nome_fantasia' => $resultado['nome_fantasia'] ?? null,
        'data_abertura' => $resultado['data_abertura'],
        'situacao_cadastral' => $resultado['situacao_cadastral'],
        'capital_social' => $resultado['capital_social'],
        'porte' => $resultado['porte_descricao'] ?? $resultado['porte_empresa'],
        'natureza_juridica' => $resultado['natureza_descricao'] ?? null,
        'endereco' => [
            'logradouro' => $resultado['logradouro'],
            'numero' => $resultado['numero'],
            'complemento' => $resultado['complemento'],
            'bairro' => $resultado['bairro'],
            'cidade' => $resultado['cidade'],
            'uf' => $resultado['uf'],
            'cep' => $resultado['cep'],
            'completo' => $resultado['endereco_completo']
        ],
        'contato' => [
            'telefone' => $resultado['telefone_formatado'] ?? $resultado['telefone1'],
            'telefone_raw' => $resultado['telefone1'],
            'email' => $resultado['email']
        ],
        'cnae' => [
            'principal' => $resultado['cnae_principal'],
            'secundarios' => json_decode($resultado['cnae_secundarios'] ?? '[]', true)
        ],
        'socios' => json_decode($resultado['socios_json'] ?? '[]', true),
        'inscricao_estadual' => $resultado['inscricao_estadual'] ?? null,
        'opcao_pelo_simples' => $resultado['opcao_pelo_simples'] ?? null,
        'opcao_pelo_mei' => $resultado['opcao_pelo_mei'] ?? null,
        'fonte_dados' => $resultado['fonte_dados'] ?? 'BrasilAPI',
        'ultima_consulta' => $resultado['ultima_consulta_api']
    ];
    
    api_response($response);
    
} catch (Exception $e) {
    api_error('Erro ao consultar CNPJ: ' . $e->getMessage(), 500);
}