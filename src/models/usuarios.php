<?php
// models/usuarios.php — entidade Usuario + criação da tabela

class Usuario
{
    public ?int $id = null;
    public string $nome = '';
    public string $email = '';
    public string $senhaHash = '';
    public string $papel = 'profissional';
    public ?string $numeroRegistro = null;
    public ?string $tipoRegistro = null;
    public ?string $telefone = null;
    public bool $ativo = true;
    public ?string $criadoEm = null;
    public ?string $atualizadoEm = null;

    /**
     * Cria a tabela `usuarios` caso ela ainda não exista.
     * Chamada por BdConexao::getConexao() logo após a criação do banco.
     */
    public static function criarTabelaSeNaoExistir(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS usuarios (
            id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nome            VARCHAR(150) NOT NULL,
            email           VARCHAR(150) NOT NULL UNIQUE,
            senha_hash      VARCHAR(255) NOT NULL,
            papel           ENUM('admin','profissional','recepcionista')
                            NOT NULL DEFAULT 'profissional',
            numero_registro VARCHAR(50)  NULL,
            tipo_registro   VARCHAR(50)  NULL,
            telefone        VARCHAR(30)  NULL,
            ativo           BOOLEAN      NOT NULL DEFAULT TRUE,
            criado_em       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            atualizado_em   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
                            ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);
    }
}