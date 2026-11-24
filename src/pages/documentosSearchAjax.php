<?php
// pages/documentosSearchAjax.php — endpoint JSON para busca em tempo real.

require_once __DIR__ . '/../configs/authGuard.php';
require_once __DIR__ . '/../configs/cors.php';
require_once __DIR__ . '/../controller/documentosDAO.php';
require_once __DIR__ . '/../views/documentosGUI.php';

header('Content-Type: application/json; charset=utf-8');

$dao = new DocumentoDAO();

$busca = trim($_GET['busca'] ?? '');
$filtro = trim($_GET['filtro'] ?? '');

if ($busca !== '' || $filtro !== '') {
    if (method_exists($dao, 'buscarComFiltros')) {
        $registros = $dao->buscarComFiltros($busca, $filtro);
    } elseif (method_exists($dao, 'buscarPorNomeParcial')) {
        $registros = $dao->buscarPorNomeParcial($busca);
    } else {
        $registros = $dao->listar();
    }
} else {
    $registros = $dao->listar();
}

ob_start();
if (function_exists('renderDocumentoTableRows')) {
    renderDocumentoTableRows($registros, null, $busca);
} else {
    echo '<tr><td colspan="10" class="text-center text-muted">Função renderDocumentoTableRows não definida.</td></tr>';
}
$html = ob_get_clean();

echo json_encode([
    'html'  => $html,
    'total' => count($registros),
], JSON_UNESCAPED_UNICODE);
