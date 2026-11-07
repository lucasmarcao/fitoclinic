<?php
// views/agendamentosGUI.php

/**
 * Renderiza apenas as <tr> da tabela de agendamentos.
 * Usada tanto pela página principal quanto pelo endpoint AJAX.
 *
 * @param Agendamento[] $agendamentos
 */
function renderAgendamentoTableRows(
    array $agendamentos,
    ?int $idEdicao = null,
    string $busca = '',
    string $situacaoFiltro = '',
    string $dataFiltro = ''
): void {
    $escapar = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

    if (empty($agendamentos)) {
        $colspan = 8;
?>
        <tr>
            <td colspan="<?= $colspan ?>" class="text-center py-5">
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <?php if ($busca !== '' || $situacaoFiltro !== '' || $dataFiltro !== ''): ?>
                        Nenhum agendamento encontrado com os filtros aplicados.
                    <?php else: ?>
                        Nenhum agendamento cadastrado ainda.
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php
        return;
    }

    foreach ($agendamentos as $a):
        $isHoje   = $a->isHoje();
        $isEdit   = $idEdicao !== null && $idEdicao === $a->id;
        $rowClass = trim(($isEdit ? 'table-warning' : '') . ' ' . ($isHoje && !$isEdit ? 'row-hoje' : ''));

        $linkEditar = '?editar=' . (int) $a->id;
        if ($busca !== '')           $linkEditar .= '&busca=' . urlencode($busca);
        if ($situacaoFiltro !== '')  $linkEditar .= '&situacao=' . urlencode($situacaoFiltro);
        if ($dataFiltro !== '')      $linkEditar .= '&data=' . urlencode($dataFiltro);
    ?>
        <tr class="<?= $rowClass ?>">
            <td class="text-fito-muted">#<?= (int) $a->id ?></td>
            <td>
                <strong><?= $escapar($a->pacienteNome ?? '—') ?></strong>
            </td>
            <td><?= $escapar($a->profissionalNome ?? '—') ?></td>
            <td>
                <?= $escapar($a->dataBRComDia()) ?>
                <br><small class="text-fito-muted"><?= $escapar($a->horaRange()) ?></small>
            </td>
            <td>
                <span class="badge rounded-pill badge-fito-prof">
                    <?= $escapar($a->tipoLabel()) ?>
                </span>
            </td>
            <td>
                <span class="badge rounded-pill <?= $escapar($a->situacaoBadgeClass()) ?>">
                    <?= $escapar($a->situacaoLabel()) ?>
                </span>
            </td>
            <td>
                <?php if ($a->teleconsulta): ?>
                    <span class="badge rounded-pill badge-teleconsulta"
                        title="Teleconsulta: <?= $escapar($a->urlReuniao ?? '') ?>">
                        <i class="bi bi-camera-video"></i> Sim
                    </span>
                <?php else: ?>
                    <span class="text-fito-muted">—</span>
                <?php endif; ?>
            </td>
            <td class="text-end text-nowrap">
                <a href="<?= $escapar($linkEditar) ?>"
                    class="btn btn-warning margem-vertical" title="Editar agendamento">
                    <i class="bi bi-pen"></i> Editar
                </a>
                <button type="button" class="btn btn-danger margem-vertical"
                    data-bs-toggle="modal" data-bs-target="#modalExcluirAgendamento"
                    data-agendamento-id="<?= (int) $a->id ?>"
                    data-agendamento-resumo="<?= $escapar(($a->pacienteNome ?? '#' . $a->pacienteId) . ' — ' . $a->dataBR() . ' ' . $a->horaRange()) ?>">
                    <i class="bi bi-trash"></i> Excluir
                </button>
            </td>
        </tr>
    <?php
    endforeach;
}

/**
 * Renderiza a página completa.
 *
 * @param Agendamento[] $agendamentos
 * @param array         $mensagens
 * @param Agendamento|null $agendamentoEdicao
 * @param string        $busca
 * @param string        $situacaoFiltro
 * @param string        $dataFiltro
 * @param array         $pacientes       lista de ['id'=>, 'nome'=>]
 * @param array         $profissionais   lista de ['id'=>, 'nome'=>]
 */
