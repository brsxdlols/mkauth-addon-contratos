# Correção AppArmor - Addon Contratos

## 🔒 Problema Identificado

O AppArmor estava **bloqueando** a gravação de arquivos PDF na pasta `/opt/mk-auth/admin/arquivos/`.

### Evidências dos Logs:

```
apparmor="DENIED" operation="mknod" profile="/opt/mk-auth/hhvm/8-0-30/bin/php-central" 
name="/opt/mk-auth/admin/arquivos/2570b23e-ed07-11e6-ac3b-20cf30a8332c/contrato_xxx.pdf"
```

## ✅ Solução Rápida (RECOMENDADO)

### Use o Script Automatizado:

```bash
cd /opt/mk-auth/admin/addons/contratos
sudo bash corrigir_apparmor.sh
```

Este script irá:
- ✓ Verificar se AppArmor está ativo
- ✓ Fazer backup do perfil atual
- ✓ Adicionar a regra necessária (se não existir)
- ✓ Recarregar o AppArmor com ambos os métodos
- ✓ Testar se a correção funcionou

---

## 🔧 Solução Manual (Passo a Passo)

### 1. Backup do Perfil
```bash
cp /etc/apparmor.d/sistema.php-central /etc/apparmor.d/sistema.php-central.backup-$(date +%Y%m%d_%H%M%S)
```

### 2. Adição da Permissão

Edite o arquivo `/etc/apparmor.d/sistema.php-central` e adicione a linha:

```
/opt/mk-auth/admin/arquivos/** mrwlkix,
```

**Onde adicionar:** Logo após a linha `/opt/mk-auth/central/disco_virtual/** rw,`

**Permissões concedidas:**
- `m` = mmap
- `r` = read (leitura)
- `w` = write (escrita)
- `l` = link
- `k` = lock
- `i` = inherit
- `x` = execute

### 3. Recarregamento do Perfil

```bash
apparmor_parser -r /etc/apparmor.d/sistema.php-central
```

### ⚠️ IMPORTANTE - Versão 25.08

Se após aplicar o comando acima o erro persistir, execute:

```bash
# Forçar recarga de TODOS os perfis AppArmor
systemctl reload apparmor

# OU reiniciar o serviço AppArmor completamente
systemctl restart apparmor

# Verificar se foi aplicado
aa-status | grep php-central
```

### 4. Verificação

```bash
# Testar permissões
sudo -u www-data touch /opt/mk-auth/admin/arquivos/teste.txt
# Resultado esperado: ✅ Sucesso

# Limpar arquivo de teste
rm /opt/mk-auth/admin/arquivos/teste.txt
```

## 📝 Observações

- O perfil já tinha permissão para `/opt/mk-auth/central/disco_virtual/** rw`
- A nova permissão permite que o addon grave em `/opt/mk-auth/admin/arquivos/`
- Backup criado antes da alteração
- Nenhum reinício de serviço foi necessário

## 🧪 Teste de Validação

```bash
sudo -u www-data touch /opt/mk-auth/admin/arquivos/teste.txt
# Resultado: ✅ Sucesso
```

## 🔄 Para Reverter (se necessário)

```bash
cp /etc/apparmor.d/sistema.php-central.backup-* /etc/apparmor.d/sistema.php-central
apparmor_parser -r /etc/apparmor.d/sistema.php-central
systemctl reload apparmor
```

---

## 🐛 Problema Específico da Versão 25.08

Na versão 25.08 do Debian, descobrimos que:

1. **A regra pode existir mas não ser aplicada:** Mesmo com a linha `/opt/mk-auth/admin/arquivos/** mrwlkix,` presente no arquivo de configuração, o AppArmor pode não reconhecer a permissão.

2. **Solução:** É necessário usar AMBOS os métodos de recarga:
   ```bash
   apparmor_parser -r /etc/apparmor.d/sistema.php-central
   systemctl reload apparmor
   ```

3. **Por que isso acontece?** O Debian 25.08 pode ter mudanças no cache do AppArmor que requerem reload completo do serviço, não apenas do perfil específico.

## 🔍 Diagnóstico Rápido

Para verificar se o problema é AppArmor:

```bash
# 1. Verificar logs recentes
grep -i "DENIED.*arquivos" /var/log/syslog | tail -5

# 2. Verificar se a regra existe
grep "admin/arquivos" /etc/apparmor.d/sistema.php-central

# 3. Verificar perfis ativos
aa-status | grep php-central

# 4. Testar permissões
sudo -u www-data touch /opt/mk-auth/admin/arquivos/teste.txt
```

---

**Data da primeira correção:** 13 de novembro de 2025  
**Data da correção v2 (Debian 25.08):** 24 de novembro de 2025
