<?php
// models/pacientes.php — entidade Paciente + criação da tabela

class Paciente
{
    public ?int $id = null;
    public string $nome = '';
    public ?string $nomeSocial = null;
    public ?string $cpf = null;
    public ?string $rg = null;
    public ?string $dataNascimento = null;      // Y-m-d
    public string $genero = 'nao_informado';
    public ?string $telefone = null;
    public ?string $whatsapp = null;
    public ?string $email = null;
    public ?string $endereco = null;
    public ?string $cidade = null;
    public ?string $estado = null;
    public ?string $cep = null;
    public ?string $contatoEmergencia = null;
    public ?string $telefoneEmergencia = null;
    public ?string $alergias = null;
    public ?string $observacoes = null;
    public ?string $criadoEm = null;
    public ?string $atualizadoEm = null;

    /** Cria a tabela `pacientes` caso ela ainda não exista. */
    public static function criarTabelaSeNaoExistir(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS pacientes (
            id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nome                  VARCHAR(150) NOT NULL,
            nome_social           VARCHAR(150) NULL,
            cpf                   VARCHAR(14)  NULL UNIQUE,
            rg                    VARCHAR(30)  NULL,
            data_nascimento       DATE         NULL,
            genero                ENUM('masculino','feminino','outro','nao_informado')
                                  DEFAULT 'nao_informado',
            telefone              VARCHAR(30)  NULL,
            whatsapp              VARCHAR(30)  NULL,
            email                 VARCHAR(150) NULL,
            endereco              VARCHAR(255) NULL,
            cidade                VARCHAR(100) NULL,
            estado                CHAR(2)      NULL,
            cep                   VARCHAR(10)  NULL,
            contato_emergencia    VARCHAR(150) NULL,
            telefone_emergencia   VARCHAR(30)  NULL,
            alergias              TEXT         NULL,
            observacoes           TEXT         NULL,
            criado_em             TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            atualizado_em         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);
    }

    // ---------- Helpers de apresentação ----------

    public function dataNascimentoBR(): ?string
    {
        if ($this->dataNascimento === null || $this->dataNascimento === '') {
            return null;
        }
        try {
            return (new DateTime($this->dataNascimento))->format('d/m/Y');
        } catch (Throwable $e) {
            return $this->dataNascimento;
        }
    }

    public function idade(): ?int
    {
        if ($this->dataNascimento === null || $this->dataNascimento === '') {
            return null;
        }
        try {
            $nasc = new DateTime($this->dataNascimento);
            $hoje = new DateTime();
            return $hoje->diff($nasc)->y;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function generoLabel(): string
    {
        return match ($this->genero) {
            'masculino'    => 'Masculino',
            'feminino'     => 'Feminino',
            'outro'        => 'Outro',
            'nao_informado' => 'Não informado',
            default        => '—',
        };
    }
}
