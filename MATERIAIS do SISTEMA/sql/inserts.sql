-- ============================================================
-- CARGA INICIAL DE DADOS DE TESTE - FITO CLINIC
-- Baseado no arquivo fito_clinic.sql e nas 6 tabelas existentes.
--
-- Quantidades:
--   SEM FK: 6 usuarios + 6 pacientes
--   RELACOES 1:N (lado N): 3 agendamentos, 3 prontuarios,
--                           3 prescricoes e 3 itens_prescricao
--   RELACAO N:M: nao existe tabela associativa entre estas 6 tabelas.
--
-- IMPORTANTE:
--   * Os dados pessoais e clinicos abaixo sao ficticios.
--   * A senha de todos os usuarios e "FitoTeste@123".
--     O valor armazenado e um hash bcrypt compativel com password_verify().
--   * Assinaturas e itens de prescricao sao demonstrativos, sem validade clinica.
--   * Execute em um banco vazio (ou ajuste os IDs caso ja existam registros).
--   * O script nao cria nem altera tabelas.
-- ============================================================

USE fito_clinic;
SET NAMES utf8mb4;

START TRANSACTION;

-- ------------------------------------------------------------
-- 1. SEM FK: USUARIOS (6 registros)
-- IDs explicitos para permitir as referencias das tabelas filhas.
-- ------------------------------------------------------------

INSERT INTO usuarios (
    id, nome, email, senha_hash, papel,
    numero_registro, tipo_registro, telefone, ativo
) VALUES
(
    19, 'Administrador Fito Clinic', 'admin@fitoclinic.test',
    '$2y$12$CD1cRw8gH3VUin83Rqqk8uGXdFSznZxsWvRvqMwtlbTctCHj.ZvgK',
    'admin', NULL, NULL, '(44) 3030-1000', TRUE
),
(
    21, 'Ana Carolina Souza', 'ana.souza@fitoclinic.test',
    '$2y$12$CD1cRw8gH3VUin83Rqqk8uGXdFSznZxsWvRvqMwtlbTctCHj.ZvgK',
    'profissional', 'CRN-8 12345', 'CRN', '(44) 99900-1002', TRUE
),
(
    31, 'Bruno Henrique Martins', 'bruno.martins@fitoclinic.test',
    '$2y$12$CD1cRw8gH3VUin83Rqqk8uGXdFSznZxsWvRvqMwtlbTctCHj.ZvgK',
    'profissional', 'CREFITO-8 67890', 'CREFITO', '(44) 99900-1003', TRUE
),
(
    41, 'Carla Fernanda Lima', 'carla.lima@fitoclinic.test',
    '$2y$12$CD1cRw8gH3VUin83Rqqk8uGXdFSznZxsWvRvqMwtlbTctCHj.ZvgK',
    'profissional', 'CRBM-6 24680', 'CRBM', '(44) 99900-1004', TRUE
),
(
    51, 'Marina Oliveira', 'marina.recepcao@fitoclinic.test',
    '$2y$12$CD1cRw8gH3VUin83Rqqk8uGXdFSznZxsWvRvqMwtlbTctCHj.ZvgK',
    'recepcionista', NULL, NULL, NULL, TRUE
),
(
    61, 'Diego Rafael Costa', 'diego.costa@fitoclinic.test',
    '$2y$12$CD1cRw8gH3VUin83Rqqk8uGXdFSznZxsWvRvqMwtlbTctCHj.ZvgK',
    'profissional', 'EST-2026-0006', 'Registro profissional demonstrativo',
    '(44) 99900-0006', FALSE
);

-- ------------------------------------------------------------
-- 2. SEM FK: PACIENTES (6 registros)
-- Os quatro primeiros possuem os campos opcionais preenchidos;
-- os dois ultimos demonstram cadastros com dados opcionais ausentes.
-- ------------------------------------------------------------

