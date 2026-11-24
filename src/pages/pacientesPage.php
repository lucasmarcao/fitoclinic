<?php
// require_once __DIR__ . '/../configs/authGuard.php'; // Bloqueia e redireciona se não estiver logado

// Restante do código da página...
// pages/pacientesPage.php — processa as ações e delega a renderização.

require_once __DIR__ . '/../configs/cors.php';
require_once __DIR__ . '/../controller/pacientesDAO.php';
require_once __DIR__ . '/../views/pacientesGUI.php';

$dao = new PacienteDAO();
$mensagens = [];
$pacienteEdicao = null;
$busca = trim($_GET['busca'] ?? '');

// Helper: normaliza CPF para "000.000.000-00" (ou null se inválido/vazio).
$normalizarCpf = static function (?string $cpf): ?string {
    if ($cpf === null) return null;
    $digits = preg_replace('/\D/', '', $cpf);
    if (strlen($digits) !== 11) return null;
    return substr($digits, 0, 3) . '.' . substr($digits, 3, 3) . '.'
        . substr($digits, 6, 3) . '-' . substr($digits, 9, 2);
};

// Helper: normaliza CEP para "00000-000" (ou null se inválido/vazio).
$normalizarCep = static function (?string $cep): ?string {
    if ($cep === null) return null;
    $digits = preg_replace('/\D/', '', $cep);
    if (strlen($digits) !== 8) return null;
    return substr($digits, 0, 5) . '-' . substr($digits, 5, 3);
};

