<?php
session_start();
require_once 'db.php';

// Login do admin
if (!isset($_SESSION['admin_logged'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_OBJ);
        if ($admin && password_verify($password, $admin->password)) {
            $_SESSION['admin_logged'] = true;
            header("Location: admin.php");
            exit;
        } else {
            $error = "Credenciais inválidas!";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
        <title>Admin Login | TROPA DO WU-TANG</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * { margin:0; padding:0; box-sizing:border-box; }
            body {
                background: linear-gradient(135deg, #0a0a0c 0%, #050508 100%);
                font-family: 'Inter', sans-serif;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .login-box {
                background: rgba(12,14,18,0.95);
                backdrop-filter: blur(16px);
                border-radius: 32px;
                padding: 40px 32px;
                border-bottom: 2px solid #ff0033;
                width: 100%;
                max-width: 400px;
            }
            .logo {
                text-align: center;
                margin-bottom: 32px;
            }
            .logo i {
                font-size: 48px;
                color: #ff0033;
                background: rgba(255,0,51,0.1);
                padding: 16px;
                border-radius: 24px;
            }
            h2 { 
                color: #ff0033; 
                text-align: center; 
                margin-bottom: 28px;
                font-size: 1.5rem;
            }
            input {
                width: 100%;
                background: #1a1a1a;
                border: 1px solid #333;
                padding: 14px 16px;
                border-radius: 16px;
                color: white;
                margin-bottom: 16px;
                font-size: 14px;
                transition: 0.3s;
            }
            input:focus {
                outline: none;
                border-color: #ff0033;
            }
            button {
                width: 100%;
                background: #ff0033;
                border: none;
                padding: 14px;
                border-radius: 16px;
                font-weight: bold;
                cursor: pointer;
                font-size: 14px;
                transition: 0.3s;
            }
            button:hover {
                background: #cc0000;
                transform: scale(1.02);
            }
            .error { 
                color: #ff4d6d; 
                text-align: center; 
                margin-bottom: 16px;
                background: rgba(255,77,109,0.1);
                padding: 10px;
                border-radius: 12px;
                font-size: 13px;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <div class="logo">
                <i class="fas fa-shield-hal"></i>
            </div>
            <h2>🔐 ADMIN LOGIN</h2>
            <?php if(isset($error)) echo "<div class='error'><i class='fas fa-exclamation-triangle'></i> $error</div>"; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Usuário" required autocomplete="off">
                <input type="password" name="password" placeholder="Senha" required>
                <button type="submit"><i class="fas fa-arrow-right"></i> ENTRAR</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ==================== CRUD DE CHAVES ====================

$msg = '';

// Criar chave
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_key'])) {
    $chave = strtoupper(trim($_POST['chave'] ?? ''));
    $tempo_valor = (int)($_POST['tempo_valor'] ?? 7);
    $tempo_unidade = $_POST['tempo_unidade'] ?? 'dias'; // 'dias' ou 'horas'
    
    // Calcular expiração
    if ($tempo_unidade == 'horas') {
        $expira = date('Y-m-d H:i:s', strtotime("+$tempo_valor hours"));
        $duracao_minutos = $tempo_valor * 60;
        $texto_expira = "$tempo_valor hora(s)";
    } else {
        $expira = date('Y-m-d H:i:s', strtotime("+$tempo_valor days"));
        $duracao_minutos = $tempo_valor * 1440;
        $texto_expira = "$tempo_valor dia(s)";
    }
    
    if (empty($chave)) {
        $chave = '@WuTang-' . strtoupper(substr(md5(uniqid()), 0, 12));
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO keys_table (chave, status, expira, duracao_minutos) VALUES (?, 2, ?, ?)");
        $stmt->execute([$chave, $expira, $duracao_minutos]);
        $msg = "✅ Chave criada com sucesso!<br>
                <strong style='color:#ff0033; font-family:monospace;'>$chave</strong><br>
                Expira em: $texto_expira";
    } catch (Exception $e) {
        $msg = "❌ Erro: " . $e->getMessage();
    }
}

// Ativar chave
if (isset($_GET['activate'])) {
    $pdo->prepare("UPDATE keys_table SET status = 2 WHERE id = ?")->execute([$_GET['activate']]);
    header("Location: admin.php");
    exit;
}

// Desativar chave
if (isset($_GET['deactivate'])) {
    $pdo->prepare("UPDATE keys_table SET status = 0 WHERE id = ?")->execute([$_GET['deactivate']]);
    header("Location: admin.php");
    exit;
}

// Excluir chave
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM keys_table WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: admin.php");
    exit;
}

$keys = $pdo->query("SELECT * FROM keys_table ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Admin | TROPA DO WU-TANG</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background: #0a0a0c;
            font-family: 'Inter', sans-serif;
            color: #e2e8f0;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        /* Header */
        .header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            gap: 15px;
        }
        h1 { 
            color: #ff0033; 
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        h1 i { font-size: 28px; }
        .btn-logout {
            background: rgba(255,77,109,0.2);
            border: 1px solid #ff4d6d;
            padding: 10px 24px;
            border-radius: 40px;
            color: #ff4d6d;
            text-decoration: none;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .btn-logout:hover { background: #ff4d6d; color: #000; }
        
        /* Cards */
        .card {
            background: rgba(12,14,18,0.92);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.05);
            padding: 24px;
            margin-bottom: 30px;
        }
        .card h3 {
            margin-bottom: 20px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card h3 i { color: #ff0033; }
        
        /* Formulário */
        .form-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .form-field {
            flex: 1;
            min-width: 160px;
        }
        .form-field label {
            display: block;
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 6px;
            letter-spacing: 1px;
        }
        input, select {
            background: #1a1a1a;
            border: 1px solid #333;
            padding: 12px 16px;
            border-radius: 12px;
            color: white;
            width: 100%;
            font-size: 14px;
            transition: 0.3s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #ff0033;
        }
        .btn-cyber {
            background: #ff0033;
            border: none;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            color: #000;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .btn-cyber:hover { background: #cc0000; transform: scale(1.02); }
        
        /* Tabela */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        th {
            color: #9ca3af;
            font-weight: 600;
            font-size: 12px;
            letter-spacing: 1px;
        }
        td { font-size: 13px; }
        .status-active { color: #ff0033; font-weight: 600; }
        .status-pending { color: #ffbd44; font-weight: 600; }
        .status-inactive { color: #ff4d6d; font-weight: 600; }
        
        /* Botões de ação */
        .btn-sm {
            background: transparent;
            border: 1px solid;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 6px;
            transition: 0.2s;
        }
        .btn-sm i { margin-right: 4px; }
        .btn-success { border-color: #ff0033; color: #ff0033; }
        .btn-success:hover { background: #ff0033; color: #000; }
        .btn-warning { border-color: #ffbd44; color: #ffbd44; }
        .btn-warning:hover { background: #ffbd44; color: #000; }
        .btn-danger { border-color: #ff4d6d; color: #ff4d6d; }
        .btn-danger:hover { background: #ff4d6d; color: #000; }
        
        /* Alertas */
        .alert-success {
            background: rgba(255,0,51,0.1);
            border: 1px solid #ff0033;
            border-radius: 16px;
            padding: 14px 18px;
            margin-bottom: 20px;
            color: #ff0033;
        }
        
        /* Responsivo */
        @media (max-width: 768px) {
            body { padding: 15px; }
            .card { padding: 18px; }
            .form-group { flex-direction: column; }
            .form-field { min-width: 100%; }
            .btn-cyber { width: 100%; justify-content: center; }
            th, td { padding: 10px 8px; }
            .btn-sm { padding: 4px 10px; font-size: 10px; }
            h1 { font-size: 20px; }
        }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #1a1a1a; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #ff0033; border-radius: 10px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-crown"></i> TROPA DO WU-TANG | ADMIN</h1>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </div>

    <?php if ($msg): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Card de Criação de Chave -->
    <div class="card">
        <h3><i class="fas fa-plus-circle"></i> Criar Nova Chave</h3>
        <form method="POST">
            <div class="form-group">
                <div class="form-field">
                    <label><i class="fas fa-key"></i> Chave (opcional)</label>
                    <input type="text" name="chave" placeholder="Deixe em branco para gerar automaticamente" autocomplete="off">
                </div>
                <div class="form-field">
                    <label><i class="fas fa-hourglass-half"></i> Tempo</label>
                    <input type="number" name="tempo_valor" value="7" min="1" max="999" required>
                </div>
                <div class="form-field">
                    <label><i class="fas fa-clock"></i> Unidade</label>
                    <select name="tempo_unidade">
                        <option value="dias">Dias</option>
                        <option value="horas">Horas</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>&nbsp;</label>
                    <button type="submit" name="create_key" class="btn-cyber"><i class="fas fa-wand-magic"></i> CRIAR CHAVE</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabela de Chaves -->
    <div class="card">
        <h3><i class="fas fa-key"></i> Chaves Registradas</h3>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Chave</th>
                        <th>Status</th>
                        <th>Expira</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($keys)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:40px;">
                                <i class="fas fa-inbox fa-2x" style="color:#4a4a5a;"></i><br>
                                Nenhuma chave criada ainda
                             </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($keys as $k): 
                            $statusClass = '';
                            $statusText = '';
                            if ($k->status == 2) { 
                                $statusClass = 'status-active'; 
                                $statusText = '✅ ATIVA'; 
                            } elseif ($k->status == 1) { 
                                $statusClass = 'status-pending'; 
                                $statusText = '⏳ PENDENTE'; 
                            } else { 
                                $statusClass = 'status-inactive'; 
                                $statusText = '❌ INATIVA'; 
                            }
                            
                            $expira_date = date('d/m/Y H:i', strtotime($k->expira));
                            $expira_diff = (strtotime($k->expira) - time()) / 86400;
                            $expira_color = $expira_diff < 3 ? '#ffbd44' : '#9ca3af';
                        ?>
                            <tr>
                                <td><?= $k->id ?></td>
                                <td><code style="background:#1a1a1a; padding:4px 8px; border-radius:6px;"><?= htmlspecialchars($k->chave) ?></code></td>
                                <td class="<?= $statusClass ?>"><?= $statusText ?></td>
                                <td style="color: <?= $expira_color ?>;"><?= $expira_date ?></td>
                                <td>
                                    <?php if ($k->status != 2): ?>
                                        <a href="?activate=<?= $k->id ?>" class="btn-sm btn-success" onclick="return confirm('Ativar esta chave?')"><i class="fas fa-play"></i> Ativar</a>
                                    <?php else: ?>
                                        <a href="?deactivate=<?= $k->id ?>" class="btn-sm btn-warning" onclick="return confirm('Desativar esta chave?')"><i class="fas fa-pause"></i> Desativar</a>
                                    <?php endif; ?>
                                    <a href="?delete=<?= $k->id ?>" class="btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja EXCLUIR esta chave?')"><i class="fas fa-trash"></i> Excluir</a>
                                 </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>