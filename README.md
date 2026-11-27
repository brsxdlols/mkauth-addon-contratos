# Addon Contratos para MK Auth

Instalador automatizado do addon de assinatura e controle de contratos para MK Auth.

## O que a instalação faz

- instala o addon em `/opt/mk-auth/admin/addons/contratos`;
- registra o atalho **Contratos Assinados** no menu **Clientes**;
- cria o diretório de PDFs em `/opt/mk-auth/admin/arquivos`;
- permite enviar ou trocar a assinatura do provedor diretamente pela barra de ações do addon;
- valida PNG, JPG, WEBP e GIF de até 5 MB e salva a imagem em `/opt/mk-auth/mkfiles/assinatura_provedor`, sem extensão;
- converte automaticamente a assinatura para PNG com fundo branco e traços pretos, neutralizando fundos coloridos, papel fotografado e transparência;
- versiona automaticamente o CSS e o JavaScript para impedir layouts antigos armazenados no cache do navegador;
- gera o PDF com paginação protegida, sem cortar parágrafos ou separar o bloco de assinaturas; mantém a selfie junto ao registro de acesso;
- inclui o gerador de PDF no próprio addon, sem depender de CDN durante a assinatura;
- oferece fluxo mais leve para iPhone, alternativa de selfie por câmera/arquivo e confirmação real do upload;
- registra diagnóstico por etapa em `addons/contratos/logs`, facilitando localizar falhas em celulares;
- exibe cards clicáveis de contratos ativos, a vencer em 60 dias e vencidos;
- permite renovar a vigência pelo próprio addon, com histórico preservado no banco;
- mantém até 20 backups das assinaturas anteriores em `/var/backups/mkauth-addon-contratos-assinaturas`;
- preserva todos os modelos existentes, inclusive os dois modelos iniciais já personalizados;
- nunca atualiza nem exclui modelos existentes, documentos, assinaturas ou historico;
- cria backup dos arquivos e do menu antes de atualizar;
- preserva configuração local, conexão com o banco e logs do addon.

O instalador completo instala ou atualiza o codigo e provisiona os dois modelos somente em uma instalacao nova e identificada. Modelos existentes, mesmo editados ou inativos, permanecem intactos. O historico de provisionamento impede recriar um modelo removido. Se uma instalacao antiga possui modelos sem identificadores reconheciveis, nenhum modelo e recriado por suposicao. PDFs, anexos, assinaturas e historico permanecem intactos.

## Instalação via GitHub

Execute como `root` no servidor MK Auth:

```sh
curl -fsSL https://raw.githubusercontent.com/brsxdlols/mkauth-addon-contratos/main/installers/github-install.sh | sh
```

O instalador remoto resolve `installers/stable-ref` para o commit estavel publicado. A dashboard chama esse mesmo instalador, sem manter uma copia diferente do addon.

Para testar diretamente o conteúdo mais recente da branch `main`:

```sh
curl -fsSL https://raw.githubusercontent.com/brsxdlols/mkauth-addon-contratos/main/installers/github-install.sh \
  | CONTRATOS_REF=main sh
```

Depois da instalação, acesse:

```text
https://SEU-DOMINIO/admin/addons/contratos/
```

Limpe o cache do navegador com `Ctrl+F5` caso o item de menu ainda não apareça.

## Instalação a partir de um checkout

```sh
git clone https://github.com/brsxdlols/mkauth-addon-contratos.git
cd mkauth-addon-contratos
sh installers/install.sh
```

## Atualização

O comando de instalação é idempotente e pode ser executado novamente. Ele atualiza o codigo e consolida o menu. O provisionamento e somente de insercao inicial: nao sobrescreve texto, nome, atividade ou padrao de modelos existentes.

## Backup e rollback

Cada execução mostra o caminho do backup criado, por exemplo:

```text
/root/backups/mkauth-addon-contratos-20260728-220000-v1.1.0
```

Para restaurar:

```sh
sh installers/rollback.sh /root/backups/mkauth-addon-contratos-DATA-HORA-vVERSAO-PID
```

No checkout não estando mais disponível, baixe o script da mesma versão antes de executar o rollback.

## Requisitos

- MK Auth instalado em `/opt/mk-auth`;
- acesso `root`;
- PHP CLI com `mysqli`;
- extensão PHP `gd`;
- banco MK Auth disponível para o funcionamento do addon;
- `curl` ou `wget` para instalação remota.

A atualização preserva `config.php` e `database/conexao.php` da instalação existente. O rollback restaura somente arquivos e menu; não restaura versões antigas dos modelos no banco.

Use o instalador v1.4.1 ou posterior para preservar os modelos. Os instaladores de versões anteriores podem sobrescrevê-los.

## Estrutura

```text
addons/contratos/       arquivos do addon
installers/install.sh   instalação local e idempotente
installers/github-install.sh
                        download e instalação via GitHub
installers/rollback.sh  restauração de backup
scripts/validate.sh     validação do pacote
```

