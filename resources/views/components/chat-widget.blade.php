<div id="chatWidget" class="chat-widget">
    {{-- Chat Toggle Button --}}
    <button class="chat-toggle-btn" id="chatToggle">
        <i class="fas fa-comments"></i>
        <span class="badge bg-danger" id="unreadBadge">0</span>
    </button>

    {{-- Chat Container --}}
    <div class="chat-container" id="chatContainer">
        {{-- Chat Header --}}
        <div class="chat-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-comments me-2"></i>Live Chat
                    </h6>
                    <small class="text-muted" id="chatStatus">Online</small>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" id="chatClose">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Chat Body --}}
        <div class="chat-body" id="chatBody">
            {{-- Messages Container --}}
            <div class="messages-container" id="messagesContainer">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Loading chat...</p>
                </div>
            </div>

            {{-- Message Input --}}
            <div class="message-input">
                <div class="input-group">
                    <input type="text" class="form-control"
                           placeholder="Type your message..."
                           id="messageInput"
                           autocomplete="off">
                    <button class="btn btn-primary" id="sendMessage">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
}

.chat-toggle-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4a6cf7 0%, #3a56d7 100%);
    border: none;
    color: white;
    font-size: 1.5rem;
    box-shadow: 0 5px 20px rgba(74, 108, 247, 0.3);
    position: relative;
    transition: all 0.3s ease;
}

.chat-toggle-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 8px 25px rgba(74, 108, 247, 0.4);
}

.chat-toggle-btn .badge {
    position: absolute;
    top: -5px;
    right: -5px;
    font-size: 0.7rem;
    padding: 4px 7px;
}

.chat-container {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 350px;
    height: 500px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    display: none;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.chat-container.open {
    display: flex;
    flex-direction: column;
}

.chat-header {
    background: linear-gradient(135deg, #4a6cf7 0%, #3a56d7 100%);
    color: white;
    padding: 15px;
    border-radius: 15px 15px 0 0;
}

.chat-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.messages-container {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    background: #f8f9fa;
}

.message-input {
    padding: 15px;
    border-top: 1px solid #f1f1f1;
    background: white;
}

.message {
    margin-bottom: 10px;
    padding: 10px 15px;
    border-radius: 10px;
    max-width: 80%;
    word-wrap: break-word;
}

.message.sent {
    background: #4a6cf7;
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 3px;
}

.message.received {
    background: white;
    color: #333;
    margin-right: auto;
    border: 1px solid #e9ecef;
    border-bottom-left-radius: 3px;
}

.message-time {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.8);
    margin-top: 5px;
    text-align: right;
}

.message.received .message-time {
    color: #6c757d;
}

.input-group .form-control {
    border-radius: 20px;
    padding: 10px 15px;
    border: 1px solid #e9ecef;
}

.input-group .btn {
    border-radius: 20px;
    padding: 10px 20px;
}

/* Scrollbar styling */
.messages-container::-webkit-scrollbar {
    width: 6px;
}

.messages-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const chatToggle = document.getElementById('chatToggle');
    const chatContainer = document.getElementById('chatContainer');
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessage');
    const messagesContainer = document.getElementById('messagesContainer');
    const unreadBadge = document.getElementById('unreadBadge');
    const chatClose = document.getElementById('chatClose');

    // State
    let isChatOpen = false;
    let pusher = null;
    let channel = null;

    // Initialize
    initChat();

    function initChat() {
        // Setup event listeners
        chatToggle.addEventListener('click', toggleChat);
        chatClose.addEventListener('click', closeChat);
        sendMessageBtn.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Initialize Pusher
        initPusher();

        // Load initial messages
        loadMessages();
    }

    function initPusher() {
        // Initialize Pusher with CDN
        pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
            cluster: '{{ env("PUSHER_APP_CLUSTER", "mt1") }}',
            encrypted: true
        });

        // Subscribe to channel (simplified for demo)
        channel = pusher.subscribe('public-chat');

        // Listen for new messages
        channel.bind('new-message', function(data) {
            addMessage(data, false);
            updateUnreadCount();
        });
    }

    function toggleChat() {
        isChatOpen = !isChatOpen;
        if (isChatOpen) {
            chatContainer.classList.add('open');
            messageInput.focus();
            // Mark all as read when opening chat
            unreadBadge.style.display = 'none';
        } else {
            chatContainer.classList.remove('open');
        }
    }

    function closeChat() {
        isChatOpen = false;
        chatContainer.classList.remove('open');
    }

    function loadMessages() {
        // Simple AJAX to load messages
        fetch('{{ route("chat.messages") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messagesContainer.innerHTML = '';
                    data.messages.forEach(msg => {
                        addMessage(msg, msg.sender_id === {{ auth()->id() }});
                    });
                }
            })
            .catch(error => {
                console.error('Error loading messages:', error);
            });
    }

    function addMessage(data, isSent) {
        const messageEl = document.createElement('div');
        messageEl.className = `message ${isSent ? 'sent' : 'received'}`;

        const time = new Date().toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });

        messageEl.innerHTML = `
            <div>${data.message}</div>
            <div class="message-time">${time}</div>
        `;

        messagesContainer.appendChild(messageEl);

        // Scroll to bottom
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function sendMessage() {
        const message = messageInput.value.trim();
        if (!message) return;

        // Add to UI immediately
        addMessage({ message: message }, true);

        // Clear input
        messageInput.value = '';

        // Send to server
        fetch('{{ route("chat.send") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                message: message,
                receiver_id: 1 // Admin ID
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to send message');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function updateUnreadCount() {
        if (!isChatOpen) {
            const currentCount = parseInt(unreadBadge.textContent) || 0;
            const newCount = currentCount + 1;
            unreadBadge.textContent = newCount;
            unreadBadge.style.display = 'inline-block';
        }
    }
});
</script>
