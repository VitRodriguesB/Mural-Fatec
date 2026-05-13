<?php
session_start();
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: login.php");
    exit;
}
require_once 'config.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$base_url = rtrim($base_url, '/\\');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // SALVAR COLABORADOR (Com vínculo de Depto)
    if (isset($_POST['save_colab'])) {
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        if (!empty($_POST['id'])) {
            $pdo->prepare("UPDATE colaboradores SET nome=?, ativo=?, id_departamento=? WHERE id=?")
                ->execute([$_POST['nome'], $ativo, $_POST['id_depto'], $_POST['id']]);
        } else {
            $pdo->prepare("INSERT INTO colaboradores (nome, ativo, id_departamento) VALUES (?, ?, ?)")
                ->execute([$_POST['nome'], $ativo, $_POST['id_depto']]);
        }
    }
    // SALVAR HORÁRIO (Sem campo de Depto)
    if (isset($_POST['save_horario'])) {
        if (!empty($_POST['id'])) {
            $sql = "UPDATE horarios SET id_colaborador=?, dia_semana=?, horario_inicio=?, horario_fim=? WHERE id=?";
            $pdo->prepare($sql)->execute([$_POST['id_colab'], $_POST['dia'], $_POST['inicio'], $_POST['fim'], $_POST['id']]);
        } else {
            $sql = "INSERT INTO horarios (id_colaborador, dia_semana, horario_inicio, horario_fim) VALUES (?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$_POST['id_colab'], $_POST['dia'], $_POST['inicio'], $_POST['fim']]);
        }
    }
    // Salvar Departamento
    if (isset($_POST['save_depto'])) {
        if (!empty($_POST['id'])) {
            $pdo->prepare("UPDATE departamentos SET nome=? WHERE id=?")->execute([$_POST['nome'], $_POST['id']]);
        } else {
            $pdo->prepare("INSERT INTO departamentos (nome) VALUES (?)")->execute([$_POST['nome']]);
        }
    }
    header("Location: admin.php"); exit;
}

if (isset($_GET['del_table']) && isset($_GET['del_id'])) {
    $tabela = $_GET['del_table'];
    $id = $_GET['del_id'];
    $allowed = ['colaboradores', 'departamentos', 'horarios'];
    if (in_array($tabela, $allowed)) {
        $pdo->prepare("DELETE FROM $tabela WHERE id = ?")->execute([$id]);
    }
    header("Location: admin.php"); exit;
}

