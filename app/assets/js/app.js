/**
 * EficienSys - JavaScript Principal
 */

document.addEventListener('DOMContentLoaded', function() {
    // Menu Toggle (Mobile)
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });

        // Fechar sidebar ao clicar fora
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Notification Dropdown
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');

    if (notificationBtn && notificationDropdown) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
            loadNotifications();
        });

        document.addEventListener('click', function(e) {
            if (!notificationDropdown.contains(e.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
    }

    // Carregar notificacoes
    function loadNotifications() {
        fetch('api/notifications.php?action=get')
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('notificationList');
                const dot = document.getElementById('notifDot');

                if (data.notifications && data.notifications.length > 0) {
                    list.innerHTML = data.notifications.map(n => `
                        <div class="notification-item ${n.lida ? '' : 'unread'}" data-id="${n.id}">
                            <div>
                                <strong>${n.titulo}</strong>
                                <p>${n.mensagem}</p>
                                <small>${n.criado_em}</small>
                            </div>
                        </div>
                    `).join('');

                    const unreadCount = data.notifications.filter(n => !n.lida).length;
                    dot.style.display = unreadCount > 0 ? 'block' : 'none';
                } else {
                    list.innerHTML = '<p class="empty-state">Nenhuma notificacao</p>';
                    dot.style.display = 'none';
                }
            })
            .catch(err => console.log('Erro ao carregar notificacoes'));
    }

    // Marcar todas como lidas
    const markAllRead = document.getElementById('markAllRead');
    if (markAllRead) {
        markAllRead.addEventListener('click', function(e) {
            e.preventDefault();
            fetch('api/notifications.php?action=read_all', { method: 'POST' })
                .then(() => loadNotifications());
        });
    }

    // Verificar novas mensagens periodicamente
    setInterval(checkNewMessages, 10000);

    function checkNewMessages() {
        fetch('api/messages.php?action=unread_count')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('msg-badge');
                if (badge && data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'block';
                } else if (badge) {
                    badge.style.display = 'none';
                }
            })
            .catch(err => {});
    }

    // Carregar inicialmente
    if (typeof loadNotifications === 'function') {
        loadNotifications();
    }
    checkNewMessages();
});

// Funcao para formatar data
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;

    if (diff < 60000) return 'Agora';
    if (diff < 3600000) return Math.floor(diff / 60000) + ' min atras';
    if (diff < 86400000) return Math.floor(diff / 3600000) + 'h atras';

    return date.toLocaleDateString('pt-BR');
}

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 14px 24px;
        background: ${type === 'error' ? '#ef4444' : '#10b981'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Confirmar acao
function confirmAction(message) {
    return confirm(message);
}
