<?php
// controller/usuariosDAO.php

require_once __DIR__ . '/../configs/bdConexao.php';
require_once __DIR__ . '/../models/usuarios.php';

class UsuarioDAO
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? BdConexao::getConexao();
    }

    /** Insere um usuário e devolve o id gerado. */
    public function inserir(Usuario $u): int
    {
        $sql = "INSERT INTO usuarios
                    (nome, email, senha_hash, papel,
                     numero_registro, tipo_registro, telefone, ativo)
                VALUES
                    (:nome, :email, :senha_hash, :papel,
                     :numero_registro, :tipo_registro, :telefone, :ativo)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $u->nome,
            ':email' => $u->email,
            ':senha_hash' => $u->senhaHash,
            ':papel' => $u->papel,
            ':numero_registro' => $u->numeroRegistro,
            ':tipo_registro' => $u->tipoRegistro,
            ':telefone' => $u->telefone,
            ':ativo' => $u->ativo ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Atualiza um usuário existente. */
    public function atualizar(Usuario $u): bool
    {
        if ($u->id === null) {
            throw new InvalidArgumentException('ID do usuário é obrigatório para atualização.');
        }

        $sql = "UPDATE usuarios SET
                    nome            = :nome,
                    email           = :email,
                    senha_hash      = :senha_hash,
                    papel           = :papel,
                    numero_registro = :numero_registro,
                    tipo_registro   = :tipo_registro,
                    telefone        = :telefone,
                    ativo           = :ativo
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $u->id,
            ':nome' => $u->nome,
            ':email' => $u->email,
            ':senha_hash' => $u->senhaHash,
            ':papel' => $u->papel,
            ':numero_registro' => $u->numeroRegistro,
            ':tipo_registro' => $u->tipoRegistro,
            ':telefone' => $u->telefone,
            ':ativo' => $u->ativo ? 1 : 0,
        ]);
    }

    /** Exclui um usuário pelo id. */
    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /** Busca por id. */
    public function buscarPorId(int $id): ?Usuario
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $dados = $stmt->fetch();

        return $dados ? self::hidratar($dados) : null;
    }

    /** Busca por e-mail (útil em login). */
    public function buscarPorEmail(string $email): ?Usuario
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $dados = $stmt->fetch();

        return $dados ? self::hidratar($dados) : null;
    }

    /** Lista todos os usuários em ordem alfabética. */
    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM usuarios ORDER BY nome ASC");
        $usuarios = [];

        while ($linha = $stmt->fetch()) {
            $usuarios[] = self::hidratar($linha);
        }

        return $usuarios;
    }

    // Adicionar dentro da classe UsuarioDAO em controller/usuariosDAO.php

    /** Busca usuário por nome exato. */
    public function buscarPorNome(string $nome): ?Usuario
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE nome = :nome LIMIT 1");
        $stmt->execute([':nome' => $nome]);
        $dados = $stmt->fetch();

        return $dados ? self::hidratar($dados) : null;
    }

    /** 
     * Verifica se já existe algum usuário com o mesmo nome OU e-mail.
     * @param int|null $ignorarId Passado na edição de conta para ignorar o próprio ID.
     */
    public function verificarExistenciaNomeOuEmail(string $nome, string $email, ?int $ignorarId = null): ?string
    {
        $sql = "SELECT nome, email FROM usuarios WHERE (nome = :nome OR email = :email)";
        if ($ignorarId !== null) {
            $sql .= " AND id != :id";
        }

        $stmt = $this->pdo->prepare($sql);
        $params = [':nome' => $nome, ':email' => $email];
        if ($ignorarId !== null) {
            $params[':id'] = $ignorarId;
        }

        $stmt->execute($params);
        $existente = $stmt->fetch();

        if ($existente) {
            if ($existente['nome'] === $nome) {
                return "Já existe um usuário cadastrado com o nome '{$nome}'.";
            }
            if ($existente['email'] === $email) {
                return "Já existe um usuário cadastrado com o e-mail '{$email}'.";
            }
        }

        return null; // Nenhum conflito encontrado
    }

    /** Converte array do banco em objeto Usuario. */
    private static function hidratar(array $d): Usuario
    {
        $u = new Usuario();
        $u->id = isset($d['id']) ? (int) $d['id'] : null;
        $u->nome = (string) ($d['nome'] ?? '');
        $u->email = (string) ($d['email'] ?? '');
        $u->senhaHash = (string) ($d['senha_hash'] ?? '');
        $u->papel = (string) ($d['papel'] ?? 'profissional');
        $u->numeroRegistro = $d['numero_registro'] ?? null;
        $u->tipoRegistro = $d['tipo_registro'] ?? null;
        $u->telefone = $d['telefone'] ?? null;
        $u->ativo = (bool) ($d['ativo'] ?? true);
        $u->criadoEm = $d['criado_em'] ?? null;
        $u->atualizadoEm = $d['atualizado_em'] ?? null;

        return $u;
    }
}