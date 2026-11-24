<?php
// controller/agendamentosDAO.php

require_once __DIR__ . '/../configs/bdConexao.php';
require_once __DIR__ . '/../models/agendamentos.php';

class AgendamentoDAO
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? BdConexao::getConexao();
    }

    /** Insere um agendamento e devolve o id gerado. */
    public function inserir(Agendamento $a): int
    {
        $sql = "INSERT INTO agendamentos
                    (paciente_id, profissional_id, data_agendamento,
                     hora_inicio, hora_fim, tipo, situacao,
                     origem_agendamento, teleconsulta, url_reuniao, observacoes)
                VALUES
                    (:paciente_id, :profissional_id, :data_agendamento,
                     :hora_inicio, :hora_fim, :tipo, :situacao,
                     :origem_agendamento, :teleconsulta, :url_reuniao, :observacoes)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':paciente_id'        => $a->pacienteId,
            ':profissional_id'    => $a->profissionalId,
            ':data_agendamento'   => $a->dataAgendamento,
            ':hora_inicio'        => $a->horaInicio,
            ':hora_fim'           => $a->horaFim,
            ':tipo'               => $a->tipo,
            ':situacao'           => $a->situacao,
            ':origem_agendamento' => $a->origemAgendamento,
            ':teleconsulta'       => $a->teleconsulta ? 1 : 0,
            ':url_reuniao'        => $a->urlReuniao,
            ':observacoes'        => $a->observacoes,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Atualiza um agendamento existente. */
    public function atualizar(Agendamento $a): bool
    {
        if ($a->id === null) {
            throw new InvalidArgumentException('ID do agendamento é obrigatório para atualização.');
        }

        $sql = "UPDATE agendamentos SET
                    paciente_id        = :paciente_id,
                    profissional_id    = :profissional_id,
                    data_agendamento   = :data_agendamento,
                    hora_inicio        = :hora_inicio,
                    hora_fim           = :hora_fim,
                    tipo               = :tipo,
                    situacao           = :situacao,
                    origem_agendamento = :origem_agendamento,
                    teleconsulta       = :teleconsulta,
                    url_reuniao        = :url_reuniao,
                    observacoes        = :observacoes
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id'                 => $a->id,
            ':paciente_id'        => $a->pacienteId,
            ':profissional_id'    => $a->profissionalId,
            ':data_agendamento'   => $a->dataAgendamento,
            ':hora_inicio'        => $a->horaInicio,
            ':hora_fim'           => $a->horaFim,
            ':tipo'               => $a->tipo,
            ':situacao'           => $a->situacao,
            ':origem_agendamento' => $a->origemAgendamento,
            ':teleconsulta'       => $a->teleconsulta ? 1 : 0,
            ':url_reuniao'        => $a->urlReuniao,
            ':observacoes'        => $a->observacoes,
        ]);
    }

    /** Exclui um agendamento pelo id. */
    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM agendamentos WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /** Busca por id (com nomes do paciente e do profissional via JOIN). */
    public function buscarPorId(int $id): ?Agendamento
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, p.nome AS paciente_nome, u.nome AS profissional_nome
             FROM agendamentos a
             INNER JOIN pacientes p ON p.id = a.paciente_id
             INNER JOIN usuarios  u ON u.id = a.profissional_id
             WHERE a.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $dados = $stmt->fetch();

        return $dados ? self::hidratar($dados) : null;
    }

    /** Lista todos os agendamentos (mais recentes primeiro). */
    public function listar(): array
    {
        $stmt = $this->pdo->query(
            "SELECT a.*, p.nome AS paciente_nome, u.nome AS profissional_nome
             FROM agendamentos a
             INNER JOIN pacientes p ON p.id = a.paciente_id
             INNER JOIN usuarios  u ON u.id = a.profissional_id
             ORDER BY a.data_agendamento DESC, a.hora_inicio DESC"
        );

        $agendamentos = [];
        while ($linha = $stmt->fetch()) {
            $agendamentos[] = self::hidratar($linha);
        }
        return $agendamentos;
    }

    /**
     * Busca por filtros combináveis.
     *  - $buscaNome  → LIKE em `pacientes.nome`
     *  - $situacao   → match exato em `situacao` (se válida)
     *  - $dataFiltro → match exato em `data_agendamento` (Y-m-d)
     *
     * @return Agendamento[]
     */
    public function buscarComFiltros(string $buscaNome = '', string $situacao = '', string $dataFiltro = ''): array
    {
        $sql = "SELECT a.*, p.nome AS paciente_nome, u.nome AS profissional_nome
                FROM agendamentos a
                INNER JOIN pacientes p ON p.id = a.paciente_id
                INNER JOIN usuarios  u ON u.id = a.profissional_id
                WHERE 1=1";

        $params = [];

        if ($buscaNome !== '') {
            $sql .= " AND p.nome LIKE :busca";
            $params[':busca'] = '%' . $buscaNome . '%';
        }

        if ($situacao !== '' && in_array($situacao, Agendamento::SITUACOES, true)) {
            $sql .= " AND a.situacao = :situacao";
            $params[':situacao'] = $situacao;
        }

        if ($dataFiltro !== '') {
            $sql .= " AND a.data_agendamento = :data";
            $params[':data'] = $dataFiltro;
        }

        $sql .= " ORDER BY a.data_agendamento DESC, a.hora_inicio DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $agendamentos = [];
        while ($linha = $stmt->fetch()) {
            $agendamentos[] = self::hidratar($linha);
        }
        return $agendamentos;
    }

    /**
     * Verifica se há conflito de horário para o profissional.
     * Regra: sobreposição de intervalo [inicio, fim] no mesmo dia,
     * ignorando agendamentos cancelados ou marcados como falta.
     * Se hora_fim não informada, assume 30 min.
     * Retorna o agendamento conflitante ou null.
     */
    public function verificarConflitoHorario(
        int $profissionalId,
        string $data,
        string $horaInicio,
        ?string $horaFim = null,
        ?int $ignorarId = null
    ): ?Agendamento {
        $novoFim = ($horaFim !== null && $horaFim !== '')
            ? $horaFim
            : date('H:i', strtotime($horaInicio) + 1800); // +30 min

        $sql = "SELECT a.*, p.nome AS paciente_nome, u.nome AS profissional_nome
                FROM agendamentos a
                INNER JOIN pacientes p ON p.id = a.paciente_id
                INNER JOIN usuarios  u ON u.id = a.profissional_id
                WHERE a.profissional_id = :prof
                  AND a.data_agendamento = :data
                  AND a.situacao NOT IN ('cancelado', 'faltou')
                  AND a.hora_inicio < :novo_fim
                  AND COALESCE(a.hora_fim, ADDTIME(a.hora_inicio, '00:30:00')) > :novo_inicio";

        $params = [
            ':prof'        => $profissionalId,
            ':data'        => $data,
            ':novo_fim'    => $novoFim,
            ':novo_inicio' => $horaInicio,
        ];

        if ($ignorarId !== null) {
            $sql .= " AND a.id != :id";
            $params[':id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $dados = $stmt->fetch();

        return $dados ? self::hidratar($dados) : null;
    }

    /** Lista pacientes para o <select> (id + nome). */
    public function listarPacientes(): array
    {
        $stmt = $this->pdo->query("SELECT id, nome FROM pacientes ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    /**
     * Lista profissionais ativos para o <select>.
     * Considera usuários com papel 'profissional' ou 'admin'.
     */
    public function listarProfissionais(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, nome FROM usuarios
             WHERE ativo = 1 AND papel IN ('profissional', 'admin')
             ORDER BY nome ASC"
        );
        return $stmt->fetchAll();
    }

    /** Verifica se um paciente existe. */
    public function pacienteExiste(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM pacientes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() !== false;
    }

    /** Verifica se um profissional existe e está ativo. */
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

    /** Converte array do banco em objeto Agendamento. */
    private static function hidratar(array $d): Agendamento
    {
        $a = new Agendamento();
        $a->id                 = isset($d['id']) ? (int) $d['id'] : null;
        $a->pacienteId         = (int) ($d['paciente_id'] ?? 0);
        $a->profissionalId     = (int) ($d['profissional_id'] ?? 0);
        $a->dataAgendamento    = (string) ($d['data_agendamento'] ?? '');
        $a->horaInicio         = (string) ($d['hora_inicio'] ?? '');
        $a->horaFim            = !empty($d['hora_fim']) ? (string) $d['hora_fim'] : null;
        $a->tipo               = (string) ($d['tipo'] ?? 'consulta');
        $a->situacao           = (string) ($d['situacao'] ?? 'agendado');
        $a->origemAgendamento  = (string) ($d['origem_agendamento'] ?? 'interno');
        $a->teleconsulta       = (bool) ($d['teleconsulta'] ?? false);
        $a->urlReuniao         = !empty($d['url_reuniao']) ? (string) $d['url_reuniao'] : null;
        $a->observacoes        = !empty($d['observacoes']) ? (string) $d['observacoes'] : null;
        $a->criadoEm           = $d['criado_em'] ?? null;
        $a->atualizadoEm       = $d['atualizado_em'] ?? null;

        if (isset($d['paciente_nome'])) {
            $a->pacienteNome = (string) $d['paciente_nome'];
        }
        if (isset($d['profissional_nome'])) {
            $a->profissionalNome = (string) $d['profissional_nome'];
        }

        return $a;
    }
}
