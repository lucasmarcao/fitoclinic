<?php
// models/prescricoes.php

declare(strict_types=1);

/**
 * Entidade de domínio: Prescricao.
 *
 * Relacionamento:
 * prescricoes 1:N itens_prescricao
 *
 * A propriedade $itens contém arrays associativos com os campos
 * da tabela itens_prescricao.
 */
class Prescricao
{
    public ?int $id = null;

    public int $pacienteId = 0;
    public int $profissionalId = 0;

    public ?int $prontuarioId = null;

    public string $dataPrescricao = '';

    public string $situacao = self::SITUACAO_RASCUNHO;

    public ?string $assinaturaDigital = null;
    public ?string $assinadoEm = null;

    public ?string $observacoes = null;

    public ?string $criadoEm = null;
    public ?string $atualizadoEm = null;

    // Dados derivados de JOIN.
    public ?string $pacienteNome = null;
    public ?string $profissionalNome = null;
    public ?string $prontuarioResumo = null;

    // Relacionamento 1:N.
    public array $itens = [];

    public int $quantidadeItens = 0;

    // ---------------------------------------------------------
    // SITUAÇÕES
    // ---------------------------------------------------------

    public const SITUACAO_RASCUNHO = 'rascunho';
    public const SITUACAO_EMITIDA = 'emitida';
    public const SITUACAO_CANCELADA = 'cancelada';

    public const SITUACOES = [
        self::SITUACAO_RASCUNHO => 'Rascunho',
        self::SITUACAO_EMITIDA => 'Emitida',
        self::SITUACAO_CANCELADA => 'Cancelada',
    ];

    /**
     * Transições permitidas.
     *
     * Rascunho -> Emitida
     * Rascunho -> Cancelada
     * Emitida  -> Cancelada
     * Cancelada -> nenhuma
     */
    public const TRANSICOES = [
        self::SITUACAO_RASCUNHO => [
            self::SITUACAO_EMITIDA,
            self::SITUACAO_CANCELADA,
        ],

        self::SITUACAO_EMITIDA => [
            self::SITUACAO_CANCELADA,
        ],

        self::SITUACAO_CANCELADA => [],
    ];

    // ---------------------------------------------------------
    // ESTRUTURA DO BANCO
    // ---------------------------------------------------------

    /**
     * Cria prescricoes e itens_prescricao caso não existam.
     *
     * A tabela principal é criada antes da tabela dependente,
     * respeitando a integridade referencial.
     *
     * As tabelas pacientes, usuarios e prontuarios devem existir
     * previamente, conforme a ordem do SQL geral da aplicação.
     */
    public static function criarTabelaSeNaoExistir(PDO $pdo): void
    {
        $sqlPrescricoes = "
            CREATE TABLE IF NOT EXISTS prescricoes (

                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                paciente_id BIGINT UNSIGNED NOT NULL,
                profissional_id BIGINT UNSIGNED NOT NULL,
                prontuario_id BIGINT UNSIGNED NULL,

                data_prescricao DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                situacao ENUM(
                    'rascunho',
                    'emitida',
                    'cancelada'
                ) NOT NULL DEFAULT 'rascunho',

                assinatura_digital TEXT NULL,
                assinado_em DATETIME NULL,

                observacoes TEXT NULL,

                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                CONSTRAINT fk_prescricoes_paciente
                    FOREIGN KEY (paciente_id)
                    REFERENCES pacientes(id)
                    ON DELETE RESTRICT,

                CONSTRAINT fk_prescricoes_profissional
                    FOREIGN KEY (profissional_id)
                    REFERENCES usuarios(id)
                    ON DELETE RESTRICT,

                CONSTRAINT fk_prescricoes_prontuario
                    FOREIGN KEY (prontuario_id)
                    REFERENCES prontuarios(id)
                    ON DELETE SET NULL,

                INDEX idx_prescricoes_paciente (paciente_id),
                INDEX idx_prescricoes_profissional (profissional_id),
                INDEX idx_prescricoes_prontuario (prontuario_id),
                INDEX idx_prescricoes_data (data_prescricao),
                INDEX idx_prescricoes_situacao (situacao)

            ) ENGINE=InnoDB
              DEFAULT CHARSET=utf8mb4
              COLLATE=utf8mb4_unicode_ci
        ";

        $pdo->exec($sqlPrescricoes);

        self::criarTabelaItensSeNaoExistir($pdo);
    }

