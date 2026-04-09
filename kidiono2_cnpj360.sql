-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 09/04/2026 às 17:03
-- Versão do servidor: 10.11.16-MariaDB
-- Versão do PHP: 8.4.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `kidiono2_cnpj360`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `alertas_empresas`
--

CREATE TABLE `alertas_empresas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `tipo_alerta` enum('mudanca_cadastral','alteracao_cnae','mudanca_situacao','vencimento') NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `ultimo_disparo` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `aliquotas_municipais`
--

CREATE TABLE `aliquotas_municipais` (
  `id` int(11) NOT NULL,
  `uf` char(2) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `codigo_ibge` int(11) DEFAULT NULL,
  `iss_item_lista` varchar(10) NOT NULL,
  `aliquota` decimal(5,2) NOT NULL,
  `data_vigencia` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','revoked','expired') DEFAULT 'active',
  `requests_count` int(11) DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `api_keys`
--

INSERT INTO `api_keys` (`id`, `usuario_id`, `api_key`, `name`, `status`, `requests_count`, `expires_at`, `last_used_at`, `created_at`, `updated_at`) VALUES
(3, 1, 'kidion_master_key_2024_001', 'Master API Key', 'active', 0, '2027-03-25 16:35:49', NULL, '2026-03-25 19:35:49', '2026-03-25 19:35:49');

-- --------------------------------------------------------

--
-- Estrutura para tabela `assinaturas`
--

CREATE TABLE `assinaturas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `plano_id` int(11) NOT NULL,
  `tipo_periodo` enum('mensal','anual') NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `status` enum('ativa','cancelada','expirada','trial') DEFAULT 'ativa',
  `pagamento_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cache_cnpj`
--

