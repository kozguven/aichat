<?php
session_start();

$db = new SQLite3('settings.db');

// Veritabanında settings tablosu yoksa oluştur
$db->exec('
    CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT
    )
');

// API anahtarlarını kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (:key, :value)');
    
    $stmt->bindValue(':key', 'openai_key', SQLITE3_TEXT);
    $stmt->bindValue(':value', $_POST['openai_key'], SQLITE3_TEXT);
    $stmt->execute();
    
    $stmt->bindValue(':key', 'claude_key', SQLITE3_TEXT);
    $stmt->bindValue(':value', $_POST['claude_key'], SQLITE3_TEXT);
    $stmt->execute();
    
    $stmt->bindValue(':key', 'gemini_key', SQLITE3_TEXT);
    $stmt->bindValue(':value', $_POST['gemini_key'], SQLITE3_TEXT);
    $stmt->execute();
    
    // config.php dosyasını güncelle
    $config_content = "<?php\n\n";
    $config_content .= "define('OPENAI_API_KEY', '" . $_POST['openai_key'] . "');\n";
    $config_content .= "define('CLAUDE_API_KEY', '" . $_POST['claude_key'] . "');\n";
    $config_content .= "define('GEMINI_API_KEY', '" . $_POST['gemini_key'] . "');\n\n";
    
    // Mevcut fonksiyonları oku ve ekle
    $current_config = file_get_contents('config.php');
    if (preg_match('/function.*$/s', $current_config, $matches)) {
        $config_content .= $matches[0];
    }
    
    file_put_contents('config.php', $config_content);
    $success = true;
}

// Mevcut API anahtarlarını veritabanından al
$openai_key = '';
$claude_key = '';
$gemini_key = '';

$results = $db->query('SELECT key, value FROM settings');
while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
    switch ($row['key']) {
        case 'openai_key':
            $openai_key = $row['value'];
            break;
        case 'claude_key':
            $claude_key = $row['value'];
            break;
        case 'gemini_key':
            $gemini_key = $row['value'];
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chat Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">API Ayarları</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">Ayarlar başarıyla kaydedildi!</div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="openai_key" class="form-label">OpenAI API Anahtarı</label>
                                <input type="password" class="form-control" id="openai_key" name="openai_key" value="<?php echo htmlspecialchars($openai_key); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="claude_key" class="form-label">Claude API Anahtarı</label>
                                <input type="password" class="form-control" id="claude_key" name="claude_key" value="<?php echo htmlspecialchars($claude_key); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="gemini_key" class="form-label">Gemini API Anahtarı</label>
                                <input type="password" class="form-control" id="gemini_key" name="gemini_key" value="<?php echo htmlspecialchars($gemini_key); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Kaydet</button>
                            <a href="index.php" class="btn btn-secondary">Geri Dön</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // API anahtarlarını göster/gizle için toggle butonları ekle
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('input[type="password"]');
        inputs.forEach(input => {
            const div = input.parentElement;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-secondary mt-2';
            btn.textContent = 'Göster/Gizle';
            btn.onclick = function() {
                input.type = input.type === 'password' ? 'text' : 'password';
            };
            div.appendChild(btn);
        });
    });
    </script>
</body>
</html>
