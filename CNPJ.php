<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Classe CNPJ - Sistema completo de consulta de CNPJ
 * @version 5.0.0
 * @author Kidion
 */
class CNPJ {
    private $db;
    private $usuario_id;
    private $cache_ttl = 3600;
    private $timeout = 15;
    private $max_retries = 2;
    private $user_agent;
    private $cnpj_atual;
    
    // Constantes de status
    const STATUS_ATIVA = 'ATIVA';
    const STATUS_SUSPENSA = 'SUSPENSA';
    const STATUS_INAPTA = 'INAPTA';
    const STATUS_BAIXADA = 'BAIXADA';
    const STATUS_NULA = 'NULA';
    
    // Constantes de porte
    const PORTE_MEI = 'Microempreendedor Individual';
    const PORTE_ME = 'Micro Empresa';
    const PORTE_EPP = 'Empresa de Pequeno Porte';
    const PORTE_MEDIO = 'Empresa de Médio Porte';
    const PORTE_GRANDE = 'Grande Empresa';
    
    // Caches em memória
    private static $naturezaCache = [];
    
    /**
     * Construtor
     */
    public function __construct($usuario_id = null) {
        $this->db = Database::getInstance();
        $this->usuario_id = $usuario_id;
        $this->user_agent = 'CNPJ360/5.0 (Sistema de Consulta CNPJ)';
        $this->configurarAmbiente();
    }
    
    /**
     * Configura ambiente
     */
    private function configurarAmbiente(): void {
        $env = getenv('APP_ENV') ?: 'development';
        
        if ($env === 'production') {
            $this->cache_ttl = 7200;
            $this->timeout = 10;
            ini_set('display_errors', 0);
        } else {
            $this->cache_ttl = 300;
            $this->timeout = 20;
            ini_set('display_errors', 1);
        }
    }
    
    /**
     * Limpa formatação do CNPJ
     */
    public function limparCNPJ($cnpj): string {
        return preg_replace('/[^0-9]/', '', (string)$cnpj);
    }
    
    /**
     * Formata CNPJ para exibição
     */
    public function formatarCNPJ($cnpj): string {
        $cnpj = $this->limparCNPJ($cnpj);
        if (strlen($cnpj) === 14) {
            return vsprintf('%s.%s.%s/%s-%s', [
                substr($cnpj, 0, 2),
                substr($cnpj, 2, 3),
                substr($cnpj, 5, 3),
                substr($cnpj, 8, 4),
                substr($cnpj, 12, 2)
            ]);
        }
        return $cnpj;
    }
    
    /**
     * Valida CNPJ com algoritmo completo
     */
    public function validarCNPJ($cnpj): bool {
        $cnpj = $this->limparCNPJ($cnpj);
        
        if (strlen($cnpj) !== 14) return false;
        if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
        
        return $this->validarDigitosVerificadores($cnpj);
    }
    
    /**
     * Valida os dígitos verificadores
     */
    private function validarDigitosVerificadores(string $cnpj): bool {
        $digito1 = $this->calcularDigitoVerificador($cnpj, 12);
        if ($cnpj[12] != $digito1) return false;
        
        $digito2 = $this->calcularDigitoVerificador($cnpj, 13);
        return $cnpj[13] == $digito2;
    }
    
    /**
     * Calcula dígito verificador
     */
    private function calcularDigitoVerificador(string $cnpj, int $tamanho): int {
        $soma = 0;
        $peso = $tamanho == 12 ? 5 : 6;
        
        for ($i = 0; $i < $tamanho; $i++) {
            $soma += (int)$cnpj[$i] * $peso;
            $peso = ($peso == 2) ? 9 : $peso - 1;
        }
        
        $resto = $soma % 11;
        return ($resto < 2) ? 0 : 11 - $resto;
    }
    
