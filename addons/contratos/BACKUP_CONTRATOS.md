# Funcionalidade de Backup de Contratos

## 📋 Descrição

Esta funcionalidade permite fazer o download de todos os contratos em PDF de uma só vez, gerando um arquivo ZIP compactado.

## ✨ Características

- **Backup Completo**: Baixa todos os contratos cadastrados no sistema
- **Organização**: Cada arquivo PDF é renomeado com o nome do cliente e código
- **Interface Intuitiva**: Botão visual na barra de ações principal
- **Feedback Visual**: Indicador de loading durante o processamento
- **Validação**: Verifica a existência dos arquivos antes de incluir no backup

## 🎯 Como Usar

1. Na página principal do addon de Contratos, localize o botão **"Baixar Todos os Contratos"** na barra de ações
2. Clique no botão
3. Confirme a ação na caixa de diálogo
4. Aguarde o processamento (o botão mostrará "Gerando backup...")
5. O arquivo ZIP será baixado automaticamente quando pronto

## 📁 Estrutura de Arquivos

### Arquivos Criados

- **backup_contratos.php**: Script backend que gera o arquivo ZIP
- Modificações em:
  - `index.php`: Adicionado botão de backup
  - `css/index.css`: Estilos para a barra de ações e botão
  - `js/index.js`: Função JavaScript para executar o backup

### Nome do Arquivo Gerado

O arquivo ZIP segue o padrão: `backup_contratos_YYYY-MM-DD_HH-mm-ss.zip`

Exemplo: `backup_contratos_2025-11-19_15-30-45.zip`

### Estrutura Interna do ZIP

Cada contrato é renomeado seguindo o padrão:
```
NomeDoCliente_CodigoCliente.pdf
```

Exemplo:
```
João_Silva_1234.pdf
Maria_Santos_5678.pdf
```

## 🔒 Segurança

- Verifica a sessão do usuário antes de executar
- Valida a existência e permissões de leitura dos arquivos
- Sanitiza nomes de arquivos para evitar problemas
- Remove arquivos temporários após o download

## ⚙️ Requisitos Técnicos

- PHP com extensão ZipArchive habilitada
- Permissões de leitura no diretório `/opt/mk-auth/admin/arquivos/`
- Permissões de escrita no diretório temporário do sistema

## 🐛 Tratamento de Erros

A funcionalidade trata os seguintes cenários:
- Erro ao criar arquivo ZIP
- Arquivos de contrato não encontrados
- Problemas de permissão de leitura
- Falha na conexão com banco de dados

## 💡 Observações

- O processamento pode levar alguns minutos dependendo da quantidade de contratos
- Apenas contratos de clientes ativos são incluídos
- Arquivos corrompidos ou inacessíveis são registrados no log mas não impedem o backup dos demais

## 🎨 Interface

### Desktop
O botão aparece horizontalmente ao lado dos filtros e busca, formando uma barra de ações completa.

### Mobile
Em telas menores, o botão se adapta e ocupa a largura total, mantendo a usabilidade.

### Temas
O botão se adapta automaticamente aos temas disponíveis (Padrão, Escuro, Oceano, Pôr do Sol, Natureza).
