<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_key'])) {
    $_SESSION['user_key'] = 'local_user';
}

$user_key = $_SESSION['user_key'];
$dias_restantes = 999;
$horas_restantes = 23;

// Salvar Live no histórico (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_live'])) {
    $card = $_POST['card'] ?? '';
    $response = $_POST['response'] ?? '';
    if ($card && $response) {
        salvarLive($pdo, $user_key, $card, $response);
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}

// Buscar histórico do usuário
$historico = buscarHistorico($pdo, $user_key);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@WuTang - TERMINAL V3</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    
    <style>
        :root {
            --bg-color: #050505;
            --card-bg: #0f0f11;
            --accent-green: #00ff88;
            --accent-red: #ff3333;
            --accent-blue: #00ccff;
            --text-main: #e0e0e0;
            --border-color: #222;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'JetBrains Mono', monospace;
            background-image: radial-gradient(#151515 1px, transparent 1px);
            background-size: 20px 20px;
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-color); }
        ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent-green); }

        .navbar {
            background: rgba(15, 15, 17, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
        }

        .brand-text {
            color: var(--accent-green);
            font-weight: 700;
            letter-spacing: 2px;
            text-shadow: 0 0 10px rgba(255, 0, 51, 0.3);
        }

        .cyber-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            transition: transform 0.2s;
        }

        .api-select {
            background: #000;
            color: var(--accent-blue);
            border: 1px solid var(--accent-blue);
            font-weight: bold;
        }
        
        .api-select:focus {
            background: #000;
            color: var(--accent-blue);
            box-shadow: 0 0 10px rgba(0, 204, 255, 0.3);
        }

        textarea.form-control {
            background: #080808;
            color: #fff;
            border: 1px solid var(--border-color);
            resize: none;
            font-size: 13px;
        }
        
        textarea.form-control:focus {
            background: #080808;
            color: #fff;
            border-color: var(--accent-green);
            box-shadow: none;
        }

        .btn-cyber {
            border: 1px solid;
            background: transparent;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
        }

        .btn-start {
            color: var(--accent-green);
            border-color: var(--accent-green);
        }
        .btn-start:hover {
            background: var(--accent-green);
            color: #000;
            box-shadow: 0 0 15px rgba(255, 0, 51, 0.4);
        }

        .btn-stop {
            color: var(--accent-red);
            border-color: var(--accent-red);
        }
        .btn-stop:hover {
            background: var(--accent-red);
            color: #000;
            box-shadow: 0 0 15px rgba(255, 51, 51, 0.4);
        }

        .badge-counter {
            font-size: 1.5rem;
            display: block;
        }

        .status-card {
            text-align: center;
            padding: 15px;
            border-top: 2px solid transparent;
        }
        .st-approved { border-top-color: var(--accent-green); }
        .st-reproved { border-top-color: var(--accent-red); }
        .st-tested { border-top-color: var(--accent-blue); }
        .st-total { border-top-color: #fff; }

        .results-area {
            height: 400px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            background: #080808;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
        }

        .text-success { color: var(--accent-green) !important; text-shadow: 0 0 5px rgba(255,0,51,0.2); }
        .text-danger { color: var(--accent-red) !important; }
        .badge-live { background-color: var(--accent-green); color: black; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .badge-die { background-color: var(--accent-red); color: white; padding: 2px 6px; border-radius: 4px; }
        
        .key-badge {
            background: rgba(0,255,136,0.1);
            border: 1px solid rgba(0,255,136,0.3);
            border-radius: 40px;
            padding: 4px 12px;
            font-size: 0.75rem;
        }
        
        .result-item {
            padding: 6px 8px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            border-left: 3px solid transparent;
            margin-bottom: 4px;
            word-break: break-all;
        }
        .result-approved { border-left-color: var(--accent-green); background: rgba(255,0,51,0.03); }
        .result-declined { border-left-color: var(--accent-red); background: rgba(255,51,51,0.03); }
        
        .historico-item {
            padding: 8px;
            border-bottom: 1px solid rgba(255,0,51,0.1);
        }
        
        hr.border-secondary { border-color: #333 !important; }
        
        .btn-sm-cyber {
            background: rgba(255,0,51,0.1);
            border: none;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            color: var(--accent-green);
            transition: 0.2s;
        }
        .btn-sm-cyber:hover { background: var(--accent-green); color: #000; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand brand-text" href="#"><i class="fas fa-terminal me-2"></i>@WuTang CENTRAL</a>
        <div class="ms-auto d-flex gap-2 align-items-center">
            <div class="key-badge"><i class="fas fa-key me-1"></i> <?= substr($user_key, 0, 12) ?>...</div>
            <div class="key-badge"><i class="fas fa-hourglass-half me-1"></i> <?= $dias_restantes ?>d <?= $horas_restantes ?>h</div>
            <a href="logout.php" class="btn-sm-cyber"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div id="bannerIP" class="alert alert-danger" style="display: none; border-radius: 8px; border: 1px solid var(--accent-red); background: rgba(255, 51, 51, 0.1);">
        <strong><i class="fas fa-exclamation-triangle"></i> ATENÇÃO:</strong> Seu IP foi bloqueado ou a conexão caiu por excesso de timeouts (3 erros seguidos). 
        <br>Por favor, <b>PAUSE A EXECUÇÃO, TROQUE SEU IP (Ligue/Desligue o Modo Avião)</b> e depois clique em Continuar Teste.
        <button type="button" class="btn-close btn-close-white float-end" onclick="$(this).parent().slideUp();"></button>
    </div>

    <div class="row">
        
        <div class="col-md-4 mb-4">
            <div class="cyber-card p-3 h-100">
                <label class="form-label text-white-50"><i class="fas fa-server"></i> Selecione a API:</label>
                <select id="api_type" class="form-select api-select mb-3">
                    <option value="gates/ttk/paya3.php">🔷 PAYPAL GATE</option>
                    <option value="gates/ttk/paya4.php">⚡ MISMATCH GATE</option>
                    <option value="gates/ttk/paya2.php">💳 VBV GATE</option>
                </select>

                <label class="form-label text-white-50"><i class="fas fa-list"></i> Lista de Cartões:</label>
                <textarea id="lista" class="form-control mb-3" rows="10" placeholder="CC|MM|AA|CVV&#10;Ex: 4220619673914191|12|2032|000"></textarea>

                <div class="d-grid gap-2">
                    <button class="btn btn-cyber btn-start" id="btnStart"><i class="fas fa-play"></i> INICIAR TESTE</button>
                    <button class="btn btn-cyber btn-stop" id="btnStop"><i class="fas fa-stop"></i> PARAR</button>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            
            <div class="row mb-3 g-2">
                <div class="col-3">
                    <div class="cyber-card status-card st-approved">
                        <small class="text-white-50">APROVADAS</small>
                        <span id="cLive" class="badge-counter text-success">0</span>
                    </div>
                </div>
                <div class="col-3">
                    <div class="cyber-card status-card st-reproved">
                        <small class="text-white-50">REPROVADAS</small>
                        <span id="cDie" class="badge-counter text-danger">0</span>
                    </div>
                </div>
                <div class="col-3">
                    <div class="cyber-card status-card st-tested">
                        <small class="text-white-50">TESTADAS</small>
                        <span id="testado" class="badge-counter text-info">0</span>
                    </div>
                </div>
                <div class="col-3">
                    <div class="cyber-card status-card st-total">
                        <small class="text-white-50">CARREGADAS</small>
                        <span id="carregada" class="badge-counter text-white">0</span>
                    </div>
                </div>
            </div>

            <nav>
                <div class="nav nav-tabs border-0 mb-2" id="nav-tab" role="tablist">
                    <button class="nav-link active bg-transparent text-success border-success" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab"><i class="fas fa-check-circle"></i> Aprovadas</button>
                    <button class="nav-link bg-transparent text-danger border-danger ms-2" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab"><i class="fas fa-times-circle"></i> Reprovadas</button>
                    <button class="nav-link bg-transparent text-info border-info ms-2" id="nav-historico-tab" data-bs-toggle="tab" data-bs-target="#nav-historico" type="button" role="tab"><i class="fas fa-history"></i> Histórico</button>
                </div>
            </nav>

            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-home" role="tabpanel">
                    <div class="cyber-card p-2">
                        <div id="lives_cs" class="results-area">
                            <div class="text-center py-5 text-muted">Nenhuma aprovada ainda</div>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-success mt-2 w-100" onclick="limparLives()">Limpar Aprovadas</button>
                </div>
                
                <div class="tab-pane fade" id="nav-profile" role="tabpanel">
                    <div class="cyber-card p-2">
                        <div id="dies_cs" class="results-area">
                            <div class="text-center py-5 text-muted">Nenhuma reprovada ainda</div>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-danger mt-2 w-100" onclick="limparDies()">Limpar Reprovadas</button>
                </div>

                <div class="tab-pane fade" id="nav-historico" role="tabpanel">
                    <div class="cyber-card p-2">
                        <div id="historicoList" class="results-area">
                            <?php if (empty($historico)): ?>
                                <div class="text-center py-5 text-muted">Nenhum histórico encontrado</div>
                            <?php else: ?>
                                <?php foreach ($historico as $item): ?>
                                    <div class="historico-item">
                                        <span class="badge-live"><i class="fas fa-dragon"></i> LIVE</span>
                                        <strong><?= htmlspecialchars($item->card) ?></strong>
                                        <div class="text-white-50 small mt-1"><?= htmlspecialchars(substr($item->response, 0, 200)) ?>...</div>
                                        <div class="text-muted small mt-1"><?= date('d/m/Y H:i:s', strtotime($item->created_at)) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script>
toastr.options = { closeButton: true, progressBar: true, timeOut: 2500, positionClass: "toast-top-right", preventDuplicates: true };

let isRunning = false, isPaused = false, approved = 0, declined = 0, processed = 0, currentList = [], currentIndex = 0;
let activeRequest = false;
let timeoutCount = 0;

const successSound = new Audio('https://www.soundjay.com/misc/sounds/bell-ringing-05.mp3');
successSound.volume = 0.5;

function playSound() {
    successSound.play().catch(e => console.log('Audio play failed'));
}

// Tab switching
$('#nav-home-tab').click(function() {
    $('#nav-home').show();
    $('#nav-profile').hide();
    $('#nav-historico').hide();
    $(this).addClass('active');
    $('#nav-profile-tab, #nav-historico-tab').removeClass('active');
});
$('#nav-profile-tab').click(function() {
    $('#nav-home').hide();
    $('#nav-profile').show();
    $('#nav-historico').hide();
    $(this).addClass('active');
    $('#nav-home-tab, #nav-historico-tab').removeClass('active');
});
$('#nav-historico-tab').click(function() {
    $('#nav-home').hide();
    $('#nav-profile').hide();
    $('#nav-historico').show();
    $(this).addClass('active');
    $('#nav-home-tab, #nav-profile-tab').removeClass('active');
});

function startChecker() {
    let lista = $('#lista').val().trim();
    if (!lista) { toastr.error('Lista vazia!'); return; }
    if (isRunning) { toastr.warning('Já está em execução!'); return; }
    currentList = lista.split('\n').filter(l => l.trim());
    if (!currentList.length) return;
    
    if (!isPaused) {
        processed = 0; approved = 0; declined = 0;
        $('#cLive, #cDie, #testado').text('0');
        $('#carregada').text(currentList.length);
        $('#lives_cs, #dies_cs').html('');
    } else {
        let oldCarregada = parseInt($('#carregada').text()) || 0;
        if (oldCarregada === 0) $('#carregada').text(currentList.length);
    }
    
    isRunning = true;
    isPaused = false;
    currentIndex = 0;
    timeoutCount = 0;
    $('#bannerIP').slideUp();
    $('#btnStart').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ENGENHARIA ATIVA...');
    toastr.info(`Iniciando testes...`);
    processNext();
}

function processNext() {
    if (!isRunning || currentIndex >= currentList.length) { 
        if (!isPaused) finishChecker(); 
        return; 
    }
    if (activeRequest) return;
    let card = currentList[currentIndex];
    let apiUrl = $('#api_type').val();
    activeRequest = true;
    $.ajax({
        url: apiUrl + '?lista=' + encodeURIComponent(card),
        type: 'GET',
        timeout: 60000,
        success: function(res) {
            timeoutCount = 0;
            let isApproved = /Aprovada|Aproved|Live|APPROVED|✅|🔐/i.test(res);
            if (isApproved) {
                approved++;
                $('#cLive').text(approved);
                $('#lives_cs').prepend(`<div class="result-item result-approved">${escapeHtml(res)}</div><hr class="border-secondary">`);
                toastr.success('APROVADO!', '✅ LIVE');
                playSound();
                $.post(window.location.href, { save_live: 1, card: card, response: res.substring(0, 500) });
            } else {
                declined++;
                $('#cDie').text(declined);
                $('#dies_cs').prepend(`<div class="result-item result-declined">${escapeHtml(res)}</div><hr class="border-secondary">`);
                toastr.error('REPROVADO', '❌ DIE');
            }
            removelinha();
            currentIndex++;
            processed++;
            $('#testado').text(processed);
            activeRequest = false;
            setTimeout(processNext, 5500);
        },
        error: function(xhr, status, err) {
            if (status === "timeout" || xhr.status === 0 || xhr.status === 403 || xhr.status === 429) {
                timeoutCount++;
                if (timeoutCount >= 3) {
                    $('#dies_cs').prepend(`<div class="result-item result-declined text-warning">⚠️ PAUSA AUTOMÁTICA: IP Bloqueado ou Timeout (3x)!</div><hr class="border-secondary">`);
                    toastr.error('Muitos erros! Sistema pausado. Troque seu IP.', 'IP BLOQUEADO');
                    $('#bannerIP').slideDown();
                    stopChecker();
                    return;
                }
            } else {
                timeoutCount = 0;
            }

            $('#dies_cs').prepend(`<div class="result-item result-declined">❌ ${card} ➔ Erro: ${status} - ${err}</div><hr class="border-secondary">`);
            declined++;
            $('#cDie').text(declined);
            removelinha();
            currentIndex++;
            processed++;
            $('#testado').text(processed);
            activeRequest = false;
            setTimeout(processNext, 5500);
        }
    });
}

function removelinha() {
    let lines = $('#lista').val().split('\n');
    lines.splice(0, 1);
    $('#lista').val(lines.join('\n'));
}

function finishChecker() {
    isRunning = false;
    isPaused = false;
    activeRequest = false;
    $('#btnStart').prop('disabled', false).html('<i class="fas fa-play"></i> INICIAR TESTE');
    toastr.success(`VARREDURA CONCLUÍDA! Aprovadas: ${approved}`);
}

function stopChecker() {
    isRunning = false;
    isPaused = true;
    activeRequest = false;
    $('#btnStart').prop('disabled', false).html('<i class="fas fa-play"></i> CONTINUAR TESTE');
    toastr.warning('Operação pausada.');
}

function limparLives() {
    $('#lives_cs').html('<div class="text-center py-5 text-muted">Nenhuma aprovada ainda</div>');
    approved = 0;
    $('#cLive').text('0');
    toastr.success('Aprovadas limpas.');
}

function limparDies() {
    $('#dies_cs').html('<div class="text-center py-5 text-muted">Nenhuma reprovada ainda</div>');
    declined = 0;
    $('#cDie').text('0');
    toastr.success('Reprovadas limpas.');
}

function escapeHtml(str) {
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

$('#btnStart').click(startChecker);
$('#btnStop').click(stopChecker);
</script>
</body>
</html>