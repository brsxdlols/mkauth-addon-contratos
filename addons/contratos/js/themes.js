/**
 * Sistema de Temas - Addon Contratos
 * Gerencia a seleção e persistência de temas
 */

// Carregar tema salvo ao iniciar
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('contratoTheme') || 'default';
    applyTheme(savedTheme);
    updateActiveTheme(savedTheme);
});

// Toggle do menu de temas
function toggleThemeMenu() {
    const menu = document.getElementById('themeMenu');
    menu.classList.toggle('active');
    
    // Fechar menu ao clicar fora
    document.addEventListener('click', function closeMenu(e) {
        if (!e.target.closest('.theme-selector-container')) {
            menu.classList.remove('active');
            document.removeEventListener('click', closeMenu);
        }
    });
}

// Mudar tema
function changeTheme(theme) {
    applyTheme(theme);
    localStorage.setItem('contratoTheme', theme);
    updateActiveTheme(theme);
    
    // Fechar menu
    document.getElementById('themeMenu').classList.remove('active');
    
    // Mostrar notificação
    showThemeNotification(`Tema "${getThemeName(theme)}" aplicado!`);
}

// Aplicar tema
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
}

// Atualizar opção ativa visualmente
function updateActiveTheme(theme) {
    document.querySelectorAll('.theme-option').forEach(option => {
        option.classList.remove('active');
        if (option.getAttribute('data-theme') === theme) {
            option.classList.add('active');
        }
    });
}

// Obter nome do tema
function getThemeName(theme) {
    const names = {
        'default': 'Padrão',
        'dark': 'Escuro',
        'ocean': 'Oceano',
        'sunset': 'Pôr do Sol',
        'nature': 'Natureza'
    };
    return names[theme] || theme;
}

// Notificação de tema alterado
function showThemeNotification(message) {
    // Remover notificação anterior se existir
    const existing = document.querySelector('.theme-notification');
    if (existing) {
        existing.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = 'theme-notification';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: var(--primary-color);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px var(--shadow-color);
        z-index: 10000;
        animation: slideIn 0.3s ease;
        font-size: 14px;
        font-weight: 600;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 2000);
}

// Adicionar animações CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
`;
document.head.appendChild(style);
