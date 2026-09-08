function confirmDelete(filePath) {
    if (confirm('Deseja realmente excluir este contrato?')) {
        window.location.href = 'excluir_arquivo.php?file=' + encodeURIComponent(filePath);
    }
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/`/g, '&#96;');
}

function abrirRenovacao(uuid, login, nome) {
    const params = new URLSearchParams({uuid: uuid, login: login, nome: nome});
    const width = Math.min(650, window.screen.availWidth - 30);
    const height = Math.min(720, window.screen.availHeight - 60);
    const left = Math.max(0, Math.round((window.screen.availWidth - width) / 2));
    const top = Math.max(0, Math.round((window.screen.availHeight - height) / 2));
    const popup = window.open('renovacao.php?' + params.toString(), 'renovacaoContrato', `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`);
    if (!popup) {
        window.location.href = 'renovacao.php?' + params.toString();
    }
}

function renderizarTabela(contratos) {
    const tbody = document.querySelector('#clientTable tbody');
    if (!tbody) return;
    if (!contratos.length) {
        tbody.innerHTML = '<tr><td colspan="16" class="empty-contracts">Nenhum contrato encontrado neste filtro.</td></tr>';
        return;
    }

    tbody.innerHTML = contratos.map((item) => {
        const pdfURL = item.caminho_arquivo || '#';
        const uuid = escapeAttribute(item.uuid_cliente);
        const login = escapeAttribute(item.login);
        const nome = escapeAttribute(item.nome_cliente);
        return `<tr data-status="${escapeAttribute(item.status_key)}">
            <td style="text-align:left">${escapeHtml(item.nome_cliente)}</td>
            <td><span class="status-dot" style="background-color:${escapeAttribute(item.status_color)}"></span><span class="status-text">${escapeHtml(item.status_label)}</span></td>
            <td>${escapeHtml(item.data_criacao_formatada)}</td>
            <td>${escapeHtml(item.data_expiracao_formatada)}</td>
            <td>${escapeHtml(item.tempo_restante)}</td>
            <td><a href="${escapeAttribute(pdfURL)}" target="_blank" rel="noopener" title="Abrir contrato"><img src="images/pdf.png" alt="PDF" width="23" height="23"></a></td>
            <td><button type="button" class="renew-btn" data-uuid="${uuid}" data-login="${login}" data-nome="${nome}" title="Renovar a vigência"><i class="bi-pencil-square"></i><span>Renovar</span></button></td>
            <td><i class="bi-trash3-fill" style="font-size:18px;color:#ff3860!important;cursor:pointer" data-delete-path="/opt/mk-auth${escapeAttribute(pdfURL)}" title="Excluir o contrato atual"></i></td>
        </tr>`;
    }).join('');
}

document.addEventListener('click', function (event) {
    const renew = event.target.closest('.renew-btn');
    if (renew && renew.dataset.uuid) {
        abrirRenovacao(renew.dataset.uuid, renew.dataset.login || '', renew.dataset.nome || '');
        return;
    }
    const remove = event.target.closest('[data-delete-path]');
    if (remove) confirmDelete(remove.dataset.deletePath);
});

function atualizarCardAtivo(status) {
    document.querySelectorAll('.contract-card').forEach((card) => {
        card.classList.toggle('active', card.dataset.status === status);
    });
}

function filtrarPorCard(status, card) {
    const select = document.getElementById('filterSelect');
    if (select) select.value = status;
    atualizarCardAtivo(status);
    filterTable(false);
    if (card) card.blur();
}

function filterTable(allowReset) {
    const searchElement = document.getElementById('searchInput');
    const select = document.getElementById('filterSelect');
    const search = searchElement ? searchElement.value.toLocaleLowerCase('pt-BR').trim() : '';
    const status = select ? select.value : 'todos';
    const pagination = document.querySelector('.pagination-container');

    if (allowReset !== false && search === '' && status === 'todos') {
        window.location.href = 'index.php';
        return;
    }

    const all = Array.isArray(window.todosContratos) ? window.todosContratos : [];
    const filtered = all.filter((item) => {
        const haystack = `${item.nome_cliente || ''} ${item.login || ''}`.toLocaleLowerCase('pt-BR');
        return (search === '' || haystack.includes(search)) && (status === 'todos' || item.status_key === status);
    });
    renderizarTabela(filtered);
    atualizarCardAtivo(status);
    if (pagination) pagination.style.display = search === '' && status === 'todos' ? '' : 'none';
}

function fazerBackupContratos() {
    window.location.href = 'backup_contratos.php';
}

function selecionarAssinaturaProvedor() {
    const input = document.getElementById('signatureFileInput');
    if (input) input.click();
}

function enviarAssinaturaProvedor(input) {
    if (!input || !input.files || !input.files.length) return;
    const file = input.files[0];
    const allowedTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        alert('Formato não permitido. Use PNG, JPG, WEBP ou GIF.'); input.value = ''; return;
    }
    if (file.size <= 0 || file.size > 5 * 1024 * 1024) {
        alert('A imagem deve ter no máximo 5 MB.'); input.value = ''; return;
    }
    if (!confirm(`Usar "${file.name}" como assinatura do provedor? A imagem será convertida para fundo branco e traços pretos.`)) {
        input.value = ''; return;
    }
    const form = document.getElementById('signatureUploadForm');
    const button = document.getElementById('signatureUploadButton');
    if (!form || !button) return;
    button.classList.add('loading'); button.disabled = true;
    const label = button.querySelector('span:last-child');
    if (label) label.textContent = 'Enviando...';
    form.submit();
}
