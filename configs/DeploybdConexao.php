<?php

class DeploybdConexao
{
    private static ?PDO $conexao = null;

    private static function obterVariavel(
        string $nome,
        ?string $padrao = null
    ): string {
        $valor = getenv($nome);

        if ($valor === false || trim($valor) === '') {
            if ($padrao !== null) {
                return $padrao;
            }

            throw new RuntimeException(
                "Variável de ambiente não configurada: {$nome}"
            );
        }

        return trim($valor);
    }

    public static function getConexao(): PDO
    {
        if (self::$conexao instanceof PDO) {
            return self::$conexao;
        }

        if (!in_array('pgsql', PDO::getAvailableDrivers(), true)) {
            throw new RuntimeException(
                'O driver PDO_PGSQL não está habilitado no PHP.'
            );
        }

        $host = self::obterVariavel('SUPABASE_DB_HOST');
        $port = self::obterVariavel('SUPABASE_DB_PORT', '5432');
        $banco = self::obterVariavel('SUPABASE_DB_NAME', 'postgres');
        $usuario = self::obterVariavel('SUPABASE_DB_USER');
        $senha = self::obterVariavel('SUPABASE_DB_PASSWORD');

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
            $host,
            $port,
            $banco
        );

        $opcoes = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];

        try {

            self::$conexao = new PDO(
                $dsn,
                $usuario,
                $senha,
                $opcoes
            );

            return self::$conexao;

        } catch (PDOException $e) {

            error_log(
                '[DeploybdConexao] ' . $e->getMessage()
            );

            throw new RuntimeException(
                'Não foi possível conectar ao banco de dados.',
                0,
                $e
            );
        }
    }
}
