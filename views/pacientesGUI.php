<?php
// require_once __DIR__ . '/../configs/authGuard.php'; // Bloqueia e redireciona se não estiver logado

// Restante do código da página...

// views/pacientesGUI.php

/**
 * @param Paciente[]   $pacientes
 * @param array        $mensagens
 * @param Paciente|null $pacienteEdicao
 * @param string       $busca          termo de busca ativo (pode ser '')
 */
function renderPacienteGUI(
    array $pacientes,
    array $mensagens = [],
    ?Paciente $pacienteEdicao = null,
    string $busca = ''
): void {
    $escapar = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

    $isEdicao = $pacienteEdicao !== null;

    $val = static function (string $campo) use ($isEdicao, $pacienteEdicao, $escapar) {
        if (!$isEdicao) return '';
        return $escapar($pacienteEdicao->$campo ?? '');
    };
    $selGenero = static function (string $gen) use ($isEdicao, $pacienteEdicao) {
        if (!$isEdicao) return $gen === 'nao_informado' ? 'selected' : '';
        return $pacienteEdicao->genero === $gen ? 'selected' : '';
    };
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title><?= $isEdicao ? 'Editar' : 'Novo' ?> paciente — Fito Clinic</title>
        <link rel="shortcut icon" href="../media/img/faviconrhay.png" type="image/x-icon">
        <link rel="stylesheet" href="../media/bootstrap/css/bootstrap.css" />
        <link rel="stylesheet" href="../media/icons/bootstrap-icons.css" />
        <link rel="stylesheet" href="../css/styles.css" />
        <link rel="stylesheet" href="../css/php.css" />
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

            <!-- ============ CONTEÚDO ============ -->
            <main class="main">

                <header class="topbar">
                    <button class="menu-btn" type="button" id="menuBtn" aria-label="Abrir menu">
                        <span class="bi bi-list"></span>
                    </button>

                    <div>
                        <span class="eyebrow">CADASTRO</span>
                        <h1><?= $isEdicao ? 'Editar paciente' : 'Gestão de Pacientes' ?></h1>
                    </div>

                    <a href="../account/conta.php" class="top-actions" style="text-decoration: none;">
                        <button class="icon-btn" title="Notificações">
                            <span class="bi bi-bell"></span>
                        </button>
                        <button class="profile-btn">
                            <span class="avatar small"><i class="bi bi-person-circle"></i></span>
                            <span>Conta | Login</span>
                            <span class="bi bi-chevron-down"></span>
                        </button>
                    </a>
                </header>

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-fito">
                        <li class="breadcrumb-item"><a href="../index.php">Início</a></li>
                        <li class="breadcrumb-item">
                            <a href="pacientesPage.php">Pacientes</a>
                        </li>
                        <?php if ($isEdicao): ?>
                            <li class="breadcrumb-item active" aria-current="page">
                                #<?= (int) $pacienteEdicao->id ?>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item active" aria-current="page">Novo</li>
                        <?php endif; ?>
                    </ol>
                </nav>

                <?php foreach ($mensagens as $m): ?>
                    <div class="alert-fito alert-<?= $escapar($m['tipo']) ?> mb-3" role="alert">
                        <i class="bi <?= $m['tipo'] === 'sucesso'
                                            ? 'bi-check-circle-fill'
                                            : 'bi-exclamation-triangle-fill' ?>"></i>
                        <span><?= $escapar($m['texto']) ?></span>
                    </div>
                <?php endforeach; ?>

                <div class="page-header">
                    <div>
                        <h1><?= $isEdicao ? 'Editando paciente #' . (int) $pacienteEdicao->id : 'Pacientes do sistema' ?></h1>
                        <span class="subtitle">
                            <?= $isEdicao
                                ? 'Altere os dados e clique em Salvar.'
                                : 'Cadastre e gerencie os pacientes da clínica.' ?>
                        </span>
                    </div>

                    <?php if ($isEdicao): ?>
                        <a href="pacientesPage.php" class="btn-fito-outline">
                            <i class="bi bi-x-lg"></i> Cancelar edição
                        </a>
                    <?php else: ?>
                        <button class="btn-fito" type="button" data-bs-toggle="collapse" data-bs-target="#formNovoPaciente"
                            aria-expanded="true" aria-controls="formNovoPaciente">
                            <i class="bi bi-plus-lg"></i> Novo paciente
                        </button>
                    <?php endif; ?>
                </div>

                <!-- ============ FORMULÁRIO ============ -->
                <div class="collapse show" id="formNovoPaciente">
                    <div class="card card-fito mb-4 <?= $isEdicao ? 'border-warning' : '' ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <span class="eyebrow gold"><?= $isEdicao ? 'EDIÇÃO' : 'CADASTRO' ?></span>
                                <h2 class="m-0">
                                    <?= $isEdicao ? 'Alterar dados do paciente' : 'Dados do paciente' ?>
                                </h2>
                            </div>
                            <?php if ($isEdicao): ?>
                                <span class="badge rounded-pill badge-fito-admin">
                                    <i class="bi bi-pencil"></i> Modo edição
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body">
                            <form method="post" action="" class="form-fito" id="formPaciente">
                                <?php if ($isEdicao): ?>
                                    <input type="hidden" name="acao" value="atualizar">
                                    <input type="hidden" name="id" value="<?= (int) $pacienteEdicao->id ?>">
                                <?php else: ?>
                                    <input type="hidden" name="acao" value="criar">
                                <?php endif; ?>

                                <div class="row g-3">
                                    <!-- Dados pessoais -->
                                    <div class="col-12">
                                        <label for="nome" class="form-label">Nome completo *</label>
                                        <input type="text" id="nome" name="nome" class="form-control"
                                            value="<?= $val('nome') ?>" maxlength="150"
                                            <?= $isEdicao ? 'autofocus' : '' ?> required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="nome_social" class="form-label">Nome social</label>
                                        <input type="text" id="nome_social" name="nome_social" class="form-control"
                                            value="<?= $val('nomeSocial') ?>" maxlength="150">
                                        <div class="form-text">Como o paciente prefere ser chamado(a).</div>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="cpf" class="form-label">CPF</label>
                                        <input type="text" id="cpf" name="cpf" class="form-control"
                                            value="<?= $val('cpf') ?>" maxlength="14"
                                            placeholder="000.000.000-00" inputmode="numeric">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="rg" class="form-label">RG</label>
                                        <input type="text" id="rg" name="rg" class="form-control"
                                            value="<?= $val('rg') ?>" maxlength="30">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="data_nascimento" class="form-label">Data de nascimento</label>
                                        <input type="date" id="data_nascimento" name="data_nascimento"
                                            class="form-control" value="<?= $val('dataNascimento') ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="genero" class="form-label">Gênero</label>
                                        <select id="genero" name="genero" class="form-select">
                                            <option value="nao_informado" <?= $selGenero('nao_informado') ?>>Prefiro não informar</option>
                                            <option value="masculino" <?= $selGenero('masculino') ?>>Masculino</option>
                                            <option value="feminino" <?= $selGenero('feminino') ?>>Feminino</option>
                                            <option value="outro" <?= $selGenero('outro') ?>>Outro</option>
                                        </select>
                                    </div>

                                    <!-- Contato -->
                                    <div class="col-md-4">
                                        <label for="telefone" class="form-label">Telefone</label>
                                        <input type="tel" id="telefone" name="telefone" class="form-control"
                                            value="<?= $val('telefone') ?>" maxlength="30">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="whatsapp" class="form-label">WhatsApp</label>
                                        <input type="tel" id="whatsapp" name="whatsapp" class="form-control"
                                            value="<?= $val('whatsapp') ?>" maxlength="30">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="email" class="form-label">E-mail</label>
                                        <input type="email" id="email" name="email" class="form-control"
                                            value="<?= $val('email') ?>" maxlength="150">
                                    </div>

                                    <!-- Endereço -->
                                    <div class="col-12">
                                        <label for="endereco" class="form-label">Endereço</label>
                                        <input type="text" id="endereco" name="endereco" class="form-control"
                                            value="<?= $val('endereco') ?>" maxlength="255"
                                            placeholder="Rua, número, complemento, bairro">
                                    </div>

                                    <div class="col-md-5">
                                        <label for="cidade" class="form-label">Cidade</label>
                                        <input type="text" id="cidade" name="cidade" class="form-control"
                                            value="<?= $val('cidade') ?>" maxlength="100">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="estado" class="form-label">Estado (UF)</label>
                                        <input type="text" id="estado" name="estado" class="form-control"
                                            value="<?= $val('estado') ?>" maxlength="2"
                                            placeholder="Ex.: SP" style="text-transform: uppercase;">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="cep" class="form-label">CEP</label>
                                        <input type="text" id="cep" name="cep" class="form-control"
                                            value="<?= $val('cep') ?>" maxlength="10"
                                            placeholder="00000-000" inputmode="numeric">
                                    </div>

                                    <!-- Emergência -->
                                    <div class="col-md-6">
                                        <label for="contato_emergencia" class="form-label">Contato de emergência</label>
                                        <input type="text" id="contato_emergencia" name="contato_emergencia"
                                            class="form-control" value="<?= $val('contatoEmergencia') ?>" maxlength="150">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="telefone_emergencia" class="form-label">Telefone de emergência</label>
                                        <input type="tel" id="telefone_emergencia" name="telefone_emergencia"
                                            class="form-control" value="<?= $val('telefoneEmergencia') ?>" maxlength="30">
                                    </div>

                                    <!-- Clínico -->
                                    <div class="col-12">
                                        <label for="alergias" class="form-label">Alergias</label>
                                        <textarea id="alergias" name="alergias" class="form-control" rows="2"
                                            maxlength="2000"
                                            placeholder="Medicamentos, alimentos, látex, etc."><?= $val('alergias') ?></textarea>
                                    </div>

                                    <div class="col-12">
                                        <label for="observacoes" class="form-label">Observações</label>
                                        <textarea id="observacoes" name="observacoes" class="form-control" rows="3"
                                            maxlength="5000"><?= $val('observacoes') ?></textarea>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <?php if ($isEdicao): ?>
                                        <a href="pacientesPage.php" class="btn-fito-outline">
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

                <!-- ============ LISTA + BUSCA ============ -->
                <div class="card card-fito">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="eyebrow gold"><?= $busca !== '' ? 'RESULTADOS' : 'LISTAGEM' ?></span>
                                <h2 class="m-0">
                                    <?= $busca !== ''
                                        ? 'Pacientes encontrados'
                                        : 'Pacientes cadastrados' ?>
                                </h2>
                            </div>
                            <span class="badge rounded-pill badge-fito-prof">
                                <?= count($pacientes) ?> <?= count($pacientes) === 1 ? 'registro' : 'registros' ?>
                            </span>
                        </div>

                        <form method="get" action="pacientesPage.php" class="d-flex gap-2 align-items-center" role="search">
                            <div class="flex-grow-1">
                                <input type="text" name="busca" value="<?= $escapar($busca) ?>"
                                    class="form-control form-control-sm" maxlength="150"
                                    placeholder="Buscar por nome…" aria-label="Buscar paciente por nome"
                                    autocomplete="off">
                            </div>
                            <button type="submit" class="btn-fito">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <?php if ($busca !== ''): ?>
                                <a href="pacientesPage.php" class="btn-fito-outline">
                                    <i class="bi bi-x-lg"></i> Limpar
                                </a>
                            <?php endif; ?>
                        </form>

                        <?php if ($busca !== ''): ?>
                            <small class="text-fito-muted d-block mt-2">
                                Exibindo resultados para
                                <strong>"<?= $escapar($busca) ?>"</strong>.
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="card-body p-0">
                        <?php if (empty($pacientes)): ?>
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <?php if ($busca !== ''): ?>
                                    Nenhum paciente encontrado para
                                    <strong>"<?= $escapar($busca) ?>"</strong>.
                                <?php else: ?>
                                    Nenhum paciente cadastrado ainda.
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-fito align-middle">
                                    <thead>
                                        <tr>
                                            <th scope="col">ID</th>
                                            <th scope="col">Nome</th>
                                            <th scope="col">CPF</th>
                                            <th scope="col">Nascimento</th>
                                            <th scope="col">Contato</th>
                                            <th scope="col">Cidade / UF</th>
                                            <th scope="col">Alergias</th>
                                            <th scope="col" class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pacientes as $p): ?>
                                            <?php
                                            $linhaEdicao = $isEdicao && $pacienteEdicao->id === $p->id;

                                            // Contato principal
                                            $contatoPrincipal = $p->whatsapp ?? $p->telefone;
                                            $titleContato = [];
                                            if ($p->whatsapp) $titleContato[] = 'WhatsApp: ' . $p->whatsapp;
                                            if ($p->telefone) $titleContato[] = 'Telefone: ' . $p->telefone;
                                            $titleContatoStr = implode(' | ', $titleContato);

                                            // Cidade / UF
                                            $local = trim(($p->cidade ?? '') . ($p->estado ? ' / ' . $p->estado : ''));

                                            // Link de edição preservando a busca
                                            $linkEditar = '?editar=' . (int) $p->id
                                                . ($busca !== '' ? '&busca=' . urlencode($busca) : '');
                                            ?>
                                            <tr class="<?= $linhaEdicao ? 'table-warning' : '' ?>">
                                                <td class="text-fito-muted">#<?= (int) $p->id ?></td>
                                                <td>
                                                    <strong><?= $escapar($p->nome) ?></strong>
                                                    <?php if ($p->nomeSocial !== null && $p->nomeSocial !== '' && $p->nomeSocial !== $p->nome): ?>
                                                        <br>
                                                        <small class="text-fito-muted">
                                                            Como prefere ser chamado(a): <?= $escapar($p->nomeSocial) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $escapar($p->cpf ?? '—') ?></td>
                                                <td>
                                                    <?php if ($p->dataNascimento !== null): ?>
                                                        <?= $escapar($p->dataNascimentoBR()) ?>
                                                        <?php $idade = $p->idade(); ?>
                                                        <?php if ($idade !== null): ?>
                                                            <br><small class="text-fito-muted"><?= (int) $idade ?> anos</small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-fito-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td title="<?= $escapar($titleContatoStr) ?>">
                                                    <?= $escapar($contatoPrincipal ?? '—') ?>
                                                </td>
                                                <td><?= $escapar($local !== '' ? $local : '—') ?></td>
                                                <td>
                                                    <?php if (!empty($p->alergias)): ?>
                                                        <span class="badge rounded-pill badge-fito-inativo"
                                                            title="<?= $escapar($p->alergias) ?>">
                                                            <i class="bi bi-exclamation-triangle"></i> Sim
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-fito-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <a href="<?= $escapar($linkEditar) ?>"
                                                        class="btn btn-warning margem-vertical" title="Editar paciente">
                                                        <i class="bi bi-pen"></i> Editar
                                                    </a>

                                                    <button type="button" class="btn btn-danger margem-vertical"
                                                        data-bs-toggle="modal" data-bs-target="#modalExcluirPaciente"
                                                        data-paciente-id="<?= (int) $p->id ?>"
                                                        data-paciente-nome="<?= $escapar($p->nome) ?>">
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
        <div class="modal fade" id="modalExcluirPaciente" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content card-fito border-0">
                    <div class="modal-header border-0 pb-0">
                        <span class="eyebrow gold">CONFIRMAÇÃO</span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <h2 class="mb-3">Excluir paciente</h2>
                        <p class="text-fito-muted mb-0">
                            Tem certeza que deseja excluir
                            <strong id="nomePacienteExcluir">este paciente</strong>?
                            Esta ação não pode ser desfeita.
                        </p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <form method="post" action="" id="formExcluirPaciente">
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" id="idPacienteExcluir" value="">
                            <button type="button" class="btn btn-fito-outline" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Excluir
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script src="../media/bootstrap/js/bootstrap.js"></script>
        <script src="../js/app.js"></script>
        <script>
            document.getElementById('modalExcluirPaciente').addEventListener('show.bs.modal', function(event) {
                const botao = event.relatedTarget;
                document.getElementById('idPacienteExcluir').value = botao.getAttribute('data-paciente-id');
                document.getElementById('nomePacienteExcluir').textContent = botao.getAttribute('data-paciente-nome');
            });

            <?php if ($isEdicao): ?>
                document.getElementById('formPaciente').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            <?php endif; ?>
        </script>
    </body>

    </html>
<?php
}
