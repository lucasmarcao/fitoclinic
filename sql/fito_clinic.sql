CREATE DATABASE fito_clinic
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE fito_clinic;

-- 1. USUÁRIOS / PROFISSIONAIS

CREATE TABLE usuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,

    papel ENUM(
        'admin',
        'profissional',
        'recepcionista'
    ) NOT NULL DEFAULT 'profissional',

    numero_registro VARCHAR(50) NULL,
    tipo_registro VARCHAR(50) NULL,

    telefone VARCHAR(30) NULL,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- 2. PACIENTES

CREATE TABLE pacientes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(150) NOT NULL,
    nome_social VARCHAR(150) NULL,

    cpf VARCHAR(14) NULL UNIQUE,
    rg VARCHAR(30) NULL,

    data_nascimento DATE NULL,
    genero ENUM(
        'masculino',
        'feminino',
        'outro',
        'nao_informado'
    ) DEFAULT 'nao_informado',

    telefone VARCHAR(30) NULL,
    whatsapp VARCHAR(30) NULL,
    email VARCHAR(150) NULL,

    endereco VARCHAR(255) NULL,
    cidade VARCHAR(100) NULL,
    estado CHAR(2) NULL,
    cep VARCHAR(10) NULL,

    contato_emergencia VARCHAR(150) NULL,
    telefone_emergencia VARCHAR(30) NULL,

    alergias TEXT NULL,
    observacoes TEXT NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- 3. AGENDA / AGENDAMENTOS / TELECONSULTA

CREATE TABLE agendamentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    paciente_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,

    data_agendamento DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NULL,

    tipo ENUM(
        'consulta',
        'massagem',
        'procedimento',
        'retorno',
        'outro'
    ) NOT NULL DEFAULT 'consulta',

    situacao ENUM(
        'agendado',
        'confirmado',
        'em_atendimento',
        'concluido',
        'cancelado',
        'faltou'
    ) NOT NULL DEFAULT 'agendado',

    origem_agendamento ENUM(
        'interno',
        'online',
        'whatsapp'
    ) NOT NULL DEFAULT 'interno',

    teleconsulta BOOLEAN NOT NULL DEFAULT FALSE,
    url_reuniao VARCHAR(500) NULL,

    observacoes TEXT NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_agendamentos_paciente
        FOREIGN KEY (paciente_id)
        REFERENCES pacientes(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_agendamentos_profissional
        FOREIGN KEY (profissional_id)
        REFERENCES usuarios(id)
        ON DELETE RESTRICT,

    INDEX idx_agendamentos_data (data_agendamento),
    INDEX idx_agendamentos_paciente (paciente_id),
    INDEX idx_agendamentos_profissional (profissional_id)
);


-- 4. PRONTUÁRIO ELETRÔNICO

CREATE TABLE prontuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    paciente_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,
    agendamento_id BIGINT UNSIGNED NULL,

    data_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    queixa TEXT NULL,
    historico_clinico TEXT NULL,
    diagnostico TEXT NULL,
    tratamento TEXT NULL,
    observacoes TEXT NULL,

    peso DECIMAL(6,2) NULL,
    altura DECIMAL(5,2) NULL,
    pressao_arterial VARCHAR(20) NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_prontuarios_paciente
        FOREIGN KEY (paciente_id)
        REFERENCES pacientes(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_prontuarios_profissional
        FOREIGN KEY (profissional_id)
        REFERENCES usuarios(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_prontuarios_agendamento
        FOREIGN KEY (agendamento_id)
        REFERENCES agendamentos(id)
        ON DELETE SET NULL,

    INDEX idx_prontuarios_paciente (paciente_id),
    INDEX idx_prontuarios_data (data_registro)
);


-- 5. PRESCRIÇÕES

CREATE TABLE prescricoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    paciente_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,
    prontuario_id BIGINT UNSIGNED NULL,

    data_prescricao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

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
        ON DELETE SET NULL
);


-- 6. ITENS DA PRESCRIÇÃO

CREATE TABLE itens_prescricao (
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

    -- Pode armazenar configuração de vitaminas,
    -- dosador, unidades, horários etc.
    dados_dosagem JSON NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_itens_prescricao_prescricao
        FOREIGN KEY (prescricao_id)
        REFERENCES prescricoes(id)
        ON DELETE CASCADE,

    INDEX idx_itens_prescricao_medicamento (nome_medicamento)
);


