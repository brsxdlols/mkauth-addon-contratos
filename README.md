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
- gera o PDF com paginação protegida, sem cortar parágrafos ou separar o bloco de assinaturas;
- inclui o gerador de PDF no próprio addon, sem depender de CDN durante a assinatura;
- oferece fluxo mais leve para iPhone, alternativa de selfie por câmera/arquivo e confirmação real do upload;
- registra diagnóstico por etapa em `addons/contratos/logs`, facilitando localizar falhas em celulares;
- exibe cards clicáveis de contratos ativos, a vencer em 60 dias e vencidos;
- permite renovar a vigência pelo próprio addon, com histórico preservado no banco;
- mantém até 20 backups das assinaturas anteriores em `/var/backups/mkauth-addon-contratos-assinaturas`;
- cria ou atualiza, sem duplicar, estes dois contratos nativos do MK Auth:
  - **CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET COM FIDELIDADE DE 1 ANO**
  - **CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET**
- cria backup automático dos arquivos, do `addon.js` e dos dois registros do banco antes de cada instalação;
- valida a sintaxe PHP, o menu e os registros do banco antes de concluir.

Contratos já existentes com outros nomes ou códigos não são removidos. Se um dos dois contratos iniciais já existir, seu código é preservado para não quebrar clientes vinculados.

## Instalação via GitHub

Execute como `root` no servidor MK Auth:

```sh
curl -fsSL https://raw.githubusercontent.com/brsxdlols/mkauth-addon-contratos/main/installers/github-install.sh | sh
```

O instalador remoto usa por padrão a versão estável `v1.3.2`.

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

O comando de instalação é idempotente e pode ser executado novamente. Ele atualiza os arquivos, consolida o menu em um único bloco e atualiza os dois modelos sem criar duplicatas.

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
- cliente MySQL/MariaDB e `mysqldump`;
- `curl` ou `wget` para instalação remota.

O banco padrão do MK Auth é utilizado. Se a senha local do MySQL tiver sido alterada, informe-a apenas para a execução:

```sh
MKAUTH_DB_PASSWORD='senha-local' sh installers/install.sh
```

## Estrutura

```text
addons/contratos/       arquivos do addon
installers/install.sh   instalação local e idempotente
installers/github-install.sh
                        download e instalação via GitHub
installers/rollback.sh  restauração de backup
scripts/validate.sh     validação do pacote
```

## Correção de contratos sem modelo — v1.3.2

- Exibe PDFs de clientes ativos mesmo quando o modelo vinculado não existe ou está sem texto, com status e filtro de pendências e sem apresentar vencimento presumido.
- Bloqueia a abertura para assinatura, o upload e a renovação quando não existe modelo com texto vinculado ao cliente.
- Aceita qualquer modelo vinculado do MK Auth; não se limita aos modelos do instalador.
- Preserva os PDFs existentes. A pendência indica o estado atual do cadastro e não comprova o conteúdo do PDF ou como estava o cadastro na data da assinatura.
- Para documentos incompletos, revisar o PDF, escolher o modelo correto e providenciar nova assinatura; a atualização não reconstrói contratos antigos nem escolhe modelos automaticamente.
