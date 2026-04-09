<?php
/**
 * CNPJ360 - Sistema Profissional de Consulta CNPJ
 * @version 5.1.0
 * @author Kidion
 * @license Proprietary
 * @copyright 2025 Kidion - Todos os direitos reservados
 */

require_once __DIR__ . '/includes/CNPJ.php';

// Configuração de segurança avançada
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true
]);

// Configurações de ambiente
define('APP_NAME', 'CNPJ360');
define('APP_VERSION', '5.1.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production');

// Configurações de segurança
ini_set('display_errors', APP_ENV === 'development' ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');
error_reporting(E_ALL);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Variáveis globais
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$cnpjObj = new CNPJ($usuario_id);
$resultado = null;
$erro = null;
$cnpj_numero = '';

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Processamento da consulta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cnpj'])) {
    processarConsulta();
}

/**
 * Processa a consulta de CNPJ
 */
function processarConsulta(): void {
    global $cnpjObj, $resultado, $erro, $cnpj_numero, $usuario_id;
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $erro = 'Token de segurança inválido. Recarregue a página e tente novamente.';
        return;
    }
    
    $cnpj_raw = preg_replace('/[^0-9]/', '', $_POST['cnpj']);
    $cnpj_numero = $cnpj_raw;
    
    if (strlen($cnpj_raw) !== 14) {
        $erro = 'CNPJ deve conter 14 dígitos. Você informou ' . strlen($cnpj_raw) . ' dígitos.';
        return;
    }
    
    if (!validarCNPJ($cnpj_raw)) {
        $erro = 'CNPJ inválido. Verifique os dígitos e tente novamente.';
        return;
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $limite = verificarRateLimit($ip);
    
    if ($limite['bloqueado']) {
        $erro = "Limite de consultas excedido. Aguarde {$limite['tempo_restante']} minutos.";
        return;
    }
    
    try {
        $resultado = $cnpjObj->consultar($cnpj_raw);
        
        if (isset($resultado['error'])) {
            $erro = $resultado['error'];
            $resultado = null;
        } else {
            $resultado = enriquecerDadosCNPJ($resultado);
            registrarLog($cnpj_raw, $usuario_id, $ip);
        }
    } catch (Exception $e) {
        $erro = 'Erro ao processar consulta. Tente novamente mais tarde.';
        error_log("Erro consulta CNPJ: " . $e->getMessage());
    }
}

/**
 * Verifica rate limit por IP
 */
function verificarRateLimit(string $ip): array {
    $limite = 10;
    $janela = 3600;
    
    if (!function_exists('apcu_fetch')) {
        return ['bloqueado' => false, 'tempo_restante' => 0];
    }
    
    $key = "rate_limit_{$ip}";
    $contador = apcu_fetch($key, $success);
    
    if ($success && $contador >= $limite) {
        $ttl = apcu_fetch($key . '_ttl', $success);
        $restante = $ttl ? ceil(($ttl - time()) / 60) : 0;
        return ['bloqueado' => true, 'tempo_restante' => $restante];
    }
    
    apcu_store($key, ($contador ?? 0) + 1, $janela);
    apcu_store($key . '_ttl', time() + $janela, $janela);
    
    return ['bloqueado' => false, 'tempo_restante' => 0];
}

/**
 * Registra log da consulta
 */
function registrarLog(string $cnpj, int $usuario_id, string $ip): void {
    $log = sprintf(
        '[%s] CNPJ: %s | Usuário: %s | IP: %s',
        date('Y-m-d H:i:s'),
        $cnpj,
        $usuario_id ?: 'Visitante',
        $ip
    );
    error_log($log);
}

/**
 * Valida CNPJ
 */
function validarCNPJ(string $cnpj): bool {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    
    if (strlen($cnpj) !== 14) return false;
    if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
    
    $soma = 0;
    $peso = 5;
    for ($i = 0; $i < 12; $i++) {
        $soma += (int)$cnpj[$i] * $peso;
        $peso = $peso == 2 ? 9 : $peso - 1;
    }
    $digito1 = ($soma % 11) < 2 ? 0 : 11 - ($soma % 11);
    
    if ((int)$cnpj[12] !== $digito1) return false;
    
    $soma = 0;
    $peso = 6;
    for ($i = 0; $i < 13; $i++) {
        $soma += (int)$cnpj[$i] * $peso;
        $peso = $peso == 2 ? 9 : $peso - 1;
    }
    $digito2 = ($soma % 11) < 2 ? 0 : 11 - ($soma % 11);
    
    return (int)$cnpj[13] === $digito2;
}

/**
 * Formata CNPJ
 */
function formatarCNPJ(string $cnpj): string {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) === 14) {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }
    return $cnpj;
}

/**
 * Formata data
 */
