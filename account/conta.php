<?php
// account/conta.php
require_once __DIR__ . '/../configs/authGuard.php'; // Protege a página
require_once __DIR__ . '/../controller/usuariosDAO.php';

$dao = new UsuarioDAO();
$usuarioId = (int) $_SESSION['usuario_id'];
$usuario = $dao->buscarPorId($usuarioId);

if (!$usuario) {
    session_destroy();
    header('Location: /index.php');
    exit;
}

$mensagemSucesso = '';
$mensagemErro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ==========================================
    // AÇÃO: EXCLUIR CONTA
    // ==========================================
    if ($acao === 'excluir_conta') {
        try {
            $dao->excluir($usuarioId);
            session_destroy();
            header('Location: /index.php?status=conta_excluida');
            exit;
        } catch (Throwable $ex) {
            $mensagemErro = 'Erro ao excluir conta: ' . $ex->getMessage();
        }
    }

    // ==========================================
    // AÇÃO: ATUALIZAR DADOS DA CONTA
    // ==========================================
    elseif ($acao === 'atualizar_conta') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $numeroRegistro = trim($_POST['numero_registro'] ?? '');
        $tipoRegistro = trim($_POST['tipo_registro'] ?? '');
        $novaSenha = (string) ($_POST['senha'] ?? '');

        if (empty($nome) || empty($email)) {
            $mensagemErro = 'Nome e E-mail não podem ficar em branco.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensagemErro = 'E-mail inválido.';
        } else {
            // Checa conflito de nome ou e-mail com OUTROS usuários
            $conflito = $dao->verificarExistenciaNomeOuEmail($nome, $email, $usuarioId);

            if ($conflito !== null) {
                $mensagemErro = $conflito;
            } else {
                $usuario->nome = $nome;
                $usuario->email = $email;
                $usuario->telefone = !empty($telefone) ? $telefone : null;
                $usuario->numeroRegistro = !empty($numeroRegistro) ? $numeroRegistro : null;
                $usuario->tipoRegistro = !empty($tipoRegistro) ? $tipoRegistro : null;

                if (!empty($novaSenha)) {
                    if (strlen($novaSenha) < 8) {
                        $mensagemErro = 'A nova senha deve ter no mínimo 8 caracteres.';
                    } else {
                        $usuario->senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
                    }
                }

                if (empty($mensagemErro)) {
                    $dao->atualizar($usuario);
                    $_SESSION['usuario_nome'] = $usuario->nome;
                    $_SESSION['usuario_email'] = $usuario->email;
                    $mensagemSucesso = 'Dados da conta atualizados com sucesso!';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Minha Conta — Fito Clinic</title>
    <link rel="shortcut icon" href="../img/faviconrhay.png" type="image/x-icon">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="../icons/bootstrap-icons.css" />
    <link rel="stylesheet" href="../css/styles.css" />
    <link rel="stylesheet" href="../css/php.css" />
</head>

<body>
    <div class="app-shell">
        <!-- ============ SIDEBAR (desktop) ============ -->
        <aside class="sidebar" id="sidebar">
            <nav class="nav">
                <div class="brand">
                    <div class="brand-mark" aria-hidden="true">
                        <span class="brand-r"></span>
                    </div>
                    <div>
                        <strong>Fito Clinic</strong>
                        <small>& Estética</small>
                    </div>
                </div>

                <div class="profile-mini">
                    <div class="avatar">RL</div>
                    <div>
                        <strong>Rhayane Lopes</strong>
                        <small>Profissional</small>
                    </div>
                </div>
                <a class="nav-item" href="../index.php"><span class="bi bi-house"></span> Início</a>
                <a class="nav-item" href="#"><span class="bi bi-calendar-event"></span> Agenda</a>
                <a class="nav-item" href="#"><span class="bi bi-people"></span> Pacientes</a>
                <a class="nav-item" href="#"><span class="bi bi-file-medical"></span> Prontuários</a>
                <a class="nav-item" href="#"><span class="bi bi-pencil"></span> Prescrições</a>
                <a class="nav-item" href="#"><span class="bi bi-currency-dollar"></span> Financeiro</a>
                <a class="nav-item" href="#"><span class="bi bi-box"></span> Estoque</a>
                <a class="nav-item" href="#"><span class="bi bi-whatsapp"></span> WhatsApp</a>
                <a class="nav-item active" href="usuariosPage.php">
                    <span class="bi bi-person-badge"></span> Usuários
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="help-card">
                    <span class="help-icon bi bi-heart"></span>
                    <div>
                        <strong>Precisa de ajuda?</strong>
                        <small>Central de suporte</small>
                    </div>
                </div>
                <small class="copyright">Fito Clinic &amp; Estética</small>
            </div>
        </aside>



        <div class="container" style="width: 95%; margin-top: 30px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Minha Conta</h1>
                <a href="../index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar ao
                    Início</a>
            </div>

            <?php if ($mensagemSucesso): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensagemSucesso) ?></div>
            <?php endif; ?>
            <?php if ($mensagemErro): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($mensagemErro) ?></div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <form method="post" action="">
                        <input type="hidden" name="acao" value="atualizar_conta">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nome" class="form-label">Nome Completo *</label>
                                <input type="text" name="nome" id="nome" class="form-control" required
                                    value="<?= htmlspecialchars($usuario->nome) ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">E-mail *</label>
                                <input type="email" name="email" id="email" class="form-control" required
                                    value="<?= htmlspecialchars($usuario->email) ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="tel" name="telefone" id="telefone" class="form-control"
                                    value="<?= htmlspecialchars($usuario->telefone ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="senha" class="form-label">Nova Senha (deixe em branco para não
                                    alterar)</label>
                                <input type="password" name="senha" id="senha" class="form-control" minlength="8">
                            </div>

                            <div class="col-md-6">
                                <label for="tipo_registro" class="form-label">Tipo de Registro</label>
                                <input type="text" name="tipo_registro" id="tipo_registro" class="form-control"
                                    placeholder="Ex: CRM, CRF, CRN"
                                    value="<?= htmlspecialchars($usuario->tipoRegistro ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="numero_registro" class="form-label">Número de Registro</label>
                                <input type="text" name="numero_registro" id="numero_registro" class="form-control"
                                    value="<?= htmlspecialchars($usuario->numeroRegistro ?? '') ?>">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Salvar
                                Alterações</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Área de Exclusão de Conta -->
            <div class="card border-danger">
                <div class="card-body p-4">
                    <h5 class="text-danger"><i class="bi bi-exclamation-triangle"></i> Excluir Conta</h5>
                    <p class="text-muted">A exclusão da sua conta removerá permanentemente seu registro do banco de
                        dados e
                        não poderá ser desfeita.</p>

                    <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                        data-bs-target="#modalExcluirConta">
                        Excluir Minha Conta
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="modalExcluirConta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    Tem certeza de que deseja excluir permanentemente sua conta? Esta ação removerá seus dados do
                    sistema.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="post" action="">
                        <input type="hidden" name="acao" value="excluir_conta">
                        <button type="submit" class="btn btn-danger">Sim, Excluir Conta</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ OFFCANVAS (menu mobile) ============ -->
    <div class="offcanvas offcanvas-start offcanvas-fito" tabindex="-1" id="menuOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">
                <span class="text-fito-green" style="font-family: Georgia, serif;">Fito Clinic</span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="nav">
                <a class="nav-item" href="../index.php"><span class="bi bi-house"></span> Início</a>
                <a class="nav-item" href="#"><span class="bi bi-calendar-event"></span> Agenda</a>
                <a class="nav-item" href="#"><span class="bi bi-people"></span> Pacientes</a>
                <a class="nav-item" href="#"><span class="bi bi-file-medical"></span> Prontuários</a>
                <a class="nav-item" href="#"><span class="bi bi-pencil"></span> Prescrições</a>
                <a class="nav-item" href="#"><span class="bi bi-currency-dollar"></span> Financeiro</a>
                <a class="nav-item" href="#"><span class="bi bi-box"></span> Estoque</a>
                <a class="nav-item" href="#"><span class="bi bi-whatsapp"></span> WhatsApp</a>
                <a class="nav-item active" href="usuariosPage.php">
                    <span class="bi bi-person-badge"></span> Usuários
                </a>
            </nav>
        </div>
    </div>


    <script src="../bootstrap/js/bootstrap.js"></script>
</body>

</html>