-- 7. DOCUMENTOS

CREATE TABLE documentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    paciente_id BIGINT UNSIGNED NULL,
    profissional_id BIGINT UNSIGNED NULL,

    tipo ENUM(
        'receita',
        'encaminhamento',
        'atestado',
        'documento',
        'outro'
    ) NOT NULL,

    titulo VARCHAR(200) NOT NULL,

    caminho_arquivo VARCHAR(500) NULL,

    hash_documento VARCHAR(255) NULL,

    assinado_digitalmente BOOLEAN NOT NULL DEFAULT FALSE,
    assinado_em DATETIME NULL,

    emitido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_documentos_paciente
        FOREIGN KEY (paciente_id)
        REFERENCES pacientes(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_documentos_profissional
        FOREIGN KEY (profissional_id)
        REFERENCES usuarios(id)
        ON DELETE SET NULL
);


-- 8. FINANCEIRO

CREATE TABLE transacoes_financeiras (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    paciente_id BIGINT UNSIGNED NULL,

    tipo ENUM(
        'receita',
        'despesa'
    ) NOT NULL,

    descricao VARCHAR(255) NOT NULL,

    valor DECIMAL(12,2) NOT NULL,

    data_vencimento DATE NULL,
    pago_em DATETIME NULL,

    situacao ENUM(
        'pendente',
        'pago',
        'cancelado'
    ) NOT NULL DEFAULT 'pendente',

    forma_pagamento ENUM(
        'dinheiro',
        'pix',
        'cartao_credito',
        'cartao_debito',
        'transferencia',
        'convenio',
        'outro'
    ) NULL,

    -- Dados básicos para futura integração TISS
    tiss_habilitado BOOLEAN NOT NULL DEFAULT FALSE,
    codigo_tiss VARCHAR(50) NULL,
    nome_convenio VARCHAR(150) NULL,
    numero_carteirinha VARCHAR(100) NULL,

    observacoes TEXT NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_transacoes_financeiras_paciente
        FOREIGN KEY (paciente_id)
        REFERENCES pacientes(id)
        ON DELETE SET NULL,

    INDEX idx_transacoes_financeiras_situacao (situacao),
    INDEX idx_transacoes_financeiras_vencimento (data_vencimento)
);


-- 9. ESTOQUE

CREATE TABLE itens_estoque (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(200) NOT NULL,

    categoria ENUM(
        'medicamento',
        'vitamina',
        'insumo',
        'cosmetico',
        'outro'
    ) NOT NULL DEFAULT 'insumo',

    descricao TEXT NULL,

    unidade VARCHAR(30) NOT NULL DEFAULT 'unidade',

    quantidade_atual DECIMAL(10,2) NOT NULL DEFAULT 0,
    quantidade_minima DECIMAL(10,2) NOT NULL DEFAULT 0,

    numero_lote VARCHAR(100) NULL,
    data_validade DATE NULL,

    preco_compra DECIMAL(12,2) NULL,
    preco_venda DECIMAL(12,2) NULL,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_itens_estoque_nome (nome),
    INDEX idx_itens_estoque_validade (data_validade)
);


-- 10. WHATSAPP / E-MAIL / LEMBRETES

CREATE TABLE comunicacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    paciente_id BIGINT UNSIGNED NOT NULL,
    agendamento_id BIGINT UNSIGNED NULL,

    canal ENUM(
        'whatsapp',
        'email'
    ) NOT NULL,

    tipo ENUM(
        'lembrete',
        'confirmacao',
        'retorno',
        'outro'
    ) NOT NULL,

    destinatario VARCHAR(200) NOT NULL,

    assunto VARCHAR(255) NULL,
    mensagem TEXT NOT NULL,

    situacao ENUM(
        'pendente',
        'enviado',
        'entregue',
        'lido',
        'erro'
    ) NOT NULL DEFAULT 'pendente',

    agendado_para DATETIME NULL,
    enviado_em DATETIME NULL,

    id_externo VARCHAR(255) NULL,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_comunicacoes_paciente
        FOREIGN KEY (paciente_id)
        REFERENCES pacientes(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_comunicacoes_agendamento
        FOREIGN KEY (agendamento_id)
        REFERENCES agendamentos(id)
        ON DELETE SET NULL,

    INDEX idx_comunicacoes_situacao (situacao),
    INDEX idx_comunicacoes_agendado (agendado_para)
);