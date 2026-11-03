<?php
// pages/agendamentosSearchAjax.php — endpoint JSON para busca em tempo real.

require_once __DIR__ . '/../configs/authGuard.php';
require_once __DIR__ . '/../configs/cors.php';
require_once __DIR__ . '/../controller/agendamentosDAO.php';
require_once __DIR__ . '/../views/agendamentosGUI.php';

header('Content-Type: application/json; charset=utf-8');

$dao = new AgendamentoDAO();

$busca          = trim($_GET['busca'] ?? '');
$situacaoFiltro = trim($_GET['situacao'] ?? '');
$dataFiltro     = trim($_GET['data'] ?? '');

if ($busca !== '' || $situacaoFiltro !== '' || $dataFiltro !== '') {
    $agendamentos = $dao->buscarComFiltros($busca, $situacaoFiltro, $dataFiltro);
} else {
    $agendamentos = $dao->listar();
}

ob_start();
renderAgendamentoTableRows($agendamentos, null, $busca, $situacaoFiltro, $dataFiltro);
$html = ob_get_clean();

echo json_encode([
    'html'  => $html,
    'total' => count($agendamentos),
], JSON_UNESCAPED_UNICODE);
