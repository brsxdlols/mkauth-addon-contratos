function copiarComando() {
    const comandoTexto = document.getElementById('comando-texto').textContent;
    const btn = document.getElementById('btn-copiar');
    
    // Copiar para clipboard
    navigator.clipboard.writeText(comandoTexto).then(function() {
        // Mudar visual do botão
        const iconElement = btn.querySelector('i');
        const textElement = btn.querySelector('span');
        
        iconElement.className = 'bi-check-circle-fill';
        textElement.textContent = 'Copiado!';
        btn.classList.add('copiado');
        
        // Voltar ao normal após 2 segundos
        setTimeout(function() {
            iconElement.className = 'bi-clipboard';
            textElement.textContent = 'Copiar';
            btn.classList.remove('copiado');
        }, 2000);
    }).catch(function(err) {
        console.error('Erro ao copiar:', err);
        alert('Erro ao copiar comando. Por favor, copie manualmente.');
    });
}

function recarregarPagina() {
    const btn = document.getElementById('btn-recarregar');
    const iconElement = btn.querySelector('i');
    const textElement = btn.querySelector('span');
    
    // Mostrar loading
    iconElement.className = 'loading';
    textElement.textContent = 'Verificando...';
    btn.disabled = true;
    
    // Recarregar após 1 segundo
    setTimeout(function() {
        window.location.reload();
    }, 1000);
}
