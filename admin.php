<?php
session_start();
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: login.php");
    exit;
}
require_once 'config.php';

// Descobre a URL base para montar os links dos QR Codes
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$base_url = rtrim($base_url, '/\\');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_colab'])) {
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        if (!empty($_POST['id'])) {
            $pdo->prepare("UPDATE colaboradores SET nome=?, ativo=? WHERE id=?")->execute([$_POST['nome'], $ativo, $_POST['id']]);
        } else {
            $pdo->prepare("INSERT INTO colaboradores (nome, ativo) VALUES (?, ?)")->execute([$_POST['nome'], $ativo]);
        }
    }
    if (isset($_POST['save_depto'])) {
        if (!empty($_POST['id'])) {
            $pdo->prepare("UPDATE departamentos SET nome=? WHERE id=?")->execute([$_POST['nome'], $_POST['id']]);
        } else {
            $pdo->prepare("INSERT INTO departamentos (nome) VALUES (?)")->execute([$_POST['nome']]);
        }
    }
    if (isset($_POST['save_horario'])) {
        if (!empty($_POST['id'])) {
            $sql = "UPDATE horarios SET id_colaborador=?, id_departamento=?, dia_semana=?, horario_inicio=?, horario_fim=? WHERE id=?";
            $pdo->prepare($sql)->execute([$_POST['id_colab'], $_POST['id_depto'], $_POST['dia'], $_POST['inicio'], $_POST['fim'], $_POST['id']]);
        } else {
            $sql = "INSERT INTO horarios (id_colaborador, id_departamento, dia_semana, horario_inicio, horario_fim) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$_POST['id_colab'], $_POST['id_depto'], $_POST['dia'], $_POST['inicio'], $_POST['fim']]);
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

$colaboradores = $pdo->query("SELECT * FROM colaboradores ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$departamentos = $pdo->query("SELECT * FROM departamentos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$horarios = $pdo->query("SELECT h.*, c.nome as colab, d.nome as depto FROM horarios h JOIN colaboradores c ON h.id_colaborador = c.id JOIN departamentos d ON h.id_departamento = d.id ORDER BY h.id DESC")->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Gestão Administrativa | Fatec</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --fatec: #b00000; }
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .nav-tabs .nav-link.active { background: var(--fatec); color: #fff; border: none; }
        .nav-link { color: #333; font-weight: bold; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
        .btn-fatec { background: var(--fatec); color: white; }
        .btn-fatec:hover { background: #8a0000; color: white; }
        .search-box { margin-bottom: 15px; border-radius: 20px; padding-left: 15px; border: 1px solid #ddd; }
        .qr-card { border: 1px solid #eee; text-align: center; border-radius: 8px; padding: 15px; background: #fff; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Gestão Administrativa - Fatec</h2>
        <div>
            <a href="relatorio.php" target="_blank" class="btn btn-outline-dark me-2">Ver Relatório</a>
            <a href="logout.php" class="btn btn-outline-danger">Sair</a>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a class="nav-link <?= !isset($_GET['edit_t']) || $_GET['edit_t']=='horarios' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-horarios">Vincular Horários</a></li>
        <li class="nav-item"><a class="nav-link <?= @$_GET['edit_t']=='colaboradores'?'active':'' ?>" data-bs-toggle="tab" href="#tab-colab">Colaboradores</a></li>
        <li class="nav-item"><a class="nav-link <?= @$_GET['edit_t']=='departamentos'?'active':'' ?>" data-bs-toggle="tab" href="#tab-depto">Departamentos</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-qrcodes">Compartilhar Links</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade <?= !isset($_GET['edit_t']) || $_GET['edit_t']=='horarios' ? 'show active' : '' ?>" id="tab-horarios">
            <div class="row">
                <div class="col-md-4">
                    <div class="card p-4">
                        <h6 class="fw-bold mb-3"><?= $edit_data && $_GET['edit_t']=='horarios' ? 'Editar' : 'Vincular' ?> Horário</h6>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= @$edit_data['id'] ?>">
                            <select name="id_colab" class="form-select mb-2" required>
                                <option value="">Colaborador...</option>
                                <?php foreach($colaboradores as $c): if($c['ativo']): ?>
                                    <option value="<?= $c['id'] ?>" <?= @$edit_data['id_colaborador']==$c['id']?'selected':'' ?>><?= $c['nome'] ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                            <select name="id_depto" class="form-select mb-2" required>
                                <option value="">Departamento...</option>
                                <?php foreach($departamentos as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= @$edit_data['id_departamento']==$d['id']?'selected':'' ?>><?= $d['nome'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="dia" class="form-select mb-2">
                                <?php foreach(['Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo'] as $d): ?>
                                    <option <?= @$edit_data['dia_semana']==$d?'selected':'' ?>><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="row mb-3">
                                <div class="col"><label class="small">Início</label><input type="time" name="inicio" class="form-control" value="<?= @$edit_data['horario_inicio'] ?>" required></div>
                                <div class="col"><label class="small">Fim</label><input type="time" name="fim" class="form-control" value="<?= @$edit_data['horario_fim'] ?>" required></div>
                            </div>
                            <button name="save_horario" class="btn btn-fatec w-100">Salvar Horário</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card p-3">
                        <input type="text" class="form-control search-box" placeholder="Filtrar horários..." onkeyup="filterTable(this, 'table-horarios')">
                        <table class="table table-hover" id="table-horarios">
                            <thead><tr><th>Depto</th><th>Colaborador</th><th>Dia/Horário</th><th class="text-center">Ação</th></tr></thead>
                            <tbody>
                                <?php foreach($horarios as $h): ?>
                                <tr>
                                    <td><small class="badge bg-secondary"><?= $h['depto'] ?></small></td>
                                    <td><strong><?= $h['colab'] ?></strong></td>
                                    <td><?= $h['dia_semana'] ?><br><small class="text-muted"><?= substr($h['horario_inicio'],0,5) ?> - <?= substr($h['horario_fim'],0,5) ?></small></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="?edit_t=horarios&edit_id=<?= $h['id'] ?>" class="btn btn-outline-primary">Editar</a>
                                            <a href="?del_table=horarios&del_id=<?= $h['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Excluir?')">Remover</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade <?= @$_GET['edit_t']=='colaboradores'?'show active':'' ?>" id="tab-colab">
            <div class="row">
                <div class="col-md-4">
                    <div class="card p-4">
                        <h6 class="fw-bold mb-3"><?= $edit_data && $_GET['edit_t']=='colaboradores' ? 'Editar' : 'Novo' ?> Colaborador</h6>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= @$edit_data['id'] ?>">
                            <input type="text" name="nome" class="form-control mb-3" placeholder="Nome do Colaborador" value="<?= @$edit_data['nome'] ?>" required>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="ativo" id="ativoCheck" <?= (!isset($edit_data) || @$edit_data['ativo'] == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ativoCheck">Colaborador Ativo</label>
                            </div>
                            <button name="save_colab" class="btn btn-fatec w-100">Salvar Colaborador</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card p-3">
                        <input type="text" class="form-control search-box" placeholder="Procurar colaborador..." onkeyup="filterTable(this, 'table-colab')">
                        <table class="table" id="table-colab">
                            <thead><tr><th>Nome</th><th>Status</th><th>Ações</th></tr></thead>
                            <tbody>
                                <?php foreach($colaboradores as $c): ?>
                                <tr>
                                    <td><?= $c['nome'] ?></td>
                                    <td><?= $c['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Inativo</span>' ?></td>
                                    <td>
                                        <a href="?edit_t=colaboradores&edit_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                        <a href="?del_table=colaboradores&del_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir?')">Excluir</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade <?= @$_GET['edit_t']=='departamentos'?'show active':'' ?>" id="tab-depto">
            <div class="row">
                <div class="col-md-4">
                    <div class="card p-4">
                        <h6 class="fw-bold mb-3"><?= $edit_data && $_GET['edit_t']=='departamentos' ? 'Editar' : 'Novo' ?> Departamento</h6>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= @$edit_data['id'] ?>">
                            <input type="text" name="nome" class="form-control mb-3" placeholder="Ex: Secretaria Acadêmica" value="<?= @$edit_data['nome'] ?>" required>
                            <button name="save_depto" class="btn btn-fatec w-100">Salvar Departamento</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card p-3">
                        <input type="text" class="form-control search-box" placeholder="Procurar departamento..." onkeyup="filterTable(this, 'table-depto')">
                        <table class="table" id="table-depto">
                            <thead><tr><th>Nome do Departamento</th><th>Ações</th></tr></thead>
                            <tbody>
                                <?php foreach($departamentos as $d): ?>
                                <tr>
                                    <td><?= $d['nome'] ?></td>
                                    <td>
                                        <a href="?edit_t=departamentos&edit_id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                        <a href="?del_table=departamentos&del_id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir?')">Excluir</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-qrcodes">
            <div class="card p-4">
                <h5 class="fw-bold mb-4">Gerador de Links e QR Codes</h5>
                <div class="row">
                    <?php foreach($departamentos as $d): 
                        $link_compartilhar = $base_url . "/relatorio.php?depto=" . $d['id'];
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="qr-card shadow-sm">
                            <h6 class="fw-bold text-truncate" title="<?= $d['nome'] ?>"><?= $d['nome'] ?></h6>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($link_compartilhar) ?>" alt="QR Code" class="my-3 border p-1">
                            
                            <div class="input-group input-group-sm mt-2">
                                <input type="text" id="link_<?= $d['id'] ?>" class="form-control text-muted" value="<?= $link_compartilhar ?>" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copiarLink('link_<?= $d['id'] ?>')">Copiar</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($departamentos)): ?>
                        <div class="alert alert-info w-100 text-center">Cadastre departamentos primeiro para gerar os QR Codes.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function filterTable(input, tableId) {
    let filter = input.value.toLowerCase();
    let rows = document.getElementById(tableId).getElementsByTagName("tr");
    for (let i = 1; i < rows.length; i++) {
        rows[i].style.display = rows[i].innerText.toLowerCase().includes(filter) ? "" : "none";
    }
}

// Função para o botão "Copiar"
function copiarLink(idInput) {
    var copyText = document.getElementById(idInput);
    copyText.select();
    copyText.setSelectionRange(0, 99999); 
    navigator.clipboard.writeText(copyText.value).then(function() {
        alert("Link copiado com sucesso!");
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>