INSERT INTO pacientes (
    id, nome, nome_social, cpf, rg, data_nascimento, genero,
    telefone, whatsapp, email, endereco, cidade, estado, cep,
    contato_emergencia, telefone_emergencia, alergias, observacoes
) VALUES
(
    19, 'Mariana Oliveira Santos', 'Mari Santos', '900.000.001-75',
    '12.345.678-1', '1989-04-18', 'feminino',
    '(44) 3030-2001', '(44) 99900-2001', 'mariana.santos@example.com',
    'Rua das Palmeiras, 145', 'Campo Mourao', 'PR', '87300-120',
    'Roberto Santos', '(44) 99900-2101',
    'Sensibilidade a fragrancias (dado ficticio).',
    'Paciente demonstrativa; prefere atendimento no periodo da manha.'
),
(
    21, 'Rafael Mendes Ferreira', 'Rafa Ferreira', '900.000.002-56',
    '23.456.789-2', '1994-09-07', 'masculino',
    '(44) 3030-2002', '(44) 99900-2002', 'rafael.ferreira@example.com',
    'Avenida Brasil, 820', 'Maringa', 'PR', '87013-000',
    'Luciana Mendes', '(44) 99900-2102',
    'Nenhuma alergia informada (registro de teste).',
    'Cadastro ficticio completo para validacao das telas.'
),
(
    31, 'Bianca Costa Ribeiro', 'Bia Ribeiro', '900.000.003-37',
    '34.567.890-3', '1978-12-22', 'feminino',
    '(43) 3030-2003', '(43) 99900-2003', 'bianca.ribeiro@example.com',
    'Rua Paranagua, 310', 'Londrina', 'PR', '86020-030',
    'Paulo Ribeiro', '(43) 99900-2103',
    'Relata sensibilidade a um produto cosmetico (dado ficticio).',
    'Prefere receber confirmacoes por WhatsApp.'
),
(
    41, 'Joao Pedro Alves', 'Joao Alves', '900.000.004-18',
    '45.678.901-4', '2001-02-11', 'masculino',
    '(44) 3030-2004', '(44) 99900-2004', 'joao.alves@example.com',
    'Rua dos Ipes, 77', 'Umuarama', 'PR', '87501-210',
    'Sandra Alves', '(44) 99900-2104',
    'Sem alergias relatadas no cadastro de teste.',
    'Primeiro atendimento; dados integralmente ficticios.'
),
(
    51, 'Ana Paula Carvalho', NULL, '900.000.005-07',
    NULL, '1965-06-30', 'nao_informado',
    '(45) 3030-2005', NULL, 'ana.carvalho@example.com',
    'Rua das Flores, 50', 'Cascavel', 'PR', '85801-100',
    NULL, NULL, NULL,
    'Cadastro parcial para testar campos opcionais ausentes.'
),
(
    61, 'Ricardo Nunes Teixeira', NULL, '900.000.006-80',
    NULL, NULL, 'masculino',
    NULL, '(44) 99900-2006', NULL,
    NULL, 'Campo Mourao', 'PR', NULL,
    'Contato familiar de teste', '(44) 99900-2106',
    NULL, 'Cadastro incompleto propositalmente para testes de formulario.'
);

-- ------------------------------------------------------------
-- 3. RELACAO 1:N: AGENDAMENTOS (3 registros)
-- Cada agendamento pertence a um paciente e a um profissional.
-- ------------------------------------------------------------

INSERT INTO agendamentos (
    id, paciente_id, profissional_id, data_agendamento,
    hora_inicio, hora_fim, tipo, situacao, origem_agendamento,
    teleconsulta, url_reuniao, observacoes
) VALUES
(
    19, 19, 21, '2026-11-04', '09:00:00', '10:00:00',
    'consulta', 'concluido', 'interno',
    FALSE, NULL, 'Consulta inicial demonstrativa; atendimento presencial.'
),
(
    21, 21, 31, '2026-11-05', '14:00:00', '15:00:00',
    'massagem', 'concluido', 'whatsapp',
    FALSE, NULL, 'Sessao demonstrativa; confirmacao recebida por WhatsApp.'
),
(
    31, 31, 41, '2026-11-06', '10:30:00', '11:15:00',
    'retorno', 'concluido', 'online',
    TRUE, 'https://meet.example.com/fito-clinic-teste-003',
    'Retorno remoto ficticio para testar o campo de teleconsulta.'
);

-- ------------------------------------------------------------
-- 4. RELACAO 1:N: PRONTUARIOS (3 registros)
-- Cada prontuario pertence a um paciente e a um profissional.
-- O terceiro registro tambem referencia um agendamento.
-- ------------------------------------------------------------

INSERT INTO prontuarios (
    id, paciente_id, profissional_id, agendamento_id, data_registro,
    queixa, historico_clinico, diagnostico, tratamento, observacoes,
    peso, altura, pressao_arterial
) VALUES
(
    19, 19, 21, 19, '2026-11-04 10:10:00',
    'Relata interesse em acompanhamento de cuidados estéticos.',
    'Primeira avaliação fictícia. Sem histórico relevante informado.',
    'Avaliação estética inicial registrada para demonstração.',
    'Plano demonstrativo de cuidados, sujeito à avaliação profissional.',
    'Registro de teste vinculado ao agendamento 1.',
    64.50, 1.65, '118/78'
),
(
    21, 21, 31, 21, '2026-11-05 15:10:00',
    'Relata tensão muscular após rotina de trabalho.',
    'Histórico fictício: rotina predominantemente sedentária.',
    'Registro de avaliação corporal demonstrativa.',
    'Sessão demonstrativa e orientações gerais registradas em ambiente de teste.',
    'Registro de teste vinculado ao agendamento 2.',
    82.30, 1.78, '122/80'
),
(
    31, 31, 41, 31, '2026-11-06 11:20:00',
    'Retorno para reavaliação de cuidados com a pele.',
    'Histórico fictício: acompanhamento estético iniciado anteriormente.',
    'Reavaliação estética demonstrativa, sem finalidade diagnóstica.',
    'Continuidade do plano demonstrativo conforme avaliação profissional.',
    'Registro de teleconsulta fictícia vinculado ao agendamento 3.',
    59.80, 1.62, '115/75'
);

