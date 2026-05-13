<?php
require_once 'config.php';

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
    $horarios = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);

    foreach ($horarios as $h) {
        $hora_formatada = substr($h['horario_inicio'], 0, 5) . " - " . substr($h['horario_fim'], 0, 5);
        $dados_tabela[$hora_formatada][$h['dia_semana']][] = $h['colab_nome'];
    }
}

$dias_semana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quadro de Horários - <?= htmlspecialchars($nome_depto) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 30px; }
        .container { max-width: 1200px; }
        .table-responsive { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .table thead { background-color: #b00000; color: white; }
        .table th, .table td { text-align: center; vertical-align: middle; border: 1px solid #dee2e6; }
        .colab-name { display: block; font-size: 0.9em; font-weight: 500; color: #333; }
        .header-title { color: #b00000; font-weight: bold; margin-bottom: 25px; }
        .btn-filtro { background-color: #b00000; color: white; }
        .btn-filtro:hover { background-color: #8a0000; color: white; }
    </style>
</head>
<body>

<div class="container mb-5">
    <div class="text-center">
        <h2 class="header-title">Quadro de Horários</h2>
    </div>

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end justify-content-center">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Selecione o Departamento para Visualizar:</label>
                    <select name="depto" class="form-select" onchange="this.form.submit()">
                        <option value="">Escolha...</option>
                        <?php foreach ($departamentos as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $depto_selecionado == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($depto_selecionado): ?>
        <?php if (empty($dados_tabela)): ?>
            <div class="alert alert-warning text-center">Nenhum horário cadastrado para este departamento.</div>
        <?php else: ?>
            <div class="table-responsive">
                <h4 class="text-center mb-4"><?= htmlspecialchars($nome_depto) ?></h4>
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Horário</th>
                            <?php foreach ($dias_semana as $dia) echo "<th>$dia</th>"; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dados_tabela as $horario => $dias): ?>
                        <tr>
                            <td class="fw-bold bg-light"><?= $horario ?></td>
                            <?php foreach ($dias_semana as $dia_f): ?>
                                <td>
                                    <?php 
                                    if (isset($dias[$dia_f])) {
                                        foreach($dias[$dia_f] as $colaborador) {
                                            echo "<span class='colab-name'>" . htmlspecialchars($colaborador) . "</span>";
                                        }
                                    } else { 
                                        echo "<span class='text-muted'>-</span>"; 
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>