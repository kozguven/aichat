<?php
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chat Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markup-templating.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
    <style>
        body {
            height: 100vh;
            overflow: hidden;
            background-color: #f8f9fa;
        }

        .chat-container {
            height: calc(100vh - 80px);
            display: flex;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px;
        }

        .sidebar {
            width: 300px;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
            border-radius: 10px 0 0 10px;
        }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: white;
            border-radius: 0 10px 10px 0;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            background-color: #f8f9fa;
        }

        .message {
            max-width: 80%;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .message-user {
            margin-left: auto;
            background-color: #007bff;
            color: white;
        }

        .message-ai {
            margin-right: auto;
            background-color: white;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .message pre {
            margin: 0.5rem 0;
            padding: 1rem;
            border-radius: 0.25rem;
            background-color: #2d2d2d !important;
            position: relative;
            max-height: 400px;
            overflow-y: auto;
        }

        .message pre code {
            color: #fff !important;
            font-family: 'Fira Code', monospace;
            font-size: 0.9rem;
            text-shadow: none;
        }

        .copy-button {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            padding: 0.25rem 0.5rem;
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.8rem;
            z-index: 1;
            transition: all 0.2s ease;
        }

        .copy-button:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
        }

        pre {
            position: relative;
            margin: 1rem 0;
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: #2d2d2d;
            overflow: hidden;
        }

        .chat-input {
            padding: 1rem;
            background-color: white;
            border-top: 1px solid #dee2e6;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .chat-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.2s;
        }

        .chat-item:hover {
            background-color: #e9ecef;
        }

        .chat-item.active {
            background-color: #007bff;
            color: white;
        }

        .typing-cursor {
            display: inline-block;
            width: 8px;
            height: 16px;
            background-color: #000;
            margin-left: 2px;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0; }
            100% { opacity: 1; }
        }

        .error {
            color: #dc3545;
            font-weight: bold;
            padding: 0.5rem;
            border: 1px solid #dc3545;
            border-radius: 0.25rem;
            background-color: #f8d7da;
        }

        /* Scrollbar stilleri */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <div class="container-fluid h-100">
        <div class="chat-container">
            <div class="sidebar p-3">
                <div class="d-flex flex-column">
                    <select id="modelSelect" class="form-select mb-3">
                        <option value="">Model seçiniz...</option>
                    </select>
                    <a href="settings.php" class="btn btn-outline-secondary btn-sm mb-3">API Ayarları</a>
                    <div class="d-flex justify-content-between mb-3">
                        <button class="btn btn-primary" onclick="newChat()">Yeni Sohbet</button>
                        <button class="btn btn-danger" onclick="deleteAllChats()">Tümünü Sil</button>
                    </div>
                </div>
                <div id="chatList"></div>
            </div>

            <!-- Chat Area -->
            <div class="chat-area">
                <div id="chatBox" class="chat-messages">
                    <!-- Messages will be populated here -->
                </div>
                <div class="chat-input">
                    <div class="input-group">
                        <input type="text" id="messageInput" class="form-control" placeholder="Mesajınızı yazın...">
                        <button class="btn btn-primary" id="sendButton" onclick="sendMessage()">Gönder</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const storage = {
            getAllChats: function() {
                const chats = localStorage.getItem('chats');
                // Sohbetleri alıp ters çevir
                return chats ? JSON.parse(chats).reverse() : [];
            },

            saveChat: function(chat) {
                const chats = this.getAllChats().reverse(); // Önce normal sıraya getir
                const index = chats.findIndex(c => c.id === chat.id);

                if (index !== -1) {
                    chats[index] = chat;
                } else {
                    // Yeni sohbeti dizinin başına ekle
                    chats.unshift(chat);
                }

                localStorage.setItem('chats', JSON.stringify(chats));
            },

            deleteChat: function(chatId) {
                const chats = this.getAllChats().filter(chat => chat.id !== chatId);
                localStorage.setItem('chats', JSON.stringify(chats));
            },

            deleteAllChats: function() {
                localStorage.removeItem('chats');
            }
        };

        let currentChat = null;
        let selectedModel = null;

        function loadChats() {
            const chats = storage.getAllChats();
            const chatList = $('#chatList');
            chatList.empty();

            chats.forEach(chat => {
                const chatItem = $('<div>')
                    .addClass('chat-item d-flex justify-content-between align-items-center')
                    .attr('data-id', chat.id);

                const title = $('<span>')
                    .addClass('chat-title cursor-pointer')
                    .text(chat.title || 'Yeni Sohbet')
                    .click(() => loadChat(chat.id));

                const deleteBtn = $('<button>')
                    .addClass('btn btn-sm btn-outline-danger')
                    .html('&times;')
                    .click((e) => {
                        e.stopPropagation();
                        deleteChat(chat.id);
                    });

                chatItem.append(title, deleteBtn);

                if (currentChat && chat.id === currentChat.id) {
                    chatItem.addClass('active');
                }

                chatList.append(chatItem);
            });
        }

        function loadChat(chatId) {
            const chats = storage.getAllChats();
            currentChat = chats.find(chat => chat.id === chatId);

            if (currentChat) {
                displayChat(currentChat);
                $('.chat-item').removeClass('active');
                $(`.chat-item[data-id="${chatId}"]`).addClass('active');

                // Model seçimini güncelle
                if (currentChat.model) {
                    $('#modelSelect').val(currentChat.model);
                    selectedModel = currentChat.model;
                }
            }
        }

        function displayChat(chat) {
            $('#chatBox').empty();
            chat.messages.forEach(message => {
                displayMessage(message);
            });
        }

        function newChat() {
            currentChat = {
                id: Date.now().toString(),
                title: 'Yeni Sohbet',
                messages: [],
                model: $('#modelSelect').val() // Seçili modeli kaydet
            };

            storage.saveChat(currentChat);
            loadChats();
            displayChat(currentChat);
            $('#messageInput').focus();
        }

        function deleteChat(chatId) {
            if (confirm('Bu sohbeti silmek istediğinizden emin misiniz?')) {
                storage.deleteChat(chatId);

                if (currentChat && currentChat.id === chatId) {
                    currentChat = null;
                    $('#chatBox').empty();
                }

                loadChats();
            }
        }

        function deleteAllChats() {
            if (confirm('Tüm sohbetleri silmek istediğinizden emin misiniz?')) {
                storage.deleteAllChats();
                currentChat = null;
                $('#chatBox').empty();
                loadChats();
            }
        }

        function copyCode(button) {
            const pre = $(button).closest('pre');
            const codeBlock = pre.find('code');

            // Orijinal kodu data attribute'undan al
            const originalCode = pre.attr('data-code');

            // Geçici textarea oluştur
            const textarea = document.createElement('textarea');
            textarea.value = originalCode;
            document.body.appendChild(textarea);
            textarea.select();

            try {
                // Kopyalama işlemi
                document.execCommand('copy');

                // Başarılı geri bildirim
                const originalText = $(button).text();
                $(button).text('Kopyalandı!');
                setTimeout(() => {
                    $(button).text(originalText);
                }, 2000);
            } catch (err) {
                console.error('Kopyalama hatası:', err);
                alert('Kopyalama başarısız oldu');
            } finally {
                // Geçici textarea'yı kaldır
                document.body.removeChild(textarea);
            }
        }

        function formatMessage(content) {
            // Markdown işaretlerini HTML'e dönüştür
            let formattedContent = content
                // Önce kod bloklarını işaretle
                .split(/(```[\s\S]*?```)/g)
                .map(part => {
                    if (part.startsWith('```')) {
                        const match = part.match(/```(\w+)?\n([\s\S]*?)```/);
                        if (match) {
                            const [, language = 'plaintext', code] = match;
                            const escapedCode = escapeHtml(code.trim());
                            return `<pre class="position-relative" data-code="${escapedCode}">
                                    <code class="language-${language}">${escapedCode}</code>
                                    <button class="copy-button" onclick="copyCode(this)">Kopyala</button>
                                   </pre>`;
                        }
                        return part;
                    }

                    // Satır içi kod bloklarını işle
                    return part
                        .replace(/`([^`]+)`/g, '<code>$1</code>')
                        // Satır sonlarını <br> ile değiştir
                        .split('\n')
                        .map(line => line.trim())
                        .filter(line => line)
                        .join('<br>');
                })
                .join('');

            return formattedContent;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function sendMessage() {
            const message = $('#messageInput').val().trim();
            if (!message) return;

            const selectedModel = $('#modelSelect').val();
            if (!selectedModel) {
                alert('Lütfen bir model seçin');
                return;
            }

            if (!currentChat) {
                newChat();
            }

            const userMessage = {
                role: 'user',
                content: message,
                timestamp: Date.now()
            };

            currentChat.messages.push(userMessage);
            displayMessage(userMessage);

            $('#messageInput').val('');
            $('#sendButton').prop('disabled', true);

            // AI yanıtı için boş bir mesaj div'i oluştur
            const aiMessageDiv = $('<div>')
                .addClass('message message-ai')
                .appendTo('#chatBox');

            // Yanıp sönen cursor efekti
            const cursorDiv = $('<span>')
                .addClass('typing-cursor')
                .text('▋')
                .appendTo(aiMessageDiv);

            let currentResponse = '';

            const formData = new FormData();
            formData.append('message', message);
            formData.append('model', selectedModel);
            formData.append('history', JSON.stringify(currentChat.messages));

            fetch('api.php', {
                method: 'POST',
                body: formData
            }).then(response => {
                const eventSource = new EventSource('api.php');

                eventSource.onmessage = function(event) {
                    if (event.data === '[DONE]') {
                        eventSource.close();
                        $('#sendButton').prop('disabled', false);
                        cursorDiv.remove();

                        // Yanıtı sohbet geçmişine ekle
                        const aiMessage = {
                            role: 'assistant',
                            content: currentResponse,
                            timestamp: Date.now()
                        };

                        currentChat.messages.push(aiMessage);

                        // İlk mesajsa, sohbet başlığını güncelle
                        if (currentChat.messages.length === 2) {
                            currentChat.title = message.substring(0, 30) + (message.length > 30 ? '...' : '');
                        }

                        storage.saveChat(currentChat);
                        loadChats();

                        return;
                    }

                    const data = JSON.parse(event.data);
                    if (data.error) {
                        aiMessageDiv.html(`<div class="error">Hata: ${data.error}</div>`);
                        eventSource.close();
                        $('#sendButton').prop('disabled', false);
                        return;
                    }

                    if (data.content) {
                        currentResponse += data.content;
                        aiMessageDiv.html(formatMessage(currentResponse));
                        requestAnimationFrame(() => {
                            Prism.highlightAllUnder(aiMessageDiv[0]);
                        });
                        cursorDiv.appendTo(aiMessageDiv);
                        $('#chatBox').scrollTop($('#chatBox')[0].scrollHeight);
                    }
                };

                eventSource.onerror = function() {
                    eventSource.close();
                    $('#sendButton').prop('disabled', false);
                    if (!currentResponse) {
                        aiMessageDiv.html('<div class="error">Bağlantı hatası oluştu</div>');
                    }
                };
            }).catch(error => {
                aiMessageDiv.html('<div class="error">İstek gönderilirken hata oluştu</div>');
                $('#sendButton').prop('disabled', false);
            });
        }

        function displayMessage(message) {
            const messageDiv = $('<div>')
                .addClass('message')
                .addClass(message.role === 'user' ? 'message-user' : 'message-ai');

            // Mesaj içeriğini formatla
            const formattedContent = formatMessage(message.content);
            messageDiv.html(formattedContent);

            // Mesajı sohbet kutusuna ekle
            $('#chatBox').append(messageDiv);

            // DOM güncellemesini bekle ve sonra renklendirme yap
            requestAnimationFrame(() => {
                Prism.highlightAllUnder(messageDiv[0]);
            });

            // Sohbet kutusunu en alta kaydır
            $('#chatBox').scrollTop($('#chatBox')[0].scrollHeight);
        }

        // Model listesini yükle
        fetch('get_models.php')
            .then(response => response.json())
            .then(models => {
                const select = $('#modelSelect');
                models.forEach(model => {
                    select.append(new Option(model.name, model.id));
                });
            })
            .catch(error => {
                console.error('Model listesi yüklenirken hata:', error);
            });

        // Model seçimi değiştiğinde
        $('#modelSelect').change(function() {
            selectedModel = $(this).val();
            if (currentChat) {
                currentChat.model = selectedModel;
                storage.saveChat(currentChat);
            }
        });

        // Event Listeners
        $(document).ready(function() {
            loadChats();

            $('#messageInput').keypress(function(e) {
                if (e.which == 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        });
    </script>
</body>
</html>
