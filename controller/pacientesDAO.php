<?php
// controller/pacientesDAO.php

require_once __DIR__ . '/../configs/bdConexao.php';
require_once __DIR__ . '/../models/pacientes.php';

class PacienteDAO
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? BdConexao::getConexao();
    }

    /** Insere um paciente e devolve o id gerado. */
    public function inserir(Paciente $p): int
    {
        $sql = "INSERT INTO pacientes
                    (nome, nome_social, cpf, rg, data_nascimento, genero,
                     telefone, whatsapp, email,
                     endereco, cidade, estado, cep,
                     contato_emergencia, telefone_emergencia,
                     alergias, observacoes)
                VALUES
                    (:nome, :nome_social, :cpf, :rg, :data_nascimento, :genero,
                     :telefone, :whatsapp, :email,
                     :endereco, :cidade, :estado, :cep,
                     :contato_emergencia, :telefone_emergencia,
                     :alergias, :observacoes)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nome'                => $p->nome,
            ':nome_social'         => $p->nomeSocial,
            ':cpf'                 => $p->cpf,
            ':rg'                  => $p->rg,
            ':data_nascimento'     => $p->dataNascimento,
            ':genero'              => $p->genero,
            ':telefone'            => $p->telefone,
            ':whatsapp'            => $p->whatsapp,
            ':email'               => $p->email,
            ':endereco'            => $p->endereco,
            ':cidade'              => $p->cidade,
            ':estado'              => $p->estado,
            ':cep'                 => $p->cep,
            ':contato_emergencia'  => $p->contatoEmergencia,
            ':telefone_emergencia' => $p->telefoneEmergencia,
            ':alergias'            => $p->alergias,
            ':observacoes'         => $p->observacoes,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Atualiza um paciente existente. */
    public function atualizar(Paciente $p): bool
    {
        if ($p->id === null) {
            throw new InvalidArgumentException('ID do paciente é obrigatório para atualização.');
        }

        $sql = "UPDATE pacientes SET
                    nome                  = :nome,
                    nome_social           = :nome_social,
                    cpf                   = :cpf,
                    rg                    = :rg,
                    data_nascimento       = :data_nascimento,
                    genero                = :genero,
                    telefone              = :telefone,
                    whatsapp              = :whatsapp,
                    email                 = :email,
                    endereco              = :endereco,
                    cidade                = :cidade,
                    estado                = :estado,
                    cep                   = :cep,
                    contato_emergencia    = :contato_emergencia,
                    telefone_emergencia   = :telefone_emergencia,
                    alergias              = :alergias,
                    observacoes           = :observacoes
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id'                  => $p->id,
            ':nome'                => $p->nome,
            ':nome_social'         => $p->nomeSocial,
            ':cpf'                 => $p->cpf,
            ':rg'                  => $p->rg,
            ':data_nascimento'     => $p->dataNascimento,
            ':genero'              => $p->genero,
            ':telefone'            => $p->telefone,
            ':whatsapp'            => $p->whatsapp,
            ':email'               => $p->email,
            ':endereco'            => $p->endereco,
            ':cidade'              => $p->cidade,
            ':estado'              => $p->estado,
            ':cep'                 => $p->cep,
            ':contato_emergencia'  => $p->contatoEmergencia,
            ':telefone_emergencia' => $p->telefoneEmergencia,
            ':alergias'            => $p->alergias,
            ':observacoes'         => $p->observacoes,
        ]);
    }

    /** Exclui um paciente pelo id. */
    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM pacientes WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /** Busca por id. */
    public function buscarPorId(int $id): ?Paciente
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pacientes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $dados = $stmt->fetch();

        return $dados ? self::hidratar($dados) : null;
    }

    /** Busca por CPF (usado para verificar unicidade). */
    public function buscarPorCpf(string $cpf): ?Paciente
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pacientes WHERE cpf = :cpf LIMIT 1");
        $stmt->execute([':cpf' => $cpf]);
        $dados = $stmt->fetch();

        return $dados ? self::hidratar($dados) : null;
    }

    /**
     * Busca parcial por nome (LIKE) em nome e nome_social.
     * Retorna lista ordenada por nome.
     *
     * @return Paciente[]
     */
    public function buscarPorNomeParcial(string $termo): array
    {
        $like = '%' . $termo . '%';

        $stmt = $this->pdo->prepare(
            "SELECT * FROM pacientes
             WHERE nome LIKE :termo
                OR nome_social LIKE :termo2
             ORDER BY nome ASC"
        );
        $stmt->execute([':termo' => $like, ':termo2' => $like]);

        $pacientes = [];
        while ($linha = $stmt->fetch()) {
            $pacientes[] = self::hidratar($linha);
        }

        return $pacientes;
    }

    /** Lista todos os pacientes em ordem alfabética. */
    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM pacientes ORDER BY nome ASC");
        $pacientes = [];

        while ($linha = $stmt->fetch()) {
            $pacientes[] = self::hidratar($linha);
        }

        return $pacientes;
    }

    /** Verifica se o CPF já está em uso por outro paciente. */
    public function cpfEmUso(string $cpf, ?int $ignorarId = null): bool
    {
        $sql = "SELECT id FROM pacientes WHERE cpf = :cpf";
        if ($ignorarId !== null) {
            $sql .= " AND id != :id";
        }

        $stmt = $this->pdo->prepare($sql);
        $params = [':cpf' => $cpf];
        if ($ignorarId !== null) {
            $params[':id'] = $ignorarId;
        }

        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }

    /** Converte array do banco em objeto Paciente. */
    private static function hidratar(array $d): Paciente
    {
        $p = new Paciente();
        $p->id                  = isset($d['id']) ? (int) $d['id'] : null;
        $p->nome                = (string) ($d['nome'] ?? '');
        $p->nomeSocial          = !empty($d['nome_social']) ? (string) $d['nome_social'] : null;
        $p->cpf                 = !empty($d['cpf']) ? (string) $d['cpf'] : null;
        $p->rg                  = !empty($d['rg']) ? (string) $d['rg'] : null;
        $p->dataNascimento      = !empty($d['data_nascimento']) ? (string) $d['data_nascimento'] : null;
        $p->genero              = (string) ($d['genero'] ?? 'nao_informado');
        $p->telefone            = !empty($d['telefone']) ? (string) $d['telefone'] : null;
        $p->whatsapp            = !empty($d['whatsapp']) ? (string) $d['whatsapp'] : null;
        $p->email               = !empty($d['email']) ? (string) $d['email'] : null;
        $p->endereco            = !empty($d['endereco']) ? (string) $d['endereco'] : null;
        $p->cidade              = !empty($d['cidade']) ? (string) $d['cidade'] : null;
        $p->estado              = !empty($d['estado']) ? (string) $d['estado'] : null;
        $p->cep                 = !empty($d['cep']) ? (string) $d['cep'] : null;
        $p->contatoEmergencia   = !empty($d['contato_emergencia']) ? (string) $d['contato_emergencia'] : null;
        $p->telefoneEmergencia  = !empty($d['telefone_emergencia']) ? (string) $d['telefone_emergencia'] : null;
        $p->alergias            = !empty($d['alergias']) ? (string) $d['alergias'] : null;
        $p->observacoes         = !empty($d['observacoes']) ? (string) $d['observacoes'] : null;
        $p->criadoEm            = $d['criado_em'] ?? null;
        $p->atualizadoEm        = $d['atualizado_em'] ?? null;

        return $p;
    }
}