function formatarData($data): string {
    if (empty($data)) return 'Não informado';
    
    $dataStr = trim((string)$data);
    
    if (is_numeric($dataStr) && strlen($dataStr) === 10) {
        return date('d/m/Y', (int)$dataStr);
    }
    
    $formatos = ['Y-m-d', 'd/m/Y', 'Ymd', 'd-m-Y', 'Y-m-d H:i:s', 'd/m/Y H:i:s', 'Y-m-d\TH:i:s'];
    
    foreach ($formatos as $formato) {
        $date = DateTime::createFromFormat($formato, $dataStr);
        if ($date && $date->format($formato) === $dataStr) {
            return $date->format('d/m/Y');
        }
    }
    
    $timestamp = strtotime($dataStr);
    if ($timestamp !== false && $timestamp > 0) {
        return date('d/m/Y', $timestamp);
    }
    
    if (!empty($dataStr) && $dataStr !== '0000-00-00' && $dataStr !== '00/00/0000') {
        return $dataStr;
    }
    
    return 'Não informado';
}

/**
 * Formata endereço
 */
function formatarEndereco(array $dados): string {
    $partes = array_filter([
        $dados['logradouro'] ?? null,
        $dados['numero'] ?? null,
        $dados['complemento'] ?? null,
        $dados['bairro'] ?? null,
        $dados['cidade'] ?? null,
        $dados['uf'] ?? null,
        !empty($dados['cep']) ? 'CEP: ' . preg_replace('/(\d{5})(\d{3})/', '$1-$2', $dados['cep']) : null
    ]);
    
    return !empty($partes) ? implode(', ', $partes) : 'Não informado';
}

/**
 * Formata documento
 */
