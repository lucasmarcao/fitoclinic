<?php
// configs/authGuard.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se não houver ID do usuário na sessão, redireciona para a index.php na raiz
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: /index.php');
    exit;
}

// mudar essa implementação

?>