function renderAgendamentoGUI(
    array $agendamentos,
    array $mensagens = [],
    ?Agendamento $agendamentoEdicao = null,
    string $busca = '',
    string $situacaoFiltro = '',
    string $dataFiltro = '',
    array $pacientes = [],
    array $profissionais = []
): void {
    $escapar = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

    $isEdicao = $agendamentoEdicao !== null;

    $val = static function (string $campo) use ($isEdicao, $agendamentoEdicao, $escapar) {
        if (!$isEdicao) return '';
        return $escapar($agendamentoEdicao->$campo ?? '');
    };
    $selOpt = static function (string $campo, string $valor) use ($isEdicao, $agendamentoEdicao) {
        if (!$isEdicao) return '';
        return $agendamentoEdicao->$campo === $valor ? 'selected' : '';
    };
    $selectedPaciente = static function (int $id) use ($isEdicao, $agendamentoEdicao) {
        return $isEdicao && $agendamentoEdicao->pacienteId === $id ? 'selected' : '';
    };
    $selectedProf = static function (int $id) use ($isEdicao, $agendamentoEdicao) {
        return $isEdicao && $agendamentoEdicao->profissionalId === $id ? 'selected' : '';
    };

    $filtroAtivo = ($busca !== '' || $situacaoFiltro !== '' || $dataFiltro !== '');
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title><?= $isEdicao ? 'Editar' : 'Novo' ?> agendamento — Fito Clinic</title>
        <link rel="shortcut icon" href="../media/img/faviconrhay.png" type="image/x-icon">
        <link rel="stylesheet" href="../media/bootstrap/css/bootstrap.css" />
        <link rel="stylesheet" href="../media/icons/bootstrap-icons.css" />
        <link rel="stylesheet" href="../css/styles.css" />
        <link rel="stylesheet" href="../css/php.css" />
        <style>
            .badge-agendado {
                background: #dbe7f7;
                color: #1e40af;
                font-weight: 600;
            }

            .badge-confirmado {
                background: #e8f2e6;
                color: var(--green);
                font-weight: 600;
            }

            .badge-em-atendimento {
                background: #f9edd4;
                color: #9a6e1e;
                font-weight: 600;
            }

            .badge-concluido {
                background: #e5e7eb;
                color: #374151;
                font-weight: 600;
            }

            .badge-cancelado {
                background: #f3d7d7;
                color: #7a1f1f;
                font-weight: 600;
            }

            .badge-faltou {
                background: #fbe1e1;
                color: #a02020;
                font-weight: 600;
            }

            .badge-teleconsulta {
                background: #e0f2fe;
                color: #0369a1;
                font-weight: 600;
            }

            .row-hoje {
                background: rgba(216, 179, 106, 0.18) !important;
            }

            .filter-bar .form-control,
            .filter-bar .form-select {
                background: var(--cream-2);
                border: 1px solid var(--line);
            }
        </style>
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
                        <span class="eyebrow">AGENDA</span>
                        <h1><?= $isEdicao ? 'Editar agendamento' : 'Gestão de Agendamentos' ?></h1>
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
                        <li class="breadcrumb-item"><a href="agendamentosPage.php">Agenda</a></li>
                        <?php if ($isEdicao): ?>
                            <li class="breadcrumb-item active" aria-current="page">#<?= (int) $agendamentoEdicao->id ?></li>
                        <?php else: ?>
                            <li class="breadcrumb-item active" aria-current="page">Novo</li>
                        <?php endif; ?>
                    </ol>
                </nav>

                <?php foreach ($mensagens as $m): ?>
                    <div class="alert-fito alert-<?= $escapar($m['tipo']) ?> mb-3" role="alert">
                        <i class="bi <?= $m['tipo'] === 'sucesso' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>"></i>
                        <span><?= $escapar($m['texto']) ?></span>
                    </div>
                <?php endforeach; ?>

                <div class="page-header">
                    <div>
                        <h1><?= $isEdicao ? 'Editando agendamento #' . (int) $agendamentoEdicao->id : 'Agendamentos da clínica' ?></h1>
                        <span class="subtitle">
                            <?= $isEdicao
                                ? 'Altere os dados e clique em Salvar.'
                                : 'Organize os atendimentos, confirme horários e acompanhe a agenda.' ?>
                        </span>
                    </div>

                    <?php if ($isEdicao): ?>
                        <a href="agendamentosPage.php" class="btn-fito-outline">
                            <i class="bi bi-x-lg"></i> Cancelar edição
                        </a>
                    <?php else: ?>
                        <button class="btn-fito" type="button" data-bs-toggle="collapse"
                            data-bs-target="#formNovoAgendamento" aria-expanded="true"
                            aria-controls="formNovoAgendamento">
                            <i class="bi bi-plus-lg"></i> Novo agendamento
                        </button>
                    <?php endif; ?>
                </div>

                <!-- ============ FORMULÁRIO ============ -->
                <div class="collapse show" id="formNovoAgendamento">
                    <div class="card card-fito mb-4 <?= $isEdicao ? 'border-warning' : '' ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <span class="eyebrow gold"><?= $isEdicao ? 'EDIÇÃO' : 'CADASTRO' ?></span>
                                <h2 class="m-0">
                                    <?= $isEdicao ? 'Alterar dados do agendamento' : 'Dados do agendamento' ?>
                                </h2>
                            </div>
                            <?php if ($isEdicao): ?>
                                <span class="badge rounded-pill badge-fito-admin">
                                    <i class="bi bi-pencil"></i> Modo edição
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body">
                            <form method="post" action="" class="form-fito" id="formAgendamento">
                                <?php if ($isEdicao): ?>
                                    <input type="hidden" name="acao" value="atualizar">
                                    <input type="hidden" name="id" value="<?= (int) $agendamentoEdicao->id ?>">
                                <?php else: ?>
                                    <input type="hidden" name="acao" value="criar">
                                <?php endif; ?>

                                <div class="row g-3">
                                    <!-- Paciente e profissional -->
                                    <div class="col-md-6">
                                        <label for="paciente_id" class="form-label">Paciente *</label>
                                        <select id="paciente_id" name="paciente_id" class="form-select" required>
                                            <option value="">— Selecione o paciente —</option>
                                            <?php foreach ($pacientes as $p): ?>
                                                <option value="<?= (int) $p['id'] ?>"
                                                    <?= $selectedPaciente((int) $p['id']) ?>>
                                                    <?= $escapar($p['nome']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="profissional_id" class="form-label">Profissional *</label>
                                        <select id="profissional_id" name="profissional_id" class="form-select" required>
                                            <option value="">— Selecione o profissional —</option>
                                            <?php foreach ($profissionais as $pr): ?>
                                                <option value="<?= (int) $pr['id'] ?>"
                                                    <?= $selectedProf((int) $pr['id']) ?>>
                                                    <?= $escapar($pr['nome']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Data e horários -->
                                    <div class="col-md-4">
                                        <label for="data_agendamento" class="form-label">Data *</label>
                                        <input type="date" id="data_agendamento" name="data_agendamento"
                                            class="form-control" value="<?= $val('dataAgendamento') ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="hora_inicio" class="form-label">Hora de início *</label>
                                        <input type="time" id="hora_inicio" name="hora_inicio"
                                            class="form-control" value="<?= $val('horaInicio') ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="hora_fim" class="form-label">Hora de término</label>
                                        <input type="time" id="hora_fim" name="hora_fim"
                                            class="form-control" value="<?= $val('horaFim') ?>">
                                        <div class="form-text">Opcional. Se vazio, considera-se 30 min.</div>
                                    </div>

                                    <!-- Tipo, situação, origem -->
                                    <div class="col-md-4">
                                        <label for="tipo" class="form-label">Tipo *</label>
                                        <select id="tipo" name="tipo" class="form-select" required>
                                            <option value="consulta" <?= $selOpt('tipo', 'consulta')     ?>>Consulta</option>
                                            <option value="massagem" <?= $selOpt('tipo', 'massagem')     ?>>Massagem</option>
                                            <option value="procedimento" <?= $selOpt('tipo', 'procedimento') ?>>Procedimento</option>
                                            <option value="retorno" <?= $selOpt('tipo', 'retorno')      ?>>Retorno</option>
                                            <option value="outro" <?= $selOpt('tipo', 'outro')        ?>>Outro</option>
                                        </select>
                                    </div>

                                    <?php if ($isEdicao): ?>
                                        <div class="col-md-4">
                                            <label for="situacao" class="form-label">Situação *</label>
                                            <select id="situacao" name="situacao" class="form-select" required>
                                                <option value="agendado" <?= $selOpt('situacao', 'agendado')       ?>>Agendado</option>
                                                <option value="confirmado" <?= $selOpt('situacao', 'confirmado')     ?>>Confirmado</option>
                                                <option value="em_atendimento" <?= $selOpt('situacao', 'em_atendimento') ?>>Em atendimento</option>
                                                <option value="concluido" <?= $selOpt('situacao', 'concluido')      ?>>Concluído</option>
                                                <option value="cancelado" <?= $selOpt('situacao', 'cancelado')      ?>>Cancelado</option>
                                                <option value="faltou" <?= $selOpt('situacao', 'faltou')         ?>>Faltou</option>
                                            </select>
                                        </div>
                                    <?php else: ?>
                                        <div class="col-md-4">
                                            <label class="form-label">Situação inicial</label>
                                            <input type="text" class="form-control" value="Agendado" disabled>
                                            <div class="form-text">Todo novo agendamento inicia como "Agendado".</div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="col-md-4">
                                        <label for="origem_agendamento" class="form-label">Origem *</label>
                                        <select id="origem_agendamento" name="origem_agendamento" class="form-select" required>
                                            <option value="interno" <?= $selOpt('origemAgendamento', 'interno')  ?>>Interno</option>
                                            <option value="online" <?= $selOpt('origemAgendamento', 'online')   ?>>Online</option>
                                            <option value="whatsapp" <?= $selOpt('origemAgendamento', 'whatsapp') ?>>WhatsApp</option>
                                        </select>
                                    </div>

                                    <!-- Teleconsulta -->
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="teleconsulta"
                                                name="teleconsulta" value="1"
                                                <?= ($isEdicao && $agendamentoEdicao->teleconsulta) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="teleconsulta">
                                                <i class="bi bi-camera-video"></i> É teleconsulta?
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="url_reuniao" class="form-label">URL da reunião</label>
                                        <input type="url" id="url_reuniao" name="url_reuniao"
                                            class="form-control" value="<?= $val('urlReuniao') ?>"
                                            maxlength="500" placeholder="https://meet.google.com/...">
                                        <div class="form-text">Obrigatória quando a teleconsulta estiver marcada.</div>
                                    </div>

                                    <div class="col-12">
                                        <label for="observacoes" class="form-label">Observações</label>
                                        <textarea id="observacoes" name="observacoes" class="form-control" rows="3"
                                            maxlength="5000"><?= $val('observacoes') ?></textarea>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <?php if ($isEdicao): ?>
                                        <a href="agendamentosPage.php" class="btn-fito-outline">
                                            <i class="bi bi-x-lg"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn-fito">
                                            <i class="bi bi-save"></i> Salvar alterações
                                        </button>
                                    <?php else: ?>
                                        <button type="reset" class="btn-fito-outline">Limpar</button>
                                        <button type="submit" class="btn-fito">
                                            <i class="bi bi-check-lg"></i> Agendar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ============ LISTA + FILTROS ============ -->
                <div class="card card-fito">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="eyebrow gold" id="listEyebrow">
                                    <?= $filtroAtivo ? 'RESULTADOS' : 'AGENDA' ?>
                                </span>
                                <h2 class="m-0" id="listTitle">
                                    <?= $filtroAtivo ? 'Agendamentos encontrados' : 'Agendamentos cadastrados' ?>
                                </h2>
                            </div>
                            <span class="badge rounded-pill badge-fito-prof" id="agendamentosCount">
                                <?= count($agendamentos) ?> <?= count($agendamentos) === 1 ? 'registro' : 'registros' ?>
                            </span>
                        </div>

                        <div class="filter-bar d-flex flex-wrap gap-2 align-items-center mb-2">
                            <div class="flex-grow-1" style="min-width: 220px;">
                                <input type="text" id="buscaInput"
                                    class="form-control form-control-sm"
                                    maxlength="150" placeholder="Buscar por paciente…"
                                    aria-label="Buscar por nome do paciente"
                                    value="<?= $escapar($busca) ?>"
                                    autocomplete="off">
                            </div>
                            <div style="min-width: 160px;">
                                <select id="situacaoFilter" class="form-select form-select-sm"
                                    aria-label="Filtrar por situação">
                                    <option value="">Todas as situações</option>
                                    <?php foreach (Agendamento::SITUACOES as $s): ?>
                                        <option value="<?= $escapar($s) ?>"
                                            <?= $situacaoFiltro === $s ? 'selected' : '' ?>>
                                            <?= $escapar(match ($s) {
                                                'agendado'       => 'Agendado',
                                                'confirmado'     => 'Confirmado',
                                                'em_atendimento' => 'Em atendimento',
                                                'concluido'      => 'Concluído',
                                                'cancelado'      => 'Cancelado',
                                                'faltou'         => 'Faltou',
                                                default          => $s,
                                            }) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="min-width: 160px;">
                                <input type="date" id="dataFilter" class="form-control form-control-sm"
                                    aria-label="Filtrar por data"
                                    value="<?= $escapar($dataFiltro) ?>">
                            </div>
                            <button type="button" id="hojeBtn" class="btn-fito-outline" title="Filtrar por hoje">
                                <i class="bi bi-calendar-day"></i> Hoje
                            </button>
                            <button type="button" id="limparFiltros" class="btn-fito-outline" title="Limpar filtros">
                                <i class="bi bi-x-lg"></i> Limpar
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive" id="agendamentosTableWrapper">
                            <table class="table table-fito align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Paciente</th>
                                        <th scope="col">Profissional</th>
                                        <th scope="col">Data / Hora</th>
                                        <th scope="col">Tipo</th>
                                        <th scope="col">Situação</th>
                                        <th scope="col">Teleconsulta</th>
                                        <th scope="col" class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="agendamentosTbody">
                                    <?php renderAgendamentoTableRows($agendamentos, $isEdicao ? $agendamentoEdicao->id : null, $busca, $situacaoFiltro, $dataFiltro); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <footer class="footer">
                    <span>Fito Clinic &amp; Estética</span>
                    <span>Sistema de gestão clínica</span>
                </footer>
            </main>
        </div>

        <!-- ============ MODAL DE EXCLUSÃO ============ -->
        <div class="modal fade" id="modalExcluirAgendamento" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content card-fito border-0">
                    <div class="modal-header border-0 pb-0">
                        <span class="eyebrow gold">CONFIRMAÇÃO</span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <h2 class="mb-3">Excluir agendamento</h2>
                        <p class="text-fito-muted mb-0">
                            Tem certeza que deseja excluir o agendamento
                            <strong id="resumoAgendamentoExcluir">selecionado</strong>?
                            Esta ação não pode ser desfeita.
                        </p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <form method="post" action="" id="formExcluirAgendamento">
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" id="idAgendamentoExcluir" value="">
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
            // ---- Modal de exclusão ----
            document.getElementById('modalExcluirAgendamento').addEventListener('show.bs.modal', function(event) {
                const botao = event.relatedTarget;
                document.getElementById('idAgendamentoExcluir').value = botao.getAttribute('data-agendamento-id');
                document.getElementById('resumoAgendamentoExcluir').textContent = botao.getAttribute('data-agendamento-resumo');
            });

            // ---- Teleconsulta: torna URL obrigatória ----
            (function() {
                const tel = document.getElementById('teleconsulta');
                const url = document.getElementById('url_reuniao');

                function sync() {
                    if (tel.checked) url.setAttribute('required', 'required');
                    else url.removeAttribute('required');
                }
                tel.addEventListener('change', sync);
                sync();
            })();

            // ---- Autoscroll em modo edição ----
            <?php if ($isEdicao): ?>
                document.getElementById('formAgendamento').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            <?php endif; ?>

                // ---- Busca em tempo real (AJAX, sem reload) ----
                (function() {
                    const buscaInput = document.getElementById('buscaInput');
                    const situacaoSel = document.getElementById('situacaoFilter');
                    const dataInput = document.getElementById('dataFilter');
                    const tbody = document.getElementById('agendamentosTbody');
                    const countBadge = document.getElementById('agendamentosCount');
                    const listEyebrow = document.getElementById('listEyebrow');
                    const listTitle = document.getElementById('listTitle');
                    const hojeBtn = document.getElementById('hojeBtn');
                    const limparBtn = document.getElementById('limparFiltros');

                    let debounceTimer = null;

                    function updateHeader(total) {
                        const temFiltro = buscaInput.value.trim() !== '' ||
                            situacaoSel.value !== '' ||
                            dataInput.value !== '';
                        listEyebrow.textContent = temFiltro ? 'RESULTADOS' : 'AGENDA';
                        listTitle.textContent = temFiltro ? 'Agendamentos encontrados' : 'Agendamentos cadastrados';
                        countBadge.textContent = total + (total === 1 ? ' registro' : ' registros');
                    }

                    function buscar() {
                        const params = new URLSearchParams({
                            busca: buscaInput.value.trim(),
                            situacao: situacaoSel.value,
                            data: dataInput.value,
                        });

                        fetch('agendamentosSearchAjax.php?' + params.toString(), {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(r => r.json())
                            .then(data => {
                                tbody.innerHTML = data.html;
                                updateHeader(data.total);
                            })
                            .catch(err => console.error('Erro na busca:', err));
                    }

                    buscaInput.addEventListener('input', () => {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(buscar, 300);
                    });
                    situacaoSel.addEventListener('change', buscar);
                    dataInput.addEventListener('change', buscar);

                    hojeBtn.addEventListener('click', () => {
                        const hoje = new Date();
                        const iso = hoje.getFullYear() + '-' +
                            String(hoje.getMonth() + 1).padStart(2, '0') + '-' +
                            String(hoje.getDate()).padStart(2, '0');
                        dataInput.value = iso;
                        buscar();
                    });

                    limparBtn.addEventListener('click', () => {
                        buscaInput.value = '';
                        situacaoSel.value = '';
                        dataInput.value = '';
                        buscar();
                    });
                })();
        </script>
    </body>

    </html>
<?php
}
