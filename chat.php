<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/notifications.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- API Handling ---

// 1. Send Message
if (isset($_GET['action']) && $_GET['action'] === 'send') {
    header('Content-Type: application/json');

    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }

    // Read JSON input if sent as JSON, or POST
    $receiver_id = $_POST['receiver_id'] ?? null;
    $message = trim($_POST['message'] ?? '');

    if (!$receiver_id || !$message) {
        echo json_encode(['success' => false, 'error' => 'Missing fields']);
        exit;
    }

    try {
        // Verify receiver exists
        $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE id = ?");
        $stmt->execute([$receiver_id]);
        $receiver = $stmt->fetch();

        if (!$receiver) {
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }

        // Insert Message
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $message]);

        // Send Notification
        $sender_name = $_SESSION['user_name'];
        $body = "New message from $sender_name: " . substr($message, 0, 50) . "...";

        if (!empty($receiver['phone'])) {
            sendSMS($receiver['phone'], $body);
        }
        if (!empty($receiver['email'])) {
            sendEmail($receiver['email'], "New Message from $sender_name", $body);
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 2. Fetch Messages
if (isset($_GET['action']) && $_GET['action'] === 'fetch') {
    header('Content-Type: application/json');
    $other_id = $_GET['user_id'] ?? null;

    if (!$other_id) {
        echo json_encode(['success' => false, 'messages' => []]);
        exit;
    }

    try {
        // Mark as read
        $update = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $update->execute([$other_id, $user_id]);

        // Fetch
        $stmt = $pdo->prepare("
            SELECT m.*, u.name as sender_name,
                   CASE WHEN m.sender_id = ? THEN 'me' ELSE 'them' END as type
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (sender_id = ? AND receiver_id = ?)
               OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at ASC
        ");
        $stmt->execute([$user_id, $user_id, $other_id, $other_id, $user_id]);
        $messages = $stmt->fetchAll();

        echo json_encode(['success' => true, 'messages' => $messages]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// --- View Logic ---
require_once 'includes/header.php';

$current_chat_user = null;
if (isset($_GET['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
    $stmt->execute([$_GET['user_id']]);
    $current_chat_user = $stmt->fetch();
}

// Get recent conversations
$conversations = [];
try {
    // Get unique user IDs involved in conversations
    $c_stmt = $pdo->prepare("
        SELECT DISTINCT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as other_id
        FROM messages
        WHERE sender_id = ? OR receiver_id = ?
    ");
    $c_stmt->execute([$user_id, $user_id, $user_id]);
    $other_ids = $c_stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($other_ids) {
        foreach ($other_ids as $oid) {
            // Get user info
            $u_stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = ?");
            $u_stmt->execute([$oid]);
            $u_info = $u_stmt->fetch();

            if ($u_info) {
                // Get last message
                $m_stmt = $pdo->prepare("SELECT message, created_at, is_read, sender_id FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1");
                $m_stmt->execute([$user_id, $oid, $oid, $user_id]);
                $last_msg = $m_stmt->fetch();

                $conversations[] = [
                    'id' => $u_info['id'],
                    'name' => $u_info['name'],
                    'role' => $u_info['role'],
                    'message' => $last_msg['message'] ?? '',
                    'time' => $last_msg['created_at'] ?? '',
                    'unread' => ($last_msg && $last_msg['sender_id'] == $oid && !$last_msg['is_read'])
                ];
            }
        }
        // Sort by time
        usort($conversations, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
    }
} catch (Exception $e) {
    // ignore
}

// Get list for "New Chat" modal
$new_chat_users = [];
if ($user_role === 'patient') {
    // All doctors
    $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'doctor'");
    $new_chat_users = $stmt->fetchAll();
} elseif ($user_role === 'doctor') {
    // Patients with appointments
    $stmt = $pdo->prepare("SELECT DISTINCT u.id, u.name FROM users u JOIN appointments a ON u.id = a.patient_id WHERE a.doctor_id = ?");
    $stmt->execute([$user_id]);
    $new_chat_users = $stmt->fetchAll();
}
?>

<div class="row bg-white shadow-sm rounded overflow-hidden" style="height: 600px;">
    <!-- Sidebar: Conversations -->
    <div class="col-md-4 col-lg-3 border-end h-100 d-flex flex-column bg-light p-0">
        <div class="p-3 border-bottom bg-white sticky-top">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Messages</h5>
                <button class="btn btn-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#newChatModal">
                    <i class="fas fa-plus"></i> New
                </button>
            </div>
        </div>
        <div class="list-group list-group-flush overflow-auto flex-grow-1">
            <?php if (empty($conversations)): ?>
                <div class="p-3 text-center text-muted">No conversations yet.</div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <a href="?user_id=<?php echo $conv['id']; ?>" class="list-group-item list-group-item-action <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $conv['id']) ? 'active' : ''; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?php echo htmlspecialchars($conv['name']); ?></h6>
                            <small class="<?php echo $conv['unread'] ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                <?php echo date('M d', strtotime($conv['time'])); ?>
                            </small>
                        </div>
                        <p class="mb-1 text-truncate small"><?php echo htmlspecialchars($conv['message']); ?></p>
                        <?php if($conv['unread']): ?>
                            <span class="badge bg-danger rounded-pill">!</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="col-md-8 col-lg-9 h-100 d-flex flex-column p-0">
        <?php if ($current_chat_user): ?>
            <div class="p-3 border-bottom bg-white d-flex align-items-center shadow-sm" style="z-index: 10;">
                <h5 class="mb-0">Chat with <?php echo htmlspecialchars($current_chat_user['name']); ?></h5>
            </div>

            <!-- Messages -->
            <div id="chat-messages" class="flex-grow-1 p-3 overflow-auto" style="background-color: #f8f9fa;">
                <div class="text-center text-muted">Loading messages...</div>
            </div>

            <!-- Input -->
            <div class="p-3 border-top bg-white">
                <form id="chat-form" class="d-flex gap-2">
                    <input type="hidden" name="receiver_id" value="<?php echo $current_chat_user['id']; ?>">
                    <input type="text" name="message" id="message-input" class="form-control" placeholder="Type a message..." required autocomplete="off">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        <?php else: ?>
            <div class="h-100 d-flex flex-column justify-content-center align-items-center text-muted">
                <i class="fas fa-comments fa-3x mb-3"></i>
                <h4>Select a conversation to start chatting</h4>
                <button class="btn btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#newChatModal">Start New Chat</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- New Chat Modal -->
<div class="modal fade" id="newChatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start New Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <?php if (empty($new_chat_users)): ?>
                        <div class="text-center p-3">No users available to chat.</div>
                    <?php else: ?>
                        <?php foreach ($new_chat_users as $u): ?>
                            <a href="?user_id=<?php echo $u['id']; ?>" class="list-group-item list-group-item-action">
                                <?php echo htmlspecialchars($u['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($current_chat_user): ?>
<script>
const currentUserId = <?php echo $current_chat_user['id']; ?>;
const chatMessages = document.getElementById('chat-messages');
const csrfToken = "<?php echo $_SESSION['csrf_token']; ?>";

function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function escapeHtml(text) {
  return text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
}

function fetchMessages() {
    fetch('chat.php?action=fetch&user_id=' + currentUserId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.messages.length === 0) {
                    chatMessages.innerHTML = '<div class="text-center text-muted mt-5">No messages yet. Say hello!</div>';
                    return;
                }

                let html = '';
                data.messages.forEach(msg => {
                    const isMe = msg.type === 'me';
                    // Use flexbox for alignment
                    const align = isMe ? 'justify-content-end' : 'justify-content-start';
                    const bg = isMe ? 'bg-primary text-white' : 'bg-white border';
                    const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

                    html += `
                        <div class="d-flex ${align} mb-2">
                            <div class="p-2 rounded shadow-sm ${bg}" style="max-width: 70%; word-wrap: break-word;">
                                <div>${escapeHtml(msg.message)}</div>
                                <div class="small text-${isMe ? 'light' : 'muted'} text-end mt-1" style="font-size: 0.75rem; opacity: 0.8;">${time}</div>
                            </div>
                        </div>
                    `;
                });

                // Only update if changed
                if (chatMessages.innerHTML !== html) {
                    const isAtBottom = chatMessages.scrollHeight - chatMessages.scrollTop <= chatMessages.clientHeight + 100;
                    chatMessages.innerHTML = html;
                    if (isAtBottom) scrollToBottom();
                }
            }
        });
}

document.getElementById('chat-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    if (!message) return;

    const formData = new FormData();
    formData.append('receiver_id', currentUserId);
    formData.append('message', message);
    formData.append('csrf_token', csrfToken);

    input.value = ''; // Clear input

    fetch('chat.php?action=send', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fetchMessages(); // Refresh
            setTimeout(scrollToBottom, 100); // Ensure scroll
        } else {
            alert('Error sending message: ' + (data.error || 'Unknown error'));
        }
    });
});

// Initial load
fetchMessages();
setInterval(fetchMessages, 3000);
window.onload = function() {
    setTimeout(scrollToBottom, 500);
};
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