    /**
     * Gera UUID v4
     */
    private function gerarUUID(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Gera chave de cache
     */
    private function gerarCacheKey(string $cnpj): string {
        return 'cnpj360_v5_' . md5($cnpj);
    }
    
    /**
     * Obtém dados do cache
     */
    private function obterCache(string $cnpj) {
        if (!function_exists('apcu_fetch')) return null;
        
        $key = $this->gerarCacheKey($cnpj);
        $cached = apcu_fetch($key, $success);
        
        if ($success && $cached) {
            $this->log("Cache hit: {$cnpj}", 'debug');
            return $cached;
        }
        
        return null;
    }
    
    /**
     * Armazena dados no cache
     */
    private function armazenarCache(string $cnpj, $dados): bool {
        if (!function_exists('apcu_store')) return false;
        
        $key = $this->gerarCacheKey($cnpj);
        return apcu_store($key, $dados, $this->cache_ttl);
    }
    
    /**
     * Realiza requisição HTTP otimizada
     */
    private function fazerRequisicao(string $url, ?int $timeout = null, int $tentativa = 1) {
        $timeout = $timeout ?? $this->timeout;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_USERAGENT => $this->user_agent,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: pt-BR,pt;q=0.9',
                'Cache-Control: no-cache'
            ],
            CURLOPT_ENCODING => 'gzip, deflate'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $this->log("Requisição: {$url} - HTTP {$httpCode} (tentativa {$tentativa})", 'debug');
        
        if ($curlError) {
            $this->log("Erro cURL: {$curlError}", 'error');
            if ($tentativa < $this->max_retries) {
                usleep(500000 * $tentativa);
                return $this->fazerRequisicao($url, $timeout, $tentativa + 1);
            }
            return null;
        }
        
        return ($httpCode === 200) ? $response : null;
    }
    
    /**
     * Consulta APIs com failover
     */
    public function consultarMultiplasAPIs(string $cnpj) {
        $apis = $this->getAPIsConfig($cnpj);
        
        foreach ($apis as $api) {
            try {
                $this->log("Tentando API: {$api['nome']}", 'info');
                $response = $this->fazerRequisicao($api['url'], $api['timeout']);
                
                if ($response && $this->validarRespostaAPI($response)) {
                    $dados = json_decode($response, true);
                    if ($this->validarDadosAPI($dados, $api['nome'])) {
                        $this->log("✅ Sucesso na API: {$api['nome']}", 'success');
                        return $this->normalizarDados($dados, $api['nome']);
                    }
                }
            } catch (Exception $e) {
                $this->log("Erro na API {$api['nome']}: " . $e->getMessage(), 'error');
                continue;
            }
        }
        
        return null;
    }
    
    /**
     * Configuração das APIs
     */
    private function getAPIsConfig(string $cnpj): array {
        $cnpj_clean = $this->limparCNPJ($cnpj);
        
        return [
            [
                'nome' => 'BrasilAPI',
                'url' => "https://brasilapi.com.br/api/cnpj/v1/{$cnpj_clean}",
                'timeout' => 10
            ],
            [
                'nome' => 'ReceitaWS',
                'url' => "https://www.receitaws.com.br/v1/cnpj/{$cnpj_clean}",
                'timeout' => 15
            ],
            [
                'nome' => 'MinhaReceita',
                'url' => "https://minhareceita.org/{$cnpj_clean}",
                'timeout' => 10
            ]
        ];
    }
    
