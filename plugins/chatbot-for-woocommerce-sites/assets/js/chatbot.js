document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('wc-chatbot-container');
    const toggleBtn = document.getElementById('wc-chatbot-toggle');
    const closeBtn = document.getElementById('wc-chatbot-close');
    const messagesDiv = document.getElementById('wc-chatbot-messages');
    const inputField = document.getElementById('wc-chatbot-input');
    const sendBtn = document.getElementById('wc-chatbot-send');

    if (!container || !wcChatbotData) return;

    let chatContext = {};
    let isInitialized = false;

    // Toggle chatbot visibility
    function toggleChat() {
        container.classList.toggle('wc-chatbot-hidden');
        if (!container.classList.contains('wc-chatbot-hidden') && !isInitialized) {
            appendMessage(wcChatbotData.greeting, 'bot');
            isInitialized = true;
        }
        if (!container.classList.contains('wc-chatbot-hidden')) {
            inputField.focus();
        }
    }

    toggleBtn.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);

    // Parse markdown-like links to HTML
    function parseText(text) {
        // Handle markdown links: [text](url)
        let html = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');
        // Handle line breaks
        html = html.replace(/\n/g, '<br>');
        return html;
    }

    // Append message to UI
    function appendMessage(text, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'chatbot-message ' + sender;
        msgDiv.innerHTML = parseText(text);
        messagesDiv.appendChild(msgDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function showLoading() {
        const loading = document.createElement('div');
        loading.className = 'chatbot-loading';
        loading.id = 'wc-chatbot-loading';
        loading.innerHTML = '<span></span><span></span><span></span>';
        messagesDiv.appendChild(loading);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function hideLoading() {
        const loading = document.getElementById('wc-chatbot-loading');
        if (loading) loading.remove();
    }

    // Send message to API
    async function sendMessage() {
        const message = inputField.value.trim();
        if (!message) return;

        inputField.value = '';
        appendMessage(message, 'user');
        showLoading();

        try {
            const response = await fetch(wcChatbotData.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wcChatbotData.nonce
                },
                body: JSON.stringify({
                    message: message,
                    context: chatContext
                })
            });

            const data = await response.json();
            hideLoading();

            if (response.ok) {
                appendMessage(data.text, 'bot');
                chatContext = data.context || {}; // Update context state
            } else {
                appendMessage("Sorry, I encountered an error. Please try again.", 'bot');
            }
        } catch (error) {
            hideLoading();
            appendMessage("Sorry, I couldn't connect to the server.", 'bot');
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    inputField.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMessage();
    });
});
