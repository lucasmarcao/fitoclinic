<?php
// pages/usuariosPage.php — processa as ações e delega a renderização.

require_once __DIR__ . '/../configs/cors.php';
require_once __DIR__ . '/../controller/usuariosDAO.php';
require_once __DIR__ . '/../views/usuariosGUI.php';

$dao = new UsuarioDAO();
$mensagens = [];
$usuarioEdicao = null;   // se != null, o form entra em modo de edição

// =========================================================
// POST — criar / atualizar / excluir
// =========================================================
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ---------------------------------------------------------
    // CRIAR
    // ---------------------------------------------------------
    if ($acao === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = (string) ($_POST['senha'] ?? '');
        $papel = $_POST['papel'] ?? 'profissional';
        $numeroRegistro = trim($_POST['numero_registro'] ?? '');
        $tipoRegistro = trim($_POST['tipo_registro'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $ativo = isset($_POST['ativo']);

        $erros = [];
        if ($nome === '')
            $erros[] = 'Informe o nome.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $erros[] = 'E-mail inválido.';
        if (strlen($senha) < 8)
            $erros[] = 'A senha deve ter ao menos 8 caracteres.';
        if (!in_array($papel, ['admin', 'profissional', 'recepcionista'], true)) {
            $erros[] = 'Papel inválido.';
        }

        if ($erros) {
            foreach ($erros as $e) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => $e];
            }
        } else {
            try {
                if ($dao->buscarPorEmail($email) !== null) {
                    $mensagens[] = ['tipo' => 'erro', 'texto' => 'Já existe um usuário com este e-mail.'];
                } else {
                    $u = new Usuario();
                    $u->nome = $nome;
                    $u->email = $email;
                    $u->senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                    $u->papel = $papel;
                    $u->numeroRegistro = $numeroRegistro !== '' ? $numeroRegistro : null;
                    $u->tipoRegistro = $tipoRegistro !== '' ? $tipoRegistro : null;
                    $u->telefone = $telefone !== '' ? $telefone : null;
                    $u->ativo = $ativo;

                    $id = $dao->inserir($u);
                    $mensagens[] = ['tipo' => 'sucesso', 'texto' => "Usuário #{$id} cadastrado com sucesso."];
                }
            } catch (Throwable $ex) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao cadastrar: ' . $ex->getMessage()];
            }
        }
    }

    // ---------------------------------------------------------
    // ATUALIZAR
    // ---------------------------------------------------------
    elseif ($acao === 'atualizar') {
        $id = (int) ($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = (string) ($_POST['senha'] ?? '');
        $papel = $_POST['papel'] ?? 'profissional';
        $numeroRegistro = trim($_POST['numero_registro'] ?? '');
        $tipoRegistro = trim($_POST['tipo_registro'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $ativo = isset($_POST['ativo']);

        $erros = [];
        if ($id <= 0)
            $erros[] = 'ID inválido para atualização.';
        if ($nome === '')
            $erros[] = 'Informe o nome.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $erros[] = 'E-mail inválido.';
        // senha é OPCIONAL na edição: só valida se foi preenchida
        if ($senha !== '' && strlen($senha) < 8) {
            $erros[] = 'A nova senha deve ter ao menos 8 caracteres.';
        }
        if (!in_array($papel, ['admin', 'profissional', 'recepcionista'], true)) {
            $erros[] = 'Papel inválido.';
        }

        if ($erros) {
            foreach ($erros as $e) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => $e];
            }
            // Reaproveita o que o usuário digitou (não recarrega do banco)
            $usuarioEdicao = new Usuario();
            $usuarioEdicao->id = $id;
            $usuarioEdicao->nome = $nome;
            $usuarioEdicao->email = $email;
            $usuarioEdicao->papel = $papel;
            $usuarioEdicao->numeroRegistro = $numeroRegistro !== '' ? $numeroRegistro : null;
            $usuarioEdicao->tipoRegistro = $tipoRegistro !== '' ? $tipoRegistro : null;
            $usuarioEdicao->telefone = $telefone !== '' ? $telefone : null;
            $usuarioEdicao->ativo = $ativo;
        } else {
            try {
                // ---------- buscarPorId() entra aqui ----------
                $existente = $dao->buscarPorId($id);

                if ($existente === null) {
                    $mensagens[] = ['tipo' => 'erro', 'texto' => "Usuário #{$id} não encontrado."];
                } else {
                    // E-mail já usado por OUTRO usuário?
                    $outro = $dao->buscarPorEmail($email);
                    if ($outro !== null && $outro->id !== $id) {
                        $mensagens[] = ['tipo' => 'erro', 'texto' => 'Já existe outro usuário com este e-mail.'];

                        $usuarioEdicao = $existente;
                        $usuarioEdicao->nome = $nome;
                        $usuarioEdicao->email = $email;
                        $usuarioEdicao->papel = $papel;
                        $usuarioEdicao->numeroRegistro = $numeroRegistro !== '' ? $numeroRegistro : null;
                        $usuarioEdicao->tipoRegistro = $tipoRegistro !== '' ? $tipoRegistro : null;
                        $usuarioEdicao->telefone = $telefone !== '' ? $telefone : null;
                        $usuarioEdicao->ativo = $ativo;
                    } else {
                        // Mescla os novos valores
                        $existente->nome = $nome;
                        $existente->email = $email;
                        $existente->papel = $papel;
                        $existente->numeroRegistro = $numeroRegistro !== '' ? $numeroRegistro : null;
                        $existente->tipoRegistro = $tipoRegistro !== '' ? $tipoRegistro : null;
                        $existente->telefone = $telefone !== '' ? $telefone : null;
                        $existente->ativo = $ativo;

                        // Senha: só troca se o usuário digitou algo
                        if ($senha !== '') {
                            $existente->senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                        }

                        $dao->atualizar($existente);
                        $mensagens[] = ['tipo' => 'sucesso', 'texto' => "Usuário #{$id} atualizado com sucesso."];
                    }
                }
            } catch (Throwable $ex) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao atualizar: ' . $ex->getMessage()];
                $usuarioEdicao = $dao->buscarPorId($id); // recarrega para o form
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
                $mensagens[] = ['tipo' => 'sucesso', 'texto' => "Usuário #{$id} removido."];
            } catch (Throwable $ex) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao excluir: ' . $ex->getMessage()];
            }
        }
    }
}

// =========================================================
// GET ?editar=ID — carrega o usuário e entra em modo edição
// =========================================================
if ($usuarioEdicao === null && isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];

    if ($id > 0) {
        $usuarioEdicao = $dao->buscarPorId($id);

        if ($usuarioEdicao === null) {
            $mensagens[] = ['tipo' => 'erro', 'texto' => "Usuário #{$id} não encontrado."];
        }
    }
}

// =========================================================
// Render
// =========================================================
$usuarios = $dao->listar();
renderUsuarioGUI($usuarios, $mensagens, $usuarioEdicao);