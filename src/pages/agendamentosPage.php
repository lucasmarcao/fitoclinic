<?php
// pages/agendamentosPage.php — processa as ações e delega a renderização.

require_once __DIR__ . '/../configs/authGuard.php'; // Bloqueia se não logado
require_once __DIR__ . '/../configs/cors.php';
require_once __DIR__ . '/../controller/agendamentosDAO.php';
require_once __DIR__ . '/../views/agendamentosGUI.php';

$dao = new AgendamentoDAO();
$mensagens = [];
$agendamentoEdicao = null;

$busca         = trim($_GET['busca'] ?? '');
$situacaoFiltro = trim($_GET['situacao'] ?? '');
$dataFiltro    = trim($_GET['data'] ?? '');

// =========================================================
// POST — criar / atualizar / excluir
// =========================================================
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ---------------------------------------------------------
    // CRIAR
    // ---------------------------------------------------------
    if ($acao === 'criar') {
        $pacienteId     = (int) ($_POST['paciente_id'] ?? 0);
        $profissionalId = (int) ($_POST['profissional_id'] ?? 0);
        $dataAgendamento = trim($_POST['data_agendamento'] ?? '');
        $horaInicio     = trim($_POST['hora_inicio'] ?? '');
        $horaFim        = trim($_POST['hora_fim'] ?? '');
        $tipo           = $_POST['tipo'] ?? 'consulta';
        $origem         = $_POST['origem_agendamento'] ?? 'interno';
        $teleconsulta   = isset($_POST['teleconsulta']);
        $urlReuniao     = trim($_POST['url_reuniao'] ?? '');
        $observacoes    = trim($_POST['observacoes'] ?? '');

        // Regra de negócio: status inicia como 'agendado' na criação
        $situacao = 'agendado';

        $erros = [];

        if ($pacienteId <= 0 || !$dao->pacienteExiste($pacienteId)) {
            $erros[] = 'Selecione um paciente válido.';
        }
        if ($profissionalId <= 0 || !$dao->profissionalValido($profissionalId)) {
            $erros[] = 'Selecione um profissional válido e ativo.';
        }

        // Data
        $dt = DateTime::createFromFormat('Y-m-d', $dataAgendamento);
        if (!$dt || $dt->format('Y-m-d') !== $dataAgendamento) {
            $erros[] = 'Data de agendamento inválida.';
        }

        // Hora início
        $hIni = DateTime::createFromFormat('H:i', $horaInicio);
        if (!$hIni || $hIni->format('H:i') !== $horaInicio) {
            $erros[] = 'Hora de início inválida.';
        }

        // Hora fim
        if ($horaFim !== '') {
            $hFim = DateTime::createFromFormat('H:i', $horaFim);
            if (!$hFim || $hFim->format('H:i') !== $horaFim) {
                $erros[] = 'Hora de término inválida.';
            } elseif ($horaInicio !== '' && $horaFim <= $horaInicio) {
                $erros[] = 'Hora de término deve ser posterior à hora de início.';
            }
        } else {
            $horaFim = null;
        }

        // Tipo
        if (!in_array($tipo, Agendamento::TIPOS, true)) {
            $erros[] = 'Tipo de agendamento inválido.';
        }

        // Origem
        if (!in_array($origem, Agendamento::ORIGENS, true)) {
            $erros[] = 'Origem do agendamento inválida.';
        }

        // Teleconsulta: URL obrigatória + válida
        if ($teleconsulta) {
            if ($urlReuniao === '') {
                $erros[] = 'URL da reunião é obrigatória quando o agendamento é por teleconsulta.';
            } elseif (!filter_var($urlReuniao, FILTER_VALIDATE_URL)) {
                $erros[] = 'URL da reunião inválida.';
            } elseif (strlen($urlReuniao) > 500) {
                $erros[] = 'URL da reunião excede 500 caracteres.';
            }
        } else {
            $urlReuniao = null;
        }

        // Regra de negócio: conflito de horário
        if (!$erros) {
            $conflito = $dao->verificarConflitoHorario(
                $profissionalId,
                $dataAgendamento,
                $horaInicio,
                $horaFim
            );
            if ($conflito !== null) {
                $erros[] = 'Já existe um agendamento para este profissional neste horário: '
                    . $conflito->dataBR() . ' ' . $conflito->horaRange()
                    . ' (paciente: ' . ($conflito->pacienteNome ?? '#' . $conflito->pacienteId) . ').';
            }
        }

        if ($erros) {
            foreach ($erros as $e) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => $e];
            }
        } else {
            try {
                $a = new Agendamento();
                $a->pacienteId        = $pacienteId;
                $a->profissionalId    = $profissionalId;
                $a->dataAgendamento   = $dataAgendamento;
                $a->horaInicio        = $horaInicio;
                $a->horaFim           = $horaFim;
                $a->tipo              = $tipo;
                $a->situacao          = $situacao;
                $a->origemAgendamento = $origem;
                $a->teleconsulta      = $teleconsulta;
                $a->urlReuniao        = $urlReuniao;
                $a->observacoes       = $observacoes !== '' ? $observacoes : null;

                $id = $dao->inserir($a);
                $mensagens[] = ['tipo' => 'sucesso', 'texto' => "Agendamento #{$id} cadastrado com sucesso."];
            } catch (Throwable $ex) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao cadastrar: ' . $ex->getMessage()];
            }
        }
    }

    // ---------------------------------------------------------
    // ATUALIZAR
    // ---------------------------------------------------------
    elseif ($acao === 'atualizar') {
        $id             = (int) ($_POST['id'] ?? 0);
        $pacienteId     = (int) ($_POST['paciente_id'] ?? 0);
        $profissionalId = (int) ($_POST['profissional_id'] ?? 0);
        $dataAgendamento = trim($_POST['data_agendamento'] ?? '');
        $horaInicio     = trim($_POST['hora_inicio'] ?? '');
        $horaFim        = trim($_POST['hora_fim'] ?? '');
        $tipo           = $_POST['tipo'] ?? 'consulta';
        $situacao       = $_POST['situacao'] ?? 'agendado';
        $origem         = $_POST['origem_agendamento'] ?? 'interno';
        $teleconsulta   = isset($_POST['teleconsulta']);
        $urlReuniao     = trim($_POST['url_reuniao'] ?? '');
        $observacoes    = trim($_POST['observacoes'] ?? '');

        $erros = [];
        if ($id <= 0) $erros[] = 'ID inválido para atualização.';
        if ($pacienteId <= 0 || !$dao->pacienteExiste($pacienteId)) {
            $erros[] = 'Selecione um paciente válido.';
        }
        if ($profissionalId <= 0 || !$dao->profissionalValido($profissionalId)) {
            $erros[] = 'Selecione um profissional válido e ativo.';
        }

        $dt = DateTime::createFromFormat('Y-m-d', $dataAgendamento);
        if (!$dt || $dt->format('Y-m-d') !== $dataAgendamento) {
            $erros[] = 'Data de agendamento inválida.';
        }

        $hIni = DateTime::createFromFormat('H:i', $horaInicio);
        if (!$hIni || $hIni->format('H:i') !== $horaInicio) {
            $erros[] = 'Hora de início inválida.';
        }

        if ($horaFim !== '') {
            $hFim = DateTime::createFromFormat('H:i', $horaFim);
            if (!$hFim || $hFim->format('H:i') !== $horaFim) {
                $erros[] = 'Hora de término inválida.';
            } elseif ($horaInicio !== '' && $horaFim <= $horaInicio) {
                $erros[] = 'Hora de término deve ser posterior à hora de início.';
            }
        } else {
            $horaFim = null;
        }

        if (!in_array($tipo, Agendamento::TIPOS, true))      $erros[] = 'Tipo de agendamento inválido.';
        if (!in_array($situacao, Agendamento::SITUACOES, true)) $erros[] = 'Situação inválida.';
        if (!in_array($origem, Agendamento::ORIGENS, true))  $erros[] = 'Origem do agendamento inválida.';

        if ($teleconsulta) {
            if ($urlReuniao === '') {
                $erros[] = 'URL da reunião é obrigatória quando o agendamento é por teleconsulta.';
            } elseif (!filter_var($urlReuniao, FILTER_VALIDATE_URL)) {
                $erros[] = 'URL da reunião inválida.';
            } elseif (strlen($urlReuniao) > 500) {
                $erros[] = 'URL da reunião excede 500 caracteres.';
            }
        } else {
            $urlReuniao = null;
        }

        if (!$erros) {
            $conflito = $dao->verificarConflitoHorario(
                $profissionalId,
                $dataAgendamento,
                $horaInicio,
                $horaFim,
                $id
            );
            if ($conflito !== null) {
                $erros[] = 'Já existe um agendamento para este profissional neste horário: '
                    . $conflito->dataBR() . ' ' . $conflito->horaRange()
                    . ' (paciente: ' . ($conflito->pacienteNome ?? '#' . $conflito->pacienteId) . ').';
            }
        }

        if ($erros) {
            foreach ($erros as $e) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => $e];
            }
            // Repopula o form com o que foi digitado
            $agendamentoEdicao = new Agendamento();
            $agendamentoEdicao->id                = $id;
            $agendamentoEdicao->pacienteId        = $pacienteId;
            $agendamentoEdicao->profissionalId    = $profissionalId;
            $agendamentoEdicao->dataAgendamento   = $dataAgendamento;
            $agendamentoEdicao->horaInicio        = $horaInicio;
            $agendamentoEdicao->horaFim           = $horaFim;
            $agendamentoEdicao->tipo              = $tipo;
            $agendamentoEdicao->situacao          = $situacao;
            $agendamentoEdicao->origemAgendamento = $origem;
            $agendamentoEdicao->teleconsulta      = $teleconsulta;
            $agendamentoEdicao->urlReuniao        = $urlReuniao;
            $agendamentoEdicao->observacoes       = $observacoes !== '' ? $observacoes : null;
        } else {
            try {
                $existente = $dao->buscarPorId($id);
                if ($existente === null) {
                    $mensagens[] = ['tipo' => 'erro', 'texto' => "Agendamento #{$id} não encontrado."];
                } else {
                    $existente->pacienteId        = $pacienteId;
                    $existente->profissionalId    = $profissionalId;
                    $existente->dataAgendamento   = $dataAgendamento;
                    $existente->horaInicio        = $horaInicio;
                    $existente->horaFim           = $horaFim;
                    $existente->tipo              = $tipo;
                    $existente->situacao          = $situacao;
                    $existente->origemAgendamento = $origem;
                    $existente->teleconsulta      = $teleconsulta;
                    $existente->urlReuniao        = $urlReuniao;
                    $existente->observacoes       = $observacoes !== '' ? $observacoes : null;

                    $dao->atualizar($existente);
                    $mensagens[] = ['tipo' => 'sucesso', 'texto' => "Agendamento #{$id} atualizado com sucesso."];
                }
            } catch (Throwable $ex) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao atualizar: ' . $ex->getMessage()];
                $agendamentoEdicao = $dao->buscarPorId($id);
            }
        }
    }

    // ---------------------------------------------------------
    // EXCLUIR
    // ---------------------------------------------------------
    elseif ($acao === 'excluir') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $mensagens[] = ['tipo' => 'erro', 'texto' => 'ID inválido para exclusão.'];
        } else {
            try {
                $dao->excluir($id);
                $mensagens[] = ['tipo' => 'sucesso', 'texto' => "Agendamento #{$id} removido."];
            } catch (Throwable $ex) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao excluir: ' . $ex->getMessage()];
            }
        }
    }
}

// =========================================================
// GET ?editar=ID — carrega o agendamento e entra em modo edição
// =========================================================
if ($agendamentoEdicao === null && isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    if ($id > 0) {
        $agendamentoEdicao = $dao->buscarPorId($id);
        if ($agendamentoEdicao === null) {
            $mensagens[] = ['tipo' => 'erro', 'texto' => "Agendamento #{$id} não encontrado."];
        }
    }
}

// =========================================================
// LISTAGEM — com filtros
// =========================================================
if ($busca !== '' || $situacaoFiltro !== '' || $dataFiltro !== '') {
    $agendamentos = $dao->buscarComFiltros($busca, $situacaoFiltro, $dataFiltro);
} else {
    $agendamentos = $dao->listar();
}

// Listas para os <select> do formulário
$pacientes     = $dao->listarPacientes();
$profissionais = $dao->listarProfissionais();

// =========================================================
// Render
// =========================================================
renderAgendamentoGUI(
    $agendamentos,
    $mensagens,
    $agendamentoEdicao,
    $busca,
    $situacaoFiltro,
    $dataFiltro,
    $pacientes,
    $profissionais
);
