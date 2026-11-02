<?php
// views/usuariosGUI.php

/**
 * @param Usuario[]    $usuarios
 * @param array        $mensagens      ['tipo' => 'sucesso'|'erro', 'texto' => '...']
 * @param Usuario|null $usuarioEdicao  Se != null, o form entra em modo de edição.
 */
function renderUsuarioGUI(array $usuarios, array $mensagens = [], ?Usuario $usuarioEdicao = null): void
{
    $escapar = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

    $isEdicao = $usuarioEdicao !== null;

    // Helpers de preenchimento do formulário
    $val = static function (string $campo) use ($isEdicao, $usuarioEdicao, $escapar) {
        if (!$isEdicao)
            return '';
        return $escapar($usuarioEdicao->$campo ?? '');
    };
    $selPapel = static function (string $papel) use ($isEdicao, $usuarioEdicao) {
        return ($isEdicao && $usuarioEdicao->papel === $papel) ? 'selected' : '';
    };
    $chkAtivo = (!$isEdicao || $usuarioEdicao->ativo) ? 'checked' : '';
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title><?= $isEdicao ? 'Editar' : 'Novo' ?> usuário — Fito Clinic</title>
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
                <!-- Botão de fechar: visível apenas no mobile -->
                <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Fechar menu">
                    <span class="bi bi-x-lg"></span>
                </button>

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

                <nav class="nav">
                    <a class="nav-item active" href="/#inicio"><span class="bi bi-house"></span> Início</a>
                    <a class="nav-item" href="#agenda"><span class="bi bi-calendar-event"></span> Agenda</a>
                    <a class="nav-item" href="../pages/pacientesPage.php"><span class="bi bi-people"></span> Pacientes</a>
                    <a class="nav-item" href="#prontuarios"><span class="bi bi-file-medical"></span> Prontuários</a>
                    <a class="nav-item" href="#prescricoes"><span class="bi bi-pencil"></span> Prescrições</a>
                    <a class="nav-item" href="#financeiro"><span class="bi bi-currency-dollar"></span> Financeiro</a>
                    <a class="nav-item" href="#estoque"><span class="bi bi-box"></span> Estoque</a>
                    <a class="nav-item" href="#mensagens"><span class="bi bi-whatsapp"></span> WhatsApp</a>
                    <a class="nav-item" href="#configuracoes"><span class="bi bi-gear"></span> Configurações</a>
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



            <!-- ============ CONTEÚDO ============ -->
            <main class="main">

                <!-- Topbar -->
                <header class="topbar">
                    <button class="menu-btn" type="button" id="menuBtn" aria-label="Abrir menu">
                        <span class="bi bi-list"></span>
                    </button>

                    <div>
                        <span class="eyebrow">CONFIGURAÇÕES</span>
                        <h1><?= $isEdicao ? 'Editar usuário' : 'Gestão de Usuários' ?></h1>
                    </div>

                    <div class="top-actions">
                        <button class="icon-btn" title="Notificações">
                            <span class="bi bi-bell"></span>
                        </button>
                        <button class="profile-btn">
                            <span class="avatar small">RL</span>
                            <span>Rhayane Lopes</span>
                            <span class="bi bi-chevron-down"></span>
                        </button>
                    </div>
                </header>

                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-fito">
                        <li class="breadcrumb-item"><a href="../index.php">Início</a></li>
                        <li class="breadcrumb-item"><a href="../main.php">Configurações</a></li>
                        <li class="breadcrumb-item">
                            <a href="usuariosPage.php">Usuários</a>
                        </li>
                        <?php if ($isEdicao): ?>
                            <li class="breadcrumb-item active" aria-current="page">
                                #<?= (int) $usuarioEdicao->id ?>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item active" aria-current="page">User</li>
                        <?php endif; ?>
                    </ol>
                </nav>

                <!-- Mensagens -->
                <?php foreach ($mensagens as $m): ?>
                    <div class="alert-fito alert-<?= $escapar($m['tipo']) ?> mb-3" role="alert">
                        <i class="bi <?= $m['tipo'] === 'sucesso'
                                            ? 'bi-check-circle-fill'
                                            : 'bi-exclamation-triangle-fill' ?>"></i>
                        <span><?= $escapar($m['texto']) ?></span>
                    </div>
                <?php endforeach; ?>

                <!-- Cabeçalho da página -->
                <div class="page-header">
                    <div>
                        <h1><?= $isEdicao ? 'Editando usuário #' . (int) $usuarioEdicao->id : 'Usuários do sistema' ?></h1>
                        <span class="subtitle">
                            <?= $isEdicao
                                ? 'Altere os dados e clique em Salvar. Deixe a senha em branco para mantê-la.'
                                : 'Cadastre administradores, profissionais e recepcionistas.' ?>
                        </span>
                    </div>

                    <?php if ($isEdicao): ?>
                        <a href="usuariosPage.php" class="btn-fito-outline">
                            <i class="bi bi-x-lg"></i> Cancelar edição
                        </a>
                    <?php else: ?>
                        <button class="btn-fito" type="button" data-bs-toggle="collapse" data-bs-target="#formNovoUsuario"
                            aria-expanded="true" aria-controls="formNovoUsuario">
                            <i class="bi bi-plus-lg"></i> Novo usuário
                        </button>
                    <?php endif; ?>
                </div>

                <!-- ============ FORMULÁRIO (criar/editar) ============ -->
                <div class="collapse show" id="formNovoUsuario">
                    <div class="card card-fito mb-4 <?= $isEdicao ? 'border-warning' : '' ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <span class="eyebrow gold"><?= $isEdicao ? 'EDIÇÃO' : 'CADASTRO' ?></span>
                                <h2 class="m-0">
                                    <?= $isEdicao ? 'Alterar dados do usuário' : 'Dados do usuário' ?>
                                </h2>
                            </div>
                            <?php if ($isEdicao): ?>
                                <span class="badge rounded-pill badge-fito-admin">
                                    <i class="bi bi-pencil"></i> Modo edição
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body">
                            <form method="post" action="" class="form-fito" id="formUsuario">
                                <?php if ($isEdicao): ?>
                                    <input type="hidden" name="acao" value="atualizar">
                                    <input type="hidden" name="id" value="<?= (int) $usuarioEdicao->id ?>">
                                <?php else: ?>
                                    <input type="hidden" name="acao" value="criar">
                                <?php endif; ?>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="nome" class="form-label">Nome completo *</label>
                                        <input type="text" id="nome" name="nome" class="form-control"
                                            value="<?= $val('nome') ?>" maxlength="150" <?= $isEdicao ? 'autofocus' : '' ?>
                                            required>

                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label">E-mail *</label>
                                        <input type="email" id="email" name="email" class="form-control"
                                            value="<?= $val('email') ?>" required maxlength="150">
                                        <div class="form-text">De preferência ao institucional!</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="senha" class="form-label">
                                            <?= $isEdicao ? 'Nova senha (opcional)' : 'Senha *' ?>
                                        </label>
                                        <input type="password" id="senha" name="senha" <?= $isEdicao ? 'minlength="8" placeholder="Deixe em branco para manter a atual"' : 'required minlength="8"' ?>
                                            class="form-control">
                                        <div class="form-text">
                                            <?= $isEdicao
                                                ? 'Só preencha se quiser trocar a senha.'
                                                : 'Mínimo de 8 caracteres.' ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="papel" class="form-label">Papel *</label>
                                        <select id="papel" name="papel" class="form-select" required>
                                            <option value="profissional" <?= $selPapel('profissional') ?>>Profissional
                                            </option>
                                            <option value="admin" <?= $selPapel('admin') ?>>Administrador</option>
                                            <option value="recepcionista" <?= $selPapel('recepcionista') ?>>Recepcionista
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="numero_registro" class="form-label">Número de registro</label>
                                        <input type="text" id="numero_registro" name="numero_registro" class="form-control"
                                            value="<?= $val('numeroRegistro') ?>" maxlength="50">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="tipo_registro" class="form-label">Tipo de registro</label>
                                        <input type="text" id="tipo_registro" name="tipo_registro" class="form-control"
                                            value="<?= $val('tipoRegistro') ?>" maxlength="50"
                                            placeholder="CRM, CRO, CRN...">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="telefone" class="form-label">Telefone</label>
                                        <input type="tel" id="telefone" name="telefone" class="form-control"
                                            value="<?= $val('telefone') ?>" maxlength="30">
                                    </div>

                                    <div class="col-md-12 d-flex align-items-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo"
                                                value="1" <?= $chkAtivo ?>>
                                            <label class="form-check-label" for="ativo">
                                                Usuário ativo
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <?php if ($isEdicao): ?>
                                        <a href="usuariosPage.php" class="btn-fito-outline">
                                            <i class="bi bi-x-lg"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn-fito">
                                            <i class="bi bi-save"></i> Salvar alterações
                                        </button>
                                    <?php else: ?>
                                        <button type="reset" class="btn-fito-outline">Limpar</button>
                                        <button type="submit" class="btn-fito">
                                            <i class="bi bi-check-lg"></i> Cadastrar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ============ LISTA ============ -->
                <div class="card card-fito">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <span class="eyebrow gold">LISTAGEM</span>
                            <h2 class="m-0">Usuários cadastrados</h2>
                        </div>
                        <span class="badge rounded-pill badge-fito-prof">
                            <?= count($usuarios) ?> registros
                        </span>
                    </div>

                    <div class="card-body p-0">
                        <?php if (empty($usuarios)): ?>
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                Nenhum usuário cadastrado ainda.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-fito align-middle">
                                    <thead>
                                        <tr>
                                            <th scope="col">ID</th>
                                            <th scope="col">Nome</th>
                                            <th scope="col">E-mail</th>
                                            <th scope="col">Papel</th>
                                            <th scope="col">Registro</th>
                                            <th scope="col">Telefone</th>
                                            <th scope="col">Status</th>
                                            <th scope="col" class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $u): ?>
                                            <?php
                                            $badgePapel = match ($u->papel) {
                                                'admin' => 'badge-fito-admin',
                                                'profissional' => 'badge-fito-prof',
                                                'recepcionista' => 'badge-fito-recep',
                                                default => 'badge-fito-prof',
                                            };
                                            $badgeStatus = $u->ativo
                                                ? 'badge-fito-ativo'
                                                : 'badge-fito-inativo';
                                            $registro = trim(($u->tipoRegistro ?? '') . ' ' . ($u->numeroRegistro ?? ''));

                                            // A linha do usuário em edição ganha destaque
                                            $linhaEdicao = $isEdicao && $usuarioEdicao->id === $u->id;
                                            ?>
                                            <tr class="<?= $linhaEdicao ? 'table-warning' : '' ?>">
                                                <td class="text-fito-muted">#<?= (int) $u->id ?></td>
                                                <td><strong><?= $escapar($u->nome) ?></strong></td>
                                                <td class="text-fito-muted"><?= $escapar($u->email) ?></td>
                                                <td>
                                                    <span class="badge rounded-pill <?= $escapar($badgePapel) ?>">
                                                        <?= $escapar(ucfirst($u->papel)) ?>
                                                    </span>
                                                </td>
                                                <td><?= $escapar($registro !== '' ? $registro : '—') ?></td>
                                                <td><?= $escapar($u->telefone ?? '—') ?></td>
                                                <td>
                                                    <span class="badge rounded-pill <?= $escapar($badgeStatus) ?>">
                                                        <?= $u->ativo ? 'Ativo' : 'Inativo' ?>
                                                    </span>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <!-- Editar: navega via GET para buscarPorId() -->
                                                    <a href="?editar=<?= (int) $u->id ?>" class="btn btn-warning margem-vertical"
                                                        title="Editar usuário">
                                                        <i class="bi bi-pen"></i> Editar
                                                    </a>

                                                    <button type="button" class="btn btn-danger margem-vertical"
                                                        data-bs-toggle="modal" data-bs-target="#modalExcluir"
                                                        data-usuario-id="<?= (int) $u->id ?>"
                                                        data-usuario-nome="<?= $escapar($u->nome) ?>">
                                                        <i class="bi bi-trash"></i> Excluir
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <footer class="footer">
                    <span>Fito Clinic &amp; Estética</span>
                    <span>Sistema de gestão clínica</span>
                </footer>
            </main>
        </div>



        <!-- ============ MODAL DE EXCLUSÃO ============ -->
        <div class="modal fade" id="modalExcluir" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content card-fito border-0">
                    <div class="modal-header border-0 pb-0">
                        <span class="eyebrow gold">CONFIRMAÇÃO</span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <h2 class="mb-3">Excluir usuário</h2>
                        <p class="text-fito-muted mb-0">
                            Tem certeza que deseja excluir
                            <strong id="nomeUsuarioExcluir">este usuário</strong>?
                            Esta ação não pode ser desfeita.
                        </p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <form method="post" action="" id="formExcluirUsuario">
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" id="idUsuarioExcluir" value="">
                            <button type="button" class="btn btn-fito-outline" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Excluir
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script src="../bootstrap/js/bootstrap.js"></script>
        <script src="../js/app.js"></script>
        <script>
            // Popula o modal de exclusão com dados do botão clicado
            document.getElementById('modalExcluir').addEventListener('show.bs.modal', function(event) {
                const botao = event.relatedTarget;
                document.getElementById('idUsuarioExcluir').value = botao.getAttribute('data-usuario-id');
                document.getElementById('nomeUsuarioExcluir').textContent = botao.getAttribute('data-usuario-nome');
            });

            // Em modo edição, rola suavemente até o formulário e destaca o card
            <?php if ($isEdicao): ?>
                document.getElementById('formUsuario').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            <?php endif; ?>
        </script>
    </body>

    </html>
<?php
}
