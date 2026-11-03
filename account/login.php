<?php
// account/login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controller/usuariosDAO.php';

$mensagemErro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        $mensagemErro = 'Informe o e-mail e a senha.';
    } else {
        $dao = new UsuarioDAO();
        $usuario = $dao->buscarPorEmail($email);

        if ($usuario && password_verify($senha, $usuario->senhaHash)) {
            if (!$usuario->ativo) {
                $mensagemErro = 'Sua conta está inativa. Entre em contato com o administrador.';
            } else {
                $_SESSION['usuario_id'] = $usuario->id;
                $_SESSION['usuario_nome'] = $usuario->nome;
                $_SESSION['usuario_email'] = $usuario->email;

                header('Location: /index.php');
                exit;
            }
        } else {
            $mensagemErro = 'E-mail ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login — Fito Clinic</title>
    <link rel="shortcut icon" href="../media/img/faviconrhay.png" type="image/x-icon">
    <link rel="stylesheet" href="../media/bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="../media/icons/bootstrap-icons.css" />
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
        <div class="container centrom" style="margin-top: 30px; margin-bottom: 30px;">
            <div class="card shadow-sm border-0" style="width: 98%;">
                <div class="card-body p-4">
                    <h2 class="h4 mb-3 text-center">Acessar Conta</h2>

                    <?php if ($mensagemErro): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($mensagemErro) ?></div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" name="email" id="email" class="form-control" required
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" name="senha" id="senha" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Entrar</button>
                    </form>

                    <div class="text-center mt-3">
                        <small>Não possui conta? <a href="cadastro.php">Cadastre-se</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>