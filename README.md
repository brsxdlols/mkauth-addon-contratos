# Addon Contratos para MK Auth

Instalador automatizado do addon de assinatura e controle de contratos para MK Auth.

## O que a instalação faz

- instala o addon em `/opt/mk-auth/admin/addons/contratos`;
- registra o atalho **Contratos Assinados** no menu **Clientes**;
- cria o diretório de PDFs em `/opt/mk-auth/admin/arquivos`;
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

O instalador remoto usa por padrão a versão estável `v1.0.0`.

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
/root/backups/mkauth-addon-contratos-20260728-220000-v1.0.0
```

Para restaurar:

```sh
sh installers/rollback.sh /root/backups/mkauth-addon-contratos-20260728-220000-v1.0.0
```

No checkout não estando mais disponível, baixe o script da mesma versão antes de executar o rollback.

## Requisitos

- MK Auth instalado em `/opt/mk-auth`;
- acesso `root`;
- PHP CLI com `mysqli`;
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
