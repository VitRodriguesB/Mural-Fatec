<?php
require_once 'config.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    die("Departamento não encontrado.");
}

$stmt = $pdo->prepare("SELECT nome FROM departamentos WHERE id = ?");
$stmt->execute([$id]);
$depto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$depto) {
    die("Departamento inválido.");
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$base_url = rtrim($base_url, '/\\');
$link_qrcode = $base_url . "/relatorio.php?depto=" . $id;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Imprimir QR - <?= htmlspecialchars($depto['nome']) ?></title>
    <style>
        /* Configurações de página para remover margens do navegador */
        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            body { background: white; }
            .no-print { display: none; }
            .print-container { 
                box-shadow: none !important; 
                border: none !important;
                height: 100vh; /* Ocupa exatamente a altura da folha */
            }
        }

        body { 
            background: #f0f0f0; 
            margin: 0; 
            padding: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .print-container {
            background: white;
            width: 210mm;
            height: 297mm;
            padding: 20mm; /* Margem interna reduzida */
            text-align: center;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center; /* Centraliza tudo verticalmente */
            gap: 40px; /* Espaço entre os blocos */
        }

        .titulo { 
            font-size: 50pt; 
            font-weight: 900; 
            color: #b00000; 
            margin: 0;
            text-transform: uppercase;
            letter-spacing: -1px;
        }

        .subtitulo { 
            font-size: 28pt; 
            color: #333; 
            margin: 0;
            font-weight: 600;
        }

        .qr-wrapper {
            margin: 20px 0;
        }

        .qr-image { 
            width: 130mm; /* Tamanho otimizado para o A4 */
            height: 130mm;
            padding: 15px;
            border: 2px solid #f0f0f0;
            border-radius: 20px;
        }

        .instrucao { 
            font-size: 20pt; 
            color: #555; 
            line-height: 1.4;
            margin: 0;
        }

        .footer-logo {
            font-size: 14pt;
            font-weight: bold;
            color: #999;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <div class="print-container">
        <div>
            <div class="titulo">Mural Digital</div>
            <div class="subtitulo"><?= htmlspecialchars($depto['nome']) ?></div>
        </div>

        <div class="qr-wrapper">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=<?= urlencode($link_qrcode) ?>" class="qr-image" alt="QR Code">
        </div>

        <div>
            <p class="instrucao">
                Aponte a câmera do celular para o QR Code<br>
                e confira os <strong>horários de atendimento</strong>.
            </p>
            <div class="footer-logo">FATEC PRESIDENTE PRUDENTE</div>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Aguarda um pouco para garantir que a imagem do QR Code da API carregou
            setTimeout(function() {
                window.print();
            }, 800);
        };
    </script>

</body>
</html>