CREATE TABLE `cache_cnpj` (
  `cnpj` varchar(14) NOT NULL,
  `dados_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`dados_json`)),
  `api_origem` varchar(50) DEFAULT NULL,
  `consultado_em` timestamp NULL DEFAULT current_timestamp(),
  `expira_em` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cnaes`
--

CREATE TABLE `cnaes` (
  `id` int(11) NOT NULL,
  `codigo` varchar(7) NOT NULL,
  `descricao` text NOT NULL,
  `descricao_completa` text DEFAULT NULL,
  `grupo` varchar(100) DEFAULT NULL,
  `divisao` varchar(100) DEFAULT NULL,
  `secao` char(1) DEFAULT NULL,
  `risco_fiscal` enum('baixo','medio','alto') DEFAULT 'medio',
  `atividades_regulamentadas` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cnaes`
--

INSERT INTO `cnaes` (`id`, `codigo`, `descricao`, `descricao_completa`, `grupo`, `divisao`, `secao`, `risco_fiscal`, `atividades_regulamentadas`, `created_at`) VALUES
(1, '6201501', 'Desenvolvimento de programas de computador sob encomenda', NULL, NULL, NULL, 'J', 'baixo', 0, '2026-03-17 01:56:26'),
(2, '6202300', 'Desenvolvimento e licenciamento de programas de computador customizáveis', NULL, NULL, NULL, 'J', 'baixo', 0, '2026-03-17 01:56:26'),
(3, '6203100', 'Desenvolvimento e licenciamento de programas de computador não-customizáveis', NULL, NULL, NULL, 'J', 'baixo', 0, '2026-03-17 01:56:26'),
(4, '6204000', 'Consultoria em tecnologia da informação', NULL, NULL, NULL, 'J', 'baixo', 0, '2026-03-17 01:56:26'),
(5, '8599604', 'Cursos de idiomas', NULL, NULL, NULL, 'P', 'medio', 0, '2026-03-17 01:56:26'),
(6, '5611203', 'Lanchonetes, casas de chá, de sucos e similares', NULL, NULL, NULL, 'I', 'medio', 0, '2026-03-17 01:56:26'),
(7, '9430800', 'CNAE 9430800 - Aguardando descrição completa', NULL, NULL, NULL, NULL, 'medio', 0, '2026-03-23 20:59:12'),
(8, '6209100', 'CNAE 6209100 - Aguardando descrição completa', NULL, NULL, NULL, NULL, 'medio', 0, '2026-03-24 18:24:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cnae_anexo_simples`
--

CREATE TABLE `cnae_anexo_simples` (
  `cnae_codigo` varchar(7) NOT NULL,
  `anexo` enum('I','II','III','IV','V') NOT NULL,
  `possibilidade_fator_r` tinyint(1) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cnae_impacto_reforma`
--

CREATE TABLE `cnae_impacto_reforma` (
  `cnae_codigo` varchar(7) NOT NULL,
  `impacto_esperado` enum('positivo','neutro','negativo') NOT NULL,
  `justificativa` text DEFAULT NULL,
  `aliquota_ibs_estimada` decimal(5,2) DEFAULT NULL,
  `aliquota_cbs_estimada` decimal(5,2) DEFAULT NULL,
  `geracao_creditos` enum('alta','media','baixa') DEFAULT 'media',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cnae_servico_iss`
--

CREATE TABLE `cnae_servico_iss` (
  `id` int(11) NOT NULL,
  `cnae_codigo` varchar(7) NOT NULL,
  `iss_item_lista` varchar(10) NOT NULL,
  `confiabilidade` enum('alta','media','baixa') DEFAULT 'alta',
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `codigos_servico_iss`
--

CREATE TABLE `codigos_servico_iss` (
  `id` int(11) NOT NULL,
  `item_lista` varchar(10) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `descricao` text NOT NULL,
  `aliquota_minima` decimal(5,2) DEFAULT 2.00,
  `aliquota_maxima` decimal(5,2) DEFAULT 5.00,
  `base_legal` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `codigos_servico_iss`
--

INSERT INTO `codigos_servico_iss` (`id`, `item_lista`, `codigo`, `descricao`, `aliquota_minima`, `aliquota_maxima`, `base_legal`, `created_at`) VALUES
(1, '1.01', NULL, 'Análise e desenvolvimento de sistemas', 2.00, 5.00, NULL, '2026-03-17 01:56:26'),
(2, '1.02', NULL, 'Programação', 2.00, 5.00, NULL, '2026-03-17 01:56:26'),
(3, '1.03', NULL, 'Processamento de dados e congêneres', 2.00, 5.00, NULL, '2026-03-17 01:56:26'),
(4, '1.04', NULL, 'Elaboração de programas de computadores, inclusive de jogos eletrônicos', 2.00, 5.00, NULL, '2026-03-17 01:56:26'),
(5, '1.05', NULL, 'Licenciamento ou cessão de direito de uso de programas de computação', 2.00, 5.00, NULL, '2026-03-17 01:56:26'),
(6, '1.06', NULL, 'Assessoria e consultoria em informática', 2.00, 5.00, NULL, '2026-03-17 01:56:26'),
(7, '1.07', NULL, 'Suporte técnico em informática, inclusive instalação, configuração e manutenção', 2.00, 5.00, NULL, '2026-03-17 01:56:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `empresas`
--

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `cnpj` varchar(14) NOT NULL,
  `razao_social` varchar(255) NOT NULL,
  `nome_fantasia` varchar(255) DEFAULT NULL,
  `data_abertura` date DEFAULT NULL,
  `situacao_cadastral` varchar(50) DEFAULT NULL,
  `data_situacao` date DEFAULT NULL,
  `capital_social` decimal(15,2) DEFAULT NULL,
  `porte_empresa` varchar(50) DEFAULT NULL,
  `natureza_juridica_codigo` varchar(4) DEFAULT NULL,
  `entidade_empregadora` tinyint(1) DEFAULT NULL,
  `opcao_pelo_simples` tinyint(1) DEFAULT NULL,
  `data_opcao_simples` date DEFAULT NULL,
  `opcao_pelo_mei` tinyint(1) DEFAULT NULL,
  `data_opcao_mei` date DEFAULT NULL,
  `situacao_especial` varchar(100) DEFAULT NULL,
  `data_situacao_especial` date DEFAULT NULL,
  `motivo_situacao_cadastral` varchar(100) DEFAULT NULL,
  `cep` varchar(8) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `codigo_municipio` varchar(10) DEFAULT NULL,
  `uf` char(2) DEFAULT NULL,
  `codigo_uf` int(2) DEFAULT NULL,
  `telefone1` varchar(20) DEFAULT NULL,
  `telefone2` varchar(20) DEFAULT NULL,
  `fax` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `cnae_principal` varchar(7) DEFAULT NULL,
  `cnae_secundarios` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cnae_secundarios`)),
  `socios_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`socios_json`)),
  `regime_tributario_sugerido` enum('simples_nacional','lucro_presumido','lucro_real','indefinido') DEFAULT 'indefinido',
  `anexo_simples_sugerido` enum('I','II','III','IV','V','MEI') DEFAULT NULL,
  `fator_r_calculado` decimal(5,2) DEFAULT NULL,
  `aliquota_simples_estimada` decimal(5,2) DEFAULT NULL,
  `pode_optar_simples` tinyint(1) DEFAULT 1,
  `motivo_restricao_simples` text DEFAULT NULL,
  `codigo_servico_iss_sugerido` varchar(20) DEFAULT NULL,
  `item_lista_iss_sugerido` varchar(10) DEFAULT NULL,
  `aliquota_iss_estimada` decimal(5,2) DEFAULT NULL,
  `impacto_reforma_estimado` enum('positivo','neutro','negativo') DEFAULT NULL,
  `variacao_carga_estimada` decimal(5,2) DEFAULT NULL,
  `aliquota_ibs_estimada` decimal(5,2) DEFAULT NULL,
  `aliquota_cbs_estimada` decimal(5,2) DEFAULT NULL,
  `capacidade_credito` enum('alta','media','baixa') DEFAULT NULL,
  `recomendacoes_reforma` text DEFAULT NULL,
  `ultima_consulta_api` datetime DEFAULT NULL,
  `ultima_analise` datetime DEFAULT NULL,
  `dados_completos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_completos`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `empresas`
--

INSERT INTO `empresas` (`id`, `uuid`, `cnpj`, `razao_social`, `nome_fantasia`, `data_abertura`, `situacao_cadastral`, `data_situacao`, `capital_social`, `porte_empresa`, `natureza_juridica_codigo`, `entidade_empregadora`, `opcao_pelo_simples`, `data_opcao_simples`, `opcao_pelo_mei`, `data_opcao_mei`, `situacao_especial`, `data_situacao_especial`, `motivo_situacao_cadastral`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `codigo_municipio`, `uf`, `codigo_uf`, `telefone1`, `telefone2`, `fax`, `email`, `cnae_principal`, `cnae_secundarios`, `socios_json`, `regime_tributario_sugerido`, `anexo_simples_sugerido`, `fator_r_calculado`, `aliquota_simples_estimada`, `pode_optar_simples`, `motivo_restricao_simples`, `codigo_servico_iss_sugerido`, `item_lista_iss_sugerido`, `aliquota_iss_estimada`, `impacto_reforma_estimado`, `variacao_carga_estimada`, `aliquota_ibs_estimada`, `aliquota_cbs_estimada`, `capacidade_credito`, `recomendacoes_reforma`, `ultima_consulta_api`, `ultima_analise`, `dados_completos`, `created_at`, `updated_at`) VALUES
(3, '25b6a4ec-26fb-11f1-b2d6-ac1f6b649494', '41479683000199', 'GRUPO ELLO ASSOCIACAO E CLUBE DE BENEFICIOS', '', NULL, '2', NULL, 0.00, 'DEMAIS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '74333020', 'SAO CARLOS', 'S/N', 'QUADRA34 LOTE 03/04 SALA 01', 'JD PLANALTO', 'GOIANIA', NULL, 'GO', NULL, '6240161040', NULL, NULL, '', '9430800', '[]', '[{\"pais\":null,\"nome_socio\":\"DENIS ALVES TERRA\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 51 a 60 anos\",\"cnpj_cpf_do_socio\":\"***270951**\",\"qualificacao_socio\":\"Presidente\",\"codigo_faixa_etaria\":6,\"data_entrada_sociedade\":\"2021-10-15\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":16,\"qualificacao_representante_legal\":\"N\\u00e3o informada\",\"codigo_qualificacao_representante_legal\":0}]', 'indefinido', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-23 17:59:12', NULL, NULL, '2026-03-23 20:59:12', '2026-03-23 20:59:12'),
(4, 'bf0896eb-27ae-11f1-b2d6-ac1f6b649494', '10753450000109', 'N & F AGENCIA DE INFORMATICA LTDA', 'ONNIX SISTEMAS', NULL, 'ATIVA', '2009-03-16', 23000.00, 'MICRO EMPRESA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '85605410', 'BOLIVIA', '1591', '', 'VILA NOVA', 'FRANCISCO BELTRAO', NULL, 'PR', NULL, '(46) 3523-0622', '', NULL, '', '6209100', '[]', '[{\"nome_socio\":\"FRANCIEL SILVESTRINI\",\"qualificacao_socio\":\"S\\u00f3cio-Administrador\",\"tipo_socio\":\"S\\u00f3cio-Administrador\",\"data_entrada_sociedade\":\"2009-03-16\",\"cnpj_cpf_do_socio\":\"***347729**\",\"identificador_socio\":null,\"faixa_etaria\":\"Entre 41 a 50 anos\",\"participacao\":null},{\"nome_socio\":\"NEUSA MARIA SALVI DE OLIVEIRA\",\"qualificacao_socio\":\"S\\u00f3cio-Administrador\",\"tipo_socio\":\"S\\u00f3cio-Administrador\",\"data_entrada_sociedade\":\"2012-11-01\",\"cnpj_cpf_do_socio\":\"***963209**\",\"identificador_socio\":null,\"faixa_etaria\":\"Entre 41 a 50 anos\",\"participacao\":null}]', 'indefinido', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-25 11:55:37', NULL, '{\"uf\":\"PR\",\"cep\":\"85605410\",\"qsa\":[{\"pais\":null,\"nome_socio\":\"FRANCIEL SILVESTRINI\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 41 a 50 anos\",\"cnpj_cpf_do_socio\":\"***347729**\",\"qualificacao_socio\":\"S\\u00f3cio-Administrador\",\"codigo_faixa_etaria\":5,\"data_entrada_sociedade\":\"2009-03-16\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":49,\"qualificacao_representante_legal\":\"N\\u00e3o informada\",\"codigo_qualificacao_representante_legal\":0},{\"pais\":null,\"nome_socio\":\"NEUSA MARIA SALVI DE OLIVEIRA\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 41 a 50 anos\",\"cnpj_cpf_do_socio\":\"***963209**\",\"qualificacao_socio\":\"S\\u00f3cio-Administrador\",\"codigo_faixa_etaria\":5,\"data_entrada_sociedade\":\"2012-11-01\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":49,\"qualificacao_representante_legal\":\"N\\u00e3o informada\",\"codigo_qualificacao_representante_legal\":0}],\"cnpj\":\"10753450000109\",\"pais\":null,\"email\":null,\"porte\":\"MICRO EMPRESA\",\"bairro\":\"VILA NOVA\",\"numero\":\"1591\",\"ddd_fax\":\"\",\"municipio\":\"FRANCISCO BELTRAO\",\"logradouro\":\"BOLIVIA\",\"cnae_fiscal\":6209100,\"codigo_pais\":null,\"complemento\":\"\",\"codigo_porte\":1,\"razao_social\":\"N & F AGENCIA DE INFORMATICA LTDA\",\"nome_fantasia\":\"ONNIX SISTEMAS\",\"capital_social\":23000,\"ddd_telefone_1\":\"4635230622\",\"ddd_telefone_2\":\"\",\"opcao_pelo_mei\":false,\"codigo_municipio\":7565,\"cnaes_secundarios\":[],\"natureza_juridica\":\"Sociedade Empres\\u00e1ria Limitada\",\"regime_tributario\":[],\"situacao_especial\":\"\",\"opcao_pelo_simples\":true,\"situacao_cadastral\":2,\"data_opcao_pelo_mei\":null,\"data_exclusao_do_mei\":null,\"cnae_fiscal_descricao\":\"Suporte t\\u00e9cnico, manuten\\u00e7\\u00e3o e outros servi\\u00e7os em tecnologia da informa\\u00e7\\u00e3o\",\"codigo_municipio_ibge\":4108403,\"data_inicio_atividade\":\"2009-03-16\",\"data_situacao_especial\":null,\"data_opcao_pelo_simples\":\"2009-03-16\",\"data_situacao_cadastral\":\"2009-03-16\",\"nome_cidade_no_exterior\":\"\",\"codigo_natureza_juridica\":2062,\"data_exclusao_do_simples\":null,\"motivo_situacao_cadastral\":0,\"ente_federativo_responsavel\":\"\",\"identificador_matriz_filial\":1,\"qualificacao_do_responsavel\":49,\"descricao_situacao_cadastral\":\"ATIVA\",\"descricao_tipo_de_logradouro\":\"RUA\",\"descricao_motivo_situacao_cadastral\":\"SEM MOTIVO\",\"descricao_identificador_matriz_filial\":\"MATRIZ\"}', '2026-03-24 18:24:48', '2026-03-25 14:55:37'),
(5, 'd5656768-27b0-11f1-b2d6-ac1f6b649494', '03086192000199', 'ELLON SISTEMAS LTDA', 'ELLON TECNOLOGIA EM GESTAO', NULL, '2', NULL, 100000.00, 'MICRO EMPRESA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '85601630', 'MINAS GERAIS', '1391', 'QUADRA217 LOTE 04 SALA 101 EDIF ELLON', 'NOSSA SENHORA APARECIDA', 'FRANCISCO BELTRAO', NULL, 'PR', NULL, '4635242555', NULL, NULL, '', '6203100', '[{\"codigo\":6202300,\"descricao\":\"Desenvolvimento e licenciamento de programas de computador customiz\\u00e1veis\"}]', '[{\"pais\":null,\"nome_socio\":\"ANA PAULA TODESCATTO\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 31 a 40 anos\",\"cnpj_cpf_do_socio\":\"***201579**\",\"qualificacao_socio\":\"S\\u00f3cio\",\"codigo_faixa_etaria\":4,\"data_entrada_sociedade\":\"2023-01-25\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":22,\"qualificacao_representante_legal\":\"N\\u00e3o informada\",\"codigo_qualificacao_representante_legal\":0},{\"pais\":null,\"nome_socio\":\"EVERTO FABIO DA SILVA MACHADO\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 31 a 40 anos\",\"cnpj_cpf_do_socio\":\"***771369**\",\"qualificacao_socio\":\"S\\u00f3cio\",\"codigo_faixa_etaria\":4,\"data_entrada_sociedade\":\"2023-01-25\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":22,\"qualificacao_representante_legal\":\"N\\u00e3o informada\",\"codigo_qualificacao_representante_legal\":0},{\"pais\":null,\"nome_socio\":\"JANETE CARARA MEURER\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 51 a 60 anos\",\"cnpj_cpf_do_socio\":\"***097739**\",\"qualificacao_socio\":\"Administrador\",\"codigo_faixa_etaria\":6,\"data_entrada_sociedade\":\"2025-04-01\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":5,\"qualificacao_representante_legal\":\"N\\u00e3o informada\",\"codigo_qualificacao_representante_legal\":0},{\"pais\":null,\"nome_socio\":\"MYCHEL DAMBROS\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 31 a 40 anos\",\"cnpj_cpf_do_socio\":\"***101989**\",\"qualificacao_socio\":\"S\\u00f3cio\",\"codigo_faixa_etaria\":4,\"data_entrada_sociedade\":\"2023-01-25\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":22,\"qualificacao_representante_legal\":\"N\\u00e3o informada\",\"codigo_qualificacao_representante_legal\":0},{\"pais\":null,\"nome_socio\":\"RODRIGO MATACZINSKI\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 31 a 40 anos\",\"cnpj_cpf_do_socio\":\"***631349**\",\"qualificacao_socio\":\"S\\u00f3cio\",\"codigo_faixa_etaria\":4,\"data_entrada_sociedade\":\"2023-01-25\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":22,\"qualificacao_representante_legal\":\"N\\u00e3o informada\",\"codigo_qualificacao_representante_legal\":0},{\"pais\":null,\"nome_socio\":\"TARCIZIO MEURER\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 51 a 60 anos\",\"cnpj_cpf_do_socio\":\"***447689**\",\"qualificacao_socio\":\"S\\u00f3cio\",\"codigo_faixa_etaria\":6,\"data_entrada_sociedade\":\"1999-03-30\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":22,\"qualificacao_representante_legal\":\"N\\u00e3o informada\",\"codigo_qualificacao_representante_legal\":0},{\"pais\":null,\"nome_socio\":\"TASSIA MEURER\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 13 a 20 ano\",\"cnpj_cpf_do_socio\":\"***940879**\",\"qualificacao_socio\":\"S\\u00f3cio Menor (Assistido\\/Representado)\",\"codigo_faixa_etaria\":2,\"data_entrada_sociedade\":\"2014-08-05\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***097739**\",\"nome_representante_legal\":\"JANETE CARARA MEURER\",\"codigo_qualificacao_socio\":30,\"qualificacao_representante_legal\":\"M\\u00e3e\",\"codigo_qualificacao_representante_legal\":14}]', 'indefinido', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-24 15:39:45', NULL, NULL, '2026-03-24 18:39:45', '2026-03-24 18:39:45'),
(6, '1dfd57c3-286b-11f1-b2d6-ac1f6b649494', '13345309000165', 'W E COMERCIO DE PISCINAS LTDA', '', NULL, 'ATIVA', '2011-03-03', 150000.00, 'DEMAIS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '95612450', 'OSCAR MARTINS RANGEL', '3549', '', 'SANTA MARIA', 'TAQUARA', NULL, 'RS', NULL, '(51) 9296-5039', '', NULL, '', '2229-30', '[{\"codigo\":2222600,\"codigo_formatado\":\"2222-60\",\"descricao\":\"Fabricação de embalagens de material plástico\"},{\"codigo\":2229399,\"codigo_formatado\":\"2229-39\",\"descricao\":\"Fabricação de artefatos de material plástico para outros usos não especificados anteriormente\"},{\"codigo\":4330499,\"codigo_formatado\":\"4330-49\",\"descricao\":\"Outras obras de acabamento da construção\"},{\"codigo\":4649499,\"codigo_formatado\":\"4649-49\",\"descricao\":\"Comércio atacadista de outros equipamentos e artigos de uso pessoal e doméstico não especificados anteriormente\"},{\"codigo\":4669999,\"codigo_formatado\":\"4669-99\",\"descricao\":\"Comércio atacadista de outras máquinas e equipamentos não especificados anteriormente; partes e peças\"},{\"codigo\":4672900,\"codigo_formatado\":\"4672-90\",\"descricao\":\"Comércio atacadista de ferragens e ferramentas\"},{\"codigo\":4673700,\"codigo_formatado\":\"4673-70\",\"descricao\":\"Comércio atacadista de material elétrico\"},{\"codigo\":4679699,\"codigo_formatado\":\"4679-69\",\"descricao\":\"Comércio atacadista de materiais de construção em geral\"},{\"codigo\":4742300,\"codigo_formatado\":\"4742-30\",\"descricao\":\"Comércio varejista de material elétrico\"},{\"codigo\":4744001,\"codigo_formatado\":\"4744-00\",\"descricao\":\"Comércio varejista de ferragens e ferramentas\"},{\"codigo\":4744003,\"codigo_formatado\":\"4744-00\",\"descricao\":\"Comércio varejista de materiais hidráulicos\"},{\"codigo\":4744005,\"codigo_formatado\":\"4744-00\",\"descricao\":\"Comércio varejista de materiais de construção não especificados anteriormente\"},{\"codigo\":4754703,\"codigo_formatado\":\"4754-70\",\"descricao\":\"Comércio varejista de artigos de iluminação\"},{\"codigo\":4789005,\"codigo_formatado\":\"4789-00\",\"descricao\":\"Comércio varejista de produtos saneantes domissanitários\"},{\"codigo\":8130300,\"codigo_formatado\":\"8130-30\",\"descricao\":\"Atividades paisagísticas\"}]', '[{\"nome_socio\":\"WILLIAM EDINGER\",\"qualificacao_socio\":\"Sócio-Administrador\",\"tipo_socio\":\"Sócio-Administrador\",\"data_entrada_sociedade\":\"2014-11-13\",\"cnpj_cpf_do_socio\":\"265950\",\"identificador_socio\":null,\"faixa_etaria\":\"Entre 31 a 40 anos\",\"participacao\":null,\"representante_legal\":null}]', 'indefinido', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-25 13:53:12', NULL, '{\"uf\":\"RS\",\"cep\":\"95612450\",\"qsa\":[{\"pais\":null,\"nome_socio\":\"WILLIAM EDINGER\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 31 a 40 anos\",\"cnpj_cpf_do_socio\":\"***265950**\",\"qualificacao_socio\":\"Sócio-Administrador\",\"codigo_faixa_etaria\":4,\"data_entrada_sociedade\":\"2014-11-13\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":49,\"qualificacao_representante_legal\":\"Não informada\",\"codigo_qualificacao_representante_legal\":0}],\"cnpj\":\"13345309000165\",\"pais\":null,\"email\":null,\"porte\":\"DEMAIS\",\"bairro\":\"SANTA MARIA\",\"numero\":\"3549\",\"ddd_fax\":\"\",\"municipio\":\"TAQUARA\",\"logradouro\":\"OSCAR MARTINS RANGEL\",\"cnae_fiscal\":2229303,\"codigo_pais\":null,\"complemento\":\"\",\"codigo_porte\":5,\"razao_social\":\"W E COMERCIO DE PISCINAS LTDA\",\"nome_fantasia\":\"\",\"capital_social\":150000,\"ddd_telefone_1\":\"5192965039\",\"ddd_telefone_2\":\"\",\"opcao_pelo_mei\":false,\"codigo_municipio\":8927,\"cnaes_secundarios\":[{\"codigo\":2222600,\"descricao\":\"Fabricação de embalagens de material plástico\"},{\"codigo\":2229399,\"descricao\":\"Fabricação de artefatos de material plástico para outros usos não especificados anteriormente\"},{\"codigo\":4330499,\"descricao\":\"Outras obras de acabamento da construção\"},{\"codigo\":4649499,\"descricao\":\"Comércio atacadista de outros equipamentos e artigos de uso pessoal e doméstico não especificados anteriormente\"},{\"codigo\":4669999,\"descricao\":\"Comércio atacadista de outras máquinas e equipamentos não especificados anteriormente; partes e peças\"},{\"codigo\":4672900,\"descricao\":\"Comércio atacadista de ferragens e ferramentas\"},{\"codigo\":4673700,\"descricao\":\"Comércio atacadista de material elétrico\"},{\"codigo\":4679699,\"descricao\":\"Comércio atacadista de materiais de construção em geral\"},{\"codigo\":4742300,\"descricao\":\"Comércio varejista de material elétrico\"},{\"codigo\":4744001,\"descricao\":\"Comércio varejista de ferragens e ferramentas\"},{\"codigo\":4744003,\"descricao\":\"Comércio varejista de materiais hidráulicos\"},{\"codigo\":4744005,\"descricao\":\"Comércio varejista de materiais de construção não especificados anteriormente\"},{\"codigo\":4754703,\"descricao\":\"Comércio varejista de artigos de iluminação\"},{\"codigo\":4789005,\"descricao\":\"Comércio varejista de produtos saneantes domissanitários\"},{\"codigo\":8130300,\"descricao\":\"Atividades paisagísticas\"}],\"natureza_juridica\":\"Sociedade Empresária Limitada\",\"regime_tributario\":[{\"ano\":2021,\"cnpj_da_scp\":null,\"forma_de_tributacao\":\"LUCRO REAL\",\"quantidade_de_escrituracoes\":1},{\"ano\":2022,\"cnpj_da_scp\":null,\"forma_de_tributacao\":\"LUCRO REAL\",\"quantidade_de_escrituracoes\":1},{\"ano\":2023,\"cnpj_da_scp\":null,\"forma_de_tributacao\":\"LUCRO REAL\",\"quantidade_de_escrituracoes\":1},{\"ano\":2024,\"cnpj_da_scp\":null,\"forma_de_tributacao\":\"LUCRO REAL\",\"quantidade_de_escrituracoes\":1}],\"situacao_especial\":\"\",\"opcao_pelo_simples\":false,\"situacao_cadastral\":2,\"data_opcao_pelo_mei\":null,\"data_exclusao_do_mei\":null,\"cnae_fiscal_descricao\":\"Fabricação de artefatos de material plástico para uso na construção, exceto tubos e acessórios\",\"codigo_municipio_ibge\":4321204,\"data_inicio_atividade\":\"2011-03-03\",\"data_situacao_especial\":null,\"data_opcao_pelo_simples\":\"2016-01-01\",\"data_situacao_cadastral\":\"2011-03-03\",\"nome_cidade_no_exterior\":\"\",\"codigo_natureza_juridica\":2062,\"data_exclusao_do_simples\":\"2020-12-31\",\"motivo_situacao_cadastral\":0,\"ente_federativo_responsavel\":\"\",\"identificador_matriz_filial\":1,\"qualificacao_do_responsavel\":49,\"descricao_situacao_cadastral\":\"ATIVA\",\"descricao_tipo_de_logradouro\":\"AVENIDA\",\"descricao_motivo_situacao_cadastral\":\"SEM MOTIVO\",\"descricao_identificador_matriz_filial\":\"MATRIZ\"}', '2026-03-25 16:53:12', '2026-03-25 16:53:12'),
(7, '8fe512cc-2886-11f1-b2d6-ac1f6b649494', '47513521000106', 'LUKE CONCRETO LTDA', 'LUKE CONCRETO', NULL, 'ATIVA', '2022-08-10', 300000.00, 'DEMAIS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '89985000', 'SC 161', 'SN', 'KM 13', 'LOTEAMENTO INDUSTRIAL', 'PALMA SOLA', NULL, 'SC', NULL, '(49) 3652-0005', '', NULL, '', '2330305', '[]', '[{\"nome_socio\":\"LUIZ HENRIQUE CRESTANI\",\"qualificacao_socio\":\"Administrador\",\"tipo_socio\":\"Administrador\",\"data_entrada_sociedade\":\"2022-08-10\",\"cnpj_cpf_do_socio\":\"920589\",\"identificador_socio\":null,\"faixa_etaria\":\"Entre 31 a 40 anos\",\"participacao\":null},{\"nome_socio\":\"LUKE GROUP LTDA\",\"qualificacao_socio\":\"Sócio\",\"tipo_socio\":\"Sócio\",\"data_entrada_sociedade\":\"2023-06-12\",\"cnpj_cpf_do_socio\":\"48.278.050\\/0001-61\",\"identificador_socio\":null,\"faixa_etaria\":\"Não se aplica\",\"participacao\":null}]', 'indefinido', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-25 17:09:39', NULL, '{\"uf\":\"SC\",\"cep\":\"89985000\",\"qsa\":[{\"pais\":null,\"nome_socio\":\"LUIZ HENRIQUE CRESTANI\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 31 a 40 anos\",\"cnpj_cpf_do_socio\":\"***920589**\",\"qualificacao_socio\":\"Administrador\",\"codigo_faixa_etaria\":4,\"data_entrada_sociedade\":\"2022-08-10\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":5,\"qualificacao_representante_legal\":\"Não informada\",\"codigo_qualificacao_representante_legal\":0},{\"pais\":null,\"nome_socio\":\"LUKE GROUP LTDA\",\"codigo_pais\":null,\"faixa_etaria\":\"Não se aplica\",\"cnpj_cpf_do_socio\":\"48278050000161\",\"qualificacao_socio\":\"Sócio\",\"codigo_faixa_etaria\":0,\"data_entrada_sociedade\":\"2023-06-12\",\"identificador_de_socio\":1,\"cpf_representante_legal\":\"***920589**\",\"nome_representante_legal\":\"LUIZ HENRIQUE CRESTANI\",\"codigo_qualificacao_socio\":22,\"qualificacao_representante_legal\":\"Administrador\",\"codigo_qualificacao_representante_legal\":5}],\"cnpj\":\"47513521000106\",\"pais\":null,\"email\":null,\"porte\":\"DEMAIS\",\"bairro\":\"LOTEAMENTO INDUSTRIAL\",\"numero\":\"SN\",\"ddd_fax\":\"\",\"municipio\":\"PALMA SOLA\",\"logradouro\":\"SC 161\",\"cnae_fiscal\":2330305,\"codigo_pais\":null,\"complemento\":\"KM 13\",\"codigo_porte\":5,\"razao_social\":\"LUKE CONCRETO LTDA\",\"nome_fantasia\":\"LUKE CONCRETO\",\"capital_social\":300000,\"ddd_telefone_1\":\"4936520005\",\"ddd_telefone_2\":\"\",\"opcao_pelo_mei\":null,\"codigo_municipio\":8235,\"cnaes_secundarios\":[],\"natureza_juridica\":\"Sociedade Empresária Limitada\",\"regime_tributario\":[{\"ano\":2022,\"cnpj_da_scp\":null,\"forma_de_tributacao\":\"LUCRO PRESUMIDO\",\"quantidade_de_escrituracoes\":1},{\"ano\":2023,\"cnpj_da_scp\":null,\"forma_de_tributacao\":\"LUCRO REAL\",\"quantidade_de_escrituracoes\":1},{\"ano\":2024,\"cnpj_da_scp\":null,\"forma_de_tributacao\":\"LUCRO REAL\",\"quantidade_de_escrituracoes\":1}],\"situacao_especial\":\"\",\"opcao_pelo_simples\":null,\"situacao_cadastral\":2,\"data_opcao_pelo_mei\":null,\"data_exclusao_do_mei\":null,\"cnae_fiscal_descricao\":\"Preparação de massa de concreto e argamassa para construção\",\"codigo_municipio_ibge\":4212007,\"data_inicio_atividade\":\"2022-08-10\",\"data_situacao_especial\":null,\"data_opcao_pelo_simples\":null,\"data_situacao_cadastral\":\"2022-08-10\",\"nome_cidade_no_exterior\":\"\",\"codigo_natureza_juridica\":2062,\"data_exclusao_do_simples\":null,\"motivo_situacao_cadastral\":0,\"ente_federativo_responsavel\":\"\",\"identificador_matriz_filial\":1,\"qualificacao_do_responsavel\":5,\"descricao_situacao_cadastral\":\"ATIVA\",\"descricao_tipo_de_logradouro\":\"RODOVIA\",\"descricao_motivo_situacao_cadastral\":\"SEM MOTIVO\",\"descricao_identificador_matriz_filial\":\"MATRIZ\"}', '2026-03-25 20:09:39', '2026-03-25 20:09:39'),
(8, '0be7f595-2888-11f1-b2d6-ac1f6b649494', '01694860000135', 'SOL BRASIL SUL ENERGIA SOLAR LTDA', 'SOL BRASIL SUL ENERGIA SOLAR', NULL, 'ATIVA', '2005-08-27', 25000.00, 'MICRO EMPRESA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '85605040', 'UNIAO DA VITORIA', '1010', 'APT 01', 'VILA NOVA', 'FRANCISCO BELTRAO', NULL, 'PR', NULL, '(46) 9911-4781', '', NULL, '', '4321500', '[{\"codigo\":4669999,\"descricao\":\"Comércio atacadista de outras máquinas e equipamentos não especificados anteriormente; partes e peças\"},{\"codigo\":4673700,\"descricao\":\"Comércio atacadista de material elétrico\"},{\"codigo\":4742300,\"descricao\":\"Comércio varejista de material elétrico\"},{\"codigo\":4789099,\"descricao\":\"Comércio varejista de outros produtos não especificados anteriormente\"},{\"codigo\":7319002,\"descricao\":\"Promoção de vendas\"}]', '[{\"nome_socio\":\"ANTONINHO SERAFIN\",\"qualificacao_socio\":\"Sócio-Administrador\",\"tipo_socio\":\"Sócio-Administrador\",\"data_entrada_sociedade\":\"2022-11-30\",\"cnpj_cpf_do_socio\":\"932789\",\"identificador_socio\":null,\"faixa_etaria\":\"Entre 61 a 70 anos\",\"participacao\":null},{\"nome_socio\":\"KARINA KUNZ SERAFIN\",\"qualificacao_socio\":\"Sócio-Administrador\",\"tipo_socio\":\"Sócio-Administrador\",\"data_entrada_sociedade\":\"2020-09-18\",\"cnpj_cpf_do_socio\":\"438339\",\"identificador_socio\":null,\"faixa_etaria\":\"Entre 31 a 40 anos\",\"participacao\":null},{\"nome_socio\":\"RAQUEL KUNZ SERAFIN\",\"qualificacao_socio\":\"Sócio\",\"tipo_socio\":\"Sócio\",\"data_entrada_sociedade\":\"2022-11-30\",\"cnpj_cpf_do_socio\":\"513129\",\"identificador_socio\":null,\"faixa_etaria\":\"Entre 31 a 40 anos\",\"participacao\":null}]', 'indefinido', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-25 17:20:17', NULL, '{\"uf\":\"PR\",\"cep\":\"85605040\",\"qsa\":[{\"pais\":null,\"nome_socio\":\"ANTONINHO SERAFIN\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 61 a 70 anos\",\"cnpj_cpf_do_socio\":\"***932789**\",\"qualificacao_socio\":\"Sócio-Administrador\",\"codigo_faixa_etaria\":7,\"data_entrada_sociedade\":\"2022-11-30\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":49,\"qualificacao_representante_legal\":\"Não informada\",\"codigo_qualificacao_representante_legal\":0},{\"pais\":null,\"nome_socio\":\"KARINA KUNZ SERAFIN\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 31 a 40 anos\",\"cnpj_cpf_do_socio\":\"***438339**\",\"qualificacao_socio\":\"Sócio-Administrador\",\"codigo_faixa_etaria\":4,\"data_entrada_sociedade\":\"2020-09-18\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":49,\"qualificacao_representante_legal\":\"Não informada\",\"codigo_qualificacao_representante_legal\":0},{\"pais\":null,\"nome_socio\":\"RAQUEL KUNZ SERAFIN\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 31 a 40 anos\",\"cnpj_cpf_do_socio\":\"***513129**\",\"qualificacao_socio\":\"Sócio\",\"codigo_faixa_etaria\":4,\"data_entrada_sociedade\":\"2022-11-30\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":22,\"qualificacao_representante_legal\":\"Não informada\",\"codigo_qualificacao_representante_legal\":0}],\"cnpj\":\"01694860000135\",\"pais\":null,\"email\":null,\"porte\":\"MICRO EMPRESA\",\"bairro\":\"VILA NOVA\",\"numero\":\"1010\",\"ddd_fax\":\"\",\"municipio\":\"FRANCISCO BELTRAO\",\"logradouro\":\"UNIAO DA VITORIA\",\"cnae_fiscal\":4321500,\"codigo_pais\":null,\"complemento\":\"APT 01\",\"codigo_porte\":1,\"razao_social\":\"SOL BRASIL SUL ENERGIA SOLAR LTDA\",\"nome_fantasia\":\"SOL BRASIL SUL ENERGIA SOLAR\",\"capital_social\":25000,\"ddd_telefone_1\":\"4699114781\",\"ddd_telefone_2\":\"\",\"opcao_pelo_mei\":false,\"codigo_municipio\":7565,\"cnaes_secundarios\":[{\"codigo\":4669999,\"descricao\":\"Comércio atacadista de outras máquinas e equipamentos não especificados anteriormente; partes e peças\"},{\"codigo\":4673700,\"descricao\":\"Comércio atacadista de material elétrico\"},{\"codigo\":4742300,\"descricao\":\"Comércio varejista de material elétrico\"},{\"codigo\":4789099,\"descricao\":\"Comércio varejista de outros produtos não especificados anteriormente\"},{\"codigo\":7319002,\"descricao\":\"Promoção de vendas\"}],\"natureza_juridica\":\"Sociedade Empresária Limitada\",\"regime_tributario\":[{\"ano\":2021,\"cnpj_da_scp\":null,\"forma_de_tributacao\":\"LUCRO PRESUMIDO\",\"quantidade_de_escrituracoes\":1},{\"ano\":2024,\"cnpj_da_scp\":null,\"forma_de_tributacao\":\"LUCRO PRESUMIDO\",\"quantidade_de_escrituracoes\":1}],\"situacao_especial\":\"\",\"opcao_pelo_simples\":true,\"situacao_cadastral\":2,\"data_opcao_pelo_mei\":null,\"data_exclusao_do_mei\":null,\"cnae_fiscal_descricao\":\"Instalação e manutenção elétrica\",\"codigo_municipio_ibge\":4108403,\"data_inicio_atividade\":\"1997-03-06\",\"data_situacao_especial\":null,\"data_opcao_pelo_simples\":\"2026-01-01\",\"data_situacao_cadastral\":\"2005-08-27\",\"nome_cidade_no_exterior\":\"\",\"codigo_natureza_juridica\":2062,\"data_exclusao_do_simples\":null,\"motivo_situacao_cadastral\":0,\"ente_federativo_responsavel\":\"\",\"identificador_matriz_filial\":1,\"qualificacao_do_responsavel\":49,\"descricao_situacao_cadastral\":\"ATIVA\",\"descricao_tipo_de_logradouro\":\"AVENIDA\",\"descricao_motivo_situacao_cadastral\":\"SEM MOTIVO\",\"descricao_identificador_matriz_filial\":\"MATRIZ\"}', '2026-03-25 20:20:17', '2026-03-25 20:20:17'),
(9, '0511d363-2904-11f1-b2d6-ac1f6b649494', '63196642000128', '63.196.642 ALVARO RODRIGO DE SOUZA SILVA', '', NULL, 'ATIVA', '2025-10-15', 1000.00, 'MICRO EMPRESA', 'Empr', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '86872608', '', '', '', 'VILA JOAO XXIII', 'IVAIPORA', NULL, 'PR', NULL, '', '', NULL, '', '4930201', '[]', '[]', 'indefinido', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-26 08:07:42', NULL, '{\"uf\":\"PR\",\"cep\":\"86872608\",\"qsa\":[],\"cnpj\":\"63196642000128\",\"pais\":null,\"email\":null,\"porte\":\"MICRO EMPRESA\",\"bairro\":\"VILA JOAO XXIII\",\"numero\":\"\",\"ddd_fax\":\"\",\"municipio\":\"IVAIPORA\",\"logradouro\":\"\",\"cnae_fiscal\":4930201,\"codigo_pais\":null,\"complemento\":\"\",\"codigo_porte\":1,\"razao_social\":\"63.196.642 ALVARO RODRIGO DE SOUZA SILVA\",\"nome_fantasia\":\"\",\"capital_social\":1000,\"ddd_telefone_1\":\"\",\"ddd_telefone_2\":\"\",\"opcao_pelo_mei\":true,\"codigo_municipio\":7623,\"cnaes_secundarios\":[],\"natureza_juridica\":\"Empresário (Individual)\",\"regime_tributario\":[],\"situacao_especial\":\"\",\"opcao_pelo_simples\":true,\"situacao_cadastral\":2,\"data_opcao_pelo_mei\":\"2025-10-15\",\"data_exclusao_do_mei\":null,\"cnae_fiscal_descricao\":\"Transporte rodoviário de carga, exceto produtos perigosos e mudanças, municipal.\",\"codigo_municipio_ibge\":4111506,\"data_inicio_atividade\":\"2025-10-15\",\"data_situacao_especial\":null,\"data_opcao_pelo_simples\":\"2025-10-15\",\"data_situacao_cadastral\":\"2025-10-15\",\"nome_cidade_no_exterior\":\"\",\"codigo_natureza_juridica\":2135,\"data_exclusao_do_simples\":null,\"motivo_situacao_cadastral\":0,\"ente_federativo_responsavel\":\"\",\"identificador_matriz_filial\":1,\"qualificacao_do_responsavel\":50,\"descricao_situacao_cadastral\":\"ATIVA\",\"descricao_tipo_de_logradouro\":\"\",\"descricao_motivo_situacao_cadastral\":\"SEM MOTIVO\",\"descricao_identificador_matriz_filial\":\"MATRIZ\"}', '2026-03-26 11:07:42', '2026-03-26 11:07:42'),
(10, '25fe6232-2c8a-11f1-b2d6-ac1f6b649494', '53011018000155', '53.011.018 ANDREIA REDIVO', '', NULL, 'ATIVA', '2023-11-27', 1000.00, 'MICRO EMPRESA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '85601020', '', '', '', 'CENTRO', 'FRANCISCO BELTRAO', NULL, 'PR', NULL, '', '', NULL, '', '8230001', '[{\"codigo\":4781400,\"descricao\":\"Comércio varejista de artigos do vestuário e acessórios\"}]', '[]', 'indefinido', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-30 19:45:21', NULL, '{\"uf\":\"PR\",\"cep\":\"85601020\",\"qsa\":[],\"cnpj\":\"53011018000155\",\"pais\":null,\"email\":null,\"porte\":\"MICRO EMPRESA\",\"bairro\":\"CENTRO\",\"numero\":\"\",\"ddd_fax\":\"\",\"municipio\":\"FRANCISCO BELTRAO\",\"logradouro\":\"\",\"cnae_fiscal\":8230001,\"codigo_pais\":null,\"complemento\":\"\",\"codigo_porte\":1,\"razao_social\":\"53.011.018 ANDREIA REDIVO\",\"nome_fantasia\":\"\",\"capital_social\":1000,\"ddd_telefone_1\":\"\",\"ddd_telefone_2\":\"\",\"opcao_pelo_mei\":true,\"codigo_municipio\":7565,\"cnaes_secundarios\":[{\"codigo\":4781400,\"descricao\":\"Comércio varejista de artigos do vestuário e acessórios\"}],\"natureza_juridica\":\"Empresário (Individual)\",\"regime_tributario\":[],\"situacao_especial\":\"\",\"opcao_pelo_simples\":true,\"situacao_cadastral\":2,\"data_opcao_pelo_mei\":\"2023-11-27\",\"data_exclusao_do_mei\":null,\"cnae_fiscal_descricao\":\"Serviços de organização de feiras, congressos, exposições e festas\",\"codigo_municipio_ibge\":4108403,\"data_inicio_atividade\":\"2023-11-27\",\"data_situacao_especial\":null,\"data_opcao_pelo_simples\":\"2023-11-27\",\"data_situacao_cadastral\":\"2023-11-27\",\"nome_cidade_no_exterior\":\"\",\"codigo_natureza_juridica\":2135,\"data_exclusao_do_simples\":null,\"motivo_situacao_cadastral\":0,\"ente_federativo_responsavel\":\"\",\"identificador_matriz_filial\":1,\"qualificacao_do_responsavel\":50,\"descricao_situacao_cadastral\":\"ATIVA\",\"descricao_tipo_de_logradouro\":\"\",\"descricao_motivo_situacao_cadastral\":\"SEM MOTIVO\",\"descricao_identificador_matriz_filial\":\"MATRIZ\"}', '2026-03-30 22:45:21', '2026-03-30 22:45:21'),
(11, '83ea0199-2e8e-11f1-b2d6-ac1f6b649494', '32833876000458', 'ZAMPIERON  CORREA TRANSPORTES LTDA', '', NULL, 'ATIVA', '2025-10-09', 100000.00, 'DEMAIS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '79982266', 'APARECIDO DONIZETE DOS SANTOS', '84', '', 'UNIVERSITARIO', 'MUNDO NOVO', NULL, 'MS', NULL, '(49) 3541-2353', '', NULL, '', '4930202', '[{\"codigo\":4930201,\"descricao\":\"Transporte rodoviário de carga, exceto produtos perigosos e mudanças, municipal.\"}]', '[{\"nome_socio\":\"BERNARDO ZAMPIERON\",\"qualificacao_socio\":\"Sócio-Administrador\",\"tipo_socio\":\"Sócio-Administrador\",\"data_entrada_sociedade\":\"2019-02-20\",\"cnpj_cpf_do_socio\":\"518649\",\"identificador_socio\":null,\"faixa_etaria\":\"Entre 51 a 60 anos\",\"participacao\":null},{\"nome_socio\":\"GILVAN PEREIRA\",\"qualificacao_socio\":\"Sócio-Administrador\",\"tipo_socio\":\"Sócio-Administrador\",\"data_entrada_sociedade\":\"2024-02-01\",\"cnpj_cpf_do_socio\":\"939139\",\"identificador_socio\":null,\"faixa_etaria\":\"Entre 41 a 50 anos\",\"participacao\":null},{\"nome_socio\":\"OLEDIR CORREA DE BAIRROS\",\"qualificacao_socio\":\"Sócio-Administrador\",\"tipo_socio\":\"Sócio-Administrador\",\"data_entrada_sociedade\":\"2019-02-20\",\"cnpj_cpf_do_socio\":\"971209\",\"identificador_socio\":null,\"faixa_etaria\":\"Entre 51 a 60 anos\",\"participacao\":null}]', 'indefinido', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-02 09:21:36', NULL, '{\"uf\":\"MS\",\"cep\":\"79982266\",\"qsa\":[{\"pais\":null,\"nome_socio\":\"BERNARDO ZAMPIERON\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 51 a 60 anos\",\"cnpj_cpf_do_socio\":\"***518649**\",\"qualificacao_socio\":\"Sócio-Administrador\",\"codigo_faixa_etaria\":6,\"data_entrada_sociedade\":\"2019-02-20\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":49,\"qualificacao_representante_legal\":\"Não informada\",\"codigo_qualificacao_representante_legal\":0},{\"pais\":null,\"nome_socio\":\"GILVAN PEREIRA\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 41 a 50 anos\",\"cnpj_cpf_do_socio\":\"***939139**\",\"qualificacao_socio\":\"Sócio-Administrador\",\"codigo_faixa_etaria\":5,\"data_entrada_sociedade\":\"2024-02-01\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":49,\"qualificacao_representante_legal\":\"Não informada\",\"codigo_qualificacao_representante_legal\":0},{\"pais\":null,\"nome_socio\":\"OLEDIR CORREA DE BAIRROS\",\"codigo_pais\":null,\"faixa_etaria\":\"Entre 51 a 60 anos\",\"cnpj_cpf_do_socio\":\"***971209**\",\"qualificacao_socio\":\"Sócio-Administrador\",\"codigo_faixa_etaria\":6,\"data_entrada_sociedade\":\"2019-02-20\",\"identificador_de_socio\":2,\"cpf_representante_legal\":\"***000000**\",\"nome_representante_legal\":\"\",\"codigo_qualificacao_socio\":49,\"qualificacao_representante_legal\":\"Não informada\",\"codigo_qualificacao_representante_legal\":0}],\"cnpj\":\"32833876000458\",\"pais\":null,\"email\":null,\"porte\":\"DEMAIS\",\"bairro\":\"UNIVERSITARIO\",\"numero\":\"84\",\"ddd_fax\":\"\",\"municipio\":\"MUNDO NOVO\",\"logradouro\":\"APARECIDO DONIZETE DOS SANTOS\",\"cnae_fiscal\":4930202,\"codigo_pais\":null,\"complemento\":\"\",\"codigo_porte\":5,\"razao_social\":\"ZAMPIERON & CORREA TRANSPORTES LTDA\",\"nome_fantasia\":\"\",\"capital_social\":100000,\"ddd_telefone_1\":\"4935412353\",\"ddd_telefone_2\":\"\",\"opcao_pelo_mei\":null,\"codigo_municipio\":9179,\"cnaes_secundarios\":[{\"codigo\":4930201,\"descricao\":\"Transporte rodoviário de carga, exceto produtos perigosos e mudanças, municipal.\"}],\"natureza_juridica\":\"Sociedade Empresária Limitada\",\"regime_tributario\":[],\"situacao_especial\":\"\",\"opcao_pelo_simples\":null,\"situacao_cadastral\":2,\"data_opcao_pelo_mei\":null,\"data_exclusao_do_mei\":null,\"cnae_fiscal_descricao\":\"Transporte rodoviário de carga, exceto produtos perigosos e mudanças, intermunicipal, interestadual e internacional\",\"codigo_municipio_ibge\":5005681,\"data_inicio_atividade\":\"2025-10-09\",\"data_situacao_especial\":null,\"data_opcao_pelo_simples\":null,\"data_situacao_cadastral\":\"2025-10-09\",\"nome_cidade_no_exterior\":\"\",\"codigo_natureza_juridica\":2062,\"data_exclusao_do_simples\":null,\"motivo_situacao_cadastral\":0,\"ente_federativo_responsavel\":\"\",\"identificador_matriz_filial\":2,\"qualificacao_do_responsavel\":49,\"descricao_situacao_cadastral\":\"ATIVA\",\"descricao_tipo_de_logradouro\":\"TRAVESSA\",\"descricao_motivo_situacao_cadastral\":\"SEM MOTIVO\",\"descricao_identificador_matriz_filial\":\"FILIAL\"}', '2026-04-02 12:21:36', '2026-04-02 12:21:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_consultas`
--

CREATE TABLE `historico_consultas` (
  `id` bigint(20) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cnpj_consultado` varchar(14) NOT NULL,
  `tipo_consulta` enum('completa','basica','api') DEFAULT 'completa',
  `creditos_gastos` int(11) DEFAULT 1,
  `ip_origem` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `consulta_gratuita` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_mudancas_empresa`
--

CREATE TABLE `historico_mudancas_empresa` (
  `id` bigint(20) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `campo_alterado` varchar(100) NOT NULL,
  `valor_antigo` text DEFAULT NULL,
  `valor_novo` text DEFAULT NULL,
  `data_mudanca` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id` bigint(20) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `entidade` varchar(50) DEFAULT NULL,
  `entidade_id` int(11) DEFAULT NULL,
  `detalhes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detalhes`)),
  `ip_origem` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `nivel` enum('info','warning','error','debug') DEFAULT 'info',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `natureza_juridica`
--

CREATE TABLE `natureza_juridica` (
  `codigo` varchar(4) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `permite_simples` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `natureza_juridica`
--

INSERT INTO `natureza_juridica` (`codigo`, `descricao`, `permite_simples`, `created_at`) VALUES
('2011', 'Empresário Individual', 1, '2026-03-17 01:56:26'),
('2038', 'Empresa Individual de Responsabilidade Limitada (EIRELI)', 1, '2026-03-25 13:03:41'),
('2062', 'Sociedade Empresária Limitada', 1, '2026-03-17 01:56:26'),
('2135', 'Sociedade Simples Pura', 1, '2026-03-17 01:56:26'),
('2143', 'Sociedade Anônima Aberta', 0, '2026-03-25 13:03:41'),
('2221', 'Sociedade Simples Pura', 1, '2026-03-25 13:03:41'),
('2248', 'Sociedade Simples Limitada', 1, '2026-03-25 13:03:41'),
('2300', 'Cooperativa', 1, '2026-03-25 13:03:41'),
('2342', 'Consórcio de Empresas', 0, '2026-03-25 13:03:41'),
('2411', 'Empresa Pública', 0, '2026-03-25 13:03:41'),
('2420', 'Sociedade de Economia Mista', 0, '2026-03-25 13:03:41'),
('2438', 'Serviço Social Autônomo', 0, '2026-03-25 13:03:41'),
('2446', 'Associação Privada', 0, '2026-03-25 13:03:41'),
('2454', 'Fundação Privada', 0, '2026-03-25 13:03:41'),
('2462', 'Organização Social (OS)', 0, '2026-03-25 13:03:41'),
('2470', 'Organização da Sociedade Civil de Interesse Público (OSCIP)', 0, '2026-03-25 13:03:41'),
('2489', 'Demais', 0, '2026-03-25 13:03:41'),
('2497', 'Cooperativa de Consumo', 1, '2026-03-25 13:03:41'),
('2500', 'Cooperativa de Crédito', 1, '2026-03-25 13:03:41'),
('2519', 'Cooperativa de Trabalho', 1, '2026-03-25 13:03:41'),
('2527', 'Cooperativa de Produção', 1, '2026-03-25 13:03:41'),
('2535', 'Cooperativa de Transporte', 1, '2026-03-25 13:03:41'),
('2543', 'Cooperativa de Saúde', 1, '2026-03-25 13:03:41'),
('2551', 'Cooperativa Educacional', 1, '2026-03-25 13:03:41'),
('2560', 'Cooperativa Agropecuária', 1, '2026-03-25 13:03:41'),
('2578', 'Cooperativa de Mineração', 1, '2026-03-25 13:03:41'),
('2586', 'Cooperativa de Habitação', 1, '2026-03-25 13:03:41'),
('2594', 'Cooperativa de Eletrificação', 1, '2026-03-25 13:03:41'),
('2608', 'Cooperativa de Laticínios', 1, '2026-03-25 13:03:41'),
('2616', 'Cooperativa de Transporte de Cargas', 1, '2026-03-25 13:03:41'),
('2624', 'Cooperativa de Transporte de Passageiros', 1, '2026-03-25 13:03:41'),
('2632', 'Cooperativa de Serviços Médicos', 1, '2026-03-25 13:03:41'),
('2640', 'Cooperativa de Serviços Odontológicos', 1, '2026-03-25 13:03:41'),
('2659', 'Cooperativa de Serviços Hospitalares', 1, '2026-03-25 13:03:41'),
('2667', 'Cooperativa de Serviços de Telecomunicações', 1, '2026-03-25 13:03:41'),
('2675', 'Cooperativa de Serviços de Informática', 1, '2026-03-25 13:03:41'),
('2683', 'Cooperativa de Serviços de Turismo', 1, '2026-03-25 13:03:41'),
('2691', 'Cooperativa de Serviços de Publicidade', 1, '2026-03-25 13:03:41'),
('2705', 'Cooperativa de Serviços de Seguros', 1, '2026-03-25 13:03:41'),
('2713', 'Cooperativa de Serviços de Engenharia', 1, '2026-03-25 13:03:41'),
('2721', 'Cooperativa de Serviços de Arquitetura', 1, '2026-03-25 13:03:41'),
('2730', 'Cooperativa de Serviços de Contabilidade', 1, '2026-03-25 13:03:41'),
('2748', 'Cooperativa de Serviços de Advocacia', 1, '2026-03-25 13:03:41'),
('2756', 'Cooperativa de Serviços de Psicologia', 1, '2026-03-25 13:03:41'),
('2764', 'Cooperativa de Serviços de Fisioterapia', 1, '2026-03-25 13:03:41'),
('2772', 'Cooperativa de Serviços de Nutrição', 1, '2026-03-25 13:03:41'),
('2780', 'Cooperativa de Serviços de Serviço Social', 1, '2026-03-25 13:03:41'),
('2799', 'Cooperativa de Serviços de Educação Física', 1, '2026-03-25 13:03:41'),
('2802', 'Cooperativa de Serviços de Administração', 1, '2026-03-25 13:03:41'),
('2810', 'Cooperativa de Serviços de Limpeza', 1, '2026-03-25 13:03:41'),
('2829', 'Cooperativa de Serviços de Segurança', 1, '2026-03-25 13:03:41'),
('2837', 'Cooperativa de Serviços de Conservação', 1, '2026-03-25 13:03:41'),
('2845', 'Cooperativa de Serviços de Jardinagem', 1, '2026-03-25 13:03:41'),
('2853', 'Cooperativa de Serviços de Manutenção', 1, '2026-03-25 13:03:41'),
('2861', 'Cooperativa de Serviços de Instalação', 1, '2026-03-25 13:03:41'),
('2870', 'Cooperativa de Serviços de Montagem', 1, '2026-03-25 13:03:41'),
('2888', 'Cooperativa de Serviços de Reparo', 1, '2026-03-25 13:03:41'),
('2896', 'Cooperativa de Serviços de Reciclagem', 1, '2026-03-25 13:03:41'),
('2900', 'Cooperativa de Serviços de Coleta de Resíduos', 1, '2026-03-25 13:03:41'),
('2918', 'Cooperativa de Serviços de Transporte Escolar', 1, '2026-03-25 13:03:41'),
('2926', 'Cooperativa de Serviços de Transporte Turístico', 1, '2026-03-25 13:03:41'),
('2934', 'Cooperativa de Serviços de Transporte de Valores', 1, '2026-03-25 13:03:41'),
('2942', 'Cooperativa de Serviços de Transporte Funerário', 1, '2026-03-25 13:03:41'),
('2950', 'Cooperativa de Serviços de Armazenamento', 1, '2026-03-25 13:03:41'),
('2969', 'Cooperativa de Serviços de Logística', 1, '2026-03-25 13:03:41'),
('2977', 'Cooperativa de Serviços de Distribuição', 1, '2026-03-25 13:03:41'),
('2985', 'Cooperativa de Serviços de Comercialização', 1, '2026-03-25 13:03:41'),
('2993', 'Cooperativa de Serviços de Representação', 1, '2026-03-25 13:03:41'),
('3000', 'Sociedade Anônima em Consórcio', 0, '2026-03-25 13:03:41'),
('3019', 'Sociedade em Comandita Simples', 1, '2026-03-25 13:03:41'),
('3027', 'Sociedade em Comandita por Ações', 0, '2026-03-25 13:03:41'),
('3031', 'Sociedade Anônima Fechada', 0, '2026-03-17 01:56:26'),
('3035', 'Sociedade em Nome Coletivo', 1, '2026-03-25 13:03:41'),
('3043', 'Sociedade em Conta de Participação', 0, '2026-03-25 13:03:41'),
('3051', 'Consórcio de Empresas', 0, '2026-03-25 13:03:41'),
('3060', 'Grupo de Sociedades', 0, '2026-03-25 13:03:41'),
('3078', 'Sociedade Cooperativa Europeia', 1, '2026-03-25 13:03:41'),
('3086', 'Sociedade de Advogados', 1, '2026-03-25 13:03:41'),
('3094', 'Sociedade de Profissionais Liberais', 1, '2026-03-25 13:03:41'),
('3108', 'Sociedade de Prestação de Serviços', 1, '2026-03-25 13:03:41'),
('3116', 'Sociedade de Crédito Imobiliário', 0, '2026-03-25 13:03:41'),
('3124', 'Sociedade de Arrendamento Mercantil', 0, '2026-03-25 13:03:41'),
('3132', 'Sociedade de Fomento Comercial', 0, '2026-03-25 13:03:41'),
('3140', 'Sociedade de Crédito ao Microempreendedor', 0, '2026-03-25 13:03:41'),
('3159', 'Sociedade de Capitalização', 0, '2026-03-25 13:03:41'),
('3167', 'Sociedade Seguradora', 0, '2026-03-25 13:03:41'),
('3175', 'Sociedade de Previdência Privada', 0, '2026-03-25 13:03:41'),
('3183', 'Sociedade de Investimento', 0, '2026-03-25 13:03:41'),
('3191', 'Sociedade de Participações', 0, '2026-03-25 13:03:41'),
('3205', 'Sociedade de Propósito Específico (SPE)', 0, '2026-03-25 13:03:41'),
('3213', 'Fundo de Investimento', 0, '2026-03-25 13:03:41'),
('3221', 'Clube de Investimento', 0, '2026-03-25 13:03:41'),
('3230', 'Carteira de Valores Mobiliários', 0, '2026-03-25 13:03:41'),
('3248', 'Empresa de Pequeno Porte (EPP)', 1, '2026-03-25 13:03:41'),
('3256', 'Microempreendedor Individual (MEI)', 1, '2026-03-25 13:03:41'),
('Empr', 'Natureza Jurídica Empresário (Individual)', 0, '2026-03-26 11:07:42'),
('Soci', 'Natureza Jurídica Sociedade Empresária Limitada - Aguardando descrição', 0, '2026-03-25 14:47:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `planos`
--

CREATE TABLE `planos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco_mensal` decimal(10,2) DEFAULT NULL,
  `preco_anual` decimal(10,2) DEFAULT NULL,
  `preco_consulta_avulsa` decimal(10,2) DEFAULT NULL,
  `limite_consultas_mensais` int(11) DEFAULT 0,
  `acesso_api` tinyint(1) DEFAULT 0,
  `relatorios_completos` tinyint(1) DEFAULT 1,
  `alertas_mudanca` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `planos`
--

INSERT INTO `planos` (`id`, `codigo`, `nome`, `descricao`, `preco_mensal`, `preco_anual`, `preco_consulta_avulsa`, `limite_consultas_mensais`, `acesso_api`, `relatorios_completos`, `alertas_mudanca`, `created_at`) VALUES
(1, 'free', 'Gratuito', 'Consultas básicas limitadas', 0.00, 0.00, 0.00, 3, 0, 1, 0, '2026-03-17 01:56:26'),
(2, 'basico', 'Básico', 'Para consultas eventuais', 49.90, 499.90, 29.90, 30, 0, 1, 0, '2026-03-17 01:56:26'),
(3, 'profissional', 'Profissional', 'Para contadores', 89.90, 899.90, NULL, 200, 0, 1, 0, '2026-03-17 01:56:26'),
(4, 'enterprise', 'Enterprise', 'Acesso ilimitado + API', 299.90, 2999.90, NULL, 999999, 1, 1, 0, '2026-03-17 01:56:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_gerados`
--

CREATE TABLE `relatorios_gerados` (
  `id` bigint(20) NOT NULL,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `usuario_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `tipo_relatorio` enum('completo','resumido','comparativo','reforma') DEFAULT 'completo',
  `dados_relatorio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`dados_relatorio`)),
  `arquivo_pdf` varchar(255) DEFAULT NULL,
  `compartilhado` tinyint(1) DEFAULT 0,
  `token_compartilhamento` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `uuid` char(36) NOT NULL DEFAULT uuid(),
  `nome` varchar(200) NOT NULL,
  `email` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `senha_hash` varchar(255) NOT NULL,
  `cpf_cnpj` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `tipo_usuario` enum('admin','assinante','consultor_avulso','empresa_parceira') DEFAULT 'assinante',
  `plano` enum('free','basico','profissional','enterprise') DEFAULT 'free',
  `creditos_restantes` int(11) DEFAULT 0,
  `data_expiracao_plano` date DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `ultimo_acesso` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alertas_empresas`
--
ALTER TABLE `alertas_empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_alerta` (`usuario_id`,`empresa_id`,`tipo_alerta`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Índices de tabela `aliquotas_municipais`
--
ALTER TABLE `aliquotas_municipais`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_local_item` (`uf`,`cidade`,`iss_item_lista`,`data_vigencia`),
  ADD KEY `idx_local` (`uf`,`cidade`),
  ADD KEY `idx_item` (`iss_item_lista`);

--
-- Índices de tabela `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_api_key` (`api_key`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `assinaturas`
--
ALTER TABLE `assinaturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plano_id` (`plano_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_datas` (`data_inicio`,`data_fim`);

--
-- Índices de tabela `cache_cnpj`
--
ALTER TABLE `cache_cnpj`
  ADD PRIMARY KEY (`cnpj`),
  ADD KEY `idx_expira` (`expira_em`);

--
-- Índices de tabela `cnaes`
--
ALTER TABLE `cnaes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_secao` (`secao`);
ALTER TABLE `cnaes` ADD FULLTEXT KEY `idx_busca` (`descricao`,`descricao_completa`);

--
-- Índices de tabela `cnae_anexo_simples`
--
ALTER TABLE `cnae_anexo_simples`
  ADD PRIMARY KEY (`cnae_codigo`),
  ADD KEY `idx_anexo` (`anexo`);

--
-- Índices de tabela `cnae_impacto_reforma`
--
ALTER TABLE `cnae_impacto_reforma`
  ADD PRIMARY KEY (`cnae_codigo`);

--
-- Índices de tabela `cnae_servico_iss`
--
ALTER TABLE `cnae_servico_iss`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cnae_iss` (`cnae_codigo`,`iss_item_lista`),
  ADD KEY `iss_item_lista` (`iss_item_lista`),
  ADD KEY `idx_cnae` (`cnae_codigo`);

--
-- Índices de tabela `codigos_servico_iss`
--
ALTER TABLE `codigos_servico_iss`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_item` (`item_lista`);
ALTER TABLE `codigos_servico_iss` ADD FULLTEXT KEY `idx_descricao` (`descricao`);

--
-- Índices de tabela `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `cnpj` (`cnpj`),
  ADD KEY `natureza_juridica_codigo` (`natureza_juridica_codigo`),
  ADD KEY `idx_cnpj` (`cnpj`),
  ADD KEY `idx_razao_social` (`razao_social`(100)),
  ADD KEY `idx_situacao` (`situacao_cadastral`),
  ADD KEY `idx_cidade_uf` (`cidade`,`uf`),
  ADD KEY `idx_cnae` (`cnae_principal`),
  ADD KEY `idx_regime_sugerido` (`regime_tributario_sugerido`),
  ADD KEY `idx_impacto_reforma` (`impacto_reforma_estimado`);
ALTER TABLE `empresas` ADD FULLTEXT KEY `idx_busca_empresa` (`razao_social`,`nome_fantasia`);

--
-- Índices de tabela `historico_consultas`
--
ALTER TABLE `historico_consultas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_data` (`usuario_id`,`created_at`),
  ADD KEY `idx_cnpj` (`cnpj_consultado`),
  ADD KEY `idx_data` (`created_at`);

--
-- Índices de tabela `historico_mudancas_empresa`
--
ALTER TABLE `historico_mudancas_empresa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_empresa` (`empresa_id`),
  ADD KEY `idx_data` (`data_mudanca`);

--
-- Índices de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_acao` (`acao`),
  ADD KEY `idx_data` (`created_at`);

--
-- Índices de tabela `natureza_juridica`
--
ALTER TABLE `natureza_juridica`
  ADD PRIMARY KEY (`codigo`);

--
-- Índices de tabela `planos`
--
ALTER TABLE `planos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Índices de tabela `relatorios_gerados`
--
ALTER TABLE `relatorios_gerados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `token_compartilhamento` (`token_compartilhamento`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_empresa` (`empresa_id`),
  ADD KEY `idx_token` (`token_compartilhamento`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_plano` (`plano`),
  ADD KEY `idx_ativo` (`ativo`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alertas_empresas`
--
ALTER TABLE `alertas_empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `aliquotas_municipais`
--
ALTER TABLE `aliquotas_municipais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `assinaturas`
--
ALTER TABLE `assinaturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cnaes`
--
ALTER TABLE `cnaes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `cnae_servico_iss`
--
ALTER TABLE `cnae_servico_iss`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `codigos_servico_iss`
--
ALTER TABLE `codigos_servico_iss`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `historico_consultas`
--
ALTER TABLE `historico_consultas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `historico_mudancas_empresa`
--
ALTER TABLE `historico_mudancas_empresa`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `planos`
--
ALTER TABLE `planos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `relatorios_gerados`
--
ALTER TABLE `relatorios_gerados`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `alertas_empresas`
--
ALTER TABLE `alertas_empresas`
  ADD CONSTRAINT `alertas_empresas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alertas_empresas_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `aliquotas_municipais`
--
ALTER TABLE `aliquotas_municipais`
  ADD CONSTRAINT `aliquotas_municipais_ibfk_1` FOREIGN KEY (`iss_item_lista`) REFERENCES `codigos_servico_iss` (`item_lista`);

--
-- Restrições para tabelas `assinaturas`
--
ALTER TABLE `assinaturas`
  ADD CONSTRAINT `assinaturas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assinaturas_ibfk_2` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`);

--
-- Restrições para tabelas `cnae_anexo_simples`
--
ALTER TABLE `cnae_anexo_simples`
  ADD CONSTRAINT `cnae_anexo_simples_ibfk_1` FOREIGN KEY (`cnae_codigo`) REFERENCES `cnaes` (`codigo`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cnae_impacto_reforma`
--
ALTER TABLE `cnae_impacto_reforma`
  ADD CONSTRAINT `cnae_impacto_reforma_ibfk_1` FOREIGN KEY (`cnae_codigo`) REFERENCES `cnaes` (`codigo`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cnae_servico_iss`
--
ALTER TABLE `cnae_servico_iss`
  ADD CONSTRAINT `cnae_servico_iss_ibfk_1` FOREIGN KEY (`cnae_codigo`) REFERENCES `cnaes` (`codigo`) ON DELETE CASCADE,
  ADD CONSTRAINT `cnae_servico_iss_ibfk_2` FOREIGN KEY (`iss_item_lista`) REFERENCES `codigos_servico_iss` (`item_lista`);

--
-- Restrições para tabelas `empresas`
--
ALTER TABLE `empresas`
  ADD CONSTRAINT `empresas_ibfk_1` FOREIGN KEY (`cnae_principal`) REFERENCES `cnaes` (`codigo`),
  ADD CONSTRAINT `empresas_ibfk_2` FOREIGN KEY (`natureza_juridica_codigo`) REFERENCES `natureza_juridica` (`codigo`);

--
-- Restrições para tabelas `historico_consultas`
--
ALTER TABLE `historico_consultas`
  ADD CONSTRAINT `historico_consultas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `historico_mudancas_empresa`
--
ALTER TABLE `historico_mudancas_empresa`
  ADD CONSTRAINT `historico_mudancas_empresa_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD CONSTRAINT `logs_sistema_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `relatorios_gerados`
--
ALTER TABLE `relatorios_gerados`
  ADD CONSTRAINT `relatorios_gerados_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `relatorios_gerados_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
