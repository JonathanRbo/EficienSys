/**
 * EficienSys - Chat JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chatForm');
    const chatMessages = document.getElementById('chatMessages');
    const searchChat = document.getElementById('searchChat');

    // Scroll para ultima mensagem
    function scrollToBottom() {
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    scrollToBottom();

    // Enviar mensagem
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(chatForm);
            const mensagem = formData.get('mensagem');

            if (!mensagem.trim()) return;

            fetch('api/messages.php?action=send', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Adicionar mensagem na tela
                    addMessage(data.message, true);
                    chatForm.reset();
                    scrollToBottom();
                } else {
                    showToast(data.error || 'Erro ao enviar mensagem', 'error');
                }
            })
            .catch(err => {
                showToast('Erro de conexao', 'error');
            });
        });
    }

    // Adicionar mensagem na tela
    function addMessage(msg, isSent = false) {
        const emptyState = chatMessages.querySelector('.empty-state');
        if (emptyState) emptyState.remove();

        const messageDiv = document.createElement('div');
        messageDiv.className = 'message' + (isSent ? ' sent' : '');
        messageDiv.innerHTML = `
            <div class="message-avatar">
                ${msg.remetente_nome ? msg.remetente_nome.charAt(0).toUpperCase() : 'U'}
            </div>
            <div>
                <div class="message-content">
                    ${msg.conteudo.replace(/\n/g, '<br>')}
                </div>
                <div class="message-time">
                    ${new Date().toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'})}
                </div>
            </div>
        `;
        chatMessages.appendChild(messageDiv);
    }

    // Buscar novas mensagens (polling)
    if (chatMessages) {
        const userId = chatMessages.dataset.user;
        const grupoId = chatMessages.dataset.grupo;
        let lastMessageId = 0;

        // Pegar ID da ultima mensagem
        const messages = chatMessages.querySelectorAll('.message');
        if (messages.length > 0) {
            lastMessageId = messages.length;
        }

        setInterval(function() {
            const params = new URLSearchParams({
                action: 'get_new',
                user: userId || '',
                grupo: grupoId || '',
                last: lastMessageId
            });

            fetch('api/messages.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            // Verificar se ja nao foi adicionada
                            addMessage(msg, msg.is_mine);
                            lastMessageId++;
                        });
                        scrollToBottom();
                    }
                })
                .catch(err => {});
        }, 3000);
    }

    // Busca de conversas
    if (searchChat) {
        searchChat.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const items = document.querySelectorAll('.chat-item');

            items.forEach(item => {
                const name = item.querySelector('.chat-name').textContent.toLowerCase();
                item.style.display = name.includes(query) ? 'flex' : 'none';
            });
        });
    }
});
