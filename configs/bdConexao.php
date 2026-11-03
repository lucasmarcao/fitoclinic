<?php
// configs/bdConexao.php
// - cria o banco "fito_clinic" caso não exista
// - delega a criação da tabela "usuarios" para models/usuarios.php

require_once __DIR__ . '/../models/usuarios.php';
require_once __DIR__ . '/../models/pacientes.php';
require_once __DIR__ . '/../models/agendamentos.php';
require_once __DIR__ . '/../models/prontuarios.php';
require_once __DIR__ . '/../models/prescricoes.php';


class BdConexao
{
    private static ?PDO $conexao = null;

    private const HOST = '127.0.0.1';
    private const PORT = '3306';
    private const BANCO = 'fito_clinic';
    private const USUARIO = 'root';
    private const SENHA = ''; // nao tem por agora
    private const CHARSET = 'utf8mb4';

    public static function getConexao(): PDO
    {
        if (self::$conexao instanceof PDO) {
            return self::$conexao;
        }

        $opcoes = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            // 1) Conecta ao servidor MySQL (sem banco) e cria o banco se não existir
            $dsnServidor = sprintf(
                'mysql:host=%s;port=%s;charset=%s',
                self::HOST,
                self::PORT,
                self::CHARSET
            );

            $pdoServidor = new PDO($dsnServidor, self::USUARIO, self::SENHA, $opcoes);

            $pdoServidor->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s`
                 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                self::BANCO
            ));

            // 2) Conecta ao banco criado
            $dsnBanco = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                self::HOST,
                self::PORT,
                self::BANCO,
                self::CHARSET
            );

            self::$conexao = new PDO($dsnBanco, self::USUARIO, self::SENHA, $opcoes);

            // 1  3) Garante a existência da tabela "usuarios"
            Usuario::criarTabelaSeNaoExistir(self::$conexao);
            // 2 -- PRECISA EM TODOS
            Paciente::criarTabelaSeNaoExistir(self::$conexao);
            // 3 -- Agendamento
            Agendamento::criarTabelaSeNaoExistir(self::$conexao);
            // 4 -- Prontuario
            Prontuario::criarTabelaSeNaoExistir(self::$conexao);
            // 5 -- prescricoes
            Prescricao::criarTabelaSeNaoExistir(self::$conexao);

            return self::$conexao;
        } catch (PDOException $e) {
            http_response_code(500);
            die('Erro ao conectar/criar o banco de dados: ' . $e->getMessage());
        }
    }
}
