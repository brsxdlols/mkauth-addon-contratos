<?php
// Iniciar sessão
session_name('mka');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexão direta com banco
$host = "127.0.0.1";
$user = "root";
$pass = "vertrigo";
$db = "mkradius";

$conecta = mysqli_connect($host, $user, $pass, $db);
mysqli_set_charset($conecta, "utf8");

if (mysqli_connect_errno()) {
    die('<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erro de Conexão</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .error-container {
                background: white;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                padding: 40px;
                text-align: center;
                max-width: 500px;
            }
            .error-icon { font-size: 64px; margin-bottom: 20px; }
            h1 { font-size: 24px; color: #333; margin-bottom: 10px; }
            p { color: #666; margin-bottom: 30px; line-height: 1.6; }
            .btn {
                display: inline-block;
                padding: 12px 30px;
                background: linear-gradient(135deg, #209cee 0%, #23d160 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: transform 0.3s;
            }
            .btn:hover { transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">❌</div>
            <h1>Erro de Conexão</h1>
            <p>Não foi possível conectar ao banco de dados.</p>
            <a href="index.php" class="btn">Voltar</a>
        </div>
    </body>
    </html>
    ');
}

// Definir diretório dos contratos
define('CONTRATOS_DIR', '/opt/mk-auth/admin/arquivos/');

// Buscar todos os clientes com contratos
$sql = "SELECT c.nome AS nome_cliente, c.contrato, c.uuid_cliente, sc.nome AS nome_contrato
        FROM sis_cliente c 
        JOIN sis_contrato sc ON c.contrato = sc.codigo 
        WHERE c.cli_ativado = 's' AND c.contrato IS NOT NULL
        ORDER BY c.nome ASC";

$resultado = $conecta->query($sql);

if (!$resultado) {
    die('<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erro ao Buscar Contratos</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .error-container {
                background: white;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                padding: 40px;
                text-align: center;
                max-width: 600px;
            }
            .error-icon { font-size: 64px; margin-bottom: 20px; }
            h1 { font-size: 24px; color: #333; margin-bottom: 10px; }
            p { color: #666; margin-bottom: 20px; line-height: 1.6; }
            .error-details {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                text-align: left;
                font-family: monospace;
                font-size: 12px;
                color: #e74c3c;
            }
            .btn {
                display: inline-block;
                padding: 12px 30px;
                background: linear-gradient(135deg, #209cee 0%, #23d160 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: transform 0.3s;
            }
            .btn:hover { transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">❌</div>
            <h1>Erro ao Buscar Contratos</h1>
            <p>Ocorreu um erro ao executar a consulta no banco de dados.</p>
            <div class="error-details">' . htmlspecialchars($conecta->error, ENT_QUOTES, 'UTF-8') . '</div>
            <a href="index.php" class="btn">Voltar</a>
        </div>
    </body>
    </html>
    ');
}

// Criar lista de downloads em HTML
$html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download de Contratos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #209cee 0%, #23d160 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.95;
            font-size: 16px;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }
        .stat {
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #209cee;
        }
        .stat-label {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
        .actions {
            padding: 20px 30px;
            background: #fff;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #209cee 0%, #23d160 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(32, 156, 238, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .list {
            padding: 30px;
            max-height: 600px;
            overflow-y: auto;
        }
        .item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        .item-info {
            flex: 1;
        }
        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .item-code {
            font-size: 12px;
            color: #6c757d;
        }
        .item-action {
            display: flex;
            gap: 10px;
        }
        .download-btn {
            padding: 8px 16px;
            background: #209cee;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .download-btn:hover {
            background: #1890d5;
            transform: translateY(-2px);
        }
        .progress-container {
            padding: 20px 30px;
            background: #f8f9fa;
            display: none;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #209cee 0%, #23d160 100%);
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 Backup de Contratos</h1>
            <p>Baixe todos os contratos individualmente</p>
        </div>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-value" id="totalContratos">0</div>
                <div class="stat-label">Total de Contratos</div>
            </div>
            <div class="stat">
                <div class="stat-value" id="baixados">0</div>
                <div class="stat-label">Baixados</div>
            </div>
        </div>
        
        <div class="actions">
            <button class="btn btn-primary" onclick="baixarTodos()">
                <span>⬇️</span> Baixar Todos (Individual)
            </button>
            <button class="btn btn-primary" onclick="window.location.href=\'baixar_zip_contratos.php\'" style="background: linear-gradient(135deg, #ff6b6b 0%, #ffa502 100%);">
                <span>📦</span> Baixar ZIP Compactado
            </button>
            <button class="btn btn-secondary" onclick="window.location.href=\'index.php\'">
                <span>◀️</span> Voltar
            </button>
        </div>
        
        <div class="progress-container" id="progressContainer">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill">0%</div>
            </div>
        </div>
        
        <div class="list" id="contratosList">
';

$contratos = [];
$totalContratos = 0;

while ($row = $resultado->fetch_assoc()) {
    // Caminho da pasta do cliente onde o PDF deve ser buscado
    $pastaContrato = CONTRATOS_DIR . $row['uuid_cliente'] . "/";
    
    // Busca arquivos que começam com 'contrato_' e terminam com '.pdf'
    $arquivos = glob($pastaContrato . "contrato_*.pdf");
    
    // Verifica se foram encontrados arquivos
    if (!empty($arquivos)) {
        foreach ($arquivos as $arquivo) {
            if (file_exists($arquivo) && is_readable($arquivo)) {
                $nomeCliente = htmlspecialchars($row['nome_cliente'], ENT_QUOTES, 'UTF-8');
                $nomeContrato = htmlspecialchars($row['nome_contrato'], ENT_QUOTES, 'UTF-8');
                $nomeArquivo = basename($arquivo);
                
                // Detectar protocolo
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
                $baseURL = $protocol . '://' . $_SERVER['HTTP_HOST'];
                $urlArquivo = $baseURL . '/admin/arquivos/' . htmlspecialchars($row['uuid_cliente'], ENT_QUOTES, 'UTF-8') . '/' . htmlspecialchars($nomeArquivo, ENT_QUOTES, 'UTF-8');
                
                $html .= '<div class="item">
                    <div class="item-info">
                        <div class="item-name">' . $nomeCliente . '</div>
                        <div class="item-code">Contrato: ' . $nomeContrato . ' | ' . htmlspecialchars($nomeArquivo, ENT_QUOTES, 'UTF-8') . '</div>
                    </div>
                    <div class="item-action">
                        <a href="' . $urlArquivo . '" class="download-btn" download target="_blank" data-index="' . $totalContratos . '">
                            📥 Baixar PDF
                        </a>
                    </div>
                </div>';
                
                $totalContratos++;
            }
        }
    }
}

$resultado->free();
$conecta->close();

$html .= '
        </div>
    </div>
    
    <script>
        document.getElementById("totalContratos").textContent = ' . $totalContratos . ';
        
        let baixadosCount = 0;
        
        function atualizarContador() {
            document.getElementById("baixados").textContent = baixadosCount;
            const percent = Math.round((baixadosCount / ' . $totalContratos . ') * 100);
            document.getElementById("progressFill").style.width = percent + "%";
            document.getElementById("progressFill").textContent = percent + "%";
        }
        
        function baixarTodos() {
            const links = document.querySelectorAll(".download-btn");
            const progressContainer = document.getElementById("progressContainer");
            progressContainer.style.display = "block";
            baixadosCount = 0;
            atualizarContador();
            
            let delay = 0;
            links.forEach((link, index) => {
                setTimeout(() => {
                    link.click();
                    baixadosCount++;
                    atualizarContador();
                }, delay);
                delay += 300; // 300ms entre cada download
            });
        }
        
        // Monitorar cliques individuais
        document.querySelectorAll(".download-btn").forEach(btn => {
            btn.addEventListener("click", function() {
                setTimeout(() => {
                    if (!document.getElementById("progressContainer").style.display || 
                        document.getElementById("progressContainer").style.display === "none") {
                        baixadosCount++;
                        atualizarContador();
                    }
                }, 100);
            });
        });
    </script>
</body>
</html>';

echo $html;
exit;
