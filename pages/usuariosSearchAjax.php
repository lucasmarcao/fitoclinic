<?php
// pages/usuariosSearchAjax.php — endpoint JSON para busca em tempo real.

require_once __DIR__ . '/../configs/authGuard.php';
require_once __DIR__ . '/../configs/cors.php';
require_once __DIR__ . '/../controller/usuariosDAO.php';
require_once __DIR__ . '/../views/usuariosGUI.php';

header('Content-Type: application/json; charset=utf-8');

$dao = new UsuarioDAO();

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
if (function_exists('renderUsuarioTableRows')) {
    renderUsuarioTableRows($registros, null, $busca);
} else {
    echo '<tr><td colspan="10" class="text-center text-muted">Função renderUsuarioTableRows não definida.</td></tr>';
}
$html = ob_get_clean();

echo json_encode([
    'html'  => $html,
    'total' => count($registros),
], JSON_UNESCAPED_UNICODE);
