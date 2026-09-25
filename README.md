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
- não cria, atualiza nem exclui registros de contratos na instalação ou no rollback;
- cria backup dos arquivos e do menu antes de atualizar;
- preserva configuração local, conexão com o banco e logs do addon.

O instalador completo instala ou atualiza o addon sem executar o script de modelos. Em instalações novas, os modelos devem ser cadastrados pelo administrador no MK Auth. PDFs, anexos, assinaturas e histórico existentes permanecem no servidor.

## Instalação via GitHub

Execute como `root` no servidor MK Auth:

```sh
curl -fsSL https://raw.githubusercontent.com/brsxdlols/mkauth-addon-contratos/main/installers/github-install.sh | sh
```

O instalador remoto usa por padrão a versão estável `v1.4.2`.

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

O comando de instalação é idempotente e pode ser executado novamente. Ele atualiza os arquivos e consolida o menu, sem alterar modelos ou registros de contratos.

## Backup e rollback

Cada execução mostra o caminho do backup criado, por exemplo:

```text
/root/backups/mkauth-addon-contratos-20260728-220000-v1.1.0
```

Para restaurar:

```sh
sh installers/rollback.sh /root/backups/mkauth-addon-contratos-20260728-220000-v1.1.0
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

## PDF em celulares e confirmação de exclusão — v1.4.2

Renderiza uma página A4 por vez, limitando o tamanho do canvas e verificando conteúdo antes do envio. O servidor também recusa PDFs do gerador cujas imagens JPEG sejam inteiramente brancas. A verificação não constitui validação do texto ou da assinatura.

A exclusão exige sessão administrativa, confirmação em formulário e token CSRF. O arquivo é movido para .contratos-excluidos na pasta do cliente, preservando o original. Reenvios preservam o PDF anterior em .contratos-anteriores. A atualização não recria PDFs brancos já recebidos; estes precisam de nova assinatura. Modelos no banco continuam preservados.
