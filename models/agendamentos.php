<?php
// models/agendamentos.php — entidade Agendamento + criação da tabela

class Agendamento
{
    public ?int $id = null;
    public int $pacienteId = 0;
    public int $profissionalId = 0;
    public string $dataAgendamento = '';       // Y-m-d
    public string $horaInicio = '';            // H:i
    public ?string $horaFim = null;            // H:i
    public string $tipo = 'consulta';
    public string $situacao = 'agendado';
    public string $origemAgendamento = 'interno';
    public bool $teleconsulta = false;
    public ?string $urlReuniao = null;
    public ?string $observacoes = null;
    public ?string $criadoEm = null;
    public ?string $atualizadoEm = null;

    // Campos derivados de JOINs (apenas para exibição)
    public ?string $pacienteNome = null;
    public ?string $profissionalNome = null;

    // Enums (fonte da verdade para validação)
    public const TIPOS = ['consulta', 'massagem', 'procedimento', 'retorno', 'outro'];
    public const SITUACOES = ['agendado', 'confirmado', 'em_atendimento', 'concluido', 'cancelado', 'faltou'];
    public const ORIGENS = ['interno', 'online', 'whatsapp'];

    /** Cria a tabela `agendamentos` caso ela ainda não exista. */
    public static function criarTabelaSeNaoExistir(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS agendamentos (
            id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            paciente_id           BIGINT UNSIGNED NOT NULL,
            profissional_id       BIGINT UNSIGNED NOT NULL,
            data_agendamento      DATE NOT NULL,
            hora_inicio           TIME NOT NULL,
            hora_fim              TIME NULL,
            tipo                  ENUM('consulta','massagem','procedimento','retorno','outro')
                                  NOT NULL DEFAULT 'consulta',
            situacao              ENUM('agendado','confirmado','em_atendimento','concluido','cancelado','faltou')
                                  NOT NULL DEFAULT 'agendado',
            origem_agendamento    ENUM('interno','online','whatsapp')
                                  NOT NULL DEFAULT 'interno',
            teleconsulta          BOOLEAN NOT NULL DEFAULT FALSE,
            url_reuniao           VARCHAR(500) NULL,
            observacoes           TEXT NULL,
            criado_em             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_agendamentos_paciente
                FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE RESTRICT,
            CONSTRAINT fk_agendamentos_profissional
                FOREIGN KEY (profissional_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
            INDEX idx_agendamentos_data (data_agendamento),
            INDEX idx_agendamentos_paciente (paciente_id),
            INDEX idx_agendamentos_profissional (profissional_id)
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);
    }

    // ---------- Helpers de apresentação ----------

    public function dataBR(): string
    {
        if ($this->dataAgendamento === '' || $this->dataAgendamento === null) return '—';
        try {
            return (new DateTime($this->dataAgendamento))->format('d/m/Y');
        } catch (Throwable $e) {
            return $this->dataAgendamento;
        }
    }

    public function dataBRComDia(): string
    {
        if ($this->dataAgendamento === '' || $this->dataAgendamento === null) return '—';
        try {
            $dt = new DateTime($this->dataAgendamento);
            $dias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            return $dt->format('d/m/Y') . ' (' . $dias[(int) $dt->format('w')] . ')';
        } catch (Throwable $e) {
            return $this->dataAgendamento;
        }
    }

    public function horaRange(): string
    {
        if ($this->horaInicio === '' || $this->horaInicio === null) return '—';
        $ini = substr($this->horaInicio, 0, 5);
        if ($this->horaFim === null || $this->horaFim === '') return $ini;
        return $ini . ' – ' . substr($this->horaFim, 0, 5);
    }

    public function tipoLabel(): string
    {
        return match ($this->tipo) {
            'consulta'     => 'Consulta',
            'massagem'     => 'Massagem',
            'procedimento' => 'Procedimento',
            'retorno'      => 'Retorno',
            'outro'        => 'Outro',
            default        => '—',
        };
    }

    public function situacaoLabel(): string
    {
        return match ($this->situacao) {
            'agendado'       => 'Agendado',
            'confirmado'     => 'Confirmado',
            'em_atendimento' => 'Em atendimento',
            'concluido'      => 'Concluído',
            'cancelado'      => 'Cancelado',
            'faltou'         => 'Faltou',
            default          => '—',
        };
    }

    public function situacaoBadgeClass(): string
    {
        return match ($this->situacao) {
            'agendado'       => 'badge-agendado',
            'confirmado'     => 'badge-confirmado',
            'em_atendimento' => 'badge-em-atendimento',
            'concluido'      => 'badge-concluido',
            'cancelado'      => 'badge-cancelado',
            'faltou'         => 'badge-faltou',
            default          => 'badge-fito-prof',
        };
    }

    public function origemLabel(): string
    {
        return match ($this->origemAgendamento) {
            'interno'  => 'Interno',
            'online'   => 'Online',
            'whatsapp' => 'WhatsApp',
            default    => '—',
        };
    }

    public function isHoje(): bool
    {
        return $this->dataAgendamento === (new DateTime())->format('Y-m-d');
    }
}
