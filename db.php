<?php
// db.php - Banco de dados SQLite

$db_file = __DIR__ . '/wolf_central.db';

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA journal_mode=WAL");
    
    // Tabela de chaves
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS keys_table (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chave TEXT UNIQUE NOT NULL,
            status INTEGER DEFAULT 1,
            expira DATETIME,
            duracao_minutos INTEGER DEFAULT 1440,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Tabela de logs de acesso
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS access_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chave TEXT,
            ip TEXT,
            data_acesso DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Tabela admin
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            password TEXT
        )
    ");
    
    // Tabela de histórico de lives (APROVADAS)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS historico_lives (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_key TEXT NOT NULL,
            card TEXT NOT NULL,
            response TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Criar admin padrão (se não existir)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)")
            ->execute(['admin', password_hash('Wolfadmin7', PASSWORD_DEFAULT)]);
    }
    
} catch (PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}

function validarChave($pdo, $chave) {
    $stmt = $pdo->prepare("SELECT * FROM keys_table WHERE chave = ?");
    $stmt->execute([$chave]);
    $key = $stmt->fetch(PDO::FETCH_OBJ);
    if (!$key) return false;
    if ($key->status != 2) return false;
    if (strtotime($key->expira) < time()) return false;
    return $key;
}

function logAcesso($pdo, $chave, $ip) {
    $stmt = $pdo->prepare("INSERT INTO access_logs (chave, ip) VALUES (?, ?)");
    $stmt->execute([$chave, $ip]);
}

// ==================== FUNÇÕES DO HISTÓRICO ====================

function salvarLive($pdo, $user_key, $card, $response) {
    $sql = "INSERT INTO historico_lives (user_key, card, response, created_at) VALUES (?, ?, ?, datetime('now'))";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$user_key, $card, $response]);
}

function buscarHistorico($pdo, $user_key) {
    $sql = "SELECT * FROM historico_lives WHERE user_key = ? ORDER BY created_at DESC LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_key]);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}
?>