-- ------------------------------------------------------------
-- 5. RELACAO 1:N: PRESCRICOES (3 registros)
-- Cada prescricao pertence a um paciente e a um profissional;
-- o prontuario e uma referencia opcional preenchida nos tres casos.
-- ------------------------------------------------------------

INSERT INTO prescricoes (
    id, paciente_id, profissional_id, prontuario_id, data_prescricao,
    situacao, assinatura_digital, assinado_em, observacoes
) VALUES
(
    19, 19, 21, 19, '2026-11-04 10:20:00',
    'emitida', 'ASSINATURA_FICTICIA_TESTE_PRESCRICAO_001',
    '2026-11-04 10:20:00',
    'Prescricao demonstrativa. Assinatura sem validade juridica ou clinica.'
),
(
    21, 21, 31, 21, '2026-11-05 15:20:00',
    'rascunho', NULL, NULL,
    'Rascunho ficticio para testar o estado de uma prescricao ainda nao emitida.'
),
(
    31, 31, 41, 31, '2026-11-06 11:30:00',
    'cancelada', NULL, NULL,
    'Registro cancelado de demonstracao; nao representa orientacao de tratamento.'
);

-- ------------------------------------------------------------
-- 6. RELACAO 1:N: ITENS_PRESCRICAO (3 registros)
-- Cada item pertence a uma prescricao. Esta tabela NAO e uma
-- associativa N:M: nao existe tabela separada de medicamentos
-- no conjunto de seis tabelas atualmente criado.
-- ------------------------------------------------------------

INSERT INTO itens_prescricao (
    id, prescricao_id, nome_medicamento, principio_ativo,
    apresentacao, concentracao, dosagem, via_administracao,
    frequencia, duracao, quantidade, instrucoes, dados_dosagem
) VALUES
(
    19, 19, 'Vitamina D3 - item demonstrativo', 'Colecalciferol (exemplo)',
    'Capsula', '1000 UI (valor ficticio)', '1 unidade (teste)',
    'Oral (exemplo)', 'Campo demonstrativo: uma vez ao dia',
    '30 dias (exemplo)', '30 capsulas (exemplo)',
    'DADO FICTICIO PARA TESTE DE SOFTWARE. NAO UTILIZAR COMO PRESCRICAO.',
    JSON_OBJECT(
        'unidade', 'capsula',
        'quantidade_por_dose', 1,
        'horarios', JSON_ARRAY('08:00'),
        'observacao', 'Dado ficticio; sem validade clinica.'
    )
),
(
    21, 21, 'Creme hidratante neutro - item demonstrativo', 'Base hidratante (exemplo)',
    'Creme', '100 g (valor ficticio)', 'Aplicacao demonstrativa',
    'Topica (exemplo)', 'Campo demonstrativo: conforme cadastro',
    '15 dias (exemplo)', '1 embalagem (exemplo)',
    'DADO FICTICIO PARA TESTE DE SOFTWARE. NAO UTILIZAR COMO PRESCRICAO.',
    JSON_OBJECT(
        'unidade', 'grama',
        'quantidade_por_aplicacao', 1,
        'horarios', JSON_ARRAY('09:00', '21:00'),
        'observacao', 'Registro de interface; nao e recomendacao de uso.'
    )
),
(
    31, 31, 'Gel cosmetico neutro - item demonstrativo', 'Composicao ilustrativa',
    'Gel', '50 g (valor ficticio)', 'Quantidade ilustrativa',
    'Topica (exemplo)', 'Campo demonstrativo: nao aplicavel',
    'Nao definido', '1 bisnaga (exemplo)',
    'ITEM DE PRESCRICAO CANCELADA. DADO FICTICIO, SEM USO CLINICO.',
    JSON_OBJECT(
        'unidade', 'grama',
        'quantidade', '50 g',
        'horarios', JSON_ARRAY(),
        'status_registro', 'cancelado',
        'observacao', 'Item ficticio vinculado a prescricao cancelada.'
    )
);

COMMIT;

-- Conferencia rapida da carga:
SELECT 'usuarios' AS tabela, COUNT(*) AS registros FROM usuarios WHERE id BETWEEN 11 AND 61
UNION ALL
SELECT 'pacientes', COUNT(*) FROM pacientes WHERE id BETWEEN 11 AND 61
UNION ALL
SELECT 'agendamentos', COUNT(*) FROM agendamentos WHERE id BETWEEN 11 AND 31
UNION ALL
SELECT 'prontuarios', COUNT(*) FROM prontuarios WHERE id BETWEEN 11 AND 31
UNION ALL
SELECT 'prescricoes', COUNT(*) FROM prescricoes WHERE id BETWEEN 11 AND 31
UNION ALL
SELECT 'itens_prescricao', COUNT(*) FROM itens_prescricao WHERE id BETWEEN 11 AND 31;