    /**
     * Valida resposta da API
     */
    private function validarRespostaAPI(?string $response): bool {
        if (empty($response)) return false;
        
        $dados = json_decode($response, true);
        if (!$dados || !is_array($dados)) return false;
        
        $erros = ['error', 'erro', 'message', 'status'];
        foreach ($erros as $erro) {
            if (isset($dados[$erro]) && in_array($dados[$erro], ['ERROR', 'error', true], true)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valida dados da API
     */
    private function validarDadosAPI(array $dados, string $fonte): bool {
        if (empty($dados)) return false;
        
        $camposObrigatorios = [
            'BrasilAPI' => ['cnpj', 'razao_social'],
            'ReceitaWS' => ['cnpj', 'nome'],
            'MinhaReceita' => ['cnpj', 'razao_social']
        ];
        
        $obrigatorios = $camposObrigatorios[$fonte] ?? ['cnpj'];
        
        foreach ($obrigatorios as $campo) {
            if (empty($dados[$campo])) {
                $this->log("Campo obrigatório '{$campo}' ausente na API {$fonte}", 'warning');
                return false;
            }
        }
        
        $cnpj = $dados['cnpj'] ?? ($dados['cnpj'] ?? '');
        if (!empty($cnpj) && !$this->validarCNPJ($cnpj)) {
            $this->log("CNPJ inválido retornado pela API {$fonte}: {$cnpj}", 'warning');
            return false;
        }
        
        return true;
    }
    
    /**
     * Normaliza dados da API
     */
    private function normalizarDados(array $dados, string $fonte = 'BrasilAPI'): array {
        $campos = $this->mapearCampos($dados, $fonte);
        
        if (empty($campos['cnpj'])) {
            $this->log("CNPJ não encontrado nos dados da API {$fonte}", 'error');
            return [];
        }
        
        $socios = $this->processarSocios($campos['qsa'] ?? []);
        $cnaesSecundarios = $this->processarCnaesSecundarios($campos['cnaes_secundarios'] ?? []);
        
        return [
            'cnpj' => $this->limparCNPJ($campos['cnpj']),
            'razao_social' => $this->sanitizarTexto($campos['razao_social'] ?? ''),
            'nome_fantasia' => $this->sanitizarTexto($campos['nome_fantasia'] ?? ''),
            'data_abertura' => $this->validarData($campos['data_abertura']),
            'situacao_cadastral' => $this->mapearSituacao($campos['situacao_cadastral'] ?? null),
            'data_situacao' => $this->validarData($campos['data_situacao']),
            'capital_social' => (float)($campos['capital_social'] ?? 0),
            'porte_empresa' => $this->mapearPorte($campos['porte'] ?? ''),
            'natureza_juridica_codigo' => $this->validarNaturezaJuridica($campos['natureza_juridica'] ?? ''),
            'cep' => $this->formatarCEP($campos['cep'] ?? ''),
            'logradouro' => $this->sanitizarTexto($campos['logradouro'] ?? ''),
            'numero' => $this->sanitizarTexto($campos['numero'] ?? ''),
            'complemento' => $this->sanitizarTexto($campos['complemento'] ?? ''),
            'bairro' => $this->sanitizarTexto($campos['bairro'] ?? ''),
            'cidade' => $this->sanitizarTexto($campos['cidade'] ?? ''),
            'uf' => strtoupper($campos['uf'] ?? ''),
            'telefone1' => $this->formatarTelefone($campos['telefone1'] ?? ''),
            'telefone2' => $this->formatarTelefone($campos['telefone2'] ?? ''),
            'email' => strtolower($campos['email'] ?? ''),
            'cnae_principal' => $campos['cnae_principal'] ?? '',
            'cnae_secundarios' => json_encode($cnaesSecundarios, JSON_UNESCAPED_UNICODE),
            'socios_json' => json_encode($socios, JSON_UNESCAPED_UNICODE),
            'ultima_consulta_api' => date('Y-m-d H:i:s'),
            'dados_completos' => json_encode($dados, JSON_UNESCAPED_UNICODE),
            'fonte_dados' => $fonte
        ];
    }
    
    /**
     * Mapeia campos de diferentes APIs
     */
    private function mapearCampos(array $dados, string $fonte): array {
        $campos = [
            'cnpj' => $dados['cnpj'] ?? '',
            'razao_social' => $dados['razao_social'] ?? '',
            'nome_fantasia' => $dados['nome_fantasia'] ?? '',
            'data_abertura' => $dados['data_abertura'] ?? null,
            'situacao_cadastral' => $dados['situacao_cadastral'] ?? null,
            'data_situacao' => $dados['data_situacao_cadastral'] ?? null,
            'capital_social' => $dados['capital_social'] ?? 0,
            'porte' => $dados['porte'] ?? '',
            'natureza_juridica' => $dados['natureza_juridica'] ?? '',
            'cep' => $dados['cep'] ?? '',
            'logradouro' => $dados['logradouro'] ?? '',
            'numero' => $dados['numero'] ?? '',
            'complemento' => $dados['complemento'] ?? '',
            'bairro' => $dados['bairro'] ?? '',
            'cidade' => $dados['municipio'] ?? '',
            'uf' => $dados['uf'] ?? '',
            'telefone1' => $dados['ddd_telefone_1'] ?? '',
            'telefone2' => $dados['ddd_telefone_2'] ?? '',
            'email' => $dados['email'] ?? '',
            'cnae_principal' => $dados['cnae_fiscal'] ?? '',
            'cnaes_secundarios' => $dados['cnaes_secundarios'] ?? [],
            'qsa' => $dados['qsa'] ?? []
        ];
        
        // ReceitaWS
        if ($fonte === 'ReceitaWS') {
            $campos = [
                'cnpj' => $dados['cnpj'] ?? '',
                'razao_social' => $dados['nome'] ?? '',
                'nome_fantasia' => $dados['fantasia'] ?? '',
                'data_abertura' => $dados['abertura'] ?? null,
                'situacao_cadastral' => $dados['situacao'] ?? null,
                'data_situacao' => $dados['data_situacao'] ?? null,
                'capital_social' => (float)str_replace(',', '.', str_replace('.', '', $dados['capital_social'] ?? '0')),
                'porte' => $dados['porte'] ?? '',
                'natureza_juridica' => $dados['natureza_juridica'] ?? '',
                'cep' => $dados['cep'] ?? '',
                'logradouro' => $dados['logradouro'] ?? '',
                'numero' => $dados['numero'] ?? '',
                'complemento' => $dados['complemento'] ?? '',
                'bairro' => $dados['bairro'] ?? '',
                'cidade' => $dados['municipio'] ?? '',
                'uf' => $dados['uf'] ?? '',
                'telefone1' => $dados['telefone'] ?? '',
                'telefone2' => '',
                'email' => $dados['email'] ?? '',
                'cnae_principal' => $dados['cnae_fiscal'] ?? '',
                'cnaes_secundarios' => [],
                'qsa' => $dados['qsa'] ?? []
            ];
        }
        
        // MinhaReceita
        if ($fonte === 'MinhaReceita') {
            $campos = [
                'cnpj' => $dados['cnpj'] ?? '',
                'razao_social' => $dados['razao_social'] ?? '',
                'nome_fantasia' => $dados['nome_fantasia'] ?? '',
                'data_abertura' => $dados['data_inicio_atividade'] ?? null,
                'situacao_cadastral' => $dados['situacao_cadastral'] ?? null,
                'data_situacao' => $dados['data_situacao_cadastral'] ?? null,
                'capital_social' => $dados['capital_social'] ?? 0,
                'porte' => $dados['porte'] ?? '',
                'natureza_juridica' => $dados['natureza_juridica'] ?? '',
                'cep' => $dados['cep'] ?? '',
                'logradouro' => $dados['logradouro'] ?? '',
                'numero' => $dados['numero'] ?? '',
                'complemento' => $dados['complemento'] ?? '',
                'bairro' => $dados['bairro'] ?? '',
                'cidade' => $dados['municipio'] ?? '',
                'uf' => $dados['uf'] ?? '',
                'telefone1' => $dados['telefone'] ?? '',
                'telefone2' => $dados['telefone_2'] ?? '',
                'email' => $dados['email'] ?? '',
                'cnae_principal' => $dados['cnae_fiscal'] ?? '',
                'cnaes_secundarios' => $dados['cnaes_secundarios'] ?? [],
                'qsa' => $dados['qsa'] ?? []
            ];
        }
        
        return $campos;
    }
    
    /**
     * Processa sócios
     */
    private function processarSocios(array $qsa): array {
        if (empty($qsa)) return [];
        
        $socios = [];
        foreach ($qsa as $socio) {
            $socios[] = [
                'nome_socio' => $this->sanitizarTexto($socio['nome_socio'] ?? $socio['nome'] ?? 'Não informado'),
                'qualificacao_socio' => $this->sanitizarTexto($socio['qualificacao_socio'] ?? $socio['qualificacao'] ?? $socio['qual'] ?? 'Não informado'),
                'tipo_socio' => $this->determinarTipoSocio($socio['qualificacao_socio'] ?? $socio['qualificacao'] ?? $socio['qual'] ?? ''),
                'data_entrada_sociedade' => $this->validarData($socio['data_entrada_sociedade'] ?? $socio['data_entrada'] ?? null),
                'cnpj_cpf_do_socio' => $this->formatarDocumento($socio['cnpj_cpf_do_socio'] ?? $socio['cnpj_cpf'] ?? $socio['cpf_cnpj'] ?? null),
                'identificador_socio' => $this->mapearIdentificadorSocio($socio['identificador_socio'] ?? null),
                'faixa_etaria' => $socio['faixa_etaria'] ?? null,
                'participacao' => isset($socio['participacao']) ? (float)$socio['participacao'] : null
            ];
        }
        
        return $socios;
    }
    
    /**
     * Processa CNAEs secundários
     */
    private function processarCnaesSecundarios(array $cnaes): array {
        if (empty($cnaes)) return [];
        
        $lista = [];
        foreach ($cnaes as $cnae) {
            $codigo = is_array($cnae) ? ($cnae['codigo'] ?? $cnae['cnae'] ?? '') : $cnae;
            if (!empty($codigo) && strlen($codigo) >= 7) {
                $lista[] = [
                    'codigo' => $codigo,
                    'descricao' => is_array($cnae) ? ($cnae['descricao'] ?? '') : ''
                ];
            }
        }
        
        return $lista;
    }
    
    /**
     * Determina tipo do sócio
     */
    private function determinarTipoSocio(string $qualificacao): string {
        if (empty($qualificacao)) return 'Sócio/Administrador';
        
        $qualificacao = strtolower($this->sanitizarTexto($qualificacao));
        
        if (strpos($qualificacao, 'administrador') !== false && strpos($qualificacao, 'sócio') !== false) {
            return 'Sócio-Administrador';
        }
        if (strpos($qualificacao, 'administrador') !== false) return 'Administrador';
        if (strpos($qualificacao, 'presidente') !== false) return 'Presidente';
        if (strpos($qualificacao, 'diretor') !== false) return 'Diretor';
        if (strpos($qualificacao, 'sócio') !== false) return 'Sócio';
        
        return 'Sócio/Administrador';
    }
    
    /**
     * Mapeia identificador do sócio
     */
    private function mapearIdentificadorSocio($identificador): ?string {
        $mapa = [1 => 'Pessoa Física', 2 => 'Pessoa Jurídica', 3 => 'Estrangeiro'];
        return $mapa[$identificador] ?? null;
    }
    
    /**
     * Mapeia situação cadastral
     */
    private function mapearSituacao($situacao): string {
        $mapa = [
            '01' => self::STATUS_NULA, '02' => self::STATUS_ATIVA,
            '03' => self::STATUS_SUSPENSA, '04' => self::STATUS_INAPTA,
            '08' => self::STATUS_BAIXADA
        ];
        
        $situacao = strtoupper(trim((string)$situacao));
        return $mapa[$situacao] ?? self::STATUS_ATIVA;
    }
    
    /**
     * Mapeia porte da empresa
     */
    private function mapearPorte($porte): string {
        $mapa = [
            '01' => self::PORTE_ME, '03' => self::PORTE_EPP,
            '05' => 'Demais', '07' => self::PORTE_MEDIO,
            '09' => self::PORTE_GRANDE, 'MEI' => self::PORTE_MEI,
            'ME' => self::PORTE_ME, 'EPP' => self::PORTE_EPP,
            'MICROEMPRESA' => self::PORTE_ME
        ];
        
        $porteUpper = strtoupper(trim((string)$porte));
        return $mapa[$porteUpper] ?? ($porte ?: 'Não informado');
    }
    
    /**
     * Formata CEP
     */
    private function formatarCEP(?string $cep): ?string {
        $cep = preg_replace('/[^0-9]/', '', (string)$cep);
        return strlen($cep) === 8 ? $cep : null;
    }
    
    /**
     * Formata telefone
     */
    private function formatarTelefone(?string $telefone): string {
        if (empty($telefone)) return '';
        
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        $len = strlen($telefone);
        
        if ($len === 10) {
            return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6, 4);
        }
        if ($len === 11) {
            return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7, 4);
        }
        
        return $telefone;
    }
    
    /**
     * Formata documento (CPF/CNPJ)
     */
    private function formatarDocumento(?string $documento): ?string {
        if (empty($documento)) return null;
        
        $documento = preg_replace('/[^0-9]/', '', $documento);
        
        if (strlen($documento) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $documento);
        }
        if (strlen($documento) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $documento);
        }
        
