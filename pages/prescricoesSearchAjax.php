<?php
// pages/prescricoesSearchAjax.php

declare(strict_types=1);

require_once __DIR__ . '/../configs/authGuard.php';
require_once __DIR__ . '/../configs/cors.php';

require_once __DIR__ . '/../controller/prescricoesDAO.php';
require_once __DIR__ . '/../views/prescricoesGUI.php';

header('Content-Type: application/json; charset=utf-8');

header('Cache-Control: no-store, no-cache, must-revalidate');

try {

    $dao = new PrescricaoDAO();

    $mode = isset($_GET['mode']) && is_string($_GET['mode'])
        ? $_GET['mode']
        : 'rows';

    // =====================================================
    // MODE: PRONTUARIOS
    // =====================================================

    /**
     * Retorna os prontuários pertencentes ao paciente.
     *
     * URL:
     * prescricoesSearchAjax.php?mode=prontuarios&paciente_id=1
     */
    if ($mode === 'prontuarios') {

        $pacienteId = filter_var(
            $_GET['paciente_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $pacienteId === false
            || $pacienteId <= 0
            || !$dao->pacienteExiste((int) $pacienteId)
        ) {

            echo json_encode(
                [
                    'options' => '',
                    'total' => 0,
                ],
                JSON_UNESCAPED_UNICODE
            );

            exit;
        }

        $lista = $dao->listarProntuariosPorPaciente(
            (int) $pacienteId
        );

        $options = '';

        foreach ($lista as $prontuario) {

            $id = (int) $prontuario['id'];

            $label = formatarPrescricaoProntuarioOption(
                $prontuario
            );

            $options .=
                '<option value="' . $id . '">'
                . htmlspecialchars(
                    $label,
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '</option>';
        }

        echo json_encode(
            [
                'options' => $options,
                'total' => count($lista),
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    // =====================================================
    // MODE: ROWS
    // =====================================================

    if ($mode !== 'rows') {

        http_response_code(400);

        echo json_encode(
            [
                'erro' => 'Modo de pesquisa inválido.',
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    // Busca textual.
    $busca = isset($_GET['busca']) && is_string($_GET['busca'])
        ? trim($_GET['busca'])
        : '';

    // Profissional.
    $profissional = filter_var(
        $_GET['profissional'] ?? 0,
        FILTER_VALIDATE_INT
    );

    $profissionalId = $profissional !== false
        ? max(0, (int) $profissional)
        : 0;

    // Situação.
    $situacao = isset($_GET['situacao'])
        && is_string($_GET['situacao'])
        ? trim($_GET['situacao'])
        : '';

    if (!array_key_exists(
        $situacao,
        Prescricao::SITUACOES
    )) {

        $situacao = '';
    }

    // Intervalo de datas.
    $dataInicio = isset($_GET['data_inicio'])
        && is_string($_GET['data_inicio'])
        ? trim($_GET['data_inicio'])
        : '';

    $dataFim = isset($_GET['data_fim'])
        && is_string($_GET['data_fim'])
        ? trim($_GET['data_fim'])
        : '';

    // Pesquisa otimizada.
    $prescricoes = $dao->buscarComFiltros(
        $busca,
        $profissionalId,
        $situacao,
        $dataInicio,
        $dataFim
    );

    // Renderiza somente as linhas.
    ob_start();

    renderPrescricaoTableRows(
        $prescricoes,
        null,
        $busca,
        $profissionalId,
        $situacao,
        $dataInicio,
        $dataFim
    );

    $html = ob_get_clean();

    echo json_encode(
        [
            'html' => $html,
            'total' => count($prescricoes),
        ],
        JSON_UNESCAPED_UNICODE
    );
} catch (Throwable $e) {

    error_log(
        '[Prescricoes AJAX] '
            . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode(
        [
            'erro' => 'Não foi possível realizar a pesquisa.',
        ],
        JSON_UNESCAPED_UNICODE
    );
}
