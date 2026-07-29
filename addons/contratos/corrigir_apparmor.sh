#!/bin/bash
#
# Script de Correção AppArmor - Addon Contratos
# Versão: 2.0 (compatível com Debian 25.08)
#

set -e

echo "======================================"
echo "  Correção AppArmor - Addon Contratos"
echo "======================================"
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}✗ Este script precisa ser executado como root${NC}"
    echo "Use: sudo bash $0"
    exit 1
fi

# Verificar se AppArmor está instalado
if ! command -v aa-status &> /dev/null; then
    echo -e "${RED}✗ AppArmor não está instalado${NC}"
    exit 1
fi

# Verificar se AppArmor está ativo
if ! aa-status &> /dev/null; then
    echo -e "${YELLOW}⚠ AppArmor não está ativo${NC}"
    echo "Nenhuma correção necessária."
    exit 0
fi

echo -e "${GREEN}✓ AppArmor está ativo${NC}"

# Arquivo do perfil
PERFIL="/etc/apparmor.d/sistema.php-central"

# Verificar se o perfil existe
if [ ! -f "$PERFIL" ]; then
    echo -e "${RED}✗ Perfil $PERFIL não encontrado${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Perfil encontrado: $PERFIL${NC}"

# Verificar se a regra já existe
if grep -q "/opt/mk-auth/admin/arquivos/\*\* mrwlkix" "$PERFIL"; then
    echo -e "${YELLOW}⚠ Regra já existe no perfil${NC}"
    echo "Forçando recarga do perfil..."
else
    # Fazer backup
    DATA=$(date +%Y%m%d_%H%M%S)
    BACKUP="${PERFIL}.backup-${DATA}"
    
    echo "Criando backup: $BACKUP"
    cp "$PERFIL" "$BACKUP"
    echo -e "${GREEN}✓ Backup criado${NC}"
    
    # Adicionar regra
    echo "Adicionando regra ao perfil..."
    
    # Tentar adicionar após a linha do disco_virtual
    if grep -q "/opt/mk-auth/central/disco_virtual" "$PERFIL"; then
        sed -i '/\/opt\/mk-auth\/central\/disco_virtual\/\*\* rw,/a\        /opt/mk-auth/admin/arquivos/** mrwlkix,' "$PERFIL"
    else
        # Se não existir, adicionar após /opt/mk-auth/** r,
        sed -i '/\/opt\/mk-auth\/\*\* r,/a\        /opt/mk-auth/admin/arquivos/** mrwlkix,' "$PERFIL"
    fi
    
    echo -e "${GREEN}✓ Regra adicionada${NC}"
fi

# Recarregar perfil
echo ""
echo "Recarregando perfil AppArmor..."
echo "Método 1: apparmor_parser..."

if apparmor_parser -r "$PERFIL" 2>&1; then
    echo -e "${GREEN}✓ Perfil recarregado com apparmor_parser${NC}"
else
    echo -e "${YELLOW}⚠ Falha com apparmor_parser, tentando método alternativo...${NC}"
fi

# Método alternativo para Debian 25.08
echo "Método 2: systemctl reload..."
if systemctl reload apparmor 2>&1; then
    echo -e "${GREEN}✓ AppArmor recarregado via systemctl${NC}"
else
    echo -e "${YELLOW}⚠ Falha com reload, tentando restart...${NC}"
    if systemctl restart apparmor 2>&1; then
        echo -e "${GREEN}✓ AppArmor reiniciado via systemctl${NC}"
    else
        echo -e "${RED}✗ Falha ao reiniciar AppArmor${NC}"
        exit 1
    fi
fi

# Verificar se o perfil está ativo
echo ""
echo "Verificando status do perfil..."
if aa-status | grep -q "php-central"; then
    echo -e "${GREEN}✓ Perfil php-central está ativo${NC}"
else
    echo -e "${RED}✗ Perfil php-central NÃO está ativo${NC}"
    exit 1
fi

# Testar permissões
echo ""
echo "Testando permissões..."
TEST_FILE="/opt/mk-auth/admin/arquivos/.teste_permissao_$$.tmp"

if sudo -u www-data touch "$TEST_FILE" 2>/dev/null; then
    rm -f "$TEST_FILE"
    echo -e "${GREEN}✓ Teste de escrita: SUCESSO${NC}"
else
    echo -e "${RED}✗ Teste de escrita: FALHOU${NC}"
    echo ""
    echo "Possíveis causas:"
    echo "1. Permissões do diretório estão incorretas"
    echo "2. AppArmor precisa de reinicialização completa do PHP-FPM"
    echo ""
    echo "Tente executar:"
    echo "chmod -R 777 /opt/mk-auth/admin/arquivos"
    echo "systemctl restart php7.3-fpm"
    exit 1
fi

# Sucesso
echo ""
echo "======================================"
echo -e "${GREEN}✓ Correção aplicada com sucesso!${NC}"
echo "======================================"
echo ""
echo "Próximos passos:"
echo "1. Acesse o addon de contratos"
echo "2. A mensagem de erro não deve mais aparecer"
echo "3. Tente gerar um contrato de teste"
echo ""
echo "Se o erro persistir, execute:"
echo "systemctl restart php7.3-fpm"
echo ""
