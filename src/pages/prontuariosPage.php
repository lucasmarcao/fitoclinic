<?php
// pages/prontuariosPage.php

require_once __DIR__ . '/../configs/authGuard.php';
require_once __DIR__ . '/../configs/cors.php';
require_once __DIR__ . '/../controller/prontuariosDAO.php';
require_once __DIR__ . '/../views/prontuariosGUI.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- CSRF helpers (idempotentes) ----------
if (!function_exists('csrfToken')) {
    function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('csrfValidate')) {
    function csrfValidate(?string $token): bool
    {
        return !empty($_SESSION['csrf_token'])
            && !empty($token)
            && hash_equals($_SESSION['csrf_token'], (string) $token);
    }
}

$dao = new ProntuarioDAO();
$mensagens = [];
$prontuarioEdicao = null;   // objeto exibido no form (edição ou repopulação pós-erro)

$busca           = trim($_GET['busca'] ?? '');
$profissionalFiltro = (int) ($_GET['profissional'] ?? 0);
$dataFiltro      = trim($_GET['data'] ?? '');

// ---------- Utilitário: string vazia → null ----------
$n = static fn(string $v): ?string => $v !== '' ? $v : null;

// ---------- Validação de pressão arterial ----------
$validarPressao = static function (?string $pa): bool {
    if ($pa === null || $pa === '') return true;
    return (bool) preg_match('/^\d{2,3}\/\d{2,3}(\s*mmHg)?$/i', $pa);
};

