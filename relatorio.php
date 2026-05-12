<?php
require_once 'config.php';

// Descobre a URL base do sistema (funciona no localhost ou no Ngrok)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$base_url = rtrim($base_url, '/\\');

$departamentos = $pdo->query("SELECT * FROM departamentos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$depto_selecionado = isset($_GET['depto']) ? $_GET['depto'] : null;
$dados_tabela = [];
$nome_depto = "";

if ($depto_selecionado) {
    $stmt_depto = $pdo->prepare("SELECT nome FROM departamentos WHERE id = ?");
    $stmt_depto->execute([$depto_selecionado]);
    $nome_depto = $stmt_depto->fetchColumn();

    $sql = "SELECT h.*, c.nome as colab_nome 
            FROM horarios h
            JOIN colaboradores c ON h.id_colaborador = c.id
            WHERE h.id_departamento = ? AND c.ativo = 1
            ORDER BY h.horario_inicio, h.dia_semana";
    
    $stmt_horarios = $pdo->prepare($sql);
    $stmt_horarios->execute([$depto_selecionado]);
    $horarios_db = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);

    foreach ($horarios_db as $row) {
        $h = substr($row['horario_inicio'], 0, 5) . " - " . substr($row['horario_fim'], 0, 5);
        $d = $row['dia_semana'];
        $dados_tabela[$h][$d][] = $row['colab_nome']; 
    }
}

$dias_fixos = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Horários - Fatec Prudente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --fatec: #b00000; }
        body { padding: 20px; background: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header-fatec { border-bottom: 3px solid var(--fatec); padding-bottom: 10px; margin-bottom: 30px; }
        .header-fatec h2 { color: var(--fatec); font-weight: bold; }
        .card-selecao { border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 12px; }
        .depto-title { background: #f2f2f2; padding: 10px; border-left: 5px solid var(--fatec); font-weight: bold; font-size: 16px; margin-bottom: 15px; }
        .table-horarios { width: 100%; border: 1px solid #000; table-layout: fixed; }
        .table-horarios th, .table-horarios td { border: 1px solid #000 !important; text-align: center; vertical-align: middle; font-size: 13px; padding: 8px !important; }
        .table-horarios th { background-color: #f8f9fa; }
        .colab-name { font-weight: bold; color: #333; display: block; margin-bottom: 2px; }
        .btn-fatec { background-color: var(--fatec); color: white; border: none; }
        .btn-fatec:hover { background-color: #8a0000; color: white; }
        @media print { .no-print { display: none !important; } body { padding: 0; } }
    </style>
</head>
<body>

<div class="container">
    <div class="header-fatec text-center">
        <h2>FATEC PRESIDENTE PRUDENTE</h2>
        <h5 class="text-muted">Gestão de Horários Administrativo</h5>
    </div>

    <?php if (!$depto_selecionado): ?>
    <div class="row justify-content-center no-print mb-5">
        <div class="col-md-6">
            <div class="card card-selecao p-4">
                <form method="GET" action="relatorio.php">
                    <label class="fw-bold mb-2 text-center w-100">Selecione o Departamento / Relatório:</label>
                    <div class="input-group">
                        <select name="depto" class="form-select" required>
                            <option value="">Escolha...</option>
                            <?php foreach($departamentos as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= $d['nome'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-fatec fw-bold px-4">Gerar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($depto_selecionado && !empty($nome_depto)): ?>
        <div class="depto-title">Departamento: <?= htmlspecialchars($nome_depto) ?></div>
        
        <?php if(empty($dados_tabela)): ?>
            <div class="alert alert-warning text-center">Nenhum horário cadastrado para este departamento no momento.</div>
        <?php else: ?>
            <table class="table table-horarios mb-4">
                <thead>
                    <tr>
                        <th style="width: 110px;">Horário</th>
                        <?php foreach ($dias_fixos as $dia) echo "<th>$dia</th>"; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dados_tabela as $horario => $dias): ?>
                    <tr>
                        <td class="fw-bold bg-light"><?= $horario ?></td>
                        <?php foreach ($dias_fixos as $dia_f): ?>
                            <td>
                                <?php 
                                if (isset($dias[$dia_f])) {
                                    foreach($dias[$dia_f] as $colaborador) {
                                        echo "<span class='colab-name'>{$colaborador}</span>";
                                    }
                                } else { echo "-"; }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="row mt-5 text-center align-items-center">
                <div class="col-md-6 text-md-end mb-4 mb-md-0 no-print">
                    <button onclick="window.print()" class="btn btn-danger btn-lg px-5">Imprimir PDF</button>
                </div>
                <div class="col-md-6 text-md-start">
                    <?php $link_atual = $base_url . "/relatorio.php?depto=" . $depto_selecionado; ?>
                    <p class="fw-bold mb-1 small text-muted">Acesse no Celular:</p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=<?= urlencode($link_atual) ?>" alt="QR Code" class="border p-1 bg-white shadow-sm">
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>