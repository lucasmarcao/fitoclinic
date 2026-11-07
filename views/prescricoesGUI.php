<?php
// views/prescricoesGUI.php

declare(strict_types=1);

/**
 * Escaping centralizado para todas as saídas HTML.
 */
function escaparPrescricao(mixed $valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Formata a opção do select de prontuários.
 */
function formatarPrescricaoProntuarioOption(
    array $prontuario
): string {

    try {

        $data = (new DateTime(
            (string) ($prontuario['data_registro'] ?? '')
        ))->format('d/m/Y H:i');
    } catch (Throwable) {

        $data = 'Data não informada';
    }

    $id = (int) ($prontuario['id'] ?? 0);

    $diagnostico = trim(
        (string) ($prontuario['diagnostico'] ?? '')
    );

    $resumo = '#' . $id . ' — ' . $data;

    if ($diagnostico !== '') {

        $resumo .= ' — '
            . mb_substr($diagnostico, 0, 60);
    }

    return $resumo;
}

/**
 * Renderiza um cartão de medicamento no formulário.
 *
 * Os campos usam data-campo para permitir que o JavaScript
 * reorganize automaticamente os índices do array POST.
 */
function renderPrescricaoItemFields(
    array $item,
    int $indice
): void {

    $campos = [

        'nome_medicamento' =>
        $item['nome_medicamento'] ?? '',

        'principio_ativo' =>
        $item['principio_ativo'] ?? '',

        'apresentacao' =>
        $item['apresentacao'] ?? '',

        'concentracao' =>
        $item['concentracao'] ?? '',

        'dosagem' =>
        $item['dosagem'] ?? '',

        'via_administracao' =>
        $item['via_administracao'] ?? '',

        'frequencia' =>
        $item['frequencia'] ?? '',

        'duracao' =>
        $item['duracao'] ?? '',

        'quantidade' =>
        $item['quantidade'] ?? '',

        'instrucoes' =>
        $item['instrucoes'] ?? '',

        'dados_dosagem' =>
        $item['dados_dosagem'] ?? '',
    ];

    $nomes = [

        'nome_medicamento' => 'Medicamento',

        'principio_ativo' => 'Princípio ativo',

        'apresentacao' => 'Apresentação',

        'concentracao' => 'Concentração',

        'dosagem' => 'Dosagem',

        'via_administracao' => 'Via de administração',

        'frequencia' => 'Frequência',

        'duracao' => 'Duração',

        'quantidade' => 'Quantidade',

    ];
?>
    <div class="col-12 item-prescricao mb-3">

        <div class="card border item-prescricao-card">

            <div class="card-header d-flex justify-content-between align-items-center">

                <div>
                    <span class="bi bi-capsule me-2"></span>

                    <strong>
                        Medicamento <span data-numero-item><?= $indice + 1 ?></span>
                    </strong>
                </div>

                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger btn-remover-item"
                    title="Remover medicamento">
                    <span class="bi bi-trash"></span>
                    Remover
                </button>

            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-12 col-lg-6">

                        <label class="form-label">
                            Nome do medicamento *
                        </label>

                        <input
                            type="text"
                            class="form-control campo-item"
                            data-campo="nome_medicamento"
                            name="itens[<?= $indice ?>][nome_medicamento]"
                            maxlength="200"
                            value="<?= escaparPrescricao($campos['nome_medicamento']) ?>"
                            placeholder="Ex.: Vitamina D3"
                            required>

                    </div>

                    <div class="col-12 col-lg-6">

                        <label class="form-label">
                            Princípio ativo
                        </label>

                        <input
                            type="text"
                            class="form-control campo-item"
                            data-campo="principio_ativo"
                            name="itens[<?= $indice ?>][principio_ativo]"
                            maxlength="200"
                            value="<?= escaparPrescricao($campos['principio_ativo']) ?>"
                            placeholder="Princípio ativo">

                    </div>

                    <?php foreach ($nomes as $campo => $rotulo): ?>

                        <?php
                        if ($campo === 'nome_medicamento') {
                            continue;
                        }
                        ?>

                        <?php
                        $max = match ($campo) {
                            'apresentacao' => 150,
                            'concentracao',
                            'dosagem',
                            'via_administracao',
                            'frequencia',
                            'duracao',
                            'quantidade' => 100,
                            default => 200,
                        };
                        ?>

                        <div class="col-12 col-md-6 col-lg-4">

                            <label class="form-label">
                                <?= escaparPrescricao($rotulo) ?>
                            </label>

                            <input
                                type="text"
                                class="form-control campo-item"
                                data-campo="<?= escaparPrescricao($campo) ?>"
                                name="itens[<?= $indice ?>][<?= escaparPrescricao($campo) ?>]"
                                maxlength="<?= $max ?>"
                                value="<?= escaparPrescricao($campos[$campo]) ?>">

                        </div>

                    <?php endforeach; ?>

                    <div class="col-12">

                        <label class="form-label">
                            Instruções adicionais
                        </label>

                        <textarea
                            class="form-control campo-item"
                            data-campo="instrucoes"
                            name="itens[<?= $indice ?>][instrucoes]"
                            rows="2"
                            maxlength="10000"
                            placeholder="Orientações específicas para utilização..."><?= escaparPrescricao($campos['instrucoes']) ?></textarea>

                    </div>

                    <div class="col-12">

                        <div class="accordion" id="accordionDosagem<?= $indice ?>">

                            <div class="accordion-item">

                                <h2 class="accordion-header">

                                    <button
                                        type="button"
                                        class="accordion-button collapsed"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#dosagemJSON<?= $indice ?>">

                                        <span class="bi bi-braces me-2"></span>

                                        Configuração avançada de dosagem (JSON)

                                    </button>

                                </h2>

                                <div
                                    id="dosagemJSON<?= $indice ?>"
                                    class="accordion-collapse collapse"
                                    data-bs-parent="#accordionDosagem<?= $indice ?>">

                                    <div class="accordion-body">

                                        <label class="form-label">
                                            Dados estruturados
                                        </label>

                                        <textarea
                                            class="form-control font-monospace campo-item"
                                            data-campo="dados_dosagem"
                                            name="itens[<?= $indice ?>][dados_dosagem]"
                                            rows="4"
                                            maxlength="10000"
                                            placeholder='{"horarios":["08:00"],"unidades_por_dose":1}'><?= escaparPrescricao($campos['dados_dosagem']) ?></textarea>

                                        <div class="form-text">
                                            Campo opcional para horários, dosadores,
                                            unidades e configurações personalizadas.
                                            Deve conter um objeto ou array JSON válido.
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
<?php
}

/**
 * Renderiza o cartão de um medicamento em modo de consulta.
 */
function renderPrescricaoItemReadonly(array $item): void
{
    $campos = [

        'Nome' => $item['nome_medicamento'] ?? '',

        'Princípio ativo' => $item['principio_ativo'] ?? '',

        'Apresentação' => $item['apresentacao'] ?? '',

        'Concentração' => $item['concentracao'] ?? '',

        'Dosagem' => $item['dosagem'] ?? '',

        'Via de administração' => $item['via_administracao'] ?? '',

        'Frequência' => $item['frequencia'] ?? '',

        'Duração' => $item['duracao'] ?? '',

        'Quantidade' => $item['quantidade'] ?? '',

        'Instruções' => $item['instrucoes'] ?? '',

    ];
?>
    <div class="card card-fito mb-3">

        <div class="card-header">

            <strong>
                <span class="bi bi-capsule me-2"></span>

                <?= escaparPrescricao(
                    $item['nome_medicamento'] ?? ''
                ) ?>
            </strong>

        </div>

        <div class="card-body">

            <div class="row g-3">

                <?php foreach ($campos as $nome => $valor): ?>

                    <?php if (trim((string) $valor) === '') continue; ?>

                    <div class="col-12 col-sm-6 col-lg-4">

                        <div class="small text-fito-muted">
                            <?= escaparPrescricao($nome) ?>
                        </div>

                        <div class="<?= $nome === 'Instruções' ? 'text-clinico' : '' ?>">
                            <?= nl2br(
                                escaparPrescricao($valor)
                            ) ?>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <?php
            $json = $item['dados_dosagem'] ?? null;

            if ($json !== null && $json !== ''):
            ?>

                <div class="mt-3">

                    <div class="small text-fito-muted mb-1">
                        Configuração de dosagem
                    </div>

                    <pre class="bg-light p-3 rounded small mb-0"><?= escaparPrescricao(
                                                                        json_encode(
                                                                            json_decode($json, true),
                                                                            JSON_PRETTY_PRINT
                                                                                | JSON_UNESCAPED_UNICODE
                                                                        )
                                                                    ) ?></pre>

                </div>

            <?php endif; ?>

        </div>

    </div>
    <?php
}


/**
 * Renderiza a página completa de gerenciamento de prescrições.
 *
 * Esta função é chamada por prescricoesPage.php e também utiliza
 * os helpers definidos anteriormente neste mesmo arquivo.
 *
 * @param Prescricao[] $prescricoes
 */


/**
 * Renderiza as linhas da tabela de prescrições.
 *
 * Utilizada tanto no carregamento inicial quanto na pesquisa AJAX.
 *
 * @param Prescricao[] $prescricoes
 */
function renderPrescricaoTableRows(
    array $prescricoes,
    ?int $idEdicao = null,
    string $busca = '',
    int $profissionalFiltro = 0,
    string $situacaoFiltro = '',
    string $dataInicio = '',
    string $dataFim = ''
): void {

    // Escape centralizado para evitar XSS.
    $e = static function ($valor): string {
        return htmlspecialchars(
            (string) ($valor ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
    };

    // Verifica se existem filtros aplicados.
    $filtrosAtivos =
        $busca !== ''
        || $profissionalFiltro > 0
        || $situacaoFiltro !== ''
        || $dataInicio !== ''
        || $dataFim !== '';

    // =====================================================
    // ESTADO VAZIO
    // =====================================================

    if (empty($prescricoes)) {
    ?>

        <tr>
            <td colspan="8" class="text-center py-5">

                <div class="empty-state">

                    <span class="bi bi-prescription2"
                        aria-hidden="true"></span>

                    <?php if ($filtrosAtivos): ?>

                        <p class="mb-0">
                            Nenhuma prescrição encontrada
                            com os filtros aplicados.
                        </p>

                    <?php else: ?>

                        <p class="mb-0">
                            Nenhuma prescrição cadastrada.
                            Crie um rascunho para começar.
                        </p>

                    <?php endif; ?>

                </div>

            </td>
        </tr>

    <?php
        return;
    }

    // =====================================================
    // LINHAS DA TABELA
    // =====================================================

    foreach ($prescricoes as $p):

        $id = (int) ($p->id ?? 0);

        if ($id <= 0) {
            continue;
        }

        $isEdit = $idEdicao !== null
            && $idEdicao === $id;

        // -------------------------------------------------
        // URL DE EDIÇÃO / VISUALIZAÇÃO
        // -------------------------------------------------

        $parametros = [
            'editar' => $id,
        ];

        if ($busca !== '') {
            $parametros['busca'] = $busca;
        }

        if ($profissionalFiltro > 0) {
            $parametros['profissional'] = $profissionalFiltro;
        }

        if ($situacaoFiltro !== '') {
            $parametros['situacao'] = $situacaoFiltro;
        }

        if ($dataInicio !== '') {
            $parametros['data_inicio'] = $dataInicio;
        }

        if ($dataFim !== '') {
            $parametros['data_fim'] = $dataFim;
        }

        $linkEditar = '?' . http_build_query($parametros);

        // -------------------------------------------------
        // RESUMO DA PRESCRIÇÃO
        // -------------------------------------------------

        $resumo = method_exists($p, 'resumo')
            ? $p->resumo()
            : (
                ($p->pacienteNome ?? 'Paciente')
                . ' — '
                . (
                    method_exists($p, 'dataPrescricaoBR')
                    ? $p->dataPrescricaoBR()
                    : 'Data não informada'
                )
            );

        // -------------------------------------------------
        // QUANTIDADE DE MEDICAMENTOS
        // -------------------------------------------------

        if (isset($p->quantidadeItens)) {

            $quantidadeItens = (int) $p->quantidadeItens;
        } elseif (
            isset($p->itens)
            && is_countable($p->itens)
        ) {

            $quantidadeItens = count($p->itens);
        } else {

            // Não inventa uma quantidade quando o DAO
            // não disponibiliza os itens da listagem.
            $quantidadeItens = null;
        }

    ?>

        <tr
            id="prescricao-<?= $id ?>"
            class="<?= $isEdit ? 'table-warning' : '' ?>">

            <!-- ID -->

            <td class="text-fito-muted">
                #<?= $id ?>
            </td>

            <!-- PACIENTE / PROFISSIONAL -->

            <td>

                <strong>
                    <?= $e($p->pacienteNome ?? '—') ?>
                </strong>

                <div class="small text-fito-muted">
                    <?= $e($p->profissionalNome ?? '—') ?>
                </div>

            </td>

            <!-- DATA -->

            <td class="text-nowrap">

                <?= $e(
                    method_exists($p, 'dataPrescricaoBR')
                        ? $p->dataPrescricaoBR()
                        : '—'
                ) ?>

            </td>

            <!-- PRONTUÁRIO -->

            <td>

                <?php if ($p->prontuarioId !== null): ?>

                    <span class="badge rounded-pill text-bg-light border">

                        <span
                            class="bi bi-journal-medical me-1"
                            aria-hidden="true"></span>

                        <?= $e(
                            $p->prontuarioResumo
                                ?? ('Prontuário #' . $p->prontuarioId)
                        ) ?>

                    </span>

                <?php else: ?>

                    <span class="text-fito-muted">
                        Sem vínculo
                    </span>

                <?php endif; ?>

            </td>

            <!-- SITUAÇÃO / ITENS -->

            <td>

                <span class="badge rounded-pill <?= $e(
                                                    $p->situacaoBadgeClass()
                                                ) ?>">

                    <?= $e($p->situacaoLabel()) ?>

                </span>

                <div class="small text-fito-muted mt-1">

                    <?php if ($quantidadeItens !== null): ?>

                        <?= $quantidadeItens ?>
                        <?= $quantidadeItens === 1
                            ? 'medicamento'
                            : 'medicamentos' ?>

                    <?php else: ?>

                        Quantidade indisponível

                    <?php endif; ?>

                </div>

            </td>

            <!-- ASSINATURA -->

            <td>

                <?php if (
                    $p->assinadoEm !== null
                    && trim((string) $p->assinadoEm) !== ''
                ): ?>

                    <span
                        class="small text-success"
                        title="Assinada em <?= $e($p->assinadoEmBR()) ?>">

                        <span
                            class="bi bi-patch-check-fill me-1"
                            aria-hidden="true"></span>

                        <?= $e($p->assinadoEmBR()) ?>

                    </span>

                <?php else: ?>

                    <span class="small text-fito-muted">

                        <span
                            class="bi bi-dash-circle me-1"
                            aria-hidden="true"></span>

                        Não assinada

                    </span>

                <?php endif; ?>

            </td>

            <!-- ATUALIZAÇÃO -->

            <td class="text-fito-muted small">

                <?= $e(
                    method_exists($p, 'atualizadoEmBR')
                        ? $p->atualizadoEmBR()
                        : '—'
                ) ?>

            </td>

            <!-- AÇÕES -->

            <td class="text-end text-nowrap">

                <div
                    class="btn-group btn-group-sm"
                    role="group"
                    aria-label="Ações da prescrição <?= $id ?>">

                    <!-- EDITAR / VISUALIZAR -->

                    <a
                        href="<?= $e($linkEditar) ?>"
                        class="btn btn-outline-secondary"
                        title="<?= $p->podeEditar()
                                    ? 'Editar rascunho'
                                    : 'Visualizar prescrição' ?>">

                        <span
                            class="bi <?= $p->podeEditar()
                                            ? 'bi-pencil-square'
                                            : 'bi-eye' ?>"
                            aria-hidden="true"></span>

                    </a>

                    <!-- EMITIR -->

                    <?php if ($p->podeEmitir()): ?>

                        <button
                            type="button"
                            class="btn btn-success"
                            title="Emitir prescrição"
                            data-bs-toggle="modal"
                            data-bs-target="#modalEmitirPrescricao"
                            data-prescricao-id="<?= $id ?>"
                            data-prescricao-resumo="<?= $e($resumo) ?>">

                            <span
                                class="bi bi-pen"
                                aria-hidden="true"></span>

                        </button>

                    <?php endif; ?>

                    <!-- CANCELAR -->

                    <?php if ($p->podeCancelar()): ?>

                        <button
                            type="button"
                            class="btn btn-outline-danger"
                            title="Cancelar prescrição"
                            data-bs-toggle="modal"
                            data-bs-target="#modalCancelarPrescricao"
                            data-prescricao-id="<?= $id ?>"
                            data-prescricao-resumo="<?= $e($resumo) ?>">

                            <span
                                class="bi bi-x-circle"
                                aria-hidden="true"></span>

                        </button>

                    <?php endif; ?>

                    <!-- EXCLUIR -->

                    <?php if ($p->podeExcluir()): ?>

                        <button
                            type="button"
                            class="btn btn-danger"
                            title="Excluir rascunho"
                            data-bs-toggle="modal"
                            data-bs-target="#modalExcluirPrescricao"
                            data-prescricao-id="<?= $id ?>"
                            data-prescricao-resumo="<?= $e($resumo) ?>">

                            <span
                                class="bi bi-trash"
                                aria-hidden="true"></span>

                        </button>

                    <?php endif; ?>

                </div>

            </td>

        </tr>

    <?php endforeach;
}


function renderPrescricaoGUI(
    array $prescricoes,
    array $mensagens = [],
    ?Prescricao $prescricaoEdicao = null,
    string $busca = '',
    int $profissionalFiltro = 0,
    string $situacaoFiltro = '',
    string $dataInicioFiltro = '',
    string $dataFimFiltro = '',
    array $pacientes = [],
    array $profissionais = [],
    array $prontuariosPaciente = [],
    array $formItens = [],
    string $csrf = ''
): void {

    $isEdicao = $prescricaoEdicao !== null
        && $prescricaoEdicao->id !== null;

    $temRegistro = $prescricaoEdicao !== null;

    $somenteLeitura = $temRegistro
        && $prescricaoEdicao->somenteLeitura();

    $idAtual = $isEdicao
        ? (int) $prescricaoEdicao->id
        : 0;

    $titulo = $somenteLeitura
        ? 'Visualizar prescrição'
        : ($isEdicao ? 'Editar prescrição' : 'Prescrições');

    $dataForm = $temRegistro
        ? $prescricaoEdicao->dataPrescricaoFormInput()
        : date('Y-m-d\TH:i');

    if ($dataForm === '') {
        $dataForm = date('Y-m-d\TH:i');
    }

    $pacienteAtual = $temRegistro
        ? $prescricaoEdicao->pacienteId
        : 0;

    $profissionalAtual = $temRegistro
        ? $prescricaoEdicao->profissionalId
        : 0;

    $prontuarioAtual = $temRegistro
        ? $prescricaoEdicao->prontuarioId
        : null;

    $observacoesAtual = $temRegistro
        ? ($prescricaoEdicao->observacoes ?? '')
        : '';

    $formItens = array_values(array_slice($formItens, 0, 25));

    if (!$somenteLeitura && empty($formItens)) {
        $formItens = [[]];
    }

    $total = count($prescricoes);

    $totalRascunhos = 0;
    $totalEmitidas = 0;
    $totalCanceladas = 0;

    foreach ($prescricoes as $prescricao) {

        switch ($prescricao->situacao) {

            case Prescricao::SITUACAO_RASCUNHO:
                $totalRascunhos++;
                break;

            case Prescricao::SITUACAO_EMITIDA:
                $totalEmitidas++;
                break;

            case Prescricao::SITUACAO_CANCELADA:
                $totalCanceladas++;
                break;
        }
    }

    $filtrosAtivos =
        $busca !== ''
        || $profissionalFiltro > 0
        || $situacaoFiltro !== ''
        || $dataInicioFiltro !== ''
        || $dataFimFiltro !== '';

    $e = 'escaparPrescricao';

    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>
            <?= $e($titulo) ?> — Fito Clinic
        </title>

        <link
            rel="shortcut icon"
            href="../media/img/faviconrhay.png"
            type="image/x-icon">

        <link
            rel="stylesheet"
            href="../media/bootstrap/css/bootstrap.css">

        <link
            rel="stylesheet"
            href="../media/icons/bootstrap-icons.css">

        <link rel="stylesheet" href="../css/styles.css">

        <link rel="stylesheet" href="../css/php.css">

        <style>
            .prescricao-resumo-card {
                min-height: 110px;
            }

            .prescricao-resumo-numero {
                font-size: 1.8rem;
                font-weight: 700;
            }

            .prescricao-formulario {
                scroll-margin-top: 25px;
            }

            .item-prescricao-card {
                border-radius: 12px;
                overflow: hidden;
            }

            .item-prescricao-card .card-header {
                background: var(--cream-2);
            }

            .prescricao-tabela td {
                vertical-align: middle;
            }

            .prescricao-detalhe {
                white-space: pre-wrap;
                overflow-wrap: anywhere;
            }

            .prescricao-status-info {
                background: var(--cream-2);
                border-left: 4px solid var(--green);
                border-radius: 8px;
            }

            @media (max-width: 768px) {

                .prescricao-page-header {
                    align-items: flex-start !important;
                    flex-direction: column;
                }

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

            <!-- CONTEÚDO -->

            <main class="main">

                <header class="topbar">

                    <button
                        class="menu-btn"
                        type="button"
                        id="menuBtn"
                        aria-label="Abrir menu">
                        <span class="bi bi-list"></span>
                    </button>

                    <div>
                        <span class="eyebrow">
                            PRESCRIÇÃO ELETRÔNICA
                        </span>

                        <h1><?= $e($titulo) ?></h1>
                    </div>

                    <div class="top-actions">

                        <button
                            type="button"
                            class="icon-btn"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasAtalhos"
                            title="Atalhos">
                            <span class="bi bi-grid"></span>
                        </button>

                        <button type="button" class="profile-btn">
                            <span class="avatar small"><i class="bi bi-person-circle"></i></span>
                            <span>Conta | Login</span>
                            <span class="bi bi-chevron-down"></span>
                        </button>

                    </div>

                </header>

                <!-- BREADCRUMB -->

                <nav aria-label="breadcrumb">

                    <ol class="breadcrumb breadcrumb-fito">

                        <li class="breadcrumb-item">
                            <a href="../main.php">Início</a>
                        </li>

                        <li class="breadcrumb-item">
                            <a href="prescricoesPage.php">Prescrições</a>
                        </li>

                        <li class="breadcrumb-item active" aria-current="page">
                            <?= $isEdicao ? '#' . $idAtual : 'Novo registro' ?>
                        </li>

                    </ol>

                </nav>

                <!-- MENSAGENS -->

                <?php foreach ($mensagens as $mensagem): ?>

                    <?php
                    $tipo = in_array(
                        $mensagem['tipo'] ?? '',
                        ['sucesso', 'erro'],
                        true
                    ) ? $mensagem['tipo'] : 'erro';

                    $icone = $tipo === 'sucesso'
                        ? 'bi-check-circle-fill'
                        : 'bi-exclamation-triangle-fill';
                    ?>

                    <div
                        class="alert-fito alert-<?= $e($tipo) ?> mb-3"
                        role="alert">

                        <span class="bi <?= $icone ?>"></span>

                        <span>
                            <?= $e($mensagem['texto'] ?? '') ?>
                        </span>

                    </div>

                <?php endforeach; ?>

                <!-- CABEÇALHO -->

                <section class="page-header prescricao-page-header">

                    <div>

                        <span class="eyebrow gold">
                            FITO CLINIC &amp; ESTÉTICA
                        </span>

                        <h1>
                            <?= $isEdicao
                                ? 'Prescrição #' . $idAtual
                                : 'Prescrições da clínica' ?>
                        </h1>

                        <span class="subtitle">
                            Gerenciamento de receitas eletrônicas,
                            medicamentos e dosagens.
                        </span>

                    </div>

                    <?php if ($isEdicao): ?>

                        <a
                            href="prescricoesPage.php"
                            class="btn-fito-outline">
                            <span class="bi bi-arrow-left me-1"></span>
                            Voltar
                        </a>

                    <?php else: ?>

                        <button
                            type="button"
                            class="btn-fito"
                            data-bs-toggle="collapse"
                            data-bs-target="#formPrescricaoContainer">
                            <span class="bi bi-plus-lg me-1"></span>
                            Nova prescrição
                        </button>

                    <?php endif; ?>

                </section>

                <!-- RESUMO -->

                <?php if (!$isEdicao): ?>

                    <section class="row g-3 mb-4">

                        <div class="col-6 col-lg-3">
                            <div class="card card-fito prescricao-resumo-card">
                                <div class="card-body">
                                    <span class="small text-fito-muted">
                                        Registros encontrados
                                    </span>
                                    <div class="prescricao-resumo-numero">
                                        <?= $total ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="card card-fito prescricao-resumo-card">
                                <div class="card-body">
                                    <span class="small text-fito-muted">
                                        Rascunhos
                                    </span>
                                    <div class="prescricao-resumo-numero">
                                        <?= $totalRascunhos ?>
                                    </div>
                                    <span class="badge text-bg-secondary">
                                        Em preparação
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="card card-fito prescricao-resumo-card">
                                <div class="card-body">
                                    <span class="small text-fito-muted">
                                        Emitidas
                                    </span>
                                    <div class="prescricao-resumo-numero">
                                        <?= $totalEmitidas ?>
                                    </div>
                                    <span class="badge text-bg-success">
                                        Formalizadas
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="card card-fito prescricao-resumo-card">
                                <div class="card-body">
                                    <span class="small text-fito-muted">
                                        Canceladas
                                    </span>
                                    <div class="prescricao-resumo-numero">
                                        <?= $totalCanceladas ?>
                                    </div>
                                    <span class="badge text-bg-danger">
                                        Histórico
                                    </span>
                                </div>
                            </div>
                        </div>

                    </section>

                <?php endif; ?>


                <!-- VISUALIZAÇÃO SOMENTE LEITURA -->


                <?php if ($somenteLeitura): ?>

                    <section class="card card-fito mb-4">

                        <div class="card-header d-flex justify-content-between align-items-center">

                            <div>
                                <span class="eyebrow gold">
                                    REGISTRO CLÍNICO
                                </span>

                                <h2 class="m-0">
                                    Prescrição #<?= $idAtual ?>
                                </h2>
                            </div>

                            <span class="badge rounded-pill <?= $e(
                                                                $prescricaoEdicao->situacaoBadgeClass()
                                                            ) ?>">
                                <?= $e($prescricaoEdicao->situacaoLabel()) ?>
                            </span>

                        </div>

                        <div class="card-body">

                            <div class="row g-3 mb-4">

                                <div class="col-md-6">
                                    <div class="small text-fito-muted">Paciente</div>
                                    <strong><?= $e($prescricaoEdicao->pacienteNome) ?></strong>
                                </div>

                                <div class="col-md-6">
                                    <div class="small text-fito-muted">Profissional</div>
                                    <strong><?= $e($prescricaoEdicao->profissionalNome) ?></strong>
                                </div>

                                <div class="col-md-4">
                                    <div class="small text-fito-muted">Data</div>
                                    <strong><?= $e($prescricaoEdicao->dataPrescricaoBR()) ?></strong>
                                </div>

                                <div class="col-md-4">
                                    <div class="small text-fito-muted">Prontuário</div>
                                    <strong>
                                        <?= $e($prescricaoEdicao->prontuarioResumo ?? 'Sem vínculo') ?>
                                    </strong>
                                </div>

                                <div class="col-md-4">
                                    <div class="small text-fito-muted">Medicamentos</div>
                                    <strong><?= count($prescricaoEdicao->itens) ?></strong>
                                </div>

                                <?php if ($prescricaoEdicao->assinadoEm !== null): ?>

                                    <div class="col-12">
                                        <div class="small text-fito-muted">Assinado em</div>
                                        <strong class="text-success">
                                            <span class="bi bi-patch-check-fill me-1"></span>
                                            <?= $e($prescricaoEdicao->assinadoEmBR()) ?>
                                        </strong>
                                    </div>

                                <?php endif; ?>

                            </div>

                            <hr>

                            <h3 class="h5 mb-3">
                                <span class="bi bi-capsule me-2"></span>
                                Medicamentos prescritos
                            </h3>

                            <?php foreach ($prescricaoEdicao->itens as $item): ?>

                                <?php renderPrescricaoItemReadonly($item); ?>

                            <?php endforeach; ?>

                            <?php if (!empty($prescricaoEdicao->observacoes)): ?>

                                <div class="mt-4">

                                    <h3 class="h6">Observações gerais</h3>

                                    <div class="prescricao-detalhe">
                                        <?= nl2br($e($prescricaoEdicao->observacoes)) ?>
                                    </div>

                                </div>

                            <?php endif; ?>

                            <div class="prescricao-status-info p-3 mt-4">

                                <strong>
                                    <span class="bi bi-shield-check me-1"></span>
                                    Registro preservado
                                </strong>

                                <div class="small text-fito-muted">
                                    Esta prescrição está em modo de consulta.
                                    Não é possível alterar seus dados ou medicamentos.
                                </div>

                            </div>

                        </div>

                    </section>

                <?php endif; ?>


                <!-- FORMULÁRIO -->


                <?php if (!$somenteLeitura): ?>

                    <div
                        class="collapse <?= (!$temRegistro || ($isEdicao && !$somenteLeitura)) ? 'show' : '' ?>"
                        id="formPrescricaoContainer">

                        <section
                            class="card card-fito mb-4 prescricao-formulario"
                            id="formularioPrescricao">

                            <div class="card-header d-flex justify-content-between align-items-center">

                                <div>
                                    <span class="eyebrow gold">
                                        <?= $isEdicao ? 'EDIÇÃO' : 'CADASTRO' ?>
                                    </span>

                                    <h2 class="m-0">
                                        <?= $isEdicao
                                            ? 'Editar rascunho'
                                            : 'Nova prescrição eletrônica' ?>
                                    </h2>
                                </div>

                                <span class="badge text-bg-secondary">
                                    Rascunho
                                </span>

                            </div>

                            <div class="card-body">

                                <form
                                    method="POST"
                                    action="prescricoesPage.php"
                                    class="form-fito"
                                    id="formPrescricao">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= $e($csrf) ?>">

                                    <input
                                        type="hidden"
                                        name="acao"
                                        value="<?= $isEdicao ? 'atualizar' : 'criar' ?>">

                                    <?php if ($isEdicao): ?>

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $idAtual ?>">

                                    <?php endif; ?>

                                    <!-- IDENTIFICAÇÃO -->

                                    <h3 class="h5 mb-3">
                                        <span class="bi bi-clipboard2-pulse me-2"></span>
                                        Identificação
                                    </h3>

                                    <div class="row g-3 mb-4">

                                        <div class="col-12 col-lg-6">

                                            <label for="paciente_id" class="form-label">
                                                Paciente *
                                            </label>

                                            <select
                                                class="form-select"
                                                name="paciente_id"
                                                id="paciente_id"
                                                required>

                                                <option value="">
                                                    — Selecione o paciente —
                                                </option>

                                                <?php foreach ($pacientes as $paciente): ?>

                                                    <option
                                                        value="<?= (int) $paciente['id'] ?>"
                                                        <?= $pacienteAtual === (int) $paciente['id']
                                                            ? 'selected'
                                                            : '' ?>>
                                                        <?= $e($paciente['nome']) ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </div>

                                        <div class="col-12 col-lg-6">

                                            <label for="profissional_id" class="form-label">
                                                Profissional responsável *
                                            </label>

                                            <select
                                                class="form-select"
                                                name="profissional_id"
                                                id="profissional_id"
                                                required>

                                                <option value="">
                                                    — Selecione o profissional —
                                                </option>

                                                <?php foreach ($profissionais as $profissional): ?>

                                                    <option
                                                        value="<?= (int) $profissional['id'] ?>"
                                                        <?= $profissionalAtual === (int) $profissional['id']
                                                            ? 'selected'
                                                            : '' ?>>
                                                        <?= $e($profissional['nome']) ?>
                                                        <?= !empty($profissional['numero_registro'])
                                                            ? ' — ' . $e($profissional['tipo_registro'] ?? '') . ' ' . $e($profissional['numero_registro'])
                                                            : '' ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </div>

                                        <div class="col-12 col-md-6">

                                            <label for="data_prescricao" class="form-label">
                                                Data e horário *
                                            </label>

                                            <input
                                                type="datetime-local"
                                                class="form-control"
                                                name="data_prescricao"
                                                id="data_prescricao"
                                                value="<?= $e($dataForm) ?>"
                                                max="<?= date('Y-m-d\TH:i') ?>"
                                                required>

                                        </div>

                                        <div class="col-12 col-md-6">

                                            <label for="prontuario_id" class="form-label">
                                                Prontuário vinculado
                                            </label>

                                            <select
                                                class="form-select"
                                                name="prontuario_id"
                                                id="prontuario_id">

                                                <option value="">
                                                    — Sem vínculo com prontuário —
                                                </option>

                                                <?php foreach ($prontuariosPaciente as $prontuario): ?>

                                                    <option
                                                        value="<?= (int) $prontuario['id'] ?>"
                                                        <?= $prontuarioAtual === (int) $prontuario['id']
                                                            ? 'selected'
                                                            : '' ?>>
                                                        <?= $e(
                                                            formatarPrescricaoProntuarioOption($prontuario)
                                                        ) ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                            <div class="form-text">
                                                A lista acompanha o paciente selecionado.
                                            </div>

                                        </div>

                                    </div>

                                    <hr>

                                    <!-- MEDICAMENTOS -->

                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

                                        <div>
                                            <h3 class="h5 mb-1">
                                                <span class="bi bi-capsule me-2"></span>
                                                Medicamentos e dosagens
                                            </h3>

                                            <small class="text-fito-muted">
                                                Cadastre os medicamentos desta prescrição.
                                            </small>
                                        </div>

                                        <button
                                            type="button"
                                            class="btn btn-outline-success"
                                            id="adicionarMedicamento">
                                            <span class="bi bi-plus-circle me-1"></span>
                                            Adicionar medicamento
                                        </button>

                                    </div>

                                    <div class="alert alert-info small">
                                        <span class="bi bi-info-circle me-1"></span>
                                        Informe pelo menos um medicamento.
                                        Cada medicamento possui configuração individual.
                                    </div>

                                    <div id="listaMedicamentos" class="row g-0">

                                        <?php foreach ($formItens as $indice => $item): ?>

                                            <?php renderPrescricaoItemFields(
                                                $item,
                                                (int) $indice
                                            ); ?>

                                        <?php endforeach; ?>

                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-4">

                                        <small class="text-fito-muted">
                                            Limite de 25 medicamentos por prescrição.
                                        </small>

                                        <span
                                            class="badge text-bg-secondary"
                                            id="contadorMedicamentos">
                                            <?= count($formItens) ?> itens
                                        </span>

                                    </div>

                                    <!-- OBSERVAÇÕES -->

                                    <hr>

                                    <h3 class="h5 mb-3">
                                        <span class="bi bi-journal-text me-2"></span>
                                        Observações gerais
                                    </h3>

                                    <textarea
                                        class="form-control mb-4"
                                        name="observacoes"
                                        id="observacoes"
                                        rows="4"
                                        maxlength="10000"
                                        placeholder="Informações complementares..."><?= $e($observacoesAtual) ?></textarea>

                                    <!-- REGRAS -->

                                    <div class="accordion mb-4" id="accordionRegrasPrescricao">

                                        <div class="accordion-item">

                                            <h2 class="accordion-header">

                                                <button
                                                    type="button"
                                                    class="accordion-button collapsed"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#regrasPrescricao">
                                                    <span class="bi bi-shield-check me-2"></span>
                                                    Regras de negócio
                                                </button>

                                            </h2>

                                            <div
                                                id="regrasPrescricao"
                                                class="accordion-collapse collapse"
                                                data-bs-parent="#accordionRegrasPrescricao">

                                                <div class="accordion-body small">

                                                    <ul class="mb-0">
                                                        <li>Novas prescrições começam como rascunho.</li>
                                                        <li>É obrigatório cadastrar pelo menos um medicamento.</li>
                                                        <li>O prontuário deve pertencer ao paciente selecionado.</li>
                                                        <li>Datas futuras não são permitidas.</li>
                                                        <li>Prescrições emitidas não podem ser editadas.</li>
                                                        <li>O cancelamento preserva o histórico.</li>
                                                    </ul>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                    <!-- AÇÕES -->

                                    <div class="d-flex justify-content-between flex-wrap gap-2">

                                        <a
                                            href="prescricoesPage.php"
                                            class="btn-fito-outline">
                                            <span class="bi bi-x-lg me-1"></span>
                                            Cancelar
                                        </a>

                                        <button type="submit" class="btn-fito">

                                            <span class="bi bi-save me-1"></span>

                                            <?= $isEdicao
                                                ? 'Salvar alterações'
                                                : 'Salvar rascunho' ?>

                                        </button>

                                    </div>

                                </form>

                            </div>

                        </section>

                    </div>

                <?php endif; ?>


                <!-- LISTAGEM -->


                <section class="card card-fito mb-4">

                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">

                        <div>
                            <span class="eyebrow gold" id="listaEyebrow">
                                <?= $filtrosAtivos ? 'RESULTADOS' : 'PRESCRIÇÕES' ?>
                            </span>

                            <h2 class="m-0" id="tituloListaPrescricoes">
                                <?= $filtrosAtivos
                                    ? 'Prescrições encontradas'
                                    : 'Registros cadastrados' ?>
                            </h2>
                        </div>

                        <span
                            class="badge rounded-pill text-bg-success"
                            id="prescricoesCount">
                            <?= $total ?>
                            <?= $total === 1 ? 'registro' : 'registros' ?>
                        </span>

                    </div>

                    <div class="card-body">

                        <!-- FILTROS -->

                        <div class="row g-3 mb-4">

                            <div class="col-12 col-lg-4">

                                <label for="buscaInput" class="form-label">
                                    Pesquisar
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        <span class="bi bi-search"></span>
                                    </span>

                                    <input
                                        type="search"
                                        id="buscaInput"
                                        class="form-control"
                                        value="<?= $e($busca) ?>"
                                        placeholder="Paciente, medicamento, profissional ou ID">

                                </div>

                            </div>

                            <div class="col-12 col-sm-6 col-lg-2">

                                <label for="profissionalFilter" class="form-label">
                                    Profissional
                                </label>

                                <select id="profissionalFilter" class="form-select">

                                    <option value="">Todos</option>

                                    <?php foreach ($profissionais as $profissional): ?>

                                        <option
                                            value="<?= (int) $profissional['id'] ?>"
                                            <?= $profissionalFiltro === (int) $profissional['id']
                                                ? 'selected'
                                                : '' ?>>
                                            <?= $e($profissional['nome']) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <div class="col-12 col-sm-6 col-lg-2">

                                <label for="situacaoFilter" class="form-label">
                                    Situação
                                </label>

                                <select id="situacaoFilter" class="form-select">

                                    <option value="">Todas</option>

                                    <?php foreach (Prescricao::SITUACOES as $codigo => $rotulo): ?>

                                        <option
                                            value="<?= $e($codigo) ?>"
                                            <?= $situacaoFiltro === $codigo
                                                ? 'selected'
                                                : '' ?>>
                                            <?= $e($rotulo) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <div class="col-6 col-lg-1">

                                <label for="dataInicioFilter" class="form-label">
                                    De
                                </label>

                                <input
                                    type="date"
                                    id="dataInicioFilter"
                                    class="form-control"
                                    value="<?= $e($dataInicioFiltro) ?>">

                            </div>

                            <div class="col-6 col-lg-1">

                                <label for="dataFimFilter" class="form-label">
                                    Até
                                </label>

                                <input
                                    type="date"
                                    id="dataFimFilter"
                                    class="form-control"
                                    value="<?= $e($dataFimFiltro) ?>">

                            </div>

                            <div class="col-12 col-lg-2 d-flex align-items-end gap-2">

                                <button
                                    type="button"
                                    id="hojeBtn"
                                    class="btn btn-outline-secondary flex-fill">
                                    <span class="bi bi-calendar-day me-1"></span>
                                    Hoje
                                </button>

                                <button
                                    type="button"
                                    id="limparFiltros"
                                    class="btn btn-outline-secondary"
                                    title="Limpar filtros">
                                    <span class="bi bi-eraser"></span>
                                </button>

                            </div>

                        </div>

                        <!-- TABELA -->

                        <div class="table-responsive">

                            <table class="table table-hover table-fito align-middle mb-0 prescricao-tabela">

                                <thead>

                                    <tr>
                                        <th>ID</th>
                                        <th>Paciente / Profissional</th>
                                        <th>Data</th>
                                        <th>Prontuário</th>
                                        <th>Situação / Itens</th>
                                        <th>Assinatura</th>
                                        <th>Atualização</th>
                                        <th class="text-end">Ações</th>
                                    </tr>

                                </thead>

                                <tbody id="prescricoesTbody">

                                    <?php renderPrescricaoTableRows(
                                        $prescricoes,
                                        $isEdicao ? $idAtual : null,
                                        $busca,
                                        $profissionalFiltro,
                                        $situacaoFiltro,
                                        $dataInicioFiltro,
                                        $dataFimFiltro
                                    ); ?>

                                </tbody>

                            </table>

                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">

                            <small class="text-fito-muted">
                                <span class="bi bi-arrow-repeat me-1"></span>
                                Pesquisa instantânea via AJAX.
                            </small>

                            <a
                                href="itens_prescricaoPage.php"
                                class="btn btn-sm btn-outline-secondary">
                                <span class="bi bi-list-check me-1"></span>
                                Gerenciar itens
                            </a>

                        </div>

                    </div>

                </section>

                <footer class="small text-fito-muted text-center py-3">
                    Fito Clinic &amp; Estética · Gestão de prescrições
                </footer>

            </main>

        </div>


        <div
            class="offcanvas offcanvas-end"
            tabindex="-1"
            id="offcanvasAtalhos"
            aria-labelledby="offcanvasAtalhosLabel">

            <div class="offcanvas-header">

                <h5 class="offcanvas-title" id="offcanvasAtalhosLabel">
                    Atalhos da clínica
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="offcanvas"
                    aria-label="Fechar"></button>

            </div>

            <div class="offcanvas-body">

                <div class="list-group list-group-flush">

                    <a class="list-group-item list-group-item-action" href="pacientesPage.php">
                        <span class="bi bi-people me-2"></span>
                        Pacientes
                    </a>

                    <a class="list-group-item list-group-item-action" href="prontuariosPage.php">
                        <span class="bi bi-file-medical me-2"></span>
                        Prontuários
                    </a>

                    <a class="list-group-item list-group-item-action active" href="prescricoesPage.php">
                        <span class="bi bi-prescription2 me-2"></span>
                        Prescrições
                    </a>

                    <a class="list-group-item list-group-item-action" href="itens_prescricaoPage.php">
                        <span class="bi bi-list-check me-2"></span>
                        Itens de prescrição
                    </a>

                    <a class="list-group-item list-group-item-action" href="itens_estoquePage.php">
                        <span class="bi bi-box-seam me-2"></span>
                        Estoque
                    </a>

                </div>

            </div>

        </div>


        <div
            class="modal fade"
            id="modalEmitirPrescricao"
            tabindex="-1"
            aria-labelledby="modalEmitirLabel"
            aria-hidden="true">

            <div class="modal-dialog modal-lg modal-dialog-centered">

                <div class="modal-content">

                    <form method="POST" action="prescricoesPage.php">

                        <div class="modal-header">

                            <h5 class="modal-title" id="modalEmitirLabel">
                                <span class="bi bi-patch-check me-2"></span>
                                Emitir prescrição
                            </h5>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Fechar"></button>

                        </div>

                        <div class="modal-body">

                            <input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>">

                            <input type="hidden" name="acao" value="emitir">

                            <input
                                type="hidden"
                                name="id"
                                id="idPrescricaoEmitir">

                            <p id="resumoPrescricaoEmitir"></p>

                            <label for="assinatura_digital" class="form-label">
                                Payload de assinatura digital *
                            </label>

                            <textarea
                                name="assinatura_digital"
                                id="assinatura_digital"
                                class="form-control font-monospace"
                                rows="5"
                                maxlength="60000"
                                required
                                placeholder="Payload retornado pelo serviço de assinatura..."></textarea>

                            <div class="form-text">
                                A validação criptográfica deve ocorrer pelo
                                serviço de assinatura antes da emissão.
                            </div>

                            <div class="alert alert-warning small mt-3 mb-0">
                                Após a emissão, os dados da prescrição ficam
                                bloqueados para edição.
                            </div>

                        </div>

                        <div class="modal-footer">

                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">
                                Voltar
                            </button>

                            <button type="submit" class="btn btn-success">
                                <span class="bi bi-check2-circle me-1"></span>
                                Confirmar emissão
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

        <div
            class="modal fade"
            id="modalCancelarPrescricao"
            tabindex="-1"
            aria-labelledby="modalCancelarLabel"
            aria-hidden="true">

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">

                    <form method="POST" action="prescricoesPage.php">

                        <div class="modal-header">

                            <h5 class="modal-title" id="modalCancelarLabel">
                                Cancelar prescrição
                            </h5>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Fechar"></button>

                        </div>

                        <div class="modal-body">

                            <input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>">

                            <input type="hidden" name="acao" value="cancelar">

                            <input
                                type="hidden"
                                name="id"
                                id="idPrescricaoCancelar">

                            <p>Confirma o cancelamento de:</p>

                            <strong id="resumoPrescricaoCancelar"></strong>

                            <div class="alert alert-warning small mt-3 mb-0">
                                O histórico e os medicamentos serão preservados.
                            </div>

                        </div>

                        <div class="modal-footer">

                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">
                                Voltar
                            </button>

                            <button type="submit" class="btn btn-danger">
                                Confirmar cancelamento
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

        <div
            class="modal fade"
            id="modalExcluirPrescricao"
            tabindex="-1"
            aria-labelledby="modalExcluirLabel"
            aria-hidden="true">

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">

                    <form method="POST" action="prescricoesPage.php">

                        <div class="modal-header">

                            <h5 class="modal-title" id="modalExcluirLabel">
                                Excluir rascunho
                            </h5>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Fechar"></button>

                        </div>

                        <div class="modal-body">

                            <input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>">

                            <input type="hidden" name="acao" value="excluir">

                            <input
                                type="hidden"
                                name="id"
                                id="idPrescricaoExcluir">

                            <p>Tem certeza de que deseja excluir:</p>

                            <strong id="resumoPrescricaoExcluir"></strong>

                            <div class="alert alert-danger small mt-3 mb-0">
                                Os medicamentos vinculados serão excluídos
                                pelo relacionamento ON DELETE CASCADE.
                            </div>

                        </div>

                        <div class="modal-footer">

                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">
                                Cancelar
                            </button>

                            <button type="submit" class="btn btn-danger">
                                <span class="bi bi-trash me-1"></span>
                                Excluir
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>


        <script src="../media/bootstrap/js/bootstrap.js"></script>

        <script src="../js/app.js"></script>

        <script>
            (() => {

                'use strict';

                // -----------------------------------------
                // MODAIS
                // -----------------------------------------

                function configurarModal(modalId, inputId, resumoId) {

                    const modal = document.getElementById(modalId);

                    if (!modal) return;

                    modal.addEventListener('show.bs.modal', event => {

                        const botao = event.relatedTarget;

                        if (!botao) return;

                        document.getElementById(inputId).value =
                            botao.getAttribute('data-prescricao-id') || '';

                        document.getElementById(resumoId).textContent =
                            botao.getAttribute('data-prescricao-resumo') || '';

                    });
                }

                configurarModal(
                    'modalEmitirPrescricao',
                    'idPrescricaoEmitir',
                    'resumoPrescricaoEmitir'
                );

                configurarModal(
                    'modalCancelarPrescricao',
                    'idPrescricaoCancelar',
                    'resumoPrescricaoCancelar'
                );

                configurarModal(
                    'modalExcluirPrescricao',
                    'idPrescricaoExcluir',
                    'resumoPrescricaoExcluir'
                );

                // -----------------------------------------
                // SCROLL DE EDIÇÃO
                // -----------------------------------------

                <?php if ($isEdicao && !$somenteLeitura): ?>

                    document.getElementById('formularioPrescricao')
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });

                <?php endif; ?>

                // -----------------------------------------
                // REPETIDOR DE MEDICAMENTOS
                // -----------------------------------------

                const lista = document.getElementById(
                    'listaMedicamentos'
                );

                const adicionar = document.getElementById(
                    'adicionarMedicamento'
                );

                const contador = document.getElementById(
                    'contadorMedicamentos'
                );

                function atualizarIndices() {

                    const itens = [
                        ...lista.querySelectorAll('.item-prescricao')
                    ];

                    itens.forEach((item, indice) => {

                        item.querySelectorAll('[data-campo]')
                            .forEach(campo => {

                                campo.name =
                                    `itens[${indice}][${campo.dataset.campo}]`;

                            });

                        const numero = item.querySelector(
                            '[data-numero-item]'
                        );

                        if (numero) {
                            numero.textContent = indice + 1;
                        }

                        const accordion = item.querySelector(
                            '.accordion[id^="accordionDosagem"]'
                        );

                        const collapse = item.querySelector(
                            '.accordion-collapse[id^="dosagemJSON"]'
                        );

                        const botao = item.querySelector(
                            '.accordion-button[data-bs-target]'
                        );

                        if (accordion && collapse && botao) {

                            const accordionId =
                                `accordionDosagem${indice}`;

                            const collapseId =
                                `dosagemJSON${indice}`;

                            accordion.id = accordionId;

                            collapse.id = collapseId;

                            collapse.setAttribute(
                                'data-bs-parent',
                                '#' + accordionId
                            );

                            botao.setAttribute(
                                'data-bs-target',
                                '#' + collapseId
                            );

                            botao.setAttribute(
                                'aria-controls',
                                collapseId
                            );
                        }

                        const remover = item.querySelector(
                            '.btn-remover-item'
                        );

                        if (remover) {
                            remover.disabled = itens.length <= 1;
                        }

                    });

                    contador.textContent =
                        itens.length + (
                            itens.length === 1 ? ' item' : ' itens'
                        );

                    adicionar.disabled = itens.length >= 25;

                }

                function criarMedicamento() {

                    const existentes = lista.querySelectorAll(
                        '.item-prescricao'
                    );

                    if (existentes.length >= 25) {
                        return;
                    }

                    const primeiro = existentes[0];

                    if (!primeiro) {
                        return;
                    }

                    const novo = primeiro.cloneNode(true);

                    novo.querySelectorAll('input, textarea')
                        .forEach(campo => {

                            campo.value = '';

                        });

                    novo.querySelectorAll('.accordion-collapse')
                        .forEach(collapse => {

                            collapse.classList.remove('show');

                        });

                    novo.querySelectorAll('.accordion-button')
                        .forEach(botao => {

                            botao.classList.add('collapsed');

                            botao.setAttribute(
                                'aria-expanded',
                                'false'
                            );

                        });

                    lista.appendChild(novo);

                    atualizarIndices();

                    novo.querySelector(
                        '[data-campo="nome_medicamento"]'
                    )?.focus();

                }

                adicionar?.addEventListener(
                    'click',
                    criarMedicamento
                );

                lista?.addEventListener('click', event => {

                    const botao = event.target.closest(
                        '.btn-remover-item'
                    );

                    if (!botao) return;

                    const itens = lista.querySelectorAll(
                        '.item-prescricao'
                    );

                    if (itens.length <= 1) {
                        return;
                    }

                    botao.closest('.item-prescricao').remove();

                    atualizarIndices();

                });

                atualizarIndices();

                // -----------------------------------------
                // VALIDAR JSON ANTES DO POST
                // -----------------------------------------

                document.getElementById('formPrescricao')
                    ?.addEventListener('submit', event => {

                        const camposJSON = lista.querySelectorAll(
                            '[data-campo="dados_dosagem"]'
                        );

                        for (const campo of camposJSON) {

                            const valor = campo.value.trim();

                            if (valor === '') {
                                continue;
                            }

                            try {

                                const dados = JSON.parse(valor);

                                if (
                                    dados === null ||
                                    typeof dados !== 'object'
                                ) {
                                    throw new Error(
                                        'Informe um objeto ou array JSON.'
                                    );
                                }

                            } catch (erro) {

                                event.preventDefault();

                                alert(
                                    'Configuração de dosagem inválida: ' +
                                    erro.message
                                );

                                campo.focus();

                                return;
                            }

                        }

                    });

                // -----------------------------------------
                // PRONTUÁRIOS DINÂMICOS
                // -----------------------------------------

                const pacienteSelect = document.getElementById(
                    'paciente_id'
                );

                const prontuarioSelect = document.getElementById(
                    'prontuario_id'
                );

                pacienteSelect?.addEventListener('change', async () => {

                    const pacienteId = pacienteSelect.value;

                    prontuarioSelect.innerHTML =
                        '<option value="">— Sem vínculo com prontuário —</option>';

                    if (!pacienteId) {
                        return;
                    }

                    prontuarioSelect.disabled = true;

                    try {

                        const response = await fetch(
                            'prescricoesSearchAjax.php?mode=prontuarios&paciente_id=' +
                            encodeURIComponent(pacienteId), {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }
                        );

                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }

                        const dados = await response.json();

                        prontuarioSelect.insertAdjacentHTML(
                            'beforeend',
                            dados.options || ''
                        );

                    } catch (erro) {

                        console.error(
                            'Erro ao carregar prontuários:',
                            erro
                        );

                    } finally {

                        prontuarioSelect.disabled = false;

                    }

                });

                // -----------------------------------------
                // PESQUISA AJAX
                // -----------------------------------------

                const busca = document.getElementById('buscaInput');

                const profissional = document.getElementById(
                    'profissionalFilter'
                );

                const situacao = document.getElementById(
                    'situacaoFilter'
                );

                const dataInicio = document.getElementById(
                    'dataInicioFilter'
                );

                const dataFim = document.getElementById(
                    'dataFimFilter'
                );

                const tbody = document.getElementById(
                    'prescricoesTbody'
                );

                const count = document.getElementById(
                    'prescricoesCount'
                );

                const eyebrow = document.getElementById(
                    'listaEyebrow'
                );

                const titulo = document.getElementById(
                    'tituloListaPrescricoes'
                );

                let timer = null;

                let controller = null;

                function filtrosAtivos() {

                    return busca.value.trim() !== '' ||
                        profissional.value !== '' ||
                        situacao.value !== '' ||
                        dataInicio.value !== '' ||
                        dataFim.value !== '';

                }

                function atualizarCabecalho(total) {

                    const filtrado = filtrosAtivos();

                    eyebrow.textContent = filtrado ?
                        'RESULTADOS' :
                        'PRESCRIÇÕES';

                    titulo.textContent = filtrado ?
                        'Prescrições encontradas' :
                        'Registros cadastrados';

                    count.textContent =
                        total + (
                            total === 1 ? ' registro' : ' registros'
                        );

                }

                async function pesquisar() {

                    if (controller) {
                        controller.abort();
                    }

                    controller = new AbortController();

                    const parametros = new URLSearchParams({

                        busca: busca.value.trim(),

                        profissional: profissional.value,

                        situacao: situacao.value,

                        data_inicio: dataInicio.value,

                        data_fim: dataFim.value

                    });

                    try {

                        const response = await fetch(
                            'prescricoesSearchAjax.php?' +
                            parametros.toString(), {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                signal: controller.signal
                            }
                        );

                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }

                        const dados = await response.json();

                        tbody.innerHTML = dados.html || '';

                        atualizarCabecalho(
                            Number(dados.total) || 0
                        );

                    } catch (erro) {

                        if (erro.name !== 'AbortError') {

                            console.error(
                                'Erro na busca de prescrições:',
                                erro
                            );

                        }

                    }

                }

                function debouncePesquisa() {

                    clearTimeout(timer);

                    timer = setTimeout(
                        pesquisar,
                        300
                    );

                }

                busca?.addEventListener(
                    'input',
                    debouncePesquisa
                );

                profissional?.addEventListener(
                    'change',
                    pesquisar
                );

                situacao?.addEventListener(
                    'change',
                    pesquisar
                );

                dataInicio?.addEventListener(
                    'change',
                    pesquisar
                );

                dataFim?.addEventListener(
                    'change',
                    pesquisar
                );

                // -----------------------------------------
                // FILTRO HOJE
                // -----------------------------------------

                document.getElementById('hojeBtn')
                    ?.addEventListener('click', () => {

                        const agora = new Date();

                        const hoje =
                            agora.getFullYear() +
                            '-' +
                            String(
                                agora.getMonth() + 1
                            ).padStart(2, '0') +
                            '-' +
                            String(
                                agora.getDate()
                            ).padStart(2, '0');

                        dataInicio.value = hoje;

                        dataFim.value = hoje;

                        pesquisar();

                    });

                // -----------------------------------------
                // LIMPAR FILTROS
                // -----------------------------------------

                document.getElementById('limparFiltros')
                    ?.addEventListener('click', () => {

                        busca.value = '';

                        profissional.value = '';

                        situacao.value = '';

                        dataInicio.value = '';

                        dataFim.value = '';

                        pesquisar();

                    });

            })();
        </script>

    </body>

    </html>
<?php
}
