<?php
// account/cadastro.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controller/usuariosDAO.php';

$mensagemErro = '';
$mensagemSucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    if (empty($nome) || empty($email) || empty($senha)) {
        $mensagemErro = 'Preencha todos os campos obrigatórios (Nome, E-mail e Senha).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagemErro = 'Insira um e-mail válido.';
    } elseif (strlen($senha) < 8) {
        $mensagemErro = 'A senha deve ter pelo menos 8 caracteres.';
    } else {
        try {
            $dao = new UsuarioDAO();

            // Verifica se existe usuário com o mesmo NOME ou E-MAIL
            $conflito = $dao->verificarExistenciaNomeOuEmail($nome, $email);

            if ($conflito !== null) {
                // Não realiza o INSERT no MySQL
                $mensagemErro = $conflito;
            } else {
                // Instancia novo usuário e grava no MySQL
                $u = new Usuario();
                $u->nome = $nome;
                $u->email = $email;
                $u->senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $u->telefone = !empty($telefone) ? $telefone : null;
                $u->papel = 'profissional';
                $u->ativo = true;

                $dao->inserir($u);

                $mensagemSucesso = 'Cadastro realizado com sucesso! Você já pode fazer login.';
            }
        } catch (Throwable $ex) {
            $mensagemErro = 'Erro ao cadastrar: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Cadastro — Fito Clinic</title>
    <link rel="stylesheet" href="../media/bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="../media/icons/bootstrap-icons.css" />
    <link rel="stylesheet" href="../css/styles.css" />
</head>

<body>
    <div class="app-shell">

        <!-- ============ SIDEBAR ============ -->
        <aside class="sidebar" id="sidebar">
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Fechar menu">
                <span class="bi bi-x-lg"></span>
            </button>

            <a href="/" class="brand">
                <div class="brand-mark" aria-hidden="true">
                    <span class="brand-r"></span>
                </div>
                <div>
                    <strong>Fito Clinic</strong>
                    <small>& Estética</small>
                </div>
            </a>

            <a href="../account/login.php" class="profile-mini" style="text-decoration: none;">
                <div class="avatar"><i class="bi bi-person-circle"></i></div>
                <div>
                    <strong>Conta | Login</strong>
                    <small>Profissional</small>
                </div>
            </a>

            <nav class="nav">
                <a class="nav-item active" href="/"><span class="bi bi-house"></span> Início</a>
                <a class="nav-item" href="../main.php"><span class="bi bi-bar-chart-steps"></span> Painel</a>
                <a class="nav-item" href="../pages/agendamentosPage.php"><span class="bi bi-calendar-event"></span> Agenda</a>
                <a class="nav-item" href="../pages/pacientesPage.php"><span class="bi bi-people"></span> Pacientes</a>
                <a class="nav-item" href="../pages/prontuariosPage.php"><span class="bi bi-file-medical"></span> Prontuários</a>
                <a class="nav-item" href="../pages/prescricoesPage.php"><span class="bi bi-pencil-square"></span> Prescrições</a>
                <a class="nav-item" href="../pages/itens_prescricaoPage.php"><span class="bi bi-list-check"></span> Itens de Prescrição</a>
                <a class="nav-item" href="../pages/transacoes_financeirasPage.php"><span class="bi bi-currency-dollar"></span> Financeiro</a>
                <a class="nav-item" href="../pages/itens_estoquePage.php"><span class="bi bi-box-seam"></span> Estoque</a>
                <a class="nav-item" href="../pages/comunicacoesPage.php"><span class="bi bi-whatsapp"></span> Comunicações</a>
                <a class="nav-item" href="../pages/documentosPage.php"><span class="bi bi-file-earmark-text"></span> Documentos</a>
                <a class="nav-item" href="../pages/usuariosPage.php"><span class="bi bi-person-badge"></span> Usuários</a>
            </nav>

            <div class="sidebar-footer">
                <div class="help-card">
                    <span class="help-icon bi bi-heart"></span>
                    <div>
                        <strong>Precisa de ajuda?</strong>
                        <small>Central de suporte</small>
                    </div>
                </div>
                <small class="copyright">Fito Clinic & Estética</small>
            </div>
        </aside>
        <div class="container centrom" style="margin-top: 30px; margin-bottom: 30px;">
            <div class="card shadow-sm border-0" style="width: 98%;">
                <div class="card-body p-4">
                    <h2 class="h4 mb-3 text-center">Criar Conta</h2>

                    <?php if ($mensagemErro): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($mensagemErro) ?></div>
                    <?php endif; ?>

                    <?php if ($mensagemSucesso): ?>
                        <div class="alert alert-success" role="alert">
                            <?= htmlspecialchars($mensagemSucesso) ?>
                            <div class="mt-2"><a href="login.php" class="btn btn-sm btn-success">Ir para o Login</a></div>
                        </div>
                    <?php else: ?>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo *</label>
                                <input type="text" name="nome" id="nome" class="form-control" required
                                    value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail *</label>
                                <input type="email" name="email" id="email" class="form-control" required
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha *</label>
                                <input type="password" name="senha" id="senha" class="form-control" minlength="8" required>
                            </div>
                            <div class="mb-3">
                                <label for="telefone" class="form-label">Telefone (Opcional)</label>
                                <input type="tel" name="telefone" id="telefone" class="form-control"
                                    value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Cadastrar</button>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <small>Já possui conta? <a href="login.php">Faça login</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>