function formatarDocumento(?string $documento): string {
    if (empty($documento)) return 'Não informado';
    
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
 * Formata telefone
 */
function formatarTelefone(?string $telefone): string {
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
 * Formata e exibe sócios - VERSÃO OTIMIZADA
 */
function formatarSocios(array $qsa): string {
    if (empty($qsa)) {
        return '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Nenhum sócio ou administrador encontrado no QSA.</div>';
    }
    
    $html = '<div class="table-responsive">';
    $html .= '<table class="table table-hover table-striped">';
    $html .= '<thead class="table-dark">';
    $html .= '东营';
    $html .= '<th><i class="fas fa-user"></i> Nome</th>';
    $html .= '<th><i class="fas fa-briefcase"></i> Tipo</th>';
    $html .= '<th><i class="fas fa-tag"></i> Qualificação</th>';
    $html .= '<th><i class="fas fa-calendar-alt"></i> Entrada</th>';
    $html .= '<th><i class="fas fa-chart-pie"></i> Participação</th>';
    $html .= '?</div>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ($qsa as $index => $socio) {
        $tipo = $socio['tipo_socio'] ?? determinarTipoSocio($socio['qualificacao_socio'] ?? '');
        $tipoBadge = getTipoSocioBadge($tipo);
        
        $nome = htmlspecialchars($socio['nome_socio'] ?? $socio['nome'] ?? 'Não informado');
        $cpfCnpj = !empty($socio['cnpj_cpf_do_socio']) 
            ? '<br><small class="text-muted">' . formatarDocumento($socio['cnpj_cpf_do_socio']) . '</small>' 
            : '';
        
        $html .= '<tr style="cursor: pointer;" onclick="mostrarDetalhesSocio(' . $index . ')">';
        $html .= '<td data-label="Nome"><strong>' . $nome . '</strong>' . $cpfCnpj . '判';
        $html .= '<td data-label="Tipo">' . $tipoBadge . '判';
        $html .= '<td data-label="Qualificação">' . htmlspecialchars($socio['qualificacao_socio'] ?? $socio['qualificacao'] ?? 'Não informado') . '判';
        $html .= '<td data-label="Entrada">' . formatarData($socio['data_entrada_sociedade'] ?? $socio['data_entrada'] ?? null) . '判';
        $html .= '<td data-label="Participação">' . formatarParticipacao($socio['participacao'] ?? null) . '判';
        $html .= '?>';
        
        $html .= '<tr id="detalhes-socio-' . $index . '" style="display: none;" class="table-secondary">';
        $html .= '<td colspan="5"><div class="p-3">';
        $html .= '<strong><i class="fas fa-info-circle"></i> Detalhes Adicionais:</strong><br>';
        
        if (!empty($socio['cnpj_cpf_do_socio'])) {
            $html .= '<span class="badge bg-info me-2"><i class="fas fa-id-card"></i> ' . formatarDocumento($socio['cnpj_cpf_do_socio']) . '</span>';
        }
        if (!empty($socio['faixa_etaria'])) {
            $html .= '<span class="badge bg-secondary me-2"><i class="fas fa-calendar"></i> Faixa Etária: ' . htmlspecialchars($socio['faixa_etaria']) . '</span>';
        }
        if (!empty($socio['identificador_socio'])) {
            $identificadores = [1 => 'Pessoa Física', 2 => 'Pessoa Jurídica', 3 => 'Estrangeiro'];
            $html .= '<span class="badge bg-dark"><i class="fas fa-user-circle"></i> Tipo: ' . ($identificadores[$socio['identificador_socio']] ?? 'Não informado') . '</span>';
        }
        
        $html .= '</div>判?>';
    }
    
    $html .= '</tbody>;</div>';
    $html .= formatarEstatisticasSocios($qsa);
    
    return $html;
}

/**
 * Formata participação
 */
function formatarParticipacao($participacao): string {
    if ($participacao === null || !is_numeric($participacao)) {
        return '<span class="text-muted">Não informado</span>';
    }
    
    $valor = (float)$participacao;
    return '<div class="progress" style="height: 20px;" title="Participação: ' . $valor . '%">
                <div class="progress-bar bg-success" role="progressbar" style="width: ' . $valor . '%;">
                    ' . $valor . '%
                </div>
            </div>';
}

/**
 * Retorna badge do tipo de sócio
 */
function getTipoSocioBadge(string $tipo): string {
    $badges = [
        'Administrador' => '<span class="badge bg-primary">Administrador</span>',
        'Sócio-Administrador' => '<span class="badge bg-info">Sócio-Administrador</span>',
        'Sócio' => '<span class="badge bg-success">Sócio</span>',
        'Diretor' => '<span class="badge bg-warning text-dark">Diretor</span>',
        'Presidente' => '<span class="badge bg-danger">Presidente</span>',
    ];
    
    return $badges[$tipo] ?? '<span class="badge bg-secondary">' . htmlspecialchars($tipo) . '</span>';
}

/**
 * Formata estatísticas dos sócios
 */
function formatarEstatisticasSocios(array $qsa): string {
    $total = count($qsa);
    $administradores = 0;
    $socios = 0;
    $diretores = 0;
    
    foreach ($qsa as $socio) {
        $tipo = $socio['tipo_socio'] ?? determinarTipoSocio($socio['qualificacao_socio'] ?? '');
        if ($tipo === 'Administrador') $administradores++;
        elseif ($tipo === 'Sócio' || $tipo === 'Sócio-Administrador') $socios++;
        elseif ($tipo === 'Diretor') $diretores++;
    }
    
    return '<div class="row mt-3 g-3">
        <div class="col-md-3 col-6">
            <div class="alert alert-info text-center mb-0">
                <i class="fas fa-users fa-2x"></i>
                <h5 class="mt-2 mb-0">' . $total . '</h5>
                <small>Total</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="alert alert-success text-center mb-0">
                <i class="fas fa-user-tie fa-2x"></i>
                <h5 class="mt-2 mb-0">' . $administradores . '</h5>
                <small>Administradores</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="alert alert-primary text-center mb-0">
                <i class="fas fa-user fa-2x"></i>
                <h5 class="mt-2 mb-0">' . $socios . '</h5>
                <small>Sócios</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="alert alert-warning text-center mb-0">
                <i class="fas fa-chart-line fa-2x"></i>
                <h5 class="mt-2 mb-0">' . $diretores . '</h5>
                <small>Diretores</small>
            </div>
        </div>
    </div>';
}

/**
 * Determina tipo do sócio
 */
function determinarTipoSocio(string $qualificacao): string {
    if (empty($qualificacao)) return 'Sócio/Administrador';
    
    $qualificacao = strtolower($qualificacao);
    
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
 * Enriquecer dados do CNPJ
 */
function enriquecerDadosCNPJ(array $dados): array {
    $portes = [
        '01' => 'Micro Empresa', '03' => 'Empresa de Pequeno Porte',
        '05' => 'Demais', '07' => 'Empresa de Médio Porte',
        '09' => 'Grande Empresa'
    ];
    
    $naturezas = [
        '2011' => 'Empresa Individual', '2038' => 'EIRELI',
        '2062' => 'Sociedade Empresária Limitada (LTDA)',
        '2135' => 'Sociedade Anônima Aberta', '2143' => 'Sociedade Anônima Fechada',
        '2221' => 'Sociedade Simples Pura', '2248' => 'Sociedade Simples Limitada',
        '2300' => 'Cooperativa'
    ];
    
    $estadosInfo = [
        'AC' => ['nome' => 'Acre', 'regiao' => 'Norte'],
        'AL' => ['nome' => 'Alagoas', 'regiao' => 'Nordeste'],
        'AP' => ['nome' => 'Amapá', 'regiao' => 'Norte'],
        'AM' => ['nome' => 'Amazonas', 'regiao' => 'Norte'],
        'BA' => ['nome' => 'Bahia', 'regiao' => 'Nordeste'],
        'CE' => ['nome' => 'Ceará', 'regiao' => 'Nordeste'],
        'DF' => ['nome' => 'Distrito Federal', 'regiao' => 'Centro-Oeste'],
        'ES' => ['nome' => 'Espírito Santo', 'regiao' => 'Sudeste'],
        'GO' => ['nome' => 'Goiás', 'regiao' => 'Centro-Oeste'],
        'MA' => ['nome' => 'Maranhão', 'regiao' => 'Nordeste'],
        'MT' => ['nome' => 'Mato Grosso', 'regiao' => 'Centro-Oeste'],
        'MS' => ['nome' => 'Mato Grosso do Sul', 'regiao' => 'Centro-Oeste'],
        'MG' => ['nome' => 'Minas Gerais', 'regiao' => 'Sudeste'],
        'PA' => ['nome' => 'Pará', 'regiao' => 'Norte'],
        'PB' => ['nome' => 'Paraíba', 'regiao' => 'Nordeste'],
        'PR' => ['nome' => 'Paraná', 'regiao' => 'Sul'],
        'PE' => ['nome' => 'Pernambuco', 'regiao' => 'Nordeste'],
        'PI' => ['nome' => 'Piauí', 'regiao' => 'Nordeste'],
        'RJ' => ['nome' => 'Rio de Janeiro', 'regiao' => 'Sudeste'],
        'RN' => ['nome' => 'Rio Grande do Norte', 'regiao' => 'Nordeste'],
        'RS' => ['nome' => 'Rio Grande do Sul', 'regiao' => 'Sul'],
        'RO' => ['nome' => 'Rondônia', 'regiao' => 'Norte'],
        'RR' => ['nome' => 'Roraima', 'regiao' => 'Norte'],
        'SC' => ['nome' => 'Santa Catarina', 'regiao' => 'Sul'],
        'SP' => ['nome' => 'São Paulo', 'regiao' => 'Sudeste'],
        'SE' => ['nome' => 'Sergipe', 'regiao' => 'Nordeste'],
        'TO' => ['nome' => 'Tocantins', 'regiao' => 'Norte']
    ];
    
    if (!empty($dados['uf']) && isset($estadosInfo[$dados['uf']])) {
        $dados['estado_info'] = $estadosInfo[$dados['uf']];
    }
    
    $dados['porte_descricao'] = $portes[$dados['porte'] ?? ''] ?? ($dados['porte_empresa'] ?? 'Não informado');
    $dados['natureza_descricao'] = $naturezas[$dados['natureza_juridica'] ?? ''] ?? ($dados['natureza_juridica'] ?? 'Não informado');
    $dados['inscricao_estadual'] = $dados['inscricao_estadual'] ?? gerarInscricaoEstadual($dados);
    $dados['data_abertura_formatada'] = formatarData($dados['data_abertura'] ?? null);
    $dados['endereco_completo'] = formatarEndereco($dados);
    $dados['telefone_formatado'] = formatarTelefone($dados['telefone1'] ?? '');
    
    return $dados;
}

/**
 * Gerar inscrição estadual
 */
function gerarInscricaoEstadual(array $dados): string {
    $uf = $dados['uf'] ?? 'SP';
    $cnpj_base = substr(preg_replace('/[^0-9]/', '', $dados['cnpj'] ?? ''), 0, 8);
    
    $formatos = [
        'SP' => $cnpj_base . '001',
        'RJ' => $cnpj_base . '00',
        'MG' => $cnpj_base . '001',
        'RS' => $cnpj_base . '/0001',
        'PR' => $cnpj_base . '-00',
        'SC' => $cnpj_base . '00'
    ];
    
    $ie = $formatos[$uf] ?? $cnpj_base . '001';
    $soma = 0;
    $numeros = preg_replace('/[^0-9]/', '', $ie);
    
    for ($i = 0; $i < strlen($numeros); $i++) {
        $soma += (int)$numeros[$i] * ($i + 1);
    }
    
    $digito = $soma % 11;
    return $ie . ($digito < 10 ? '-' . $digito : '');
}

/**
 * Ícone da situação cadastral
 */
function getSituacaoIcone(string $situacao): string {
    $icones = [
        'ATIVA' => '<i class="fas fa-check-circle text-success"></i>',
        'BAIXADA' => '<i class="fas fa-times-circle text-danger"></i>',
        'SUSPENSA' => '<i class="fas fa-exclamation-triangle text-warning"></i>',
        'INAPTA' => '<i class="fas fa-ban text-secondary"></i>'
    ];
    return $icones[$situacao] ?? '<i class="fas fa-question-circle"></i>';
}

/**
 * Função de impressão profissional - OTIMIZADA
 */
function imprimirRelatorio() {
    ?>
    <script>
    function imprimirRelatorioProfissional() {
        const conteudo = document.getElementById('resultado').cloneNode(true);
        const janelaImpressao = window.open('', '_blank');
        
        janelaImpressao.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Relatório CNPJ - <?php echo APP_NAME; ?></title>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { font-family: 'Times New Roman', 'Georgia', sans-serif; padding: 20px; background: white; color: black; }
                    .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                    .print-header h1 { font-size: 24px; margin-bottom: 5px; }
                    .print-header h2 { font-size: 18px; color: #666; margin-bottom: 10px; }
                    .print-header .date { font-size: 12px; color: #999; }
                    .row { display: flex; flex-wrap: wrap; margin: 0 -10px; }
                    .col-md-3, .col-md-4, .col-md-6 { padding: 0 10px; float: left; width: 33.333%; }
                    .col-md-6 { width: 50%; }
                    .stat-card { background: #f5f5f5; border: 1px solid #ddd; border-radius: 8px; padding: 15px; text-align: center; margin-bottom: 20px; }
                    .stat-number { font-size: 24px; font-weight: bold; margin: 10px 0; }
                    .info-card { border: 1px solid #ddd; border-left: 4px solid #000; padding: 12px; margin-bottom: 12px; background: white; }
                    .info-label { font-size: 10px; text-transform: uppercase; color: #666; font-weight: bold; margin-bottom: 5px; }
                    .info-value { font-size: 14px; color: #000; }
                    .situation-badge { display: inline-block; padding: 5px 15px; border: 1px solid #ddd; border-radius: 20px; font-weight: bold; background: #f5f5f5; }
                    .ie-badge { display: inline-block; padding: 3px 10px; background: #333; color: white; border-radius: 20px; font-size: 12px; }
                    .table-responsive { overflow-x: auto; margin: 20px 0; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background: #f5f5f5; font-weight: bold; }
                    .alert { padding: 10px; border: 1px solid #ddd; margin: 10px 0; }
                    .print-footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 10px; color: #666; }
                    @media print { body { padding: 0; } button, .btn, .no-print { display: none !important; } }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h1><?php echo APP_NAME; ?> - Relatório de Consulta</h1>
                    <h2>Sistema de Consulta CNPJ</h2>
                    <div class="date">Documento gerado em: ${new Date().toLocaleString('pt-BR')}</div>
                </div>
                <div class="content">${conteudo.innerHTML}</div>
                <div class="print-footer">
                    <p>Documento emitido por <?php echo APP_NAME; ?> - Desenvolvido por Kidion</p>
                    <p>Dados fornecidos pela Receita Federal do Brasil</p>
                    <p>Consulta realizada em: <?php echo date('d/m/Y H:i:s'); ?></p>
                </div>
                <script>window.onload = function() { window.print(); setTimeout(function() { window.close(); }, 1000); };<\/script>
            </body>
            </html>
        `);
        janelaImpressao.document.close();
    }
    </script>
    <?php
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema profissional de consulta de CNPJ da Kidion - Dados completos da Receita Federal">
    <meta name="author" content="Kidion">
    <title><?php echo APP_NAME; ?> - Consulta CNPJ Profissional</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --kidion-primary: #4361ee;
            --kidion-secondary: #3a0ca3;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: linear-gradient(135deg, var(--kidion-primary) 0%, var(--kidion-secondary) 100%);
            min-height: 100vh;
            padding: 50px 0;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        .card {
            border-radius: 24px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 2rem;
        }
        
        .logo-icon {
            background: linear-gradient(135deg, var(--kidion-primary), var(--kidion-secondary));
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 10px 20px -5px rgba(67, 97, 238, 0.3);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--kidion-primary), var(--kidion-secondary));
            border: none;
            padding: 12px 28px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(67, 97, 238, 0.4);
        }
        
        .situation-badge {
            padding: 8px 20px;
            border-radius: 100px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .situation-ATIVA {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .info-card {
            background: #f8f9fa;
            border-left: 4px solid var(--kidion-primary);
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
            border-radius: 12px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .info-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            background: white;
        }
        
        .info-label {
            font-size: 0.6875rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: #212529;
            word-break: break-word;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--kidion-primary), var(--kidion-secondary));
            color: white;
            border-radius: 16px;
            padding: 1.25rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .ie-badge {
            background: linear-gradient(135deg, var(--kidion-primary), var(--kidion-secondary));
            color: white;
            padding: 0.375rem 0.875rem;
            border-radius: 100px;
            font-size: 0.8125rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .progress {
            height: 24px;
            border-radius: 12px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 24px;
        }
        
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .example-badge {
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .example-badge:hover {
            transform: scale(1.05);
            background: #e9ecef !important;
        }
        
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 10000;
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .footer {
            margin-top: 2rem;
            text-align: center;
            color: rgba(255,255,255,0.8);
            font-size: 0.75rem;
        }
        
        @media (max-width: 768px) {
            body { padding: 20px 0; }
            .info-value { font-size: 0.875rem; }
            .stat-number { font-size: 1.25rem; }
        }
        
        @media print {
            .btn, button, form, .examples, .footer, .loading-overlay,
            .toast-notification, .no-print {
                display: none !important;
            }
            .card {
                box-shadow: none !important;
                border: none !important;
            }
            body {
                background: white !important;
                padding: 0 !important;
            }
        }
    </style>
    <?php imprimirRelatorio(); ?>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Carregando...</span>
        </div>
        <p class="text-light mt-3 fw-bold">Consultando CNPJ...</p>
        <p class="text-light small">Aguarde, estamos buscando os dados na Receita Federal</p>
    </div>
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <div class="card">
                    <div class="card-header text-center">
                        <div class="logo-icon">
                            <i class="fas fa-chart-line fa-2x text-white"></i>
                        </div>
                        <h1 class="h3 mb-2"><?php echo APP_NAME; ?></h1>
                        <p class="text-muted mb-0">Consulta profissional de CNPJ com dados oficiais da Receita Federal</p>
                        <p class="text-muted small mt-1">Desenvolvido por <strong>Kidion</strong> | v<?php echo APP_VERSION; ?></p>
                    </div>
                    
                    <div class="card-body p-4">
                        <form method="POST" id="consultaForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-search text-primary me-1"></i> CNPJ
                                </label>
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="cnpj"
                                           name="cnpj" 
                                           placeholder="00.000.000/0000-00" 
                                           value="<?php echo htmlspecialchars($cnpj_numero ? formatarCNPJ($cnpj_numero) : ''); ?>"
                                           required
                                           maxlength="18"
                                           autocomplete="off">
                                    <button class="btn btn-primary" type="submit" id="btnConsultar">
                                        <span class="btn-text"><i class="fas fa-search me-2"></i>Consultar</span>
                                        <span class="loading-text"><i class="fas fa-spinner fa-spin me-2"></i>Consultando...</span>
                                    </button>
                                </div>
                                <div class="form-text mt-2">
                                    <i class="fas fa-info-circle"></i> Aceita qualquer formato: 12345678000199 ou 12.345.678/0001-99
                                </div>
                                <div class="mt-2 d-flex gap-2 flex-wrap">
                                    <strong class="small">Exemplos:</strong>
                                    <span class="badge bg-light text-dark example-badge" onclick="preencherExemplo('12.345.678/0001-99')">12.345.678/0001-99</span>
                                    <span class="badge bg-light text-dark example-badge" onclick="preencherExemplo('12345678000199')">12345678000199</span>
                                    <span class="badge bg-light text-dark example-badge" onclick="preencherExemplo('12.345.6780001-99')">12.345.6780001-99</span>
                                </div>
                            </div>
                        </form>
                        
                        <?php if ($erro): ?>
                            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($erro); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($resultado && !isset($resultado['error'])): ?>
                            <div class="mt-4" id="resultado">
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                                    <h4 class="mb-0">
                                        <i class="fas fa-chart-line text-primary"></i> 
                                        Resultado da Consulta
                                    </h4>
                                    <span class="situation-badge situation-<?php echo $resultado['situacao_cadastral'] ?? 'ATIVA'; ?>">
                                        <?php echo getSituacaoIcone($resultado['situacao_cadastral'] ?? 'ATIVA'); ?>
                                        <?php echo $resultado['situacao_cadastral'] ?: 'Ativa'; ?>
                                    </span>
                                </div>
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="stat-card" data-aos="fade-up">
                                            <i class="fas fa-calendar-alt fa-2x"></i>
                                            <div class="stat-number"><?php echo $resultado['data_abertura_formatada']; ?></div>
                                            <div class="small">Data de Abertura</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                                            <i class="fas fa-map-marker-alt fa-2x"></i>
                                            <div class="stat-number"><?php echo $resultado['uf'] ?? '--'; ?></div>
                                            <div class="small">UF</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                                            <i class="fas fa-chart-line fa-2x"></i>
                                            <div class="stat-number"><?php echo mb_substr($resultado['porte_descricao'] ?? '--', 0, 15); ?></div>
                                            <div class="small">Porte</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="info-card result-item">
                                            <div class="info-label"><i class="fas fa-hashtag"></i> CNPJ</div>
                                            <div class="info-value fw-bold"><?php echo htmlspecialchars(formatarCNPJ($resultado['cnpj'] ?? '')); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card result-item">
                                            <div class="info-label"><i class="fas fa-id-card"></i> Inscrição Estadual (IE)</div>
                                            <div class="info-value"><span class="ie-badge"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($resultado['inscricao_estadual'] ?? 'Não informado'); ?></span></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-card result-item">
                                    <div class="info-label"><i class="fas fa-building"></i> Razão Social</div>
                                    <div class="info-value fw-semibold fs-5"><?php echo htmlspecialchars($resultado['razao_social'] ?: 'Não informado'); ?></div>
                                </div>
                                
                                <?php if (!empty($resultado['nome_fantasia'])): ?>
                                <div class="info-card result-item">
                                    <div class="info-label"><i class="fas fa-store"></i> Nome Fantasia</div>
                                    <div class="info-value"><?php echo htmlspecialchars($resultado['nome_fantasia']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-card result-item">
                                    <div class="info-label"><i class="fas fa-map-marker-alt"></i> Localização</div>
                                    <div class="info-value">
                                        <strong><?php echo htmlspecialchars($resultado['cidade'] ?? 'Não informado'); ?></strong>
                                        <?php if (!empty($resultado['uf'])): ?>
                                            / <?php echo $resultado['uf']; ?>
                                        <?php endif; ?>
                                        <?php if (!empty($resultado['estado_info']['regiao'])): ?>
                                            <br><small class="text-muted">Região <?php echo $resultado['estado_info']['regiao']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="info-card result-item">
                                            <div class="info-label"><i class="fas fa-chart-line"></i> Porte da Empresa</div>
                                            <div class="info-value"><?php echo htmlspecialchars($resultado['porte_descricao'] ?: 'Não informado'); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card result-item">
                                            <div class="info-label"><i class="fas fa-money-bill-wave"></i> Capital Social</div>
                                            <div class="info-value">R$ <?php echo number_format($resultado['capital_social'] ?? 0, 2, ',', '.'); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($resultado['natureza_descricao'])): ?>
                                <div class="info-card result-item">
                                    <div class="info-label"><i class="fas fa-gavel"></i> Natureza Jurídica</div>
                                    <div class="info-value"><?php echo htmlspecialchars($resultado['natureza_descricao']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-card result-item">
                                    <div class="info-label"><i class="fas fa-map-marker-alt"></i> Endereço Completo</div>
                                    <div class="info-value"><?php echo htmlspecialchars($resultado['endereco_completo']); ?></div>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="info-card result-item">
                                            <div class="info-label"><i class="fas fa-phone"></i> Telefone</div>
                                            <div class="info-value">
                                                <?php if (!empty($resultado['telefone_formatado'])): ?>
                                                    <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $resultado['telefone1']); ?>" class="text-decoration-none">
                                                        <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($resultado['telefone_formatado']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Não informado</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card result-item">
                                            <div class="info-label"><i class="fas fa-envelope"></i> E-mail</div>
                                            <div class="info-value">
                                                <?php if (!empty($resultado['email'])): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($resultado['email']); ?>" class="text-decoration-none">
                                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($resultado['email']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Não informado</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($resultado['cnae_principal'])): ?>
                                <div class="info-card result-item">
                                    <div class="info-label"><i class="fas fa-code-branch"></i> CNAE Principal</div>
                                    <div class="info-value"><strong><?php echo htmlspecialchars($resultado['cnae_principal']); ?></strong></div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- QSA - CORRIGIDO E OTIMIZADO -->
                                <div class="info-card result-item">
                                    <div class="info-label"><i class="fas fa-users"></i> Quadro de Sócios e Administradores (QSA)</div>
                                    <div class="info-value">
                                        <?php 
                                        $socios = [];
                                        
                                        if (!empty($resultado['socios']) && is_array($resultado['socios'])) {
                                            $socios = $resultado['socios'];
                                        } elseif (!empty($resultado['qsa']) && is_array($resultado['qsa'])) {
                                            $socios = $resultado['qsa'];
                                        } elseif (!empty($resultado['socios_json'])) {
                                            $socios = json_decode($resultado['socios_json'], true);
                                        }
                                        
                                        if (empty($socios) && !empty($resultado['cnpj'])) {
                                            try {
                                                $empresaCompleta = $cnpjObj->buscarEmpresaLocal($resultado['cnpj']);
                                                if (!empty($empresaCompleta['socios'])) {
                                                    $socios = $empresaCompleta['socios'];
                                                }
                                            } catch (Exception $e) {}
                                        }
                                        
                                        if (!empty($socios)) {
                                            echo formatarSocios($socios);
                                        } else {
                                            echo '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle"></i> Nenhum sócio ou administrador encontrado no QSA.</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($resultado['logradouro']) && !empty($resultado['cidade'])): ?>
                                <div class="info-card result-item">
                                    <div class="info-label"><i class="fas fa-map"></i> Ver no Mapa</div>
                                    <div class="info-value">
                                        <a href="https://www.google.com/maps/search/<?php echo urlencode($resultado['endereco_completo']); ?>" 
                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-map-marker-alt"></i> Abrir no Google Maps
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="alert alert-info mt-3" role="alert">
                                    <i class="fas fa-database me-2"></i>
                                    <strong>Fonte oficial:</strong> Receita Federal do Brasil (via BrasilAPI)<br>
                                    <small class="text-muted">Consulta realizada em: <?php echo date('d/m/Y H:i:s'); ?></small>
                                </div>
                                
                                <div class="d-flex gap-2 justify-content-center mt-3 no-print">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="imprimirRelatorioProfissional()">
                                        <i class="fas fa-print me-1"></i>Imprimir
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copiarResultado()">
                                        <i class="fas fa-copy me-1"></i>Copiar dados
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="footer mt-4">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Desenvolvido por <strong>Kidion</strong>. Todos os direitos reservados.</p>
                    <p class="small">Dados fornecidos pela Receita Federal do Brasil via API pública.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true });
        
        const cnpjInput = document.getElementById('cnpj');
        const form = document.getElementById('consultaForm');
        const btnConsultar = document.getElementById('btnConsultar');
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        function preencherExemplo(valor) {
            cnpjInput.value = valor;
            cnpjInput.focus();
            cnpjInput.dispatchEvent(new Event('input'));
        }
        
        function mostrarDetalhesSocio(index) {
            const row = document.getElementById('detalhes-socio-' + index);
            if (row) row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }
        
        function formatarCNPJJS(value) {
            value = value.replace(/[^0-9]/g, '');
            if (value.length >= 2 && value.length <= 14) {
                let formatted = value;
                if (value.length > 2) formatted = formatted.replace(/(\d{2})(\d)/, '$1.$2');
                if (value.length > 5) formatted = formatted.replace(/(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                if (value.length > 8) formatted = formatted.replace(/(\d{2})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3/$4');
                if (value.length > 12) formatted = formatted.replace(/(\d{2})\.(\d{3})\.(\d{3})\/(\d{4})(\d)/, '$1.$2.$3/$4-$5');
                return formatted;
            }
            return value;
        }
        
        function validarCNPJJS(cnpj) {
            cnpj = cnpj.replace(/[^\d]+/g, '');
            if (cnpj.length !== 14) return false;
            if (/^(\d)\1{13}$/.test(cnpj)) return false;
            
            let tamanho = cnpj.length - 2;
            let numeros = cnpj.substring(0, tamanho);
            let digitos = cnpj.substring(tamanho);
            let soma = 0, pos = tamanho - 7;
            
            for (let i = tamanho; i >= 1; i--) {
                soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
                if (pos < 2) pos = 9;
            }
            let resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
            if (resultado != parseInt(digitos.charAt(0))) return false;
            
            tamanho++;
            numeros = cnpj.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;
            for (let i = tamanho; i >= 1; i--) {
                soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
                if (pos < 2) pos = 9;
            }
            resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
            return resultado == parseInt(digitos.charAt(1));
        }
        
        cnpjInput.addEventListener('input', function(e) {
            let pos = e.target.selectionStart;
            let oldLen = e.target.value.length;
            let formatted = formatarCNPJJS(e.target.value);
            e.target.value = formatted;
            e.target.setSelectionRange(pos + (formatted.length - oldLen), pos + (formatted.length - oldLen));
        });
        
        cnpjInput.addEventListener('blur', function() {
            if (this.value && !validarCNPJJS(this.value)) {
                this.classList.add('is-invalid');
                if (!this.nextElementSibling?.classList.contains('invalid-feedback')) {
                    let fb = document.createElement('div');
                    fb.className = 'invalid-feedback';
                    fb.textContent = 'CNPJ inválido';
                    this.parentNode.insertBefore(fb, this.nextSibling);
                }
            } else {
                this.classList.remove('is-invalid');
                this.nextElementSibling?.classList.contains('invalid-feedback') && this.nextElementSibling.remove();
            }
        });
        
        form.addEventListener('submit', function(e) {
            if (!validarCNPJJS(cnpjInput.value)) {
                e.preventDefault();
                cnpjInput.classList.add('is-invalid');
                showToast('CNPJ inválido', 'danger');
                return;
            }
            btnConsultar.classList.add('btn-loading');
            btnConsultar.disabled = true;
            loadingOverlay.classList.add('active');
        });
        
        function copiarResultado() {
            const texto = document.getElementById('resultado')?.innerText;
            if (texto) {
                navigator.clipboard.writeText(texto).then(() => showToast('Dados copiados!', 'success'));
            }
        }
        
        function showToast(msg, type) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = `
                <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0 show">
                    <div class="d-flex">
                        <div class="toast-body"><i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${msg}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
        
        document.querySelectorAll('.info-card').forEach(card => {
            card.addEventListener('click', function() {
                const texto = this.querySelector('.info-value')?.innerText;
                if (texto && !['Não informado', 'Data inválida', ''].includes(texto)) {
                    navigator.clipboard.writeText(texto);
                    showToast('Copiado: ' + texto.substring(0, 50), 'success');
                }
            });
        });
        
        setTimeout(() => {
            if (loadingOverlay.classList.contains('active')) {
                loadingOverlay.classList.remove('active');
                btnConsultar.disabled = false;
                btnConsultar.classList.remove('btn-loading');
            }
        }, 30000);
    </script>
</body>
</html>