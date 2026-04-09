<?php
/**
 * CNPJ360 API - Ponto de entrada
 * @version 1.0.0
 * @author Kidion
 */

require_once __DIR__ . '/config/api_config.php';

// Rotas da API
$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Remover prefixo /api/
$path = str_replace('/api/', '', parse_url($request_uri, PHP_URL_PATH));
$path = trim($path, '/');
$segments = explode('/', $path);

// Roteamento
switch ($segments[0]) {
    case '':
    case 'v1':
        // Documentação da API
        api_response([
            'name' => API_NAME,
            'version' => API_VERSION,
            'endpoints' => [
                [
                    'path' => '/api/v1/cnpj/{cnpj}',
                    'method' => 'GET',
                    'description' => 'Consultar CNPJ',
                    'auth' => 'API Key',
                    'example' => '/api/v1/cnpj/12345678000199'
                ],
                [
                    'path' => '/api/v1/empresa/{cnpj}',
                    'method' => 'GET',
                    'description' => 'Buscar empresa no banco local',
                    'auth' => 'API Key'
                ],
                [
                    'path' => '/api/v1/empresa/{cnpj}/socios',
                    'method' => 'GET',
                    'description' => 'Buscar sócios da empresa',
                    'auth' => 'API Key'
                ],
                [
                    'path' => '/api/v1/empresa/{cnpj}/cnaes',
                    'method' => 'GET',
                    'description' => 'Buscar CNAEs da empresa',
                    'auth' => 'API Key'
                ],
                [
                    'path' => '/api/v1/empresa/busca',
                    'method' => 'POST',
                    'description' => 'Busca avançada de empresas',
                    'auth' => 'API Key',
                    'body' => ['cidade', 'uf', 'porte', 'situacao']
                ],
                [
                    'path' => '/api/v1/estatisticas',
                    'method' => 'GET',
                    'description' => 'Estatísticas do sistema',
                    'auth' => 'API Key'
                ]
            ]
        ]);
        break;
        
    case 'v1':
        array_shift($segments);
        $resource = $segments[0] ?? '';
        
        switch ($resource) {
            case 'cnpj':
                require_once __DIR__ . '/v1/cnpj.php';
                break;
            case 'empresa':
            case 'empresas':
                require_once __DIR__ . '/v1/empresas.php';
                break;
            case 'estatisticas':
                require_once __DIR__ . '/v1/estatisticas.php';
                break;
            case 'auth':
                require_once __DIR__ . '/v1/auth.php';
                break;
            default:
                api_error('Endpoint não encontrado', 404);
        }
        break;
        
    default:
        api_error('Endpoint não encontrado', 404);
}