## Correção de contratos sem modelo — v1.4.0

- Exibe PDFs de clientes ativos mesmo quando o modelo vinculado não existe ou está sem texto, com status e filtro de pendências e sem apresentar vencimento presumido.
- Bloqueia a abertura para assinatura, o upload e a renovação quando não existe modelo com texto vinculado ao cliente.
- Aceita qualquer modelo vinculado do MK Auth; não se limita aos modelos do instalador.
- Preserva os PDFs existentes. A pendência indica o estado atual do cadastro e não comprova o conteúdo do PDF ou como estava o cadastro na data da assinatura.
- Para documentos incompletos, revisar o PDF, escolher o modelo correto e providenciar nova assinatura; a atualização não reconstrói contratos antigos nem escolhe modelos automaticamente.

## Contratos existentes e clientes sem documento — v1.4.0

O card Todos inclui todos os clientes ativos. Sem contrato identifica quem não tem documento no addon; o botão Anexar contrato permite enviar PDF, JPG ou PNG (até 20 MB, sujeito ao limite PHP). Para contratos físicos com várias páginas, envie um PDF único.

O upload exige sessão administrativa e token CSRF, valida o tipo real do arquivo, registra operador, origem e datas e recusa substituir documentos existentes. Aceita contratos externos independentemente do modelo cadastrado. A data original é obrigatória; sem data de vencimento, exibe Anexado e não presume prazo. Anexos com vencimento participam dos cards de vigência. Os anexos também aparecem no backup de documentos.

Não cria assinatura digital nem altera o cadastro do cliente. Documentos e metadados são preservados nas atualizações, em admin/arquivos e sis_contrato_anexo. Inclua ambos no backup do servidor. O botão Renovar permanece destinado ao fluxo de contratos digitais do addon.

## PDF em celulares e confirmação de exclusão — v1.4.3

Renderiza uma página A4 por vez, limitando o tamanho do canvas e verificando conteúdo antes do envio. O servidor também recusa PDFs do gerador cujas imagens JPEG sejam inteiramente brancas. A verificação não constitui validação do texto ou da assinatura.

A exclusão exige sessão administrativa, confirmação em formulário e token CSRF. O arquivo selecionado é apagado definitivamente após confirmação. Históricos criados por versões anteriores não são removidos automaticamente. Reenvios preservam o PDF anterior em .contratos-anteriores. A atualização não recria PDFs brancos já recebidos; estes precisam de nova assinatura. Modelos no banco continuam preservados.


## Variáveis nativas e produtos no contrato

A montagem para assinatura usa o mecanismo `MKAC_VARS` do MK-Auth para as variáveis do modelo, em vez de manter uma lista parcial no addon. `%produtos%` inclui a tabela de produtos vinculados ao cliente, com suas quantidades; `%servicos%` inclui os serviços e seus valores. A variável opcional `%equipamentos%` continua disponível quando o respectivo módulo está instalado. Modelos não precisam ser recriados.

O renderizador é somente de leitura: não altera estoque, cadastro, modelo, seleção nativa de impressão ou PDFs já assinados. Uma variável desconhecida pelo próprio MK-Auth ou uma versão incompatível do mecanismo apresenta um erro antes de disponibilizar a assinatura, evitando um documento incompleto. Documentos anteriores não são reconstruídos automaticamente.

O arquivo `functions/native_contract_renderer.php` também é distribuído, com conteúdo idêntico, no repositório `brsxdlols/mkauth-dashboard-busca-suite`, em `addons/shared/native_contract_renderer.php`. Atualizações desse componente devem ser publicadas nos dois repositórios. Cada addon funciona de forma independente.

Teste isolado, sem banco ou credenciais: `php scripts/test-native-contract.php addons/contratos/functions/native_contract_renderer.php`. A Busca Inteligente oferece separadamente a prévia do modelo atual e o acesso ao documento assinado.


## v1.5.0 — instalacao unificada e preservacao

Use somente o comando completo acima; nao e necessario baixar e executar `seed-contracts.php` separadamente. O antigo utilitario foi alterado para nao sobrescrever modelos. O historico `addon_contratos_model_seed` e mantido fora dos arquivos substituidos e o rollback de codigo nao restaura nem apaga esse historico ou documentos.

A atualizacao preserva tambem arquivos locais nao pertencentes ao pacote, inclusive uploads dentro da pasta do addon. A previa da dashboard prefere o renderizador oficial instalado; uma atualizacao do addon passa a valer para a previa sem duplicar o mecanismo. As variaveis se aplicam a novas geracoes, sem reconstruir PDFs anteriores.

Teste de preservacao em banco isolado: `php scripts/test-seed-preservation.php installers/seed-contracts-lib.php addons/contratos`. Exige um servidor SQL de testes configurado por `TEST_SQL_HOST`, `TEST_SQL_USER`, `TEST_SQL_PASSWORD` e `TEST_SQL_PORT`; cria e remove somente uma base aleatoria com prefixo `suite_contract_test_`.
