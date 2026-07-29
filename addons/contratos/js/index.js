// Função para confirmar exclusão
function confirmDelete(filePath) {
    console.log(filePath);
    if (confirm("Deseja realmente excluir este contrato?")) {
        window.location.href = 'excluir_arquivo.php?file=' + encodeURIComponent(filePath);
    }
}

// Mapeamento de cores para filtros
const coresStatus = {
    'verde': 'mediumseagreen',
    'amarelo': 'gold',
    'laranja': 'darkorange',
    'vermelho': 'indianred'
};

// Função para renderizar a tabela com os contratos filtrados
function renderizarTabela(contratos) {
    const tbody = document.querySelector('#clientTable tbody');
    
    if (contratos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="14" style="text-align: center; padding: 20px;">Nenhum contrato encontrado</td></tr>';
        return;
    }
    
    tbody.innerHTML = contratos.map(resultado => {
        // Usar data já formatada do PHP
        const dataCriacao = resultado.data_criacao_formatada || 'N/A';
        const dataExpiracao = resultado.data_expiracao ? 
            new Date(resultado.data_expiracao.date).toLocaleDateString('pt-BR') : 'N/A';
        
        // Calcular tempo restante
        let tempoRestante = '';
        if (resultado.meses_restantes !== undefined && resultado.dias_restantes !== undefined) {
            const meses = Math.max(0, resultado.meses_restantes);
            const dias = Math.max(0, resultado.dias_restantes);
            
            if (meses > 0) {
                const mesesTexto = (meses == 1) ? "mês" : "meses";
                const diasTexto = (dias == 1) ? "dia" : "dias";
                tempoRestante = `${meses} ${mesesTexto} e ${dias} ${diasTexto}`;
            } else {
                const diasTexto = (dias == 1) ? "dia" : "dias";
                tempoRestante = `${dias} ${diasTexto}`;
            }
        } else {
            tempoRestante = 'N/A';
        }
        
        // Construir URLs
        const protocol = window.location.protocol;
        const host = window.location.host;
        const caminhoArquivo = resultado.caminho_arquivo.replace('/opt/mk-auth', '');
        const pdfURL = `${protocol}//${host}/${caminhoArquivo.replace(/^\//, '')}`;
        
        return `
            <tr>
                <td style="text-align: left;">${escapeHtml(resultado.nome_cliente)}</td>
                <td>
                    <span class="status-dot" style="background-color: ${resultado.status_color};" title="${resultado.status_fidelidade}"></span>
                    <span class="status-text">${resultado.status_fidelidade}</span>
                </td>
                <td>${dataCriacao}</td>
                <td>${dataExpiracao}</td>
                <td>${tempoRestante}</td>
                <td>
                    <a href="${pdfURL}" target="_blank" title="Baixar o contrato em PDF">
                        <img src="images/pdf.png" alt="PDF" width="23" height="23">
                    </a>
                </td>
                <td>
                    <i class="bi-trash3-fill" style="font-size: 18px; color: #ff3860 !important; cursor: pointer;"
                       onclick="confirmDelete('/opt/mk-auth/${escapeHtml(resultado.caminho_arquivo)}')"
                       title="Excluir o contrato atual"></i>
                </td>
            </tr>
        `;
    }).join('');
}

// Função para filtrar contratos globalmente (todas as páginas)
function filterTable() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase().trim();
    const filterSelect = document.getElementById('filterSelect');
    const filterValue = filterSelect ? filterSelect.value : 'todos';
    const paginationContainer = document.querySelector('.pagination-container');
    
    // Se não há busca ativa e filtro está em "todos", recarregar página para exibir paginação normal
    if (searchInput === '' && filterValue === 'todos') {
        if (paginationContainer) {
            paginationContainer.style.display = 'flex';
        }
        // Recarregar página para restaurar estado original com paginação
        window.location.href = 'index.php';
        return;
    }
    
    // Verificar se window.todosContratos está disponível
    if (!window.todosContratos || window.todosContratos.length === 0) {
        console.warn('⚠️ Array global de contratos não está disponível');
        return;
    }
    
    // Filtrar contratos
    const contratosFiltrados = window.todosContratos.filter(contrato => {
        const nomeCliente = (contrato.nome_cliente || '').toLowerCase();
        const statusColor = contrato.status_color || '';
        
        const matchesSearch = searchInput === '' || nomeCliente.includes(searchInput);
        const matchesFilter = filterValue === 'todos' || statusColor === coresStatus[filterValue];
        
        return matchesSearch && matchesFilter;
    });
    
    console.log(`🔍 Filtrados ${contratosFiltrados.length} de ${window.todosContratos.length} contratos`);
    
    // Renderizar resultados filtrados
    renderizarTabela(contratosFiltrados);
    
    // Esconder paginação durante busca/filtro ativo
    if (paginationContainer) {
        paginationContainer.style.display = 'none';
    }
}

// Função auxiliar para escapar HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Função para fazer backup de todos os contratos
function fazerBackupContratos() {
    // Abrir página de download na mesma aba
    window.location.href = 'backup_contratos.php';
}
