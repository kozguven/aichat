<?php
require_once 'config.php';

try {
    $db = new SQLite3('settings.db');
    
    // API anahtarlarını veritabanına kaydet
    $stmt = $db->prepare('
        INSERT OR REPLACE INTO settings (key, value, updated_at) 
        VALUES (:key, :value, CURRENT_TIMESTAMP)
    ');
    
    $apiKeys = [
        'openai_api_key' => defined('OPENAI_API_KEY') ? OPENAI_API_KEY : null,
        'claude_api_key' => defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : null,
        'gemini_api_key' => defined('GEMINI_API_KEY') ? GEMINI_API_KEY : null
    ];
    
    $updatedKeys = [];
    
    foreach ($apiKeys as $key => $value) {
        if ($value !== null) {
            $stmt->reset();
            $stmt->bindValue(':key', $key, SQLITE3_TEXT);
            $stmt->bindValue(':value', $value, SQLITE3_TEXT);
            $stmt->execute();
            $updatedKeys[] = $key;
        }
    }
    
    if (empty($updatedKeys)) {
        echo "⚠️ Hiçbir API anahtarı bulunamadı. config.php dosyasını kontrol edin.\n";
    } else {
        echo "✅ Aşağıdaki API anahtarları başarıyla güncellendi:\n";
        foreach ($updatedKeys as $key) {
            echo "  • " . ucfirst(str_replace('_api_key', '', $key)) . "\n";
        }
    }
    
    // Veritabanındaki mevcut anahtarları kontrol et
    $result = $db->query('SELECT key, value FROM settings WHERE key LIKE "%_api_key"');
    $missingKeys = [];
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (empty($row['value'])) {
            $missingKeys[] = $row['key'];
        }
    }
    
    if (!empty($missingKeys)) {
        echo "\n⚠️ Aşağıdaki API anahtarları hala ayarlanmamış:\n";
        foreach ($missingKeys as $key) {
            echo "  • " . ucfirst(str_replace('_api_key', '', $key)) . "\n";
        }
        echo "\n💡 API anahtarlarını settings.php sayfasından ayarlayabilirsiniz.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    exit(1);
}
