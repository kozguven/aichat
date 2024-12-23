<?php
require_once 'config.php';

header('Content-Type: application/json');

// API anahtarlarını kontrol et
$models = [];

// OpenAI modelleri
if (!empty(OPENAI_API_KEY)) {
    try {
        $ch = curl_init('https://api.openai.com/v1/models');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]);
        
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        
        if ($data && isset($data['data'])) {
            // Sadece GPT modellerini filtrele
            $gptModels = array_filter($data['data'], function($model) {
                return strpos($model['id'], 'gpt') !== false;
            });
            
            // Modelleri formatlayıp ekle
            foreach ($gptModels as $model) {
                $models[] = [
                    'id' => $model['id'],
                    'name' => $model['id'],
                    'provider' => 'openai'
                ];
            }
        }
    } catch (Exception $e) {
        error_log('OpenAI Models Error: ' . $e->getMessage());
    }
}

// Claude modelleri
if (!empty(CLAUDE_API_KEY)) {
    try {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . CLAUDE_API_KEY,
            'anthropic-version: 2023-06-01'
        ]);
        
        // Claude'un mevcut modellerini manuel olarak tanımla
        // Bu modeller Claude API'sinin desteklediği güncel modellerdir
        $claudeModels = [
            ['id' => 'claude-3-5-sonnet-20241022', 'name' => 'Claude 3.5 Sonnet'],
            ['id' => 'claude-3-5-haiku-20241022', 'name' => 'Claude 3.5 Haiku'],
            ['id' => 'claude-3-opus-20240229', 'name' => 'Claude 3 Opus'],
            ['id' => 'claude-3-sonnet-20240229', 'name' => 'Claude 3 Sonnet'],
            ['id' => 'claude-3-haiku-20240307', 'name' => 'Claude 3 Haiku']
        ];
        
        // API'yi test et - eğer çalışıyorsa modelleri ekle
        $testResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode !== 401) { // API anahtarı geçerliyse
            foreach ($claudeModels as $model) {
                $models[] = [
                    'id' => $model['id'],
                    'name' => $model['name'],
                    'provider' => 'claude'
                ];
            }
        } else {
            error_log('Claude API key is invalid');
        }
    } catch (Exception $e) {
        error_log('Claude Models Error: ' . $e->getMessage());
    }
}

// Gemini modelleri
if (!empty(GEMINI_API_KEY)) {
    try {
        $ch = curl_init('https://generativelanguage.googleapis.com/v1/models?key=' . GEMINI_API_KEY);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        
        if ($data && isset($data['models'])) {
            foreach ($data['models'] as $model) {
                if (isset($model['name'])) {
                    // Model adı "models/gemini-pro" formatında geliyor, sadece "gemini-pro" kısmını al
                    $modelId = str_replace('models/', '', $model['name']);
                    $models[] = [
                        'id' => $modelId,
                        'name' => $model['displayName'] ?? $modelId,
                        'provider' => 'gemini'
                    ];
                }
            }
        }
    } catch (Exception $e) {
        error_log('Gemini Models Error: ' . $e->getMessage());
    }
}

// Debug için
error_log('Final Models List: ' . print_r($models, true));

echo json_encode($models);