// BUSCA DE DADOS COM OS NOVOS VÍNCULOS
$colaboradores = $pdo->query("SELECT c.*, d.nome as depto_nome FROM colaboradores c LEFT JOIN departamentos d ON c.id_departamento = d.id ORDER BY c.nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$departamentos = $pdo->query("SELECT * FROM departamentos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$horarios = $pdo->query("SELECT h.*, c.nome as colab, d.nome as depto 
                         FROM horarios h 
                         JOIN colaboradores c ON h.id_colaborador = c.id 
                         JOIN departamentos d ON c.id_departamento = d.id 
                         ORDER BY h.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$edit_data = null;
if (isset($_GET['edit_t']) && isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM {$_GET['edit_t']} WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Administrativo - FATEC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root { --fatec: #b00000; }
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .nav-tabs .nav-link.active { background: var(--fatec) !important; color: #fff !important; border-radius: 8px 8px 0 0; }
        .nav-link { color: #444; font-weight: 600; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .btn-fatec { background: var(--fatec); color: white; border: none; }
        .btn-fatec:hover { background: #8a0000; color: white; }
        .qr-card { border: 1px solid #eee; text-align: center; border-radius: 12px; padding: 20px; background: #fff; transition: transform 0.2s; height: 100%; }
        .qr-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">Mural Digital FATEC</h2>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
    </div>

    <ul class="nav nav-tabs mb-4" id="adminTabs">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-horarios">Horários</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-colab">Colaboradores</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-depto">Departamentos</a></li>
        <li class="nav-item"><a class="nav-link text-danger" data-bs-toggle="tab" href="#tab-share"><i class="bi bi-qr-code"></i> Compartilhar</a></li>
    </ul>

    <div class="tab-content mt-3">
        <div class="tab-pane fade show active" id="tab-horarios">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card p-4">
                        <h5 class="fw-bold mb-3"><?= $edit_data ? 'Editar' : 'Vincular' ?> Horário</h5>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= @$edit_data['id'] ?>">
                            <div class="mb-2">
                                <label class="small fw-bold">Colaborador</label>
                                <select name="id_colab" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach($colaboradores as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= @$edit_data['id_colaborador']==$c['id']?'selected':'' ?>><?= $c['nome'] ?> (<?= $c['depto_nome'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="small fw-bold">Dia</label>
                                <select name="dia" class="form-select">
                                    <?php foreach(['Segunda','Terça','Quarta','Quinta','Sexta','Sábado'] as $dia): ?>
                                        <option <?= @$edit_data['dia_semana']==$dia?'selected':'' ?>><?= $dia ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col"><label class="small fw-bold">Início</label><input type="time" name="inicio" class="form-control" value="<?= @$edit_data['horario_inicio'] ?>" required></div>
                                <div class="col"><label class="small fw-bold">Fim</label><input type="time" name="fim" class="form-control" value="<?= @$edit_data['horario_fim'] ?>" required></div>
                            </div>
                            <button name="save_horario" class="btn btn-fatec w-100">Salvar Horário</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card p-3 table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light"><tr><th>Depto</th><th>Nome</th><th>Dia/Hora</th><th>Ação</th></tr></thead>
                            <tbody>
                                <?php foreach($horarios as $h): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= $h['depto'] ?></span></td>
                                    <td><strong><?= $h['colab'] ?></strong></td>
                                    <td><small><?= $h['dia_semana'] ?> (<?= substr($h['horario_inicio'],0,5) ?>-<?= substr($h['horario_fim'],0,5) ?>)</small></td>
                                    <td>
                                        <a href="?edit_t=horarios&edit_id=<?= $h['id'] ?>" class="text-primary me-2"><i class="bi bi-pencil"></i></a>
                                        <a href="?del_table=horarios&del_id=<?= $h['id'] ?>" class="text-danger" onclick="return confirm('Excluir?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-colab">
            <div class="row">
                <div class="col-md-4">
                    <div class="card p-4">
                        <h5 class="fw-bold mb-3">Gerenciar Colaborador</h5>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= @$edit_data['id'] ?>">
                            <div class="mb-3">
                                <label class="small fw-bold">Nome</label>
                                <input type="text" name="nome" class="form-control" value="<?= @$edit_data['nome'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold">Departamento Fixo</label>
                                <select name="id_depto" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach($departamentos as $d): ?>
                                        <option value="<?= $d['id'] ?>" <?= @$edit_data['id_departamento']==$d['id']?'selected':'' ?>><?= $d['nome'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="ativo" id="swAtivo" checked>
                                <label class="form-check-label" for="swAtivo">Ativo</label>
                            </div>
                            <button name="save_colab" class="btn btn-fatec w-100">Salvar</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card p-3">
                        <table class="table">
                            <thead><tr><th>Nome</th><th>Departamento</th><th>Ações</th></tr></thead>
                            <tbody>
                                <?php foreach($colaboradores as $c): ?>
                                <tr>
                                    <td><?= $c['nome'] ?></td>
                                    <td><small><?= $c['depto_nome'] ?></small></td>
                                    <td>
                                        <a href="?edit_t=colaboradores&edit_id=<?= $c['id'] ?>" class="text-primary me-2"><i class="bi bi-pencil"></i></a>
                                        <a href="?del_table=colaboradores&del_id=<?= $c['id'] ?>" class="text-danger" onclick="return confirm('Excluir?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-share">
            <div class="card p-4">
                <div class="row">
                    <?php foreach($departamentos as $d): 
                        $link_compartilhar = $base_url . "/relatorio.php?depto=" . $d['id'];
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="qr-card">
                            <h6 class="fw-bold mb-3"><?= htmlspecialchars($d['nome']) ?></h6>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($link_compartilhar) ?>" class="img-fluid mb-3">
                            <div class="input-group input-group-sm mb-2">
                                <input type="text" id="link_<?= $d['id'] ?>" class="form-control" value="<?= $link_compartilhar ?>" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copiarLink('link_<?= $d['id'] ?>')">Copiar</button>
                            </div>
                            <a href="imprimir_qr.php?id=<?= $d['id'] ?>" target="_blank" class="btn btn-danger btn-sm w-100"><i class="bi bi-printer"></i> Imprimir</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-depto">
            <div class="row">
                <div class="col-md-4">
                    <div class="card p-4">
                        <h5 class="fw-bold mb-3">Departamento</h5>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= @$edit_data['id'] ?>">
                            <input type="text" name="nome" class="form-control mb-3" placeholder="Nome Depto" value="<?= @$edit_data['nome'] ?>" required>
                            <button name="save_depto" class="btn btn-fatec w-100">Salvar</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card p-3">
                        <table class="table">
                            <thead><tr><th>Nome</th><th>Ações</th></tr></thead>
                            <tbody>
                                <?php foreach($departamentos as $d): ?>
                                <tr><td><?= $d['nome'] ?></td><td>
                                    <a href="?edit_t=departamentos&edit_id=<?= $d['id'] ?>" class="text-primary me-2"><i class="bi bi-pencil"></i></a>
                                    <a href="?del_table=departamentos&del_id=<?= $d['id'] ?>" class="text-danger" onclick="return confirm('Excluir?')"><i class="bi bi-trash"></i></a>
                                </td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function copiarLink(idInput) {
    var copyText = document.getElementById(idInput);
    copyText.select();
    navigator.clipboard.writeText(copyText.value);
    alert("Link copiado!");
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>