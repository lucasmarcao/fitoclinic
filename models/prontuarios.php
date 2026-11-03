<?php
// models/prontuarios.php — entidade Prontuario + criação da tabela

class Prontuario
{
    public ?int $id = null;
    public int $pacienteId = 0;
    public int $profissionalId = 0;
    public ?int $agendamentoId = null;
    public string $dataRegistro = '';               // Y-m-d H:i:s
    public ?string $queixa = null;
    public ?string $historicoClinico = null;
    public ?string $diagnostico = null;
    public ?string $tratamento = null;
    public ?string $observacoes = null;
    public ?float $peso = null;                     // kg
    public ?float $altura = null;                   // metros
    public ?string $pressaoArterial = null;         // "120/80"
    public ?string $criadoEm = null;
    public ?string $atualizadoEm = null;

    // Derivados de JOIN
    public ?string $pacienteNome = null;
    public ?string $profissionalNome = null;
    public ?string $agendamentoResumo = null;

    // Enums não aplicáveis aqui, mas mantemos lista de campos clínicos
    public const CAMPOS_CLINICOS = ['queixa', 'historicoClinico', 'diagnostico', 'tratamento', 'observacoes'];

    /** Cria a tabela `prontuarios` caso ela ainda não exista. */
    public static function criarTabelaSeNaoExistir(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS prontuarios (
            id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            paciente_id        BIGINT UNSIGNED NOT NULL,
            profissional_id    BIGINT UNSIGNED NOT NULL,
            agendamento_id     BIGINT UNSIGNED NULL,
            data_registro      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            queixa             TEXT NULL,
            historico_clinico  TEXT NULL,
            diagnostico        TEXT NULL,
            tratamento         TEXT NULL,
            observacoes        TEXT NULL,
            peso               DECIMAL(6,2) NULL,
            altura             DECIMAL(5,2) NULL,
            pressao_arterial   VARCHAR(20) NULL,
            criado_em          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_prontuarios_paciente
                FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE RESTRICT,
            CONSTRAINT fk_prontuarios_profissional
                FOREIGN KEY (profissional_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
            CONSTRAINT fk_prontuarios_agendamento
                FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE SET NULL,
            INDEX idx_prontuarios_paciente (paciente_id),
            INDEX idx_prontuarios_data (data_registro)
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);
    }

    // ---------- Helpers de apresentação ----------

    public function dataRegistroBR(): string
    {
        if (empty($this->dataRegistro)) return '—';
        try {
            return (new DateTime($this->dataRegistro))->format('d/m/Y H:i');
        } catch (Throwable $e) {
            return $this->dataRegistro;
        }
    }

    public function dataRegistroBRComDia(): string
    {
        if (empty($this->dataRegistro)) return '—';
        try {
            $dt = new DateTime($this->dataRegistro);
            $dias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            return $dt->format('d/m/Y') . ' (' . $dias[(int) $dt->format('w')] . ') ' . $dt->format('H:i');
        } catch (Throwable $e) {
            return $this->dataRegistro;
        }
    }

    public function dataRegistroFormInput(): string
    {
        if (empty($this->dataRegistro)) return '';
        try {
            return (new DateTime($this->dataRegistro))->format('Y-m-d\TH:i');
        } catch (Throwable $e) {
            return '';
        }
    }

    public function pesoFormatado(): string
    {
        if ($this->peso === null || $this->peso <= 0) return '—';
        return number_format($this->peso, 2, ',', '.') . ' kg';
    }

    public function alturaFormatada(): string
    {
        if ($this->altura === null || $this->altura <= 0) return '—';
        return number_format($this->altura, 2, ',', '.') . ' m';
    }

    public function pressaoFormatada(): string
    {
        if (empty($this->pressaoArterial)) return '—';
        return $this->pressaoArterial . ' mmHg';
    }

    /** IMC = peso / altura² (arredondado a 1 casa). */
    public function imc(): ?float
    {
        if ($this->peso === null || $this->altura === null) return null;
        if ($this->peso <= 0 || $this->altura <= 0) return null;
        return round($this->peso / ($this->altura * $this->altura), 1);
    }

    public function imcLabel(): string
    {
        $imc = $this->imc();
        if ($imc === null) return '—';
        if ($imc < 18.5) return 'Baixo peso';
        if ($imc < 25.0) return 'Normal';
        if ($imc < 30.0) return 'Sobrepeso';
        if ($imc < 35.0) return 'Obesidade I';
        if ($imc < 40.0) return 'Obesidade II';
        return 'Obesidade III';
    }

    public function imcBadgeClass(): string
    {
        $imc = $this->imc();
        if ($imc === null) return 'badge-imc';
        if ($imc < 18.5) return 'badge-imc-baixo';
        if ($imc < 25.0) return 'badge-imc-normal';
        if ($imc < 30.0) return 'badge-imc-sobrepeso';
        return 'badge-imc-obesidade';
    }

    /** True se pelo menos um campo clínico estiver preenchido. */
    public function temConteudoClinico(): bool
    {
        foreach (self::CAMPOS_CLINICOS as $campo) {
            if (!empty(trim((string) $this->$campo))) return true;
        }
        return false;
    }

    /** Linha curta com data + profissional, útil para resumos e modais. */
    public function resumo(): string
    {
        $pac = $this->pacienteNome ?? ('#' . $this->pacienteId);
        return $pac . ' — ' . $this->dataRegistroBR();
    }
}
