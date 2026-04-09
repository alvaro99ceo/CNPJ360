<?php
/**
 * Endpoints para busca de empresas
 */

// Validar API Key
$api_key_data = validate_api_key();
check_rate_limit($api_key_data['id']);

$cnpjObj = new CNPJ($api_key_data['usuario_id'] ?? 0);
$db = Database::getInstance();

// Pegar ação da URL
$acao = $segments[1] ?? '';
$param = $segments[2] ?? '';

switch ($acao) {
    case 'busca':
        // Busca avançada
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_error('Método não permitido. Use POST.', 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $filters = [];
        $params = [];
        
        if (!empty($input['cidade'])) {
            $filters[] = "cidade LIKE :cidade";
            $params[':cidade'] = '%' . $input['cidade'] . '%';
        }
        
        if (!empty($input['uf'])) {
            $filters[] = "uf = :uf";
            $params[':uf'] = $input['uf'];
        }
        
        if (!empty($input['porte'])) {
            $filters[] = "porte_empresa = :porte";
            $params[':porte'] = $input['porte'];
        }
        
        if (!empty($input['situacao'])) {
            $filters[] = "situacao_cadastral = :situacao";
            $params[':situacao'] = $input['situacao'];
        }
        
        if (!empty($input['cnae'])) {
            $filters[] = "cnae_principal LIKE :cnae";
            $params[':cnae'] = '%' . $input['cnae'] . '%';
        }
        
        $limit = min($input['limit'] ?? 20, 100);
        $offset = $input['offset'] ?? 0;
        
        $where = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
        $sql = "SELECT * FROM empresas {$where} LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        try {
            $stmt = $db->execute($sql, $params);
            $empresas = $stmt->fetchAll();
            
            $result = array_map(function($empresa) use ($cnpjObj) {
                return [
                    'id' => $empresa['id'],
                    'cnpj' => $empresa['cnpj'],
                    'cnpj_formatado' => $cnpjObj->formatarCNPJ($empresa['cnpj']),
                    'razao_social' => $empresa['razao_social'],
                    'nome_fantasia' => $empresa['nome_fantasia'],
                    'cidade' => $empresa['cidade'],
                    'uf' => $empresa['uf'],
                    'situacao' => $empresa['situacao_cadastral'],
                    'data_abertura' => $empresa['data_abertura'],
                    'porte' => $empresa['porte_empresa']
                ];
            }, $empresas);
            
            api_response([
                'total' => count($empresas),
                'limit' => $limit,
                'offset' => $offset,
                'empresas' => $result
            ]);
        } catch (Exception $e) {
            api_error('Erro na busca: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'socios':
        // Buscar sócios da empresa
        $cnpj = $param;
        if (empty($cnpj)) {
            api_error('CNPJ é obrigatório', 400);
        }
        
        $empresa = $cnpjObj->buscarEmpresaLocal($cnpj);
        
        if (!$empresa) {
            api_error('Empresa não encontrada', 404);
        }
        
        api_response([
            'cnpj' => $empresa['cnpj'],
            'razao_social' => $empresa['razao_social'],
            'total_socios' => count($empresa['socios']),
            'socios' => $empresa['socios']
        ]);
        break;
        
    case 'cnaes':
        // Buscar CNAEs da empresa
        $cnpj = $param;
        if (empty($cnpj)) {
            api_error('CNPJ é obrigatório', 400);
        }
        
        $empresa = $cnpjObj->buscarEmpresaLocal($cnpj);
        
        if (!$empresa) {
            api_error('Empresa não encontrada', 404);
        }
        
        api_response([
            'cnpj' => $empresa['cnpj'],
            'razao_social' => $empresa['razao_social'],
            'cnae_principal' => $empresa['cnae_principal'],
            'cnaes_secundarios' => json_decode($empresa['cnae_secundarios'] ?? '[]', true)
        ]);
        break;
        
    default:
        // Buscar empresa por CNPJ
        $cnpj = $acao;
        if (empty($cnpj)) {
            api_error('CNPJ é obrigatório', 400);
        }
        
        $empresa = $cnpjObj->buscarEmpresaLocal($cnpj);
        
        if (!$empresa) {
            api_error('Empresa não encontrada', 404);
        }
        
        api_response([
            'id' => $empresa['id'],
            'uuid' => $empresa['uuid'],
            'cnpj' => $empresa['cnpj'],
            'cnpj_formatado' => $cnpjObj->formatarCNPJ($empresa['cnpj']),
            'razao_social' => $empresa['razao_social'],
            'nome_fantasia' => $empresa['nome_fantasia'],
            'data_abertura' => $empresa['data_abertura'],
            'situacao_cadastral' => $empresa['situacao_cadastral'],
            'capital_social' => $empresa['capital_social'],
            'porte' => $empresa['porte_empresa'],
            'endereco' => [
                'logradouro' => $empresa['logradouro'],
                'numero' => $empresa['numero'],
                'complemento' => $empresa['complemento'],
                'bairro' => $empresa['bairro'],
                'cidade' => $empresa['cidade'],
                'uf' => $empresa['uf'],
                'cep' => $empresa['cep'],
                'completo' => $empresa['endereco_completo'] ?? null
            ],
            'contato' => [
                'telefone' => $empresa['telefone1'],
                'telefone2' => $empresa['telefone2'],
                'email' => $empresa['email']
            ],
            'cnae_principal' => $empresa['cnae_principal'],
            'total_socios' => count($empresa['socios']),
            'ultima_consulta' => $empresa['ultima_consulta_api']
        ]);
}