<?php
// views/prontuariosGUI.php

/** Formata uma linha de agendamento como texto legível para <option>. */
function formatAgendamentoOptionLabel(array $a): string
{
    $dias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    try {
        $dt = new DateTime($a['data_agendamento']);
        $dia = $dias[(int) $dt->format('w')];
        $dataStr = $dt->format('d/m/Y') . " ({$dia})";
    } catch (Throwable $e) {
        $dataStr = (string) ($a['data_agendamento'] ?? '');
    }
    $hora = substr((string) ($a['hora_inicio'] ?? ''), 0, 5);
    $tipo = match ($a['tipo'] ?? '') {
        'consulta'     => 'Consulta',
        'massagem'     => 'Massagem',
        'procedimento' => 'Procedimento',
        'retorno'      => 'Retorno',
        'outro'        => 'Outro',
        default        => (string) ($a['tipo'] ?? ''),
    };
    return "{$dataStr} {$hora} — {$tipo}";
}

/**
 * Renderiza apenas as <tr> da tabela de prontuários.
 * Usada pela página principal e pelo endpoint AJAX.
 *
 * @param Prontuario[] $prontuarios
 */
function renderProntuarioTableRows(
    array $prontuarios,
    ?int $idEdicao = null,
    string $busca = '',
    int $profissionalFiltro = 0,
    string $dataFiltro = ''
): void {
    $escapar = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

    if (empty($prontuarios)) {
        $colspan = 8;
?>
        <tr>
            <td colspan="<?= $colspan ?>" class="text-center py-5">
                <div class="empty-state">
                    <i class="bi bi-file-medical"></i>
                    <?php if ($busca !== '' || $profissionalFiltro > 0 || $dataFiltro !== ''): ?>
                        Nenhum prontuário encontrado com os filtros aplicados.
                    <?php else: ?>
                        Nenhum prontuário cadastrado ainda.
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php
        return;
    }

    foreach ($prontuarios as $p):
        $isEdit   = $idEdicao !== null && $idEdicao === $p->id;
        $rowClass = $isEdit ? 'table-warning' : '';

        $linkEditar = '?editar=' . (int) $p->id;
        if ($busca !== '')              $linkEditar .= '&busca=' . urlencode($busca);
        if ($profissionalFiltro > 0)    $linkEditar .= '&profissional=' . (int) $profissionalFiltro;
        if ($dataFiltro !== '')         $linkEditar .= '&data=' . urlencode($dataFiltro);

        $queixaResumida = $p->queixa !== null
            ? (mb_strlen($p->queixa) > 55
                ? mb_substr($p->queixa, 0, 55) . '…'
                : $p->queixa)
            : '—';

        $imc = $p->imc();
    ?>
        <tr class="<?= $rowClass ?>">
            <td class="text-fito-muted">#<?= (int) $p->id ?></td>
            <td><strong><?= $escapar($p->pacienteNome ?? '—') ?></strong></td>
            <td><?= $escapar($p->profissionalNome ?? '—') ?></td>
            <td>
                <?= $escapar($p->dataRegistroBRComDia()) ?>
            </td>
            <td title="<?= $escapar($p->queixa ?? '') ?>">
                <?= $escapar($queixaResumida) ?>
            </td>
            <td>
                <?php if ($imc !== null): ?>
                    <span class="badge rounded-pill <?= $escapar($p->imcBadgeClass()) ?>"
                        title="IMC: <?= $escapar($imc) ?> — <?= $escapar($p->imcLabel()) ?>">
                        IMC <?= $escapar(number_format($imc, 1, ',', '.')) ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($p->pressaoArterial)): ?>
                    <br><small class="text-fito-muted"><?= $escapar($p->pressaoFormatada()) ?></small>
                <?php elseif ($imc === null): ?>
                    <span class="text-fito-muted">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($p->agendamentoId !== null): ?>
                    <span class="badge rounded-pill badge-agendamento-vinculado"
                        title="Agendamento #<?= (int) $p->agendamentoId ?>">
                        <i class="bi bi-calendar-check"></i>
                        <?= $escapar($p->agendamentoResumo ?? ('#' . $p->agendamentoId)) ?>
                    </span>
                <?php else: ?>
                    <span class="text-fito-muted">—</span>
                <?php endif; ?>
            </td>
            <td class="text-end text-nowrap">
                <a href="<?= $escapar($linkEditar) ?>"
                    class="btn btn-warning margem-vertical" title="Editar prontuário">
                    <i class="bi bi-pen"></i> Editar
                </a>
                <button type="button" class="btn btn-danger margem-vertical"
                    data-bs-toggle="modal" data-bs-target="#modalExcluirProntuario"
                    data-prontuario-id="<?= (int) $p->id ?>"
                    data-prontuario-resumo="<?= $escapar($p->resumo()) ?>">
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
 * @param Prontuario[] $prontuarios
 * @param array        $mensagens
 * @param Prontuario|null $prontuarioEdicao
 * @param string       $busca
 * @param int          $profissionalFiltro
 * @param string       $dataFiltro
 * @param array        $pacientes
 * @param array        $profissionais
 * @param array        $agendamentosPaciente
 */