// =========================================================
// POST — criar / atualizar / excluir
// =========================================================
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ---------------------------------------------------------
    // CRIAR
    // ---------------------------------------------------------
    if ($acao === 'criar') {
        $nome            = trim($_POST['nome'] ?? '');
        $nomeSocial      = trim($_POST['nome_social'] ?? '');
        $cpfRaw          = trim($_POST['cpf'] ?? '');
        $rg              = trim($_POST['rg'] ?? '');
        $dataNascimento  = trim($_POST['data_nascimento'] ?? '');
        $genero          = $_POST['genero'] ?? 'nao_informado';
        $telefone        = trim($_POST['telefone'] ?? '');
        $whatsapp        = trim($_POST['whatsapp'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $endereco        = trim($_POST['endereco'] ?? '');
        $cidade          = trim($_POST['cidade'] ?? '');
        $estado          = strtoupper(trim($_POST['estado'] ?? ''));
        $cepRaw          = trim($_POST['cep'] ?? '');
        $contatoEmerg    = trim($_POST['contato_emergencia'] ?? '');
        $telefoneEmerg   = trim($_POST['telefone_emergencia'] ?? '');
        $alergias        = trim($_POST['alergias'] ?? '');
        $observacoes     = trim($_POST['observacoes'] ?? '');

        $erros = [];

        if ($nome === '') {
            $erros[] = 'Informe o nome.';
        }

        // CPF
        $cpf = null;
        if ($cpfRaw !== '') {
            $cpf = $normalizarCpf($cpfRaw);
            if ($cpf === null) {
                $erros[] = 'CPF inválido (deve ter 11 dígitos).';
            } elseif ($dao->cpfEmUso($cpf)) {
                $erros[] = 'Já existe um paciente cadastrado com este CPF.';
            }
        }

        // Data de nascimento
        if ($dataNascimento !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', $dataNascimento);
            if (!$dt || $dt->format('Y-m-d') !== $dataNascimento) {
                $erros[] = 'Data de nascimento inválida.';
            } elseif ($dt > new DateTime()) {
                $erros[] = 'Data de nascimento não pode ser futura.';
            }
        } else {
            $dataNascimento = null;
        }

        // Gênero
        if (!in_array($genero, ['masculino', 'feminino', 'outro', 'nao_informado'], true)) {
            $genero = 'nao_informado';
        }

        // E-mail (opcional, mas se preenchido deve ser válido)
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'E-mail inválido.';
        }

        // Estado (UF)
        if ($estado !== '' && strlen($estado) !== 2) {
            $erros[] = 'Estado deve ter 2 letras (UF).';
        }

        // CEP
        $cep = null;
        if ($cepRaw !== '') {
            $cep = $normalizarCep($cepRaw);
            if ($cep === null) {
                $erros[] = 'CEP inválido (deve ter 8 dígitos).';
            }
        }

        if ($erros) {
            foreach ($erros as $e) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => $e];
            }
        } else {
            try {
                $p = new Paciente();
                $p->nome               = $nome;
                $p->nomeSocial         = $nomeSocial !== '' ? $nomeSocial : null;
                $p->cpf                = $cpf;
                $p->rg                 = $rg !== '' ? $rg : null;
                $p->dataNascimento     = $dataNascimento;
                $p->genero             = $genero;
                $p->telefone           = $telefone !== '' ? $telefone : null;
                $p->whatsapp           = $whatsapp !== '' ? $whatsapp : null;
                $p->email              = $email !== '' ? $email : null;
                $p->endereco           = $endereco !== '' ? $endereco : null;
                $p->cidade             = $cidade !== '' ? $cidade : null;
                $p->estado             = $estado !== '' ? $estado : null;
                $p->cep                = $cep;
                $p->contatoEmergencia  = $contatoEmerg !== '' ? $contatoEmerg : null;
                $p->telefoneEmergencia = $telefoneEmerg !== '' ? $telefoneEmerg : null;
                $p->alergias           = $alergias !== '' ? $alergias : null;
                $p->observacoes        = $observacoes !== '' ? $observacoes : null;

                $id = $dao->inserir($p);
                $mensagens[] = ['tipo' => 'sucesso', 'texto' => "Paciente #{$id} cadastrado com sucesso."];
            } catch (Throwable $ex) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao cadastrar: ' . $ex->getMessage()];
            }
        }
    }

    // ---------------------------------------------------------
    // ATUALIZAR
    // ---------------------------------------------------------
    elseif ($acao === 'atualizar') {
        $id              = (int) ($_POST['id'] ?? 0);
        $nome            = trim($_POST['nome'] ?? '');
        $nomeSocial      = trim($_POST['nome_social'] ?? '');
        $cpfRaw          = trim($_POST['cpf'] ?? '');
        $rg              = trim($_POST['rg'] ?? '');
        $dataNascimento  = trim($_POST['data_nascimento'] ?? '');
        $genero          = $_POST['genero'] ?? 'nao_informado';
        $telefone        = trim($_POST['telefone'] ?? '');
        $whatsapp        = trim($_POST['whatsapp'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $endereco        = trim($_POST['endereco'] ?? '');
        $cidade          = trim($_POST['cidade'] ?? '');
        $estado          = strtoupper(trim($_POST['estado'] ?? ''));
        $cepRaw          = trim($_POST['cep'] ?? '');
        $contatoEmerg    = trim($_POST['contato_emergencia'] ?? '');
        $telefoneEmerg   = trim($_POST['telefone_emergencia'] ?? '');
        $alergias        = trim($_POST['alergias'] ?? '');
        $observacoes     = trim($_POST['observacoes'] ?? '');

        $erros = [];
        if ($id <= 0) $erros[] = 'ID inválido para atualização.';
        if ($nome === '') $erros[] = 'Informe o nome.';

        // CPF
        $cpf = null;
        if ($cpfRaw !== '') {
            $cpf = $normalizarCpf($cpfRaw);
            if ($cpf === null) {
                $erros[] = 'CPF inválido (deve ter 11 dígitos).';
            } elseif ($dao->cpfEmUso($cpf, $id)) {
                $erros[] = 'Já existe outro paciente cadastrado com este CPF.';
            }
        }

        // Data
        if ($dataNascimento !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', $dataNascimento);
            if (!$dt || $dt->format('Y-m-d') !== $dataNascimento) {
                $erros[] = 'Data de nascimento inválida.';
            } elseif ($dt > new DateTime()) {
                $erros[] = 'Data de nascimento não pode ser futura.';
            }
        } else {
            $dataNascimento = null;
        }

        if (!in_array($genero, ['masculino', 'feminino', 'outro', 'nao_informado'], true)) {
            $genero = 'nao_informado';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'E-mail inválido.';
        }

        if ($estado !== '' && strlen($estado) !== 2) {
            $erros[] = 'Estado deve ter 2 letras (UF).';
        }

        $cep = null;
        if ($cepRaw !== '') {
            $cep = $normalizarCep($cepRaw);
            if ($cep === null) {
                $erros[] = 'CEP inválido (deve ter 8 dígitos).';
            }
        }

        if ($erros) {
            foreach ($erros as $e) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => $e];
            }
            // Repopula o form de edição com o que foi digitado
            $pacienteEdicao = new Paciente();
            $pacienteEdicao->id               = $id;
            $pacienteEdicao->nome             = $nome;
            $pacienteEdicao->nomeSocial       = $nomeSocial !== '' ? $nomeSocial : null;
            $pacienteEdicao->cpf              = $cpf ?? $cpfRaw;
            $pacienteEdicao->rg               = $rg !== '' ? $rg : null;
            $pacienteEdicao->dataNascimento   = $dataNascimento;
            $pacienteEdicao->genero           = $genero;
            $pacienteEdicao->telefone         = $telefone !== '' ? $telefone : null;
            $pacienteEdicao->whatsapp         = $whatsapp !== '' ? $whatsapp : null;
            $pacienteEdicao->email            = $email !== '' ? $email : null;
            $pacienteEdicao->endereco         = $endereco !== '' ? $endereco : null;
            $pacienteEdicao->cidade           = $cidade !== '' ? $cidade : null;
            $pacienteEdicao->estado           = $estado !== '' ? $estado : null;
            $pacienteEdicao->cep              = $cep ?? $cepRaw;
            $pacienteEdicao->contatoEmergencia = $contatoEmerg !== '' ? $contatoEmerg : null;
            $pacienteEdicao->telefoneEmergencia = $telefoneEmerg !== '' ? $telefoneEmerg : null;
            $pacienteEdicao->alergias         = $alergias !== '' ? $alergias : null;
            $pacienteEdicao->observacoes      = $observacoes !== '' ? $observacoes : null;
        } else {
            try {
                $existente = $dao->buscarPorId($id);
                if ($existente === null) {
                    $mensagens[] = ['tipo' => 'erro', 'texto' => "Paciente #{$id} não encontrado."];
                } else {
                    $existente->nome             = $nome;
                    $existente->nomeSocial       = $nomeSocial !== '' ? $nomeSocial : null;
                    $existente->cpf              = $cpf;
                    $existente->rg               = $rg !== '' ? $rg : null;
                    $existente->dataNascimento   = $dataNascimento;
                    $existente->genero           = $genero;
                    $existente->telefone         = $telefone !== '' ? $telefone : null;
                    $existente->whatsapp         = $whatsapp !== '' ? $whatsapp : null;
                    $existente->email            = $email !== '' ? $email : null;
                    $existente->endereco         = $endereco !== '' ? $endereco : null;
                    $existente->cidade           = $cidade !== '' ? $cidade : null;
                    $existente->estado           = $estado !== '' ? $estado : null;
                    $existente->cep              = $cep;
                    $existente->contatoEmergencia = $contatoEmerg !== '' ? $contatoEmerg : null;
                    $existente->telefoneEmergencia = $telefoneEmerg !== '' ? $telefoneEmerg : null;
                    $existente->alergias         = $alergias !== '' ? $alergias : null;
                    $existente->observacoes      = $observacoes !== '' ? $observacoes : null;

                    $dao->atualizar($existente);
                    $mensagens[] = ['tipo' => 'sucesso', 'texto' => "Paciente #{$id} atualizado com sucesso."];
                }
            } catch (Throwable $ex) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao atualizar: ' . $ex->getMessage()];
                $pacienteEdicao = $dao->buscarPorId($id);
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
                $mensagens[] = ['tipo' => 'sucesso', 'texto' => "Paciente #{$id} removido."];
            } catch (Throwable $ex) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao excluir: ' . $ex->getMessage()];
            }
        }
    }
}

// =========================================================
// GET ?editar=ID — carrega o paciente e entra em modo edição
// =========================================================
if ($pacienteEdicao === null && isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    if ($id > 0) {
        $pacienteEdicao = $dao->buscarPorId($id);
        if ($pacienteEdicao === null) {
            $mensagens[] = ['tipo' => 'erro', 'texto' => "Paciente #{$id} não encontrado."];
        }
    }
}

// =========================================================
// LISTAGEM — com busca parcial por nome
// =========================================================
if ($busca !== '') {
    $pacientes = $dao->buscarPorNomeParcial($busca);
} else {
    $pacientes = $dao->listar();
}

// =========================================================
// Render
// =========================================================
renderPacienteGUI($pacientes, $mensagens, $pacienteEdicao, $busca);