    /**
     * Cria a tabela dependente de itens.
     *
     * Estrutura fiel ao SQL fornecido.
     */
    public static function criarTabelaItensSeNaoExistir(PDO $pdo): void
    {
        $sqlItens = "
            CREATE TABLE IF NOT EXISTS itens_prescricao (

                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                prescricao_id BIGINT UNSIGNED NOT NULL,

                nome_medicamento VARCHAR(200) NOT NULL,

                principio_ativo VARCHAR(200) NULL,

                apresentacao VARCHAR(150) NULL,

                concentracao VARCHAR(100) NULL,

                dosagem VARCHAR(100) NULL,

                via_administracao VARCHAR(100) NULL,

                frequencia VARCHAR(100) NULL,

                duracao VARCHAR(100) NULL,

                quantidade VARCHAR(100) NULL,

                instrucoes TEXT NULL,

                dados_dosagem JSON NULL,

                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_itens_prescricao_prescricao
                    FOREIGN KEY (prescricao_id)
                    REFERENCES prescricoes(id)
                    ON DELETE CASCADE,

                INDEX idx_itens_prescricao_medicamento (
                    nome_medicamento
                )

            ) ENGINE=InnoDB
              DEFAULT CHARSET=utf8mb4
              COLLATE=utf8mb4_unicode_ci
        ";

        $pdo->exec($sqlItens);
    }

    // ---------------------------------------------------------
    // SITUAÇÃO E REGRAS DE NEGÓCIO
    // ---------------------------------------------------------

    public function situacaoEhValida(): bool
    {
        return array_key_exists(
            $this->situacao,
            self::SITUACOES
        );
    }

    public function situacaoLabel(): string
    {
        return self::SITUACOES[$this->situacao]
            ?? 'Desconhecida';
    }

    public function situacaoBadgeClass(): string
    {
        return match ($this->situacao) {

            self::SITUACAO_RASCUNHO =>
            'text-bg-secondary',

            self::SITUACAO_EMITIDA =>
            'text-bg-success',

            self::SITUACAO_CANCELADA =>
            'text-bg-danger',

            default =>
            'text-bg-dark',
        };
    }

    public function podeEditar(): bool
    {
        return $this->situacao === self::SITUACAO_RASCUNHO;
    }

    public function podeEmitir(): bool
    {
        return $this->situacao === self::SITUACAO_RASCUNHO
            && $this->quantidadeItens > 0;
    }

    public function podeCancelar(): bool
    {
        return in_array(
            $this->situacao,
            [
                self::SITUACAO_RASCUNHO,
                self::SITUACAO_EMITIDA,
            ],
            true
        );
    }

    public function podeExcluir(): bool
    {
        return $this->situacao === self::SITUACAO_RASCUNHO;
    }

    public function somenteLeitura(): bool
    {
        return !$this->podeEditar();
    }

    // ---------------------------------------------------------
    // FORMATAÇÃO DE DATAS
    // ---------------------------------------------------------

    public function dataPrescricaoBR(): string
    {
        return self::formatarData(
            $this->dataPrescricao,
            'd/m/Y H:i'
        );
    }

    public function dataPrescricaoFormInput(): string
    {
        return self::formatarData(
            $this->dataPrescricao,
            'Y-m-d\TH:i',
            ''
        );
    }

    public function assinadoEmBR(): string
    {
        return self::formatarData(
            $this->assinadoEm,
            'd/m/Y H:i'
        );
    }

    public function criadoEmBR(): string
    {
        return self::formatarData(
            $this->criadoEm,
            'd/m/Y H:i'
        );
    }

    public function atualizadoEmBR(): string
    {
        return self::formatarData(
            $this->atualizadoEm,
            'd/m/Y H:i'
        );
    }

    private static function formatarData(
        ?string $valor,
        string $formato,
        string $fallback = '—'
    ): string {

        if ($valor === null || trim($valor) === '') {
            return $fallback;
        }

        try {

            return (new DateTime($valor))->format($formato);
        } catch (Throwable) {

            return $fallback === '—'
                ? $valor
                : $fallback;
        }
    }

    // ---------------------------------------------------------
    // HELPERS
    // ---------------------------------------------------------

    public function resumo(): string
    {
        $paciente = $this->pacienteNome
            ?? ('Paciente #' . $this->pacienteId);

        return $paciente
            . ' — '
            . $this->dataPrescricaoBR();
    }

    public function itensResumo(): string
    {
        if (empty($this->itens)) {
            return 'Nenhum medicamento informado.';
        }

        $nomes = [];

        foreach ($this->itens as $item) {

            $nome = trim(
                (string) ($item['nome_medicamento'] ?? '')
            );

            if ($nome !== '') {
                $nomes[] = $nome;
            }
        }

        return implode(', ', $nomes);
    }

    public function assinaturaExiste(): bool
    {
        return $this->assinaturaDigital !== null
            && trim($this->assinaturaDigital) !== '';
    }

    public function quantidadeItensLabel(): string
    {
        return $this->quantidadeItens
            . (
                $this->quantidadeItens === 1
                ? ' medicamento'
                : ' medicamentos'
            );
    }
}