function renderProntuarioGUI(
    array $prontuarios,
    array $mensagens = [],
    ?Prontuario $prontuarioEdicao = null,
    string $busca = '',
    int $profissionalFiltro = 0,
    string $dataFiltro = '',
    array $pacientes = [],
    array $profissionais = [],
    array $agendamentosPaciente = []
): void {
    $escapar = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

    $isEdicao       = $prontuarioEdicao !== null && $prontuarioEdicao->id !== null;
    $temFormData    = $prontuarioEdicao !== null;

    $val = static function (string $campo) use ($prontuarioEdicao, $escapar) {
        if ($prontuarioEdicao === null) return '';
        return $escapar($prontuarioEdicao->$campo ?? '');
    };
    $dataFormValue = $temFormData
        ? $prontuarioEdicao->dataRegistroFormInput()
        : (new DateTime())->format('Y-m-d\TH:i');

    $selPaciente = static function (int $id) use ($prontuarioEdicao) {
        return ($prontuarioEdicao !== null && $prontuarioEdicao->pacienteId === $id) ? 'selected' : '';
    };
    $selProf = static function (int $id) use ($prontuarioEdicao) {
        return ($prontuarioEdicao !== null && $prontuarioEdicao->profissionalId === $id) ? 'selected' : '';
    };
    $selAgendamento = static function (int $id) use ($prontuarioEdicao) {
        return ($prontuarioEdicao !== null && $prontuarioEdicao->agendamentoId === $id) ? 'selected' : '';
    };

    $filtroAtivo = ($busca !== '' || $profissionalFiltro > 0 || $dataFiltro !== '');

    // Gera/recupera token CSRF (usa as helpers do Page se disponíveis)
    $csrf = function_exists('csrfToken') ? csrfToken() : '';
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title><?= $isEdicao ? 'Editar' : 'Novo' ?> prontuário — Fito Clinic</title>
        <link rel="shortcut icon" href="../media/img/faviconrhay.png" type="image/x-icon">
        <link rel="stylesheet" href="../media/bootstrap/css/bootstrap.css" />
        <link rel="stylesheet" href="../media/icons/bootstrap-icons.css" />
        <link rel="stylesheet" href="../css/styles.css" />
        <link rel="stylesheet" href="../css/php.css" />
        <style>
            .badge-imc {
                background: #e5e7eb;
                color: #374151;
                font-weight: 600;
            }

            .badge-imc-baixo {
                background: #dbe7f7;
                color: #1e40af;
                font-weight: 600;
            }

            .badge-imc-normal {
                background: #e8f2e6;
                color: var(--green);
                font-weight: 600;
            }

            .badge-imc-sobrepeso {
                background: #f9edd4;
                color: #9a6e1e;
                font-weight: 600;
            }

            .badge-imc-obesidade {
                background: #f3d7d7;
                color: #7a1f1f;
                font-weight: 600;
            }

            .badge-agendamento-vinculado {
                background: #e0f2fe;
                color: #0369a1;
                font-weight: 600;
            }

            .filter-bar .form-control,
            .filter-bar .form-select {
                background: var(--cream-2);
                border: 1px solid var(--line);
            }

            .text-clinico {
                white-space: pre-wrap;
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
                        <span class="eyebrow">PRONTUÁRIO</span>
                        <h1><?= $isEdicao ? 'Editar prontuário' : 'Gestão de Prontuários' ?></h1>
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
                        <li class="breadcrumb-item"><a href="prontuariosPage.php">Prontuários</a></li>
                        <?php if ($isEdicao): ?>
                            <li class="breadcrumb-item active" aria-current="page">#<?= (int) $prontuarioEdicao->id ?></li>
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
                        <h1><?= $isEdicao ? 'Editando prontuário #' . (int) $prontuarioEdicao->id : 'Prontuários da clínica' ?></h1>
                        <span class="subtitle">
                            <?= $isEdicao
                                ? 'Altere os dados e clique em Salvar.'
                                : 'Registre consultas, evoluções e sinais vitais dos pacientes.' ?>
                        </span>
                    </div>

                    <?php if ($isEdicao): ?>
                        <a href="prontuariosPage.php" class="btn-fito-outline">
                            <i class="bi bi-x-lg"></i> Cancelar edição
                        </a>
                    <?php else: ?>
                        <button class="btn-fito" type="button" data-bs-toggle="collapse"
                            data-bs-target="#formNovoProntuario" aria-expanded="true"
                            aria-controls="formNovoProntuario">
                            <i class="bi bi-plus-lg"></i> Novo prontuário
                        </button>
                    <?php endif; ?>
                </div>

                <!-- ============ FORMULÁRIO ============ -->
                <div class="collapse show" id="formNovoProntuario">
                    <div class="card card-fito mb-4 <?= $isEdicao ? 'border-warning' : '' ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <span class="eyebrow gold"><?= $isEdicao ? 'EDIÇÃO' : 'CADASTRO' ?></span>
                                <h2 class="m-0">
                                    <?= $isEdicao ? 'Alterar dados do prontuário' : 'Dados do prontuário' ?>
                                </h2>
                            </div>
                            <?php if ($isEdicao): ?>
                                <span class="badge rounded-pill badge-fito-admin">
                                    <i class="bi bi-pencil"></i> Modo edição
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body">
                            <form method="post" action="" class="form-fito" id="formProntuario">
                                <input type="hidden" name="csrf_token" value="<?= $escapar($csrf) ?>">
                                <?php if ($isEdicao): ?>
                                    <input type="hidden" name="acao" value="atualizar">
                                    <input type="hidden" name="id" value="<?= (int) $prontuarioEdicao->id ?>">
                                <?php else: ?>
                                    <input type="hidden" name="acao" value="criar">
                                <?php endif; ?>

                                <div class="row g-3">
                                    <!-- Paciente / Profissional -->
                                    <div class="col-md-6">
                                        <label for="paciente_id" class="form-label">Paciente *</label>
                                        <select id="paciente_id" name="paciente_id" class="form-select" required>
                                            <option value="">— Selecione o paciente —</option>
                                            <?php foreach ($pacientes as $p): ?>
                                                <option value="<?= (int) $p['id'] ?>" <?= $selPaciente((int) $p['id']) ?>>
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
                                                <option value="<?= (int) $pr['id'] ?>" <?= $selProf((int) $pr['id']) ?>>
                                                    <?= $escapar($pr['nome']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Data e agendamento -->
                                    <div class="col-md-6">
                                        <label for="data_registro" class="form-label">Data/hora do registro *</label>
                                        <input type="datetime-local" id="data_registro" name="data_registro"
                                            class="form-control" value="<?= $escapar($dataFormValue) ?>" required>
                                        <div class="form-text">Não é permitido registrar datas futuras.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="agendamento_id" class="form-label">Agendamento vinculado</label>
                                        <select id="agendamento_id" name="agendamento_id" class="form-select">
                                            <option value="">— Sem agendamento vinculado —</option>
                                            <?php foreach ($agendamentosPaciente as $ag): ?>
                                                <option value="<?= (int) $ag['id'] ?>" <?= $selAgendamento((int) $ag['id']) ?>>
                                                    <?= $escapar(formatAgendamentoOptionLabel($ag)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">
                                            Opcional. Selecione um paciente primeiro para listar os agendamentos.
                                            Cada agendamento só pode ser vinculado a um prontuário.
                                        </div>
                                    </div>

                                    <!-- Sinais vitais -->
                                    <div class="col-md-4">
                                        <label for="peso" class="form-label">Peso (kg)</label>
                                        <input type="number" id="peso" name="peso" class="form-control"
                                            value="<?= $escapar($prontuarioEdicao && $prontuarioEdicao->peso !== null ? number_format($prontuarioEdicao->peso, 2, '.', '') : '') ?>"
                                            min="0.01" max="999" step="0.01" placeholder="70.5">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="altura" class="form-label">Altura (m)</label>
                                        <input type="number" id="altura" name="altura" class="form-control"
                                            value="<?= $escapar($prontuarioEdicao && $prontuarioEdicao->altura !== null ? number_format($prontuarioEdicao->altura, 2, '.', '') : '') ?>"
                                            min="0.01" max="2.99" step="0.01" placeholder="1.75">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="pressao_arterial" class="form-label">Pressão arterial</label>
                                        <input type="text" id="pressao_arterial" name="pressao_arterial"
                                            class="form-control" value="<?= $val('pressaoArterial') ?>"
                                            maxlength="20" placeholder="120/80">
                                        <div class="form-text">Formato: 120/80 ou 120/80 mmHg</div>
                                    </div>

                                    <!-- Campos clínicos -->
                                    <div class="col-12">
                                        <label for="queixa" class="form-label">Queixa principal</label>
                                        <textarea id="queixa" name="queixa" class="form-control" rows="2"
                                            maxlength="2000" placeholder="O que o paciente relata..."><?= $val('queixa') ?></textarea>
                                    </div>

                                    <div class="col-12">
                                        <label for="historico_clinico" class="form-label">Histórico clínico</label>
                                        <textarea id="historico_clinico" name="historico_clinico"
                                            class="form-control" rows="3" maxlength="5000"
                                            placeholder="Antecedentes, condições prévias, medicações em uso..."><?= $val('historicoClinico') ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="diagnostico" class="form-label">Diagnóstico</label>
                                        <textarea id="diagnostico" name="diagnostico" class="form-control" rows="3"
                                            maxlength="3000"><?= $val('diagnostico') ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="tratamento" class="form-label">Tratamento / conduta</label>
                                        <textarea id="tratamento" name="tratamento" class="form-control" rows="3"
                                            maxlength="3000"><?= $val('tratamento') ?></textarea>
                                    </div>

                                    <div class="col-12">
                                        <label for="observacoes" class="form-label">Observações gerais</label>
                                        <textarea id="observacoes" name="observacoes" class="form-control" rows="2"
                                            maxlength="3000"><?= $val('observacoes') ?></textarea>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <?php if ($isEdicao): ?>
                                        <a href="prontuariosPage.php" class="btn-fito-outline">
                                            <i class="bi bi-x-lg"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn-fito">
                                            <i class="bi bi-save"></i> Salvar alterações
                                        </button>
                                    <?php else: ?>
                                        <button type="reset" class="btn-fito-outline">Limpar</button>
                                        <button type="submit" class="btn-fito">
                                            <i class="bi bi-check-lg"></i> Registrar
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
                                    <?= $filtroAtivo ? 'RESULTADOS' : 'PRONTUÁRIOS' ?>
                                </span>
                                <h2 class="m-0" id="listTitle">
                                    <?= $filtroAtivo ? 'Prontuários encontrados' : 'Prontuários cadastrados' ?>
                                </h2>
                            </div>
                            <span class="badge rounded-pill badge-fito-prof" id="prontuariosCount">
                                <?= count($prontuarios) ?> <?= count($prontuarios) === 1 ? 'registro' : 'registros' ?>
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
                            <div style="min-width: 180px;">
                                <select id="profissionalFilter" class="form-select form-select-sm"
                                    aria-label="Filtrar por profissional">
                                    <option value="">Todos os profissionais</option>
                                    <?php foreach ($profissionais as $pr): ?>
                                        <option value="<?= (int) $pr['id'] ?>"
                                            <?= $profissionalFiltro === (int) $pr['id'] ? 'selected' : '' ?>>
                                            <?= $escapar($pr['nome']) ?>
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
                        <div class="table-responsive" id="prontuariosTableWrapper">
                            <table class="table table-fito align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Paciente</th>
                                        <th scope="col">Profissional</th>
                                        <th scope="col">Data/Hora</th>
                                        <th scope="col">Queixa</th>
                                        <th scope="col">Sinais vitais</th>
                                        <th scope="col">Agendamento</th>
                                        <th scope="col" class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="prontuariosTbody">
                                    <?php renderProntuarioTableRows($prontuarios, $isEdicao ? $prontuarioEdicao->id : null, $busca, $profissionalFiltro, $dataFiltro); ?>
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
        <div class="modal fade" id="modalExcluirProntuario" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content card-fito border-0">
                    <div class="modal-header border-0 pb-0">
                        <span class="eyebrow gold">CONFIRMAÇÃO</span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <h2 class="mb-3">Excluir prontuário</h2>
                        <p class="text-fito-muted mb-0">
                            Tem certeza que deseja excluir o prontuário
                            <strong id="resumoProntuarioExcluir">selecionado</strong>?
                            Esta ação não pode ser desfeita.
                        </p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <form method="post" action="" id="formExcluirProntuario">
                            <input type="hidden" name="csrf_token" value="<?= $escapar($csrf) ?>">
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" id="idProntuarioExcluir" value="">
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
            document.getElementById('modalExcluirProntuario').addEventListener('show.bs.modal', function(event) {
                const botao = event.relatedTarget;
                document.getElementById('idProntuarioExcluir').value = botao.getAttribute('data-prontuario-id');
                document.getElementById('resumoProntuarioExcluir').textContent = botao.getAttribute('data-prontuario-resumo');
            });

            // ---- Autoscroll em modo edição ou após erro ----
            <?php if ($temFormData): ?>
                document.getElementById('formProntuario').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            <?php endif; ?>

                // ---- Agendamentos dinâmicos ao trocar paciente ----
                (function() {
                    const pacienteSel = document.getElementById('paciente_id');
                    const agendamentoSel = document.getElementById('agendamento_id');
                    if (!pacienteSel || !agendamentoSel) return;

                    const placeholderSemPaciente = '— Sem agendamento vinculado —';

                    pacienteSel.addEventListener('change', function() {
                        const pid = pacienteSel.value;
                        if (!pid) {
                            agendamentoSel.innerHTML = '<option value="">' + placeholderSemPaciente + '</option>';
                            return;
                        }
                        fetch('prontuariosSearchAjax.php?mode=agendamentos&paciente_id=' + encodeURIComponent(pid), {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(r => r.json())
                            .then(data => {
                                agendamentoSel.innerHTML =
                                    '<option value="">' + placeholderSemPaciente + '</option>' + (data.options || '');
                            })
                            .catch(err => console.error('Erro ao buscar agendamentos:', err));
                    });
                })();

            // ---- Busca em tempo real (AJAX, debounce 300 ms) ----
            (function() {
                const buscaInput = document.getElementById('buscaInput');
                const profSel = document.getElementById('profissionalFilter');
                const dataInput = document.getElementById('dataFilter');
                const tbody = document.getElementById('prontuariosTbody');
                const countBadge = document.getElementById('prontuariosCount');
                const listEyebrow = document.getElementById('listEyebrow');
                const listTitle = document.getElementById('listTitle');
                const hojeBtn = document.getElementById('hojeBtn');
                const limparBtn = document.getElementById('limparFiltros');

                let debounceTimer = null;

                function updateHeader(total) {
                    const temFiltro = buscaInput.value.trim() !== '' ||
                        profSel.value !== '' ||
                        dataInput.value !== '';
                    listEyebrow.textContent = temFiltro ? 'RESULTADOS' : 'PRONTUÁRIOS';
                    listTitle.textContent = temFiltro ? 'Prontuários encontrados' : 'Prontuários cadastrados';
                    countBadge.textContent = total + (total === 1 ? ' registro' : ' registros');
                }

                function buscar() {
                    const params = new URLSearchParams({
                        busca: buscaInput.value.trim(),
                        profissional: profSel.value,
                        data: dataInput.value,
                    });

                    fetch('prontuariosSearchAjax.php?' + params.toString(), {
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
                profSel.addEventListener('change', buscar);
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
                    profSel.value = '';
                    dataInput.value = '';
                    buscar();
                });
            })();
        </script>
    </body>

    </html>
<?php
}
