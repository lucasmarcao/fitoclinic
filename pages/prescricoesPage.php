<?php
// pages/prescricoesPage.php

declare(strict_types=1);

require_once __DIR__ . '/../configs/authGuard.php';
require_once __DIR__ . '/../configs/cors.php';

require_once __DIR__ . '/../controller/prescricoesDAO.php';
require_once __DIR__ . '/../views/prescricoesGUI.php';

// ---------------------------------------------------------
// SESSÃO
// ---------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------
// CSRF
// ---------------------------------------------------------

function prescricoesCsrfToken(): string
{
    if (empty($_SESSION['csrf_token_prescricoes'])) {

        $_SESSION['csrf_token_prescricoes'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token_prescricoes'];
}

function prescricoesCsrfValido(?string $token): bool
{
    return isset($_SESSION['csrf_token_prescricoes'])
        && is_string($token)
        && $token !== ''
        && hash_equals(
            $_SESSION['csrf_token_prescricoes'],
            $token
        );
}

// ---------------------------------------------------------
// INICIALIZAÇÃO
// ---------------------------------------------------------

$dao = new PrescricaoDAO();

$mensagens = [];

$prescricaoEdicao = null;

$formItens = [];

$busca = isset($_GET['busca']) && is_string($_GET['busca'])
    ? trim($_GET['busca'])
    : '';

$profissionalFiltro = filter_var(
    $_GET['profissional'] ?? 0,
    FILTER_VALIDATE_INT
);

$profissionalFiltro = max(
    0,
    (int) ($profissionalFiltro ?: 0)
);

$situacaoFiltro = isset($_GET['situacao'])
    && is_string($_GET['situacao'])
    ? trim($_GET['situacao'])
    : '';

if (!array_key_exists(
    $situacaoFiltro,
    Prescricao::SITUACOES
)) {
    $situacaoFiltro = '';
}

$dataInicioFiltro = isset($_GET['data_inicio'])
    && is_string($_GET['data_inicio'])
    ? trim($_GET['data_inicio'])
    : '';

$dataFimFiltro = isset($_GET['data_fim'])
    && is_string($_GET['data_fim'])
    ? trim($_GET['data_fim'])
    : '';

// ---------------------------------------------------------
// HELPERS DE VALIDAÇÃO
// ---------------------------------------------------------

/**
 * Lê apenas valores escalares enviados pelo formulário.
 */
$lerPost = static function (
    string $campo,
    string $padrao = ''
): string {

    if (!isset($_POST[$campo])) {
        return $padrao;
    }

    if (!is_string($_POST[$campo])) {
        return $padrao;
    }

    return trim($_POST[$campo]);
};

/**
 * Converte string vazia em NULL.
 */
$nullSeVazio = static function (string $valor): ?string {

    return $valor !== ''
        ? $valor
        : null;
};

/**
 * Valida comprimento de campos textuais.
 */
$validarComprimento = static function (
    string $valor,
    int $maximo,
    string $nome
): ?string {

    if (mb_strlen($valor, 'UTF-8') > $maximo) {

        return "{$nome} excede o limite de {$maximo} caracteres.";
    }

    return null;
};

/**
 * Valida e converte datetime-local para DATETIME MySQL.
 *
 * Formato:
 * Y-m-d\TH:i
 */
$converterDataPrescricao = static function (
    string $valor
): ?string {

    if (
        !preg_match(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/',
            $valor
        )
    ) {
        return null;
    }

    $dt = DateTime::createFromFormat(
        '!Y-m-d\TH:i',
        $valor
    );

    if ($dt === false) {
        return null;
    }

    $errosData = DateTime::getLastErrors();

    if (
        $errosData !== false
        && (
            $errosData['warning_count'] > 0
            || $errosData['error_count'] > 0
        )
    ) {
        return null;
    }

    if ($dt->format('Y-m-d\TH:i') !== $valor) {
        return null;
    }

    return $dt->format('Y-m-d H:i:s');
};

/**
 * Normaliza os medicamentos enviados pelo formulário.
 *
 * @return array{
 *   itens: array,
 *   erros: array
 * }
 */
$processarItens = static function (
    mixed $entrada
): array {

    $itens = [];
    $erros = [];

    if (!is_array($entrada)) {

        return [
            'itens' => [],
            'erros' => [
                'Os medicamentos enviados são inválidos.'
            ],
        ];
    }

    if (count($entrada) > 25) {

        $erros[] =
            'Uma prescrição pode conter no máximo 25 medicamentos.';

        return [
            'itens' => [],
            'erros' => $erros,
        ];
    }

    $limites = [

        'nome_medicamento' => 200,

        'principio_ativo' => 200,

        'apresentacao' => 150,

        'concentracao' => 100,

        'dosagem' => 100,

        'via_administracao' => 100,

        'frequencia' => 100,

        'duracao' => 100,

        'quantidade' => 100,

        'instrucoes' => 10000,

    ];

    foreach ($entrada as $indice => $linha) {

        if (!is_array($linha)) {
            continue;
        }

        $item = [];

        $possuiConteudo = false;

        foreach ($limites as $campo => $limite) {

            $valor = $linha[$campo] ?? '';

            if (!is_string($valor)) {
                $valor = '';
            }

            $valor = trim($valor);

            if ($valor !== '') {
                $possuiConteudo = true;
            }

            if (mb_strlen($valor, 'UTF-8') > $limite) {

                $erros[] =
                    "O campo {$campo} do medicamento "
                    . ($indice + 1)
                    . " excede {$limite} caracteres.";
            }

            $item[$campo] = $valor !== ''
                ? $valor
                : null;
        }

        // Configuração JSON de dosagem.
        $jsonRaw = $linha['dados_dosagem'] ?? '';

        if (!is_string($jsonRaw)) {
            $jsonRaw = '';
        }

        $jsonRaw = trim($jsonRaw);

        if ($jsonRaw !== '') {
            $possuiConteudo = true;
        }

        if ($jsonRaw !== '') {

            try {

                if (strlen($jsonRaw) > 10000) {
                    throw new RuntimeException(
                        'JSON excede 10.000 bytes.'
                    );
                }

                $jsonDecodificado = json_decode(
                    $jsonRaw,
                    true,
                    32,
                    JSON_THROW_ON_ERROR
                );

                if (!is_array($jsonDecodificado)) {

                    throw new RuntimeException(
                        'A configuração deve ser um objeto ou array JSON.'
                    );
                }

                $item['dados_dosagem'] = json_encode(
                    $jsonDecodificado,
                    JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                        | JSON_THROW_ON_ERROR
                );
            } catch (Throwable $e) {

                $erros[] =
                    'JSON inválido no medicamento '
                    . ($indice + 1)
                    . ': '
                    . $e->getMessage();

                // Mantém o texto original para repopular o formulário.
                $item['dados_dosagem'] = $jsonRaw;
            }
        } else {

            $item['dados_dosagem'] = null;
        }

        // Ignora linhas totalmente vazias.
        if (!$possuiConteudo) {
            continue;
        }

        if (
            empty($item['nome_medicamento'])
        ) {

            $erros[] =
                'Informe o nome do medicamento '
                . ($indice + 1)
                . '.';

            continue;
        }

        $itens[] = $item;
    }

    if (count($itens) === 0) {

        $erros[] =
            'Adicione pelo menos um medicamento à prescrição.';
    }

    return [
        'itens' => $itens,
        'erros' => $erros,
    ];
};

// =========================================================
// POST
// =========================================================

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {

    $token = $_POST['csrf_token'] ?? null;

    if (!prescricoesCsrfValido(
        is_string($token) ? $token : null
    )) {

        $mensagens[] = [
            'tipo' => 'erro',
            'texto' => 'Sessão expirada ou token CSRF inválido. Recarregue a página.',
        ];
    } else {

        $acao = $lerPost('acao');

        // =================================================
        // CRIAR / ATUALIZAR
        // =================================================

        if (
            $acao === 'criar'
            || $acao === 'atualizar'
        ) {

            $erros = [];

            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $id = $id !== false
                ? (int) $id
                : 0;

            $pacienteId = filter_var(
                $_POST['paciente_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $pacienteId = $pacienteId !== false
                ? (int) $pacienteId
                : 0;

            $profissionalId = filter_var(
                $_POST['profissional_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $profissionalId = $profissionalId !== false
                ? (int) $profissionalId
                : 0;

            $prontuarioIdRaw = $lerPost('prontuario_id');

            $prontuarioId = null;

            if ($prontuarioIdRaw !== '') {

                $prontuarioId = filter_var(
                    $prontuarioIdRaw,
                    FILTER_VALIDATE_INT
                );

                if (
                    $prontuarioId === false
                    || $prontuarioId <= 0
                ) {

                    $erros[] =
                        'O prontuário selecionado é inválido.';

                    $prontuarioId = null;
                } else {

                    $prontuarioId = (int) $prontuarioId;
                }
            }

            $dataInput = $lerPost('data_prescricao');

            $observacoes = $lerPost('observacoes');

            $dataConvertida = $converterDataPrescricao(
                $dataInput
            );

            // ---------------------------------------------
            // IDENTIFICADOR
            // ---------------------------------------------

            if (
                $acao === 'atualizar'
                && $id <= 0
            ) {

                $erros[] =
                    'ID inválido para atualização.';
            }

            // ---------------------------------------------
            // PACIENTE
            // ---------------------------------------------

            if (
                $pacienteId <= 0
                || !$dao->pacienteExiste($pacienteId)
            ) {

                $erros[] =
                    'Selecione um paciente existente.';
            }

            // ---------------------------------------------
            // PROFISSIONAL
            // ---------------------------------------------

            if (
                $profissionalId <= 0
                || !$dao->profissionalValido($profissionalId)
            ) {

                $erros[] =
                    'Selecione um profissional ativo válido.';
            }

            // ---------------------------------------------
            // PRONTUÁRIO
            // ---------------------------------------------

            if (
                $prontuarioId !== null
                && $pacienteId > 0
            ) {

                if (
                    !$dao->prontuarioPertenceAoPaciente(
                        $prontuarioId,
                        $pacienteId
                    )
                ) {

                    $erros[] =
                        'O prontuário selecionado não pertence ao paciente informado.';
                }
            }

            // ---------------------------------------------
            // DATA
            // ---------------------------------------------

            if ($dataConvertida === null) {

                $erros[] =
                    'Informe uma data e horário válidos para a prescrição.';
            } else {

                $dataComparacao = new DateTime(
                    $dataConvertida
                );

                $agora = new DateTime();

                if ($dataComparacao > $agora) {

                    $erros[] =
                        'A data da prescrição não pode estar no futuro.';
                }
            }

            // ---------------------------------------------
            // OBSERVAÇÕES
            // ---------------------------------------------

            $erroObservacoes = $validarComprimento(
                $observacoes,
                10000,
                'Observações'
            );

            if ($erroObservacoes !== null) {
                $erros[] = $erroObservacoes;
            }

            // ---------------------------------------------
            // MEDICAMENTOS
            // ---------------------------------------------

            $resultadoItens = $processarItens(
                $_POST['itens'] ?? []
            );

            $itens = $resultadoItens['itens'];

            $erros = array_merge(
                $erros,
                $resultadoItens['erros']
            );

            // ---------------------------------------------
            // REPUBLICAÇÃO DO FORMULÁRIO
            // ---------------------------------------------

            $formItens = [];

            if (isset($_POST['itens']) && is_array($_POST['itens'])) {

                foreach (
                    array_slice($_POST['itens'], 0, 25, true)
                    as $linha
                ) {

                    if (!is_array($linha)) {
                        continue;
                    }

                    $formItens[] = $linha;
                }
            }

            if ($erros) {

                foreach ($erros as $erro) {

                    $mensagens[] = [
                        'tipo' => 'erro',
                        'texto' => $erro,
                    ];
                }

                $prescricaoEdicao = new Prescricao();

                if ($acao === 'atualizar') {
                    $prescricaoEdicao->id = $id;
                }

                $prescricaoEdicao->pacienteId = $pacienteId;

                $prescricaoEdicao->profissionalId = $profissionalId;

                $prescricaoEdicao->prontuarioId = $prontuarioId;

                $prescricaoEdicao->dataPrescricao =
                    $dataConvertida ?? $dataInput;

                $prescricaoEdicao->observacoes =
                    $nullSeVazio($observacoes);

                $prescricaoEdicao->itens = $formItens;

                $prescricaoEdicao->quantidadeItens = count($itens);
            } else {

                // -----------------------------------------
                // PERSISTÊNCIA
                // -----------------------------------------

                try {

                    $prescricao = new Prescricao();

                    $prescricao->pacienteId = $pacienteId;

                    $prescricao->profissionalId = $profissionalId;

                    $prescricao->prontuarioId = $prontuarioId;

                    $prescricao->dataPrescricao = $dataConvertida;

                    $prescricao->situacao =
                        Prescricao::SITUACAO_RASCUNHO;

                    $prescricao->observacoes =
                        $nullSeVazio($observacoes);

                    if ($acao === 'criar') {

                        $novoId = $dao->inserir(
                            $prescricao,
                            $itens
                        );

                        $mensagens[] = [
                            'tipo' => 'sucesso',
                            'texto' => "Prescrição #{$novoId} cadastrada com sucesso.",
                        ];
                    } else {

                        $prescricao->id = $id;

                        $dao->atualizar(
                            $prescricao,
                            $itens
                        );

                        $mensagens[] = [
                            'tipo' => 'sucesso',
                            'texto' => "Prescrição #{$id} atualizada com sucesso.",
                        ];
                    }

                    $prescricaoEdicao = null;

                    $formItens = [];
                } catch (DomainException | InvalidArgumentException $e) {

                    $mensagens[] = [
                        'tipo' => 'erro',
                        'texto' => $e->getMessage(),
                    ];

                    $prescricaoEdicao = new Prescricao();

                    $prescricaoEdicao->id =
                        $acao === 'atualizar' ? $id : null;

                    $prescricaoEdicao->pacienteId = $pacienteId;

                    $prescricaoEdicao->profissionalId = $profissionalId;

                    $prescricaoEdicao->prontuarioId = $prontuarioId;

                    $prescricaoEdicao->dataPrescricao = $dataConvertida
                        ?? $dataInput;

                    $prescricaoEdicao->observacoes = $nullSeVazio(
                        $observacoes
                    );

                    $prescricaoEdicao->itens = $formItens;

                    $prescricaoEdicao->quantidadeItens = count(
                        $itens
                    );
                } catch (Throwable $e) {

                    error_log(
                        '[Prescricoes] Erro ao salvar: '
                            . $e->getMessage()
                    );

                    $mensagens[] = [
                        'tipo' => 'erro',
                        'texto' => 'Ocorreu um erro interno ao salvar a prescrição.',
                    ];
                }
            }
        }

        // =================================================
        // EMITIR
        // =================================================

        elseif ($acao === 'emitir') {

            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $assinatura = $lerPost(
                'assinatura_digital'
            );

            if ($id === false || $id <= 0) {

                $mensagens[] = [
                    'tipo' => 'erro',
                    'texto' => 'ID de prescrição inválido.',
                ];
            } else {

                try {

                    $dao->emitir(
                        (int) $id,
                        $assinatura
                    );

                    $mensagens[] = [
                        'tipo' => 'sucesso',
                        'texto' => "Prescrição #{$id} emitida com sucesso.",
                    ];
                } catch (DomainException | InvalidArgumentException $e) {

                    $mensagens[] = [
                        'tipo' => 'erro',
                        'texto' => $e->getMessage(),
                    ];
                } catch (Throwable $e) {

                    error_log(
                        '[Prescricoes] Erro ao emitir: '
                            . $e->getMessage()
                    );

                    $mensagens[] = [
                        'tipo' => 'erro',
                        'texto' => 'Não foi possível emitir a prescrição.',
                    ];
                }
            }
        }

        // =================================================
        // CANCELAR
        // =================================================

        elseif ($acao === 'cancelar') {

            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if ($id === false || $id <= 0) {

                $mensagens[] = [
                    'tipo' => 'erro',
                    'texto' => 'ID inválido para cancelamento.',
                ];
            } else {

                try {

                    $dao->cancelar((int) $id);

                    $mensagens[] = [
                        'tipo' => 'sucesso',
                        'texto' => "Prescrição #{$id} cancelada. O histórico foi preservado.",
                    ];
                } catch (DomainException | InvalidArgumentException $e) {

                    $mensagens[] = [
                        'tipo' => 'erro',
                        'texto' => $e->getMessage(),
                    ];
                } catch (Throwable $e) {

                    error_log(
                        '[Prescricoes] Erro ao cancelar: '
                            . $e->getMessage()
                    );

                    $mensagens[] = [
                        'tipo' => 'erro',
                        'texto' => 'Não foi possível cancelar a prescrição.',
                    ];
                }
            }
        }

        // =================================================
        // EXCLUIR
        // =================================================

        elseif ($acao === 'excluir') {

            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if ($id === false || $id <= 0) {

                $mensagens[] = [
                    'tipo' => 'erro',
                    'texto' => 'ID inválido para exclusão.',
                ];
            } else {

                try {

                    $dao->excluir((int) $id);

                    $mensagens[] = [
                        'tipo' => 'sucesso',
                        'texto' => "Rascunho #{$id} removido com sucesso.",
                    ];
                } catch (DomainException | InvalidArgumentException $e) {

                    $mensagens[] = [
                        'tipo' => 'erro',
                        'texto' => $e->getMessage(),
                    ];
                } catch (Throwable $e) {

                    error_log(
                        '[Prescricoes] Erro ao excluir: '
                            . $e->getMessage()
                    );

                    $mensagens[] = [
                        'tipo' => 'erro',
                        'texto' => 'Não foi possível excluir a prescrição.',
                    ];
                }
            }
        }

        // =================================================
        // AÇÃO DESCONHECIDA
        // =================================================

        else {

            $mensagens[] = [
                'tipo' => 'erro',
                'texto' => 'Operação não reconhecida.',
            ];
        }
    }
}

// =========================================================
// GET ?editar=ID
// =========================================================

if (
    $prescricaoEdicao === null
    && isset($_GET['editar'])
) {

    $idEditar = filter_var(
        $_GET['editar'],
        FILTER_VALIDATE_INT
    );

    if ($idEditar === false || $idEditar <= 0) {

        $mensagens[] = [
            'tipo' => 'erro',
            'texto' => 'Identificador da prescrição inválido.',
        ];
    } else {

        $prescricaoEdicao = $dao->buscarPorId(
            (int) $idEditar
        );

        if ($prescricaoEdicao === null) {

            $mensagens[] = [
                'tipo' => 'erro',
                'texto' => 'Prescrição não encontrada.',
            ];
        } else {

            $formItens = $prescricaoEdicao->itens;

            if ($prescricaoEdicao->podeEditar()) {

                $formItens = $prescricaoEdicao->itens;
            }
        }
    }
}

// =========================================================
// LISTAGEM
// =========================================================

$prescricoes = $dao->buscarComFiltros(
    $busca,
    $profissionalFiltro,
    $situacaoFiltro,
    $dataInicioFiltro,
    $dataFimFiltro
);

// =========================================================
// DADOS DOS SELECTS
// =========================================================

$pacientes = $dao->listarPacientes();

$profissionais = $dao->listarProfissionais();

$prontuariosPaciente = [];

if (
    $prescricaoEdicao !== null
    && $prescricaoEdicao->pacienteId > 0
) {

    $prontuariosPaciente =
        $dao->listarProntuariosPorPaciente(
            $prescricaoEdicao->pacienteId
        );
}

// =========================================================
// RENDER
// =========================================================

renderPrescricaoGUI(
    $prescricoes,
    $mensagens,
    $prescricaoEdicao,
    $busca,
    $profissionalFiltro,
    $situacaoFiltro,
    $dataInicioFiltro,
    $dataFimFiltro,
    $pacientes,
    $profissionais,
    $prontuariosPaciente,
    $formItens,
    prescricoesCsrfToken()
);
