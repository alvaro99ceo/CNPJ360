# 📊 CNPJ 360 - Sistema de Consulta e Análise de Empresas

Sistema completo para consulta de CNPJs, análise tributária, enquadramento Simples Nacional, impacto da Reforma Tributária e gestão de assinaturas.

## 🚀 Funcionalidades Principais

- ✅ **Consulta completa de CNPJs** com dados da Receita Federal
- ✅ **Análise tributária inteligente** (Simples Nacional, Lucro Presumido, Lucro Real)
- ✅ **Enquadramento automático** no anexo do Simples Nacional com cálculo do Fator R
- ✅ **Impacto da Reforma Tributária** (IBS/CBS) por CNAE
- ✅ **Sistema de assinaturas** com planos Free, Básico, Profissional e Enterprise
- ✅ **API REST** para integração com sistemas terceiros
- ✅ **Alertas de mudanças cadastrais** em empresas monitoradas
- ✅ **Cache de consultas** para otimização de performance
- ✅ **Relatórios completos** em JSON/PDF
- ✅ **Histórico completo** de consultas e logs do sistema

## 🗂️ Estrutura do Banco de Dados

### 📌 Tabelas Principais

| Tabela | Descrição |
|--------|-----------|
| `empresas` | Dados cadastrais completos das empresas (CNPJ, sócios, CNAEs) |
| `cnaes` | Classificação Nacional de Atividades Econômicas |
| `usuarios` | Usuários do sistema (assinantes, administradores) |
| `assinaturas` | Planos e períodos de assinatura |
| `api_keys` | Chaves de acesso para API externa |
| `cache_cnpj` | Cache de consultas para evitar chamadas repetidas |
| `historico_consultas` | Log de todas as consultas realizadas |
| `alertas_empresas` | Configuração de alertas por usuário/empresa |

### 📌 Tabelas de Análise Tributária

| Tabela | Descrição |
|--------|-----------|
| `cnae_anexo_simples` | Mapeamento CNAE → Anexo do Simples Nacional |
| `cnae_impacto_reforma` | Estimativa de impacto da Reforma Tributária por CNAE |
| `cnae_servico_iss` | Relação CNAE → Item da lista de serviços do ISS |
| `codigos_servico_iss` | Itens da lista de serviços do ISS (Lei Complementar 116/2003) |
| `aliquotas_municipais` | Alíquotas de ISS por município e tipo de serviço |
| `natureza_juridica` | Naturezas jurídicas e permissão para Simples Nacional |

### 📌 Tabelas de Log e Auditoria

| Tabela | Descrição |
|--------|-----------|
| `logs_sistema` | Logs de ações, erros e eventos do sistema |
| `historico_mudancas_empresa` | Histórico de alterações em empresas monitoradas |
| `relatorios_gerados` | Relatórios salvos e compartilháveis |


