# Configuração do Diretório de Contratos

## 📁 Como Configurar o Caminho dos PDFs

Todos os arquivos de contratos agora utilizam um caminho centralizado definido no arquivo `config.php`.

### Arquivo de Configuração

**Localização:** `/opt/mk-auth/admin/addons/contratos/config.php`

```php
define('CONTRATOS_DIR', '/opt/mk-auth/admin/arquivos/');
```

### Como Alterar o Diretório

Para alterar onde os PDFs dos contratos são salvos, edite apenas uma linha no arquivo `config.php`:

```php
// Opção 1: Usar a pasta arquivos (padrão - RECOMENDADO)
define('CONTRATOS_DIR', '/opt/mk-auth/admin/arquivos/');

// Opção 2: Usar disco virtual (se preferir)
define('CONTRATOS_DIR', '/opt/mk-auth/central/disco_virtual/');

// Opção 3: Usar caminho customizado
define('CONTRATOS_DIR', '/var/www/contratos/');
```

**Observação:** Por padrão, o addon usa `/opt/mk-auth/admin/arquivos/` que já tem as permissões do AppArmor configuradas.

### Arquivos que Utilizam esta Configuração

1. **`upload.php`** - Upload dos PDFs assinados
2. **`functions/dados_index.php`** - Listagem de contratos
3. **`functions/dados_contrato.php`** - Validação de contratos pendentes

### ✅ Vantagens da Centralização

- ✓ Alterar o caminho em um único lugar
- ✓ Facilita manutenção e atualizações
- ✓ Reduz erros de inconsistência
- ✓ Documentação clara e objetiva

### 🔧 Aplicando as Mudanças

Após editar o `config.php`, as mudanças são aplicadas automaticamente. Não é necessário reiniciar nenhum serviço.

### 📝 Observações

- Certifique-se de que o diretório configurado existe
- O diretório deve ter permissões 777 ou www-data:www-data
- Subpastas são criadas automaticamente pelo sistema (UUID do cliente)

