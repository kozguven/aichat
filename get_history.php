<?php
session_start();

if (!isset($_SESSION['chats'])) {
    $_SESSION['chats'] = [];
}

if (!isset($_SESSION['current_chat_id'])) {
    $_SESSION['current_chat_id'] = uniqid();
    $_SESSION['chats'][$_SESSION['current_chat_id']] = [
        'model' => 'openai',
        'messages' => []
    ];
}

// Sohbetleri tersine çevir
$reversed_chats = array_reverse($_SESSION['chats'], true);

header('Content-Type: application/json');
echo json_encode([
    'chats' => $reversed_chats,
    'current_chat_id' => $_SESSION['current_chat_id'],
    'current_chat' => $_SESSION['chats'][$_SESSION['current_chat_id']] ?? null
]);