<?php
// pages/prontuariosSearchAjax.php — endpoint JSON para busca e autocomplete.

require_once __DIR__ . '/../configs/authGuard.php';
require_once __DIR__ . '/../configs/cors.php';
require_once __DIR__ . '/../controller/prontuariosDAO.php';
require_once __DIR__ . '/../views/prontuariosGUI.php';

header('Content-Type: application/json; charset=utf-8');

$dao  = new ProntuarioDAO();
$mode = $_GET['mode'] ?? 'rows';

// ---------- mode=agendamentos (popula <select> ao trocar paciente) ----------
if ($mode === 'agendamentos') {
    $pacienteId = (int) ($_GET['paciente_id'] ?? 0);
    if ($pacienteId <= 0) {
        echo json_encode(['options' => '', 'total' => 0], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $lista = $dao->listarAgendamentosPorPaciente($pacienteId);
    $opts  = '';
    foreach ($lista as $a) {
        $opts .= '<option value="' . (int) $a['id'] . '">'
            . htmlspecialchars(formatAgendamentoOptionLabel($a), ENT_QUOTES, 'UTF-8')
            . '</option>';
    }
    echo json_encode(['options' => $opts, 'total' => count($lista)], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- mode=rows (padrão) — filtros da listagem ----------
$busca              = trim($_GET['busca'] ?? '');
$profissionalFiltro = (int) ($_GET['profissional'] ?? 0);
$dataFiltro         = trim($_GET['data'] ?? '');

if ($busca !== '' || $profissionalFiltro > 0 || $dataFiltro !== '') {
    $prontuarios = $dao->buscarComFiltros($busca, $profissionalFiltro, $dataFiltro);
} else {
    $prontuarios = $dao->listar();
}

ob_start();
renderProntuarioTableRows($prontuarios, null, $busca, $profissionalFiltro, $dataFiltro);
$html = ob_get_clean();

echo json_encode([
    'html'  => $html,
    'total' => count($prontuarios),
], JSON_UNESCAPED_UNICODE);
