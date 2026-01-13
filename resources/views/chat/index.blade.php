@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Live Chat</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- User List -->
                        <div class="col-md-4 border-end">
                            <h5>Users</h5>
                            <div class="list-group" id="userList">
                                @foreach($users as $user)
                                <a href="#" class="list-group-item list-group-item-action user-item"
                                   data-user-id="{{ $user->id }}"
                                   data-user-name="{{ $user->name }}">
                                    {{ $user->name }} ({{ $user->email }})
                                </a>
                                @endforeach
                            </div>
                        </div>

                        <!-- Chat Area -->
                        <div class="col-md-8">
                            <div id="chatArea" style="display: none;">
                                <h5>Chat with: <span id="chatWith"></span></h5>
                                <div id="messages" style="height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;">
                                    <!-- Messages will appear here -->
                                </div>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="messageInput" placeholder="Type your message...">
                                    <button class="btn btn-primary" id="sendButton">Send</button>
                                </div>
                            </div>
                            <div id="noChatSelected" style="text-align: center; padding: 50px;">
                                <p>Select a user to start chatting</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
console.log('Chat script starting...');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded and parsed');

    let currentChatUserId = null;
    let currentChatUserName = null;

    // Debug: Cek elemen
    console.log('Elements:', {
        userItems: document.querySelectorAll('.user-item').length,
        chatArea: document.getElementById('chatArea'),
        messages: document.getElementById('messages'),
        noChatSelected: document.getElementById('noChatSelected')
    });

    // Handle user click dengan try-catch
    document.querySelectorAll('.user-item').forEach(item => {
        item.addEventListener('click', function(e) {
            try {
                e.preventDefault();
                console.log('User clicked:', this.dataset.userId);

                const userId = this.dataset.userId;
                const userName = this.dataset.userName;

                // Update UI
                document.querySelectorAll('.user-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');

                // Show chat area
                document.getElementById('chatArea').style.display = 'block';
                document.getElementById('noChatSelected').style.display = 'none';
                document.getElementById('chatWith').textContent = userName;

                // Set current user
                currentChatUserId = userId;
                currentChatUserName = userName;

                // Load messages
                loadMessages(userId);

                // Focus on message input
                document.getElementById('messageInput').focus();
            } catch (error) {
                console.error('Error in user click handler:', error);
                alert('Error: ' + error.message);
            }
        });
    });

    // Load messages function - FIXED
    function loadMessages(userId) {
        console.log('Loading messages for user:', userId);

        // Show loading state
        const messagesDiv = document.getElementById('messages');
        messagesDiv.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Loading messages...</p></div>';

        // Gunakan URL langsung
        fetch(`/chat/messages/${userId}`)
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Messages data:', data);
                if (data.success) {
                    displayMessages(data.messages, data.current_user_id);
                } else {
                    console.error('Error:', data.message);
                    messagesDiv.innerHTML = '<div class="text-center text-danger">Error loading messages</div>';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                messagesDiv.innerHTML = '<div class="text-center text-danger">Failed to load messages</div>';
            });
    }

    // Send message function dengan try-catch
    document.getElementById('sendButton').addEventListener('click', function() {
        try {
            sendMessage();
        } catch (error) {
            console.error('Send button error:', error);
        }
    });

    document.getElementById('messageInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            try {
                sendMessage();
            } catch (error) {
                console.error('Enter key error:', error);
            }
        }
    });

    function sendMessage() {
        const message = document.getElementById('messageInput').value.trim();
        console.log('Sending message:', message, 'to:', currentChatUserId);

        if (!message || !currentChatUserId) {
            console.warn('No message or user selected');
            return;
        }

        fetch('/chat/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                receiver_id: currentChatUserId,
                message: message
            })
        })
        .then(response => {
            console.log('Send response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Send response data:', data);
            if (data.success) {
                // Add message to UI
                addMessageToUI(data.message, true);
                document.getElementById('messageInput').value = '';

                // Scroll to bottom
                const messagesDiv = document.getElementById('messages');
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            } else {
                console.error('Send failed:', data.message);
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
        });
    }

    function displayMessages(messages, currentUserId) {
        console.log('Displaying messages:', messages.length, 'messages');
        const messagesDiv = document.getElementById('messages');

        if (!messages || messages.length === 0) {
            messagesDiv.innerHTML = '<div class="text-center py-4 text-muted">No messages yet. Start the conversation!</div>';
            return;
        }

        messagesDiv.innerHTML = '';
        messages.forEach(message => {
            addMessageToUI(message, message.sender_id === currentUserId);
        });

        // Scroll to bottom
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function addMessageToUI(message, isSent) {
        const messagesDiv = document.getElementById('messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isSent ? 'sent' : 'received'}`;
        messageDiv.style.cssText = 'margin-bottom: 10px; padding: 10px; border-radius: 5px; background-color: ' + (isSent ? '#d1ecf1' : '#f8d7da');

        const time = new Date(message.created_at).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });

        messageDiv.innerHTML = `
            <div>${message.message}</div>
            <small class="text-muted" style="font-size: 0.8em;">${time}</small>
        `;

        messagesDiv.appendChild(messageDiv);
    }

    console.log('Chat script initialization complete');
});

// Global error handler
window.addEventListener('error', function(e) {
    console.error('Global error:', e.message, 'at', e.filename, ':', e.lineno);
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
});
</script>

<style>
.user-item.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}
</style>
@endsection
