<?php
// controller/prescricoesDAO.php

declare(strict_types=1);

require_once __DIR__ . '/../configs/bdConexao.php';
require_once __DIR__ . '/../models/prescricoes.php';

class PrescricaoDAO
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? BdConexao::getConexao();

        $this->pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $this->pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );
    }

    // ---------------------------------------------------------
    // CRIAR PRESCRIÇÃO + ITENS
    // ---------------------------------------------------------

    /**
     * Insere o cabeçalho e todos os medicamentos atomicamente.
     *
     * @param Prescricao $p
     * @param array $itens
     */
    public function inserir(Prescricao $p, array $itens): int
    {
        if ($p->situacao !== Prescricao::SITUACAO_RASCUNHO) {
            throw new DomainException(
                'Uma nova prescrição deve começar como rascunho.'
            );
        }

        if (empty($itens)) {
            throw new DomainException(
                'Adicione pelo menos um medicamento à prescrição.'
            );
        }

        return $this->transacao(function () use ($p, $itens): int {

            $sql = "
                INSERT INTO prescricoes (
                    paciente_id,
                    profissional_id,
                    prontuario_id,
                    data_prescricao,
                    situacao,
                    assinatura_digital,
                    assinado_em,
                    observacoes
                ) VALUES (
                    :paciente_id,
                    :profissional_id,
                    :prontuario_id,
                    :data_prescricao,
                    'rascunho',
                    NULL,
                    NULL,
                    :observacoes
                )
            ";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':paciente_id' => $p->pacienteId,
                ':profissional_id' => $p->profissionalId,
                ':prontuario_id' => $p->prontuarioId,
                ':data_prescricao' => $p->dataPrescricao,
                ':observacoes' => $p->observacoes,
            ]);

            $novoId = (int) $this->pdo->lastInsertId();

            $this->salvarItens($novoId, $itens);

            return $novoId;
        });
    }

    // ---------------------------------------------------------
    // ATUALIZAR PRESCRIÇÃO + ITENS
    // ---------------------------------------------------------

    /**
     * Atualiza somente prescrições em rascunho.
     *
     * Os itens antigos são substituídos pelos novos dentro
     * da mesma transação.
     *
     * Não existe possibilidade de atualizar apenas o cabeçalho
     * e deixar os medicamentos inconsistentes.
     */
    public function atualizar(Prescricao $p, array $itens): bool
    {
        if ($p->id === null || $p->id <= 0) {
            throw new InvalidArgumentException(
                'ID da prescrição inválido.'
            );
        }

        if (empty($itens)) {
            throw new DomainException(
                'A prescrição precisa possuir pelo menos um medicamento.'
            );
        }

        return $this->transacao(function () use ($p, $itens): bool {

            $situacao = $this->buscarSituacaoComLock(
                (int) $p->id
            );

            if ($situacao === null) {
                throw new DomainException(
                    'Prescrição não encontrada.'
                );
            }

            if ($situacao !== Prescricao::SITUACAO_RASCUNHO) {
                throw new DomainException(
                    'Somente prescrições em rascunho podem ser editadas.'
                );
            }

            $sql = "
                UPDATE prescricoes SET

                    paciente_id = :paciente_id,

                    profissional_id = :profissional_id,

                    prontuario_id = :prontuario_id,

                    data_prescricao = :data_prescricao,

                    observacoes = :observacoes

                WHERE id = :id
                  AND situacao = 'rascunho'
            ";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':id' => $p->id,
                ':paciente_id' => $p->pacienteId,
                ':profissional_id' => $p->profissionalId,
                ':prontuario_id' => $p->prontuarioId,
                ':data_prescricao' => $p->dataPrescricao,
                ':observacoes' => $p->observacoes,
            ]);

            // Substituição atômica dos itens.
            $stmtExcluir = $this->pdo->prepare(
                'DELETE FROM itens_prescricao
                 WHERE prescricao_id = :id'
            );

            $stmtExcluir->execute([
                ':id' => $p->id,
            ]);

            $this->salvarItens(
                (int) $p->id,
                $itens
            );

            return true;
        });
    }

    // ---------------------------------------------------------
    // ITENS DA PRESCRIÇÃO
    // ---------------------------------------------------------

    /**
     * Insere todos os itens de uma prescrição.
     *
     * A FK fk_itens_prescricao_prescricao garante
     * que não existam itens sem prescrição.
     */
    private function salvarItens(
        int $prescricaoId,
        array $itens
    ): void {

        $sql = "
            INSERT INTO itens_prescricao (

                prescricao_id,

                nome_medicamento,

                principio_ativo,

                apresentacao,

                concentracao,

                dosagem,

                via_administracao,

                frequencia,

                duracao,

                quantidade,

                instrucoes,

                dados_dosagem

            ) VALUES (

                :prescricao_id,

                :nome_medicamento,

                :principio_ativo,

                :apresentacao,

                :concentracao,

                :dosagem,

                :via_administracao,

                :frequencia,

                :duracao,

                :quantidade,

                :instrucoes,

                :dados_dosagem

            )
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($itens as $item) {

            $nome = trim(
                (string) ($item['nome_medicamento'] ?? '')
            );

            if ($nome === '') {
                throw new DomainException(
                    'O nome do medicamento é obrigatório.'
                );
            }

            $stmt->execute([

                ':prescricao_id' => $prescricaoId,

                ':nome_medicamento' => $nome,

                ':principio_ativo' =>
                $item['principio_ativo'] ?? null,

                ':apresentacao' =>
                $item['apresentacao'] ?? null,

                ':concentracao' =>
                $item['concentracao'] ?? null,

                ':dosagem' =>
                $item['dosagem'] ?? null,

                ':via_administracao' =>
                $item['via_administracao'] ?? null,

                ':frequencia' =>
                $item['frequencia'] ?? null,

                ':duracao' =>
                $item['duracao'] ?? null,

                ':quantidade' =>
                $item['quantidade'] ?? null,

                ':instrucoes' =>
                $item['instrucoes'] ?? null,

                ':dados_dosagem' =>
                $item['dados_dosagem'] ?? null,

            ]);
        }
    }

    /**
     * Busca os medicamentos vinculados a uma prescrição.
     */
    public function listarItensPorPrescricao(int $prescricaoId): array
    {
        $sql = "
            SELECT
                id,
                prescricao_id,
                nome_medicamento,
                principio_ativo,
                apresentacao,
                concentracao,
                dosagem,
                via_administracao,
                frequencia,
                duracao,
                quantidade,
                instrucoes,
                dados_dosagem,
                criado_em

            FROM itens_prescricao

            WHERE prescricao_id = :prescricao_id

            ORDER BY id ASC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':prescricao_id' => $prescricaoId,
        ]);

        return $stmt->fetchAll();
    }

    // ---------------------------------------------------------
    // EMITIR PRESCRIÇÃO
    // ---------------------------------------------------------

    /**
     * Emite uma prescrição.
     *
     * O payload deve ter sido produzido por um serviço externo
     * de assinatura digital.
     *
     * Este DAO NÃO valida certificados ou assinaturas ICP-Brasil.
     */
    public function emitir(
        int $id,
        string $assinaturaDigital
    ): bool {

        $assinaturaDigital = trim($assinaturaDigital);

        if ($id <= 0) {
            throw new InvalidArgumentException(
                'ID inválido.'
            );
        }

        if ($assinaturaDigital === '') {
            throw new DomainException(
                'O payload de assinatura é obrigatório.'
            );
        }

        // A coluna TEXT suporta no máximo 65.535 bytes.
        if (strlen($assinaturaDigital) > 60000) {
            throw new DomainException(
                'O payload de assinatura excede o limite permitido.'
            );
        }

        return $this->transacao(function () use (
            $id,
            $assinaturaDigital
        ): bool {

            $situacao = $this->buscarSituacaoComLock($id);

            if ($situacao === null) {
                throw new DomainException(
                    'Prescrição não encontrada.'
                );
            }

            if ($situacao !== Prescricao::SITUACAO_RASCUNHO) {
                throw new DomainException(
                    'Somente prescrições em rascunho podem ser emitidas.'
                );
            }

            $stmtItens = $this->pdo->prepare(
                'SELECT COUNT(*)
                 FROM itens_prescricao
                 WHERE prescricao_id = :id'
            );

            $stmtItens->execute([
                ':id' => $id,
            ]);

            $quantidade = (int) $stmtItens->fetchColumn();

            if ($quantidade <= 0) {
                throw new DomainException(
                    'Não é possível emitir uma prescrição sem medicamentos.'
                );
            }

            $sql = "
                UPDATE prescricoes SET

                    situacao = 'emitida',

                    assinatura_digital = :assinatura,

                    assinado_em = CURRENT_TIMESTAMP

                WHERE id = :id
                  AND situacao = 'rascunho'
            ";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':assinatura' => $assinaturaDigital,
                ':id' => $id,
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new DomainException(
                    'Não foi possível concluir a emissão.'
                );
            }

            return true;
        });
    }

    // ---------------------------------------------------------
    // CANCELAR
    // ---------------------------------------------------------

    /**
     * Cancela uma prescrição preservando seu histórico.
     *
     * Não apaga os medicamentos nem a assinatura.
     */
    public function cancelar(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'ID inválido para cancelamento.'
            );
        }

        return $this->transacao(function () use ($id): bool {

            $situacao = $this->buscarSituacaoComLock($id);

            if ($situacao === null) {
                throw new DomainException(
                    'Prescrição não encontrada.'
                );
            }

            if ($situacao === Prescricao::SITUACAO_CANCELADA) {
                throw new DomainException(
                    'Esta prescrição já está cancelada.'
                );
            }

            $stmt = $this->pdo->prepare(
                "UPDATE prescricoes
                 SET situacao = 'cancelada'
                 WHERE id = :id"
            );

            $stmt->execute([
                ':id' => $id,
            ]);

            return $stmt->rowCount() === 1;
        });
    }

    // ---------------------------------------------------------
    // EXCLUIR
    // ---------------------------------------------------------

    /**
     * Exclui exclusivamente prescrições em rascunho.
     *
     * Os medicamentos associados são removidos pela FK
     * ON DELETE CASCADE.
     */
    public function excluir(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'ID inválido para exclusão.'
            );
        }

        return $this->transacao(function () use ($id): bool {

            $situacao = $this->buscarSituacaoComLock($id);

            if ($situacao === null) {
                throw new DomainException(
                    'Prescrição não encontrada.'
                );
            }

            if ($situacao !== Prescricao::SITUACAO_RASCUNHO) {
                throw new DomainException(
                    'Somente prescrições em rascunho podem ser excluídas. Cancele as demais.'
                );
            }

            $stmt = $this->pdo->prepare(
                "DELETE FROM prescricoes
                 WHERE id = :id
                   AND situacao = 'rascunho'"
            );

            $stmt->execute([
                ':id' => $id,
            ]);

            return $stmt->rowCount() === 1;
        });
    }

    // ---------------------------------------------------------
    // CONSULTAS
    // ---------------------------------------------------------

    /**
     * Busca uma prescrição completa, incluindo medicamentos.
     */
    public function buscarPorId(int $id): ?Prescricao
    {
        $sql = $this->sqlBase()
            . ' WHERE pr.id = :id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $id,
        ]);

        $dados = $stmt->fetch();

        if (!$dados) {
            return null;
        }

        $prescricao = self::hidratar($dados);

        $prescricao->itens = $this->listarItensPorPrescricao(
            $id
        );

        return $prescricao;
    }

    /**
     * Lista todas as prescrições.
     *
     * @return Prescricao[]
     */
    public function listar(): array
    {
        return $this->buscarComFiltros();
    }

    /**
     * Busca otimizada com filtros combináveis.
     *
     * Os filtros por data usam intervalos para preservar
     * o uso dos índices da coluna data_prescricao.
     *
     * @return Prescricao[]
     */
    public function buscarComFiltros(
        string $busca = '',
        int $profissionalId = 0,
        string $situacao = '',
        string $dataInicio = '',
        string $dataFim = ''
    ): array {

        $sql = $this->sqlBase() . ' WHERE 1 = 1';

        $params = [];

        // Busca textual.
        if ($busca !== '') {

            $termo = '%' . $busca . '%';

            $sql .= "
                AND (
                    p.nome LIKE :busca_paciente

                    OR u.nome LIKE :busca_profissional

                    OR pr.observacoes LIKE :busca_observacoes

                    OR CAST(pr.id AS CHAR) = :busca_id

                    OR EXISTS (
                        SELECT 1
                        FROM itens_prescricao ip
                        WHERE ip.prescricao_id = pr.id
                          AND ip.nome_medicamento LIKE :busca_item
                    )
                )
            ";

            $params[':busca_paciente'] = $termo;
            $params[':busca_profissional'] = $termo;
            $params[':busca_observacoes'] = $termo;
            $params[':busca_id'] = $busca;
            $params[':busca_item'] = $termo;
        }

        // Profissional.
        if ($profissionalId > 0) {

            $sql .= "
                AND pr.profissional_id = :profissional_id
            ";

            $params[':profissional_id'] = $profissionalId;
        }

        // Situação.
        if (array_key_exists(
            $situacao,
            Prescricao::SITUACOES
        )) {

            $sql .= "
                AND pr.situacao = :situacao
            ";

            $params[':situacao'] = $situacao;
        }

        // Data inicial.
        if ($this->dataISOValida($dataInicio)) {

            $sql .= "
                AND pr.data_prescricao >= :data_inicio
            ";

            $params[':data_inicio'] = $dataInicio . ' 00:00:00';
        }

        // Data final exclusiva.
        if ($this->dataISOValida($dataFim)) {

            $sql .= "
                AND pr.data_prescricao < DATE_ADD(
                    :data_fim,
                    INTERVAL 1 DAY
                )
            ";

            $params[':data_fim'] = $dataFim . ' 00:00:00';
        }

        $sql .= "
            ORDER BY
                pr.data_prescricao DESC,
                pr.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        return array_map(
            [self::class, 'hidratar'],
            $stmt->fetchAll()
        );
    }

    /**
     * Consulta base usada pelas pesquisas.
     *
     * COUNT dos itens é realizado em subconsulta para evitar
     * duplicação dos registros de prescrição.
     */
    private function sqlBase(): string
    {
        return "
            SELECT

                pr.*,

                p.nome AS paciente_nome,

                u.nome AS profissional_nome,

                CASE
                    WHEN pront.id IS NULL THEN NULL

                    ELSE CONCAT(
                        '#',
                        pront.id,
                        ' — ',
                        DATE_FORMAT(
                            pront.data_registro,
                            '%d/%m/%Y %H:%i'
                        )
                    )
                END AS prontuario_resumo,

                (
                    SELECT COUNT(*)
                    FROM itens_prescricao ip
                    WHERE ip.prescricao_id = pr.id
                ) AS quantidade_itens

            FROM prescricoes pr

            INNER JOIN pacientes p
                ON p.id = pr.paciente_id

            INNER JOIN usuarios u
                ON u.id = pr.profissional_id

            LEFT JOIN prontuarios pront
                ON pront.id = pr.prontuario_id
        ";
    }

    // ---------------------------------------------------------
    // LISTAS PARA SELECT
    // ---------------------------------------------------------

    public function listarPacientes(): array
    {
        $sql = "
            SELECT id, nome

            FROM pacientes

            ORDER BY nome ASC
        ";

        return $this->pdo
            ->query($sql)
            ->fetchAll();
    }

    public function listarProfissionais(): array
    {
        $sql = "
            SELECT
                id,
                nome,
                papel,
                numero_registro,
                tipo_registro

            FROM usuarios

            WHERE ativo = 1

              AND papel IN (
                'profissional',
                'admin'
              )

            ORDER BY nome ASC
        ";

        return $this->pdo
            ->query($sql)
            ->fetchAll();
    }

    /**
     * Lista prontuários pertencentes a determinado paciente.
     */
    public function listarProntuariosPorPaciente(
        int $pacienteId
    ): array {

        $sql = "
            SELECT
                id,
                paciente_id,
                data_registro,
                diagnostico

            FROM prontuarios

            WHERE paciente_id = :paciente_id

            ORDER BY
                data_registro DESC,
                id DESC

            LIMIT 100
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':paciente_id' => $pacienteId,
        ]);

        return $stmt->fetchAll();
    }

    // ---------------------------------------------------------
    // VALIDADORES DE RELACIONAMENTO
    // ---------------------------------------------------------

    public function pacienteExiste(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM pacientes
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            ':id' => $id,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function profissionalValido(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1
             FROM usuarios

             WHERE id = :id

               AND ativo = 1

               AND papel IN (
                    'profissional',
                    'admin'
               )

             LIMIT 1"
        );

        $stmt->execute([
            ':id' => $id,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Confirma que o prontuário está vinculado ao paciente.
     */
    public function prontuarioPertenceAoPaciente(
        int $prontuarioId,
        int $pacienteId
    ): bool {

        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM prontuarios

             WHERE id = :prontuario_id

               AND paciente_id = :paciente_id

             LIMIT 1'
        );

        $stmt->execute([
            ':prontuario_id' => $prontuarioId,
            ':paciente_id' => $pacienteId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    // ---------------------------------------------------------
    // TRANSAÇÕES
    // ---------------------------------------------------------

    /**
     * Obtém a situação bloqueando a linha até o final
     * da transação.
     */
    private function buscarSituacaoComLock(int $id): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT situacao
             FROM prescricoes
             WHERE id = :id
             FOR UPDATE'
        );

        $stmt->execute([
            ':id' => $id,
        ]);

        $situacao = $stmt->fetchColumn();

        return $situacao === false
            ? null
            : (string) $situacao;
    }

    /**
     * Gerenciador transacional.
     *
     * Respeita transações externas, evitando commit indevido.
     */
    private function transacao(callable $operacao): mixed
    {
        $iniciouAqui = !$this->pdo->inTransaction();

        if ($iniciouAqui) {
            $this->pdo->beginTransaction();
        }

        try {

            $resultado = $operacao();

            if (
                $iniciouAqui
                && $this->pdo->inTransaction()
            ) {
                $this->pdo->commit();
            }

            return $resultado;
        } catch (Throwable $e) {

            if (
                $iniciouAqui
                && $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ---------------------------------------------------------
    // VALIDADORES INTERNOS
    // ---------------------------------------------------------

    private function dataISOValida(string $data): bool
    {
        if (
            !preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $data
            )
        ) {
            return false;
        }

        $dt = DateTime::createFromFormat(
            '!Y-m-d',
            $data
        );

        return $dt !== false
            && $dt->format('Y-m-d') === $data;
    }

    // ---------------------------------------------------------
    // HIDRATAÇÃO
    // ---------------------------------------------------------

    private static function hidratar(array $d): Prescricao
    {
        $p = new Prescricao();

        $p->id = isset($d['id'])
            ? (int) $d['id']
            : null;

        $p->pacienteId = (int) (
            $d['paciente_id'] ?? 0
        );

        $p->profissionalId = (int) (
            $d['profissional_id'] ?? 0
        );

        $p->prontuarioId =
            isset($d['prontuario_id'])
            && $d['prontuario_id'] !== null
            ? (int) $d['prontuario_id']
            : null;

        $p->dataPrescricao = (string) (
            $d['data_prescricao'] ?? ''
        );

        $p->situacao = (string) (
            $d['situacao']
            ?? Prescricao::SITUACAO_RASCUNHO
        );

        $p->assinaturaDigital = isset(
            $d['assinatura_digital']
        )
            ? (string) $d['assinatura_digital']
            : null;

        $p->assinadoEm = isset($d['assinado_em'])
            ? (string) $d['assinado_em']
            : null;

        $p->observacoes = isset($d['observacoes'])
            ? (string) $d['observacoes']
            : null;

        $p->criadoEm = isset($d['criado_em'])
            ? (string) $d['criado_em']
            : null;

        $p->atualizadoEm = isset($d['atualizado_em'])
            ? (string) $d['atualizado_em']
            : null;

        $p->pacienteNome = isset($d['paciente_nome'])
            ? (string) $d['paciente_nome']
            : null;

        $p->profissionalNome = isset($d['profissional_nome'])
            ? (string) $d['profissional_nome']
            : null;

        $p->prontuarioResumo = isset($d['prontuario_resumo'])
            ? (string) $d['prontuario_resumo']
            : null;

        $p->quantidadeItens = (int) (
            $d['quantidade_itens'] ?? 0
        );

        return $p;
    }
}
