<?php
// controller/prontuariosDAO.php

require_once __DIR__ . '/../configs/bdConexao.php';
require_once __DIR__ . '/../models/prontuarios.php';

class ProntuarioDAO
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? BdConexao::getConexao();
    }

    /** Insere um prontuário e devolve o id gerado. */
    public function inserir(Prontuario $p): int
    {
        $sql = "INSERT INTO prontuarios
                    (paciente_id, profissional_id, agendamento_id, data_registro,
                     queixa, historico_clinico, diagnostico, tratamento, observacoes,
                     peso, altura, pressao_arterial)
                VALUES
                    (:paciente_id, :profissional_id, :agendamento_id, :data_registro,
                     :queixa, :historico_clinico, :diagnostico, :tratamento, :observacoes,
                     :peso, :altura, :pressao_arterial)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':paciente_id'       => $p->pacienteId,
            ':profissional_id'   => $p->profissionalId,
            ':agendamento_id'    => $p->agendamentoId,
            ':data_registro'     => $p->dataRegistro,
            ':queixa'            => $p->queixa,
            ':historico_clinico' => $p->historicoClinico,
            ':diagnostico'       => $p->diagnostico,
            ':tratamento'        => $p->tratamento,
            ':observacoes'       => $p->observacoes,
            ':peso'              => $p->peso,
            ':altura'            => $p->altura,
            ':pressao_arterial'  => $p->pressaoArterial,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Atualiza um prontuário existente. */
    public function atualizar(Prontuario $p): bool
    {
        if ($p->id === null) {
            throw new InvalidArgumentException('ID do prontuário é obrigatório para atualização.');
        }

        $sql = "UPDATE prontuarios SET
                    paciente_id       = :paciente_id,
                    profissional_id   = :profissional_id,
                    agendamento_id    = :agendamento_id,
                    data_registro     = :data_registro,
                    queixa            = :queixa,
                    historico_clinico = :historico_clinico,
                    diagnostico       = :diagnostico,
                    tratamento        = :tratamento,
                    observacoes       = :observacoes,
                    peso              = :peso,
                    altura            = :altura,
                    pressao_arterial  = :pressao_arterial
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id'                => $p->id,
            ':paciente_id'       => $p->pacienteId,
            ':profissional_id'   => $p->profissionalId,
            ':agendamento_id'    => $p->agendamentoId,
            ':data_registro'     => $p->dataRegistro,
            ':queixa'            => $p->queixa,
            ':historico_clinico' => $p->historicoClinico,
            ':diagnostico'       => $p->diagnostico,
            ':tratamento'        => $p->tratamento,
            ':observacoes'       => $p->observacoes,
            ':peso'              => $p->peso,
            ':altura'            => $p->altura,
            ':pressao_arterial'  => $p->pressaoArterial,
        ]);
    }

    /** Exclui um prontuário pelo id. */
    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM prontuarios WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /** Busca por id, com nomes de paciente e profissional via JOIN. */
    public function buscarPorId(int $id): ?Prontuario
    {
        $stmt = $this->pdo->prepare(
            "SELECT pr.*,
                    p.nome AS paciente_nome,
                    u.nome AS profissional_nome,
                    CONCAT(DATE_FORMAT(a.data_agendamento, '%d/%m/%Y'),
                           ' ', LEFT(a.hora_inicio, 5)) AS agendamento_resumo
             FROM prontuarios pr
             INNER JOIN pacientes p ON p.id = pr.paciente_id
             INNER JOIN usuarios  u ON u.id = pr.profissional_id
             LEFT  JOIN agendamentos a ON a.id = pr.agendamento_id
             WHERE pr.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $dados = $stmt->fetch();

        return $dados ? self::hidratar($dados) : null;
    }

    /** Lista todos os prontuários (mais recentes primeiro). */
    public function listar(): array
    {
        $stmt = $this->pdo->query(
            "SELECT pr.*,
                    p.nome AS paciente_nome,
                    u.nome AS profissional_nome,
                    CONCAT(DATE_FORMAT(a.data_agendamento, '%d/%m/%Y'),
                           ' ', LEFT(a.hora_inicio, 5)) AS agendamento_resumo
             FROM prontuarios pr
             INNER JOIN pacientes p ON p.id = pr.paciente_id
             INNER JOIN usuarios  u ON u.id = pr.profissional_id
             LEFT  JOIN agendamentos a ON a.id = pr.agendamento_id
             ORDER BY pr.data_registro DESC, pr.id DESC"
        );

        $out = [];
        while ($linha = $stmt->fetch()) {
            $out[] = self::hidratar($linha);
        }
        return $out;
    }

    /**
     * Filtros combináveis:
     *  - $buscaNome      → LIKE em pacientes.nome
     *  - $profissionalId → match em profissionais.id
     *  - $dataFiltro     → match na data (DATE(data_registro)) — formato Y-m-d
     *
     * @return Prontuario[]
     */
    public function buscarComFiltros(
        string $buscaNome = '',
        int $profissionalId = 0,
        string $dataFiltro = ''
    ): array {
        $sql = "SELECT pr.*,
                       p.nome AS paciente_nome,
                       u.nome AS profissional_nome,
                       CONCAT(DATE_FORMAT(a.data_agendamento, '%d/%m/%Y'),
                              ' ', LEFT(a.hora_inicio, 5)) AS agendamento_resumo
                FROM prontuarios pr
                INNER JOIN pacientes p ON p.id = pr.paciente_id
                INNER JOIN usuarios  u ON u.id = pr.profissional_id
                LEFT  JOIN agendamentos a ON a.id = pr.agendamento_id
                WHERE 1=1";

        $params = [];

        if ($buscaNome !== '') {
            $sql .= " AND p.nome LIKE :busca";
            $params[':busca'] = '%' . $buscaNome . '%';
        }

        if ($profissionalId > 0) {
            $sql .= " AND pr.profissional_id = :prof";
            $params[':prof'] = $profissionalId;
        }

        if ($dataFiltro !== '') {
            $sql .= " AND DATE(pr.data_registro) = :data";
            $params[':data'] = $dataFiltro;
        }

        $sql .= " ORDER BY pr.data_registro DESC, pr.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $out = [];
        while ($linha = $stmt->fetch()) {
            $out[] = self::hidratar($linha);
        }
        return $out;
    }

    /** Lista prontuários de um paciente específico. */
    public function buscarPorPaciente(int $pacienteId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT pr.*,
                    p.nome AS paciente_nome,
                    u.nome AS profissional_nome
             FROM prontuarios pr
             INNER JOIN pacientes p ON p.id = pr.paciente_id
             INNER JOIN usuarios  u ON u.id = pr.profissional_id
             WHERE pr.paciente_id = :pid
             ORDER BY pr.data_registro DESC"
        );
        $stmt->execute([':pid' => $pacienteId]);

        $out = [];
        while ($linha = $stmt->fetch()) {
            $out[] = self::hidratar($linha);
        }
        return $out;
    }

    /** Lista pacientes para o <select>. */
    public function listarPacientes(): array
    {
        $stmt = $this->pdo->query("SELECT id, nome FROM pacientes ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    /** Lista profissionais ativos para o <select>. */
    public function listarProfissionais(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, nome FROM usuarios
             WHERE ativo = 1 AND papel IN ('profissional', 'admin')
             ORDER BY nome ASC"
        );
        return $stmt->fetchAll();
    }

    /** Lista agendamentos de um paciente (para vincular ao prontuário). */
    public function listarAgendamentosPorPaciente(int $pacienteId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, data_agendamento, hora_inicio, tipo, situacao
             FROM agendamentos
             WHERE paciente_id = :pid
             ORDER BY data_agendamento DESC, hora_inicio DESC
             LIMIT 100"
        );
        $stmt->execute([':pid' => $pacienteId]);
        return $stmt->fetchAll();
    }

    /** Verifica se o paciente existe. */
    public function pacienteExiste(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM pacientes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() !== false;
    }

    /** Verifica se o profissional existe, está ativo e é apto. */
    public function profissionalValido(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM usuarios
             WHERE id = :id AND ativo = 1
               AND papel IN ('profissional', 'admin')
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() !== false;
    }

    /** Verifica se o agendamento pertence ao paciente informado. */
    public function agendamentoPertenceAoPaciente(int $agendamentoId, int $pacienteId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM agendamentos
             WHERE id = :aid AND paciente_id = :pid
             LIMIT 1"
        );
        $stmt->execute([':aid' => $agendamentoId, ':pid' => $pacienteId]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Retorna um prontuário já vinculado ao agendamento informado,
     * ou null. Garante a regra 1:1 (um prontuário por atendimento).
     */
    public function prontuarioDoAgendamento(int $agendamentoId, ?int $ignorarId = null): ?Prontuario
    {
        $sql = "SELECT pr.*,
                       p.nome AS paciente_nome,
                       u.nome AS profissional_nome
                FROM prontuarios pr
                INNER JOIN pacientes p ON p.id = pr.paciente_id
                INNER JOIN usuarios  u ON u.id = pr.profissional_id
                WHERE pr.agendamento_id = :aid";
        if ($ignorarId !== null) {
            $sql .= " AND pr.id != :id";
        }
        $sql .= " LIMIT 1";

        $params = [':aid' => $agendamentoId];
        if ($ignorarId !== null) {
            $params[':id'] = $ignorarId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $dados = $stmt->fetch();

        return $dados ? self::hidratar($dados) : null;
    }

    /** Converte array do banco em objeto Prontuario. */
    private static function hidratar(array $d): Prontuario
    {
        $p = new Prontuario();
        $p->id               = isset($d['id']) ? (int) $d['id'] : null;
        $p->pacienteId       = (int) ($d['paciente_id'] ?? 0);
        $p->profissionalId   = (int) ($d['profissional_id'] ?? 0);
        $p->agendamentoId    = !empty($d['agendamento_id']) ? (int) $d['agendamento_id'] : null;
        $p->dataRegistro     = (string) ($d['data_registro'] ?? '');
        $p->queixa           = !empty($d['queixa']) ? (string) $d['queixa'] : null;
        $p->historicoClinico = !empty($d['historico_clinico']) ? (string) $d['historico_clinico'] : null;
        $p->diagnostico      = !empty($d['diagnostico']) ? (string) $d['diagnostico'] : null;
        $p->tratamento       = !empty($d['tratamento']) ? (string) $d['tratamento'] : null;
        $p->observacoes      = !empty($d['observacoes']) ? (string) $d['observacoes'] : null;
        $p->peso             = isset($d['peso']) && $d['peso'] !== null ? (float) $d['peso'] : null;
        $p->altura           = isset($d['altura']) && $d['altura'] !== null ? (float) $d['altura'] : null;
        $p->pressaoArterial  = !empty($d['pressao_arterial']) ? (string) $d['pressao_arterial'] : null;
        $p->criadoEm         = $d['criado_em'] ?? null;
        $p->atualizadoEm     = $d['atualizado_em'] ?? null;

        if (isset($d['paciente_nome']))     $p->pacienteNome = (string) $d['paciente_nome'];
        if (isset($d['profissional_nome'])) $p->profissionalNome = (string) $d['profissional_nome'];
        if (isset($d['agendamento_resumo'])) $p->agendamentoResumo = (string) $d['agendamento_resumo'];

        return $p;
    }
}