        return $documento;
    }
    
    /**
     * Sanitiza texto
     */
    private function sanitizarTexto(?string $texto): string {
        if (empty($texto)) return '';
        
        $texto = trim(preg_replace('/\s+/', ' ', $texto));
        return preg_replace('/[^\p{L}\p{N}\s\.\,\-\/\(\)]/u', '', $texto);
    }
    
    /**
     * Valida e formata data
     */
    private function validarData($data): ?string {
        if (empty($data)) return null;
        
        $formatos = ['Y-m-d', 'd/m/Y', 'Ymd', 'd-m-Y', 'Y-m-d H:i:s'];
        
        foreach ($formatos as $formato) {
            $date = DateTime::createFromFormat($formato, (string)$data);
            if ($date && $date->format($formato) === (string)$data) {
                return $date->format('Y-m-d');
            }
        }
        
        return null;
    }
    
    /**
     * Valida natureza jurídica (com cache em memória)
     */
    private function validarNaturezaJuridica(?string $codigo): ?string {
        if (empty($codigo)) return null;
        
        if (isset(self::$naturezaCache[$codigo])) {
            return self::$naturezaCache[$codigo];
        }
        
        try {
            $sql = "SELECT codigo FROM natureza_juridica WHERE codigo = :codigo";
            $stmt = $this->db->execute($sql, [':codigo' => $codigo]);
            $existe = $stmt->fetch();
            
            if (!$existe) {
                $sql = "INSERT INTO natureza_juridica (codigo, descricao, permite_simples, created_at) 
                        VALUES (:codigo, :descricao, 0, NOW())";
                $this->db->execute($sql, [
                    ':codigo' => $codigo,
                    ':descricao' => "Natureza Jurídica {$codigo}"
                ]);
            }
            
            self::$naturezaCache[$codigo] = $codigo;
            return $codigo;
        } catch (Exception $e) {
            $this->log("Erro natureza jurídica: " . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * Prepara parâmetros para empresa
     */
    private function prepararParamsEmpresa(array $dados, string $cnpj): array {
        return [
            ':cnpj' => $cnpj,
            ':razao_social' => $dados['razao_social'],
            ':nome_fantasia' => $dados['nome_fantasia'],
            ':data_abertura' => $dados['data_abertura'],
            ':situacao_cadastral' => $dados['situacao_cadastral'],
            ':data_situacao' => $dados['data_situacao'],
            ':capital_social' => $dados['capital_social'],
            ':porte_empresa' => $dados['porte_empresa'],
            ':natureza_juridica_codigo' => $dados['natureza_juridica_codigo'],
            ':cep' => $dados['cep'],
            ':logradouro' => $dados['logradouro'],
            ':numero' => $dados['numero'],
            ':complemento' => $dados['complemento'],
            ':bairro' => $dados['bairro'],
            ':cidade' => $dados['cidade'],
            ':uf' => $dados['uf'],
            ':telefone1' => $dados['telefone1'],
            ':telefone2' => $dados['telefone2'],
            ':email' => $dados['email'],
            ':cnae_principal' => $dados['cnae_principal'],
            ':cnae_secundarios' => $dados['cnae_secundarios'],
            ':socios_json' => $dados['socios_json'],
            ':ultima_consulta_api' => $dados['ultima_consulta_api'],
            ':dados_completos' => $dados['dados_completos']
        ];
    }
    
    /**
     * Build SQL de update
     */
    private function buildUpdateSQL(): string {
        return "UPDATE empresas SET 
                razao_social = :razao_social,
                nome_fantasia = :nome_fantasia,
                data_abertura = :data_abertura,
                situacao_cadastral = :situacao_cadastral,
                data_situacao = :data_situacao,
                capital_social = :capital_social,
                porte_empresa = :porte_empresa,
                natureza_juridica_codigo = :natureza_juridica_codigo,
                cep = :cep,
                logradouro = :logradouro,
                numero = :numero,
                complemento = :complemento,
                bairro = :bairro,
                cidade = :cidade,
                uf = :uf,
                telefone1 = :telefone1,
                telefone2 = :telefone2,
                email = :email,
                cnae_principal = :cnae_principal,
                cnae_secundarios = :cnae_secundarios,
                socios_json = :socios_json,
                ultima_consulta_api = :ultima_consulta_api,
                dados_completos = :dados_completos,
                updated_at = NOW()
                WHERE cnpj = :cnpj";
    }
    
    /**
     * Build SQL de insert
     */
    private function buildInsertSQL(): string {
        return "INSERT INTO empresas (
                    cnpj, razao_social, nome_fantasia, data_abertura,
                    situacao_cadastral, data_situacao, capital_social, porte_empresa,
                    natureza_juridica_codigo, cep, logradouro, numero, complemento,
                    bairro, cidade, uf, telefone1, telefone2, email,
                    cnae_principal, cnae_secundarios, socios_json,
                    ultima_consulta_api, dados_completos,
                    regime_tributario_sugerido, pode_optar_simples,
                    created_at, updated_at
                ) VALUES (
                    :cnpj, :razao_social, :nome_fantasia, :data_abertura,
                    :situacao_cadastral, :data_situacao, :capital_social, :porte_empresa,
                    :natureza_juridica_codigo, :cep, :logradouro, :numero, :complemento,
                    :bairro, :cidade, :uf, :telefone1, :telefone2, :email,
                    :cnae_principal, :cnae_secundarios, :socios_json,
                    :ultima_consulta_api, :dados_completos,
                    'indefinido', 1,
                    NOW(), NOW()
                )";
    }
    
    /**
     * Salva empresa no banco
     */
    public function salvarEmpresa(array $dados): int {
        $cnpj = $this->limparCNPJ($dados['cnpj']);
        
        $sql = "SELECT id FROM empresas WHERE cnpj = :cnpj";
        $stmt = $this->db->execute($sql, [':cnpj' => $cnpj]);
        $empresa = $stmt->fetch();
        
        $params = $this->prepararParamsEmpresa($dados, $cnpj);
        
        try {
            $this->db->execute("SET FOREIGN_KEY_CHECKS = 0");
            
            if ($empresa) {
                $sql = $this->buildUpdateSQL();
                $this->db->execute($sql, $params);
                $empresa_id = $empresa['id'];
                $this->log("Empresa atualizada: {$cnpj}", 'info');
            } else {
                $sql = $this->buildInsertSQL();
                $this->db->execute($sql, $params);
                $empresa_id = $this->db->lastInsertId();
                $this->log("Nova empresa inserida: {$cnpj}", 'info');
            }
            
            $this->db->execute("SET FOREIGN_KEY_CHECKS = 1");
            $this->armazenarCache($cnpj, $dados);
            
            if ($this->usuario_id) {
                $this->registrarHistorico($empresa_id);
            }
            
            return $empresa_id;
        } catch (PDOException $e) {
            $this->db->execute("SET FOREIGN_KEY_CHECKS = 1");
            $this->log("Erro ao salvar empresa: " . $e->getMessage(), 'error');
            throw new Exception("Erro ao salvar dados da empresa: " . $e->getMessage());
        }
    }
    
    /**
     * Consulta principal
     */
    public function consultar(string $cnpj): array {
        $cnpj = $this->limparCNPJ($cnpj);
        
        if (!$this->validarCNPJ($cnpj)) {
            return ['error' => 'CNPJ inválido. Verifique os dígitos e tente novamente.'];
        }
        
        // Cache
        $cache = $this->obterCache($cnpj);
        if ($cache) return $cache;
        
        // Banco local
        $empresaLocal = $this->buscarEmpresaLocal($cnpj);
        if ($empresaLocal && !empty($empresaLocal['ultima_consulta_api']) && 
            $empresaLocal['ultima_consulta_api'] > date('Y-m-d H:i:s', strtotime('-30 days'))) {
            return $empresaLocal;
        }
        
        try {
            $dados = $this->consultarBrasilAPI($cnpj);
            
            if (!$dados) {
                $this->log("BrasilAPI falhou, tentando outras APIs...", 'warning');
                $dados = $this->consultarMultiplasAPIs($cnpj);
            }
            
            if (!$dados) {
                return ['error' => 'CNPJ não encontrado. Verifique o número ou tente novamente mais tarde.'];
            }
            
            $empresa_id = $this->salvarEmpresa($dados);
            $dados['empresa_id'] = $empresa_id;
            
            $this->log("Consulta realizada com sucesso: {$cnpj} via {$dados['fonte_dados']}", 'success');
            
            return $dados;
        } catch (Exception $e) {
            $this->log("Erro na consulta: " . $e->getMessage(), 'error');
            return ['error' => 'Erro ao processar consulta. Tente novamente mais tarde.'];
        }
    }
    
    /**
     * Consulta BrasilAPI
     */
    public function consultarBrasilAPI(string $cnpj): ?array {
        $cnpj = $this->limparCNPJ($cnpj);
        $url = "https://brasilapi.com.br/api/cnpj/v1/{$cnpj}";
        
        $response = $this->fazerRequisicao($url, 15);
        
        if ($response) {
            $dados = json_decode($response, true);
            if ($dados && !isset($dados['error'])) {
                return $this->normalizarDados($dados, 'BrasilAPI');
            }
        }
        
        return null;
    }
    
    /**
     * Busca empresa localmente - CORRIGIDO
     */
    public function buscarEmpresaLocal(string $cnpj): ?array {
        $cnpj = $this->limparCNPJ($cnpj);
        
        try {
            $sql = "SELECT * FROM empresas WHERE cnpj = :cnpj";
            $stmt = $this->db->execute($sql, [':cnpj' => $cnpj]);
            $empresa = $stmt->fetch();
            
            if (!$empresa) {
                return null;
            }
            
            // Decodifica JSON
            $empresa['socios'] = json_decode($empresa['socios_json'] ?? '[]', true);
            $empresa['cnaes_secundarios'] = json_decode($empresa['cnae_secundarios'] ?? '[]', true);
            
            // Adiciona campos formatados
            $empresa['cnpj_formatado'] = $this->formatarCNPJ($empresa['cnpj']);
            $empresa['telefone_formatado'] = $this->formatarTelefone($empresa['telefone1'] ?? '');
            $empresa['endereco_completo'] = $this->montarEndereco($empresa);
            
            return $empresa;
        } catch (Exception $e) {
            $this->log("Erro ao buscar empresa local: " . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * Monta endereço completo
     */
    public function montarEndereco(array $empresa): string {
        $partes = array_filter([
            $empresa['logradouro'] ?? null,
            $empresa['numero'] ?? null,
            $empresa['complemento'] ?? null,
            $empresa['bairro'] ?? null,
            $empresa['cidade'] ?? null,
            $empresa['uf'] ?? null,
            !empty($empresa['cep']) ? 'CEP: ' . substr($empresa['cep'], 0, 5) . '-' . substr($empresa['cep'], 5, 3) : null
        ]);
        
        return implode(', ', $partes) ?: 'Não informado';
    }
    
    /**
     * Busca empresas recentes
     */
    public function getEmpresasRecentes(int $limite = 10): array {
        $sql = "SELECT * FROM empresas ORDER BY ultima_consulta_api DESC LIMIT :limite";
        $stmt = $this->db->execute($sql, [':limite' => $limite]);
        
        $empresas = $stmt->fetchAll();
        foreach ($empresas as &$empresa) {
            $empresa['cnpj_formatado'] = $this->formatarCNPJ($empresa['cnpj']);
            $empresa['endereco_completo'] = $this->montarEndereco($empresa);
        }
        
        return $empresas;
    }
    
    /**
     * Registra consulta no histórico
     */
    public function registrarHistorico(int $empresa_id): bool {
        if (!$this->usuario_id) return false;
        
        try {
            $sql = "INSERT INTO historico_consultas (usuario_id, empresa_id, data_consulta) 
                    VALUES (:usuario_id, :empresa_id, NOW())";
            return $this->db->execute($sql, [
                ':usuario_id' => $this->usuario_id,
                ':empresa_id' => $empresa_id
            ]);
        } catch (Exception $e) {
            $this->log("Erro ao registrar histórico: " . $e->getMessage(), 'warning');
            return false;
        }
    }
    
    /**
     * Log de atividades
     */
    private function log(string $mensagem, string $nivel = 'info'): void {
        $niveis = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3, 'success' => 1];
        $nivel_atual = getenv('LOG_LEVEL') ?: 'info';
        
        if ($niveis[$nivel] < $niveis[$nivel_atual]) return;
        
        $log = sprintf('[%s] [%s] %s', date('Y-m-d H:i:s'), strtoupper($nivel), $mensagem);
        error_log($log);
    }
}
?>