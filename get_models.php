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
        $ch = curl_init('https://api.anthropic.com/v1/models');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . CLAUDE_API_KEY,
            'anthropic-version: 2023-06-01'
        ]);
        
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        
        if ($data && isset($data['data'])) {
            foreach ($data['data'] as $model) {
                $models[] = [
                    'id' => $model['id'],
                    'name' => $model['display_name'],
                    'provider' => 'claude'
                ];
            }
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
