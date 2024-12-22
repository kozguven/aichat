<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['api_data'] = [
        'message' => $_POST['message'],
        'history' => json_decode($_POST['history'], true),
        'model' => $_POST['model']
    ];
    exit;
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

if (!isset($_SESSION['api_data'])) {
    echo "data: " . json_encode(['error' => 'No message data found']) . "\n\n";
    session_write_close();
    exit;
}

$data = $_SESSION['api_data'];
unset($_SESSION['api_data']);
session_write_close();

try {
    $message = $data['message'];
    $history = $data['history'];
    $model = $data['model'];
    
    if (strpos($model, 'gpt') !== false) {
        streamOpenAI($message, $history, $model);
    } elseif (strpos($model, 'claude') !== false) {
        streamClaude($message, $history, $model);
    } elseif (strpos($model, 'gemini') !== false) {
        streamGemini($message, $history, $model);
    } else {
        throw new Exception('Geçersiz model seçildi: ' . $model);
    }
} catch (Exception $e) {
    echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
}
flush();

function streamOpenAI($message, $history, $model_id) {
    if (empty(OPENAI_API_KEY)) {
        throw new Exception('OpenAI API anahtarı ayarlanmamış');
    }

    $url = 'https://api.openai.com/v1/chat/completions';
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ];
    
    $messages = [];
    foreach ($history as $msg) {
        if (!empty($msg['content'])) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }
    }
    $messages[] = [
        'role' => 'user',
        'content' => $message
    ];
    
    $data = [
        'model' => $model_id,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 4000,
        'stream' => true
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
        $lines = explode("\n", $data);
        foreach ($lines as $line) {
            if (strlen(trim($line)) === 0) continue;
            if (strpos($line, 'data: ') !== 0) continue;
            
            $jsonData = substr($line, 6);
            if ($jsonData === '[DONE]') {
                echo "data: [DONE]\n\n";
                flush();
                return strlen($data);
            }
            
            $decoded = json_decode($jsonData, true);
            if (isset($decoded['choices'][0]['delta']['content'])) {
                echo "data: " . json_encode(['content' => $decoded['choices'][0]['delta']['content']]) . "\n\n";
                flush();
            }
        }
        return strlen($data);
    });
    
    curl_exec($ch);
    curl_close($ch);
}

function streamClaude($message, $history, $model_id) {
    if (empty(CLAUDE_API_KEY)) {
        throw new Exception('Claude API anahtarı ayarlanmamış');
    }

    $url = 'https://api.anthropic.com/v1/messages';
    $headers = [
        'Content-Type: application/json',
        'x-api-key: ' . CLAUDE_API_KEY,
        'anthropic-version: 2023-06-01'
    ];
    
    $messages = [];
    foreach ($history as $msg) {
        if (!empty($msg['content'])) {
            $messages[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content']
            ];
        }
    }
    $messages[] = [
        'role' => 'user',
        'content' => $message
    ];
    
    $data = [
        'model' => $model_id,
        'messages' => $messages,
        'stream' => true,
        'max_tokens' => 4000
    ];
    
    error_log('Claude Request: ' . json_encode($data)); // Debug için
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
        error_log('Claude Response: ' . $data); // Debug için
        $lines = explode("\n", $data);
        foreach ($lines as $line) {
            if (strlen(trim($line)) === 0) continue;
            
            // event: completion satırını kontrol et
            if (strpos($line, 'event: completion') !== false) {
                continue;
            }
            
            // data: satırını bul
            if (strpos($line, 'data: ') === false) continue;
            
            $jsonData = trim(substr($line, strpos($line, 'data: ') + 6));
            if ($jsonData === '[DONE]') {
                echo "data: [DONE]\n\n";
                flush();
                return strlen($data);
            }
            
            $decoded = json_decode($jsonData, true);
            if ($decoded && isset($decoded['delta']) && isset($decoded['delta']['text'])) {
                echo "data: " . json_encode(['content' => $decoded['delta']['text']]) . "\n\n";
                flush();
            }
        }
        return strlen($data);
    });
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Claude API hatası: ' . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
        error_log('Claude Error Response: ' . $response); // Debug için
        throw new Exception('Claude API yanıt kodu: ' . $httpCode . ' - Yanıt: ' . $response);
    }
    
    curl_close($ch);
    echo "data: [DONE]\n\n";
    flush();
}

function streamGemini($message, $history, $model_id) {
    if (empty(GEMINI_API_KEY)) {
        throw new Exception('Gemini API anahtarı ayarlanmamış');
    }

    $url = "https://generativelanguage.googleapis.com/v1/models/{$model_id}:generateContent?key=" . GEMINI_API_KEY;
    $headers = [
        'Content-Type: application/json'
    ];
    
    $contents = [];
    foreach ($history as $msg) {
        if (!empty($msg['content'])) {
            $contents[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [
                    ['text' => $msg['content']]
                ]
            ];
        }
    }
    $contents[] = [
        'role' => 'user',
        'parts' => [
            ['text' => $message]
        ]
    ];
    
    $data = [
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 4000
        ]
    ];
    
    error_log('Gemini Request: ' . json_encode($data)); // Debug için
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Gemini API hatası: ' . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
        error_log('Gemini Error Response: ' . $response); // Debug için
        throw new Exception('Gemini API yanıt kodu: ' . $httpCode . ' - Yanıt: ' . $response);
    }
    
    curl_close($ch);
    
    $decoded = json_decode($response, true);
    if (!$decoded || !isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception('Gemini\'den geçersiz yanıt: ' . $response);
    }
    
    // Yanıtı kelime kelime gönder
    $text = $decoded['candidates'][0]['content']['parts'][0]['text'];
    $words = preg_split('/(\s+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    
    foreach ($words as $word) {
        echo "data: " . json_encode(['content' => $word]) . "\n\n";
        flush();
        usleep(50000); // 50ms bekle
    }
    
    echo "data: [DONE]\n\n";
    flush();
}
