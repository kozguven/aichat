<?php
require_once 'config.php';

try {
    // Veritabanı bağlantısını oluştur
    $db = new SQLite3('settings.db');
    
    // API anahtarları için tablo oluştur
    $db->exec('
        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    // Varsayılan ayarları ekle
    $defaultSettings = [
        'openai_api_key' => '',
        'claude_api_key' => '',
        'gemini_api_key' => '',
        'default_model' => 'gpt-3.5-turbo',
        'theme' => 'light',
        'language' => 'tr'
    ];
    
    // Her bir ayarı kontrol et ve yoksa ekle
    $stmt = $db->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)');
    
    foreach ($defaultSettings as $key => $value) {
        $stmt->reset();
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':value', $value, SQLITE3_TEXT);
        $stmt->execute();
    }
    
    echo "✅ Veritabanı başarıyla oluşturuldu ve varsayılan ayarlar eklendi!\n";
    echo "🔑 API anahtarlarınızı ayarlamak için settings.php sayfasını ziyaret edin.\n";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    echo "💡 İpucu: Veritabanı dizininin yazılabilir olduğundan emin olun.\n";
    exit(1);
}