// =========================================================
// POST — criar / atualizar / excluir
// =========================================================
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {

    if (!csrfValidate($_POST['csrf_token'] ?? null)) {
        $mensagens[] = ['tipo' => 'erro', 'texto' => 'Sessão expirada ou requisição inválida. Recarregue a página.'];
    } else {
        $acao = $_POST['acao'] ?? '';

        // ---------- CRIAR ----------
        if ($acao === 'criar' || $acao === 'atualizar') {
            $id              = (int) ($_POST['id'] ?? 0);
            $pacienteId      = (int) ($_POST['paciente_id'] ?? 0);
            $profissionalId  = (int) ($_POST['profissional_id'] ?? 0);
            $agendamentoId   = !empty($_POST['agendamento_id']) ? (int) $_POST['agendamento_id'] : null;
            $dataInput       = trim($_POST['data_registro'] ?? '');
            $queixa          = trim($_POST['queixa'] ?? '');
            $historico       = trim($_POST['historico_clinico'] ?? '');
            $diagnostico     = trim($_POST['diagnostico'] ?? '');
            $tratamento      = trim($_POST['tratamento'] ?? '');
            $observacoes     = trim($_POST['observacoes'] ?? '');
            $pesoRaw         = trim($_POST['peso'] ?? '');
            $alturaRaw       = trim($_POST['altura'] ?? '');
            $pressaoRaw      = trim($_POST['pressao_arterial'] ?? '');

            $erros = [];

            if ($acao === 'atualizar' && $id <= 0) {
                $erros[] = 'ID inválido para atualização.';
            }

            if ($pacienteId <= 0 || !$dao->pacienteExiste($pacienteId)) {
                $erros[] = 'Selecione um paciente válido.';
            }
            if ($profissionalId <= 0 || !$dao->profissionalValido($profissionalId)) {
                $erros[] = 'Selecione um profissional válido e ativo.';
            }

            // Agendamento (opcional) — se informado, deve pertencer ao paciente
            if ($agendamentoId !== null) {
                if (!$dao->agendamentoPertenceAoPaciente($agendamentoId, $pacienteId)) {
                    $erros[] = 'O agendamento selecionado não pertence ao paciente escolhido.';
                } else {
                    $jaLigado = $dao->prontuarioDoAgendamento($agendamentoId, $acao === 'atualizar' ? $id : null);
                    if ($jaLigado !== null) {
                        $erros[] = 'Este agendamento já possui um prontuário vinculado (#'
                            . $jaLigado->id . '). Edite o prontuário existente.';
                    }
                }
            }

            // Data do registro (não pode ser futura; tolerância de 5 min)
            $dtRegistro = DateTime::createFromFormat('Y-m-d\TH:i', $dataInput);
            if (!$dtRegistro) {
                $erros[] = 'Data/hora do registro inválida.';
            } else {
                $limite = (new DateTime())->modify('+5 minutes');
                if ($dtRegistro > $limite) {
                    $erros[] = 'A data do registro não pode estar no futuro.';
                }
            }

            // Pelo menos um campo clínico
            $temClinico = $queixa !== '' || $historico !== ''
                || $diagnostico !== '' || $tratamento !== ''
                || $observacoes !== '';
            if (!$temClinico) {
                $erros[] = 'Preencha pelo menos um campo clínico (queixa, histórico, diagnóstico, tratamento ou observações).';
            }

            // Peso / altura
            $peso = null;
            if ($pesoRaw !== '') {
                $pesoStr = str_replace(',', '.', $pesoRaw);
                if (!is_numeric($pesoStr) || (float) $pesoStr <= 0 || (float) $pesoStr >= 1000) {
                    $erros[] = 'Peso inválido (kg, maior que zero).';
                } else {
                    $peso = round((float) $pesoStr, 2);
                }
            }

            $altura = null;
            if ($alturaRaw !== '') {
                $altStr = str_replace(',', '.', $alturaRaw);
                if (!is_numeric($altStr) || (float) $altStr <= 0 || (float) $altStr >= 3) {
                    $erros[] = 'Altura inválida (metros, maior que zero).';
                } else {
                    $altura = round((float) $altStr, 2);
                }
            }

            // Pressão arterial
            if (!$validarPressao($pressaoRaw)) {
                $erros[] = 'Pressão arterial inválida (use o formato 120/80 ou 120/80 mmHg).';
            }

            if ($erros) {
                foreach ($erros as $e) {
                    $mensagens[] = ['tipo' => 'erro', 'texto' => $e];
                }
                // Repopula o form
                $prontuarioEdicao = new Prontuario();
                if ($acao === 'atualizar') $prontuarioEdicao->id = $id;
                $prontuarioEdicao->pacienteId       = $pacienteId;
                $prontuarioEdicao->profissionalId   = $profissionalId;
                $prontuarioEdicao->agendamentoId    = $agendamentoId;
                $prontuarioEdicao->dataRegistro     = $dtRegistro ? $dtRegistro->format('Y-m-d H:i:s') : $dataInput;
                $prontuarioEdicao->queixa           = $n($queixa);
                $prontuarioEdicao->historicoClinico = $n($historico);
                $prontuarioEdicao->diagnostico      = $n($diagnostico);
                $prontuarioEdicao->tratamento       = $n($tratamento);
                $prontuarioEdicao->observacoes      = $n($observacoes);
                $prontuarioEdicao->peso             = $peso;
                $prontuarioEdicao->altura           = $altura;
                $prontuarioEdicao->pressaoArterial  = $n($pressaoRaw);
            } else {
                try {
                    $dataParaDB = $dtRegistro->format('Y-m-d H:i:s');

                    if ($acao === 'criar') {
                        $p = new Prontuario();
                        $p->pacienteId       = $pacienteId;
                        $p->profissionalId   = $profissionalId;
                        $p->agendamentoId    = $agendamentoId;
                        $p->dataRegistro     = $dataParaDB;
                        $p->queixa           = $n($queixa);
                        $p->historicoClinico = $n($historico);
                        $p->diagnostico      = $n($diagnostico);
                        $p->tratamento       = $n($tratamento);
                        $p->observacoes      = $n($observacoes);
                        $p->peso             = $peso;
                        $p->altura           = $altura;
                        $p->pressaoArterial  = $n($pressaoRaw);

                        $novoId = $dao->inserir($p);
                        $mensagens[] = ['tipo' => 'sucesso', 'texto' => "Prontuário #{$novoId} cadastrado com sucesso."];
                    } else {
                        $existente = $dao->buscarPorId($id);
                        if ($existente === null) {
                            $mensagens[] = ['tipo' => 'erro', 'texto' => "Prontuário #{$id} não encontrado."];
                        } else {
                            $existente->pacienteId       = $pacienteId;
                            $existente->profissionalId   = $profissionalId;
                            $existente->agendamentoId    = $agendamentoId;
                            $existente->dataRegistro     = $dataParaDB;
                            $existente->queixa           = $n($queixa);
                            $existente->historicoClinico = $n($historico);
                            $existente->diagnostico      = $n($diagnostico);
                            $existente->tratamento       = $n($tratamento);
                            $existente->observacoes      = $n($observacoes);
                            $existente->peso             = $peso;
                            $existente->altura           = $altura;
                            $existente->pressaoArterial  = $n($pressaoRaw);

                            $dao->atualizar($existente);
                            $mensagens[] = ['tipo' => 'sucesso', 'texto' => "Prontuário #{$id} atualizado com sucesso."];
                        }
                    }
                } catch (Throwable $ex) {
                    $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao salvar: ' . $ex->getMessage()];
                }
            }
        }

        // ---------- EXCLUIR ----------
        elseif ($acao === 'excluir') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => 'ID inválido para exclusão.'];
            } else {
                try {
                    $dao->excluir($id);
                    $mensagens[] = ['tipo' => 'sucesso', 'texto' => "Prontuário #{$id} removido."];
                } catch (Throwable $ex) {
                    $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao excluir: ' . $ex->getMessage()];
                }
            }
        }
    }
}

// =========================================================
// GET ?editar=ID
// =========================================================
if ($prontuarioEdicao === null && isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    if ($id > 0) {
        $prontuarioEdicao = $dao->buscarPorId($id);
        if ($prontuarioEdicao === null) {
            $mensagens[] = ['tipo' => 'erro', 'texto' => "Prontuário #{$id} não encontrado."];
        }
    }
}

// =========================================================
// Listagem com filtros
// =========================================================
if ($busca !== '' || $profissionalFiltro > 0 || $dataFiltro !== '') {
    $prontuarios = $dao->buscarComFiltros($busca, $profissionalFiltro, $dataFiltro);
} else {
    $prontuarios = $dao->listar();
}

// Listas para os <select>
$pacientes     = $dao->listarPacientes();
$profissionais = $dao->listarProfissionais();

// Agendamentos do paciente atual (para pré-popular o <select> em edição)
$agendamentosPaciente = [];
if ($prontuarioEdicao !== null && $prontuarioEdicao->pacienteId > 0) {
    $agendamentosPaciente = $dao->listarAgendamentosPorPaciente($prontuarioEdicao->pacienteId);
}

// =========================================================
// Render
// =========================================================
renderProntuarioGUI(
    $prontuarios,
    $mensagens,
    $prontuarioEdicao,
    $busca,
    $profissionalFiltro,
    $dataFiltro,
    $pacientes,
    $profissionais,
    $agendamentosPaciente
);
